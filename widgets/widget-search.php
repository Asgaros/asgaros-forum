<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumSearch_Widget extends WP_Widget {
    public function __construct() {
        $widget_ops = array('classname' => 'asgarosforumsearch_widget', 'description' => __('A search form for Asgaros Forum.', 'asgaros-forum'));
		parent::__construct('asgarosforumsearch_widget', __('Asgaros Forum: Search', 'asgaros-forum'), $widget_ops);
    }

    public function widget($args, $instance) {
        global $asgarosforum;
        $title = null;

        if ($instance['title']) {
            $title = $instance['title'];
        } else {
            $title = __('Forum Search', 'asgaros-forum');
        }

        echo $args['before_widget'];
        echo $args['before_title'].$title.$args['after_title'];

        $locationSetUp = AsgarosForumWidgets::setUpLocation();

        if ($locationSetUp) {
            // TODO: Rewrite code so can use input-generation of search class.
            echo '<div class="asgarosforum-widget-search">';
            //echo '<span class="dashicons-before dashicons-search"></span>';
            echo '<form method="get" action="'.$asgarosforum->get_link('search').'">';
                // Workaround for broken search in posts/pages when using plain permalink structure.
                if (!$asgarosforum->rewrite->use_permalinks) {
                    echo '<input name="view" type="hidden" value="search">';
                    echo '<input name="page_id" type="hidden" value="'.$asgarosforum->options['location'].'">';
                }

                echo '<input name="keywords" type="search" placeholder="'.__('Search ...', 'asgaros-forum').'" value="'.$asgarosforum->search->search_keywords_for_output.'">';
                echo '<button type="submit" class="dashicons-before dashicons-search"></button>';
            echo '</form>';
            echo '</div>';
        } else {
            _e('The forum has not been configured correctly.', 'asgaros-forum');
        }

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : __('Forum Search', 'asgaros-forum');

		echo '<p>';
		echo '<label for="'.$this->get_field_id('title').'">'.__('Title:', 'asgaros-forum').'</label>';
		echo '<input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" type="text" value="'.$title.'">';
		echo '</p>';
	}

    public function update($new_instance, $old_instance) {
        $instance = array();
		$instance['title'] = sanitize_text_field($new_instance['title']);
		return $instance;
	}
}