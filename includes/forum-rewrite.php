<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumRewrite {
    private $asgarosforum = null;
    public $use_permalinks = false;
    private $links = array();
    public $slug_cache = array();

    function __construct($object) {
		$this->asgarosforum = $object;

        // Check if permalinks are enabled.
        if ($this->asgarosforum->options['enable_seo_urls'] && get_option('permalink_structure')) {
            $this->use_permalinks = true;

            add_filter('rewrite_rules_array', array($this, 'add_rewrite_rules_array'));
            add_filter('redirect_canonical', array($this, 'disable_front_page_redirect'), 10, 2);
        }
	}

    // Ensures that all rewrite rules exist.
    private function ensure_rewrite_rules() {
        if ($this->use_permalinks) {
            // Get the rewrite rule pattern.
            $pattern = $this->generate_rewrite_rule_pattern($this->asgarosforum->options['location']);

            $rules = get_option('rewrite_rules');

            if (!isset($rules[$pattern])) {
                flush_rewrite_rules(false);
            }
        }
    }

    // Generate all necessary rewrite rules.
    function add_rewrite_rules_array($rules) {
        // Get all pages with a shortcode first.
        $page_ids = $this->asgarosforum->db->get_col('SELECT ID FROM '.$this->asgarosforum->db->prefix.'posts WHERE post_type = "page" AND (post_content LIKE "%[forum%" OR post_content LIKE "%[Forum%");');

        if (!empty($page_ids)) {
            foreach ($page_ids as $page_id) {
                // Get the rewrite rule pattern.
                $pattern = $this->generate_rewrite_rule_pattern($page_id);

                // Set target url.
                $target_url = 'index.php?page_id='.$page_id;

                // Add rule to array when it does not exists.
                if (!in_array($target_url, $rules)) {
                    $rules = array_merge(array($pattern => $target_url), $rules);
                }
            }
        }

        return $rules;
    }

    // Generates a rewrite rule pattern based on the given page id.
    private function generate_rewrite_rule_pattern($page_id) {
        // Retrieve relative base url. We need to use the internal _get_page_link function because
        // otherwise the generated links would not be correct when the forum is located on a static front page.
        $home_url = trailingslashit(home_url());
        $perm_url = trailingslashit(_get_page_link($page_id));
        $base_url = str_replace($home_url, '', $perm_url);
        $base_url = untrailingslashit($base_url);

        // Generate the pattern.
        $pattern = $base_url.'((?:/|$).*)$';

        return $pattern;
    }

    // Disable canonical redirect for the static front page when the forum is located on it. Otherwise the rewrite rules would not work.
    function disable_front_page_redirect($requested_url, $do_redirect) {
        global $post;

        if (get_option('show_on_front') === 'page') {
            $front_page_id = get_option('page_on_front');

            if ($front_page_id == $post->ID && $front_page_id == $this->asgarosforum->options['location']) {
                $requested_url = false;
            }
        }

        return $requested_url;
    }

    // Tries to parse the url and set the corresponding values.
    function parse_url() {
        // Set the current view.
        if (!empty($_GET['view'])) {
            $this->asgarosforum->current_view = esc_html($_GET['view']);
        }

        // Set the current element id.
        if (!empty($_GET['id'])) {
            $this->asgarosforum->current_element = absint($_GET['id']);
        }

        // Set the current page.
        if (isset($_GET['part']) && absint($_GET['part']) > 0) {
            $this->asgarosforum->current_page = (absint($_GET['part']) - 1);
        }

        // Fallback for old view-name.
        if ($this->asgarosforum->current_view == 'thread') {
             $this->asgarosforum->current_view = 'topic';
        }

        // Try to set current elements based on permalinks.
        if ($this->use_permalinks) {
            // Do a 301 redirect if necessary.
            $this->maybe_301_redirect();

            // Create base urls.
            $home_url = $this->get_link('home');
            $current_url = $this->get_link('current');

            // Remove the home url from the beginning of the current url.
            $parsed_url = preg_replace('#^/?'.preg_quote($home_url).'#isu', '', $current_url, 1);

            // Remove parameters from the current url.
            $parsed_url = preg_replace('#/?\?.*$#isu', '', $parsed_url);

            // Trim url and split parameters.
            $parsed_url = trim($parsed_url, '/');
            $parsed_url = explode('/', $parsed_url);

            // Set the current view.
            if (!empty($parsed_url[0])) {
                $this->asgarosforum->current_view = esc_html($parsed_url[0]);
            }

            // Set the current element id.
            if (!empty($parsed_url[1])) {
                // If we have a numeric value, its already an idea. But this does not hold for usernames because they can be numeric as well.
                if (is_numeric($parsed_url[1]) && $this->asgarosforum->current_view != 'profile') {
                    $this->asgarosforum->current_element = absint($parsed_url[1]);
                } else {
                    $this->asgarosforum->current_element = $this->convert_slug_to_id($parsed_url[1], $this->asgarosforum->current_view);
                }
            }
        }
    }

    // Do a 301 redirect if necessary.
    function maybe_301_redirect() {
        // When permalinks are enabled and view/id are already set, an old URL was used.
        // In this case we have to do a 301 redirect to point to the updated location.
        // This is necessary to prevent multiple links pointing to the same content in
        // search engines.
        if ($this->asgarosforum->current_view) {
            $redirect_link = $this->get_link($this->asgarosforum->current_view, $this->asgarosforum->current_element);

            if ($this->asgarosforum->current_page) {
                $redirect_link = add_query_arg(array('part' => ($this->asgarosforum->current_page + 1)), $redirect_link);
            }

            $redirect_link = html_entity_decode($redirect_link);

            wp_redirect($redirect_link, 301);
            exit;
        }
    }

    // Builds and returns a requested link.
    function get_link($type, $element_id = false, $additional_parameters = false, $appendix = '', $escape_url = true) {
        // Only generate a link when that type is available.
        if (isset($this->links[$type])) {
            // Initialize the base-link.
            $link = $this->links[$type];

            // Set an ID if available.
            if ($element_id) {
                if ($this->use_permalinks) {
                    if (is_numeric($element_id)) {
                        $element_id = $this->convert_id_to_slug($element_id, $type);
                    }

                    $link = $link.$element_id.'/';
                } else {
                    $link = add_query_arg('id', $element_id, $link);
                }
            }

            // Set additional parameters if available, otherwise let the link unchanged.
            $link = ($additional_parameters) ? add_query_arg($additional_parameters, $link) : $link;

            // Return (escaped) URL with optional appendix at the end if set.
            if ($escape_url) {
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
        $post_link = $this->get_link('topic', $topic_id, $additional_parameters, '#postid-'.$post_id);

        return $post_link;
    }

    function set_links() {
        global $wp;

        $this->ensure_rewrite_rules();

        // Set forum home and current link first. We need to use the internal _get_page_link function because
        // otherwise the generated links would not be correct when the forum is located on a static front page.
        $this->links['home']    = untrailingslashit(_get_page_link($this->asgarosforum->options['location']));

        // Build current link.
        $protocol = strtolower($_SERVER['SERVER_PROTOCOL']);
        $protocol = substr($protocol, 0, strpos($protocol, '/'));

        if (is_ssl()) {
            $protocol .= 's';
        }

        $this->links['current'] = $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        // Set additional links based on global permalink-settings.
        if ($this->use_permalinks) {
            $this->links['activity']      = $this->links['home'].'/activity/';
            $this->links['subscriptions'] = $this->links['home'].'/subscriptions/';
            $this->links['search']        = $this->links['home'].'/search/';
            $this->links['forum']         = $this->links['home'].'/forum/';
            $this->links['topic']         = $this->links['home'].'/topic/';
            $this->links['topic_add']     = $this->links['home'].'/addtopic/';
            $this->links['movetopic']     = $this->links['home'].'/movetopic/';
            $this->links['post_add']      = $this->links['home'].'/addpost/';
            $this->links['post_edit']     = $this->links['home'].'/editpost/';
            $this->links['markallread']   = $this->links['home'].'/markallread/';
            $this->links['members']       = $this->links['home'].'/members/';
            $this->links['profile']       = $this->links['home'].'/profile/';
        } else {
            $this->links['activity']      = add_query_arg(array('view' => 'activity'), $this->links['home']);
            $this->links['subscriptions'] = add_query_arg(array('view' => 'subscriptions'), $this->links['home']);
            $this->links['search']        = add_query_arg(array('view' => 'search'), $this->links['home']);
            $this->links['forum']         = add_query_arg(array('view' => 'forum'), $this->links['home']);
            $this->links['topic']         = add_query_arg(array('view' => 'topic'), $this->links['home']);
            $this->links['topic_add']     = add_query_arg(array('view' => 'addtopic'), $this->links['home']);
            $this->links['movetopic']     = add_query_arg(array('view' => 'movetopic'), $this->links['home']);
            $this->links['post_add']      = add_query_arg(array('view' => 'addpost'), $this->links['home']);
            $this->links['post_edit']     = add_query_arg(array('view' => 'editpost'), $this->links['home']);
            $this->links['markallread']   = add_query_arg(array('view' => 'markallread'), $this->links['home']);
            $this->links['members']       = add_query_arg(array('view' => 'members'), $this->links['home']);
            $this->links['profile']       = add_query_arg(array('view' => 'profile'), $this->links['home']);
        }
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
                case 'movetopic':
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
                case 'movetopic':
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
