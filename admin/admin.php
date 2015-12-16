<?php
class asgarosforum_admin
{
    var $saved = false;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_init', array($this, 'save_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    // Add admin pages
    public function add_admin_pages() {
        add_menu_page(__("Forum - Options", "asgarosforum"), "Forum", "administrator", "asgarosforum", array($this, 'options_page'), 'dashicons-clipboard');
        add_submenu_page("asgarosforum", __("Forum - Options", "asgarosforum"), __("Options", "asgarosforum"), "administrator", 'asgarosforum', array($this, 'options_page'));
        add_submenu_page("asgarosforum", __("Structure - Categories & Forums", "asgarosforum"), __("Structure", "asgarosforum"), "administrator", 'asgarosforum-structure', array($this, 'structure_page'));
    }

    public function enqueue_admin_scripts($hook) {
        global $asgarosforum_directory;
        $l10n_vars = array('remove_category_warning' => __('WARNING: Deleting this Category will also PERMANENTLY DELETE ALL Forums, Threads, and Replies associated with it!!! Are you sure you want to delete this Category???', 'asgarosforum'),
            'remove_forum_warning' => __('WARNING: Deleting this Forum will also PERMANENTLY DELETE ALL Threads, and Replies associated with it!!! Are you sure you want to delete this Forum???', 'asgarosforum'));

        // Let's only load our shiz on asgarosforum admin pages
        if (strstr($hook, 'asgarosforum') !== false) {
            wp_enqueue_style('asgarosforum-admin-css', $asgarosforum_directory . "admin/admin.css");
            wp_enqueue_script('asgarosforum-admin-js', $asgarosforum_directory . "admin/admin.js", array('jquery-ui-sortable'));
            wp_localize_script('asgarosforum-admin-js', 'asgarosforum_admin', $l10n_vars);
        }
    }

    public function save_settings() {
        if (isset($_POST['af_options_submit']) && !empty($_POST['af_options_submit'])) {
            $this->save_options();
        } else if (isset($_POST['af_categories_save']) && !empty($_POST['af_categories_save'])) {
            $this->save_categories();
        } else if (isset($_POST['af_forums_save']) && !empty($_POST['af_forums_save'])) {
            $this->save_forums();
        } else {
            return;
        }
    }

    /* OPTIONS */
    public function options_page() {
        global $asgarosforum;
        require('views/options.php');
    }

    public function save_options() {
        global $asgarosforum;
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
        $asgarosforum->options = array_merge($asgarosforum->options_default, get_option('asgarosforum_options', array()));
        $this->saved = true;
    }

    /* STRUCTURE */
    public function structure_page() {
        global $asgarosforum;
        $categories = $asgarosforum->get_categories(true);

        if (isset($_GET['action']) && !empty($_GET['action']) && $_GET['action'] == 'forums') {
            require('views/structure_forums.php');
        } else {
            require('views/structure_categories.php');
        }
    }

    public function save_categories() {
        global $asgarosforum, $wpdb;
        $order = 1;
        $listed_categories = array();
        $category_ids = array();

        if (isset($_POST['af_category_id']) && !empty($_POST['af_category_id'])) {
            foreach ($_POST['af_category_id'] as $key => $value) {
                $id = $_POST['af_category_id'][$key];
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

                $order++;
            }
        }

        // Delete categories that the user removed from the list
        $remove_categories = implode(',', $listed_categories);

        if (empty($remove_categories)) {
            $category_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_categories}");
        } else {
            $category_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_categories} WHERE id NOT IN ({$remove_categories})");
        }

        if (!empty($category_ids)) {
            foreach ($category_ids as $cid) {
                $this->delete_category($cid);
            }
        }

        do_action('asgarosforum_admin_after_save_categories', $listed_categories);

        $this->saved = true;
    }

    public function save_forums() {
        global $asgarosforum, $wpdb;
        $order = 1;
        $listed_forums = array();
        $forum_ids = array();
        $categories = $asgarosforum->get_categories(true);

        if (empty($categories)) { // This should never happen, but just in case
            return;
        }

        foreach ($categories as $category) {
            if (isset($_POST['af_forum_id'][$category->id]) && !empty($_POST['af_forum_id'][$category->id])) {
                foreach ($_POST['af_forum_id'][$category->id] as $key => $value) {
                    $id = $_POST['af_forum_id'][$category->id][$key];
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

                    $order++;
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
                $this->delete_forum($fid);
            }
        }

        $this->saved = true;
    }

    public function delete_category($cid) {
        global $wpdb, $asgarosforum;

        // First delete all associated forums
        $forum_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_forums} WHERE parent_id = {$cid}");

        if (!empty($forum_ids)) {
            foreach ($forum_ids as $fid) {
                $this->delete_forum($fid);
            }
        }

        $wpdb->query("DELETE FROM {$asgarosforum->table_categories} WHERE id = {$cid}");
    }

    public function delete_forum($fid) {
        global $wpdb, $asgarosforum;

        // First delete all associated threads
        $thread_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_threads} WHERE parent_id = {$fid}");

        if (!empty($thread_ids)) {
            foreach ($thread_ids as $tid) {
                $this->delete_thread($tid);
            }
        }

        $wpdb->query("DELETE FROM {$asgarosforum->table_forums} WHERE id = {$fid}");
    }

    public function delete_thread($tid) {
        global $wpdb, $asgarosforum;

        // Delete uploads
        $posts = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$asgarosforum->table_posts} WHERE parent_id = %d;", $tid));
        foreach ($posts as $post) {
            $asgarosforum->remove_post_files($post->id);
        }

        $wpdb->query("DELETE FROM {$asgarosforum->table_posts} WHERE parent_id = {$tid}");
        $wpdb->query("DELETE FROM {$asgarosforum->table_threads} WHERE id = {$tid}");
    }
}
?>
