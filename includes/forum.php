<?php

if (!defined('ABSPATH')) exit;

class AsgarosForum {
    var $executePlugin = false;
    var $db = null;
    var $tables = null;
    var $directory = '';
    var $date_format = '';
    var $error = false;
    var $info = false;
    var $current_category = false;
    var $current_forum = false;
    var $current_topic = false;
    var $current_post = false;
    var $current_view = false;
    var $current_page = 0;
    var $parent_forum = false;
    var $category_access_level = false;
    var $links = array();
    var $options = array();
    var $options_default = array(
        'location'                  => 0,
        'posts_per_page'            => 10,
        'topics_per_page'           => 20,
        'minimalistic_editor'       => true,
        'allow_shortcodes'          => false,
        'allow_guest_postings'      => false,
        'allowed_filetypes'         => 'jpg,jpeg,gif,png,bmp,pdf',
        'allow_file_uploads'        => false,
        'allow_file_uploads_guests' => false,
        'hide_uploads_from_guests'  => false,
        'admin_subscriptions'       => false,
        'allow_subscriptions'       => true,
        'enable_search'             => true,
        'highlight_admin'           => true,
        'highlight_authors'         => true,
        'show_edit_date'            => true,
        'require_login'             => false,
        'custom_color'              => '#2d89cc',
        'custom_text_color'         => '#444444',
        'custom_background_color'   => '#ffffff',
        'theme'                     => 'default'
    );
    var $options_editor = array(
        'media_buttons' => false,
        'textarea_rows' => 12,
        'teeny'         => true,
        'quicktags'     => false
    );
    var $cache = array();   // Used to store selected database queries.

    function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->directory = plugin_dir_url(dirname(__FILE__));
        $this->options = array_merge($this->options_default, get_option('asgarosforum_options', array()));
        $this->options_editor['teeny'] = $this->options['minimalistic_editor'];
        $this->date_format = get_option('date_format').', '.get_option('time_format');
        $this->tables = AsgarosForumDatabase::getTables();

