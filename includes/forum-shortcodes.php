<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumShortcodes {
    private static $asgarosforum = null;

    public function __construct($object) {
		self::$asgarosforum = $object;

        // Register multiple shortcodes because sometimes users ignore the fact that shortcodes are case-sensitive.
        add_shortcode('forum', array(self::$asgarosforum, 'forum'));
        add_shortcode('Forum', array(self::$asgarosforum, 'forum'));
    }

    static function checkAttributes($atts) {
        global $asgarosforum;

        // Normalize attribute keys.
        $atts = array_change_key_case((array)$atts, CASE_LOWER);

        if (!empty($atts['post'])) {
            $postID = $atts['post'];
            $asgarosforum->current_view = 'post';
            $asgarosforum->setParents($postID, 'post');
        }
    }

    // Prevent the execution of specific shortcodes inside of posts.
    static function filterShortcodes($tags_to_remove, $content) {
        global $asgarosforum;

        $tags_to_remove = array();
        $tags_to_remove[] = 'forum';
        $tags_to_remove[] = 'Forum';
        $tags_to_remove = apply_filters('asgarosforum_filter_post_shortcodes', $tags_to_remove);

        return $tags_to_remove;
    }
}
