<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPagination {
    private $asgarosforum = null;

    public function __construct($object) {
		$this->asgarosforum = $object;
    }

    public function renderTopicOverviewPagination($topicID) {
        $count = $this->asgarosforum->db->get_var($this->asgarosforum->db->prepare("SELECT COUNT(*) FROM {$this->asgarosforum->tables->posts} WHERE parent_id = %d;", $topicID));
        $num_pages = ceil($count / $this->asgarosforum->options['posts_per_page']);

        // Only show pagination when there is more than one page.
        if ($num_pages > 1) {
            echo '&nbsp;&middot;&nbsp;<div class="pages">';

            if ($num_pages <= 5) {
                for ($i = 1; $i <= $num_pages; $i++) {
                    $link = $this->asgarosforum->getLink('topic', $topicID, array('part' => $i));

                    echo '<a href="'.$link.'">'.number_format_i18n($i).'</a>';
                }
            } else {
                for ($i = 1; $i <= 3; $i++) {
                    $link = $this->asgarosforum->getLink('topic', $topicID, array('part' => $i));

                    echo '<a href="'.$link.'">'.number_format_i18n($i).'</a>';
                }

                $link = $this->asgarosforum->getLink('topic', $topicID, array('part' => $num_pages));
                echo '&raquo;<a href="'.$link.'">'.__('Last', 'asgaros-forum').'</a>';
            }

            echo '</div>';
        }
    }

    public function renderPagination($location, $sourceID = false) {
        $current_page = $this->asgarosforum->current_page;
        $num_pages = 0;
        $select_source = '';
        $select_url = '';
        $link = '';

        if ($location == $this->asgarosforum->tables->posts) {
            $count = $this->asgarosforum->db->get_var($this->asgarosforum->db->prepare("SELECT COUNT(*) FROM {$location} WHERE parent_id = %d;", $sourceID));
            $num_pages = ceil($count / $this->asgarosforum->options['posts_per_page']);
            $select_source = $sourceID;
            $select_url = 'topic';
        } else if ($location == $this->asgarosforum->tables->topics) {
            $count = $this->asgarosforum->db->get_var($this->asgarosforum->db->prepare("SELECT COUNT(*) FROM {$location} WHERE parent_id = %d AND status LIKE %s;", $sourceID, "normal%"));
            $num_pages = ceil($count / $this->asgarosforum->options['topics_per_page']);
            $select_source = $sourceID;
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

        // Only show pagination when there is more than one page.
        if ($num_pages > 1) {
            $out = '<div class="pages">';
            $out .= __('Pages:', 'asgaros-forum');

            if ($num_pages <= 5) {
                for ($i = 1; $i <= $num_pages; $i++) {
                    if ($i == ($current_page + 1)) {
                        $out .= '<strong>'.number_format_i18n($i).'</strong>';
                    } else {
                        if ($location === 'search') {
                            $link = $this->asgarosforum->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => $i));
                        } else {
                            $link = $this->asgarosforum->getLink($select_url, $select_source, array('part' => $i));
                        }

                        $out .= '<a href="'.$link.'">'.number_format_i18n($i).'</a>';
                    }
                }
            } else {
                if ($current_page >= 3) {
                    if ($location === 'search') {
                        $link = $this->asgarosforum->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords));
                    } else {
                        $link = $this->asgarosforum->getLink($select_url, $select_source);
                    }

                    $out .= '<a href="'.$link.'">'.__('First', 'asgaros-forum').'</a>&laquo;';
                }

                for ($i = 2; $i > 0; $i--) {
                    if ((($current_page + 1) - $i) > 0) {
                        if ($location === 'search') {
                            $link = $this->asgarosforum->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => (($current_page + 1) - $i)));
                        } else {
                            $link = $this->asgarosforum->getLink($select_url, $select_source, array('part' => (($current_page + 1) - $i)));
                        }

                        $out .= '<a href="'.$link.'">'.number_format_i18n(($current_page + 1) - $i).'</a>';
                    }
                }

                $out .= '<strong>'.number_format_i18n($current_page + 1).'</strong>';

                for ($i = 1; $i <= 2; $i++) {
                    if ((($current_page + 1) + $i) <= $num_pages) {
                        if ($location === 'search') {
                            $link = $this->asgarosforum->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => (($current_page + 1) + $i)));
                        } else {
                            $link = $this->asgarosforum->getLink($select_url, $select_source, array('part' => (($current_page + 1) + $i)));
                        }

                        $out .= '<a href="'.$link.'">'.number_format_i18n(($current_page + 1) + $i).'</a>';
                    }
                }

                if ($num_pages - $current_page >= 4) {
                    if ($location === 'search') {
                        $link = $this->asgarosforum->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => $num_pages));
                    } else {
                        $link = $this->asgarosforum->getLink($select_url, $select_source, array('part' => $num_pages));
                    }

                    $out .= '&raquo;<a href="'.$link.'">'.__('Last', 'asgaros-forum').'</a>';
                }
            }

            $out .= '</div>';
            return $out;
        } else {
            return false;
        }
    }
}