        add_action('init', array($this, 'initialize'));
        add_action('widgets_init', array($this, 'initialize_widgets'));
        add_action('wp', array($this, 'prepare'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_front_scripts'));
        add_filter('wp_title', array($this, 'change_wp_title'), 10, 3);
        add_filter('document_title_parts', array($this, 'change_document_title_parts'));
        add_filter('teeny_mce_buttons', array($this, 'add_mce_buttons'), 9999, 2);
        add_filter('mce_buttons', array($this, 'add_mce_buttons'), 9999, 2);
        add_filter('disable_captions', array($this, 'disable_captions'));

        // Register multiple shortcodes because sometimes users ignore the fact that shortcodes are case-sensitive.
        add_shortcode('forum', array($this, 'forum'));
        add_shortcode('Forum', array($this, 'forum'));
    }

    function initialize() {
        new AsgarosForumTaxonomies();
        new AsgarosForumUploads($this);
        AsgarosForumUnread::createInstance();
    }

    function initialize_widgets() {
        new AsgarosForumWidgets($this);
    }

    function prepare() {
        global $post;

        if (is_a($post, 'WP_Post') && $this->checkForShortcode($post)) {
            $this->executePlugin = true;
            $this->options['location'] = $post->ID;
        }

        // Set all base links.
        if ($this->executePlugin || get_post($this->options['location'])) {
            $this->setLinks();
        }

        if (!$this->executePlugin) {
            return;
        }

        if (isset($_GET['view'])) {
            $this->current_view = esc_html($_GET['view']);
        }

        if (isset($_GET['part']) && absint($_GET['part']) > 0) {
            $this->current_page = (absint($_GET['part']) - 1);
        }

        $elementID = (isset($_GET['id'])) ? absint($_GET['id']) : false;

        switch ($this->current_view) {
            case 'forum':
            case 'addthread':
                if ($this->element_exists($elementID, $this->tables->forums)) {
                    $this->current_forum = $elementID;
                    $this->parent_forum = $this->get_parent_id($this->current_forum, $this->tables->forums, 'parent_forum');
                    $this->current_category = $this->get_parent_id($this->current_forum, $this->tables->forums);
                } else {
                    $this->error = __('Sorry, this forum does not exist.', 'asgaros-forum');
                }
                break;
            case 'movetopic':
            case 'thread':
            case 'addpost':
                if ($this->element_exists($elementID, $this->tables->topics)) {
                    $this->current_topic = $elementID;
                    $this->current_forum = $this->get_parent_id($this->current_topic, $this->tables->topics);
                    $this->parent_forum = $this->get_parent_id($this->current_forum, $this->tables->forums, 'parent_forum');
                    $this->current_category = $this->get_parent_id($this->current_forum, $this->tables->forums);
                } else {
                    $this->error = __('Sorry, this topic does not exist.', 'asgaros-forum');
                }
                break;
            case 'editpost':
                if ($this->element_exists($elementID, $this->tables->posts)) {
                    $this->current_post = $elementID;
                    $this->current_topic = $this->get_parent_id($this->current_post, $this->tables->posts);
                    $this->current_forum = $this->get_parent_id($this->current_topic, $this->tables->topics);
                    $this->parent_forum = $this->get_parent_id($this->current_forum, $this->tables->forums, 'parent_forum');
                    $this->current_category = $this->get_parent_id($this->current_forum, $this->tables->forums);
                } else {
                    $this->error = __('Sorry, this post does not exist.', 'asgaros-forum');
                }
                break;
            case 'search':
                // Go back to overview when search is not enabled.
                if (!$this->options['enable_search']) {
                    $this->current_view = 'overview';
                }
                break;
            default:
                $this->current_view = 'overview';
                break;
        }

        // Check
        $this->check_access();

        // Override editor settings.
        $this->options_editor = apply_filters('asgarosforum_filter_editor_settings', $this->options_editor);

        // Prevent generation of some head-elements.
        remove_action('wp_head', 'rel_canonical');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');

        if (isset($_POST['submit_action']) && (is_user_logged_in() || $this->options['allow_guest_postings'])) {
            if (AsgarosForumInsert::getAction()) {
                AsgarosForumInsert::setData();
                if (AsgarosForumInsert::validateExecution()) {
                    AsgarosForumInsert::insertData();
                }
            }
        } else if ($this->current_view === 'markallread') {
            AsgarosForumUnread::markAllRead();
        } else if (isset($_GET['move_thread'])) {
            $this->move_thread();
        } else if (isset($_GET['delete_thread'])) {
            $this->delete_thread($this->current_topic);
        } else if (isset($_GET['remove_post'])) {
            $this->remove_post();
        } else if (isset($_GET['sticky_topic']) || isset($_GET['unsticky_topic'])) {
            $this->change_status('sticky');
        } else if (isset($_GET['open_topic']) || isset($_GET['close_topic'])) {
            $this->change_status('closed');
        } else if (isset($_GET['subscribe_topic'])) {
            AsgarosForumNotifications::subscribeTopic();
        } else if (isset($_GET['unsubscribe_topic'])) {
            AsgarosForumNotifications::unsubscribeTopic();
        }

        // Mark visited topic as read.
        if ($this->current_view === 'thread' && $this->current_topic) {
            AsgarosForumUnread::markThreadRead();
        }
    }

    function checkForShortcode($postObject = false) {
        // If no post-object is set, use the location.
        if (!$postObject && $this->options['location']) {
            $postObject = get_post($this->options['location']);
        }

        if ($postObject && (has_shortcode($postObject->post_content, 'forum') || has_shortcode($postObject->post_content, 'Forum'))) {
            return true;
        } else {
            return false;
        }
    }

    function setLinks() {
        global $wp;
        $this->links['home']        = get_page_link($this->options['location']);
        $this->links['search']      = add_query_arg(array('view' => 'search'), $this->links['home']);
        $this->links['forum']       = add_query_arg(array('view' => 'forum'), $this->links['home']);
        $this->links['topic']       = add_query_arg(array('view' => 'thread'), $this->links['home']);
        $this->links['topic_add']   = add_query_arg(array('view' => 'addthread'), $this->links['home']);
        $this->links['topic_move']  = add_query_arg(array('view' => 'movetopic'), $this->links['home']);
        $this->links['post_add']    = add_query_arg(array('view' => 'addpost'), $this->links['home']);
        $this->links['post_edit']   = add_query_arg(array('view' => 'editpost'), $this->links['home']);
        $this->links['current']     = add_query_arg($_SERVER['QUERY_STRING'], '', trailingslashit(home_url($wp->request)));
    }

    function check_access() {
        // Check login access.
        if ($this->options['require_login'] && !is_user_logged_in()) {
            $this->error = __('Sorry, only logged in users have access to the forum.', 'asgaros-forum').'&nbsp;<a href="'.esc_url(wp_login_url($this->getLink('current'))).'">&raquo; '.__('Login', 'asgaros-forum').'</a>';
            $this->error = apply_filters('asgarosforum_filter_error_message_require_login', $this->error);
            return;
        }

        // Check category access.
        $this->category_access_level = get_term_meta($this->current_category, 'category_access', true);

        if ($this->category_access_level) {
            if ($this->category_access_level === 'loggedin' && !is_user_logged_in()) {
                $this->error = __('Sorry, only logged in users have access to this category.', 'asgaros-forum').'&nbsp;<a href="'.esc_url(wp_login_url($this->getLink('current'))).'">&raquo; '.__('Login', 'asgaros-forum').'</a>';
                return;
            }

            if ($this->category_access_level === 'moderator' && !AsgarosForumPermissions::isModerator('current')) {
                $this->error = __('Sorry, you dont have access to this area.', 'asgaros-forum');
                return;
            }
        }

        // Check custom access.
        $custom_access = apply_filters('asgarosforum_filter_check_access', true, $this->current_category);

        if (!$custom_access) {
            $this->error = __('Sorry, you dont have access to this area.', 'asgaros-forum');
            return;
        }
    }

    function enqueue_front_scripts() {
        if (!$this->executePlugin) {
            return;
        }

        wp_enqueue_script('asgarosforum-js', $this->directory.'js/script.js', array('jquery'));
        wp_enqueue_style('dashicons');
    }

    function change_wp_title($title, $sep, $seplocation) {
        return $this->get_title($title);
    }

    function change_document_title_parts($title) {
        $title['title'] = $this->get_title($title['title']);
        return $title;
    }

    function get_title($title) {
        if ($this->executePlugin && !$this->error && $this->current_view) {
            if ($this->current_view == 'forum' && $this->current_forum) {
                $title = esc_html(stripslashes($this->get_name($this->current_forum, $this->tables->forums))).' - '.$title;
            } else if ($this->current_view == 'thread' && $this->current_topic) {
                $title = esc_html(stripslashes($this->get_name($this->current_topic, $this->tables->topics))).' - '.$title;
            } else if ($this->current_view == 'editpost') {
                $title = __('Edit Post', 'asgaros-forum').' - '.$title;
            } else if ($this->current_view == 'addpost') {
                $title = __('Post Reply', 'asgaros-forum').' - '.$title;
            } else if ($this->current_view == 'addthread') {
                $title = __('New Topic', 'asgaros-forum').' - '.$title;
            } else if ($this->current_view == 'movetopic') {
                $title = __('Move Topic', 'asgaros-forum').' - '.$title;
            } else if ($this->current_view == 'search') {
                $title = __('Search', 'asgaros-forum').' - '.$title;
            }
        }

        return $title;
    }

    function add_mce_buttons($buttons, $editor_id) {
        if (!$this->executePlugin || $editor_id !== 'message') {
            return $buttons;
        } else {
            $buttons[] = 'image';
            return $buttons;
        }
    }

    function disable_captions($args) {
        if ($this->executePlugin) {
            return true;
        } else {
            return $args;
        }
    }

    function forum() {
        ob_start();
        echo '<div id="af-wrapper">';

        do_action('asgarosforum_'.$this->current_view.'_custom_content_top');

        if (!empty($this->error)) {
            echo '<div class="error">'.$this->error.'</div>';
        } else {
            $this->breadcrumbs();

            if (!empty($this->info)) {
                echo '<div class="info">'.$this->info.'</div>';
            }

            $this->showLoginMessage();

            switch ($this->current_view) {
                case 'search':
                    include('views/search.php');
                    break;
                case 'movetopic':
                    $this->movetopic();
                    break;
                case 'forum':
                    $this->showforum();
                    break;
                case 'thread':
                    $this->showthread();
                    break;
                case 'addthread':
                case 'addpost':
                case 'editpost':
                    include('views/editor.php');
                    break;
                default:
                    $this->overview();
                    break;
            }
        }

        do_action('asgarosforum_'.$this->current_view.'_custom_content_bottom');

        echo '</div>';
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    function overview() {
        $categories = $this->get_categories();

        require('views/overview.php');
    }

    function showforum() {
        $threads = $this->get_threads($this->current_forum);
        $sticky_threads = $this->get_threads($this->current_forum, 'sticky');
        $counter_normal = count($threads);
        $counter_total = $counter_normal + count($sticky_threads);

        require('views/forum.php');
    }

    function showthread() {
        global $wp_embed;
        $posts = $this->get_posts();

        if ($posts) {
            $this->db->query($this->db->prepare("UPDATE {$this->tables->topics} SET views = views + 1 WHERE id = %d", $this->current_topic));

            $meClosed = ($this->get_status('closed')) ? '&nbsp;('.__('Topic closed', 'asgaros-forum').')' : '';

            require('views/thread.php');
        } else {
            echo '<div class="notice">'.__('Sorry, but there are no posts.', 'asgaros-forum').'</div>';
        }
    }

    function showLoginMessage() {
        if (!is_user_logged_in() && !$this->options['allow_guest_postings']) {
            $loginMessage = '<div class="info">'.__('You need to login in order to create posts and topics.', 'asgaros-forum').'&nbsp;<a href="'.esc_url(wp_login_url($this->getLink('current'))).'">&raquo; '.__('Login', 'asgaros-forum').'</a></div>';
            $loginMessage = apply_filters('asgarosforum_filter_login_message', $loginMessage);
            echo $loginMessage;
        }
    }

    function movetopic() {
        echo '<h1 class="main-title">'.__('Move Topic', 'asgaros-forum').'</h1>';

        if (AsgarosForumPermissions::isModerator('current')) {
            $strOUT = '<form method="post" action="'.$this->getLink('topic_move', $this->current_topic, array('move_thread' => 1)).'">';
            $strOUT .= '<div class="title-element">'.sprintf(__('Move "<strong>%s</strong>" to new forum:', 'asgaros-forum'), esc_html(stripslashes($this->get_name($this->current_topic, $this->tables->topics)))).'</div>';
            $strOUT .= '<div class="content-element"><div class="notice">';
            $strOUT .= '<select name="newForumID">';

            $frs = $this->get_forums();

            foreach ($frs as $f) {
                $strOUT .= '<option value="'.$f->id.'"'.($f->id == $this->current_forum ? ' selected="selected"' : '').'>'.esc_html($f->name).'</option>';
            }

            $strOUT .= '</select><br /><input type="submit" value="'.__('Move', 'asgaros-forum').'"></div></div></form>';

            echo $strOUT;
        } else {
            echo '<div class="notice">'.__('You are not allowed to move threads.', 'asgaros-forum').'</div>';
        }
    }

    function element_exists($id, $location) {
        if (!empty($id) && is_numeric($id) && $this->db->get_row($this->db->prepare("SELECT id FROM {$location} WHERE id = %d;", $id))) {
            return true;
        } else {
            return false;
        }
    }

    function get_postlink($thread_id, $post_id, $page = 0) {
        if (!$page) {
            $postNumber = $this->db->get_var($this->db->prepare("SELECT COUNT(id) FROM {$this->tables->posts} WHERE parent_id = %d;", $thread_id));
            $page = ceil($postNumber / $this->options['posts_per_page']);
        }

        return $this->getLink('topic', $thread_id, array('part' => $page), '#postid-'.$post_id);
    }

    function get_categories($enableFiltering = true) {
        $filter = array();
        $metaQueryFilter = array();

        if ($enableFiltering) {
            $filter = apply_filters('asgarosforum_filter_get_categories', $filter);
            $metaQueryFilter = $this->getCategoriesFilter();
        }

        $categories = get_terms('asgarosforum-category', array('hide_empty' => false, 'exclude' => $filter, 'meta_key' => 'order', 'orderby' => 'order', 'meta_query' => $metaQueryFilter));

        return $categories;
    }

    function getCategoriesFilter() {
        $metaQueryFilter = array('relation' => 'AND');

        if (!AsgarosForumPermissions::isModerator('current')) {
            $metaQueryFilter[] = array(
                'key'       => 'category_access',
                'value'     => 'moderator',
                'compare'   => 'NOT LIKE'
            );
        }

        if (!is_user_logged_in()) {
            $metaQueryFilter[] = array(
                'key'       => 'category_access',
                'value'     => 'loggedin',
                'compare'   => 'NOT LIKE'
            );
        }

        if (sizeof($metaQueryFilter) > 1) {
            return $metaQueryFilter;
        } else {
            return array();
        }
    }

    function categories_compare($a, $b) {
        return ($a->order < $b->order) ? -1 : (($a->order > $b->order) ? 1 : 0);
    }

    function get_forums($id = false, $parent_forum = 0) {
        if ($id) {
            return $this->db->get_results($this->db->prepare("SELECT f.id, f.name, f.description, f.closed, f.sort, f.parent_forum, (SELECT COUNT(ct_t.id) FROM {$this->tables->topics} AS ct_t, {$this->tables->forums} AS ct_f WHERE ct_t.parent_id = ct_f.id AND (ct_f.id = f.id OR ct_f.parent_forum = f.id)) AS count_threads, (SELECT COUNT(cp_p.id) FROM {$this->tables->posts} AS cp_p, {$this->tables->topics} AS cp_t, {$this->tables->forums} AS cp_f WHERE cp_p.parent_id = cp_t.id AND cp_t.parent_id = cp_f.id AND (cp_f.id = f.id OR cp_f.parent_forum = f.id)) AS count_posts, (SELECT COUNT(csf_f.id) FROM {$this->tables->forums} AS csf_f WHERE csf_f.parent_forum = f.id) AS count_subforums FROM {$this->tables->forums} AS f WHERE f.parent_id = %d AND f.parent_forum = %d GROUP BY f.id ORDER BY f.sort ASC;", $id, $parent_forum));
        } else {
            // Load all forums.
            return $this->db->get_results("SELECT id, name FROM {$this->tables->forums} ORDER BY sort ASC;");
        }
    }

    function get_threads($id, $type = 'normal') {
        $limit = "";

        if ($type == 'normal') {
            $start = $this->current_page * $this->options['topics_per_page'];
            $end = $this->options['topics_per_page'];
            $limit = $this->db->prepare("LIMIT %d, %d", $start, $end);
        }

        $order = apply_filters('asgarosforum_filter_get_threads_order', "(SELECT MAX(id) FROM {$this->tables->posts} AS p WHERE p.parent_id = t.id) DESC");
        $results = $this->db->get_results($this->db->prepare("SELECT t.id, t.name, t.views, t.status FROM {$this->tables->topics} AS t WHERE t.parent_id = %d AND t.status LIKE %s ORDER BY {$order} {$limit};", $id, $type.'%'));
        $results = apply_filters('asgarosforum_filter_get_threads', $results);
        return $results;
    }

    function get_posts() {
        $start = $this->current_page * $this->options['posts_per_page'];
        $end = $this->options['posts_per_page'];

        $order = apply_filters('asgarosforum_filter_get_posts_order', 'p1.id ASC');
        $results = $this->db->get_results($this->db->prepare("SELECT p1.id, p1.text, p1.date, p1.date_edit, p1.author_id, (SELECT COUNT(p2.id) FROM {$this->tables->posts} AS p2 WHERE p2.author_id = p1.author_id) AS author_posts, uploads FROM {$this->tables->posts} AS p1 WHERE p1.parent_id = %d ORDER BY {$order} LIMIT %d, %d;", $this->current_topic, $start, $end));
        $results = apply_filters('asgarosforum_filter_get_posts', $results);
        return $results;
    }

    function is_first_post($post_id) {
        $first_post_id = $this->db->get_var("SELECT id FROM {$this->tables->posts} WHERE parent_id = {$this->current_topic} ORDER BY id ASC LIMIT 1;");

        if ($first_post_id == $post_id) {
            return true;
        } else {
            return false;
        }
    }

    function get_name($id, $location) {
        if (empty($this->cache['get_name'][$location][$id])) {
            $this->cache['get_name'][$location][$id] = $this->db->get_var($this->db->prepare("SELECT name FROM {$location} WHERE id = %d;", $id));
        }

        return $this->cache['get_name'][$location][$id];
    }

    function cut_string($string, $length = 33) {
        if (strlen($string) > $length) {
            return mb_substr($string, 0, $length, 'UTF-8') . ' &hellip;';
        }

        return $string;
    }

    function get_username($user_id, $widget = false) {
        if ($user_id == 0) {
            return __('Guest', 'asgaros-forum');
        } else {
            $user = get_userdata($user_id);

            if ($user) {
                $username = $user->display_name;

                if ($this->options['highlight_admin'] && !$widget) {
                    if (user_can($user_id, 'manage_options')) {
                        $username = '<span class="highlight-admin">'.$username.'</span>';
                    } else if (AsgarosForumPermissions::isModerator($user_id)) {
                        $username = '<span class="highlight-moderator">'.$username.'</span>';
                    }
                }

                return $username;
            } else {
                return __('Deleted user', 'asgaros-forum');
            }
        }
    }

    function get_lastpost($lastpost_data, $context = 'forum') {
        $lastpost = false;

        if ($lastpost_data) {
            $lastpost_link = $this->get_postlink($lastpost_data->parent_id, $lastpost_data->id);
            $lastpost .= ($context === 'forum') ? '<small><strong><a href="'.$lastpost_link.'">'.esc_html($this->cut_string(stripslashes($lastpost_data->name))).'</a></strong></small>' : '';
            $lastpost .= '<small><span class="dashicons-before dashicons-admin-users">'.__('By', 'asgaros-forum').'&nbsp;<strong>'.$this->get_username($lastpost_data->author_id).'</strong></span></small>';
            $lastpost .= '<small><span class="dashicons-before dashicons-calendar-alt"><a href="'.$lastpost_link.'">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($lastpost_data->date), current_time('timestamp'))).'</a></span></small>';
        } else if ($context === 'forum') {
            $lastpost = '<small>'.__('No topics yet!', 'asgaros-forum').'</small>';
        }

        return $lastpost;
    }

    function get_thread_starter($thread_id) {
        return $this->db->get_var($this->db->prepare("SELECT author_id FROM {$this->tables->posts} WHERE parent_id = %d ORDER BY id ASC LIMIT 1;", $thread_id));
    }

    function post_menu($post_id, $author_id, $counter) {
        $o = '';

        if ((!is_user_logged_in() && $this->options['allow_guest_postings'] && !$this->get_status('closed')) || (is_user_logged_in() && (!$this->get_status('closed') || AsgarosForumPermissions::isModerator('current')) && !AsgarosForumPermissions::isBanned('current'))) {
            $o .= '<a href="'.$this->getLink('post_add', $this->current_topic, array('quote' => $post_id)).'"><span class="dashicons-before dashicons-editor-quote"></span>'.__('Quote', 'asgaros-forum').'</a>';
        }

        if (is_user_logged_in()) {
            if (($counter > 1 || $this->current_page >= 1) && AsgarosForumPermissions::isModerator('current')) {
                $o .= '<a onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');" href="'.$this->getLink('topic', $this->current_topic, array('post' => $post_id, 'remove_post' => 1)).'"><span class="dashicons-before dashicons-trash"></span>'.__('Delete', 'asgaros-forum').'</a>';
            }

            if ((AsgarosForumPermissions::isModerator('current') || get_current_user_id() == $author_id) && !AsgarosForumPermissions::isBanned('current')) {
                $o .= '<a href="'.$this->getLink('post_edit', $post_id, array('part' => ($this->current_page + 1))).'"><span class="dashicons-before dashicons-edit"></span>'.__('Edit', 'asgaros-forum').'</a>';
            }
        }

        $o = (!empty($o)) ? $o = '<div class="post-menu">'.$o.'</div>' : $o;

        return $o;
    }

    function format_date($date) {
        return date_i18n($this->date_format, strtotime($date));
    }

    function current_time() {
        return current_time('Y-m-d H:i:s');
    }

    function get_post_author($post_id) {
        return $this->db->get_var($this->db->prepare("SELECT author_id FROM {$this->tables->posts} WHERE id = %d;", $post_id));
    }

    function forum_menu($location, $showallbuttons = true) {
        $menu = '';

        if ($location === 'forum' && ((is_user_logged_in() && !AsgarosForumPermissions::isBanned('current')) || (!is_user_logged_in() && $this->options['allow_guest_postings'])) && $this->get_forum_status()) {
            $menu .= '<a href="'.$this->getLink('topic_add', $this->current_forum).'"><span class="dashicons-before dashicons-plus-alt"></span><span>'.__('New Topic', 'asgaros-forum').'</span></a>';
        } else if ($location === 'thread' && ((is_user_logged_in() && (AsgarosForumPermissions::isModerator('current') || (!$this->get_status('closed') && !AsgarosForumPermissions::isBanned('current')))) || (!is_user_logged_in() && $this->options['allow_guest_postings'] && !$this->get_status('closed')))) {
            $menu .= '<a href="'.$this->getLink('post_add', $this->current_topic).'"><span class="dashicons-before dashicons-plus-alt"></span><span>'.__('Reply', 'asgaros-forum').'</span></a>';
        }

        if (is_user_logged_in() && $location === 'thread' && AsgarosForumPermissions::isModerator('current') && $showallbuttons) {
            $menu .= '<a href="'.$this->getLink('topic_move', $this->current_topic).'"><span class="dashicons-before dashicons-randomize"></span><span>'.__('Move', 'asgaros-forum').'</span></a>';
            $menu .= '<a href="'.$this->getLink('topic', $this->current_topic, array('delete_thread' => 1)).'&amp;delete_thread" onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');"><span class="dashicons-before dashicons-trash"></span><span>'.__('Delete', 'asgaros-forum').'</span></a>';

            if ($this->get_status('sticky')) {
                $menu .= '<a href="'.$this->getLink('topic', $this->current_topic, array('unsticky_topic' => 1)).'"><span class="dashicons-before dashicons-sticky"></span><span>'.__('Undo Sticky', 'asgaros-forum').'</span></a>';
            } else {
                $menu .= '<a href="'.$this->getLink('topic', $this->current_topic, array('sticky_topic' => 1)).'"><span class="dashicons-before dashicons-admin-post"></span><span>'.__('Sticky', 'asgaros-forum').'</span></a>';
            }

            if ($this->get_status('closed')) {
                $menu .= '<a href="'.$this->getLink('topic', $this->current_topic, array('open_topic' => 1)).'"><span class="dashicons-before dashicons-unlock"></span><span>'.__('Open', 'asgaros-forum').'</span></a>';
            } else {
                $menu .= '<a href="'.$this->getLink('topic', $this->current_topic, array('close_topic' => 1)).'"><span class="dashicons-before dashicons-lock"></span><span>'.__('Close', 'asgaros-forum').'</span></a>';
            }
        }

        return $menu;
    }

