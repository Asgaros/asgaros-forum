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

    public static function getSearchResults() {
        global $asgarosforum;

        if (!empty($_GET['keywords'])) {
            self::$searchKeywords = esc_sql(trim($_GET['keywords']));

            if (!empty(self::$searchKeywords)) {
                $categories = $asgarosforum->get_categories();
                $categoriesFilter = array();

                foreach ($categories as $category) {
                    $categoriesFilter[] = $category->term_id;
                }

                $where = 'AND f.parent_id IN ('.implode(',', $categoriesFilter).')';

                $start = $asgarosforum->current_page * $asgarosforum->options['topics_per_page'];
                $end = $asgarosforum->options['topics_per_page'];
                $limit = $asgarosforum->db->prepare("LIMIT %d, %d", $start, $end);

                $shortcodeSearchFilter = AsgarosForumShortcodes::$shortcodeSearchFilter;

                $query = "SELECT t.id, t.name, t.views, t.status, (SELECT author_id FROM {$asgarosforum->tables->posts} WHERE parent_id = t.id ORDER BY id ASC LIMIT 1) AS author_id, (SELECT (COUNT(id) - 1) FROM {$asgarosforum->tables->posts} WHERE parent_id = t.id) AS answers, MATCH (p.text) AGAINST ('".self::$searchKeywords."*' IN BOOLEAN MODE) AS score FROM {$asgarosforum->tables->topics} AS t, {$asgarosforum->tables->posts} AS p, {$asgarosforum->tables->forums} AS f WHERE p.parent_id = t.id AND t.parent_id = f.id AND MATCH (p.text) AGAINST ('".self::$searchKeywords."*' IN BOOLEAN MODE) {$where} {$shortcodeSearchFilter} GROUP BY p.parent_id ORDER BY score DESC, p.id DESC {$limit};";

                $results = $asgarosforum->db->get_results($query);

                if (!empty($results)) {
                    return $results;
                }
            }
        }

        return false;
    }
}
