<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumRewrite {
    private static $instance = null;
    private static $permalinksEnabled = false;

    // AsgarosForumRewrite instance creator
    public static function createInstance() {
		if (self::$instance === null) {
			self::$instance = new self;
		}

        return self::$instance;
	}

    // AsgarosForumRewrite constructor
	private function __construct() {
        add_action('init', array($this, 'addRewriteRules'));
        add_action('init', array($this, 'addRewriteTags'));
        add_filter('query_vars', array($this, 'addQueryVars'));

        // Check if permalinks are enabled.
        if (get_option('permalink_structure')) {
            self::$permalinksEnabled = true;
        }
	}

    public static function addRewriteRules() {
        /*
        add_rewrite_rule(
            '^([^/]*)/([^/]*)/([0-9]+)/?$',
            'index.php?pagename=$matches[1]&view=$matches[2]&id=$matches[3]',
            'top'
        );

        add_rewrite_rule(
            '^([^/]*)/([^/]*)/([0-9]+)/([0-9]+)/?$',
            'index.php?pagename=$matches[1]&view=$matches[2]&id=$matches[3]&part=$matches[4]',
            'top'
        );
        */
    }

    public static function addRewriteTags() {
        /*
        add_rewrite_tag('%view%', '([^/]*)');
        add_rewrite_tag('%id%', '([0-9]+)');
        add_rewrite_tag('%part%', '([0-9]+)');
        */
    }

    public static function addQueryVars($vars) {
        /*
        $vars[] = 'view';
        $vars[] = 'id';
        $vars[] = 'part';
        */

        return $vars;
    }

    public static function setLinks() {
        global $asgarosforum, $wp;

        $links = new StdClass;
        $links->home = esc_url(get_permalink());
        $links->forum = esc_url(add_query_arg(array('view' => 'forum'), $links->home).'&amp;id=');
        $links->topic = esc_url(add_query_arg(array('view' => 'thread'), $links->home).'&amp;id=');
        $links->topic_add = esc_url(add_query_arg(array('view' => 'addthread', 'id' => $asgarosforum->current_forum), $links->home));
        $links->topic_move = esc_url(add_query_arg(array('view' => 'movethread', 'id' => $asgarosforum->current_thread), $links->home));
        $links->post_add = esc_url(add_query_arg(array('view' => 'addpost', 'id' => $asgarosforum->current_thread), $links->home));
        $links->post_edit = esc_url(add_query_arg(array('view' => 'editpost'), $links->home).'&amp;id=');
        $links->current = esc_url(add_query_arg($_SERVER['QUERY_STRING'], '', trailingslashit(home_url($wp->request))));

        return $links;
    }
}

?>
