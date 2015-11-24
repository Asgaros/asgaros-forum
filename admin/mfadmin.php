<?php
if (!class_exists("AFAdmin"))
{
    class AFAdmin
    {
        public static function load_hooks()
        {
            add_action('admin_init', 'AFAdmin::save_settings');
            add_action('admin_enqueue_scripts', 'AFAdmin::enqueue_admin_scripts');
        }

        public static function enqueue_admin_scripts($hook)
        {
            $plug_url = plugin_dir_url(__FILE__) . '../';
            $l10n_vars = array('remove_category_warning' => __('WARNING: Deleting this Category will also PERMANENTLY DELETE ALL Forums, Topics, and Replies associated with it!!! Are you sure you want to delete this Category???', 'asgarosforum'),
                'remove_forum_warning' => __('WARNING: Deleting this Forum will also PERMANENTLY DELETE ALL Topics, and Replies associated with it!!! Are you sure you want to delete this Forum???', 'asgarosforum'),
                'remove_user_group_warning' => __('Are you sure you want to remove this Group?', 'asgarosforum'));

            // Let's only load our shiz on asgarosforum admin pages
            if (strstr($hook, 'asgarosforum') !== false) {
                wp_enqueue_style('asgarosforum-admin-css', $plug_url . "admin/admin.css");
                wp_enqueue_script('asgarosforum-admin-js', $plug_url . "admin/admin.js", array('jquery-ui-sortable'));
                wp_localize_script('asgarosforum-admin-js', 'AFAdmin', $l10n_vars);
            }
        }

        public static function save_settings()
        {
            if (isset($_POST['mf_options_submit']) && !empty($_POST['mf_options_submit'])) {
                self::save_options();
            } else if (isset($_POST['mf_user_groups_save']) && !empty($_POST['mf_user_groups_save'])) {
                self::save_user_groups();
            } else if (isset($_POST['usergroup_users_save']) && !empty($_POST['usergroup_users_save'])) {
                self::save_user_in_user_group();
            } else if (isset($_GET['action']) && !empty($_GET['action']) && $_GET['action'] == 'deluser') {
                self::save_user_in_user_group();
            } else if (isset($_POST['mf_categories_save']) && !empty($_POST['mf_categories_save'])) {
                self::process_save_categories();
            } else if (isset($_POST['mf_forums_save']) && !empty($_POST['mf_forums_save'])) {
                self::process_save_forums();
            } else {
                return;
            }
        }

        /* OPTIONS */
        public static function options_page()
        {
            global $asgarosforum;
            $saved = (isset($_GET['saved']) && $_GET['saved'] == 'true');
            require('views/options_page.php');
        }

        public static function save_options() {
            global $wpdb, $asgarosforum;
            $saved_ops = array();

            foreach ($asgarosforum->default_ops as $k => $v) {
                if (isset($_POST[$k]) && !empty($_POST[$k])) {
                    if (is_numeric($v)) {
                        $saved_ops[$k] = (int)$_POST[$k];
                    } else if (is_bool($v)) {
                        $saved_ops[$k] = true;
                    } else {
                        $saved_ops[$k] = esc_sql(stripslashes($_POST[$k]));
                    }
                } else {
                    if (is_numeric($v)) {
                        $saved_ops[$k] = $v;
                    } else if (is_bool($v)) {
                        $saved_ops[$k] = false;
                    } else {
                        $saved_ops[$k] = '';
                    }
                }
            }

            // Set some stuff that isn't on the options page
            $saved_ops['forum_db_version'] = $asgarosforum->options['forum_db_version'];

            update_option('asgarosforum_options', $saved_ops);
            wp_redirect(admin_url('admin.php?page=asgarosforum&saved=true'));
            exit();
        }

        /* USERGROUPS */
        public static function user_groups_page()
        {
            global $asgarosforum;
            $saved = (isset($_GET['saved']) && $_GET['saved'] == 'true');
            $user_groups = $asgarosforum->get_usergroups();

            if (isset($_GET['action']) && $_GET['action'] == 'users') {
                if (isset($_GET['groupid'])) {
                    $usergroup = $asgarosforum->get_usergroups($_GET['groupid']);
                    $usergroup_users = $asgarosforum->get_members($_GET['groupid']);

                    if (!empty($usergroup)) {
                        require('views/user_groups_users_page.php');
                    } else {
                        require('views/user_groups_page.php');
                    }
                } else {
                    require('views/user_groups_page.php');
                }
            } else {
                require('views/user_groups_page.php');
            }
        }

        public static function save_user_groups()
        {
            global $asgarosforum, $wpdb;
            $listed_user_groups = array();
            $user_group_ids = array();

            if (isset($_POST['user_group_name']) && !empty($_POST['user_group_name'])) {
                foreach ($_POST['user_group_name'] as $i => $v) {
                    $id = $_POST['mf_user_group_id'][$i];
                    $name = stripslashes($_POST['user_group_name'][$i]);
                    $description = stripslashes($_POST['user_group_description'][$i]);

                    if (empty($name)) { // If no name, don't save this User Group
                        if ($id != 'new') {
                            $listed_user_groups[] = $id;
                        }

                        continue;
                    }

                    if ($id == 'new') { // Create a new User Group
                        $wpdb->insert($asgarosforum->t_usergroups, array('name' => $name, 'description' => $description), array('%s', '%s'));
                        $listed_user_groups[] = $wpdb->insert_id;
                    } else { // Update an existing User Group
                        $q = "UPDATE {$asgarosforum->t_usergroups} SET name = %s, description = %s WHERE id = %d";
                        $wpdb->query($wpdb->prepare($q, $name, $description, $id));
                        $listed_user_groups[] = $id;
                    }
                }
            }

            // Delete user groups that the user removed from the list
            $listed_user_groups = implode(',', $listed_user_groups);

            if (empty($listed_user_groups)) {
                $user_group_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->t_usergroups}");
            } else {
                $user_group_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->t_usergroups} WHERE id NOT IN ({$listed_user_groups})");
            }

            if (!empty($user_group_ids)) {
                foreach ($user_group_ids as $ugid) {
                    self::delete_usergroup($ugid);
                }
            }

            wp_redirect(admin_url('admin.php?page=asgarosforum-user-groups&saved=true'));
            exit();
        }

        public static function delete_usergroup($ugid)
        {
            global $asgarosforum, $wpdb;

            $wpdb->query("DELETE FROM {$asgarosforum->t_usergroup2user} WHERE group_id = {$ugid}");
            $wpdb->query("DELETE FROM {$asgarosforum->t_usergroups} WHERE id = {$ugid}");

            // Remove this group from categories too
            $cats = $wpdb->get_results("SELECT * FROM {$asgarosforum->t_categories}");

            if (!empty($cats)) {
                foreach ($cats as $cat) {
                    $usergroups = (array)unserialize($cat->usergroups);

                    if (in_array($ugid, $usergroups)) {
                        $usergroups = serialize(array_diff($usergroups, array($ugid)));
                        $wpdb->query("UPDATE {$asgarosforum->t_categories} SET usergroups = '{$usergroups}' WHERE id = {$cat->id}");
                    }
                }
            }
        }

        function save_user_in_user_group()
        {
            global $asgarosforum, $wpdb;
            $groupID = $_GET['groupid'];

            if (isset($_POST['usergroup_user_add_new']) && !empty($_POST['usergroup_user_add_new'])) {
                $user = trim(stripslashes($_POST['usergroup_user_add_new']));
                $userID = username_exists($user);

                if ($userID) {
                    if (!$asgarosforum->is_user_ingroup($userID, $groupID)) {
                        $wpdb->insert($asgarosforum->t_usergroup2user, array('user_id' => $userID, 'group_id' => $groupID), array('%d', '%d'));
                    }
                }
            }

            if (isset($_GET['action']) && $_GET['action'] == 'deluser') {
                $userID = $_GET['user_id'];
                $wpdb->query("DELETE FROM {$asgarosforum->t_usergroup2user} WHERE user_id = {$userID} AND group_id = {$groupID}");
            }

            wp_redirect(admin_url('admin.php?page=asgarosforum-user-groups&action=users&groupid='.$groupID.'&saved=true'));
            exit();
        }

        /* STRUCTURE */
        public static function structure_page()
        {
            global $asgarosforum;
            $saved = (isset($_GET['saved']) && $_GET['saved'] == 'true');
            $categories = $asgarosforum->get_groups();

            if (isset($_GET['action']) && !empty($_GET['action']) && $_GET['action'] == 'forums') {
                require('views/structure_page_forums.php');
            } else {
                require('views/structure_page_categories.php');
            }
        }

        public static function process_save_categories()
        {
            global $asgarosforum, $wpdb;
            $order = 10000; // Order is DESC for some reason
            $listed_categories = array();
            $category_ids = array();

            if (isset($_POST['mf_category_id']) && !empty($_POST['mf_category_id'])) {
                foreach ($_POST['mf_category_id'] as $key => $value) {
                    $id = $_POST['mf_category_id'][$key];
                    $name = stripslashes($_POST['category_name'][$key]);
                    $description = stripslashes($_POST['category_description'][$key]);

                    if (empty($name)) {
                        if ($id != 'new') {
                            $listed_categories[] = $id;
                        }

                        continue;
                    }

                    if ($id == 'new') { // Save new category
                        $wpdb->insert($asgarosforum->t_categories, array('name' => $name, 'description' => $description, 'sort' => $order), array('%s', '%s', '%d'));
                        $listed_categories[] = $wpdb->insert_id;
                    } else { // Update existing category
                        $usergroups = '';

                        if (isset($_POST['category_usergroups_'.$id]) && !empty($_POST['category_usergroups_'.$id])) {
                            $usergroups = serialize((array)$_POST['category_usergroups_'.$id]);
                        }

                        $q = "UPDATE {$asgarosforum->t_categories} SET name = %s, description = %s, sort = %d, usergroups = %s WHERE id = %d";
                        $wpdb->query($wpdb->prepare($q, $name, $description, $order, $usergroups, $id));
                        $listed_categories[] = $id;
                    }

                    $order--;
                }
            }

            // Delete categories that the user removed from the list
            $listed_categories = implode(',', $listed_categories);

            if (empty($listed_categories)) {
                $category_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->t_categories}");
            } else {
                $category_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->t_categories} WHERE id NOT IN ({$listed_categories})");
            }

            if (!empty($category_ids)) {
                foreach ($category_ids as $cid) {
                    self::delete_category($cid);
                }
            }

            wp_redirect(admin_url('admin.php?page=asgarosforum-structure&saved=true'));
            exit();
        }

        public static function process_save_forums()
        {
            global $asgarosforum, $wpdb;
            $order = 100000; // Order is DESC for some reason
            $listed_forums = array();
            $forum_ids = array();
            $categories = $asgarosforum->get_groups();

            if (empty($categories)) { // This should never happen, but just in case
                return;
            }

            foreach ($categories as $category) {
                if (isset($_POST['mf_forum_id'][$category->id]) && !empty($_POST['mf_forum_id'][$category->id])) {
                    foreach ($_POST['mf_forum_id'][$category->id] as $key => $value) {
                        $id = $_POST['mf_forum_id'][$category->id][$key];
                        $name = stripslashes($_POST['forum_name'][$category->id][$key]);
                        $description = stripslashes($_POST['forum_description'][$category->id][$key]);

                        if (empty($name)) {
                            if ($id != 'new') {
                                $listed_forums[] = $id;
                            }

                            continue;
                        }

                        if ($id == 'new') { // Save new forum
                            $wpdb->insert($asgarosforum->t_forums, array('name' => $name, 'description' => $description, 'sort' => $order, 'parent_id' => $category->id), array('%s', '%s', '%d', '%d'));
                            $listed_forums[] = $wpdb->insert_id;
                        } else { // Update existing forum
                            $q = "UPDATE {$asgarosforum->t_forums} SET name = %s, description = %s, sort = %d, parent_id = %d WHERE id = %d";
                            $wpdb->query($wpdb->prepare($q, $name, $description, $order, $category->id, $id));
                            $listed_forums[] = $id;
                        }

                        $order--;
                    }
                }
            }

            // Delete forums that the user removed from the list
            $listed_forums = implode(',', $listed_forums);

            if (empty($listed_forums)) {
                $forum_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->t_forums}");
            } else {
                $forum_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->t_forums} WHERE id NOT IN ({$listed_forums})");
            }

            if (!empty($forum_ids)) {
                foreach ($forum_ids as $fid) {
                    self::delete_forum($fid);
                }
            }

            wp_redirect(admin_url('admin.php?page=asgarosforum-structure&action=forums&saved=true'));
            exit();
        }

        public static function delete_category($cid)
        {
            global $wpdb, $asgarosforum;

            // First delete all associated forums
            $forum_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->t_forums} WHERE parent_id = {$cid}");

            if (!empty($forum_ids)) {
                foreach ($forum_ids as $fid) {
                    self::delete_forum($fid);
                }
            }

            $wpdb->query("DELETE FROM {$asgarosforum->t_categories} WHERE id = {$cid}");
        }

        public static function delete_forum($fid)
        {
            global $wpdb, $asgarosforum;

            // First delete all associated topics
            $topic_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->t_threads} WHERE parent_id = {$fid}");

            if (!empty($topic_ids)) {
                foreach ($topic_ids as $tid) {
                    self::delete_topic($tid);
                }
            }

            $wpdb->query("DELETE FROM {$asgarosforum->t_forums} WHERE id = {$fid}");
        }

        public static function delete_topic($tid)
        {
            global $wpdb, $asgarosforum;

            $wpdb->query("DELETE FROM {$asgarosforum->t_posts} WHERE parent_id = {$tid}");
            $wpdb->query("DELETE FROM {$asgarosforum->t_threads} WHERE id = {$tid}");
        }
    }
}
?>