    function get_parent_id($id, $location, $value = 'parent_id') {
        return $this->db->get_var($this->db->prepare("SELECT {$value} FROM {$location} WHERE id = %d;", $id));
    }

    function breadcrumbs() {
        echo '<div id="top-container">';

        echo '<div id="breadcrumbs">';
        echo '<span class="dashicons-before dashicons-admin-home"></span>';
        echo '<a href="'.$this->getLink('home').'">'.__('Forum', 'asgaros-forum').'</a>';

        $trail = '';

        if ($this->parent_forum && $this->parent_forum > 0) {
            echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
            echo '<a href="'.$this->getLink('forum', $this->parent_forum).'">'.esc_html(stripslashes($this->get_name($this->parent_forum, $this->tables->forums))).'</a>';
        }

        if ($this->current_forum) {
            echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
            echo '<a href="'.$this->getLink('forum', $this->current_forum).'">'.esc_html(stripslashes($this->get_name($this->current_forum, $this->tables->forums))).'</a>';
        }

        if ($this->current_topic) {
            $name = stripslashes($this->get_name($this->current_topic, $this->tables->topics));
            echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
            echo '<a href="'.$this->getLink('topic', $this->current_topic).'" title="'.esc_html($name).'">'.esc_html($this->cut_string($name)).'</a>';
        }

        if ($this->current_view === 'addpost') {
            echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
            echo '<a href="#">'.__('Post Reply', 'asgaros-forum').'</a>';
        } else if ($this->current_view === 'editpost') {
            echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
            echo '<a href="#">'.__('Edit Post', 'asgaros-forum').'</a>';
        } else if ($this->current_view === 'addthread') {
            echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
            echo '<a href="#">'.__('New Topic', 'asgaros-forum').'</a>';
        } else if ($this->current_view === 'search') {
            echo '<span class="dashicons-before dashicons-arrow-right-alt2 separator"></span>';
            echo '<a href="#">'.__('Search', 'asgaros-forum').'</a>';
        }

        echo '</div>';

        if ($this->options['enable_search']) {
            echo '<div id="forum-search">';
            echo '<span class="dashicons-before dashicons-search"></span>';
            echo '<form method="get" action="'.$this->getLink('search').'">';
            echo '<input name="view" type="hidden" value="search">';
            echo '<input name="keywords" type="search" placeholder="'.__('Search ...', 'asgaros-forum').'">';
            echo '</form>';
            echo '</div>';
        }

        echo '<div class="clear"></div>';
        echo '</div>';
    }

