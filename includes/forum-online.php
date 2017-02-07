<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumOnline {
    private static $asgarosforum = null;
    private static $userID = null;
    private static $currentTimeStamp = null;
    private static $functionalityEnabled = false;
    private static $intervalUpdate = 1;
    private static $intervalOnline = 10;
    private static $onlineList = array();

    public function __construct($object) {
		self::$asgarosforum = $object;
        self::$functionalityEnabled = self::$asgarosforum->options['show_who_is_online'];
    }

    public static function updateOnlineStatus() {
        if (self::$functionalityEnabled) {
            self::$userID = get_current_user_id();
            self::$currentTimeStamp = self::$asgarosforum->current_time();

            // Only run timestamp logic for loggedin users.
            if (self::$userID) {
                // Get the timestamp of the current user.
                $userTimeStamp = get_user_meta(self::$userID, 'asgarosforum_online_timestamp', true);

                // If there is no timestamp for that user of the update interval passed, create or update it.
                if (!$userTimeStamp || ((strtotime(self::$currentTimeStamp) - strtotime($userTimeStamp)) > (self::$intervalUpdate * 60))) {
                    update_user_meta(self::$userID, 'asgarosforum_online_timestamp', self::$currentTimeStamp);
                    $userTimeStamp = self::$currentTimeStamp;
                }
            }

            // Load list of online users.
            self::loadOnlineList();
        }
    }

    public static function loadOnlineList() {
        $minimumCheckTime = date_i18n('Y-m-d H:i:s', (strtotime(self::$currentTimeStamp) - (self::$intervalOnline * 60)));

        // Get list of online users.
        self::$onlineList = get_users(
            array(
                'fields'        => 'id',
                'meta_query'    => array(
                    'relation'  => 'AND',
                    array(
                        'key'       => 'asgarosforum_online_timestamp',
                        'compare'   => 'EXISTS'
                    ),
                    array(
                        'key'       => 'asgarosforum_online_timestamp',
                        'value'     => $minimumCheckTime,
                        'compare'   => '>='
                    )
                )
            )
        );
    }

    public static function renderStatisticsElement() {
        if (self::$functionalityEnabled) {
            AsgarosForumStatistics::renderStatisticsElement(__('Online', 'asgaros-forum'), count(self::$onlineList), 'dashicons-before dashicons-lightbulb');
        }
    }

    public static function isUserOnline($userID) {
        if (self::$functionalityEnabled && in_array($userID, self::$onlineList)) {
            return true;
        } else {
            return false;
        }
    }

    public static function deleteUserTimeStamp() {
        delete_user_meta(get_current_user_id(), 'asgarosforum_online_timestamp');
    }
}
