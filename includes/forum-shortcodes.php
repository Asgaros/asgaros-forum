<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumShortcodes {
    private static $asgarosforum = null;
    private static $postObject = null;
    public static $shortcodeSearchFilter = '';
    public static $includeCategories = false;

    public function __construct($object) {
		self::$asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
    }

    public function initialize() {
        // Register multiple shortcodes because sometimes users ignore the fact that shortcodes are case-sensitive.
        add_shortcode('forum', array(self::$asgarosforum, 'forum'));
        add_shortcode('Forum', array(self::$asgarosforum, 'forum'));
    }

    public static function checkForShortcode($object = false) {
        self::$postObject = $object;

        // If no post-object is set, use the location.
        if (!self::$postObject && self::$asgarosforum->options['location']) {
            self::$postObject = get_post(self::$asgarosforum->options['location']);
        }

        if (self::$postObject && (has_shortcode(self::$postObject->post_content, 'forum') || has_shortcode(self::$postObject->post_content, 'Forum'))) {
            return true;
        } else {
            return false;
        }
    }

    public static function handleAttributes() {
        $atts = array();
        $pattern = get_shortcode_regex();

        if (preg_match_all('/'.$pattern.'/s', self::$postObject->post_content, $matches) && array_key_exists(2, $matches) && (in_array('forum', $matches[2]) || in_array('Forum', $matches[2]))) {
            $atts = shortcode_parse_atts($matches[3][0]);

            if (!empty($atts)) {
                // Normalize attribute keys.
                $atts = array_change_key_case((array)$atts, CASE_LOWER);

                if (!empty($atts['post']) && ctype_digit($atts['post'])) {
                    $postID = $atts['post'];
                    self::$asgarosforum->current_view = 'post';
                    self::$asgarosforum->setParents($postID, 'post');
                } else if (!empty($atts['topic']) && ctype_digit($atts['topic'])) {
                    $topicID = $atts['topic'];
                    $allowedViews = array('movetopic', 'addpost', 'editpost', 'thread', 'profile');

                    // Ensure that we are in the correct element.
                    if (self::$asgarosforum->current_topic != $topicID) {
                        self::$asgarosforum->setParents($topicID, 'topic');
                        self::$asgarosforum->current_view = 'thread';
                    } else if (!in_array(self::$asgarosforum->current_view, $allowedViews)) {
                        // Ensure that we are in an allowed view.
                        self::$asgarosforum->current_view = 'thread';
                    }

                    // Configure components.
                    self::$asgarosforum->options['enable_search'] = false;
                    AsgarosForumBreadCrumbs::$breadCrumbsLevel = 1;
                } else if (!empty($atts['forum']) && ctype_digit($atts['forum'])) {
                    $forumID = $atts['forum'];
                    $allowedViews = array('forum', 'addtopic', 'movetopic', 'addpost', 'editpost', 'thread', 'search', 'subscriptions', 'profile');

                    // Ensure that we are in the correct element.
                    if (self::$asgarosforum->current_forum != $forumID && self::$asgarosforum->parent_forum != $forumID && self::$asgarosforum->current_view != 'search' && self::$asgarosforum->current_view != 'subscriptions' && self::$asgarosforum->current_view != 'profile') {
                        self::$asgarosforum->setParents($forumID, 'forum');
                        self::$asgarosforum->current_view = 'forum';
                    } else if (!in_array(self::$asgarosforum->current_view, $allowedViews)) {
                        // Ensure that we are in an allowed view.
                        self::$asgarosforum->current_view = 'forum';
                    }

                    // Configure components.
                    AsgarosForumBreadCrumbs::$breadCrumbsLevel = (self::$asgarosforum->parent_forum != $forumID) ? 2 : 3;
                    self::$shortcodeSearchFilter = 'AND (f.id = '.$forumID.' OR f.parent_forum = '.$forumID.')';
                } else if (!empty($atts['category'])) {
                    self::$includeCategories = explode(',', $atts['category']);

                    // Ensure that we are in the correct element.
                    if (!in_array(self::$asgarosforum->current_category, self::$includeCategories) && self::$asgarosforum->current_view != 'search') {
                        self::$asgarosforum->current_category   = false;
                        self::$asgarosforum->parent_forum       = false;
                        self::$asgarosforum->current_forum      = false;
                        self::$asgarosforum->current_topic      = false;
                        self::$asgarosforum->current_post       = false;
                        self::$asgarosforum->current_view       = 'default';
                    }
                }
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