    function pageing($location) {
        $out = '<div class="pages">'.__('Pages:', 'asgaros-forum');
        $num_pages = 0;
        $select_source = '';
        $select_url = '';
        $link = '';

        if ($location == $this->tables->posts) {
            $count = $this->db->get_var($this->db->prepare("SELECT COUNT(id) FROM {$location} WHERE parent_id = %d;", $this->current_topic));
            $num_pages = ceil($count / $this->options['posts_per_page']);
            $select_source = $this->current_topic;
            $select_url = 'topic';
        } else if ($location == $this->tables->topics) {
            $count = $this->db->get_var($this->db->prepare("SELECT COUNT(id) FROM {$location} WHERE parent_id = %d AND status LIKE %s;", $this->current_forum, "normal%"));
            $num_pages = ceil($count / $this->options['topics_per_page']);
            $select_source = $this->current_forum;
            $select_url = 'forum';
        } else if ($location === 'search') {
            $categories = $this->get_categories();
            $categoriesFilter = array();

            foreach ($categories as $category) {
                $categoriesFilter[] = $category->term_id;
            }

            $where = 'AND f.parent_id IN ('.implode(',', $categoriesFilter).')';
            $keywords = AsgarosForumSearch::$searchKeywords;
            $count = $this->db->get_col("SELECT t.id FROM {$this->tables->topics} AS t, {$this->tables->posts} AS p, {$this->tables->forums} AS f WHERE p.parent_id = t.id AND t.parent_id = f.id AND MATCH (p.text) AGAINST ('".$keywords."*' IN BOOLEAN MODE) {$where} GROUP BY p.parent_id;");
            $count = count($count);
            $num_pages = ceil($count / $this->options['topics_per_page']);
            $select_url = 'search';
        }

        if ($num_pages > 1) {
            if ($num_pages <= 6) {
                for ($i = 1; $i <= $num_pages; $i++) {
                    if ($i == ($this->current_page + 1)) {
                        $out .= ' <strong>'.$i.'</strong>';
                    } else {
                        if ($location === 'search') {
                            $link = $this->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => $i));
                        } else {
                            $link = $this->getLink($select_url, $select_source, array('part' => $i));
                        }

                        $out .= ' <a href="'.$link.'">'.$i.'</a>';
                    }
                }
            } else {
                if ($this->current_page >= 4) {
                    if ($location === 'search') {
                        $link = $this->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords));
                    } else {
                        $link = $this->getLink($select_url, $select_source);
                    }

