<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumActivity {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_action('asgarosforum_breadcrumbs_activity', array($this, 'add_breadcrumbs'));
    }

    public function functionality_enabled() {
        return $this->asgarosforum->options['enable_activity'];
    }

    public function add_breadcrumbs() {
        $element_link = $this->asgarosforum->get_link('activity');
        $element_title = __('Activity', 'asgaros-forum');
        $this->asgarosforum->breadcrumbs->add_breadcrumb($element_link, $element_title);
    }

    public function show_activity() {
        $pagination_rendering = $this->asgarosforum->pagination->renderPagination('activity');
        $paginationRendering = ($pagination_rendering) ? '<div class="pages-and-menu">'.$pagination_rendering.'<div class="clear"></div></div>' : '';
        echo $paginationRendering;

        $data = $this->load_activity_data();

        if (!empty($data)) {
            $date_today = date($this->asgarosforum->date_format);
            $date_yesterday = date($this->asgarosforum->date_format, strtotime('-1 days'));
            $last_time = false;
            $first_group = true;

            foreach ($data as $activity) {
                $current_time = date($this->asgarosforum->date_format, strtotime($activity->date));
                $human_time_diff = sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($activity->date), current_time('timestamp')));

                if ($current_time == $date_today) {
                    $current_time = __('Today', 'asgaros-forum');
                } else if ($current_time == $date_yesterday) {
                    $current_time = __('Yesterday', 'asgaros-forum');
                } else {
                    $current_time = $human_time_diff;
                }

                if ($last_time != $current_time) {
                    $last_time = $current_time;

                    if ($first_group) {
                        $first_group = false;
                    } else {
                        echo '</div>';
                    }

                    echo '<div class="title-element">'.$current_time.'</div>';
                    echo '<div class="content-element">';
                }

                $name_author = $this->asgarosforum->getUsername($activity->author_id);
                $name_topic = esc_html(stripslashes($activity->name));
                $read_status = $this->asgarosforum->unread->get_status_post($activity->id, $activity->author_id, $activity->date, $activity->parent_id);

                if ($this->asgarosforum->is_first_post($activity->id, $activity->parent_id)) {
                    $link = $this->asgarosforum->get_link('topic', $activity->parent_id);
                    $link_html = '<a href="'.$link.'">'.$name_topic.'</a>';
                    echo '<div class="activity-element dashicons-before dashicons-edit '.$read_status.'">';
                    echo sprintf(__('New topic %s created by %s.', 'asgaros-forum'), $link_html, $name_author).' <i class="activity-time">'.$human_time_diff.'</i>';
                    echo '</div>';
                } else {
                    $link = $this->asgarosforum->rewrite->get_post_link($activity->id, $activity->parent_id);
                    $link_html = '<a href="'.$link.'">'.$name_topic.'</a>';
                    echo '<div class="activity-element dashicons-before dashicons-admin-comments '.$read_status.'">';
                    echo sprintf(__('%s answered in %s.', 'asgaros-forum'), $name_author, $link_html).' <i class="activity-time">'.$human_time_diff.'</i>';
                    echo '</div>';
                }
            }

            echo '</div>';
        } else {
            echo '<div class="title-element"></div>';
            echo '<div class="content-element">';
            echo '<div class="notice">'.__('No activity yet!', 'asgaros-forum').'</div>';
            echo '</div>';
        }

        echo $paginationRendering;
    }

    public function load_activity_data($count_all = false) {
        $ids_categories = $this->asgarosforum->content->get_categories_ids();

        if (empty($ids_categories)) {
            return false;
        } else {
            $ids_categories = implode(',', $ids_categories);

            if ($count_all) {
                return $this->asgarosforum->db->get_var("SELECT COUNT(p.id) FROM {$this->asgarosforum->tables->posts} AS p, {$this->asgarosforum->tables->forums} AS f WHERE p.forum_id = f.id AND f.parent_id IN ({$ids_categories})");
            } else {
                $start = $this->asgarosforum->current_page * 50;
                $end = 50;
                return $this->asgarosforum->db->get_results("SELECT p.id, p.parent_id, p.date, p.author_id, t.name FROM {$this->asgarosforum->tables->posts} AS p LEFT JOIN {$this->asgarosforum->tables->topics} AS t ON (t.id = p.parent_id) WHERE EXISTS (SELECT f.id FROM {$this->asgarosforum->tables->forums} AS f WHERE f.id = t.parent_id AND f.parent_id IN ({$ids_categories})) ORDER BY p.id DESC LIMIT {$start}, {$end};");
            }
        }
    }

    public function show_activity_link() {
        if ($this->functionality_enabled()) {
            echo '<a class="activity-link" href="'.$this->asgarosforum->get_link('activity').'">'.__('Activity', 'asgaros-forum').'</a>';
        }
    }
}
