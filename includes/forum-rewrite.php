<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumRewrite {
    private static $asgarosforum = null;
    private static $usePermalinks = false;

    public function __construct($object) {
		self::$asgarosforum = $object;

        // Check if permalinks are enabled.
        if (get_option('permalink_structure')) {
            $usePermalinks = true;
        }
	}

    public static function setLinks() {
        global $wp;
        $links = array();
        $links['home']        = get_page_link(self::$asgarosforum->options['location']);
        $links['search']      = add_query_arg(array('view' => 'search'), $links['home']);
        $links['forum']       = add_query_arg(array('view' => 'forum'), $links['home']);
        $links['topic']       = add_query_arg(array('view' => 'thread'), $links['home']);
        $links['topic_add']   = add_query_arg(array('view' => 'addthread'), $links['home']);
        $links['topic_move']  = add_query_arg(array('view' => 'movetopic'), $links['home']);
        $links['post_add']    = add_query_arg(array('view' => 'addpost'), $links['home']);
        $links['post_edit']   = add_query_arg(array('view' => 'editpost'), $links['home']);
        $links['current']     = add_query_arg($_SERVER['QUERY_STRING'], '', trailingslashit(home_url($wp->request)));
        return $links;
    }

    public static function createUniqueSlug($name, $location) {
        $slug = sanitize_title($name);
        $slug = (is_numeric($slug)) ? 'forum-'.$slug : $slug;
        $existingSlugs = self::$asgarosforum->db->get_col("SELECT slug FROM ".$location." WHERE slug LIKE '".$slug."%';");

        if (count($existingSlugs) !== 0 && in_array($slug, $existingSlugs)) {
            $max = 1;
            while (in_array(($slug.'-'.++$max), $existingSlugs));
            $slug .= '-'.$max;
        }

        return $slug;
    }
}
