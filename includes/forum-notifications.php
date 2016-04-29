<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumNotifications {
    private static $instance = null;

    // AsgarosForumNotifications instance creator
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self;
		} else {
			return self::$instance;
		}
	}

    // AsgarosForumNotifications constructor
	private function __construct() {
	}
}

?>
