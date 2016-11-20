<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumWidgets {
    public static function getWidgetLink($thread_id, $post_id) {
        global $asgarosforum;

        if (empty($asgarosforum->cache['getWidgetLink'][$thread_id])) {
            $asgarosforum->cache['getWidgetLink'][$thread_id] = $asgarosforum->db->get_var($asgarosforum->db->prepare("SELECT count(id) FROM {$asgarosforum->table_posts} WHERE parent_id = %d;", $thread_id));
        }

        $page = ceil($asgarosforum->cache['getWidgetLink'][$thread_id] / $asgarosforum->options['posts_per_page']);

        return $asgarosforum->getLink('topic', $thread_id, array('part' => $page), '#postid-'.$post_id);
    }

    public static function filterCategories() {
        $where = '';
        $categories_list = array();
        $categories_list = apply_filters('asgarosforum_filter_get_categories', $categories_list);

        if (!AsgarosForumPermissions::isModerator('current')) {
            $categories = get_terms('asgarosforum-category', array(
                'fields'        => 'ids',
                'hide_empty'    => false,
                'meta_query'    => array(
                    array(
                        'key'       => 'category_access',
                        'value'     => 'moderator',
                        'compare'   => 'LIKE'
                    )
                )
            ));
            $categories_list = array_merge($categories_list, $categories);
        }

        if (!is_user_logged_in()) {
            $categories = get_terms('asgarosforum-category', array(
                'fields'        => 'ids',
                'hide_empty'    => false,
                'meta_query'    => array(
                    array(
                        'key'       => 'category_access',
                        'value'     => 'loggedin',
                        'compare'   => 'LIKE'
                    )
                )
            ));
            $categories_list = array_merge($categories_list, $categories);
        }

        if (!empty($categories_list)) {
            $categories_list = implode(',', $categories_list);
            $where = 'AND f.parent_id NOT IN ('.$categories_list.')';
        }

        return $where;
    }

    public static function showWidget($args, $title, $contentType, $instance) {
        global $asgarosforum;

        $numberOfItems = (!empty($instance['number'])) ? absint($instance['number']) : 3;

        if (!$numberOfItems) {
			$numberOfItems = 3;
        }

        echo $args['before_widget'];

        if ($title) {
            echo $args['before_title'].$title.$args['after_title'];
        }

        $elements = null;
        $where = self::filterCategories();
        if ($contentType === 'posts') {
            $elements = $asgarosforum->db->get_results($asgarosforum->db->prepare("SELECT p1.id, p1.date, p1.parent_id, p1.author_id, t.name FROM {$asgarosforum->table_posts} AS p1 LEFT JOIN {$asgarosforum->table_posts} AS p2 ON (p1.parent_id = p2.parent_id AND p1.id < p2.id) LEFT JOIN {$asgarosforum->table_topics} AS t ON (t.id = p1.parent_id) LEFT JOIN {$asgarosforum->table_forums} AS f ON (f.id = t.parent_id) WHERE p2.id IS NULL {$where} ORDER BY p1.id DESC LIMIT %d;", $numberOfItems));
        } else if ($contentType === 'topics') {
            $elements = $asgarosforum->db->get_results($asgarosforum->db->prepare("SELECT p1.id, p1.date, p1.parent_id, p1.author_id, t.name FROM {$asgarosforum->table_posts} AS p1 LEFT JOIN {$asgarosforum->table_posts} AS p2 ON (p1.parent_id = p2.parent_id AND p1.id > p2.id) LEFT JOIN {$asgarosforum->table_topics} AS t ON (t.id = p1.parent_id) LEFT JOIN {$asgarosforum->table_forums} AS f ON (f.id = t.parent_id) WHERE p2.id IS NULL {$where} ORDER BY t.id DESC LIMIT %d;", $numberOfItems));
        }

        if (!empty($elements)) {
            echo '<ul class="asgarosforum-widget">';

            foreach ($elements as $element) {
                echo '<li>';
                echo '<span class="post-link"><a href="'.AsgarosForumWidgets::getWidgetLink($element->parent_id, $element->id).'" title="'.esc_html(stripslashes($element->name)).'">'.esc_html($asgarosforum->cut_string(stripslashes($element->name))).'</a></span>';
                echo '<span class="post-author">'.__('by', 'asgaros-forum').'&nbsp;<b>'.$asgarosforum->get_username($element->author_id, true).'</b></span>';
                echo '<span class="post-date">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($element->date), current_time('timestamp'))).'</span>';
                echo '</li>';
            }

            echo '</ul>';
        } else {
            if ($contentType === 'posts') {
                _e('No posts yet!', 'asgaros-forum');
            } else if ($contentType === 'topics') {
                _e('No topics yet!', 'asgaros-forum');
            }
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
		echo '<label for="'.$object->get_field_id('number').'">'.__('Number of posts to show:', 'asgaros-forum').'</label>&nbsp;';
		echo '<input class="tiny-text" id="'.$object->get_field_id('number').'" name="'.$object->get_field_name('number').'" type="number" step="1" min="1" value="'.$number.'" size="3">';
		echo '</p>';
    }

    public static function updateWidget($new_instance, $old_instance) {
        $instance = $old_instance;
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
        if (!isset($args['widget_id'])) {
			$args['widget_id'] = $this->id;
		}

		$title = (!empty($instance['title'])) ? $instance['title'] : __('Recent forum posts', 'asgaros-forum');
        $title = apply_filters('widget_title', $title, $instance, $this->id_base);

        AsgarosForumWidgets::showWidget($args, $title, 'posts', $instance);
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
        if (!isset($args['widget_id'])) {
			$args['widget_id'] = $this->id;
		}

		$title = (!empty($instance['title'])) ? $instance['title'] : __('Recent forum topics', 'asgaros-forum');
        $title = apply_filters('widget_title', $title, $instance, $this->id_base);

        AsgarosForumWidgets::showWidget($args, $title, 'topics', $instance);
    }

    public function form($instance) {
        AsgarosForumWidgets::showForm($instance, $this, __('Recent forum topics', 'asgaros-forum'));
	}

    public function update($new_instance, $old_instance) {
		return AsgarosForumWidgets::updateWidget($new_instance, $old_instance);
	}
}

function asgarosforum_widgets_init() {
    global $asgarosforum;

    if (!$asgarosforum->options['require_login'] || is_user_logged_in()) {
        register_widget('AsgarosForumRecentPosts_Widget');
        register_widget('AsgarosForumRecentTopics_Widget');
    }
}

add_action('widgets_init', 'asgarosforum_widgets_init');

?>
