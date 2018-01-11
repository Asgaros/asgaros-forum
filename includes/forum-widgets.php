<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumWidgets {
    private static $asgarosforum = null;

    public function __construct($object) {
        self::$asgarosforum = $object;

        add_action('widgets_init', array($this, 'initializeWidgets'));
    }

    public function initializeWidgets() {
        if (!self::$asgarosforum->options['require_login'] || is_user_logged_in()) {
            register_widget('AsgarosForumRecentPosts_Widget');
            register_widget('AsgarosForumRecentTopics_Widget');
            register_widget('AsgarosForumSearch_Widget');
        }
    }

    public static function setUpLocation() {
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

        return $locationSetUp;
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

        $locationSetUp = self::setUpLocation();

        if ($locationSetUp) {
            // Build query for filtering elements first.
            $categoriesIDs = array();
            $excludeList = array();
            $excludeList = apply_filters('asgarosforum_filter_get_categories', $excludeList);
            $metaQueryFilter = self::$asgarosforum->getCategoriesFilter();
            $categoriesList = get_terms('asgarosforum-category', array(
                'hide_empty'    => false,
                'exclude'       => $excludeList,
                'meta_query'    => $metaQueryFilter
            ));

            $categoriesList = AsgarosForumUserGroups::filterCategories($categoriesList);

            foreach ($categoriesList as $category) {
                $categoriesIDs[] = $category->term_id;
            }

            $where = ($categoriesIDs) ? 'AND f.parent_id IN ('.implode(',', $categoriesIDs).')' : false;

            // Select the elements.
            $elements = null;
            $numberOfItems = ($instance['number']) ? absint($instance['number']) : 3;

            // Dont show last posts/topics in widgets when user cant access any categories.
            if ($where) {
                if ($widgetType === 'posts') {
                    $elementIDs = self::$asgarosforum->db->get_col(self::$asgarosforum->db->prepare("SELECT MAX(p.id) AS id FROM ".self::$asgarosforum->tables->posts." AS p LEFT JOIN ".self::$asgarosforum->tables->topics." AS t ON (t.id = p.parent_id) WHERE EXISTS (SELECT f.id FROM ".self::$asgarosforum->tables->forums." AS f WHERE f.id = t.parent_id {$where}) GROUP BY p.parent_id ORDER BY MAX(p.id) DESC LIMIT %d;", $numberOfItems));

                    // Select data if selectable elements exist.
                    if (!empty($elementIDs)) {
                        $elements = self::$asgarosforum->db->get_results("SELECT p.id, p.date, p.parent_id, p.author_id, t.name, (SELECT COUNT(*) FROM ".self::$asgarosforum->tables->posts." WHERE parent_id = p.parent_id) AS post_counter FROM ".self::$asgarosforum->tables->posts." AS p LEFT JOIN ".self::$asgarosforum->tables->topics." AS t ON (t.id = p.parent_id) WHERE p.id IN (".implode(',', $elementIDs).") ORDER BY p.id DESC;");
                    }
                } else if ($widgetType === 'topics') {
                    $elements = self::$asgarosforum->db->get_results(self::$asgarosforum->db->prepare("SELECT p.id, p.date, p.parent_id, p.author_id, t.name, (SELECT COUNT(*) FROM ".self::$asgarosforum->tables->posts." WHERE parent_id = p.parent_id) AS post_counter FROM ".self::$asgarosforum->tables->posts." AS p LEFT JOIN ".self::$asgarosforum->tables->topics." AS t ON (t.id = p.parent_id) LEFT JOIN ".self::$asgarosforum->tables->forums." AS f ON (f.id = t.parent_id) WHERE p.id IN (SELECT MAX(p_inner.id) FROM ".self::$asgarosforum->tables->posts." AS p_inner GROUP BY p_inner.parent_id) {$where} ORDER BY t.id DESC LIMIT %d;", $numberOfItems));
                }
            }

            if ($elements) {
                $avatars_available = get_option('show_avatars');
                $show_avatar = isset($instance['show_avatar']) ? $instance['show_avatar'] : true;
                $widgetTitleLength = apply_filters('asgarosforum_filter_widget_title_length', 33);
                $widgetAvatarSize = apply_filters('asgarosforum_filter_widget_avatar_size', 30);

                echo '<div class="asgarosforum-widget">';

                foreach ($elements as $element) {
                    // Calculate the page, where the last post is calculated.
                    $pageNumber = ceil($element->post_counter / self::$asgarosforum->options['posts_per_page']);
                    echo '<div class="widget-element">';

                    // Add avatars
                    if ($avatars_available && $show_avatar) {
                        echo '<div class="widget-avatar">'.get_avatar($element->author_id, $widgetAvatarSize).'</div>';
                    }

                    echo '<div class="widget-content">';
                        $count_answers_i18n_text = '';

                        if ($widgetType === 'topics' && $element->post_counter > 1) {
                            $answers = ($element->post_counter - 1);
                            $count_answers_i18n = number_format_i18n($answers);
                            $count_answers_i18n_text = ', '.sprintf(_n('%s Answer', '%s Answers', $answers, 'asgaros-forum'), $count_answers_i18n);
                        }

                        echo '<span class="post-link"><a href="'.self::$asgarosforum->getLink('topic', $element->parent_id, array('part' => $pageNumber), '#postid-'.$element->id).'" title="'.esc_html(stripslashes($element->name)).'">'.esc_html(self::$asgarosforum->cut_string(stripslashes($element->name), $widgetTitleLength)).'</a></span>';
                        echo '<span class="post-author">'.__('by', 'asgaros-forum').'&nbsp;<b>'.self::$asgarosforum->getUsername($element->author_id).'</b></span>';
                        echo '<span class="post-date">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($element->date), current_time('timestamp'))).$count_answers_i18n_text.'</span>';
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
        $show_avatar = isset($instance['show_avatar']) ? (bool)$instance['show_avatar'] : true;

		echo '<p>';
		echo '<label for="'.$object->get_field_id('title').'">'.__('Title:', 'asgaros-forum').'</label>';
		echo '<input class="widefat" id="'.$object->get_field_id('title').'" name="'.$object->get_field_name('title').'" type="text" value="'.$title.'">';
		echo '</p>';

        echo '<p>';
		echo '<label for="'.$object->get_field_id('number').'">'.__('Number of topics to show:', 'asgaros-forum').'</label>&nbsp;';
		echo '<input class="tiny-text" id="'.$object->get_field_id('number').'" name="'.$object->get_field_name('number').'" type="number" step="1" min="1" value="'.$number.'" size="3">';
		echo '</p>';

        echo '<p>';
        echo '<input class="checkbox" type="checkbox" '.checked($show_avatar, true, false).' id="'.$object->get_field_id('show_avatar').'" name="'.$object->get_field_name('show_avatar').'" />';
		echo '<label for="'.$object->get_field_id('show_avatar').'">'.__('Show avatars?', 'asgaros-forum').'</label>';
        echo '</p>';
    }

    public static function updateWidget($new_instance, $old_instance) {
        $instance = array();
		$instance['title'] = sanitize_text_field($new_instance['title']);
		$instance['number'] = (int)$new_instance['number'];
        $instance['show_avatar'] = isset($new_instance['show_avatar']) ? (bool)$new_instance['show_avatar'] : false;
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
            echo '<form method="get" action="'.$asgarosforum->getLink('search').'">';
                echo '<input name="view" type="hidden" value="search">';

                // Workaround for broken search in posts/pages when using plain permalink structure.
                if (!get_option('permalink_structure')) {
                    echo '<input name="page_id" type="hidden" value="'.$asgarosforum->options['location'].'">';
                }

                echo '<input name="keywords" type="search" placeholder="'.__('Search ...', 'asgaros-forum').'" value="'.AsgarosForumSearch::$searchKeywords.'">';
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