                    $out .= ' <a href="'.$link.'">'.__('First', 'asgaros-forum').'</a> &laquo;';
                }

                for ($i = 3; $i > 0; $i--) {
                    if ((($this->current_page + 1) - $i) > 0) {
                        if ($location === 'search') {
                            $link = $this->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => (($this->current_page + 1) - $i)));
                        } else {
                            $link = $this->getLink($select_url, $select_source, array('part' => (($this->current_page + 1) - $i)));
                        }

                        $out .= ' <a href="'.$link.'">'.(($this->current_page + 1) - $i).'</a>';
                    }
                }

                $out .= ' <strong>'.($this->current_page + 1).'</strong>';

                for ($i = 1; $i <= 3; $i++) {
                    if ((($this->current_page + 1) + $i) <= $num_pages) {
                        if ($location === 'search') {
                            $link = $this->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => (($this->current_page + 1) + $i)));
                        } else {
                            $link = $this->getLink($select_url, $select_source, array('part' => (($this->current_page + 1) + $i)));
                        }

                        $out .= ' <a href="'.$link.'">'.(($this->current_page + 1) + $i).'</a>';
                    }
                }

                if ($num_pages - $this->current_page >= 5) {
                    if ($location === 'search') {
                        $link = $this->getLink($select_url, false, array('keywords' => AsgarosForumSearch::$searchKeywords, 'part' => $num_pages));
                    } else {
                        $link = $this->getLink($select_url, $select_source, array('part' => $num_pages));
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

    function delete_thread($thread_id, $admin_action = false) {
        if (AsgarosForumPermissions::isModerator('current')) {
            if ($thread_id) {
                // Delete uploads
                $posts = $this->db->get_col($this->db->prepare("SELECT id FROM {$this->tables->posts} WHERE parent_id = %d;", $thread_id));
                foreach ($posts as $post) {
                    AsgarosForumUploads::deletePostFiles($post);
                }

                $this->db->delete($this->tables->posts, array('parent_id' => $thread_id), array('%d'));
                $this->db->delete($this->tables->topics, array('id' => $thread_id), array('%d'));
                AsgarosForumNotifications::removeTopicSubscriptions($thread_id);

                if (!$admin_action) {
                    wp_redirect(html_entity_decode($this->getLink('forum', $this->current_forum)));
                    exit;
                }
            }
        }
    }

    function move_thread() {
        $newForumID = $_POST['newForumID'];

        if (AsgarosForumPermissions::isModerator('current') && $newForumID && $this->element_exists($newForumID, $this->tables->forums)) {
            $this->db->update($this->tables->topics, array('parent_id' => $newForumID), array('id' => $this->current_topic), array('%d'), array('%d'));
            wp_redirect(html_entity_decode($this->getLink('topic', $this->current_topic)));
            exit;
        }
    }

    function remove_post() {
        $post_id = (isset($_GET['post']) && is_numeric($_GET['post'])) ? absint($_GET['post']) : 0;

        if (AsgarosForumPermissions::isModerator('current') && $this->element_exists($post_id, $this->tables->posts)) {
            $this->db->delete($this->tables->posts, array('id' => $post_id), array('%d'));
            AsgarosForumUploads::deletePostFiles($post_id);
        }
    }

    // TODO: Optimize sql-query same as widget-query. (http://stackoverflow.com/a/28090544/4919483)
    function get_lastpost_in_thread($id) {
        if (empty($this->cache['get_lastpost_in_thread'][$id])) {
            $this->cache['get_lastpost_in_thread'][$id] = $this->db->get_row($this->db->prepare("SELECT p.id, p.date, p.author_id, p.parent_id FROM {$this->tables->posts} AS p INNER JOIN {$this->tables->topics} AS t ON p.parent_id = t.id WHERE p.parent_id = %d ORDER BY p.id DESC LIMIT 1;", $id));
        }

        return $this->cache['get_lastpost_in_thread'][$id];
    }

    // TODO: Optimize sql-query same as widget-query. (http://stackoverflow.com/a/28090544/4919483)
    function get_lastpost_in_forum($id) {
        if (empty($this->cache['get_lastpost_in_forum'][$id])) {
            return $this->db->get_row($this->db->prepare("SELECT p.id, p.date, p.parent_id, p.author_id, t.name FROM {$this->tables->posts} AS p INNER JOIN {$this->tables->topics} AS t ON p.parent_id = t.id INNER JOIN {$this->tables->forums} AS f ON t.parent_id = f.id WHERE f.id = %d OR f.parent_forum = %d ORDER BY p.id DESC LIMIT 1;", $id, $id));
        }

        return $this->cache['get_lastpost_in_forum'][$id];
    }

    function change_status($property) {
        if (AsgarosForumPermissions::isModerator('current')) {
            $new_status = '';

            if ($property == 'sticky') {
                $new_status .= ($this->get_status('sticky')) ? 'normal_' : 'sticky_';
                $new_status .= ($this->get_status('closed')) ? 'closed' : 'open';
            } else if ($property == 'closed') {
                $new_status .= ($this->get_status('sticky')) ? 'sticky_' : 'normal_';
                $new_status .= ($this->get_status('closed')) ? 'open' : 'closed';
            }

            $this->db->update($this->tables->topics, array('status' => $new_status), array('id' => $this->current_topic), array('%s'), array('%d'));

            // Update cache
            $this->cache['get_status'][$this->current_topic] = $new_status;
        }
    }

    function get_status($property) {
        if (empty($this->cache['get_status'][$this->current_topic])) {
            $this->cache['get_status'][$this->current_topic] = $this->db->get_var($this->db->prepare("SELECT status FROM {$this->tables->topics} WHERE id = %d;", $this->current_topic));
        }

        $status = $this->cache['get_status'][$this->current_topic];

        if ($property == 'sticky' && ($status == 'sticky_open' || $status == 'sticky_closed')) {
            return true;
        } else if ($property == 'closed' && ($status == 'normal_closed' || $status == 'sticky_closed')) {
            return true;
        } else {
            return false;
        }
    }

    // Returns TRUE if the forum is opened or the user has at least moderator rights.
    function get_forum_status() {
        if (!AsgarosForumPermissions::isModerator('current')) {
            $closed = intval($this->db->get_var($this->db->prepare("SELECT closed FROM {$this->tables->forums} WHERE id = %d;", $this->current_forum)));

            if ($closed === 1) {
                return false;
            }
        }

        return true;
    }

    // Builds and returns a requested link.
    public function getLink($type, $elementID = false, $additionalParameters = false, $appendix = '') {
        // Only generate a link when that type is available.
        if (isset($this->links[$type])) {
            // Set an ID if available, otherwise initialize the base-link.
            $link = ($elementID) ? add_query_arg('id', $elementID, $this->links[$type]) : $this->links[$type];

            // Set additional parameters if available, otherwise let the link unchanged.
            $link = ($additionalParameters) ? add_query_arg($additionalParameters, $link) : $link;

            // Return escaped URL with optional appendix at the end if set.
            return esc_url($link.$appendix);
        } else {
            return false;
        }
    }

    public function getSearchResults() {
        if (!empty($_GET['keywords'])) {
            AsgarosForumSearch::$searchKeywords = esc_sql(trim($_GET['keywords']));
            $keywords = AsgarosForumSearch::$searchKeywords;

            if (!empty($keywords)) {
                $categories = $this->get_categories();
                $categoriesFilter = array();

                foreach ($categories as $category) {
                    $categoriesFilter[] = $category->term_id;
                }

                $where = 'AND f.parent_id IN ('.implode(',', $categoriesFilter).')';

                $start = $this->current_page * $this->options['topics_per_page'];
                $end = $this->options['topics_per_page'];
                $limit = $this->db->prepare("LIMIT %d, %d", $start, $end);

                $query = "SELECT t.*, MATCH (p.text) AGAINST ('".$keywords."*' IN BOOLEAN MODE) AS score FROM {$this->tables->topics} AS t, {$this->tables->posts} AS p, {$this->tables->forums} AS f WHERE p.parent_id = t.id AND t.parent_id = f.id AND MATCH (p.text) AGAINST ('".$keywords."*' IN BOOLEAN MODE) {$where} GROUP BY p.parent_id ORDER BY score DESC, p.id DESC {$limit};";

                $results = $this->db->get_results($query);

                if (!empty($results)) {
                    return $results;
                }
            }
        }

        return false;
    }
}

?>
