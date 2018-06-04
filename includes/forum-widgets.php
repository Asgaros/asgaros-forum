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
        $locationSetUp = self::$asgarosforum->shortcode->checkForShortcode();

        // Try to get the forum-location when it is not set correctly.
        if (!$locationSetUp) {
            $pageID = self::$asgarosforum->db->get_var('SELECT ID FROM '.self::$asgarosforum->db->prefix.'posts WHERE post_type = "page" AND (post_content LIKE "%[forum]%" OR post_content LIKE "%[Forum]%");');
            if ($pageID) {
                self::$asgarosforum->options['location'] = $pageID;
                self::$asgarosforum->rewrite->set_links();
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
            $metaQueryFilter = self::$asgarosforum->content->get_categories_filter();
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

                        echo '<span class="post-link"><a href="'.self::$asgarosforum->get_link('topic', $element->parent_id, array('part' => $pageNumber), '#postid-'.$element->id).'" title="'.esc_html(stripslashes($element->name)).'">'.esc_html(self::$asgarosforum->cut_string(stripslashes($element->name), $widgetTitleLength)).'</a></span>';
                        echo '<span class="post-author">'.__('by', 'asgaros-forum').'&nbsp;<b>'.self::$asgarosforum->getUsername($element->author_id).'</b></span>';
                        echo '<span class="post-date">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($element->date), current_time('timestamp'))).$count_answers_i18n_text.'</span>';

                        do_action('asgarosforum_widget_recent_'.$widgetType.'_custom_content', $element->id);
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
}
