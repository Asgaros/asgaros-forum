<?php

class AsgarosForumRecentPosts_Widget extends WP_Widget {
    public function __construct() {
        $widget_ops = array('classname' => 'asgarosforumrecentposts_widget', 'description' => __('Shows recent posts in Asgaros Forum.', 'asgaros-forum'));
		parent::__construct('asgarosforumrecentposts_widget', __('Asgaros Forum: Recent Posts', 'asgaros-forum'), $widget_ops);
    }

    public function widget($args, $instance) {
        global $asgarosforum;

        if (!isset($args['widget_id'])) {
			$args['widget_id'] = $this->id;
		}

		$title = (!empty($instance['title'])) ? $instance['title'] : __('Recent forum posts', 'asgaros-forum');
        $title = apply_filters('widget_title', $title, $instance, $this->id_base);

		$number = (!empty($instance['number'])) ? absint($instance['number']) : 5;

        if (!$number) {
			$number = 5;
        }

		$posts = $asgarosforum->get_last_posts($number);

		if (!empty($posts)) {
            echo $args['before_widget'];

            if ($title) {
                echo $args['before_title'].$title.$args['after_title'];
            }

            echo '<ul>';
            foreach ($posts as $post) {
                echo '<li>';
                echo '<a href="'.$asgarosforum->get_postlink($post->parent_id, $post->id).'">'.$post->name.'</a>';
				echo '<span class="post-date">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($post->date), current_time('timestamp'))).'</span>';
			    echo '</li>';
            }
            echo '</ul>';
            echo $args['after_widget'];
        }
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : __('Recent forum posts', 'asgaros-forum');
        $number = isset($instance['number']) ? absint($instance['number']) : 5;

		echo '<p>';
		echo '<label for="'.$this->get_field_id('title').'">'.__('Title:', 'asgaros-forum').'</label>';
		echo '<input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" type="text" value="'.$title.'">';
		echo '</p>';

        echo '<p>';
		echo '<label for="'.$this->get_field_id('number').'">'.__('Number of topics to show:', 'asgaros-forum').'</label>&nbsp;';
		echo '<input class="tiny-text" id="'.$this->get_field_id('number').'" name="'.$this->get_field_name('number').'" type="number" step="1" min="1" value="'.$number.'" size="3">';
		echo '</p>';
	}

    public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field($new_instance['title']);
		$instance['number'] = (int)$new_instance['number'];
		return $instance;
	}
}

add_action('widgets_init', function() {
    register_widget('AsgarosForumRecentPosts_Widget');
});

?>
