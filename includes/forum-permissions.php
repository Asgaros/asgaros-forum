<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPermissions {
    private $asgarosforum = null;
    public $currentUserID;

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
	}

    public function initialize() {
        $this->currentUserID = get_current_user_id();
    }

    public function isAdministrator($userID = false) {
        if ($userID) {
            if ($userID === 'current') {
                // Return for current user
                return $this->isAdministrator($this->currentUserID);
            } else if (is_super_admin($userID) || user_can($userID, 'administrator')) {
                // Always true for administrators
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
                // Always true for administrators
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
            } else if ($this->isAdministrator($userID)) {
                // Always false for administrators
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
            case 'banned':
                update_user_meta($user_id, 'asgarosforum_role', 'banned');
            break;
        }
    }

    // TODO: Possible data leak because $userID is not checked ...
    public function canUserAccessForumCategory($userID, $forumCategoryID) {
        $access_level = get_term_meta($forumCategoryID, 'category_access', true);

        if ($access_level == 'moderator' && !$this->isModerator('current')) {
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
}

?>
