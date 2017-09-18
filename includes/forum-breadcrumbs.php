<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumBreadCrumbs {
    public static $breadCrumbsLevel = 4;
    public static $breadCrumbsElements = 0;

    public static function showBreadCrumbs() {
        global $asgarosforum;

        if ($asgarosforum->options['enable_breadcrumbs']) {
            // Build breadcrumbs links.
            $breadCrumbsLinks = array();

            if (self::$breadCrumbsLevel >= 4) {
                $elementLink = $asgarosforum->getLink('home');
                $elementName = __('Forum', 'asgaros-forum');
                $elementTitle = $elementName;
                $breadCrumbsLinks[] = array('link' => $elementLink, 'title' => $elementTitle, 'name' => $elementName, 'position' => 1);
            }

            if (self::$breadCrumbsLevel >= 3 && $asgarosforum->parent_forum && $asgarosforum->parent_forum > 0) {
                $elementLink = $asgarosforum->getLink('forum', $asgarosforum->parent_forum);
                $elementName = esc_html(stripslashes($asgarosforum->parent_forum_name));
                $elementTitle = $elementName;
                $breadCrumbsLinks[] = array('link' => $elementLink, 'title' => $elementTitle, 'name' => $elementName, 'position' => 2);
            }

            if (self::$breadCrumbsLevel >= 2 && $asgarosforum->current_forum) {
                $elementLink = $asgarosforum->getLink('forum', $asgarosforum->current_forum);
                $elementName = esc_html(stripslashes($asgarosforum->current_forum_name));
                $elementTitle = $elementName;
                $breadCrumbsLinks[] = array('link' => $elementLink, 'title' => $elementTitle, 'name' => $elementName, 'position' => 2);
            }

            if (self::$breadCrumbsLevel >= 1 && $asgarosforum->current_topic) {
                $name = stripslashes($asgarosforum->current_topic_name);
                $elementLink = $asgarosforum->getLink('topic', $asgarosforum->current_topic);
                $elementName = esc_html($asgarosforum->cut_string($name));
                $elementTitle = esc_html($name);
                $breadCrumbsLinks[] = array('link' => $elementLink, 'title' => $elementTitle, 'name' => $elementName, 'position' => 3);
            }

            if ($asgarosforum->current_view === 'addpost') {
                $breadCrumbsLinks[] = array('link' => $asgarosforum->getLink('current'), 'title' => __('Post Reply', 'asgaros-forum'), 'name' => __('Post Reply', 'asgaros-forum'), 'position' => false);
            } else if ($asgarosforum->current_view === 'editpost') {
                $breadCrumbsLinks[] = array('link' => $asgarosforum->getLink('current'), 'title' => __('Edit Post', 'asgaros-forum'), 'name' => __('Edit Post', 'asgaros-forum'), 'position' => false);
            } else if ($asgarosforum->current_view === 'addtopic') {
                $breadCrumbsLinks[] = array('link' => $asgarosforum->getLink('current'), 'title' => __('New Topic', 'asgaros-forum'), 'name' => __('New Topic', 'asgaros-forum'), 'position' => false);
            } else if ($asgarosforum->current_view === 'movetopic') {
                $breadCrumbsLinks[] = array('link' => $asgarosforum->getLink('current'), 'title' => __('Move Topic', 'asgaros-forum'), 'name' => __('Move Topic', 'asgaros-forum'), 'position' => false);
            } else if ($asgarosforum->current_view === 'search') {
                $breadCrumbsLinks[] = array('link' => $asgarosforum->getLink('current'), 'title' => __('Search', 'asgaros-forum'), 'name' => __('Search', 'asgaros-forum'), 'position' => false);
            } else if ($asgarosforum->current_view === 'subscriptions') {
                $breadCrumbsLinks[] = array('link' => $asgarosforum->getLink('current'), 'title' => __('Subscriptions', 'asgaros-forum'), 'name' => __('Subscriptions', 'asgaros-forum'), 'position' => false);
            } else if ($asgarosforum->current_view === 'profile') {
                $breadCrumbsLinks = $asgarosforum->profile->setBreadCrumbs($breadCrumbsLinks);
            }

            // Render breadcrumbs links.
            echo '<div id="breadcrumbs-container">';
                echo '<div id="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">';
                foreach ($breadCrumbsLinks as $element) {
                    self::renderBreadCrumbsElement($element);
                }
                echo '</div>';
                echo '<div class="clear"></div>';
            echo '</div>';
        }
    }

    public static function renderBreadCrumbsElement($element) {
        if (self::$breadCrumbsElements === 0) {
            echo '<span class="dashicons-before dashicons-admin-home"></span>';
        } else {
            echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
        }

        echo '<span property="itemListElement" typeof="ListItem">';
            echo '<a property="item" typeof="WebPage" href="'.$element['link'].'" title="'.$element['title'].'">';
            echo '<span property="name">'.$element['name'].'</span>';
            echo '</a>';
            if ($element['position']) {
                echo '<meta property="position" content="'.$element['position'].'">';
            }
        echo '</span>';

        self::$breadCrumbsElements++;
    }
}
