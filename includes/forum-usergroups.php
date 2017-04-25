<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumUserGroups {
    private static $asgarosforum = null;

    public function __construct($object) {
		self::$asgarosforum = $object;
    }

    // Adds a new user groups string to the structure page.
    public static function renderUserGroupsInCategory($categoryID) {
        $userGroupsInCategory = get_term_meta($categoryID, 'usergroups', true);

        if ($userGroupsInCategory) {
            $userGroupsNames = get_terms('user-group', array('hide_empty' => false, 'include' => $userGroupsInCategory, 'fields' => 'names'));

            if (!empty($userGroupsNames)) {
                $userGroupsString = esc_attr(implode(', ', $userGroupsNames));

                echo ' | '.__('User Groups:', 'asgaros-forum').' '.$userGroupsString;
            }
        }
    }

    public static function renderCategoryEditorFields() {
        $available_usergroups = get_terms('user-group', array('hide_empty' => false));

        if (!empty($available_usergroups) && !is_wp_error($available_usergroups)) {
            echo '<tr id="usergroups-editor">';
                echo '<th><label>'.__('User Groups:', 'asgaros-forum').'</label></th>';
                echo '<td>';
                    foreach ($available_usergroups as $usergroup) {
                        echo '<label><input type="checkbox" name="category_usergroups[]" value="'.$usergroup->term_id.'" /> '.$usergroup->name.'</label>';
                    }

                    echo '<p class="description">'.__('When user groups are selected, only users of the selected user groups will have access to the category.', 'asgaros-forum').'</p>';
                echo '</td>';
            echo '</tr>';
        }
    }

    public static function renderHiddenFields($categoryID) {
        $userGroupsInCategory = get_term_meta($categoryID, 'usergroups', true);
        $userGroupsInCategoryString = '';

        if ($userGroupsInCategory) {
            $userGroupsInCategoryString = implode(',', $userGroupsInCategory);
        }

        echo '<input type="hidden" id="category_'.$categoryID.'_usergroups" value="'.$userGroupsInCategoryString.'">';
    }

    public static function saveUserGroupsOfCategory($categoryID) {
        $userGroups = isset($_POST['category_usergroups']) ? $_POST['category_usergroups'] : '';

        if (empty($userGroups)) {
            delete_term_meta($categoryID, 'usergroups');
        } else {
            update_term_meta($categoryID, 'usergroups', $userGroups);
        }
    }

    public static function filterCategories($filter) {
        global $user_ID;
        $groups_of_user = wp_get_object_terms($user_ID, 'user-group', array('fields' => 'ids'));
        $categories = get_terms('asgarosforum-category', array('hide_empty' => false)); // TODO: Produces a duplicate query.

        if (!empty($categories) && !is_wp_error($categories) && !is_super_admin($user_ID)) {
            foreach ($categories as $category) {
                $usergroups = get_term_meta($category->term_id, 'usergroups', true);

                if (!empty($usergroups)) {
                    $hide = true;

                    foreach ($usergroups as $usergroup) {
                        if (in_array($usergroup, $groups_of_user)) {
                            $hide = false;
                        }
                    }

                    if ($hide) {
                        $filter[] = $category->term_id;
                    }
                }
            }
        }

        return $filter;
    }

    public static function checkAccess($categoryID) {
        $status = true;

        global $user_ID;
        $groups_of_user = wp_get_object_terms($user_ID, 'user-group', array('fields' => 'ids'));
        $usergroups = get_term_meta($categoryID, 'usergroups', true);

        if (!empty($usergroups) && !is_super_admin($user_ID)) {
            $status = false;

            foreach ($usergroups as $usergroup) {
                if (in_array($usergroup, $groups_of_user)) {
                    $status = true;
                }
            }
        }

        return $status;
    }

    // Makes sure that only users of a user-group are receiving mails.
    public static function filterSubscriberMails($mails) {
        // TODO: A cleaner way to implement this would be tax_query support in get_users, see: https://core.trac.wordpress.org/ticket/31383
        global $asgarosforum;

        // Get Usergroups of current category.
        $usergroups = get_term_meta($asgarosforum->current_category, 'usergroups', true);

        if (!empty($usergroups)) {
            // Get all objects (users) which are in that group.
            $userids = array();
            foreach ($usergroups as $usergroup) {
                $userids = array_merge($userids, get_objects_in_term($usergroup, 'user-group'));
            }

            // Get mail-adresses of those users.
            $allowed_mails_objects = get_users(
                array(
                    'fields'    => array('user_email'),
                    'include'   => $userids
                )
            );

            // Rebuild list for easier comparison.
            $allowed_mails_list = array();

            foreach ($allowed_mails_objects as $mail) {
                if (!in_array($mail->user_email, $allowed_mails_list)) {
                    $allowed_mails_list[] = $mail->user_email;
                }
            }

            // Test for each mail, if its included in the list of allowed addresses.
            foreach ($mails as $key => $value) {
                if (!in_array($value, $allowed_mails_list)) {
                    unset($mails[$key]);
                }
            }
        }

        return $mails;
    }
}
