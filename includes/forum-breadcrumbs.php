<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumBreadCrumbs {
    public static function showBreadCrumbs() {
        global $asgarosforum;

        if ($asgarosforum->options['show_breadcrumbs']) {
            echo '<div id="breadcrumbs">';
            echo '<span class="dashicons-before dashicons-admin-home"></span>';
            echo '<a href="'.$asgarosforum->getLink('home').'">'.__('Forum', 'asgaros-forum').'</a>';

            if ($asgarosforum->parent_forum && $asgarosforum->parent_forum > 0) {
                echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
                echo '<a href="'.$asgarosforum->getLink('forum', $asgarosforum->parent_forum).'">'.esc_html(stripslashes($asgarosforum->get_name($asgarosforum->parent_forum, $asgarosforum->tables->forums))).'</a>';
            }

            if ($asgarosforum->current_forum) {
                echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
                echo '<a href="'.$asgarosforum->getLink('forum', $asgarosforum->current_forum).'">'.esc_html(stripslashes($asgarosforum->get_name($asgarosforum->current_forum, $asgarosforum->tables->forums))).'</a>';
            }

            if ($asgarosforum->current_topic) {
                $name = stripslashes($asgarosforum->get_name($asgarosforum->current_topic, $asgarosforum->tables->topics));
                echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
                echo '<a href="'.$asgarosforum->getLink('topic', $asgarosforum->current_topic).'" title="'.esc_html($name).'">'.esc_html($asgarosforum->cut_string($name)).'</a>';
            }

            if ($asgarosforum->current_view === 'addpost') {
                echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
                echo '<a href="#">'.__('Post Reply', 'asgaros-forum').'</a>';
            } else if ($asgarosforum->current_view === 'editpost') {
                echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
                echo '<a href="#">'.__('Edit Post', 'asgaros-forum').'</a>';
            } else if ($asgarosforum->current_view === 'addtopic') {
                echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
                echo '<a href="#">'.__('New Topic', 'asgaros-forum').'</a>';
            } else if ($asgarosforum->current_view === 'search') {
                echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
                echo '<a href="#">'.__('Search', 'asgaros-forum').'</a>';
            }
            echo '</div>';
        }
    }
}
