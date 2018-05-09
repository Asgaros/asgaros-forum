<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumBreadCrumbs {
    private $asgarosforum = null;
    public $breadcrumbs_level = 4;
    public $breadcrumbs_links = array();

    public function __construct($object) {
        $this->asgarosforum = $object;
    }

    public function add_breadcrumb($link, $title, $position = false) {
        $this->breadcrumbs_links[] = array(
            'link'      => $link,
            'title'     => $title,
            'position'  => $position
        );
    }

    public function show_breadcrumbs() {
        if ($this->asgarosforum->options['enable_breadcrumbs'] && empty($this->asgarosforum->error)) {
            if ($this->breadcrumbs_level >= 4) {
                $element_link = $this->asgarosforum->get_link('home');
                $element_title = __('Forum', 'asgaros-forum');
                $this->add_breadcrumb($element_link, $element_title, 1);
            }

            // Define category prefix.
            $category_prefix = '';

            if ($this->asgarosforum->options['breadcrumbs_show_category']) {
                if ($this->breadcrumbs_level >= 4 && $this->asgarosforum->current_category) {
                    $category_name = $this->asgarosforum->get_category_name($this->asgarosforum->current_category);

                    if ($category_name) {
                        $category_prefix = $category_name.': ';
                    }
                }
            }

            // Define forum breadcrumbs.
            if ($this->breadcrumbs_level >= 3 && $this->asgarosforum->parent_forum && $this->asgarosforum->parent_forum > 0) {
                $element_link = $this->asgarosforum->get_link('forum', $this->asgarosforum->parent_forum);
                $element_title = $category_prefix.esc_html(stripslashes($this->asgarosforum->parent_forum_name));
                $this->add_breadcrumb($element_link, $element_title, 2);
                $category_prefix = '';
            }

            if ($this->breadcrumbs_level >= 2 && $this->asgarosforum->current_forum) {
                $element_link = $this->asgarosforum->get_link('forum', $this->asgarosforum->current_forum);
                $element_title = $category_prefix.esc_html(stripslashes($this->asgarosforum->current_forum_name));
                $this->add_breadcrumb($element_link, $element_title, 2);
            }

            if ($this->breadcrumbs_level >= 1 && $this->asgarosforum->current_topic) {
                $name = stripslashes($this->asgarosforum->current_topic_name);
                $element_link = $this->asgarosforum->get_link('topic', $this->asgarosforum->current_topic);
                $element_title = esc_html($this->asgarosforum->cut_string($name));
                $this->add_breadcrumb($element_link, $element_title, 3);
            }

            if ($this->asgarosforum->current_view === 'addpost') {
                $element_link = $this->asgarosforum->get_link('current');
                $element_title = __('Post Reply', 'asgaros-forum');
                $this->add_breadcrumb($element_link, $element_title);
            } else if ($this->asgarosforum->current_view === 'editpost') {
                $element_link = $this->asgarosforum->get_link('current');
                $element_title = __('Edit Post', 'asgaros-forum');
                $this->add_breadcrumb($element_link, $element_title);
            } else if ($this->asgarosforum->current_view === 'addtopic') {
                $element_link = $this->asgarosforum->get_link('current');
                $element_title = __('New Topic', 'asgaros-forum');
                $this->add_breadcrumb($element_link, $element_title);
            } else if ($this->asgarosforum->current_view === 'movetopic') {
                $element_link = $this->asgarosforum->get_link('current');
                $element_title = __('Move Topic', 'asgaros-forum');
                $this->add_breadcrumb($element_link, $element_title);
            } else if ($this->asgarosforum->current_view === 'search') {
                $element_link = $this->asgarosforum->get_link('current');
                $element_title = __('Search', 'asgaros-forum');
                $this->add_breadcrumb($element_link, $element_title);
            } else if ($this->asgarosforum->current_view === 'subscriptions') {
                $element_link = $this->asgarosforum->get_link('current');
                $element_title = __('Subscriptions', 'asgaros-forum');
                $this->add_breadcrumb($element_link, $element_title);
            } else if ($this->asgarosforum->current_view === 'profile') {
                $this->asgarosforum->profile->setBreadCrumbs();
            } else if ($this->asgarosforum->current_view === 'members') {
                $element_link = $this->asgarosforum->get_link('current');
                $element_title = __('Members', 'asgaros-forum');
                $this->add_breadcrumb($element_link, $element_title);
            } else if ($this->asgarosforum->current_view === 'activity') {
                $element_link = $this->asgarosforum->get_link('current');
                $element_title = __('Activity', 'asgaros-forum');
                $this->add_breadcrumb($element_link, $element_title);
            }

            // Render breadcrumbs links.
            echo '<div id="forum-breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">';
                echo '<span class="dashicons-before dashicons-admin-home"></span>';
                foreach ($this->breadcrumbs_links as $element) {
                    $this->render_breadcrumb($element);
                }
            echo '</div>';
        }
    }

    public function render_breadcrumb($element) {
        echo '<span property="itemListElement" typeof="ListItem">';
            echo '<a property="item" typeof="WebPage" href="'.$element['link'].'" title="'.$element['title'].'">';
            echo '<span property="name">'.$element['title'].'</span>';
            echo '</a>';
            if ($element['position']) {
                echo '<meta property="position" content="'.$element['position'].'">';
            }
        echo '</span>';

        echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
    }
}
