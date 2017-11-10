<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumBreadCrumbs {
    public static $breadCrumbsLevel = 4;
    public static $breadCrumbsElements = 0;
    private static $breadCrumbsLinks = array();

    public static function addToBreadCrumbsList($link, $title, $position = false) {
        self::$breadCrumbsLinks[] = array(
            'link'      => $link,
            'title'     => $title,
            'position'  => $position
        );
    }

    public static function showBreadCrumbs() {
        global $asgarosforum;

        if ($asgarosforum->options['enable_breadcrumbs']) {
            if (self::$breadCrumbsLevel >= 4) {
                $elementLink = $asgarosforum->getLink('home');
                $elementTitle = __('Forum', 'asgaros-forum');
                self::addToBreadCrumbsList($elementLink, $elementTitle, 1);
            }

            // Define category prefix.
            $categoryPrefix = '';

            if (self::$breadCrumbsLevel >= 4 && $asgarosforum->current_category) {
                $category_name = $asgarosforum->get_category_name($asgarosforum->current_category);

                if ($category_name) {
                    $categoryPrefix = $category_name.': ';
                }
            }

            // Define forum breadcrumbs.
            if (self::$breadCrumbsLevel >= 3 && $asgarosforum->parent_forum && $asgarosforum->parent_forum > 0) {
                $elementLink = $asgarosforum->getLink('forum', $asgarosforum->parent_forum);
                $elementTitle = $categoryPrefix.esc_html(stripslashes($asgarosforum->parent_forum_name));
                self::addToBreadCrumbsList($elementLink, $elementTitle, 2);
                $categoryPrefix = '';
            }

            if (self::$breadCrumbsLevel >= 2 && $asgarosforum->current_forum) {
                $elementLink = $asgarosforum->getLink('forum', $asgarosforum->current_forum);
                $elementTitle = $categoryPrefix.esc_html(stripslashes($asgarosforum->current_forum_name));
                self::addToBreadCrumbsList($elementLink, $elementTitle, 2);
            }

            if (self::$breadCrumbsLevel >= 1 && $asgarosforum->current_topic) {
                $name = stripslashes($asgarosforum->current_topic_name);
                $elementLink = $asgarosforum->getLink('topic', $asgarosforum->current_topic);
                $elementTitle = esc_html($asgarosforum->cut_string($name));
                self::addToBreadCrumbsList($elementLink, $elementTitle, 3);
            }

            if ($asgarosforum->current_view === 'addpost') {
                $elementLink = $asgarosforum->getLink('current');
                $elementTitle = __('Post Reply', 'asgaros-forum');
                self::addToBreadCrumbsList($elementLink, $elementTitle);
            } else if ($asgarosforum->current_view === 'editpost') {
                $elementLink = $asgarosforum->getLink('current');
                $elementTitle = __('Edit Post', 'asgaros-forum');
                self::addToBreadCrumbsList($elementLink, $elementTitle);
            } else if ($asgarosforum->current_view === 'addtopic') {
                $elementLink = $asgarosforum->getLink('current');
                $elementTitle = __('New Topic', 'asgaros-forum');
                self::addToBreadCrumbsList($elementLink, $elementTitle);
            } else if ($asgarosforum->current_view === 'movetopic') {
                $elementLink = $asgarosforum->getLink('current');
                $elementTitle = __('Move Topic', 'asgaros-forum');
                self::addToBreadCrumbsList($elementLink, $elementTitle);
            } else if ($asgarosforum->current_view === 'search') {
                $elementLink = $asgarosforum->getLink('current');
                $elementTitle = __('Search', 'asgaros-forum');
                self::addToBreadCrumbsList($elementLink, $elementTitle);
            } else if ($asgarosforum->current_view === 'subscriptions') {
                $elementLink = $asgarosforum->getLink('current');
                $elementTitle = __('Subscriptions', 'asgaros-forum');
                self::addToBreadCrumbsList($elementLink, $elementTitle);
            } else if ($asgarosforum->current_view === 'profile') {
                $asgarosforum->profile->setBreadCrumbs();
            }

            // Render breadcrumbs links.
            echo '<div id="breadcrumbs-container">';
                echo '<div id="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">';
                    echo '<span class="dashicons-before dashicons-admin-home"></span>';
                    foreach (self::$breadCrumbsLinks as $element) {
                        self::renderBreadCrumbsElement($element);
                    }
                echo '</div>';
                echo '<div class="clear"></div>';
            echo '</div>';
        }
    }

    public static function renderBreadCrumbsElement($element) {
        if (self::$breadCrumbsElements > 0) {
            echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
        }

        echo '<span property="itemListElement" typeof="ListItem">';
            echo '<a property="item" typeof="WebPage" href="'.$element['link'].'" title="'.$element['title'].'">';
            echo '<span property="name">'.$element['title'].'</span>';
            echo '</a>';
            if ($element['position']) {
                echo '<meta property="position" content="'.$element['position'].'">';
            }
        echo '</span>';

        self::$breadCrumbsElements++;
    }
}
