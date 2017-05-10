<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumUserGroups {
    private static $asgarosforum = null;
    private static $taxonomyName = 'asgarosforum-usergroup';

    public function __construct($object) {
		self::$asgarosforum = $object;

        add_action('init', array($this, 'initialize'));

        // Users List in Administration.
        add_filter('manage_users_columns', array($this, 'manageUsersColumns'));
        add_action('manage_users_custom_column', array($this, 'manageUsersCustomColumn'), 10, 3);
    }

    public function initialize() {
        // Empty ...
    }

    // Users List in Administration.
    public function manageUsersColumns($columns) {
        $columns['user-group'] = __('User Groups', 'asgaros-forum');
        return $columns;
  	}

    function manageUsersCustomColumn($out, $column, $user_id) {
		if ($column === 'user-group') {
            $usergroups = self::getUserGroupsForUser($user_id);

    		if (!empty($usergroups)) {
        		$tags = '';

        		foreach ($usergroups as $usergroup) {
        			$href = add_query_arg(array('user-group' => $usergroup->slug), admin_url('users.php'));
                    $color = self::getUserGroupColor($usergroup->term_id);
        			$tags .= '<a class="usergroup-tag" style="border: 3px solid '.$color.';" href="'.$href.'" title="'.$usergroup->description.'">'.$usergroup->name.'</a>';
        		}

        		return $tags;
            } else {
                return false;
            }
		} else {
            return $out;
        }
	}

    // Returns all usergroups
    public static function getUserGroups() {
        return get_terms(self::$taxonomyName, array('hide_empty' => false));
    }

    // Returns usergroup by id/slug/name/term_taxonomy_id
    public static function getUserGroupBy($value, $by = 'id') {
        return get_term_by($by, $value, self::$taxonomyName);
    }

    // Returns color of usergroup
    public static function getUserGroupColor($term_id) {
        return get_term_meta($term_id, 'usergroup-color', true);
    }

    // Returns usergroups of user
    public static function getUserGroupsForUser($user_id, $fields = 'all') {
        return wp_get_object_terms($user_id, self::$taxonomyName, array('fields' => $fields));
    }

    // Returns usergroups of post
    public static function getUserGroupsForPost($post_id) {
        return get_post_meta($post_id, 'usergroups', true);
    }

    // Returns users in usergroup
    public static function getUsersInUserGroup($usergroup_id) {
        return get_objects_in_term($usergroup_id, self::$taxonomyName);
    }

    // Checks if a user is in a specific user group.
    public static function isUserInUserGroup($userID, $userGroupID) {
        return is_object_in_term($userID, self::$taxonomyName, $userGroupID);
    }

    public static function saveUserGroup() {
        global $asgarosforum;
        $usergroup_id       = $_POST['usergroup_id'];
        $usergroup_name     = trim($_POST['usergroup_name']);
        $usergroup_color    = trim($_POST['usergroup_color']);

        if (!empty($usergroup_name)) {
            if ($usergroup_id === 'new') {
                $newTerm = wp_insert_term($usergroup_name, self::$taxonomyName);
                $usergroup_id = $newTerm['term_id'];
            } else {
                wp_update_term($usergroup_id, self::$taxonomyName, array('name' => $usergroup_name));
            }

            update_term_meta($usergroup_id, 'usergroup-color', $usergroup_color);

            return true;
        }

        return false;
    }

    public static function deleteUserGroup($userGroupID) {
        wp_delete_term($userGroupID, self::$taxonomyName);
    }

    // Adds a new user groups string to the structure page.
    public static function renderUserGroupsInCategory($categoryID) {
        $userGroupsInCategory = get_term_meta($categoryID, 'usergroups', true);

        if ($userGroupsInCategory) {
            $userGroupsNames = get_terms(self::$taxonomyName, array('hide_empty' => false, 'include' => $userGroupsInCategory, 'fields' => 'names'));

            if (!empty($userGroupsNames)) {
                $userGroupsString = esc_attr(implode(', ', $userGroupsNames));

                echo ' | '.__('User Groups:', 'asgaros-forum').' '.$userGroupsString;
            }
        }
    }

    public static function renderCategoryEditorFields() {
        $available_usergroups = get_terms(self::$taxonomyName, array('hide_empty' => false));

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
        $groups_of_user = wp_get_object_terms($user_ID, self::$taxonomyName, array('fields' => 'ids'));
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
        $groups_of_user = wp_get_object_terms($user_ID, self::$taxonomyName, array('fields' => 'ids'));
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
                $userids = array_merge($userids, get_objects_in_term($usergroup, self::$taxonomyName));
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

    public static function showUserProfileFields($userID) {
        $output = '';
        $usergroups = self::getUserGroups();

        if (!empty($usergroups)) {
            $output .= '<tr>';
            $output .= '<th><label>'.__('User Groups', 'asgaros-forum').'</label></th>';
            $output .= '<td>';

            foreach ($usergroups as $usergroup) {
                $color = self::getUserGroupColor($usergroup->term_id);

				$output .= '<input type="checkbox" name="'.self::$taxonomyName.'[]" id="'.self::$taxonomyName.'-'.$usergroup->slug.'" value="'.$usergroup->slug.'" '.checked(true, self::isUserInUserGroup($userID, $usergroup->term_id), false).' />';
                $output .= '<label class="usergroup-label" for="'.self::$taxonomyName.'-'.$usergroup->slug.'" style="border: 3px solid '.$color.';">';
                $output .= $usergroup->name;
                $output .= '</label>';
                $output .= '<br />';
			}

            $output .= '</td>';
    		$output .= '</tr>';
		}

        return $output;
    }

    public static function updateUserProfileFields($user_id, $user_groups = array(), $bulk = false) {
        if (empty($user_groups) && !$bulk) {
            $user_groups = isset($_POST[self::$taxonomyName]) ? $_POST[self::$taxonomyName] : null;
		}

		if (is_null($user_groups) || empty($user_groups)) {
            wp_delete_object_term_relationships($user_id, self::$taxonomyName);
		} else {
			wp_set_object_terms($user_id, $user_groups, self::$taxonomyName, false);
		}

		clean_object_term_cache($user_id, self::$taxonomyName);
    }
}
