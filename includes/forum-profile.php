<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumProfile {
    protected static $instance = null;
    private $asgarosforum = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    protected function __clone() {}

    protected function __construct() {
        global $asgarosforum;

        $this->asgarosforum = $asgarosforum;
    }

    // Check if the profile functionality is enabled.
    public function functionalityEnabled() {
        return $this->asgarosforum->options['enable_profiles'];
    }
}
