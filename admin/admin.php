<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumAdmin {
    var $saved = false;

    function __construct() {
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_init', array($this, 'save_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // User profile options.
        add_action('edit_user_profile', array($this, 'user_profile_fields'));
        add_action('show_user_profile', array($this, 'user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'user_profile_fields_update'));
        add_action('personal_options_update', array($this, 'user_profile_fields_update'));
    }

    function user_profile_fields($user) {
        global $asgarosforum;
        $output = '';

        // Show settings only when current user is admin and he edits a non-admin user.
        if (current_user_can('manage_options') && !user_can($user->ID, 'manage_options')) {
            $output .= '<tr>';
            $output .= '<th><label for="asgarosforum_moderator">'.__('Forum Moderator', 'asgaros-forum').'</label></th>';
            $output .= '<td><input type="checkbox" name="asgarosforum_moderator" id="asgarosforum_moderator" value="1" '.checked(get_the_author_meta('asgarosforum_moderator', $user->ID), '1', false).'></td>';
            $output .= '</tr>';
            $output .= '<tr>';
            $output .= '<th><label for="asgarosforum_banned">'.__('Banned User', 'asgaros-forum').'</label></th>';
            $output .= '<td><input type="checkbox" name="asgarosforum_banned" id="asgarosforum_banned" value="1" '.checked(get_the_author_meta('asgarosforum_banned', $user->ID), '1', false).'></td>';
            $output .= '</tr>';
        }

        if ($asgarosforum->options['allow_subscriptions'] && $user->user_email !== get_bloginfo('admin_email')) {
            $output .= '<tr>';
            $output .= '<th><label for="asgarosforum_subscription_global_topics">'.__('Notify about new topics', 'asgaros-forum').'</label></th>';
            $output .= '<td><input type="checkbox" name="asgarosforum_subscription_global_topics" id="asgarosforum_subscription_global_topics" value="1" '.checked(get_the_author_meta('asgarosforum_subscription_global_topics', $user->ID), '1', false).'></td>';
            $output .= '</tr>';
        }

        if ($asgarosforum->options['allow_signatures']) {
            $output .= '<tr>';
            $output .= '<th><label for="asgarosforum_signature">'.__('Signature', 'asgaros-forum').'</label></th>';
            $output .= '<td><textarea rows="5" cols="30" name="asgarosforum_signature" id="asgarosforum_signature">'.get_the_author_meta('asgarosforum_signature', $user->ID).'</textarea></td>';
            $output .= '</tr>';
        }

        if (!empty($output)) {
            echo '<h3>'.__('Forum', 'asgaros-forum').'</h3>';
            echo '<table class="form-table">';
            echo $output;
            echo '</table>';
        }
    }

    function user_profile_fields_update($user_id) {
        global $asgarosforum;

        if (current_user_can('manage_options') && !user_can($user->ID, 'manage_options')) {
            update_usermeta(absint($user_id), 'asgarosforum_moderator', wp_kses_post($_POST['asgarosforum_moderator']));
            update_usermeta(absint($user_id), 'asgarosforum_banned', wp_kses_post($_POST['asgarosforum_banned']));
        }

        if ($asgarosforum->options['allow_subscriptions']) {
            update_usermeta(absint($user_id), 'asgarosforum_subscription_global_topics', wp_kses_post($_POST['asgarosforum_subscription_global_topics']));
        }

        if ($asgarosforum->options['allow_signatures']) {
            update_usermeta(absint($user_id), 'asgarosforum_signature', trim(wp_kses_post($_POST['asgarosforum_signature'])));
        }
    }

    function add_admin_pages() {
        add_menu_page(__('Forum', 'asgaros-forum'), __('Forum', 'asgaros-forum'), 'manage_options', 'asgarosforum-options', array($this, 'options_page'), 'dashicons-clipboard');
        add_submenu_page('asgarosforum-options', __('Options', 'asgaros-forum'), __('Options', 'asgaros-forum'), 'manage_options', 'asgarosforum-options', array($this, 'options_page'));
        add_submenu_page('asgarosforum-options', __('Structure', 'asgaros-forum'), __('Structure', 'asgaros-forum'), 'manage_options', 'asgarosforum-structure', array($this, 'structure_page'));
    }

    function enqueue_admin_scripts($hook) {
        global $asgarosforum;

        if (strstr($hook, 'asgarosforum') !== false) {
            wp_enqueue_style('asgarosforum-admin-css', $asgarosforum->directory.'admin/admin.css', array(), $asgarosforum->version);
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('asgarosforum-admin-js', $asgarosforum->directory.'admin/admin.js', array('wp-color-picker'), $asgarosforum->version, true);
        }
    }

    function save_settings() {
        if (isset($_POST['af_options_submit'])) {
            $this->save_options();
        } else if (isset($_POST['af-create-edit-forum-submit'])) {
            $this->save_forum();
        } else if (isset($_POST['asgaros-forum-delete-forum'])) {
            if (!empty($_POST['forum-id']) && is_numeric($_POST['forum-id']) && !empty($_POST['forum-category']) && is_numeric($_POST['forum-category'])) {
                $this->delete_forum($_POST['forum-id'], $_POST['forum-category']);
            }
        } else if (isset($_POST['af-create-edit-category-submit'])) {
            $this->save_category();
        } else if (isset($_POST['asgaros-forum-delete-category'])) {
            if (!empty($_POST['category-id']) && is_numeric($_POST['category-id'])) {
                $this->delete_category($_POST['category-id']);
            }
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
            if (isset($_POST[$k])) {
                if ($k === 'uploads_maximum_number' || $k === 'uploads_maximum_size') {
                    $saved_ops[$k] = ((int)$_POST[$k] >= 0) ? (int)$_POST[$k] : $v;
                } else if (is_numeric($v)) {
                    $saved_ops[$k] = ((int)$_POST[$k] > 0) ? (int)$_POST[$k] : $v;
                } else if (is_bool($v)) {
                    $saved_ops[$k] = $k;
                } else if ($k === 'theme') {
                    $saved_ops[$k] = (!empty($_POST[$k])) ? esc_sql(stripslashes($_POST[$k])) : $v;
                } else {
                    $saved_ops[$k] = (!empty($_POST[$k])) ? esc_sql(stripslashes(strtolower($_POST[$k]))) : $v;
                }
            } else {
                if (is_bool($v)) {
                    $saved_ops[$k] = false;
                } else {
                    $saved_ops[$k] = $v;
                }
            }
        }

        update_option('asgarosforum_options', $saved_ops);
        $asgarosforum->options = get_option('asgarosforum_options', array());
        AsgarosForumThemeManager::set_current_theme($asgarosforum->options['theme']);
        $this->saved = true;
    }

    /* STRUCTURE */
    function structure_page() {
        global $asgarosforum;
        $categories = $asgarosforum->get_categories(false);

        require('views/structure.php');
    }

    function save_category() {
        global $asgarosforum;
        $category_id        = $_POST['category_id'];
        $category_name      = trim($_POST['category_name']);
        $category_access    = trim($_POST['category_access']);
        $category_order     = (is_numeric($_POST['category_order'])) ? $_POST['category_order'] : 1;

        if (!empty($category_name)) {
            if ($category_id === 'new') {
                $newTerm = wp_insert_term($category_name, 'asgarosforum-category');
                $category_id = $newTerm['term_id'];
            } else {
                wp_update_term($category_id, 'asgarosforum-category', array('name' => $category_name));
            }

            update_term_meta($category_id, 'category_access', $category_access);
            update_term_meta($category_id, 'order', $category_order);

            $this->saved = true;
        }
    }

    function save_forum() {
        global $asgarosforum;
        $forum_id           = $_POST['forum_id'];
        $forum_category     = $_POST['forum_category'];
        $forum_parent_forum = $_POST['forum_parent_forum'];
        $forum_name         = trim($_POST['forum_name']);
        $forum_description  = trim($_POST['forum_description']);
        $forum_closed       = (isset($_POST['forum_closed'])) ? 1 : 0;
        $forum_order        = (is_numeric($_POST['forum_order'])) ? $_POST['forum_order'] : 0;
        $forum_slug         = AsgarosForumRewrite::createUniqueSlug($forum_name, $asgarosforum->tables->forums);

        if (!empty($forum_name)) {
            if ($forum_id === 'new') {
                $asgarosforum->db->insert(
                    $asgarosforum->tables->forums,
                    array('name' => $forum_name, 'parent_id' => $forum_category, 'parent_forum' => $forum_parent_forum, 'description' => $forum_description, 'sort' => $forum_order, 'closed' => $forum_closed, 'slug' => $forum_slug),
                    array('%s', '%d', '%d', '%s', '%d', '%d', '%s')
                );
            } else {
                $asgarosforum->db->update(
                    $asgarosforum->tables->forums,
                    array('name' => $forum_name, 'description' => $forum_description, 'sort' => $forum_order, 'closed' => $forum_closed),
                    array('id' => $forum_id),
                    array('%s', '%s', '%d', '%d'),
                    array('%d')
                );
            }

            $this->saved = true;
        }
    }

    function delete_category($categoryID) {
        global $asgarosforum;

        $forums = $asgarosforum->db->get_col("SELECT id FROM {$asgarosforum->tables->forums} WHERE parent_id = {$categoryID};");

        if (!empty($forums)) {
            foreach ($forums as $forum) {
                $this->delete_forum($forum, $categoryID);
            }
        }

        wp_delete_term($categoryID, 'asgarosforum-category');
    }

    function delete_forum($forum_id, $category_id) {
        global $asgarosforum;

        // Delete all subforums first
        $subforums = $asgarosforum->get_forums($category_id, $forum_id);

        if (count($subforums) > 0) {
            foreach ($subforums as $subforum) {
                $this->delete_forum($subforum->id, $category_id);
            }
        }

        // Delete all topics.
        $topics = $asgarosforum->db->get_col("SELECT id FROM {$asgarosforum->tables->topics} WHERE parent_id = {$forum_id};");

        if (!empty($topics)) {
            foreach ($topics as $topic) {
                $asgarosforum->delete_topic($topic, true);
            }
        }

        // Delete subscriptions for this forum.
        AsgarosForumNotifications::removeForumSubscriptions($forum_id);

        // Last but not least delete the forum
        $asgarosforum->db->delete($asgarosforum->tables->forums, array('id' => $forum_id), array('%d'));

        $this->saved = true;
    }
}

?>
