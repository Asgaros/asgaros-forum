<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumShortcodes {
    private static $asgarosforum = null;
    public static $shortcodeSearchFilter = '';
    public static $includeCategories = false;

    public function __construct($object) {
		self::$asgarosforum = $object;

        // Register multiple shortcodes because sometimes users ignore the fact that shortcodes are case-sensitive.
        add_shortcode('forum', array(self::$asgarosforum, 'forum'));
        add_shortcode('Forum', array(self::$asgarosforum, 'forum'));
    }

    static function checkAttributes($atts) {
        // Normalize attribute keys.
        $atts = array_change_key_case((array)$atts, CASE_LOWER);

        if (!empty($atts['post'])) {
            $postID = $atts['post'];

            self::$asgarosforum->current_view = 'post';
            self::$asgarosforum->setParents($postID, 'post');
        } else if (!empty($atts['topic'])) {
            $topicID = $atts['topic'];
            $allowedViews = array('movetopic', 'addpost', 'editpost', 'thread');

            // Ensure that we are in the correct element.
            if (self::$asgarosforum->current_topic != $topicID) {
                self::$asgarosforum->setParents($topicID, 'topic');
                self::$asgarosforum->current_view = 'thread';
            }

            // Ensure that we are in a correct view.
            else if (!in_array(self::$asgarosforum->current_view, $allowedViews)) {
                self::$asgarosforum->current_view = 'thread';
            }

            // Check category access.
            self::$asgarosforum->check_access();

            // Configure components.
            self::$asgarosforum->options['enable_search'] = false;
            AsgarosForumBreadCrumbs::$breadCrumbsLevel = 1;
        } else if (!empty($atts['forum'])) {
            $forumID = $atts['forum'];
            $allowedViews = array('forum', 'addtopic', 'movetopic', 'addpost', 'editpost', 'thread', 'search');

            // Ensure that we are in the correct element.
            if (self::$asgarosforum->current_forum != $forumID && self::$asgarosforum->parent_forum != $forumID) {
                self::$asgarosforum->setParents($forumID, 'forum');

                // Only change view when not inside the search.
                if (self::$asgarosforum->current_view != 'search') {
                    self::$asgarosforum->current_view = 'forum';
                }
            }

            // Ensure that we are in a correct view.
            else if (!in_array(self::$asgarosforum->current_view, $allowedViews)) {
                self::$asgarosforum->current_view = 'forum';
            }

            // Check category access.
            self::$asgarosforum->check_access();

            // Configure components.
            if (self::$asgarosforum->parent_forum != $forumID) {
                AsgarosForumBreadCrumbs::$breadCrumbsLevel = 2;
            } else {
                AsgarosForumBreadCrumbs::$breadCrumbsLevel = 3;
            }

            self::$shortcodeSearchFilter = 'AND (f.id = '.$forumID.' OR f.parent_forum = '.$forumID.')';
        } else if (!empty($atts['category'])) {
            self::$includeCategories = explode(',', $atts['category']);

            // Ensure that we are in the correct element.
            if (!in_array(self::$asgarosforum->current_category, self::$includeCategories)) {
                self::$asgarosforum->current_category = false;
                self::$asgarosforum->parent_forum     = false;
                self::$asgarosforum->current_forum    = false;
                self::$asgarosforum->current_topic    = false;
                self::$asgarosforum->current_post     = false;

                // Only change view when not inside the search.
                if (self::$asgarosforum->current_view != 'search') {
                    self::$asgarosforum->current_view = 'default';
                }
            }

            // Check category access.
            if (self::$asgarosforum->current_category) {
                self::$asgarosforum->check_access();
            }
        }
    }

    // Prevent the execution of specific shortcodes inside of posts.
    static function filterShortcodes($tags_to_remove, $content) {
        $tags_to_remove = array();
        $tags_to_remove[] = 'forum';
        $tags_to_remove[] = 'Forum';
        $tags_to_remove = apply_filters('asgarosforum_filter_post_shortcodes', $tags_to_remove);
        return $tags_to_remove;
    }
}
