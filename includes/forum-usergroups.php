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
        self::initializeTaxonomy();
    }

    public static function initializeTaxonomy() {
        // Register the taxonomies.
        register_taxonomy(
			self::$taxonomyName,
			null,
			array(
				'public'        => false,
                'hierarchical'  => true,
				'rewrite'       => false
			)
		);

        self::$taxonomyName = apply_filters('asgarosforum_filter_user_groups_taxonomy_name', self::$taxonomyName);
    }

    //======================================================================
    // FUNCTIONS FOR INSERTING CONTENT.
    //======================================================================

    public static function insertUserGroup($categoryID, $userGroupName, $userGroupColor = '#444444') {
        $userGroupName = trim($userGroupName);
        $userGroupColor = trim($userGroupColor);

        $status = wp_insert_term($userGroupName, self::$taxonomyName, array('parent' => $categoryID));

        // Return possible error.
        if (is_wp_error($status)) {
            return $status;
        } else {
            $userGroupID = $status['term_id'];

            return self::updateUserGroupColor($userGroupID, $userGroupColor);
        }
    }

    public static function insertUserGroupCategory($categoryName) {
        $categoryName = trim($categoryName);

        $status = wp_insert_term($categoryName, self::$taxonomyName);

        return $status;
    }

    public static function insertUserGroupsOfForumCategory($forumCategoryID, $userGroups) {
        // Only insert user groups to a forum category when there are some. Otherwise delete them all.
        if (!empty($userGroups)) {
            update_term_meta($forumCategoryID, 'usergroups', $userGroups);
        } else {
            self::deleteUserGroupsOfForumCategory($forumCategoryID);
        }
    }

    public static function insertUserGroupsOfUsers($userID, $userGroups) {
        if (!empty($userGroups)) {
            wp_set_object_terms($userID, $userGroups, self::$taxonomyName);
            clean_object_term_cache($userID, self::$taxonomyName);
        } else {
            self::deleteUserGroupsOfUser($userID);
        }
    }

    //======================================================================
    // FUNCTIONS FOR UPDATING CONTENT.
    //======================================================================

    public static function updateUserGroup($userGroupID, $categoryID, $userGroupName, $userGroupColor = '#444444') {
        $userGroupName = trim($userGroupName);
        $userGroupColor = trim($userGroupColor);

        $status = wp_update_term($userGroupID, self::$taxonomyName, array('parent' => $categoryID, 'name' => $userGroupName));

        // Return possible error.
        if (is_wp_error($status)) {
            return $status;
        } else {
            return self::updateUserGroupColor($userGroupID, $userGroupColor);
        }
    }

    public static function updateUserGroupCategory($categoryID, $categoryName) {
        $categoryName = trim($categoryName);

        $status = wp_update_term($categoryID, self::$taxonomyName, array('name' => $categoryName));

        return $status;
    }

    public static function updateUserGroupColor($userGroupID, $userGroupColor) {
        $userGroupColor = trim($userGroupColor);
        $userGroupColor = (empty($userGroupColor)) ? '#444444' : $userGroupColor;

        $status = update_term_meta($userGroupID, 'usergroup-color', $userGroupColor);

        return $status;
    }

    //======================================================================
    // FUNCTIONS FOR DELETING CONTENT.
    //======================================================================

    public static function deleteUserGroup($userGroupID) {
        wp_delete_term($userGroupID, self::$taxonomyName);
    }

    public static function deleteUserGroupCategory($categoryID) {
        // Get all user groups of the category first.
        $userGroups = self::getUserGroupsOfCategory($categoryID);

        // Delete all user groups of the category.
        foreach ($userGroups as $group) {
            self::deleteUserGroup($group->term_id);
        }

        // Now delete the category.
        wp_delete_term($categoryID, self::$taxonomyName);
    }

    public static function deleteUserGroupsOfForumCategory($forumCategoryID) {
        delete_term_meta($forumCategoryID, 'usergroups');
    }

    public static function deleteUserGroupsOfUser($userID) {
        wp_delete_object_term_relationships($userID, self::$taxonomyName);
        clean_object_term_cache($userID, self::$taxonomyName);
    }

    //======================================================================
    // FUNCTIONS FOR GETTING CONTENT.
    //======================================================================

    // Returns a specific user group.
    public static function getUserGroup($userGroupID) {
        return get_term($userGroupID, self::$taxonomyName);
    }

    // Returns all or specific user groups.
    public static function getUserGroups($include = array()) {
        // First load all terms.
        $userGroups = get_terms(self::$taxonomyName, array('hide_empty' => false, 'include' => $include));

        // Now remove the categories so we only have user groups.
        $userGroups = array_filter($userGroups, array('AsgarosForumUserGroups', 'getUserGroupsArrayFilter'));

        return $userGroups;
    }

    // Explicit callback function for array_filter() to support older versions of PHP.
    public static function getUserGroupsArrayFilter($term) {
        return ($term->parent != 0);
    }

    // Returns all user groups of a specific category.
    public static function getUserGroupsOfCategory($categoryID) {
        return get_terms(self::$taxonomyName, array('hide_empty' => false, 'parent' => $categoryID));
    }

    // Returns all user groups categories.
    public static function getUserGroupCategories($hide_empty = false) {
        $userGroupCategories = get_terms(self::$taxonomyName, array('hide_empty' => false, 'parent' => 0));

        // Hide categories without user groups.
        if ($hide_empty) {
            foreach ($userGroupCategories as $key => $category) {
                $userGroupsInCategory = self::getUserGroupsOfCategory($category->term_id);

                if (empty($userGroupsInCategory)) {
                    unset($userGroupCategories[$key]);
                }
            }
        }

        return $userGroupCategories;
    }

    // Returns all user groups of an user.
    public static function getUserGroupsOfUser($userID, $fields = 'all') {
        return wp_get_object_terms($userID, self::$taxonomyName, array('fields' => $fields));
    }

    // Returns the color of an user group.
    public static function getUserGroupColor($userGroupID) {
        return get_term_meta($userGroupID, 'usergroup-color', true);
    }

    // Returns all user groups of a specific forum category.
    public static function getUserGroupsOfForumCategory($forumCategoryID) {
        $userGroupsIDs = self::getUserGroupsIDsOfForumCategory($forumCategoryID);

        if (!empty($userGroupsIDs)) {
            return self::getUserGroups($userGroupsIDs);
        }

        return false;
    }

    // Returns all user groups IDs of a specific forum category.
    public static function getUserGroupsIDsOfForumCategory($forumCategoryID) {
        return get_term_meta($forumCategoryID, 'usergroups', true);
    }

    // Returns all users of a user group.
    public static function getUsersOfUserGroup($userGroupID) {
        return get_objects_in_term($userGroupID, self::$taxonomyName);
    }

    //======================================================================
    // MORE FUNCTIONS.
    //======================================================================

    // Checks if a specific user can access a specific forum category.
    public static function canUserAccessForumCategory($userID, $forumCategoryID) {
        // Default status is true.
        $canAccess = true;

        // We only need to check the access when the user is not an administrator.
        if (!AsgarosForumPermissions::isAdministrator($userID)) {
            // Get user groups IDs of a forum category first.
            $userGroupsIDsOfForumCategory = self::getUserGroupsIDsOfForumCategory($forumCategoryID);

            // Only continue the check when there are user groups IDs for a forum category.
            if (!empty($userGroupsIDsOfForumCategory)) {
                // Now get the user groups IDs of a user.
                $userGroupsIDsOfUser = self::getUserGroupsOfUser($userID, 'ids');

                // Get the insersection.
                $intersection = array_intersect($userGroupsIDsOfForumCategory, $userGroupsIDsOfUser);

                // When the intersection is empty, the user cant access the forum category.
                if (empty($intersection)) {
                    $canAccess = false;
                }
            }
        }

        return $canAccess;
    }

    // Checks if a user is in a specific user group.
    public static function isUserInUserGroup($userID, $userGroupID) {
        return is_object_in_term($userID, self::$taxonomyName, $userGroupID);
    }

    // Counts the users of an user group.
    public static function countUsersOfUserGroup($userGroupID) {
        return count(self::getUsersOfUserGroup($userGroupID));
    }

    //======================================================================
    // ADDITIONAL FUNCTIONS.
    //======================================================================

    // Users List in Administration.
    public function manageUsersColumns($columns) {
        $columns['forum-user-groups'] = __('Forum', 'asgaros-forum');
        return $columns;
  	}

    public function manageUsersCustomColumn($output, $column_name, $user_id) {
		if ($column_name === 'forum-user-groups') {
            $usergroups = self::getUserGroupsOfUser($user_id);

    		if (!empty($usergroups)) {
        		foreach ($usergroups as $usergroup) {
        			$link = add_query_arg(array('forum-user-group' => $usergroup->term_id), admin_url('users.php'));
                    $color = self::getUserGroupColor($usergroup->term_id);
        			$output .= '<a class="af-usergroup-tag" style="border-color: '.$color.';" href="'.$link.'" title="'.$usergroup->name.'">'.$usergroup->name.'</a>';
        		}
            }
		}

        return $output;
	}

    public static function saveUserGroup() {
        $usergroup_id       = $_POST['usergroup_id'];
        $usergroup_name     = $_POST['usergroup_name'];
        $usergroup_category = $_POST['usergroup_category'];
        $usergroup_color    = $_POST['usergroup_color'];

        if ($usergroup_id === 'new') {
            return self::insertUserGroup($usergroup_category, $usergroup_name, $usergroup_color);
        } else {
            return self::updateUserGroup($usergroup_id, $usergroup_category, $usergroup_name, $usergroup_color);
        }
    }

    public static function saveUserGroupCategory() {
        $category_id    = $_POST['usergroup_category_id'];
        $category_name  = $_POST['usergroup_category_name'];

        if ($category_id === 'new') {
            return self::insertUserGroupCategory($category_name);
        } else {
            return self::updateUserGroupCategory($category_id, $category_name);
        }
    }

    // Adds a new user groups string to the structure page.
    public static function renderUserGroupsInCategory($categoryID) {
        $userGroupsOfForumCategory = self::getUserGroupsOfForumCategory($categoryID);

        if (!empty($userGroupsOfForumCategory)) {
            echo ' &middot; '.__('User Groups:', 'asgaros-forum').' ';

            foreach ($userGroupsOfForumCategory as $key => $userGroup) {
                if ($key > 0) {
                    echo ', ';
                }

                echo $userGroup->name;
            }
        }
    }

    public static function renderCategoryEditorFields() {
        $userGroupCategories = self::getUserGroupCategories(true);

        if (!empty($userGroupCategories)) {
            echo '<tr id="usergroups-editor">';
                echo '<th><label>'.__('User Groups:', 'asgaros-forum').'</label></th>';
                echo '<td>';
                    foreach ($userGroupCategories as $category) {
                        echo '<span>'.$category->name.':</span>';

                        $userGroups = self::getUserGroupsOfCategory($category->term_id);

                        foreach ($userGroups as $usergroup) {
                            echo '<label><input type="checkbox" name="category_usergroups[]" value="'.$usergroup->term_id.'">'.$usergroup->name.'</label>';
                        }
                    }
                    echo '<p class="description">'.__('When user groups are selected, only users of the selected user groups will have access to the category.', 'asgaros-forum').'</p>';
                echo '</td>';
            echo '</tr>';
        }
    }

    public static function renderHiddenFields($categoryID) {
        $userGroupsIDsOfForumCategory = self::getUserGroupsIDsOfForumCategory($categoryID);
        $userGroupsOfForumCategoryString = '';

        if (!empty($userGroupsIDsOfForumCategory)) {
            $userGroupsOfForumCategoryString = implode(',', $userGroupsIDsOfForumCategory);
        }

        echo '<input type="hidden" id="category_'.$categoryID.'_usergroups" value="'.$userGroupsOfForumCategoryString.'">';
    }

    public static function saveUserGroupsOfForumCategory($forumCategoryID) {
        $userGroups = isset($_POST['category_usergroups']) ? $_POST['category_usergroups'] : '';

        self::insertUserGroupsOfForumCategory($forumCategoryID, $userGroups);
    }

    public static function filterCategories($unfilteredCategories) {
        global $user_ID;
        $filteredCategories = $unfilteredCategories;

        // We only need to filter when the user is not an administrator and when there are categories to filter.
        if (!AsgarosForumPermissions::isAdministrator('current') && !empty($filteredCategories) && !is_wp_error($filteredCategories)) {
            foreach ($filteredCategories as $key => $forumCategory) {
                $canAccess = self::canUserAccessForumCategory($user_ID, $forumCategory->term_id);

                if (!$canAccess) {
                    unset($filteredCategories[$key]);
                }
            }
        }

        return $filteredCategories;
    }

    public static function checkAccess($forumCategoryID) {
        global $user_ID;

        return self::canUserAccessForumCategory($user_ID, $forumCategoryID);
    }

    // Makes sure that only users who have access to the forum category will receive mails.
    public static function filterSubscriberMails($mails, $forumCategoryID) {
        // Only filter when there are mails.
        if (!empty($mails)) {
            foreach ($mails as $key => $mail) {
                // Get the user of the mail.
                $userObject = get_user_by('email', $mail);

                if (!empty($userObject)) {
                    $canAccess = self::canUserAccessForumCategory($userObject->ID, $forumCategoryID);

                    // When the user cant access the user group, remove it from the mail list.
                    if (!$canAccess) {
                        unset($mails[$key]);
                    }
                }
            }
        }

        return $mails;
    }

    public static function showUserProfileFields($userID) {
        $output = '';
        $userGroupCategories = self::getUserGroupCategories(true);

        if (!empty($userGroupCategories)) {
            $output .= '<tr class="usergroups-editor">';
            $output .= '<th><label>'.__('User Groups', 'asgaros-forum').'</label></th>';
            $output .= '<td>';

            foreach ($userGroupCategories as $category) {
                $output .= '<span>'.$category->name.':</span>';

                $userGroups = self::getUserGroupsOfCategory($category->term_id);

                foreach ($userGroups as $usergroup) {
                    $color = self::getUserGroupColor($usergroup->term_id);

    				$output .= '<input type="checkbox" name="'.self::$taxonomyName.'[]" id="'.self::$taxonomyName.'-'.$usergroup->term_id.'" value="'.$usergroup->term_id.'" '.checked(true, self::isUserInUserGroup($userID, $usergroup->term_id), false).'>';
                    $output .= '<label class="af-usergroup-tag" for="'.self::$taxonomyName.'-'.$usergroup->term_id.'" style="border-color: '.$color.';">';
                    $output .= $usergroup->name;
                    $output .= '</label>';
                    $output .= '<br />';
                }
			}

            $output .= '</td>';
    		$output .= '</tr>';
		}

        return $output;
    }

    public static function updateUserProfileFields($user_id) {
        $user_groups = isset($_POST[self::$taxonomyName]) ? array_map('intval', $_POST[self::$taxonomyName]) : array();

		self::insertUserGroupsOfUsers($user_id, $user_groups);
    }

    public function views($views) {
        $usergroups = self::getUserGroups();

        if ($usergroups) {
            $views['forum-user-group'] = __('Forum:', 'asgaros-forum').'&nbsp;';

            $loopCounter = 0;

            foreach ($usergroups as $term) {
                $loopCounter++;
                $cssClass = (!empty($_GET['forum-user-group']) && $_GET['forum-user-group'] == $term->term_id) ? 'class="current"' : '';
                $usersCounter = self::countUsersOfUserGroup($term->term_id);

                if ($loopCounter > 1) {
                    $views['forum-user-group'] .= '&nbsp;|&nbsp;';
                }

                $views['forum-user-group'] .= '<a '.$cssClass.' href="'.admin_url('users.php?forum-user-group='.$term->term_id).'">'.$term->name.'</a> ('.$usersCounter.')';
            }
        }

		return $views;
	}

    // Delete assigned terms when deleting a user.
    public function delete_term_relationships($user_id) {
        self::deleteUserGroupsOfUser($user_id);
	}

    public function user_query($Query = '') {
		global $pagenow, $wpdb;

		if ($pagenow == 'users.php') {
            if (!empty($_GET['forum-user-group'])) {
    			$userGroupID = $_GET['forum-user-group'];
    			$term = self::getUserGroup($userGroupID);

                if (!empty($term)) {
        			$user_ids = self::getUsersOfUserGroup($term->term_id);

                    if (!empty($user_ids)) {
            			$ids = implode(',', wp_parse_id_list($user_ids));
            			$Query->query_where .= " AND $wpdb->users.ID IN ($ids)";
                    } else {
                        $Query->query_where .= " AND $wpdb->users.ID IN (-1)";
                    }
                }
    		}
        }
	}

    public function bulk_actions_users($bulk_actions) {
        $userGroups = self::getUserGroups();

        if (!empty($userGroups)) {
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
        // Cancel when the user_ids array is empty.
        if (empty($user_ids)) {
            return $redirect_to;
        }

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

        // Cancel when no bulk action found.
        if (!$bulkActionFound) {
            return $redirect_to;
        }

        foreach ($user_ids as $user_id) {
            $groupsOfUser = self::getUserGroupsOfUser($user_id, 'ids');

            if ($bulkActionFound[0] === 'add') {
                if (!in_array($bulkActionFound[1], $groupsOfUser)) {
                    $groupsOfUser[] = $bulkActionFound[1];
                }
            } else if ($bulkActionFound[0] === 'remove') {
                $searchKey = array_search($bulkActionFound[1], $groupsOfUser);

                if (!$searchKey) {
                    unset($groupsOfUser[$searchKey]);
                }
            }

            self::insertUserGroupsOfUsers($user_id, $groupsOfUser);
        }

        $redirect_to = add_query_arg('forum_user_groups_assigned', 1, $redirect_to);
        return $redirect_to;
    }

    public function bulk_actions_admin_notices() {
        if (!empty($_REQUEST['forum_user_groups_assigned'])) {
            printf('<div class="updated"><p>'.__('User groups assignments updated.', 'asgaros-forum').'</p></div>');
        }
    }
}
