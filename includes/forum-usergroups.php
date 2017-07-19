<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumUserGroups {
    private static $asgarosforum = null;
    private static $taxonomyName = 'asgarosforum-usergroup';

    public function __construct($object) {
		self::$asgarosforum = $object;

        add_action('init', array($this, 'initialize'));

        // Users list in administration.
        add_filter('manage_users_columns', array($this, 'manageUsersColumns'));
        add_action('manage_users_custom_column', array($this, 'manageUsersCustomColumn'), 10, 3);
        add_action('delete_user', array($this, 'delete_term_relationships'));

        // Filtering users list in administration by user group.
		add_filter('views_users', array($this, 'views'));
        add_action('pre_user_query', array($this, 'user_query'));

		// Bulk edit inside the users list.
        add_filter('bulk_actions-users', array($this, 'bulk_actions_users'));
        add_filter('handle_bulk_actions-users', array($this, 'handle_bulk_actions_users'), 10, 3);
        add_action('admin_notices', array($this, 'bulk_actions_admin_notices'));
    }

    public function initialize() {
        self::$taxonomyName = apply_filters('asgarosforum_filter_user_groups_taxonomy_name', self::$taxonomyName);
    }

    // Users List in Administration.
    public function manageUsersColumns($columns) {
        $columns['forum-user-groups'] = __('Forum', 'asgaros-forum');
        return $columns;
  	}

    public function manageUsersCustomColumn($out, $column, $user_id) {
		if ($column === 'forum-user-groups') {
            $usergroups = self::getUserGroupsForUser($user_id);

    		if (!empty($usergroups)) {
        		$tags = '';

        		foreach ($usergroups as $usergroup) {
        			$href = add_query_arg(array('forum-user-group' => $usergroup->term_id), admin_url('users.php'));
                    $color = self::getUserGroupColor($usergroup->term_id);
        			$tags .= '<a class="af-usergroup-tag" style="border-color: '.$color.';" href="'.$href.'" title="'.$usergroup->description.'">'.$usergroup->name.'</a>';
        		}

        		return $tags;
            } else {
                return false;
            }
		} else {
            return $out;
        }
	}

    // Delete assigned terms when deleting a user.
    public function delete_term_relationships($user_id) {
		wp_delete_object_term_relationships($user_id, self::$taxonomyName);
	}

    // Returns all usergroups.
    public static function getUserGroups() {
        return get_terms(self::$taxonomyName, array('hide_empty' => false));
    }

    // Returns usergroup by id/slug/name/term_taxonomy_id.
    public static function getUserGroupBy($value, $by = 'id') {
        return get_term_by($by, $value, self::$taxonomyName);
    }

    // Returns color of usergroup.
    public static function getUserGroupColor($term_id) {
        return get_term_meta($term_id, 'usergroup-color', true);
    }

    // Returns usergroups of user.
    public static function getUserGroupsForUser($user_id, $fields = 'all') {
        return wp_get_object_terms($user_id, self::$taxonomyName, array('fields' => $fields));
    }

    // Returns users in usergroup.
    public static function getUsersInUserGroup($usergroup_id) {
        return get_objects_in_term($usergroup_id, self::$taxonomyName);
    }

    // Counts users in usergroup.
    public static function countUsersInUserGroup($usergroup_id) {
        return count(self::getUsersInUserGroup($usergroup_id));
    }

    // Checks if a user is in a specific user group.
    public static function isUserInUserGroup($userID, $userGroupID) {
        return is_object_in_term($userID, self::$taxonomyName, $userGroupID);
    }

    public static function saveUserGroup() {
        $usergroup_id       = $_POST['usergroup_id'];
        $usergroup_name     = trim($_POST['usergroup_name']);
        $usergroup_color    = '#444444';

        if (isset($_POST['usergroup_color'])) {
            $tmp = trim($_POST['usergroup_color']);

            if (!empty($tmp)) {
                $usergroup_color = $tmp;
            }
        }

        if (!empty($usergroup_name)) {
            if ($usergroup_id === 'new') {
                $newTerm = wp_insert_term($usergroup_name, self::$taxonomyName);

                // Return possible error.
                if (is_wp_error($newTerm)) {
                    return $newTerm;
                }

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
        $available_usergroups = self::getUserGroups();

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

    public static function filterCategories($categories) {
        global $user_ID;
        $filteredCategories = $categories;

        // Do the following checks when user is not an administrator.
        if (!is_super_admin($user_ID)) {
            $groups_of_user = self::getUserGroupsForUser($user_ID, 'ids');

            if (!empty($filteredCategories) && !is_wp_error($filteredCategories)) {
                foreach ($filteredCategories as $key => $category) {
                    $usergroups = get_term_meta($category->term_id, 'usergroups', true);

                    if (!empty($usergroups)) {
                        $intersect = array_intersect($usergroups, $groups_of_user);

                        if (empty($intersect)) {
                            unset($filteredCategories[$key]);
                        }
                    }
                }
            }
        }

        return $filteredCategories;
    }

    public static function checkAccess($categoryID) {
        global $user_ID;
        $status = true;
        $groups_of_user = self::getUserGroupsForUser($user_ID, 'ids');
        $usergroups = get_term_meta($categoryID, 'usergroups', true);

        if (!empty($usergroups) && !is_super_admin($user_ID)) {
            $status = false;

            // TODO: Optimize with early break ...
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
                $userids = array_merge($userids, self::getUsersInUserGroup($usergroup));
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

				$output .= '<input type="checkbox" name="'.self::$taxonomyName.'[]" id="'.self::$taxonomyName.'-'.$usergroup->term_id.'" value="'.$usergroup->slug.'" '.checked(true, self::isUserInUserGroup($userID, $usergroup->term_id), false).' />';
                $output .= '<label class="af-usergroup-tag" for="'.self::$taxonomyName.'-'.$usergroup->term_id.'" style="border-color: '.$color.';">';
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

	public function views($views) {
        $usergroups = self::getUserGroups();

        if ($usergroups) {
            $views['forum-user-group'] = __('Forum:', 'asgaros-forum').'&nbsp;';

            $loopCounter = 0;

            foreach ($usergroups as $term) {
                $loopCounter++;
                $cssClass = (!empty($_GET['forum-user-group']) && $_GET['forum-user-group'] == $term->term_id) ? 'class="current"' : '';

                if ($loopCounter > 1) {
                    $views['forum-user-group'] .= '&nbsp;|&nbsp;';
                }

                $views['forum-user-group'] .= '<a '.$cssClass.' href="'.admin_url('users.php?forum-user-group='.$term->term_id).'">'.$term->name.'</a>';
            }
        }

		return $views;
	}

	public function user_query($Query = '') {
		global $pagenow, $wpdb;

		if ($pagenow == 'users.php') {
            if (!empty($_GET['forum-user-group'])) {
    			$userGroup = $_GET['forum-user-group'];
    			$term = self::getUserGroupBy($userGroup);

                if (!empty($term)) {
        			$user_ids = self::getUsersInUserGroup($term->term_id);

                    if (!empty($user_ids)) {
            			$ids = implode(',', wp_parse_id_list($user_ids));
            			$Query->query_where .= " AND $wpdb->users.ID IN ($ids)";
                    } else {
                        $Query->query_where .= " AND $wpdb->users.ID IN (-1)";
                    }
                }
    		}
        } else {
            return;
        }
	}

    public function bulk_actions_users($bulk_actions) {
        $userGroups = self::getUserGroups();

        if (!empty($userGroups)) {
            // TODO: Maybe optimize those two foreach-loops into one.
            foreach ($userGroups as $usergroup) {
                $bulk_actions['forum_user_group_add_'.$usergroup->term_id] = __('Add to', 'asgaros-forum').' '.$usergroup->name;
            }

            foreach ($userGroups as $usergroup) {
                $bulk_actions['forum_user_group_remove_'.$usergroup->term_id] = __('Remove from', 'asgaros-forum').' '.$usergroup->name;
            }
        }
        return $bulk_actions;
    }

    public function handle_bulk_actions_users($redirect_to, $action, $user_ids) {
        // Check for a triggered bulk action first.
        $bulkActionFound = false;
        $userGroups = self::getUserGroups();

        if (!empty($userGroups)) {
            foreach ($userGroups as $usergroup) {
                if ($action == 'forum_user_group_add_'.$usergroup->term_id) {
                    $bulkActionFound = array('add', $usergroup->term_id);
                    break;
                } else if ($action == 'forum_user_group_remove_'.$usergroup->term_id) {
                    $bulkActionFound = array('remove', $usergroup->term_id);
                    break;
                }
            }
        }

        // Cancel handler when no bulk action found or the user_ids array is empty.
        if (!$bulkActionFound || empty($user_ids)) {
            return $redirect_to;
        }

        foreach ($user_ids as $user_id) {
            $groupsOfUser = self::getUserGroupsForUser($user_id, 'ids');

            if ($bulkActionFound[0] === 'add') {
                if (!in_array($bulkActionFound[1], $groupsOfUser)) {
                    $groupsOfUser[] = $bulkActionFound[1];
                }
            } else if ($bulkActionFound[0] === 'remove') {
                $searchKey = $key = array_search($bulkActionFound[1], $groupsOfUser);

                if ($searchKey !== false) {
                    unset($groupsOfUser[$searchKey]);
                }
            }

            self::updateUserProfileFields($user_id, $groupsOfUser, true);
        }

        $redirect_to = add_query_arg('forum_user_groups_assigned', 1, $redirect_to);
        return $redirect_to;
    }

    public function bulk_actions_admin_notices() {
        if (empty($_REQUEST['forum_user_groups_assigned'])) {
            return;
        }

        printf('<div class="updated"><p>'.__('User groups assignments updated.', 'asgaros-forum').'</p></div>');
    }
}
