<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPagination {
    private $asgarosforum = null;

    public function __construct($object) {
		$this->asgarosforum = $object;
    }

    public function renderPagination($location) {
        $out = '<div class="pages">'.__('Pages:', 'asgaros-forum');
        $num_pages = 0;
        $select_source = '';
        $select_url = '';
        $link = '';

        if ($location == $this->asgarosforum->tables->posts) {
            $count = $this->asgarosforum->db->get_var($this->asgarosforum->db->prepare("SELECT COUNT(*) FROM {$location} WHERE parent_id = %d;", $this->asgarosforum->current_topic));
            $num_pages = ceil($count / $this->asgarosforum->options['posts_per_page']);
            $select_source = $this->asgarosforum->current_topic;
            $select_url = 'topic';
        } else if ($location == $this->asgarosforum->tables->topics) {
            $count = $this->asgarosforum->db->get_var($this->asgarosforum->db->prepare("SELECT COUNT(*) FROM {$location} WHERE parent_id = %d AND status LIKE %s;", $this->asgarosforum->current_forum, "normal%"));
            $num_pages = ceil($count / $this->asgarosforum->options['topics_per_page']);
            $select_source = $this->asgarosforum->current_forum;
            $select_url = 'forum';
        } else if ($location === 'search') {
            $categories = $this->asgarosforum->get_categories();
            $categoriesFilter = array();

            foreach ($categories as $category) {
                $categoriesFilter[] = $category->term_id;
            }

            $where = 'AND f.parent_id IN ('.implode(',', $categoriesFilter).')';
            $keywords = AsgarosForumSearch::$searchKeywords;
            $shortcodeSearchFilter = AsgarosForumShortcodes::$shortcodeSearchFilter;
            $count = $this->asgarosforum->db->get_col("SELECT t.id FROM {$this->asgarosforum->tables->topics} AS t, {$this->asgarosforum->tables->posts} AS p, {$this->asgarosforum->tables->forums} AS f WHERE p.parent_id = t.id AND t.parent_id = f.id AND MATCH (p.text) AGAINST ('".$keywords."*' IN BOOLEAN MODE) {$where} {$shortcodeSearchFilter} GROUP BY p.parent_id;");
            $count = count($count);
            $num_pages = ceil($count / $this->asgarosforum->options['topics_per_page']);
            $select_url = 'search';
        }

        if ($num_pages > 1) {
            if ($num_pages <= 6) {
                for ($i = 1; $i <= $num_pages; $i++) {
                    if ($i == ($this->asgarosforum->current_page + 1)) {
                        $out .= ' <strong>'.number_format_i18n($i).'</strong>';
                    } else {
                        if ($location === 'search') {
                            $link = $this->asgarosforum->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => $i));
                        } else {
                            $link = $this->asgarosforum->getLink($select_url, $select_source, array('part' => $i));
                        }

                        $out .= ' <a href="'.$link.'">'.number_format_i18n($i).'</a>';
                    }
                }
            } else {
                if ($this->asgarosforum->current_page >= 4) {
                    if ($location === 'search') {
                        $link = $this->asgarosforum->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords));
                    } else {
                        $link = $this->asgarosforum->getLink($select_url, $select_source);
                    }

                    $out .= ' <a href="'.$link.'">'.__('First', 'asgaros-forum').'</a> &laquo;';
                }

                for ($i = 3; $i > 0; $i--) {
                    if ((($this->asgarosforum->current_page + 1) - $i) > 0) {
                        if ($location === 'search') {
                            $link = $this->asgarosforum->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => (($this->asgarosforum->current_page + 1) - $i)));
                        } else {
                            $link = $this->asgarosforum->getLink($select_url, $select_source, array('part' => (($this->asgarosforum->current_page + 1) - $i)));
                        }

                        $out .= ' <a href="'.$link.'">'.number_format_i18n(($this->asgarosforum->current_page + 1) - $i).'</a>';
                    }
                }

                $out .= ' <strong>'.number_format_i18n($this->asgarosforum->current_page + 1).'</strong>';

                for ($i = 1; $i <= 3; $i++) {
                    if ((($this->asgarosforum->current_page + 1) + $i) <= $num_pages) {
                        if ($location === 'search') {
                            $link = $this->asgarosforum->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => (($this->asgarosforum->current_page + 1) + $i)));
                        } else {
                            $link = $this->asgarosforum->getLink($select_url, $select_source, array('part' => (($this->asgarosforum->current_page + 1) + $i)));
                        }

                        $out .= ' <a href="'.$link.'">'.number_format_i18n(($this->asgarosforum->current_page + 1) + $i).'</a>';
                    }
                }

                if ($num_pages - $this->asgarosforum->current_page >= 5) {
                    if ($location === 'search') {
                        $link = $this->asgarosforum->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => $num_pages));
                    } else {
                        $link = $this->asgarosforum->getLink($select_url, $select_source, array('part' => $num_pages));
                    }

                    $out .= ' &raquo; <a href="'.$link.'">'.__('Last', 'asgaros-forum').'</a>';
                }
            }

            $out .= '</div>';
            return $out;
        } else {
            return '';
        }
    }
}
