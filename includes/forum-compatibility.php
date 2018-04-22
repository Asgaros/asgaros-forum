<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumCompatibility {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_filter('autoptimize_filter_js_exclude', array($this, 'compatibility_autoptimize'), 10 , 1);
    }

    function compatibility_autoptimize($exclude) {
        return $exclude.', wp-includes/js/tinymce';
    }
}