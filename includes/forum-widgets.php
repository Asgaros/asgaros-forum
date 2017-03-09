<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumWidgets {
    private static $asgarosforum = null;

    public function __construct($object) {
        self::$asgarosforum = $object;

        if (!self::$asgarosforum->options['require_login'] || is_user_logged_in()) {
            register_widget('AsgarosForumRecentPosts_Widget');
            register_widget('AsgarosForumRecentTopics_Widget');
        }
    }

    public static function showWidget($args, $instance, $widgetType) {
        $title = null;
        if ($instance['title']) {
            $title = $instance['title'];
        } else {
            if ($widgetType === 'posts') {
                $title = __('Recent forum posts', 'asgaros-forum');
            } else if ($widgetType === 'topics') {
                $title = __('Recent forum topics', 'asgaros-forum');
            }
        }

        echo $args['before_widget'];
        echo $args['before_title'].$title.$args['after_title'];

        $locationSetUp = AsgarosForumShortcodes::checkForShortcode();

        // Try to get the forum-location when it is not set correctly.
        if (!$locationSetUp) {
            $pageID = self::$asgarosforum->db->get_var('SELECT ID FROM '.self::$asgarosforum->db->prefix.'posts WHERE post_type = "page" AND (post_content LIKE "%[forum]%" OR post_content LIKE "%[Forum]%");');
            if ($pageID) {
                self::$asgarosforum->options['location'] = $pageID;
                AsgarosForumRewrite::setLinks();
                $locationSetUp = true;
            }
        }

        if ($locationSetUp) {
            // Build query for filtering elements first.
            $excludeList = apply_filters('asgarosforum_filter_get_categories', array());
            $metaQueryFilter = self::$asgarosforum->getCategoriesFilter();
            $categoriesList = get_terms('asgarosforum-category', array(
                'fields'        => 'ids',
                'hide_empty'    => false,
                'exclude'       => $excludeList,
                'meta_query'    => $metaQueryFilter
            ));

            $where = ($categoriesList) ? 'AND f.parent_id IN ('.implode(',', $categoriesList).')' : '';

            // Select the elements.
            $elements = null;
            $numberOfItems = ($instance['number']) ? absint($instance['number']) : 3;

            if ($widgetType === 'posts') {
                $elements = self::$asgarosforum->db->get_results(self::$asgarosforum->db->prepare("SELECT p1.id, p1.date, p1.parent_id, p1.author_id, t.name, (SELECT COUNT(id) FROM ".self::$asgarosforum->tables->posts." WHERE parent_id = p1.parent_id) AS post_counter FROM ".self::$asgarosforum->tables->posts." AS p1 LEFT JOIN ".self::$asgarosforum->tables->posts." AS p2 ON (p1.parent_id = p2.parent_id AND p1.id < p2.id) LEFT JOIN ".self::$asgarosforum->tables->topics." AS t ON (t.id = p1.parent_id) LEFT JOIN ".self::$asgarosforum->tables->forums." AS f ON (f.id = t.parent_id) WHERE p2.id IS NULL {$where} ORDER BY p1.id DESC LIMIT %d;", $numberOfItems));
            } else if ($widgetType === 'topics') {
                $elements = self::$asgarosforum->db->get_results(self::$asgarosforum->db->prepare("SELECT p1.id, p1.date, p1.parent_id, p1.author_id, t.name, (SELECT COUNT(id) FROM ".self::$asgarosforum->tables->posts." WHERE parent_id = p1.parent_id) AS post_counter FROM ".self::$asgarosforum->tables->posts." AS p1 LEFT JOIN ".self::$asgarosforum->tables->posts." AS p2 ON (p1.parent_id = p2.parent_id AND p1.id > p2.id) LEFT JOIN ".self::$asgarosforum->tables->topics." AS t ON (t.id = p1.parent_id) LEFT JOIN ".self::$asgarosforum->tables->forums." AS f ON (f.id = t.parent_id) WHERE p2.id IS NULL {$where} ORDER BY t.id DESC LIMIT %d;", $numberOfItems));
            }

            if ($elements) {
                $avatars_available = get_option('show_avatars');
                $widgetTitleLength = apply_filters('asgarosforum_filter_widget_title_length', 33);

                echo '<div class="asgarosforum-widget">';

                foreach ($elements as $element) {
                    // Calculate the page, where the last post is calculated.
                    $pageNumber = ceil($element->post_counter / self::$asgarosforum->options['posts_per_page']);
                    echo '<div class="widget-element">';
                    // Add avatars
                    if ($avatars_available) {
                        echo '<div class="widget-avatar">'.get_avatar($element->author_id, 30).'</div>';
                    }
                    echo '<div class="widget-content">';
                        echo '<span class="post-link"><a href="'.self::$asgarosforum->getLink('topic', $element->parent_id, array('part' => $pageNumber), '#postid-'.$element->id).'" title="'.esc_html(stripslashes($element->name)).'">'.esc_html(self::$asgarosforum->cut_string(stripslashes($element->name), $widgetTitleLength)).'</a></span>';
                        echo '<span class="post-author">'.__('by', 'asgaros-forum').'&nbsp;<b>'.self::$asgarosforum->getUsername($element->author_id).'</b></span>';
                        echo '<span class="post-date">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($element->date), current_time('timestamp'))).'</span>';
                    echo '</div>';
                    echo '</div>';
                }

                echo '</div>';
            } else {
                _e('No topics yet!', 'asgaros-forum');
            }
        } else {
            _e('The forum has not been configured correctly.', 'asgaros-forum');
        }

        echo $args['after_widget'];
    }

    public static function showForm($instance, $object, $defaultTitle) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : $defaultTitle;
        $number = isset($instance['number']) ? absint($instance['number']) : 3;

		echo '<p>';
		echo '<label for="'.$object->get_field_id('title').'">'.__('Title:', 'asgaros-forum').'</label>';
		echo '<input class="widefat" id="'.$object->get_field_id('title').'" name="'.$object->get_field_name('title').'" type="text" value="'.$title.'">';
		echo '</p>';

        echo '<p>';
		echo '<label for="'.$object->get_field_id('number').'">'.__('Number of topics to show:', 'asgaros-forum').'</label>&nbsp;';
		echo '<input class="tiny-text" id="'.$object->get_field_id('number').'" name="'.$object->get_field_name('number').'" type="number" step="1" min="1" value="'.$number.'" size="3">';
		echo '</p>';
    }

    public static function updateWidget($new_instance, $old_instance) {
        $instance = array();
		$instance['title'] = sanitize_text_field($new_instance['title']);
		$instance['number'] = (int)$new_instance['number'];
		return $instance;
    }
}

