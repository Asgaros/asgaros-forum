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
}

?>
