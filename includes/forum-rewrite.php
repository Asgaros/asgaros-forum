<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumRewrite {
    private static $asgarosforum = null;
    private static $usePermalinks = false;
    private static $links = array();
    public static $slugCache = array();

    public function __construct($object) {
		self::$asgarosforum = $object;

        // Check if permalinks are enabled.
        if (get_option('permalink_structure')) {
            $usePermalinks = true;
        }

        add_action('init', array($this, 'initialize'));
	}

    public function initialize() {
        // Empty ...
    }

    // Builds and returns a requested link.
    public static function getLink($type, $elementID = false, $additionalParameters = false, $appendix = '', $escapeURL = true) {
        // Only generate a link when that type is available.
        if (isset(self::$links[$type])) {
            // Set an ID if available, otherwise initialize the base-link.
            $link = ($elementID) ? add_query_arg('id', $elementID, self::$links[$type]) : self::$links[$type];

            // Set additional parameters if available, otherwise let the link unchanged.
            $link = ($additionalParameters) ? add_query_arg($additionalParameters, $link) : $link;

            // Return (escaped) URL with optional appendix at the end if set.
            if ($escapeURL) {
                return esc_url($link.$appendix);
            } else {
                return $link.$appendix;
            }
        } else {
            return false;
        }
    }

    public static function setLinks() {
        global $wp;
        $links = array();
        $links['home']          = get_page_link(self::$asgarosforum->options['location']);
        $links['subscriptions'] = add_query_arg(array('view' => 'subscriptions'), $links['home']);
        $links['search']        = add_query_arg(array('view' => 'search'), $links['home']);
        $links['forum']         = add_query_arg(array('view' => 'forum'), $links['home']);
        $links['topic']         = add_query_arg(array('view' => 'thread'), $links['home']);
        $links['topic_add']     = add_query_arg(array('view' => 'addtopic'), $links['home']);
        $links['topic_move']    = add_query_arg(array('view' => 'movetopic'), $links['home']);
        $links['post_add']      = add_query_arg(array('view' => 'addpost'), $links['home']);
        $links['post_edit']     = add_query_arg(array('view' => 'editpost'), $links['home']);
        $links['markallread']   = add_query_arg(array('view' => 'markallread'), $links['home']);
        $links['current']       = add_query_arg($_SERVER['QUERY_STRING'], '', trailingslashit(home_url($wp->request)));

        $links = self::$asgarosforum->profile->setLinks($links);

        self::$links = $links;
    }

    public static function createUniqueSlug($name, $location, $type) {
        // Cache all existing slugs if not already done.
        if (empty(self::$slugCache[$type])) {
            self::$slugCache[$type] = self::$asgarosforum->db->get_col("SELECT slug FROM ".$location." WHERE slug <> '';");
        }

        // Suggest a new slug for the element.
        $slug = sanitize_title($name);
        $slug = (is_numeric($slug)) ? $type.'-'.$slug : $slug;

        // Modify the suggested slug when it already exists.
        if (!empty(self::$slugCache[$type]) && in_array($slug, self::$slugCache[$type])) {
            $max = 1;
            while (in_array(($slug.'-'.++$max), self::$slugCache[$type]));
            $slug .= '-'.$max;
        }

        // Safe newly generated slug in cache.
        self::$slugCache[$type][] = $slug;

        return $slug;
    }
}
