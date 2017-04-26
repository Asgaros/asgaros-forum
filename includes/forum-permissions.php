<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPermissions {
    private static $asgarosforum = null;
    private static $current_user_is_moderator;
    private static $current_user_is_banned;
    public static $current_user_id;

    public function __construct($object) {
        self::$asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
	}

    public function initialize() {
        self::$current_user_id = get_current_user_id();
        self::$current_user_is_moderator = self::isModerator(self::$current_user_id);
        self::$current_user_is_banned = self::isBanned(self::$current_user_id);
    }

    public static function isModerator($userid = false) {
        if ($userid) {
            if ($userid === 'current') {
                // Return for current user
                return self::$current_user_is_moderator;
            } else if (is_super_admin($userid) || user_can($userid, 'administrator')) {
                // Always true for administrators
                return true;
            } else if (get_user_meta($userid, 'asgarosforum_banned', true) == 1) {
                // Always false for banned users
                return false;
            } else if (get_user_meta($userid, 'asgarosforum_moderator', true) == 1) {
                // And true for moderators of course ...
                return true;
            }
        } else {
            // Otherwise false ...
            return false;
        }
    }

    public static function isBanned($userid = false) {
        if ($userid) {
            if ($userid === 'current') {
                // Return for current user
                return self::$current_user_is_banned;
            } else if (is_super_admin($userid) || user_can($userid, 'administrator')) {
                // Always false for administrators
                return false;
            } else if (get_user_meta($userid, 'asgarosforum_banned', true) == 1) {
                // And true for banned users of course. Moderators can be banned too in this case.
                return true;
            }
        } else {
            // Otherwise false ...
            return false;
        }
    }
}

?>
