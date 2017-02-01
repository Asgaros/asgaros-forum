<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumBreadCrumbs {
    public static $breadCrumbsLevel = 4;
    public static $breadCrumbsElements = 0;

    public static function showBreadCrumbs() {
        global $asgarosforum;

        if ($asgarosforum->options['show_breadcrumbs']) {
            // Build breadcrumbs links.
            $breadCrumsLinks = array();

            if (self::$breadCrumbsLevel >= 4) {
                $breadCrumsLinks[] = '<a href="'.$asgarosforum->getLink('home').'">'.__('Forum', 'asgaros-forum').'</a>';
            }

            if (self::$breadCrumbsLevel >= 3 && $asgarosforum->parent_forum && $asgarosforum->parent_forum > 0) {
                $breadCrumsLinks[] = '<a href="'.$asgarosforum->getLink('forum', $asgarosforum->parent_forum).'">'.esc_html(stripslashes($asgarosforum->get_name($asgarosforum->parent_forum, $asgarosforum->tables->forums))).'</a>';
            }

            if (self::$breadCrumbsLevel >= 2 && $asgarosforum->current_forum) {
                $breadCrumsLinks[] = '<a href="'.$asgarosforum->getLink('forum', $asgarosforum->current_forum).'">'.esc_html(stripslashes($asgarosforum->get_name($asgarosforum->current_forum, $asgarosforum->tables->forums))).'</a>';
            }

            if (self::$breadCrumbsLevel >= 1 && $asgarosforum->current_topic) {
                $name = stripslashes($asgarosforum->get_name($asgarosforum->current_topic, $asgarosforum->tables->topics));
                $breadCrumsLinks[] = '<a href="'.$asgarosforum->getLink('topic', $asgarosforum->current_topic).'" title="'.esc_html($name).'">'.esc_html($asgarosforum->cut_string($name)).'</a>';
            }

            if ($asgarosforum->current_view === 'addpost') {
                $breadCrumsLinks[] = '<a href="#">'.__('Post Reply', 'asgaros-forum').'</a>';
            } else if ($asgarosforum->current_view === 'editpost') {
                $breadCrumsLinks[] = '<a href="#">'.__('Edit Post', 'asgaros-forum').'</a>';
            } else if ($asgarosforum->current_view === 'addtopic') {
                $breadCrumsLinks[] = '<a href="#">'.__('New Topic', 'asgaros-forum').'</a>';
            } else if ($asgarosforum->current_view === 'movetopic') {
                $breadCrumsLinks[] = '<a href="#">'.__('Move Topic', 'asgaros-forum').'</a>';
            } else if ($asgarosforum->current_view === 'search') {
                $breadCrumsLinks[] = '<a href="#">'.__('Search', 'asgaros-forum').'</a>';
            }

            // Render breadcrums links.
            echo '<div id="breadcrumbs">';
            foreach ($breadCrumsLinks as $link) {
                self::renderBreadCrumbsElement($link);
            }
            echo '</div>';
        }
    }

    public static function renderBreadCrumbsElement($link) {
        if (self::$breadCrumbsElements === 0) {
            echo '<span class="dashicons-before dashicons-admin-home"></span>';
        } else {
            echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
        }

        echo $link;

        self::$breadCrumbsElements++;
    }
}
