<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPermissions {
    private static $instance = null;
    private static $current_user_is_moderator;
    private static $current_user_is_banned;

    // AsgarosForumPermissions instance creator
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self;
		} else {
			return self::$instance;
		}
	}

    // AsgarosForumPermissions constructor
	private function __construct() {
        add_action('init', array($this, 'setCurrentUserPermissions'));
	}

    public static function setCurrentUserPermissions() {
        global $user_ID;

        self::$current_user_is_moderator = self::isModerator($user_ID);
        self::$current_user_is_banned = self::isBanned($user_ID);
    }

    public static function isModerator($userid = false) {
        if ($userid) {
            if ($userid === 'current') {
                // Return for current user
                return self::$current_user_is_moderator;
            } else if (is_super_admin($userid)) {
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
            } else if (is_super_admin($userid)) {
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
