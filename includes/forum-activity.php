<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumActivity {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;
    }

    public function functionality_enabled() {
        return $this->asgarosforum->options['enable_activity'];
    }

    public function show_activity() {
        $pagination_rendering = $this->asgarosforum->pagination->renderPagination('activity');
        $paginationRendering = ($pagination_rendering) ? '<div class="pages-and-menu">'.$pagination_rendering.'<div class="clear"></div></div>' : '';
        echo $paginationRendering;

        echo '<div class="title-element"></div>';
        echo '<div class="content-element">';
            $data = $this->load_activity_data();

            if (!empty($data)) {
                foreach ($data as $activity) {
                    $name_author = $this->asgarosforum->getUsername($activity->author_id);
                    $name_topic = esc_html(stripslashes($activity->name));
                    $time = sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($activity->date), current_time('timestamp')));

                    if ($this->asgarosforum->is_first_post($activity->id, $activity->parent_id)) {
                        $link = $this->asgarosforum->getLink('topic', $activity->parent_id);
                        $link_html = '<a href="'.$link.'">'.$name_topic.'</a>';
                        echo '<div class="activity-element dashicons-before dashicons-edit">';
                        echo sprintf(__('New topic %s created by %s.', 'asgaros-forum'), $link_html, $name_author).' <i class="activity-time">'.$time.'</i>';
                        echo '</div>';
                    } else {
                        $link = AsgarosForumRewrite::get_post_link($activity->id, $activity->parent_id);
                        $link_html = '<a href="'.$link.'">'.$name_topic.'</a>';
                        echo '<div class="activity-element dashicons-before dashicons-admin-comments">';
                        echo sprintf(__('%s answered in %s.', 'asgaros-forum'), $name_author, $link_html).' <i class="activity-time">'.$time.'</i>';
                        echo '</div>';
                    }
                }
            } else {
                echo '<div class="notice">'.__('No activity yet!', 'asgaros-forum').'</div>';
            }
        echo '</div>';

        echo $paginationRendering;
    }

    public function load_activity_data($count_all = false) {
        // Prepare lists and filters.
        $ids_categories = array();
        $ids_categories_excluded = array();
        $ids_categories_excluded = apply_filters('asgarosforum_filter_get_categories', $ids_categories_excluded);
        $meta_query_filter = $this->asgarosforum->content->get_categories_filter();

        // Get accessible categories first.
        $categories_list = get_terms('asgarosforum-category', array(
            'hide_empty'    => false,
            'exclude'       => $ids_categories_excluded,
            'meta_query'    => $meta_query_filter
        ));

        // Now filter them based on usergroups.
        $categories_list = AsgarosForumUserGroups::filterCategories($categories_list);

        foreach ($categories_list as $category) {
            $ids_categories[] = $category->term_id;
        }

        if (empty($ids_categories)) {
            return false;
        } else {
            if ($count_all) {
                return $this->asgarosforum->db->get_var("SELECT COUNT(p.id) FROM {$this->asgarosforum->tables->posts} AS p LEFT JOIN {$this->asgarosforum->tables->topics} AS t ON (t.id = p.parent_id) WHERE EXISTS (SELECT f.id FROM {$this->asgarosforum->tables->forums} AS f WHERE f.id = t.parent_id AND f.parent_id IN (".implode(',', $ids_categories)."));");
            } else {
                $start = $this->asgarosforum->current_page * 50;
                $end = 50;
                return $this->asgarosforum->db->get_results("SELECT p.*, t.name FROM {$this->asgarosforum->tables->posts} AS p LEFT JOIN {$this->asgarosforum->tables->topics} AS t ON (t.id = p.parent_id) WHERE EXISTS (SELECT f.id FROM {$this->asgarosforum->tables->forums} AS f WHERE f.id = t.parent_id AND f.parent_id IN (".implode(',', $ids_categories).")) ORDER BY p.id DESC LIMIT {$start}, {$end};");
            }
        }
    }

    public function show_activity_link() {
        if ($this->functionality_enabled()) {
            echo '<a href="'.$this->asgarosforum->getLink('activity').'">'.__('Activity', 'asgaros-forum').'</a>';
        }
    }
}