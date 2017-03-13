<?php

if (!defined('ABSPATH')) exit;

class AsgarosForum {
    var $version = '1.4.4';
    var $executePlugin = false;
    var $db = null;
    var $tables = null;
    var $directory = '';
    var $date_format = '';
    var $error = false;
    var $info = false;
    var $current_title = false;
    var $current_description = false;
    var $current_category = false;
    var $current_forum = false;
    var $current_forum_name = false;
    var $current_topic = false;
    var $current_topic_name = false;
    var $current_post = false;
    var $current_view = false;
    var $current_page = 0;
    var $parent_forum = false;
    var $parent_forum_name = false;
    var $category_access_level = false;
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
        'uploads_maximum_number'    => 5,
        'uploads_maximum_size'      => 5,
        'uploads_show_thumbnails'   => true,
        'admin_subscriptions'       => false,
        'allow_subscriptions'       => true,
        'allow_signatures'          => false,
        'enable_search'             => true,
        'show_who_is_online'        => true,
        'show_statistics'           => true,
        'enable_breadcrumbs'        => true,
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
        'editor_height' => 250,
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
        add_action('clear_auth_cookie', array('AsgarosForumOnline', 'deleteUserTimeStamp'));
        add_filter('wp_title', array($this, 'change_wp_title'), 10, 3);
        add_filter('document_title_parts', array($this, 'change_document_title_parts'));
        add_filter('teeny_mce_buttons', array($this, 'add_mce_buttons'), 9999, 2);
        add_filter('mce_buttons', array($this, 'add_mce_buttons'), 9999, 2);
        add_filter('disable_captions', array($this, 'disable_captions'));

