<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumAdmin {
    var $saved = false;
    var $error = false;
    // TODO: Remove globals
    private $asgarosforum = null;

    function __construct($object) {
        $this->asgarosforum = $object;

        add_action('wp_loaded', array($this, 'save_settings'));
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // User profile options.
        add_action('edit_user_profile', array($this, 'user_profile_fields'));
        add_action('show_user_profile', array($this, 'user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'user_profile_fields_update'));
        add_action('personal_options_update', array($this, 'user_profile_fields_update'));
    }

    function user_profile_fields($user) {
        // TODO: get_the_author_meta can be removed. Use get_user_meta instead.
        global $asgarosforum;
        $output = '';

        // Show settings only when current user is admin ...
        if (current_user_can('manage_options')) {
            // ... and he edits a non-admin user.
            if (!user_can($user->ID, 'manage_options')) {
                $output .= '<tr>';
                $output .= '<th><label for="asgarosforum_moderator">'.__('Forum Moderator', 'asgaros-forum').'</label></th>';
                $output .= '<td><input type="checkbox" name="asgarosforum_moderator" id="asgarosforum_moderator" value="1" '.checked(get_the_author_meta('asgarosforum_moderator', $user->ID), '1', false).'></td>';
                $output .= '</tr>';
                $output .= '<tr>';
                $output .= '<th><label for="asgarosforum_banned">'.__('Banned User', 'asgaros-forum').'</label></th>';
                $output .= '<td><input type="checkbox" name="asgarosforum_banned" id="asgarosforum_banned" value="1" '.checked(get_the_author_meta('asgarosforum_banned', $user->ID), '1', false).'></td>';
                $output .= '</tr>';
            }

            $output .= AsgarosForumUserGroups::showUserProfileFields($user->ID);
        }

        if ($asgarosforum->options['enable_mentioning']) {
            $output .= '<tr>';
            $output .= '<th><label for="asgarosforum_mention_notify">'.__('Notify me when I got mentioned', 'asgaros-forum').'</label></th>';
            $output .= '<td><input type="checkbox" name="asgarosforum_mention_notify" id="asgarosforum_mention_notify" value="1" '.checked($asgarosforum->mentioning->user_wants_notification($user->ID), true, false).'></td>';
            $output .= '</tr>';
        }

        if ($asgarosforum->options['allow_signatures']) {
            $output .= '<tr>';
            $output .= '<th><label for="asgarosforum_signature">'.__('Signature', 'asgaros-forum').'</label></th>';
            $output .= '<td><textarea rows="5" cols="30" name="asgarosforum_signature" id="asgarosforum_signature">'.get_the_author_meta('asgarosforum_signature', $user->ID).'</textarea></td>';
            $output .= '</tr>';
        }

        if (!empty($output)) {
            echo '<h2>'.__('Forum', 'asgaros-forum').'</h2>';
            echo '<table class="form-table">';
            echo $output;
            echo '</table>';
        }
    }

    function user_profile_fields_update($user_id) {
        global $asgarosforum;
        $user_id = absint($user_id);

        if (current_user_can('manage_options')) {
            if (!user_can($user_id, 'manage_options')) {
                if (isset($_POST['asgarosforum_moderator'])) {
                    update_user_meta($user_id, 'asgarosforum_moderator', wp_kses_post($_POST['asgarosforum_moderator']));
                } else {
                    delete_user_meta($user_id, 'asgarosforum_moderator');
                }

                if (isset($_POST['asgarosforum_banned'])) {
                    update_user_meta($user_id, 'asgarosforum_banned', wp_kses_post($_POST['asgarosforum_banned']));
                } else {
                    delete_user_meta($user_id, 'asgarosforum_banned');
                }
            }

            AsgarosForumUserGroups::updateUserProfileFields($user_id);
        }

        if ($asgarosforum->options['enable_mentioning']) {
            if (isset($_POST['asgarosforum_mention_notify'])) {
                update_user_meta($user_id, 'asgarosforum_mention_notify', 'yes');
            } else {
                update_user_meta($user_id, 'asgarosforum_mention_notify', 'no');
            }
        }

        if ($asgarosforum->options['allow_signatures']) {
            if (isset($_POST['asgarosforum_signature'])) {
                update_user_meta($user_id, 'asgarosforum_signature', trim(wp_kses_post($_POST['asgarosforum_signature'])));
            } else {
                delete_user_meta($user_id, 'asgarosforum_signature');
            }
        }
    }

    // Add all required pages to the menu.
    function add_admin_pages() {
        add_menu_page(__('Forum', 'asgaros-forum'), __('Forum', 'asgaros-forum'), 'manage_options', 'asgarosforum-structure', array($this, 'structure_page'), 'dashicons-clipboard');
        add_submenu_page('asgarosforum-structure', __('Structure', 'asgaros-forum'), __('Structure', 'asgaros-forum'), 'manage_options', 'asgarosforum-structure', array($this, 'structure_page'));
        add_submenu_page('asgarosforum-structure', __('Appearance', 'asgaros-forum'), __('Appearance', 'asgaros-forum'), 'manage_options', 'asgarosforum-appearance', array($this, 'appearance_page'));
        add_submenu_page('asgarosforum-structure', __('User Groups', 'asgaros-forum'), __('User Groups', 'asgaros-forum'), 'manage_options', 'asgarosforum-usergroups', array($this, 'usergroups_page'));

        if ($this->asgarosforum->options['reports_enabled']) {
            // Add report counter to menu.
            $label_reports = __('Reports', 'asgaros-forum');
            $counter_reports = $this->asgarosforum->reports->count_reports();

            if ($counter_reports > 0) {
                $label_reports = sprintf(__('Reports %s', 'asgaros-forum'), '<span class="update-plugins count-'.$counter_reports.'"><span class="plugin-count">'.number_format_i18n($counter_reports).'</span></span>');
            }

            add_submenu_page('asgarosforum-structure', __('Reports', 'asgaros-forum'), $label_reports, 'manage_options', 'asgarosforum-reports', array($this, 'reports_page'));
        }

        add_submenu_page('asgarosforum-structure', __('Settings', 'asgaros-forum'), __('Settings', 'asgaros-forum'), 'manage_options', 'asgarosforum-options', array($this, 'options_page'));
    }

    function options_page() {
        global $asgarosforum;
        require('views/options.php');
    }

    function structure_page() {
        global $asgarosforum;
        $categories = $asgarosforum->content->get_categories(false);

        require('views/structure.php');
    }

    function appearance_page() {
        require('views/appearance.php');
    }

    function usergroups_page() {
        require('views/usergroups.php');
    }

    function reports_page() {
        require('views/reports.php');
    }

    function enqueue_admin_scripts($hook) {
        global $asgarosforum;

        wp_enqueue_style('asgarosforum-admin-common-css', $asgarosforum->directory.'admin/css/admin-common.css', array(), $asgarosforum->version);

        if (strstr($hook, 'asgarosforum') !== false) {
            wp_enqueue_style('asgarosforum-admin-css', $asgarosforum->directory.'admin/css/admin.css', array(), $asgarosforum->version);
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('asgarosforum-admin-js', $asgarosforum->directory.'admin/js/admin.js', array('wp-color-picker'), $asgarosforum->version, true);
        }
    }

    function save_settings() {
        // Only save changes when the user is an administrator.
        if (current_user_can('manage_options')) {
            if (isset($_POST['af_options_submit'])) {
                // Verify nonce first.
                check_admin_referer('asgaros_forum_save_options');

                $this->save_options();
            } else if (isset($_POST['af_appearance_submit'])) {
                // Verify nonce first.
                check_admin_referer('asgaros_forum_save_appearance');

                $this->save_appearance();
            } else if (isset($_POST['af-create-edit-forum-submit'])) {
                // Verify nonce first.
                check_admin_referer('asgaros_forum_save_forum');

                $this->save_forum();
            } else if (isset($_POST['asgaros-forum-delete-forum'])) {
                // Verify nonce first.
                check_admin_referer('asgaros_forum_delete_forum');

                if (!empty($_POST['forum-id']) && is_numeric($_POST['forum-id']) && !empty($_POST['forum-category']) && is_numeric($_POST['forum-category'])) {
                    $this->delete_forum($_POST['forum-id'], $_POST['forum-category']);
                }
            } else if (isset($_POST['af-create-edit-category-submit'])) {
                // Verify nonce first.
                check_admin_referer('asgaros_forum_save_category');

                $this->save_category();
            } else if (isset($_POST['asgaros-forum-delete-category'])) {
                // Verify nonce first.
                check_admin_referer('asgaros_forum_delete_category');

                if (!empty($_POST['category-id']) && is_numeric($_POST['category-id'])) {
                    $this->delete_category($_POST['category-id']);
                }
            } else if (isset($_POST['af-create-edit-usergroup-category-submit'])) {
                // Verify nonce first.
                check_admin_referer('asgaros_forum_save_usergroup_category');

                $saveStatus = AsgarosForumUserGroups::saveUserGroupCategory();

                if (is_wp_error($saveStatus)) {
                    $this->error = $saveStatus->get_error_message();
                } else {
                    $this->saved = $saveStatus;
                }
            } else if (isset($_POST['af-create-edit-usergroup-submit'])) {
                // Verify nonce first.
                check_admin_referer('asgaros_forum_save_usergroup');

                $saveStatus = AsgarosForumUserGroups::saveUserGroup();

                if (is_wp_error($saveStatus)) {
                    $this->error = $saveStatus->get_error_message();
                } else {
                    $this->saved = $saveStatus;
                }
            } else if (isset($_POST['asgaros-forum-delete-usergroup'])) {
                // Verify nonce first.
                check_admin_referer('asgaros_forum_delete_usergroup');

                if (!empty($_POST['usergroup-id']) && is_numeric($_POST['usergroup-id'])) {
                    AsgarosForumUserGroups::deleteUserGroup($_POST['usergroup-id']);
                }
            } else if (isset($_POST['asgaros-forum-delete-usergroup-category'])) {
                // Verify nonce first.
                check_admin_referer('asgaros_forum_delete_usergroup_category');

                if (!empty($_POST['usergroup-category-id']) && is_numeric($_POST['usergroup-category-id'])) {
                    AsgarosForumUserGroups::deleteUserGroupCategory($_POST['usergroup-category-id']);
                }
            } else if (isset($_POST['asgaros-forum-delete-report'])) {
                // Verify nonce first.
                check_admin_referer('asgaros_forum_delete_report');

                if (!empty($_POST['report-id']) && is_numeric($_POST['report-id'])) {
                    $this->asgarosforum->reports->remove_report($_POST['report-id']);
                }
            }
        }
    }

    /* OPTIONS */
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
                    $saved_ops[$k] = (bool)$_POST[$k];
                } else if ($k === 'allowed_filetypes') {
                    $tmp = esc_sql(stripslashes(strtolower(trim($_POST[$k]))));
                    $saved_ops[$k] = (!empty($tmp)) ? $tmp : $v;
                } else {
                    $tmp = esc_sql(stripslashes(trim($_POST[$k])));
                    $saved_ops[$k] = (!empty($tmp)) ? $tmp : $v;
                }
            } else {
                if (is_bool($v)) {
                    $saved_ops[$k] = false;
                } else {
                    $saved_ops[$k] = $v;
                }
            }
        }

        $asgarosforum->saveOptions($saved_ops);
        $this->saved = true;
    }

    function save_appearance() {
        global $asgarosforum;
        $saved_ops = array();

        foreach ($asgarosforum->appearance->options_default as $k => $v) {
            if (isset($_POST[$k])) {
                $tmp = esc_sql(stripslashes(trim($_POST[$k])));
                $saved_ops[$k] = (!empty($tmp)) ? $tmp : $v;
            } else {
                $saved_ops[$k] = $v;
            }
        }

        $asgarosforum->appearance->save_options($saved_ops);
        $this->saved = true;
    }

    /* STRUCTURE */
    function save_category() {
        global $asgarosforum;
        $category_id        = $_POST['category_id'];
        $category_name      = trim($_POST['category_name']);
        $category_access    = trim($_POST['category_access']);
        $category_order     = (is_numeric($_POST['category_order'])) ? $_POST['category_order'] : 1;

        if (!empty($category_name)) {
            if ($category_id === 'new') {
                $newTerm = wp_insert_term($category_name, 'asgarosforum-category');

                // Return possible error.
                if (is_wp_error($newTerm)) {
                    $this->error = $newTerm->get_error_message();
                    return;
                }

                $category_id = $newTerm['term_id'];
            } else {
                wp_update_term($category_id, 'asgarosforum-category', array('name' => $category_name));
            }

            update_term_meta($category_id, 'category_access', $category_access);
            update_term_meta($category_id, 'order', $category_order);
            AsgarosForumUserGroups::saveUserGroupsOfForumCategory($category_id);

            $this->saved = true;
        }
    }

    function save_forum() {
        global $asgarosforum;

        // ID of the forum.
        $forum_id           = $_POST['forum_id'];

        // Determine parent IDs.
        $parent_ids          = explode('_', $_POST['forum_parent']);
        $forum_category     = $parent_ids[0];
        $forum_parent_forum = $parent_ids[1];

        // Additional data.
        $forum_name         = trim($_POST['forum_name']);
        $forum_description  = trim($_POST['forum_description']);
        $forum_icon         = trim($_POST['forum_icon']);
        $forum_icon         = (empty($forum_icon)) ? 'dashicons-editor-justify' : $forum_icon;
        $forum_closed       = (isset($_POST['forum_closed'])) ? 1 : 0;
        $forum_order        = (is_numeric($_POST['forum_order'])) ? $_POST['forum_order'] : 0;

        if (!empty($forum_name)) {
            if ($forum_id === 'new') {
                $asgarosforum->content->insert_forum($forum_category, $forum_name, $forum_description, $forum_parent_forum, $forum_icon, $forum_order, $forum_closed);
            } else {
                // Update forum.
                $asgarosforum->db->update(
                    $asgarosforum->tables->forums,
                    array('name' => $forum_name, 'description' => $forum_description, 'icon' => $forum_icon, 'sort' => $forum_order, 'closed' => $forum_closed, 'parent_id' => $forum_category, 'parent_forum' => $forum_parent_forum),
                    array('id' => $forum_id),
                    array('%s', '%s', '%s', '%d', '%d', '%d', '%d'),
                    array('%d')
                );

                // Update category ids of sub-forums in case the forum got moved.
                $asgarosforum->db->update(
                    $asgarosforum->tables->forums,
                    array('parent_id' => $forum_category),
                    array('parent_forum' => $forum_id),
                    array('%d'),
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
        $subforums = $asgarosforum->get_forums($category_id, $forum_id, true);

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
        $asgarosforum->notifications->remove_all_forum_subscriptions($forum_id);

        // Last but not least delete the forum
        $asgarosforum->db->delete($asgarosforum->tables->forums, array('id' => $forum_id), array('%d'));

        $this->saved = true;
    }

    /* USERGROUPS */
    function render_admin_header($title, $titleUpdated) {
        global $asgarosforum;

        echo '<div id="asgaros-panel">';
            echo '<div class="header-panel">';
                echo '<div class="sub-panel-left">';
                    echo '<img src="'.$asgarosforum->directory.'admin/images/logo.png">';
                echo '</div>';
                echo '<div class="sub-panel-left">';
                    echo '<h1>'.$title.'</h1>';
                echo '</div>';
                echo '<div class="sub-panel-right">';
                    echo '<a href="https://www.asgaros.de/support/" target="_blank" class="dashicons-before dashicons-admin-users">'.__('Official Support Forum', 'asgaros-forum').'</a>';
                    echo '&bull;';
                    echo '<a href="https://www.paypal.me/asgaros" target="_blank" class="dashicons-before dashicons-heart">'.__('Donate', 'asgaros-forum').'</a>';
                echo '</div>';
                echo '<div class="clear"></div>';
            echo '</div>';

            if ($this->error) {
                echo '<div class="error-panel"><p>'.$this->error.'</p></div>';
            } else if ($this->saved) {
                echo '<div class="updated-panel"><p>'.$titleUpdated.'</p></div>';
            }

        echo '</div>';
    }
}
