<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumShortcodes {
    private $asgarosforum = null;
    private $postObject = null;
    public $shortcodeSearchFilter = '';
    public $includeCategories = false;

    public function __construct($object) {
		$this->asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
    }

    public function initialize() {
        // Register multiple shortcodes because sometimes users ignore the fact that shortcodes are case-sensitive.
        add_shortcode('forum', array($this->asgarosforum, 'forum'));
        add_shortcode('Forum', array($this->asgarosforum, 'forum'));
    }

    public function checkForShortcode($object = false) {
        $this->postObject = $object;

        // If no post-object is set, use the location.
        if (!$this->postObject && $this->asgarosforum->options['location']) {
            $this->postObject = get_post($this->asgarosforum->options['location']);
        }

        if ($this->postObject && (has_shortcode($this->postObject->post_content, 'forum') || has_shortcode($this->postObject->post_content, 'Forum'))) {
            return true;
        } else {
            return false;
        }
    }

    public function handleAttributes() {
        $atts = array();
        $pattern = get_shortcode_regex();

        if (preg_match_all('/'.$pattern.'/s', $this->postObject->post_content, $matches) && array_key_exists(2, $matches) && (in_array('forum', $matches[2]) || in_array('Forum', $matches[2]))) {
            $atts = shortcode_parse_atts($matches[3][0]);

            if (!empty($atts)) {
                // Normalize attribute keys.
                $atts = array_change_key_case((array)$atts, CASE_LOWER);

                if (!empty($atts['post']) && ctype_digit($atts['post'])) {
                    $postID = $atts['post'];
                    $this->asgarosforum->current_view = 'post';
                    $this->asgarosforum->setParents($postID, 'post');
                } else if (!empty($atts['topic']) && ctype_digit($atts['topic'])) {
                    $topicID = $atts['topic'];
                    $allowedViews = array('movetopic', 'addpost', 'editpost', 'topic', 'profile');

                    // Ensure that we are in the correct element.
                    if ($this->asgarosforum->current_topic != $topicID) {
                        $this->asgarosforum->setParents($topicID, 'topic');
                        $this->asgarosforum->current_view = 'topic';
                    } else if (!in_array($this->asgarosforum->current_view, $allowedViews)) {
                        // Ensure that we are in an allowed view.
                        $this->asgarosforum->current_view = 'topic';
                    }

                    // Configure components.
                    $this->asgarosforum->options['enable_search'] = false;
                    $this->asgarosforum->breadcrumbs->breadcrumbs_level = 1;
                } else if (!empty($atts['forum']) && ctype_digit($atts['forum'])) {
                    $forumID = $atts['forum'];
                    $allowedViews = array('forum', 'addtopic', 'movetopic', 'addpost', 'editpost', 'topic', 'search', 'subscriptions', 'profile', 'members');

                    // Ensure that we are in the correct element.
                    if ($this->asgarosforum->current_forum != $forumID && $this->asgarosforum->parent_forum != $forumID && $this->asgarosforum->current_view != 'search' && $this->asgarosforum->current_view != 'subscriptions' && $this->asgarosforum->current_view != 'profile' && $this->asgarosforum->current_view != 'members') {
                        $this->asgarosforum->setParents($forumID, 'forum');
                        $this->asgarosforum->current_view = 'forum';
                    } else if (!in_array($this->asgarosforum->current_view, $allowedViews)) {
                        // Ensure that we are in an allowed view.
                        $this->asgarosforum->current_view = 'forum';
                    }

                    // Configure components.
                    $this->asgarosforum->breadcrumbs->breadcrumbs_level = ($this->asgarosforum->parent_forum != $forumID) ? 2 : 3;
                    $this->shortcodeSearchFilter = 'AND (f.id = '.$forumID.' OR f.parent_forum = '.$forumID.')';
                } else if (!empty($atts['category'])) {
                    $this->includeCategories = explode(',', $atts['category']);

                    // Ensure that we are in the correct element.
                    if (!in_array($this->asgarosforum->current_category, $this->includeCategories) && $this->asgarosforum->current_view != 'search' && $this->asgarosforum->current_view != 'subscriptions' && $this->asgarosforum->current_view != 'profile' && $this->asgarosforum->current_view != 'members' && $this->asgarosforum->current_view != 'markallread') {
                        $this->asgarosforum->current_category   = false;
                        $this->asgarosforum->parent_forum       = false;
                        $this->asgarosforum->current_forum      = false;
                        $this->asgarosforum->current_topic      = false;
                        $this->asgarosforum->current_post       = false;
                        $this->asgarosforum->current_view       = 'overview';
                    }
                }
            }
        }
    }

    // Prevent the execution of specific shortcodes inside of posts.
    function filterShortcodes($tags_to_remove, $content) {
        $tags_to_remove = array();
        $tags_to_remove[] = 'forum';
        $tags_to_remove[] = 'Forum';
        $tags_to_remove = apply_filters('asgarosforum_filter_post_shortcodes', $tags_to_remove);
        return $tags_to_remove;
    }
}