        new AsgarosForumRewrite($this);
    }

    function initialize() {
        new AsgarosForumTaxonomies();
        new AsgarosForumPermissions();
        new AsgarosForumUploads($this);
        new AsgarosForumUnread($this);
        new AsgarosForumThemeManager($this);
        new AsgarosForumEditor($this);
        new AsgarosForumShortcodes($this);
        new AsgarosForumStatistics($this);
        new AsgarosForumOnline($this);
        new AsgarosForumSearch($this);
    }

    function initialize_widgets() {
        new AsgarosForumWidgets($this);
    }

    function prepare() {
        global $post;

        if (is_a($post, 'WP_Post') && AsgarosForumShortcodes::checkForShortcode($post)) {
            $this->executePlugin = true;
            $this->options['location'] = $post->ID;
        }

        // Set all base links.
        if ($this->executePlugin || get_post($this->options['location'])) {
            AsgarosForumRewrite::setLinks();
        }

        if (!$this->executePlugin) {
            return;
        }

        // Update online status.
        AsgarosForumOnline::updateOnlineStatus();

        if (isset($_GET['view'])) {
            $this->current_view = esc_html($_GET['view']);
        }

        if (isset($_GET['part']) && absint($_GET['part']) > 0) {
            $this->current_page = (absint($_GET['part']) - 1);
        }

        $elementID = (isset($_GET['id'])) ? absint($_GET['id']) : false;

        switch ($this->current_view) {
            case 'forum':
            case 'addtopic':
                $this->setParents($elementID, 'forum');
                break;
            case 'movetopic':
            case 'topic':
            case 'thread':
            case 'addpost':
                // Fallback for old view-name.
                $this->current_view = ($this->current_view == 'topic') ? 'thread' : $this->current_view;
                $this->setParents($elementID, 'topic');
                break;
            case 'editpost':
                $this->setParents($elementID, 'post');
                break;
            case 'markallread':
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

        AsgarosForumShortcodes::handleAttributes();

        // Check
        $this->check_access();

        $this->setCurrentTitle();

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
        } else if (isset($_GET['move_topic'])) {
            $this->moveTopic();
        } else if (isset($_GET['delete_topic'])) {
            $this->delete_topic($this->current_topic);
        } else if (isset($_GET['remove_post'])) {
            $this->remove_post();
        } else if (isset($_GET['sticky_topic'])) {
            $this->change_status('sticky');
        } else if (isset($_GET['unsticky_topic'])) {
            $this->change_status('normal');
        } else if (isset($_GET['open_topic'])) {
            $this->change_status('open');
        } else if (isset($_GET['close_topic'])) {
            $this->change_status('closed');
        } else if (isset($_GET['subscribe_topic'])) {
            AsgarosForumNotifications::subscribeTopic();
        } else if (isset($_GET['unsubscribe_topic'])) {
            AsgarosForumNotifications::unsubscribeTopic();
        } else if (isset($_GET['subscribe_forum'])) {
            AsgarosForumNotifications::subscribeForum();
        } else if (isset($_GET['unsubscribe_forum'])) {
            AsgarosForumNotifications::unsubscribeForum();
        }

        // Mark visited topic as read.
        if ($this->current_view === 'thread' && $this->current_topic) {
            AsgarosForumUnread::markTopicRead();
        }
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

        wp_enqueue_script('asgarosforum-js', $this->directory.'js/script.js', array('jquery'), $this->version);
        wp_enqueue_style('dashicons');
    }

    function change_wp_title($title, $sep, $seplocation) {
        return $this->get_title($title);
    }

    function change_document_title_parts($title) {
        $title['title'] = $this->get_title($title['title']);
        return $title;
    }

    function setCurrentTitle() {
        if (!$this->error && $this->current_view) {
            if ($this->current_view == 'forum' && $this->current_forum) {
                $this->current_title = esc_html(stripslashes($this->current_forum_name));
            } else if ($this->current_view == 'thread' && $this->current_topic) {
                $this->current_title = esc_html(stripslashes($this->current_topic_name));
            } else if ($this->current_view == 'editpost') {
                $this->current_title = __('Edit Post', 'asgaros-forum');
            } else if ($this->current_view == 'addpost') {
                $this->current_title = __('Post Reply', 'asgaros-forum').': '.esc_html(stripslashes($this->current_topic_name));
            } else if ($this->current_view == 'addtopic') {
                $this->current_title = __('New Topic', 'asgaros-forum');
            } else if ($this->current_view == 'movetopic') {
                $this->current_title = __('Move Topic', 'asgaros-forum');
            } else if ($this->current_view == 'search') {
                $this->current_title = __('Search', 'asgaros-forum');
            }
        }
    }

    function get_title($title) {
        if ($this->executePlugin && $this->current_title) {
            $title = $this->current_title.' - '.$title;
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
            if ($this->current_view === 'post') {
                $this->showSinglePost();
            } else {
                $this->showHeader();

                if (!empty($this->info)) {
                    echo '<div class="info">'.$this->info.'</div>';
                }

                $this->showLoginMessage();
                $this->showMainTitle();

                switch ($this->current_view) {
                    case 'search':
                        include('views/search.php');
                        break;
                    case 'movetopic':
                        $this->showMoveTopic();
                        break;
                    case 'forum':
                        $this->showforum();
                        break;
                    case 'thread':
                        $this->showTopic();
                        break;
                    case 'addtopic':
                    case 'addpost':
                    case 'editpost':
                        AsgarosForumEditor::showEditor();
                        break;
                    default:
                        $this->overview();
                        break;
                }
            }
        }

        do_action('asgarosforum_'.$this->current_view.'_custom_content_bottom');

        echo '</div>';
        return ob_get_clean();
    }

    function showMainTitle() {
        $mainTitle = ($this->current_title) ? $this->current_title : __('Forum', 'asgaros-forum');

        echo '<h1 class="main-title">'.$mainTitle.'</h1>';
    }

    function overview() {
        $categories = $this->get_categories();

        require('views/overview.php');
    }

    function showSinglePost() {
        global $wp_embed;
        $counter = 0;
        $avatars_available = get_option('show_avatars');
        $topicStarter = $this->get_topic_starter($this->current_topic);
        $post = $this->getSinglePost();

        echo '<div class="title-element"></div>';
        echo '<div class="content-element">';
        require('views/post-element.php');
        echo '</div>';
    }

    function showforum() {
        $threads = $this->get_topics($this->current_forum);
        $sticky_topics = $this->get_topics($this->current_forum, 'sticky');
        $counter_normal = count($threads);
        $counter_total = $counter_normal + count($sticky_topics);

        require('views/forum.php');
    }

    function showTopic() {
        global $wp_embed;
        $posts = $this->get_posts();

        if ($posts) {
            $this->incrementTopicViews();

            $meClosed = ($this->get_status('closed')) ? '<span class="dashicons-before dashicons-lock"></span>' : '';

            require('views/topic.php');
        } else {
            echo '<div class="notice">'.__('Sorry, but there are no posts.', 'asgaros-forum').'</div>';
        }
    }

    public function incrementTopicViews() {
        $this->db->query($this->db->prepare("UPDATE {$this->tables->topics} SET views = views + 1 WHERE id = %d", $this->current_topic));
    }

    function showLoginMessage() {
        if (!is_user_logged_in() && !$this->options['allow_guest_postings']) {
            $loginMessage = '<div class="info">'.__('You need to login in order to create posts and topics.', 'asgaros-forum').'&nbsp;<a href="'.esc_url(wp_login_url($this->getLink('current'))).'">&raquo; '.__('Login', 'asgaros-forum').'</a></div>';
            $loginMessage = apply_filters('asgarosforum_filter_login_message', $loginMessage);
            echo $loginMessage;
        }
    }

    function showMoveTopic() {
        if (AsgarosForumPermissions::isModerator('current')) {
            $strOUT = '<form method="post" action="'.$this->getLink('topic_move', $this->current_topic, array('move_topic' => 1)).'">';
            $strOUT .= '<div class="title-element">'.sprintf(__('Move "<strong>%s</strong>" to new forum:', 'asgaros-forum'), esc_html(stripslashes($this->current_topic_name))).'</div>';
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
        $include = array();
        $metaQueryFilter = array();

        if ($enableFiltering) {
            $filter = apply_filters('asgarosforum_filter_get_categories', $filter);
            $metaQueryFilter = $this->getCategoriesFilter();

            // Set include filter when extended shortcode is used.
            if (AsgarosForumShortcodes::$includeCategories) {
                $include = AsgarosForumShortcodes::$includeCategories;
            }
        }

        $categories = get_terms('asgarosforum-category', array('hide_empty' => false, 'exclude' => $filter, 'include' => $include, 'meta_query' => $metaQueryFilter));

        foreach ($categories as $category) {
            $category->order = get_term_meta($category->term_id, 'order', true);
        }

        usort($categories, array($this, 'categories_compare'));

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

    function get_forums($id = false, $parent_forum = 0, $output_type = OBJECT) {
        if ($id) {
            return $this->db->get_results($this->db->prepare("SELECT f.id, f.parent_id, f.name, f.description, f.closed, f.sort, f.parent_forum, (SELECT COUNT(ct_t.id) FROM {$this->tables->topics} AS ct_t, {$this->tables->forums} AS ct_f WHERE ct_t.parent_id = ct_f.id AND (ct_f.id = f.id OR ct_f.parent_forum = f.id)) AS count_topics, (SELECT COUNT(cp_p.id) FROM {$this->tables->posts} AS cp_p, {$this->tables->topics} AS cp_t, {$this->tables->forums} AS cp_f WHERE cp_p.parent_id = cp_t.id AND cp_t.parent_id = cp_f.id AND (cp_f.id = f.id OR cp_f.parent_forum = f.id)) AS count_posts, (SELECT COUNT(csf_f.id) FROM {$this->tables->forums} AS csf_f WHERE csf_f.parent_forum = f.id) AS count_subforums, f.slug FROM {$this->tables->forums} AS f WHERE f.parent_id = %d AND f.parent_forum = %d GROUP BY f.id ORDER BY f.sort ASC;", $id, $parent_forum), $output_type);
        } else {
            // Load all forums.
            return $this->db->get_results("SELECT id, name FROM {$this->tables->forums} ORDER BY sort ASC;", $output_type);
        }
    }

    function get_topics($id, $type = 'normal') {
        $limit = "";

        if ($type == 'normal') {
            $start = $this->current_page * $this->options['topics_per_page'];
            $end = $this->options['topics_per_page'];
            $limit = $this->db->prepare("LIMIT %d, %d", $start, $end);
        }

        $order = apply_filters('asgarosforum_filter_get_threads_order', "(SELECT MAX(id) FROM {$this->tables->posts} AS p WHERE p.parent_id = t.id) DESC");
        $results = $this->db->get_results($this->db->prepare("SELECT t.id, t.name, t.views, t.status, (SELECT author_id FROM {$this->tables->posts} WHERE parent_id = t.id ORDER BY id ASC LIMIT 1) AS author_id, (SELECT (COUNT(id) - 1) FROM {$this->tables->posts} WHERE parent_id = t.id) AS answers FROM {$this->tables->topics} AS t WHERE t.parent_id = %d AND t.status LIKE %s ORDER BY {$order} {$limit};", $id, $type.'%'));
        $results = apply_filters('asgarosforum_filter_get_threads', $results);
        return $results;
    }

    function get_posts() {
        $start = $this->current_page * $this->options['posts_per_page'];
        $end = $this->options['posts_per_page'];

        $order = apply_filters('asgarosforum_filter_get_posts_order', 'p1.id ASC');
        $results = $this->db->get_results($this->db->prepare("SELECT p1.id, p1.text, p1.date, p1.date_edit, p1.author_id, (SELECT COUNT(p2.id) FROM {$this->tables->posts} AS p2 WHERE p2.author_id = p1.author_id) AS author_posts, p1.uploads FROM {$this->tables->posts} AS p1 WHERE p1.parent_id = %d ORDER BY {$order} LIMIT %d, %d;", $this->current_topic, $start, $end));
        $results = apply_filters('asgarosforum_filter_get_posts', $results);
        return $results;
    }

    function getSinglePost() {
        $result = $this->db->get_row($this->db->prepare("SELECT p1.id, p1.text, p1.date, p1.date_edit, p1.author_id, (SELECT COUNT(p2.id) FROM {$this->tables->posts} AS p2 WHERE p2.author_id = p1.author_id) AS author_posts, p1.uploads FROM {$this->tables->posts} AS p1 WHERE p1.id = %d;", $this->current_post));
        return $result;
    }

    function is_first_post($post_id) {
        $first_post_id = $this->db->get_var("SELECT id FROM {$this->tables->posts} WHERE parent_id = {$this->current_topic} ORDER BY id ASC LIMIT 1;");

        if ($first_post_id == $post_id) {
            return true;
        } else {
            return false;
        }
    }

    function cut_string($string, $length = 33) {
        if (strlen($string) > $length) {
            return mb_substr($string, 0, $length, 'UTF-8') . ' &hellip;';
        }

        return $string;
    }

    function getUsername($user_id) {
        if ($user_id) {
            $user = get_userdata($user_id);

            if ($user) {
                return $this->highlightUsername($user);
            } else {
                return __('Deleted user', 'asgaros-forum');
            }
        } else {
            return __('Guest', 'asgaros-forum');
        }
    }

    function highlightUsername($user) {
        if ($this->options['highlight_admin']) {
            if (is_super_admin($user->ID) || user_can($user->ID, 'administrator')) {
                return '<span class="highlight-admin">'.$user->display_name.'</span>';
            } else if (AsgarosForumPermissions::isModerator($user->ID)) {
                return '<span class="highlight-moderator">'.$user->display_name.'</span>';
            }
        }

        return $user->display_name;
    }

    function get_lastpost($lastpost_data, $context = 'forum') {
        $lastpost = false;

        if ($lastpost_data) {
            $lastpost_link = $this->getLink('topic', $lastpost_data->parent_id, array('part' => ceil($lastpost_data->number_of_posts/$this->options['posts_per_page'])), '#postid-'.$lastpost_data->id);
            $lastpost .= ($context === 'forum') ? '<small><strong><a href="'.$lastpost_link.'">'.esc_html($this->cut_string(stripslashes($lastpost_data->name))).'</a></strong></small>' : '';
            $lastpost .= '<small><span class="dashicons-before dashicons-admin-users">'.__('By', 'asgaros-forum').'&nbsp;<strong>'.$this->getUsername($lastpost_data->author_id).'</strong></span></small>';
            $lastpost .= '<small><span class="dashicons-before dashicons-calendar-alt"><a href="'.$lastpost_link.'">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($lastpost_data->date), current_time('timestamp'))).'</a></span></small>';
        } else if ($context === 'forum') {
            $lastpost = '<small>'.__('No topics yet!', 'asgaros-forum').'</small>';
        }

        return $lastpost;
    }

    function get_topic_starter($thread_id) {
        return $this->db->get_var($this->db->prepare("SELECT author_id FROM {$this->tables->posts} WHERE parent_id = %d ORDER BY id ASC LIMIT 1;", $thread_id));
    }

    function post_menu($post_id, $author_id, $counter) {
        $o = '';

        if (is_user_logged_in()) {
            if (($counter > 1 || $this->current_page >= 1) && AsgarosForumPermissions::isModerator('current')) {
                $o .= '<a onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');" href="'.$this->getLink('topic', $this->current_topic, array('post' => $post_id, 'remove_post' => 1)).'"><span class="dashicons-before dashicons-trash"></span>'.__('Delete', 'asgaros-forum').'</a>';
            }

            if ((AsgarosForumPermissions::isModerator('current') || get_current_user_id() == $author_id) && !AsgarosForumPermissions::isBanned('current')) {
                $o .= '<a href="'.$this->getLink('post_edit', $post_id, array('part' => ($this->current_page + 1))).'"><span class="dashicons-before dashicons-edit"></span>'.__('Edit', 'asgaros-forum').'</a>';
            }
        }

        if ((!is_user_logged_in() && $this->options['allow_guest_postings'] && !$this->get_status('closed')) || (is_user_logged_in() && (!$this->get_status('closed') || AsgarosForumPermissions::isModerator('current')) && !AsgarosForumPermissions::isBanned('current'))) {
            $o .= '<a class="forum-editor-quote-button" data-value-id="'.$post_id.'" href="'.$this->getLink('post_add', $this->current_topic, array('quote' => $post_id)).'"><span class="dashicons-before dashicons-editor-quote"></span>'.__('Quote', 'asgaros-forum').'</a>';
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

        if ($location === 'forum' && ((is_user_logged_in() && !AsgarosForumPermissions::isBanned('current')) || (!is_user_logged_in() && $this->options['allow_guest_postings'])) && $this->forumIsOpen()) {
            $menu .= '<a class="forum-editor-button" href="'.$this->getLink('topic_add', $this->current_forum).'"><span class="dashicons-before dashicons-plus-alt"></span><span>'.__('New Topic', 'asgaros-forum').'</span></a>';
        } else if ($location === 'topic' && ((is_user_logged_in() && (AsgarosForumPermissions::isModerator('current') || (!$this->get_status('closed') && !AsgarosForumPermissions::isBanned('current')))) || (!is_user_logged_in() && $this->options['allow_guest_postings'] && !$this->get_status('closed')))) {
            $menu .= '<a class="forum-editor-button" href="'.$this->getLink('post_add', $this->current_topic).'"><span class="dashicons-before dashicons-plus-alt"></span><span>'.__('Reply', 'asgaros-forum').'</span></a>';
        }

        if (is_user_logged_in() && $location === 'topic' && AsgarosForumPermissions::isModerator('current') && $showallbuttons) {
            $menu .= '<a href="'.$this->getLink('topic_move', $this->current_topic).'"><span class="dashicons-before dashicons-randomize"></span><span>'.__('Move', 'asgaros-forum').'</span></a>';
            $menu .= '<a href="'.$this->getLink('topic', $this->current_topic, array('delete_topic' => 1)).'" onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');"><span class="dashicons-before dashicons-trash"></span><span>'.__('Delete', 'asgaros-forum').'</span></a>';

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

        $menu = (!empty($menu)) ? '<div class="forum-menu">'.$menu.'</div>' : $menu;
        return $menu;
    }

    function showHeader() {
        if ($this->options['enable_breadcrumbs'] || $this->options['enable_search']) {
            echo '<div id="top-container">';
            AsgarosForumBreadCrumbs::showBreadCrumbs();
            AsgarosForumSearch::showSearchInput();
            echo '<div class="clear"></div>';
            echo '</div>';
        }
    }

    function delete_topic($topicID, $admin_action = false) {
        if (AsgarosForumPermissions::isModerator('current')) {
            if ($topicID) {
                // Delete uploads
                $posts = $this->db->get_col($this->db->prepare("SELECT id FROM {$this->tables->posts} WHERE parent_id = %d;", $topicID));
                foreach ($posts as $post) {
                    AsgarosForumUploads::deletePostFiles($post);
                }

                $this->db->delete($this->tables->posts, array('parent_id' => $topicID), array('%d'));
                $this->db->delete($this->tables->topics, array('id' => $topicID), array('%d'));
                AsgarosForumNotifications::removeTopicSubscriptions($topicID);

                if (!$admin_action) {
                    wp_redirect(html_entity_decode($this->getLink('forum', $this->current_forum)));
                    exit;
                }
            }
        }
    }

    function moveTopic() {
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
    function get_lastpost_in_topic($id) {
        if (empty($this->cache['get_lastpost_in_topic'][$id])) {
            $this->cache['get_lastpost_in_topic'][$id] = $this->db->get_row($this->db->prepare("SELECT (SELECT COUNT(p_inner.id) FROM {$this->tables->posts} AS p_inner WHERE p_inner.parent_id = p.parent_id) AS number_of_posts, p.id, p.date, p.author_id, p.parent_id FROM {$this->tables->posts} AS p INNER JOIN {$this->tables->topics} AS t ON p.parent_id = t.id WHERE p.parent_id = %d ORDER BY p.id DESC LIMIT 1;", $id));
        }

        return $this->cache['get_lastpost_in_topic'][$id];
    }

    // TODO: Optimize sql-query same as widget-query. (http://stackoverflow.com/a/28090544/4919483)
    function get_lastpost_in_forum($id) {
        if (empty($this->cache['get_lastpost_in_forum'][$id])) {
            return $this->db->get_row($this->db->prepare("SELECT (SELECT COUNT(p_inner.id) FROM {$this->tables->posts} AS p_inner WHERE p_inner.parent_id = p.parent_id) AS number_of_posts, p.id, p.date, p.parent_id, p.author_id, t.name FROM {$this->tables->posts} AS p INNER JOIN {$this->tables->topics} AS t ON p.parent_id = t.id INNER JOIN {$this->tables->forums} AS f ON t.parent_id = f.id WHERE f.id = %d OR f.parent_forum = %d ORDER BY p.id DESC LIMIT 1;", $id, $id));
        }

        return $this->cache['get_lastpost_in_forum'][$id];
    }

    function change_status($property) {
        if (AsgarosForumPermissions::isModerator('current')) {
            $new_status = '';

            if ($property == 'sticky') {
                $new_status .= 'sticky_';
                $new_status .= ($this->get_status('closed')) ? 'closed' : 'open';
            } else if ($property == 'normal') {
                $new_status .= 'normal_';
                $new_status .= ($this->get_status('closed')) ? 'closed' : 'open';
            } else if ($property == 'closed') {
                $new_status .= ($this->get_status('sticky')) ? 'sticky_' : 'normal_';
                $new_status .= 'closed';
            } else if ($property == 'open') {
                $new_status .= ($this->get_status('sticky')) ? 'sticky_' : 'normal_';
                $new_status .= 'open';
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
    function forumIsOpen() {
        if (!AsgarosForumPermissions::isModerator('current')) {
            $closed = intval($this->db->get_var($this->db->prepare("SELECT closed FROM {$this->tables->forums} WHERE id = %d;", $this->current_forum)));

            if ($closed === 1) {
                return false;
            }
        }

        return true;
    }

    // Builds and returns a requested link.
    public function getLink($type, $elementID = false, $additionalParameters = false, $appendix = '', $escapeURL = true) {
        return AsgarosForumRewrite::getLink($type, $elementID, $additionalParameters, $appendix, $escapeURL);
    }

    // Checks if an element exists and sets all parent IDs based on the given id and its content type.
    public function setParents($id, $contentType) {
        // Set possible error messages.
        $error = array();
        $error['post']  = __('Sorry, this post does not exist.', 'asgaros-forum');
        $error['topic'] = __('Sorry, this topic does not exist.', 'asgaros-forum');
        $error['forum'] = __('Sorry, this forum does not exist.', 'asgaros-forum');

        if ($id) {
            $query = '';
            $results = false;

            // Build the query.
            switch ($contentType) {
                case 'post':
                    $query = "SELECT f.parent_id AS current_category, f.id AS current_forum, f.name AS current_forum_name, f.parent_forum AS parent_forum, pf.name AS parent_forum_name, t.id AS current_topic, t.name AS current_topic_name, p.id AS current_post, p.text AS current_description FROM {$this->tables->forums} AS f LEFT JOIN {$this->tables->forums} AS pf ON (pf.id = f.parent_forum) LEFT JOIN {$this->tables->topics} AS t ON (f.id = t.parent_id) LEFT JOIN {$this->tables->posts} AS p ON (t.id = p.parent_id) WHERE p.id = {$id};";
                    break;
                case 'topic':
                    $query = "SELECT f.parent_id AS current_category, f.id AS current_forum, f.name AS current_forum_name, f.parent_forum AS parent_forum, pf.name AS parent_forum_name, t.id AS current_topic, t.name AS current_topic_name, (SELECT td.text FROM {$this->tables->posts} AS td WHERE td.parent_id = t.id ORDER BY td.id ASC LIMIT 1) AS current_description FROM {$this->tables->forums} AS f LEFT JOIN {$this->tables->forums} AS pf ON (pf.id = f.parent_forum) LEFT JOIN {$this->tables->topics} AS t ON (f.id = t.parent_id) WHERE t.id = {$id};";
                    break;
                case 'forum':
                    $query = "SELECT f.parent_id AS current_category, f.id AS current_forum, f.name AS current_forum_name, f.parent_forum AS parent_forum, pf.name AS parent_forum_name, f.description AS current_description FROM {$this->tables->forums} AS f LEFT JOIN {$this->tables->forums} AS pf ON (pf.id = f.parent_forum) WHERE f.id = {$id};";
                    break;
            }

            $results = $this->db->get_row($query);

            // When the element exists, set parents and exit function.
            if ($results) {
                $this->current_description  = ($contentType === 'post' || $contentType === 'topic' || $contentType === 'forum') ? $this->cut_string(str_replace(array("\r", "\n"), '', esc_html(strip_tags($results->current_description))), 155) : false;
                $this->current_category     = ($contentType === 'post' || $contentType === 'topic' || $contentType === 'forum') ? $results->current_category : false;
                $this->parent_forum         = ($contentType === 'post' || $contentType === 'topic' || $contentType === 'forum') ? $results->parent_forum : false;
                $this->parent_forum_name    = ($contentType === 'post' || $contentType === 'topic' || $contentType === 'forum') ? $results->parent_forum_name : false;
                $this->current_forum        = ($contentType === 'post' || $contentType === 'topic' || $contentType === 'forum') ? $results->current_forum : false;
                $this->current_forum_name   = ($contentType === 'post' || $contentType === 'topic' || $contentType === 'forum') ? $results->current_forum_name : false;
                $this->current_topic        = ($contentType === 'post' || $contentType === 'topic') ? $results->current_topic : false;
                $this->current_topic_name   = ($contentType === 'post' || $contentType === 'topic') ? $results->current_topic_name : false;
                $this->current_post         = ($contentType === 'post') ? $results->current_post : false;
                return;
            }
        }

        // Assign error message, because when this location is reached, no parents has been set.
        $this->error = $error[$contentType];
    }
}
