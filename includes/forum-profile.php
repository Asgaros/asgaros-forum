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

    // Sets the current view when the functionality is enabled.
    public function setCurrentView() {
        if ($this->functionalityEnabled()) {
            $this->asgarosforum->current_view = 'profile';
        } else {
            $this->asgarosforum->current_view = 'overview';
        }
    }

    // Sets the current title.
    public function setCurrentTitle() {
        $titleSuffix = '';

        if (!empty($_GET['id'])) {
            $userID = absint($_GET['id']);
            $userData = get_user_by('id', $userID);

            if ($userData) {
                $titleSuffix = ': '.$userData->display_name;
            }
        }
        
        $this->asgarosforum->current_title = __('Profile', 'asgaros-forum').$titleSuffix;
    }
}
