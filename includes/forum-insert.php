<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumInsert {
    private static $action = false;

    // Data for insertion is stored here.
    private static $dataID; // This ID is only used when editing a post.
    private static $dataSubject;
    private static $dataContent;

    public static function determineAction() {
        if ($_POST['submit_action'] === 'add_thread' || $_POST['submit_action'] === 'add_post' || $_POST['submit_action'] === 'edit_post') {
            self::$action = $_POST['submit_action'];
        }
    }

    public static function getAction() {
        return self::$action;
    }

    public static function setData() {
    }

    public static function validateData() {
        global $asgarosforum;

        // Cancel if there is already an error.
        if (!empty($asgarosforum->error)) {
            return false;
        }

        // Cancel if the current user is banned.
        if (AsgarosForumPermissions::isBanned('current')) {
            $asgarosforum->error .= '<span>'.__('You are banned!', 'asgaros-forum').'</span>';
            return false;
        }
    }

    public static function insertData() {

    }
}

?>
