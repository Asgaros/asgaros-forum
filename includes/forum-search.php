<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumSearch {
    static $searchKeywords = '';

    public static function showSearchInput() {
        global $asgarosforum;

        if ($asgarosforum->options['enable_search']) {
            echo '<div id="forum-search">';
            echo '<span class="dashicons-before dashicons-search"></span>';
            echo '<form method="get" action="'.$asgarosforum->getLink('search').'">';
            echo '<input name="view" type="hidden" value="search">';
            echo '<input name="keywords" type="search" placeholder="'.__('Search ...', 'asgaros-forum').'">';
            echo '</form>';
            echo '</div>';
        }
    }
}
