<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumUserGroups {
    private static $asgarosforum = null;
    private static $taxonomyName = 'asgarosforum-usergroup';

    public function __construct($object) {
		self::$asgarosforum = $object;

        // Users list in administration.
        add_filter('manage_users_columns', array($this, 'manageUsersColumns'));
        add_action('manage_users_custom_column', array($this, 'manageUsersCustomColumn'), 10, 3);
        add_action('delete_user', array($this, 'delete_term_relationships'));

        // Filtering users list in administration by user group.
		add_filter('views_users', array($this, 'views'));
        add_action('pre_user_query', array($this, 'user_query'));

		/* Bulk edit */
		//add_action('admin_init', array($this, 'bulk_edit_action'));
		//add_filter('views_users', array($this, 'bulk_edit'));
    }

    // Users List in Administration.
    public function manageUsersColumns($columns) {
        $columns['forum-user-groups'] = __('Forum User Groups', 'asgaros-forum');
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
        $usergroup_color    = (!empty(trim($_POST['usergroup_color']))) ? trim($_POST['usergroup_color']) : '#444444';

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

    public static function filterCategories($filter) {
        global $user_ID;
        $groups_of_user = self::getUserGroupsForUser($user_ID, 'ids');
        $categories = get_terms('asgarosforum-category', array('hide_empty' => false)); // TODO: Produces a duplicate query.

        if (!empty($categories) && !is_wp_error($categories) && !is_super_admin($user_ID)) {
            foreach ($categories as $category) {
                $usergroups = get_term_meta($category->term_id, 'usergroups', true);

                if (!empty($usergroups)) {
                    $hide = true;

                    // TODO: Optimize with early break ...
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
            // Show name of current usergroup.
            $currentUserGroup = (!empty($_GET['forum-user-group'])) ? self::getUserGroupBy($_GET['forum-user-group']) : false;

            if ($currentUserGroup) {
                $color = self::getUserGroupColor($currentUserGroup->term_id);
                echo '<h2><div class="af-userlist-color" style="background-color: '.$color.';"></div>'.$currentUserGroup->name.'</h2>';
            }

            $form = '<form method="get" action="'.admin_url('users.php').'">';
            $form .= '<select name="forum-user-group" id="forum-user-group-select"><option value="0">'.__('Select Forum User Group ...', 'asgaros-forum').'</option>';

            foreach ($usergroups as $term) {
    			$form .= '<option value="'.$term->term_id.'"'.selected($term->term_id, ($currentUserGroup) ? $currentUserGroup->term_id : '', false).'>'.$term->name.'</option>';
    		}

            $form .= '</select></form>';

            $views['forum-user-group'] = $form;
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

    /* NOT YET IMPLEMENTED */
    /*
	function bulk_edit_action() {
		if (!isset( $_REQUEST['bulkedituser-groupsubmit'] ) || empty($_POST['user-group'])) { return; }

		check_admin_referer('bulk-edit-user-group');

		// Get an array of users from the string
		parse_str(urldecode($_POST['users']), $users);

		if(empty($users)) { return; }

		$action = $_POST['groupaction'];

        foreach($users['users'] as $user) {
			$update_groups = array();
			$groups = $this->get_usergroups_for_user($user);
			foreach($groups as $group) {
				$update_groups[$group->slug] = $group->slug;
			}

			if($action === 'add') {
				if(!in_array($_POST['user-group'], $update_groups)) {
					$update_groups[] = $_POST['user-group'];
				}
			} elseif($action === 'remove') {
				unset($update_groups[$_POST['user-group']]);
			}

			// Delete all user groups if they're empty
			if(empty($update_groups)) { $update_groups = null; }

			self::save_user_usergroups( $user, $update_groups, true);
		}
	}

	function bulk_edit($views) {
		if (!current_user_can('edit_users') ) { return $views; }
		$terms = get_terms('user-group', array('hide_empty' => false));
		?>
		<form method="post" id="bulkedituser-groupform" class="alignright" style="clear:right; margin:0 10px;">
			<fieldset>
				<legend class="screen-reader-text"><?php _e('Update User Groups', 'asgaros-forum'); ?></legend>
				<div>
					<label for="groupactionadd" style="margin-right:5px;"><input name="groupaction" value="add" type="radio" id="groupactionadd" checked="checked" /> <?php _e('Add users to', 'asgaros-forum'); ?></label>
					<label for="groupactionremove"><input name="groupaction" value="remove" type="radio" id="groupactionremove" /> <?php _e('Remove users from', 'asgaros-forum'); ?></label>
				</div>
				<div>
					<input name="users" value="" type="hidden" id="bulkedituser-groupusers" />

					<label for="usergroups-select" class="screen-reader-text"><?php _('User Group', 'asgaros-forum'); ?></label>
					<select name="user-group" id="usergroups-select" style="max-width: 300px;">
						<?php
						$select = '<option value="">'.__( 'Select User Group&hellip;', 'asgaros-forum').'</option>';
						foreach($terms as $term) {
							$select .= '<option value="'.$term->slug.'">'.$term->name.'</option>'."\n";
						}
						echo $select;
						?>
					</select>
					<?php wp_nonce_field('bulk-edit-user-group') ?>
				</div>
				<div class="clear" style="margin-top:.5em;">
					<?php submit_button( __( 'Update' ), 'small', 'bulkedituser-groupsubmit', false ); ?>
				</div>
			</fieldset>
		</form>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#bulkedituser-groupform').remove().insertAfter('ul.subsubsub');
				$('#bulkedituser-groupform').live('submit', function() {
					var users = $('.wp-list-table.users .check-column input:checked').serialize();
					$('#bulkedituser-groupusers').val(users);
				});
			});
		</script>
		<?php
		return $views;
	}*/
}
