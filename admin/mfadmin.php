<?php
if (!class_exists("AFAdmin"))
{
    class AFAdmin
    {
        public static function load_hooks()
        {
            add_action("admin_menu", 'AFAdmin::add_admin_pages');
            add_action('admin_init', 'AFAdmin::save_settings');
            add_action('admin_enqueue_scripts', 'AFAdmin::enqueue_admin_scripts');
        }

        // Add admin pages
        public static function add_admin_pages() {
            add_menu_page(__("Forum - Options", "asgarosforum"), "Forum", "administrator", "asgarosforum", 'AFAdmin::options_page', WPFURL . "admin/images/logo.png");
            add_submenu_page("asgarosforum", __("Forum - Options", "asgarosforum"), __("Options", "asgarosforum"), "administrator", 'asgarosforum', 'AFAdmin::options_page');
            add_submenu_page("asgarosforum", __("Structure - Categories & Forums", "asgarosforum"), __("Structure", "asgarosforum"), "administrator", 'asgarosforum-structure', 'AFAdmin::structure_page');
        }

        public static function enqueue_admin_scripts($hook)
        {
            $plug_url = plugin_dir_url(__FILE__) . '../';
            $l10n_vars = array('remove_category_warning' => __('WARNING: Deleting this Category will also PERMANENTLY DELETE ALL Forums, Topics, and Replies associated with it!!! Are you sure you want to delete this Category???', 'asgarosforum'),
                'remove_forum_warning' => __('WARNING: Deleting this Forum will also PERMANENTLY DELETE ALL Topics, and Replies associated with it!!! Are you sure you want to delete this Forum???', 'asgarosforum'));

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

            foreach ($asgarosforum->options_default as $k => $v) {
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

            update_option('asgarosforum_options', $saved_ops);
            wp_redirect(admin_url('admin.php?page=asgarosforum&saved=true'));
            exit();
        }

        /* STRUCTURE */
        public static function structure_page()
        {
            global $asgarosforum;
            $saved = (isset($_GET['saved']) && $_GET['saved'] == 'true');
            $categories = $asgarosforum->get_categories();

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

                    if (empty($name)) {
                        if ($id != 'new') {
                            $listed_categories[] = $id;
                        }

                        continue;
                    }

                    if ($id == 'new') { // Save new category
                        $wpdb->insert($asgarosforum->table_categories, array('name' => $name, 'sort' => $order), array('%s', '%d'));
                        $listed_categories[] = $wpdb->insert_id;
                    } else { // Update existing category
                        $q = "UPDATE {$asgarosforum->table_categories} SET name = %s, sort = %d WHERE id = %d";
                        $wpdb->query($wpdb->prepare($q, $name, $order, $id));
                        $listed_categories[] = $id;
                    }

                    $order--;
                }
            }

            // Delete categories that the user removed from the list
            $listed_categories = implode(',', $listed_categories);

            if (empty($listed_categories)) {
                $category_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_categories}");
            } else {
                $category_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_categories} WHERE id NOT IN ({$listed_categories})");
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
            $categories = $asgarosforum->get_categories();

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
                            $wpdb->insert($asgarosforum->table_forums, array('name' => $name, 'description' => $description, 'sort' => $order, 'parent_id' => $category->id), array('%s', '%s', '%d', '%d'));
                            $listed_forums[] = $wpdb->insert_id;
                        } else { // Update existing forum
                            $q = "UPDATE {$asgarosforum->table_forums} SET name = %s, description = %s, sort = %d, parent_id = %d WHERE id = %d";
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
                $forum_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_forums}");
            } else {
                $forum_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_forums} WHERE id NOT IN ({$listed_forums})");
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
            $forum_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_forums} WHERE parent_id = {$cid}");

            if (!empty($forum_ids)) {
                foreach ($forum_ids as $fid) {
                    self::delete_forum($fid);
                }
            }

            $wpdb->query("DELETE FROM {$asgarosforum->table_categories} WHERE id = {$cid}");
        }

        public static function delete_forum($fid)
        {
            global $wpdb, $asgarosforum;

            // First delete all associated threads
            $thread_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_threads} WHERE parent_id = {$fid}");

            if (!empty($thread_ids)) {
                foreach ($thread_ids as $tid) {
                    self::delete_thread($tid);
                }
            }

            $wpdb->query("DELETE FROM {$asgarosforum->table_forums} WHERE id = {$fid}");
        }

        public static function delete_thread($tid)
        {
            global $wpdb, $asgarosforum;

            $wpdb->query("DELETE FROM {$asgarosforum->table_posts} WHERE parent_id = {$tid}");
            $wpdb->query("DELETE FROM {$asgarosforum->table_threads} WHERE id = {$tid}");
        }
    }
}
?>
