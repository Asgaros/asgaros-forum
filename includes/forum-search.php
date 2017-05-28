<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumSearch {
    private static $asgarosforum = null;
    static $searchKeywords = '';

    public function __construct($object) {
		self::$asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
    }

    public function initialize() {
        if (!empty($_GET['keywords'])) {
            self::$searchKeywords = esc_sql(trim($_GET['keywords']));
        }
    }

    public static function showSearchInput() {
        if (self::$asgarosforum->options['enable_search']) {
            echo '<div id="forum-search">';
            echo '<span class="dashicons-before dashicons-search"></span>';
            echo '<form method="get" action="'.self::$asgarosforum->getLink('search').'">';
            echo '<input name="view" type="hidden" value="search">';
            echo '<input name="keywords" type="search" placeholder="'.__('Search ...', 'asgaros-forum').'" value="'.self::$searchKeywords.'">';
            echo '</form>';
            echo '</div>';
        }
    }

    public static function getSearchResults() {
        if (!empty(self::$searchKeywords)) {
            $categories = self::$asgarosforum->get_categories();
            $categoriesFilter = array();

            foreach ($categories as $category) {
                $categoriesFilter[] = $category->term_id;
            }

            $where = 'AND f.parent_id IN ('.implode(',', $categoriesFilter).')';

            $start = self::$asgarosforum->current_page * self::$asgarosforum->options['topics_per_page'];
            $end = self::$asgarosforum->options['topics_per_page'];
            $limit = self::$asgarosforum->db->prepare("LIMIT %d, %d", $start, $end);

            $shortcodeSearchFilter = AsgarosForumShortcodes::$shortcodeSearchFilter;

            $query = "SELECT t.id, t.name, t.views, t.status, (SELECT author_id FROM ".self::$asgarosforum->tables->posts." WHERE parent_id = t.id ORDER BY id ASC LIMIT 1) AS author_id, (SELECT (COUNT(*) - 1) FROM ".self::$asgarosforum->tables->posts." WHERE parent_id = t.id) AS answers, MATCH (p.text) AGAINST ('".self::$searchKeywords."*' IN BOOLEAN MODE) AS score FROM ".self::$asgarosforum->tables->topics." AS t, ".self::$asgarosforum->tables->posts." AS p, ".self::$asgarosforum->tables->forums." AS f WHERE p.parent_id = t.id AND t.parent_id = f.id AND MATCH (p.text) AGAINST ('".self::$searchKeywords."*' IN BOOLEAN MODE) {$where} {$shortcodeSearchFilter} GROUP BY p.parent_id ORDER BY score DESC, p.id DESC {$limit};";

            $results = self::$asgarosforum->db->get_results($query);

            if (!empty($results)) {
                return $results;
            }
        }

        return false;
    }
}
