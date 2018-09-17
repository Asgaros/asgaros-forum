<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPermissions {
    private $asgarosforum = null;
    public $currentUserID;

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
        add_action('asgarosforum_prepare_profile', array($this, 'change_ban_status'));

        // Users list in administration.
        add_filter('manage_users_columns', array($this, 'manage_users_columns'));
        add_action('manage_users_custom_column', array($this, 'manage_users_custom_column'), 10, 3);
	}

    public function initialize() {
        $this->currentUserID = get_current_user_id();
    }

    public function isSiteAdministrator($user_id = false) {
        if ($user_id) {
            if ($user_id === 'current') {
                // Return for current user
                return $this->isSiteAdministrator($this->currentUserID);
            } else if (is_super_admin($user_id) || user_can($user_id, 'administrator')) {
                // Always true for site administrators
                return true;
            }
        }

        // Otherwise false ...
        return false;
    }

    public function isAdministrator($userID = false) {
        if ($userID) {
            if ($userID === 'current') {
                // Return for current user
                return $this->isAdministrator($this->currentUserID);
            } else if ($this->isSiteAdministrator($userID)) {
                // Always true for site administrators
                return true;
            } else if ($this->get_forum_role($userID) === 'administrator') {
                // And true for forum administrators of course ...
                return true;
            }
        }

        // Otherwise false ...
        return false;
    }

    public function isModerator($userID = false) {
        if ($userID) {
            if ($userID === 'current') {
                // Return for current user
                return $this->isModerator($this->currentUserID);
            } else if ($this->isAdministrator($userID)) {
                // Always true for (site) administrators
                return true;
            } else if ($this->get_forum_role($userID) === 'moderator') {
                // And true for moderators of course ...
                return true;
            }
        }

        // Otherwise false ...
        return false;
    }

    public function isBanned($userID = false) {
        if ($userID) {
            if ($userID === 'current') {
                // Return for current user
                return $this->isBanned($this->currentUserID);
            } else if ($this->isSiteAdministrator($userID)) {
                // Ensure that site-administrators cannot get banned.
                return false;
            } else if ($this->get_forum_role($userID) === 'banned') {
                // And true for banned users of course ...
                return true;
            }
        }

        // Otherwise false ...
        return false;
    }

    public function getForumRole($userID) {
        if ($this->isAdministrator($userID)) {
            return __('Administrator', 'asgaros-forum');
        } else if ($this->isModerator($userID)) {
            return __('Moderator', 'asgaros-forum');
        } else if ($this->isBanned($userID)) {
            return __('Banned', 'asgaros-forum');
        } else {
            return __('User', 'asgaros-forum');
        }
    }

    public function get_forum_role($user_id) {
        $role = get_user_meta($user_id, 'asgarosforum_role', true);

        if (!empty($role)) {
            return $role;
        }

        return 'normal';
    }

    public function set_forum_role($user_id, $role) {
        switch ($role) {
            case 'normal':
                delete_user_meta($user_id, 'asgarosforum_role');
            break;
            case 'moderator':
                update_user_meta($user_id, 'asgarosforum_role', 'moderator');
            break;
            case 'administrator':
                update_user_meta($user_id, 'asgarosforum_role', 'administrator');
            break;
            case 'banned':
                update_user_meta($user_id, 'asgarosforum_role', 'banned');
            break;
        }
    }

    public function canUserAccessForumCategory($userID, $forumCategoryID) {
        $access_level = get_term_meta($forumCategoryID, 'category_access', true);

        if ($access_level == 'moderator' && !$this->isModerator($userID)) {
            return false;
        }

        return true;
    }

    // This function checks if a user can edit a specified post. Optional parameters for author_id and post_date available to reduce database queries.
    public function can_edit_post($user_id, $post_id, $author_id = false, $post_date = false) {
        // Disallow when user is banned.
        if ($this->isBanned($user_id)) {
            return false;
        }

        // Allow when user is moderator.
        if ($this->isModerator($user_id)) {
            return true;
        }

        // Disallow when user is not the author of a post.
        $author_id = ($author_id) ? $author_id : $this->asgarosforum->get_post_author($post_id);

        if ($user_id != $author_id) {
            return false;
        }

        // Allow when there is no time limitation.
        $time_limitation = $this->asgarosforum->options['time_limit_edit_posts'];

        if ($time_limitation == 0) {
            return true;
        }

        // Otherwise decision based on current time.
        $date_creation = ($post_date) ? $post_date : $this->asgarosforum->get_post_date($post_id);
        $date_creation = strtotime($date_creation);
        $date_now = strtotime($this->asgarosforum->current_time());
        $date_difference = $date_now - $date_creation;

        if (($time_limitation * 60) < $date_difference) {
            return false;
        } else {
            return true;
        }
    }

    // Check if a user can ban another user.
    public function can_ban_user($user_id, $ban_id) {
        if ($this->isSiteAdministrator($user_id)) {
            // Site administrators cannot ban other site administrators.
            if ($this->isSiteAdministrator($ban_id)) {
                return false;
            }

            return true;
        }


        if ($this->isAdministrator($user_id)) {
            // Administrators cannot ban other (site) administrators.
            if ($this->isAdministrator($ban_id)) {
                return false;
            }

            return true;
        }

        if ($this->isModerator($user_id)) {
            // Moderators cannot ban other administrators/moderators.
            // Hint: This function also works for administrators because the
            // moderator-check function also return TRUE for administrators.
            if ($this->isModerator($ban_id)) {
                return false;
            }

            return true;
        }

        // Otherwise the user cannot ban.
        return false;
    }

    public function ban_user($user_id, $ban_id) {
        // Verify nonce first.
        if (wp_verify_nonce($_REQUEST['_wpnonce'], 'ban_user_'.$ban_id)) {
            // Check if the current user can ban another user.
            if ($this->can_ban_user($user_id, $ban_id)) {
                // Ensure that the user is not already banned.
                if (!$this->isBanned($ban_id)) {
                    $this->set_forum_role($ban_id, 'banned');
                }
            }
        }
    }

    public function unban_user($user_id, $unban_id) {
        // Verify nonce first.
        if (wp_verify_nonce($_REQUEST['_wpnonce'], 'unban_user_'.$unban_id)) {
            // Check if the current user can ban another user.
            if ($this->can_ban_user($user_id, $unban_id)) {
                // Ensure that the user is banned.
                if ($this->isBanned($unban_id)) {
                    $this->set_forum_role($unban_id, 'normal');
                }
            }
        }
    }

    public function change_ban_status() {
        if (!empty($_GET['ban_user'])) {
            $user_id = get_current_user_id();
            $ban_id = $_GET['ban_user'];

            $this->ban_user($user_id, $ban_id);
        }

        if (!empty($_GET['unban_user'])) {
            $user_id = get_current_user_id();
            $unban_id = $_GET['unban_user'];

            $this->unban_user($user_id, $unban_id);
        }
    }

    /*
    public function get_by_role($role) {
        switch ($role) {
            case 'moderator':
                return get_users(array(
                    'fields'            => array('ID', 'display_name'),
                    'meta_query'        => array(
                        array(
                            'key'       => 'asgarosforum_role',
                            'value'     => 'moderator'
                        )
                    ),
                    'role__not_in'      => array('administrator')
                ));
            break;
            case 'administrator':
                return get_users(array(
                    'fields'            => array('ID', 'display_name'),
                    'meta_query'        => array(
                        array(
                            'key'       => 'asgarosforum_role',
                            'value'     => 'administrator'
                        )
                    ),
                    'role__not_in'      => array('administrator')
                ));
            break;
            case 'banned':
                return get_users(array(
                    'fields'            => array('ID', 'display_name'),
                    'meta_query'        => array(
                        array(
                            'key'       => 'asgarosforum_role',
                            'value'     => 'banned'
                        )
                    ),
                    'role__not_in'      => array('administrator')
                ));
            break;
        }

        return false;
    }
    */

    // Users List in Administration.
    public function manage_users_columns($columns) {
        $columns['forum-user-role'] = __('Forum Role', 'asgaros-forum');
        return $columns;
  	}

    public function manage_users_custom_column($output, $column_name, $user_id) {
		if ($column_name === 'forum-user-role') {
            $output .= $this->getForumRole($user_id);
		}

        return $output;
	}

    /*
    public function views($views) {
        $views['forum-user-view'] = __('Forum:', 'asgaros-forum').'&nbsp;';

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

		return $views;
	}
    */
}
