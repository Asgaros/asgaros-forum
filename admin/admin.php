<?php

if (!defined('ABSPATH')) exit;

class asgarosforum_admin {
    var $saved = false;

	/**
	 * Array of available themes selectable
	 *
	 * @var array
	 */
	protected $themes = array();

    function __construct() {
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_init', array($this, 'save_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Taxonomy stuff
        add_action('init', 'asgarosforum::register_category_taxonomy');
        add_filter('parent_file', array($this, 'set_current_menu'));
        add_filter('submenu_file', array($this, 'set_current_submenu'));
        add_filter('manage_edit-asgarosforum-category_columns', array($this, 'manage_columns'));
        add_action('manage_asgarosforum-category_custom_column', array($this, 'manage_custom_columns'), 10, 3);
        add_filter('manage_edit-asgarosforum-category_sortable_columns', array($this, 'manage_sortable_columns'));
        add_action('asgarosforum-category_add_form_fields', array($this, 'add_category_form_fields'));
		add_action('asgarosforum-category_edit_form_fields', array($this, 'edit_category_form_fields'));
        add_action('create_asgarosforum-category', array($this, 'save_category_form_fields'));
        add_action('edit_asgarosforum-category', array($this, 'save_category_form_fields'));
        add_action('delete_asgarosforum-category', array($this, 'delete_category'), 10, 3);
        add_action('get_terms', array($this, 'get_ordered_terms'));

        // Moderator stuff
        add_action('edit_user_profile', array($this, 'moderator_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'moderator_profile_fields_update'));
    }

    function moderator_profile_fields($user) {
        if (!current_user_can('manage_options')) {
            return false;
        }

        echo '<h3>'.__('Forum', 'asgaros-forum').'</h3>';
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="asgarosforum_moderator">'.__('Forum Moderator', 'asgaros-forum').'</label></th>';
        echo '<td>';
        echo '<input type="checkbox" name="asgarosforum_moderator" id="asgarosforum_moderator" value="1" '.checked(get_the_author_meta('asgarosforum_moderator', $user->ID ), '1', false).' />';
        echo '</td></tr></table>';
    }

    function moderator_profile_fields_update($user_id) {
        if (!current_user_can('manage_options')) {
            return false;
        }

        update_usermeta(absint($user_id), 'asgarosforum_moderator', wp_kses_post($_POST['asgarosforum_moderator']));
    }

    function set_current_menu($parent_file) {
        global $submenu_file;
        $parent_file = ($submenu_file == 'edit-tags.php?taxonomy=asgarosforum-category') ? 'asgarosforum' : $parent_file;
        return $parent_file;
    }

    function set_current_submenu($submenu_file) {
        $submenu_file = ($submenu_file == 'edit-tags.php?taxonomy=asgarosforum-category') ? 'edit-tags.php?taxonomy=asgarosforum-category&orderby=order&order=asc' : $submenu_file;
        return $submenu_file;
    }

    function manage_columns($columns) {
        unset($columns['description'], $columns['slug'], $columns['posts']);

        $columns['order'] = __('Order', 'asgaros-forum');
        $columns['category_access'] = __('Access', 'asgaros-forum');
        $columns = apply_filters('asgarosforum_filter_manage_columns', $columns);

        return $columns;
    }

    function manage_custom_columns($out, $column, $term_id) {
        if ($column == 'order') {
            $order = get_term_meta($term_id, 'order', true);
            $out = sprintf('<p>%s</p>', esc_attr($order));
        } else if ($column == 'category_access') {
            $access = get_term_meta($term_id, 'category_access', true);
            $access_name = __('Everyone', 'asgaros-forum');

            if ($access === 'loggedin') {
                $access_name = __('Logged in users only', 'asgaros-forum');
            } else if ($access === 'moderator') {
                $access_name = __('Moderators only', 'asgaros-forum');
            }

            $out = sprintf('<p>%s</p>', esc_attr($access_name));
        } else {
            $out = apply_filters('asgarosforum_filter_manage_custom_columns', $out, $column, $term_id);
        }

        return $out;
    }

    function manage_sortable_columns($sortable) {
        $sortable['order'] = 'order';
        return $sortable;
    }

    function add_category_form_fields() {
        echo '<div class="form-field form-required term-order-wrap">';
            echo '<label>'.__('Order', 'asgaros-forum').'</label>';
            echo '<input type="number" name="category_order" value="1" size="3" />';
        echo '</div>';

        echo '<div class="form-field form-required term-category_access-wrap">';
            echo '<label>'.__('Access', 'asgaros-forum').'</label>';
            echo '<select name="category_access">';
                echo '<option value="everyone">'.__('Everyone', 'asgaros-forum').'</option>';
                echo '<option value="loggedin">'.__('Logged in users only', 'asgaros-forum').'</option>';
                echo '<option value="moderator">'.__('Moderators only', 'asgaros-forum').'</option>';
            echo '</select>';
            echo '<p>'.__('Select which user role has access to this category.', 'asgaros-forum').'</p>';
        echo '</div>';

        do_action('asgarosforum_action_add_category_form_fields');
    }

    function edit_category_form_fields($term) {
        $term_meta = get_term_meta($term->term_id);
        $order = (!empty($term_meta['order'][0])) ? $term_meta['order'][0] : 1;
        $access = (!empty($term_meta['category_access'][0])) ? $term_meta['category_access'][0] : 'everyone';

        echo '<tr class="form-field form-required term-order-wrap">';
            echo '<th scope="row">'.__('Order', 'asgaros-forum').'</th>';
            echo '<td>';
                echo '<input type="text" name="category_order" value="'.$order.'" />';
            echo '</td>';
        echo '</tr>';

        echo '<tr class="form-field form-required term-category_access-wrap">';
            echo '<th scope="row">'.__('Access', 'asgaros-forum').'</th>';
            echo '<td>';
                echo '<select name="category_access">';
                    echo '<option value="everyone" '.selected($access, 'everyone', false).'>'.__('Everyone', 'asgaros-forum').'</option>';
                    echo '<option value="loggedin" '.selected($access, 'loggedin', false).'>'.__('Logged in users only', 'asgaros-forum').'</option>';
                    echo '<option value="moderator" '.selected($access, 'moderator', false).'>'.__('Moderators only', 'asgaros-forum').'</option>';
                echo '</select>';
                echo '<p class="description">'.__('Select which user role has access to this category.', 'asgaros-forum').'</p>';
            echo '</td>';
        echo '</tr>';

        do_action('asgarosforum_action_edit_category_form_fields', $term);
    }

    function save_category_form_fields($term_id) {
        if (isset($_POST['category_order'])) {
            update_term_meta($term_id, 'order', $_POST['category_order']);
        }

        if (isset($_POST['category_access'])) {
            update_term_meta($term_id, 'category_access', $_POST['category_access']);
        }

        do_action('asgarosforum_action_save_category_form_fields', $term_id);
    }

    function get_ordered_terms($categories) {
        global $submenu_file, $asgarosforum;

        if ($submenu_file === 'edit-tags.php?taxonomy=asgarosforum-category&orderby=order&order=asc') {
            if (!empty($_GET['orderby']) && $_GET['orderby'] === 'order') {
                foreach ($categories as $key => $category) {
                    $category->order = get_term_meta($category->term_id, 'order', true);
                }

                usort($categories, array($asgarosforum, 'categories_compare'));

                if (!empty($_GET['order']) && $_GET['order'] === 'desc') {
                    $categories = array_reverse($categories);
                }
            }
        }

        return $categories;
    }

    function add_admin_pages() {
        add_menu_page(__('Forum', 'asgaros-forum'), __('Forum', 'asgaros-forum'), 'administrator', 'asgarosforum', array($this, 'options_page'), 'dashicons-clipboard');
        add_submenu_page('asgarosforum', __('Options', 'asgaros-forum'), __('Options', 'asgaros-forum'), 'administrator', 'asgarosforum', array($this, 'options_page'));
        add_submenu_page('asgarosforum', __('Categories', 'asgaros-forum'), __('Categories', 'asgaros-forum'), 'administrator', 'edit-tags.php?taxonomy=asgarosforum-category&orderby=order&order=asc', null);
        add_submenu_page('asgarosforum', __('Forums', 'asgaros-forum'), __('Forums', 'asgaros-forum'), 'administrator', 'asgarosforum-structure', array($this, 'forums_page'));
    }

    function enqueue_admin_scripts($hook) {
        global $submenu_file, $asgarosforum;
        $l10n_vars = array('remove_forum_warning' => __('WARNING: Deleting this Forum will also PERMANENTLY DELETE ALL Threads, and Replies associated with it!!! Are you sure you want to delete this Forum???', 'asgaros-forum'));

        if (strstr($hook, 'asgarosforum') !== false || $submenu_file == 'edit-tags.php?taxonomy=asgarosforum-category') {
            wp_enqueue_style('asgarosforum-admin-css', $asgarosforum->directory.'admin/admin.css');
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('asgarosforum-admin-js', $asgarosforum->directory.'admin/admin.js', array('wp-color-picker'), false, true);
            wp_localize_script('asgarosforum-admin-js', 'asgarosforum_admin', $l10n_vars);
        }
    }

    function save_settings() {
        if (isset($_POST['af_options_submit'])) {
            $this->save_options();
        } else if (isset($_POST['af_forums_submit'])) {
            $this->save_forums();
        }
    }

    /* OPTIONS */
    function options_page() {
        global $asgarosforum;
        require('views/options.php');
    }

    function save_options() {
        global $asgarosforum;
        $saved_ops = array();

        foreach ($asgarosforum->options_default as $k => $v) {
            if (isset($_POST[$k]) && !empty($_POST[$k])) {
                if (is_numeric($v)) {
                    $saved_ops[$k] = ((int)$_POST[$k] > 0) ? (int)$_POST[$k] : $v;
                } else if (is_bool($v)) {
                    $saved_ops[$k] = true;
                } else {
                    $saved_ops[$k] = esc_sql(stripslashes(strtolower($_POST[$k])));
                }

                if ($k == 'theme') {
                    AsgarosThemeManager::set_current_theme($_POST[$k]);
                }
            } else {
                if (is_numeric($v)) {
                    $saved_ops[$k] = $v;
                } else if (is_bool($v)) {
                    $saved_ops[$k] = false;
                } else {
                    $saved_ops[$k] = $v;
                }
            }


        }

        update_option('asgarosforum_options', $saved_ops);
        $asgarosforum->options = get_option('asgarosforum_options', array());
        $this->saved = true;
    }

    /* STRUCTURE */
    function forums_page() {
        global $asgarosforum;
        $categories = $asgarosforum->get_categories(true);

        require('views/forums.php');
    }

    function save_forums() {
        global $asgarosforum, $wpdb;
        $order = 1;
        $listed_forums = array();
        $forums = array();
        $categories = $asgarosforum->get_categories(true);

        foreach ($categories as $category) {
            if (isset($_POST['forum_id'][$category->term_id]) && !empty($_POST['forum_id'][$category->term_id])) {
                foreach ($_POST['forum_id'][$category->term_id] as $key => $forum_id) {
                    $name = trim($_POST['forum_name'][$category->term_id][$key]);
                    $description = trim($_POST['forum_description'][$category->term_id][$key]);
                    $closed = (!empty($_POST['forum_closed'][$category->term_id][$forum_id])) ? 1 : 0;

                    if (empty($name)) {
                        if ($forum_id != 'new') {
                            $listed_forums[] = $forum_id;
                        }

                        continue;
                    }

                    if ($forum_id == 'new') {
                        $wpdb->insert($asgarosforum->table_forums, array('name' => $name, 'description' => $description, 'sort' => $order, 'parent_id' => $category->term_id, 'closed' => $closed), array('%s', '%s', '%d', '%d', '%d'));
                        $listed_forums[] = $wpdb->insert_id;
                    } else {
                        $wpdb->update($asgarosforum->table_forums, array('name' => $name, 'description' => $description, 'sort' => $order, 'parent_id' => $category->term_id, 'closed' => $closed), array('id' => $forum_id), array('%s', '%s', '%d', '%d', '%d'), array('%d'));
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

    function delete_category($term_id, $term_taxonomy_id, $deleted_term) {
        global $wpdb, $asgarosforum;

        $forums = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_forums} WHERE parent_id = {$term_id};");

        if (!empty($forums)) {
            foreach ($forums as $forum) {
                $this->delete_forum($forum);
            }
        }
    }

    function delete_forum($forum_id) {
        global $wpdb, $asgarosforum;

        $threads = $wpdb->get_col("SELECT id FROM {$asgarosforum->table_threads} WHERE parent_id = {$forum_id};");

        if (!empty($threads)) {
            foreach ($threads as $thread) {
                $asgarosforum->delete_thread($thread, true);
            }
        }

        $wpdb->delete($asgarosforum->table_forums, array('id' => $forum_id), array('%d'));
    }
}
?>
