<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumShortcodes {
    static function checkAttributes($atts) {
        global $asgarosforum;
        //$asgarosforum->debugOutput($atts);

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
