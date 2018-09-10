<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPermissions {
    private $asgarosforum = null;
    private $currentUserIsAdministrator;
    private $currentUserIsModerator;
    private $currentUserIsBanned;
    public $currentUserID;

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
	}

    public function initialize() {
        $this->currentUserID = get_current_user_id();
        $this->currentUserIsAdministrator = $this->isAdministrator($this->currentUserID);
        $this->currentUserIsModerator = $this->isModerator($this->currentUserID);
        $this->currentUserIsBanned = $this->isBanned($this->currentUserID);
    }

    public function isAdministrator($userID = false) {
        if ($userID) {
            if ($userID === 'current') {
                // Return for current user
                return $this->currentUserIsAdministrator;
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
                return $this->currentUserIsModerator;
            } else if ($this->isAdministrator($userID)) {
                // Always true for administrators
                return true;
            } else if ($this->isBanned($userID)) {
                // Always false for banned users
                return false;
            } else if (get_user_meta($userID, 'asgarosforum_moderator', true) == 1) {
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
                return $this->currentUserIsBanned;
            } else if ($this->isAdministrator($userID)) {
                // Always false for administrators
                return false;
            } else if (get_user_meta($userID, 'asgarosforum_banned', true) == 1) {
                // And true for banned users of course. Moderators can be banned too in this case.
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
