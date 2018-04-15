<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPermissions {
    private static $asgarosforum = null;
    private static $currentUserIsAdministrator;
    private static $currentUserIsModerator;
    private static $currentUserIsBanned;
    public static $currentUserID;

    public function __construct($object) {
        self::$asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
	}

    public function initialize() {
        self::$currentUserID = get_current_user_id();
        self::$currentUserIsAdministrator = self::isAdministrator(self::$currentUserID);
        self::$currentUserIsModerator = self::isModerator(self::$currentUserID);
        self::$currentUserIsBanned = self::isBanned(self::$currentUserID);
    }

    public static function isAdministrator($userID = false) {
        if ($userID) {
            if ($userID === 'current') {
                // Return for current user
                return self::$currentUserIsAdministrator;
            } else if (is_super_admin($userID) || user_can($userID, 'administrator')) {
                // Always true for administrators
                return true;
            } else {
                // Otherwise false ...
                return false;
            }
        } else {
            // Otherwise false ...
            return false;
        }
    }

    public static function isModerator($userID = false) {
        if ($userID) {
            if ($userID === 'current') {
                // Return for current user
                return self::$currentUserIsModerator;
            } else if (self::isAdministrator($userID)) {
                // Always true for administrators
                return true;
            } else if (self::isBanned($userID)) {
                // Always false for banned users
                return false;
            } else if (get_user_meta($userID, 'asgarosforum_moderator', true) == 1) {
                // And true for moderators of course ...
                return true;
            } else {
                // Otherwise false ...
                return false;
            }
        } else {
            // Otherwise false ...
            return false;
        }
    }

    public static function isBanned($userID = false) {
        if ($userID) {
            if ($userID === 'current') {
                // Return for current user
                return self::$currentUserIsBanned;
            } else if (self::isAdministrator($userID)) {
                // Always false for administrators
                return false;
            } else if (get_user_meta($userID, 'asgarosforum_banned', true) == 1) {
                // And true for banned users of course. Moderators can be banned too in this case.
                return true;
            } else {
                // Otherwise false ...
                return false;
            }
        } else {
            // Otherwise false ...
            return false;
        }
    }

    public static function getForumRole($userID) {
        if (self::isAdministrator($userID)) {
            return __('Administrator', 'asgaros-forum');
        } else if (self::isModerator($userID)) {
            return __('Moderator', 'asgaros-forum');
        } else if (self::isBanned($userID)) {
            return __('Banned', 'asgaros-forum');
        } else {
            return __('User', 'asgaros-forum');
        }
    }

    public static function canUserAccessForumCategory($userID, $forumCategoryID) {
        $access_level = get_term_meta($forumCategoryID, 'category_access', true);

        if ($access_level == 'moderator' && !AsgarosForumPermissions::isModerator('current')) {
            return false;
        }

        return true;
    }

    // This function checks if a user can edit a specified post. Optional parameters for author_id and post_date available to reduce database queries.
    public static function can_edit_post($user_id, $post_id, $author_id = false, $post_date = false) {
        // Disallow when user is banned.
        if (self::isBanned($user_id)) {
            return false;
        }

        // Allow when user is moderator.
        if (self::isModerator($user_id)) {
            return true;
        }

        // Disallow when user is not the author of a post.
        $author_id = ($author_id) ? $author_id : self::$asgarosforum->get_post_author($post_id);

        if ($user_id != $author_id) {
            return false;
        }

        // Allow when there is no time limitation.
        $time_limitation = self::$asgarosforum->options['time_limit_edit_posts'];

        if ($time_limitation == 0) {
            return true;
        }

        // Otherwise decision based on current time.
        $date_creation = ($post_date) ? $post_date : self::$asgarosforum->get_post_date($post_id);
        $date_creation = strtotime($date_creation);
        $date_now = strtotime(self::$asgarosforum->current_time());
        $date_difference = $date_now - $date_creation;

        if (($time_limitation * 60) < $date_difference) {
            return false;
        } else {
            return true;
        }
    }
}

?>
