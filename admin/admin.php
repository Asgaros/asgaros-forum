<?php
class asgarosforum_admin {
    var $saved = false;

    public function __construct() {
        // Taxonomy stuff ...
        add_action('init', 'asgarosforum::register_category_taxonomy');
        add_filter('parent_file', array($this, 'set_current_menu'));
        add_filter('manage_edit-asgarosforum-category_columns', array($this, 'manage_columns'));
        add_action('admin_head', array($this, 'remove_slug'));
        add_action('delete_term', array($this, 'delete_category'), 10, 4);

        add_action('asgarosforum-category_add_form_fields', array($this, 'add_category_form_fields'));
		add_action('asgarosforum-category_edit_form_fields', array($this, 'edit_category_form_fields'));
        add_action('create_asgarosforum-category', array($this, 'save_category_form_fields'));
        add_action('edit_asgarosforum-category', array($this, 'save_category_form_fields'));




        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_init', array($this, 'save_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    function set_current_menu($parent_file) {
        global $submenu_file;

        if ($submenu_file == 'edit-tags.php?taxonomy=asgarosforum-category') {
            $parent_file = 'asgarosforum';
        }

        return $parent_file;
    }

    function manage_columns($columns) {
        unset($columns['description'], $columns['slug'], $columns['posts']);

        $columns = apply_filters('asgarosforum_filter_manage_columns', $columns);

        return $columns;
    }

    function add_category_form_fields() {
        do_action('asgarosforum_action_add_category_form_fields');
    }

    function edit_category_form_fields($term) {
        do_action('asgarosforum_action_edit_category_form_fields', $term);
    }

    function save_category_form_fields($term_id) {
        do_action('asgarosforum_action_save_category_form_fields', $term_id);
    }

    function remove_slug() {
        global $submenu_file;

        if ($submenu_file == 'edit-tags.php?taxonomy=asgarosforum-category') {
            echo '<style type="text/css">.term-name-wrap p, .term-slug-wrap, .term-description-wrap { display: none; }</style>';
            echo '<script type="text/javascript">jQuery(document).ready(function($) { $(".inline-edit-col input[name=slug]").parents("label").hide(); });</script>';
        }
    }

    // Add admin pages
    public function add_admin_pages() {
        $category_taxonomy = get_taxonomy('asgarosforum-category');

        add_menu_page(__("Forum - Options", "asgarosforum"), "Forum", "administrator", 'asgarosforum', array($this, 'options_page'), 'dashicons-clipboard');
        add_submenu_page("asgarosforum", __('Options', 'asgarosforum'), __('Options', 'asgarosforum'), "administrator", 'asgarosforum', array($this, 'options_page'));
        add_submenu_page('asgarosforum', __('Categories', 'asgarosforum'), __('Categories', 'asgarosforum'), 'administrator', 'edit-tags.php?taxonomy='.$category_taxonomy->name, null);
        add_submenu_page("asgarosforum", __('Forums', 'asgarosforum'), __('Forums', 'asgarosforum'), 'administrator', 'asgarosforum-structure', array($this, 'structure_page'));
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

        require('views/structure_forums.php');
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
            if (isset($_POST['af_forum_id'][$category->term_id]) && !empty($_POST['af_forum_id'][$category->term_id])) {
                foreach ($_POST['af_forum_id'][$category->term_id] as $key => $value) {
                    $id = $_POST['af_forum_id'][$category->term_id][$key];
                    $name = stripslashes($_POST['forum_name'][$category->term_id][$key]);
                    $description = stripslashes($_POST['forum_description'][$category->term_id][$key]);

                    if (empty($name)) {
                        if ($id != 'new') {
                            $listed_forums[] = $id;
                        }

                        continue;
                    }

                    if ($id == 'new') { // Save new forum
                        $wpdb->insert($asgarosforum->table_forums, array('name' => $name, 'description' => $description, 'sort' => $order, 'parent_id' => $category->term_id), array('%s', '%s', '%d', '%d'));
                        $listed_forums[] = $wpdb->insert_id;
                    } else { // Update existing forum
                        $q = "UPDATE {$asgarosforum->table_forums} SET name = %s, description = %s, sort = %d, parent_id = %d WHERE id = %d";
                        $wpdb->query($wpdb->prepare($q, $name, $description, $order, $category->term_id, $id));
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

    public function delete_category($term, $tt_id, $taxonomy, $deleted_term) {
        global $wpdb, $asgarosforum;

        // Delete all associated forums
        $forum_ids = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_forums} WHERE parent_id = {$term}");

        if (!empty($forum_ids)) {
            foreach ($forum_ids as $fid) {
                $this->delete_forum($fid);
            }
        }
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
