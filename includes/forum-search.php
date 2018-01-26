<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumSearch {
    private $asgarosforum = null;
    public $search_keywords_for_query = '';
    public $search_keywords_for_output = '';
    public $search_keywords_for_url = '';

    public function __construct($object) {
		$this->asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
    }

    public function initialize() {
        if (!empty($_GET['keywords'])) {
            $keywords = trim($_GET['keywords']);
            $this->search_keywords_for_query = esc_sql($keywords);
            $this->search_keywords_for_output = stripslashes(esc_html($keywords));
            $this->search_keywords_for_url = urlencode(stripslashes($keywords));
        }
    }

    public function show_search_input() {
        if ($this->asgarosforum->options['enable_search']) {
            echo '<div id="forum-search" class="dashicons-before dashicons-search">';
            echo '<form method="get" action="'.$this->asgarosforum->getLink('search').'">';
            echo '<input name="view" type="hidden" value="search">';

            // Workaround for broken search in posts when using plain permalink structure.
            if (!empty($_GET['p'])) {
                $value = esc_html(trim($_GET['p']));
                echo '<input name="p" type="hidden" value="'.$value.'">';
            }

            // Workaround for broken search in pages when using plain permalink structure.
            if (!empty($_GET['page_id'])) {
                $value = esc_html(trim($_GET['page_id']));
                echo '<input name="page_id" type="hidden" value="'.$value.'">';
            }

            echo '<input name="keywords" type="search" placeholder="'.__('Search ...', 'asgaros-forum').'" value="'.$this->search_keywords_for_output.'">';
            echo '</form>';
            echo '</div>';
        }
    }

    public function get_search_results() {
        if (!empty($this->search_keywords_for_query)) {
            $categories = AsgarosForumContent::get_categories();
            $categoriesFilter = array();

            foreach ($categories as $category) {
                $categoriesFilter[] = $category->term_id;
            }

            $where = 'AND f.parent_id IN ('.implode(',', $categoriesFilter).')';

            $start = $this->asgarosforum->current_page * $this->asgarosforum->options['topics_per_page'];
            $end = $this->asgarosforum->options['topics_per_page'];
            $limit = $this->asgarosforum->db->prepare("LIMIT %d, %d", $start, $end);

            $shortcodeSearchFilter = AsgarosForumShortcodes::$shortcodeSearchFilter;

            $query_author = "SELECT author_id FROM {$this->asgarosforum->tables->posts} WHERE parent_id = t.id ORDER BY id ASC LIMIT 1";
            $query_answers = "SELECT (COUNT(*) - 1) FROM {$this->asgarosforum->tables->posts} WHERE parent_id = t.id";
            $query_match_name = "MATCH (t.name) AGAINST ('{$this->search_keywords_for_query}*' IN BOOLEAN MODE)";
            $query_match_text = "MATCH (p.text) AGAINST ('{$this->search_keywords_for_query}*' IN BOOLEAN MODE)";

            $query = "SELECT t.*, ({$query_author}) AS author_id, ({$query_answers}) AS answers, {$query_match_name} AS score_name, {$query_match_text} AS score_text FROM {$this->asgarosforum->tables->topics} AS t, {$this->asgarosforum->tables->posts} AS p, {$this->asgarosforum->tables->forums} AS f WHERE p.parent_id = t.id AND t.parent_id = f.id AND ({$query_match_text} OR {$query_match_name}) {$where} {$shortcodeSearchFilter} GROUP BY p.parent_id ORDER BY (score_name + score_text) DESC, p.id DESC {$limit};";

            $results = $this->asgarosforum->db->get_results($query);

            if (!empty($results)) {
                return $results;
            }
        }

        return false;
    }
}
