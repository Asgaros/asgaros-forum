<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumRecentPosts_Widget extends WP_Widget {
    public function __construct() {
        $widget_ops = array('classname' => 'asgarosforumrecentposts_widget', 'description' => __('Shows recent posts in Asgaros Forum.', 'asgaros-forum'));
		parent::__construct('asgarosforumrecentposts_widget', __('Asgaros Forum: Recent Posts', 'asgaros-forum'), $widget_ops);
    }

    public function widget($args, $instance) {
        AsgarosForumWidgets::showWidget($args, $instance, 'posts');
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : __('Recent forum posts', 'asgaros-forum');
        $number = isset($instance['number']) ? absint($instance['number']) : 3;
        $show_avatar = isset($instance['show_avatar']) ? (bool)$instance['show_avatar'] : true;
        $show_excerpt = isset($instance['show_excerpt']) ? (bool)$instance['show_excerpt'] : false;
        $group_by_topic = isset($instance['group_by_topic']) ? (bool)$instance['group_by_topic'] : true;

		echo '<p>';
		echo '<label for="'.$this->get_field_id('title').'">'.__('Title:', 'asgaros-forum').'</label>';
		echo '<input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" type="text" value="'.$title.'">';
		echo '</p>';

        echo '<p>';
		echo '<label for="'.$this->get_field_id('number').'">'.__('Number of topics to show:', 'asgaros-forum').'</label>&nbsp;';
		echo '<input class="tiny-text" id="'.$this->get_field_id('number').'" name="'.$this->get_field_name('number').'" type="number" step="1" min="1" value="'.$number.'" size="3">';
		echo '</p>';

        echo '<p>';
        echo '<input class="checkbox" type="checkbox" '.checked($show_avatar, true, false).' id="'.$this->get_field_id('show_avatar').'" name="'.$this->get_field_name('show_avatar').'">';
		echo '<label for="'.$this->get_field_id('show_avatar').'">'.__('Show avatars', 'asgaros-forum').'</label>';
        echo '</p>';

        echo '<p>';
        echo '<input class="checkbox" type="checkbox" '.checked($show_excerpt, true, false).' id="'.$this->get_field_id('show_excerpt').'" name="'.$this->get_field_name('show_excerpt').'">';
		echo '<label for="'.$this->get_field_id('show_excerpt').'">'.__('Show excerpt', 'asgaros-forum').'</label>';
        echo '</p>';

        echo '<p>';
        echo '<input class="checkbox" type="checkbox" '.checked($group_by_topic, true, false).' id="'.$this->get_field_id('group_by_topic').'" name="'.$this->get_field_name('group_by_topic').'">';
		echo '<label for="'.$this->get_field_id('group_by_topic').'">'.__('Group posts by topic', 'asgaros-forum').'</label>';
        echo '</p>';
	}

    public function update($new_instance, $old_instance) {
        $instance = array();
		$instance['title'] = sanitize_text_field($new_instance['title']);
		$instance['number'] = (int)$new_instance['number'];
        $instance['show_avatar'] = isset($new_instance['show_avatar']) ? (bool)$new_instance['show_avatar'] : false;
        $instance['show_excerpt'] = isset($new_instance['show_excerpt']) ? (bool)$new_instance['show_excerpt'] : false;
        $instance['group_by_topic'] = isset($new_instance['group_by_topic']) ? (bool)$new_instance['group_by_topic'] : false;
		return $instance;
	}
}
