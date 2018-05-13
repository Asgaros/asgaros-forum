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
            echo '<form method="get" action="'.$this->asgarosforum->get_link('search').'">';

            // Workaround for broken search when using plain permalink structure.
            if (!$this->asgarosforum->rewrite->use_permalinks) {
                echo '<input name="view" type="hidden" value="search">';
            }

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
            $categories = $this->asgarosforum->content->get_categories();
            $categoriesFilter = array();

            foreach ($categories as $category) {
                $categoriesFilter[] = $category->term_id;
            }

            $where = 'AND f.parent_id IN ('.implode(',', $categoriesFilter).')';

            $start = $this->asgarosforum->current_page * $this->asgarosforum->options['topics_per_page'];
            $end = $this->asgarosforum->options['topics_per_page'];
            $limit = $this->asgarosforum->db->prepare("LIMIT %d, %d", $start, $end);

            $shortcodeSearchFilter = $this->asgarosforum->shortcode->shortcodeSearchFilter;

            $match_name = "MATCH (search_name.name) AGAINST ('{$this->search_keywords_for_query}*' IN BOOLEAN MODE)";
            $match_text = "MATCH (search_text.text) AGAINST ('{$this->search_keywords_for_query}*' IN BOOLEAN MODE)";
            $query_author = "SELECT author_id FROM {$this->asgarosforum->tables->posts} WHERE parent_id = t.id ORDER BY id ASC LIMIT 1";
            $query_answers = "SELECT (COUNT(*) - 1) FROM {$this->asgarosforum->tables->posts} WHERE parent_id = t.id";
            $query_match_name = "SELECT search_name.id AS topic_id, {$match_name} AS score_name, 0 AS score_text FROM {$this->asgarosforum->tables->topics} AS search_name WHERE MATCH (search_name.name) AGAINST ('{$this->search_keywords_for_query}*' IN BOOLEAN MODE) GROUP BY topic_id";
            $query_match_text = "SELECT search_text.parent_id AS topic_id, 0 AS score_name, {$match_text} AS score_text FROM {$this->asgarosforum->tables->posts} AS search_text WHERE MATCH (search_text.text) AGAINST ('{$this->search_keywords_for_query}*' IN BOOLEAN MODE) GROUP BY topic_id";

            $query = "SELECT t.*, ({$query_author}) AS author_id, ({$query_answers}) AS answers, search_union.topic_id, SUM(search_union.score_name + search_union.score_text) AS score FROM ({$query_match_name} UNION {$query_match_text}) AS search_union, {$this->asgarosforum->tables->topics} AS t, {$this->asgarosforum->tables->forums} AS f WHERE search_union.topic_id = t.id AND t.parent_id = f.id {$where} {$shortcodeSearchFilter} GROUP BY search_union.topic_id ORDER BY score DESC, search_union.topic_id DESC {$limit}";

            $results = $this->asgarosforum->db->get_results($query);

            if (!empty($results)) {
                return $results;
            }
        }

        return false;
    }
}
