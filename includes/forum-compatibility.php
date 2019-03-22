<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumCompatibility {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;

        $this->compatibility_autoptimize();
        $this->compatibility_yoastseo();
        $this->compatibility_rankmathseo();
    }

    // AUTOPTIMIZE
    function compatibility_autoptimize() {
        add_filter('autoptimize_filter_js_exclude', array($this, 'comp_autoptimize_filter_js_exclude'), 10 , 1);
    }

    function comp_autoptimize_filter_js_exclude($exclude) {
        return $exclude.', wp-includes/js/tinymce';
    }

    // YOASTSEO
    function compatibility_yoastseo() {
        add_action('template_redirect', array($this, 'comp_yoastseo_template_redirect'));
    }

    function comp_yoastseo_template_redirect() {
        if ($this->asgarosforum->executePlugin) {
            // Old API.
            global $wpseo_front;

            if ($wpseo_front) {
                remove_action('wp_head', array($wpseo_front, 'head'), 1);
                return;
            }

            // New API.
            if (class_exists('WPSEO_Frontend')) {
                $wpseo_front = WPSEO_Frontend::get_instance();
                remove_action('wp_head', array($wpseo_front, 'head'), 1);
            }
        }
    }

    // RANK MATH SEO
    function compatibility_rankmathseo() {
        add_action('wp_head', array($this, 'comp_rankmathseo_wp_head'), 1);
    }

    function comp_rankmathseo_wp_head() {
        if ($this->asgarosforum->executePlugin) {
            remove_all_actions('rank_math/head');
            add_filter('rank_math/frontend/remove_credit_notice', '__return_true');
        }
    }
}