class AsgarosForumRecentPosts_Widget extends WP_Widget {
    public function __construct() {
        $widget_ops = array('classname' => 'asgarosforumrecentposts_widget', 'description' => __('Shows recent posts in Asgaros Forum.', 'asgaros-forum'));
		parent::__construct('asgarosforumrecentposts_widget', __('Asgaros Forum: Recent Posts', 'asgaros-forum'), $widget_ops);
    }

    public function widget($args, $instance) {
        AsgarosForumWidgets::showWidget($args, $instance, 'posts');
    }

    public function form($instance) {
        AsgarosForumWidgets::showForm($instance, $this, __('Recent forum posts', 'asgaros-forum'));
	}

    public function update($new_instance, $old_instance) {
		return AsgarosForumWidgets::updateWidget($new_instance, $old_instance);
	}
}

class AsgarosForumRecentTopics_Widget extends WP_Widget {
    public function __construct() {
        $widget_ops = array('classname' => 'asgarosforumrecenttopics_widget', 'description' => __('Shows recent topics in Asgaros Forum.', 'asgaros-forum'));
		parent::__construct('asgarosforumrecenttopics_widget', __('Asgaros Forum: Recent Topics', 'asgaros-forum'), $widget_ops);
    }

    public function widget($args, $instance) {
        AsgarosForumWidgets::showWidget($args, $instance, 'topics');
    }

    public function form($instance) {
        AsgarosForumWidgets::showForm($instance, $this, __('Recent forum topics', 'asgaros-forum'));
	}

    public function update($new_instance, $old_instance) {
		return AsgarosForumWidgets::updateWidget($new_instance, $old_instance);
	}
}
