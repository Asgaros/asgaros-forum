<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumRewrite {
    private $asgarosforum = null;
    private $usePermalinks = false;
    private $links = array();
    public $slug_cache = array();

    function __construct($object) {
		$this->asgarosforum = $object;

        // Check if permalinks are enabled.
        if (get_option('permalink_structure')) {
            $usePermalinks = true;
        }

        add_action('init', array($this, 'initialize'));
	}

    function initialize() {
        // Empty ...
    }

    // Builds and returns a requested link.
    function getLink($type, $elementID = false, $additionalParameters = false, $appendix = '', $escapeURL = true) {
        // Only generate a link when that type is available.
        if (isset($this->links[$type])) {
            // Set an ID if available, otherwise initialize the base-link.
            $link = ($elementID) ? add_query_arg('id', $elementID, $this->links[$type]) : $this->links[$type];

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

    private $cache_get_post_link_ids = array();
    function get_post_link($post_id, $topic_id = false, $post_page = false, $additional_parameters = array()) {
        // Get the topic ID when we dont know it yet.
        if (!$topic_id) {
            $topic_id = $this->asgarosforum->db->get_var($this->asgarosforum->db->prepare("SELECT parent_id FROM {$this->asgarosforum->tables->posts} WHERE id = %d;", $post_id));
        }

        // Get the page of the post as well when we dont know it.
        if (!$post_page) {
            // Get all post ids of the topic.
            if (empty($this->cache_get_post_link_ids[$topic_id])) {
                $this->cache_get_post_link_ids[$topic_id] = $this->asgarosforum->db->get_col("SELECT id FROM {$this->asgarosforum->tables->posts} WHERE parent_id = ".$topic_id." ORDER BY id ASC;");
            }

            // Now get the position of the post.
            $post_position = array_search($post_id, $this->cache_get_post_link_ids[$topic_id]) + 1;

            // Now get the page on which this post is located.
            $post_page = ceil($post_position / $this->asgarosforum->options['posts_per_page']);
        }

        $additional_parameters['part'] = $post_page;

        // Now create the link.
        $post_link = $this->getLink('topic', $topic_id, $additional_parameters, '#postid-'.$post_id);

        return $post_link;
    }

    function setLinks() {
        global $wp;
        $links = array();
        $links['home']          = get_page_link($this->asgarosforum->options['location']);
        $links['activity']      = add_query_arg(array('view' => 'activity'), $links['home']);
        $links['subscriptions'] = add_query_arg(array('view' => 'subscriptions'), $links['home']);
        $links['search']        = add_query_arg(array('view' => 'search'), $links['home']);
        $links['forum']         = add_query_arg(array('view' => 'forum'), $links['home']);
        $links['topic']         = add_query_arg(array('view' => 'thread'), $links['home']);
        $links['topic_add']     = add_query_arg(array('view' => 'addtopic'), $links['home']);
        $links['topic_move']    = add_query_arg(array('view' => 'movetopic'), $links['home']);
        $links['post_add']      = add_query_arg(array('view' => 'addpost'), $links['home']);
        $links['post_edit']     = add_query_arg(array('view' => 'editpost'), $links['home']);
        $links['markallread']   = add_query_arg(array('view' => 'markallread'), $links['home']);
        $links['members']       = add_query_arg(array('view' => 'members'), $links['home']);
        $links['current']       = add_query_arg($_SERVER['QUERY_STRING'], '', trailingslashit(home_url($wp->request)));

        $links = $this->asgarosforum->profile->setLinks($links);

        $this->links = $links;
    }

    function create_unique_slug($name, $location, $type) {
        // Cache all existing slugs if not already done.
        if (empty($this->slug_cache[$type])) {
            $this->slug_cache[$type] = $this->asgarosforum->db->get_col("SELECT slug FROM ".$location." WHERE slug <> '';");
        }

        // Suggest a new slug for the element.
        $slug = sanitize_title($name);
        $slug = (is_numeric($slug)) ? $type.'-'.$slug : $slug;

        // Modify the suggested slug when it already exists.
        if (!empty($this->slug_cache[$type]) && in_array($slug, $this->slug_cache[$type])) {
            $max = 1;
            while (in_array(($slug.'-'.++$max), $this->slug_cache[$type]));
            $slug .= '-'.$max;
        }

        // Safe newly generated slug in cache.
        $this->slug_cache[$type][] = $slug;

        return $slug;
    }

    // Converts a slug to an id.
    private $convert_slug_to_id_cache = array();
    function convert_slug_to_id($slug, $type) {
        // Check cache first.
        if (empty($this->convert_slug_to_id_cache[$type.'-'.$slug])) {
            // Set false as a default value in case it does not belong to an element.
            $this->convert_slug_to_id_cache[$type.'-'.$slug] = false;

            // Now try to determine an id.
            switch ($type) {
                case 'topic':
                    $result = $this->asgarosforum->db->get_var('SELECT id FROM '.$this->asgarosforum->tables->topics.' WHERE slug = "'.$slug.'";');

                    if ($result) {
                        $this->convert_slug_to_id_cache[$type.'-'.$slug] = $result;
                    }

                    break;
                case 'forum':
                    $result = $this->asgarosforum->db->get_var('SELECT id FROM '.$this->asgarosforum->tables->forums.' WHERE slug = "'.$slug.'";');

                    if ($result) {
                        $this->convert_slug_to_id_cache[$type.'-'.$slug] = $result;
                    }

                    break;
                case 'profile':
                    $result = get_user_by('slug', $slug);

                    if ($result) {
                        $this->convert_slug_to_id_cache[$type.'-'.$slug] = $result->ID;
                    }

                    break;
            }
        }

        return $this->convert_slug_to_id_cache[$type.'-'.$slug];
    }

    // Converts an id to a slug.
    private $convert_id_to_slug_cache = array();
    function convert_id_to_slug($id, $type) {
        // Check cache first.
        if (empty($this->convert_id_to_slug_cache[$type.'-'.$id])) {
            // Set the id as a default value in case we cant find a slug.
            $this->convert_id_to_slug_cache[$type.'-'.$id] = $id;

            // Now try to determine a slug.
            switch ($type) {
                case 'topic':
                    $result = $this->asgarosforum->db->get_var('SELECT slug FROM '.$this->asgarosforum->tables->topics.' WHERE id = '.$id.';');

                    if ($result) {
                        $this->convert_id_to_slug_cache[$type.'-'.$id] = $result;
                    }

                    break;
                case 'forum':
                    $result = $this->asgarosforum->db->get_var('SELECT slug FROM '.$this->asgarosforum->tables->forums.' WHERE id = '.$id.';');

                    if ($result) {
                        $this->convert_id_to_slug_cache[$type.'-'.$id] = $result;
                    }

                    break;
                case 'profile':
                    $result = get_user_by('id', $id);

                    if ($result) {
                        $this->convert_id_to_slug_cache[$type.'-'.$id] = $result->user_nicename;
                    }

                    break;
            }
        }

        return $this->convert_id_to_slug_cache[$type.'-'.$id];
    }
}
