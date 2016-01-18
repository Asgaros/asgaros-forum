<?php

if (!defined('ABSPATH')) exit;

class asgarosforum_admin {
    var $saved = false;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_init', array($this, 'save_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Taxonomy stuff
        add_action('init', 'asgarosforum::register_category_taxonomy');
        add_filter('parent_file', array($this, 'set_current_menu'));
        add_filter('manage_edit-asgarosforum-category_columns', array($this, 'manage_columns'));
        add_action('manage_asgarosforum-category_custom_column', array($this, 'manage_custom_columns'), 10, 3);
        add_action('asgarosforum-category_add_form_fields', array($this, 'add_category_form_fields'));
		add_action('asgarosforum-category_edit_form_fields', array($this, 'edit_category_form_fields'));
        add_action('create_asgarosforum-category', array($this, 'save_category_form_fields'));
        add_action('edit_asgarosforum-category', array($this, 'save_category_form_fields'));
        add_action('delete_asgarosforum-category', array($this, 'delete_category'), 10, 3);
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

        $columns['order'] = __('Order', 'asgaros-forum');
        $columns = apply_filters('asgarosforum_filter_manage_columns', $columns);

        return $columns;
    }

    function manage_custom_columns($out, $column, $term_id) {
        if ($column == 'order') {
            $order = get_term_meta($term_id, 'order', true);
            $out = sprintf('<p>%s</p>', esc_attr($order));
        } else {
            $out = apply_filters('asgarosforum_filter_manage_custom_columns', $out, $column, $term_id);
        }

        return $out;
    }

    function add_category_form_fields() {
        echo '<div class="form-field form-required term-order-wrap">';
            echo '<label>'.__('Order', 'asgaros-forum').'</label>';
            echo '<input type="text" name="category_order" value="1" />';
        echo '</div>';

        do_action('asgarosforum_action_add_category_form_fields');
    }

    function edit_category_form_fields($term) {
        echo '<tr class="form-field form-required term-order-wrap">';
            echo '<th scope="row">'.__('Order', 'asgaros-forum').'</th>';
            echo '<td>';
                $order = get_term_meta($term->term_id, 'order', true);
                echo '<input type="text" name="category_order" value="'.$order.'" />';
            echo '</td>';
        echo '</tr>';

        do_action('asgarosforum_action_edit_category_form_fields', $term);
    }

    function save_category_form_fields($term_id) {
        $new_order = isset($_POST['category_order']) ? $_POST['category_order'] : '';

        if (!empty($new_order)) {
            update_term_meta($term_id, 'order', $new_order);
        }

        do_action('asgarosforum_action_save_category_form_fields', $term_id);
    }

    public function add_admin_pages() {
        $category_taxonomy = get_taxonomy('asgarosforum-category');

        add_menu_page(__('Forum', 'asgaros-forum'), __('Forum', 'asgaros-forum'), 'administrator', 'asgarosforum', array($this, 'options_page'), 'dashicons-clipboard');
        add_submenu_page('asgarosforum', __('Options', 'asgaros-forum'), __('Options', 'asgaros-forum'), 'administrator', 'asgarosforum', array($this, 'options_page'));
        add_submenu_page('asgarosforum', __('Categories', 'asgaros-forum'), __('Categories', 'asgaros-forum'), 'administrator', 'edit-tags.php?taxonomy='.$category_taxonomy->name, null);
        add_submenu_page('asgarosforum', __('Forums', 'asgaros-forum'), __('Forums', 'asgaros-forum'), 'administrator', 'asgarosforum-structure', array($this, 'forums_page'));
    }

    public function enqueue_admin_scripts($hook) {
        global $submenu_file, $asgarosforum_directory;
        $l10n_vars = array('remove_forum_warning' => __('WARNING: Deleting this Forum will also PERMANENTLY DELETE ALL Threads, and Replies associated with it!!! Are you sure you want to delete this Forum???', 'asgaros-forum'));

        if (strstr($hook, 'asgarosforum') !== false || $submenu_file == 'edit-tags.php?taxonomy=asgarosforum-category') {
            wp_enqueue_style('asgarosforum-admin-css', $asgarosforum_directory.'admin/admin.css');
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('asgarosforum-admin-js', $asgarosforum_directory.'admin/admin.js', array('wp-color-picker'), false, true);
            wp_localize_script('asgarosforum-admin-js', 'asgarosforum_admin', $l10n_vars);
        }
    }

    public function save_settings() {
        if (isset($_POST['af_options_submit'])) {
            $this->save_options();
        } else if (isset($_POST['af_forums_submit'])) {
            $this->save_forums();
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
                    $saved_ops[$k] = ((int)$_POST[$k] > 0) ? (int)$_POST[$k] : $v;
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
        $asgarosforum->options = get_option('asgarosforum_options', array());
        $this->saved = true;
    }

    /* STRUCTURE */
    public function forums_page() {
        global $asgarosforum;
        $categories = $asgarosforum->get_categories(true);

        require('views/forums.php');
    }

    public function save_forums() {
        global $asgarosforum, $wpdb;
        $order = 1;
        $listed_forums = array();
        $forums = array();
        $categories = $asgarosforum->get_categories(true);

        foreach ($categories as $category) {
            if (isset($_POST['forum_id'][$category->term_id]) && !empty($_POST['forum_id'][$category->term_id])) {
                foreach ($_POST['forum_id'][$category->term_id] as $key => $forum_id) {
                    $name = trim(stripslashes($_POST['forum_name'][$category->term_id][$key]));
                    $description = trim(stripslashes($_POST['forum_description'][$category->term_id][$key]));

                    if (empty($name)) {
                        if ($forum_id != 'new') {
                            $listed_forums[] = $forum_id;
                        }

                        continue;
                    }

                    if ($forum_id == 'new') {
                        $wpdb->insert($asgarosforum->table_forums, array('name' => $name, 'description' => $description, 'sort' => $order, 'parent_id' => $category->term_id), array('%s', '%s', '%d', '%d'));
                        $listed_forums[] = $wpdb->insert_id;
                    } else {
                        $query = "UPDATE {$asgarosforum->table_forums} SET name = %s, description = %s, sort = %d, parent_id = %d WHERE id = %d;";
                        $wpdb->query($wpdb->prepare($query, $name, $description, $order, $category->term_id, $forum_id));
                        $listed_forums[] = $forum_id;
                    }

                    $order++;
                }
            }
        }

        $listed_forums = implode(',', $listed_forums);

        if (empty($listed_forums)) {
            $forums = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_forums};");
        } else {
            $forums = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_forums} WHERE id NOT IN ({$listed_forums});");
        }

        if (!empty($forums)) {
            foreach ($forums as $forum) {
                $this->delete_forum($forum);
            }
        }

        $this->saved = true;
    }

    public function delete_category($term_id, $term_taxonomy_id, $deleted_term) {
        global $wpdb, $asgarosforum;

        $forums = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_forums} WHERE parent_id = {$term_id};");

        if (!empty($forums)) {
            foreach ($forums as $forum) {
                $this->delete_forum($forum);
            }
        }
    }

    public function delete_forum($forum_id) {
        global $wpdb, $asgarosforum;

        $threads = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_threads} WHERE parent_id = {$forum_id};");

        if (!empty($threads)) {
            foreach ($threads as $thread) {
                $asgarosforum->delete_thread($thread, true);
            }
        }

        $wpdb->query("DELETE FROM {$asgarosforum->table_forums} WHERE id = {$forum_id};");
    }
}
?>
