<?php

if (!defined('ABSPATH')) exit;

class AsgarosForum {
    var $directory = '';
    var $date_format = '';
    var $error = false;
    var $info = false;
    var $url_home = '';
    var $url_forum = '';
    var $url_thread = '';
    var $url_editor_thread = '';
    var $url_editor_post = '';
    var $url_editor_edit = '';
    var $url_markallread = '';
    var $url_movethread = '';
    var $table_forums = '';
    var $table_threads = '';
    var $table_posts = '';
    var $current_category = false;
    var $current_forum = false;
    var $current_thread = false;
    var $current_post = false;
    var $current_view = false;
    var $current_url = false;
    var $current_page = 0;
    var $parent_forum = false;
    var $options = array();
    var $options_default = array(
        'posts_per_page'            => 10,
        'threads_per_page'          => 20,
        'minimalistic_editor'       => true,
        'allowed_filetypes'         => 'jpg,jpeg,gif,png,bmp,pdf',
        'allow_file_uploads'        => false,
        'admin_subscriptions'       => false,
        'allow_subscriptions'       => true,
        'highlight_admin'           => true,
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

    function __construct($directory) {
        $this->directory = $directory;
        $this->options = array_merge($this->options_default, get_option('asgarosforum_options', array()));
        $this->options_editor['teeny'] = $this->options['minimalistic_editor'];
        $this->date_format = get_option('date_format').', '.get_option('time_format');
        $this->table_forums = AsgarosForumDatabase::getTable('forums');
        $this->table_threads = AsgarosForumDatabase::getTable('threads');
        $this->table_posts = AsgarosForumDatabase::getTable('posts');

        add_action('init', array($this, 'register_category_taxonomy'));
        add_action('wp', array($this, 'prepare'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_front_scripts'));
        add_filter('wp_title', array($this, 'change_wp_title'), 10, 3);
        add_filter('document_title_parts', array($this, 'change_document_title_parts'));
        add_filter('teeny_mce_buttons', array($this, 'add_mce_buttons'), 9999, 2);
        add_filter('mce_buttons', array($this, 'add_mce_buttons'), 9999, 2);
        add_filter('disable_captions', array($this, 'disable_captions'));
        add_shortcode('forum', array($this, 'forum'));
    }

    function execute_plugin() {
        global $post;

        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'forum')) {
            return true;
        } else {
            return false;
        }
    }

    static function register_category_taxonomy() {
        register_taxonomy(
            'asgarosforum-category',
            null,
            array(
                'labels' => array(
                    'name'          => __('Categories', 'asgaros-forum'),
                    'singular_name' => __('Category', 'asgaros-forum'),
                    'edit_item'     => __('Edit Category', 'asgaros-forum'),
                    'update_item'   => __('Update Category', 'asgaros-forum'),
                    'add_new_item'  => __('Add new Category', 'asgaros-forum'),
                    'search_items'  => __('Search Categories', 'asgaros-forum'),
                    'not_found'     => __('No Categories found.', 'asgaros-forum')
                ),
                'public' => false,
                'show_ui' => true,
                'rewrite' => false,
                'capabilities' => array(
                    'manage_terms' => 'edit_users',
					'edit_terms'   => 'edit_users',
					'delete_terms' => 'edit_users',
					'assign_terms' => 'edit_users'
				)
            )
        );
    }

    function prepare() {
        if (!$this->execute_plugin()) {
            return;
        }

        global $user_ID, $wp;

        if (isset($_GET['view'])) {
            $this->current_view = esc_html($_GET['view']);
        }

        if (isset($_GET['part']) && absint($_GET['part']) > 0) {
            $this->current_page = (absint($_GET['part']) - 1);
        }

        switch ($this->current_view) {
            case 'forum':
            case 'addthread':
                $forum_id = absint($_GET['id']);
                if ($this->element_exists($forum_id, $this->table_forums)) {
                    $this->current_forum = $forum_id;
                    $this->parent_forum = $this->get_parent_id($this->current_forum, $this->table_forums, 'parent_forum');
                    $this->current_category = $this->get_parent_id($this->current_forum, $this->table_forums);
                } else {
                    $this->error = __('Sorry, this forum does not exist.', 'asgaros-forum');
                }
                break;
            case 'movethread':
            case 'thread':
            case 'addpost':
                $thread_id = absint($_GET['id']);
                if ($this->element_exists($thread_id, $this->table_threads)) {
                    $this->current_thread = $thread_id;
                    $this->current_forum = $this->get_parent_id($this->current_thread, $this->table_threads);
                    $this->parent_forum = $this->get_parent_id($this->current_forum, $this->table_forums, 'parent_forum');
                    $this->current_category = $this->get_parent_id($this->current_forum, $this->table_forums);
                } else {
                    $this->error = __('Sorry, this thread does not exist.', 'asgaros-forum');
                }
                break;
            case 'editpost':
                $post_id = absint($_GET['id']);
                if ($this->element_exists($post_id, $this->table_posts)) {
                    $this->current_post = $post_id;
                    $this->current_thread = $this->get_parent_id($post_id, $this->table_posts);
                    $this->current_forum = $this->get_parent_id($this->current_thread, $this->table_threads);
                    $this->parent_forum = $this->get_parent_id($this->current_forum, $this->table_forums, 'parent_forum');
                    $this->current_category = $this->get_parent_id($this->current_forum, $this->table_forums);
                } else {
                    $this->error = __('Sorry, this post does not exist.', 'asgaros-forum');
                }
                break;
        }

        $this->url_home = esc_url(get_permalink());
        $this->url_forum = esc_url(add_query_arg(array('view' => 'forum'), $this->url_home).'&amp;id=');
        $this->url_thread = esc_url(add_query_arg(array('view' => 'thread'), $this->url_home).'&amp;id=');
        $this->url_editor_thread = esc_url(add_query_arg(array('view' => 'addthread', 'id' => $this->current_forum), $this->url_home));
        $this->url_editor_post = esc_url(add_query_arg(array('view' => 'addpost', 'id' => $this->current_thread), $this->url_home));
        $this->url_editor_edit = esc_url(add_query_arg(array('view' => 'editpost'), $this->url_home).'&amp;id=');
        $this->url_markallread = esc_url(add_query_arg(array('view' => 'markallread'), $this->url_home));
        $this->url_movethread = esc_url(add_query_arg(array('view' => 'movethread', 'id' => $this->current_thread), $this->url_home));
        $this->current_url = esc_url(add_query_arg($_SERVER['QUERY_STRING'], '', trailingslashit(home_url($wp->request))));

        // Check
        $this->check_access();

        // Override editor settings
        $this->options_editor = apply_filters('asgarosforum_filter_editor_settings', $this->options_editor);

        // Set cookie
        if ($user_ID && !isset($_COOKIE['wpafcookie'])) {
            $last = get_user_meta($user_ID, 'asgarosforum_lastvisit', true);
            setcookie("wpafcookie", $last, 0, "/");
            update_user_meta($user_ID, 'asgarosforum_lastvisit', $this->current_time());
        }

        if (isset($_POST['submit_action']) && $user_ID) {
            AsgarosForumInsert::determineAction();
            if (AsgarosForumInsert::getAction()) {
                AsgarosForumInsert::setData();
                if (AsgarosForumInsert::validateExecution()) {
                    AsgarosForumInsert::insertData();
                }
            }
        } else if ($this->current_view === 'markallread' && $user_ID) {
            $time = $this->current_time();
            setcookie("wpafcookie", $time, 0, "/");
            update_user_meta($user_ID, 'asgarosforum_lastvisit', $time);
            wp_redirect(html_entity_decode($this->url_home));
            exit;
        } else if (isset($_GET['move_thread'])) {
            $this->move_thread();
        } else if (isset($_GET['delete_thread'])) {
            $this->delete_thread($this->current_thread);
        } else if (isset($_GET['remove_post'])) {
            $this->remove_post();
        } else if (isset($_GET['sticky'])) {
            $this->change_status('sticky');
        } else if (isset($_GET['closed'])) {
            $this->change_status('closed');
        } else if (isset($_GET['subscribe_topic'])) {
            AsgarosForumNotifications::subscribeTopic();
        } else if (isset($_GET['unsubscribe_topic'])) {
            AsgarosForumNotifications::unsubscribeTopic();
        }
    }

    function check_access() {
        // Check login access.
        if ($this->options['require_login'] && !is_user_logged_in()) {
            $this->error = __('Sorry, only logged in users have access to the forum.', 'asgaros-forum').'&nbsp;<a href="'.esc_url(wp_login_url($this->current_url)).'">&raquo; '.__('Login', 'asgaros-forum').'</a>';
            return false;
        }

        // Check category access.
        $category_access = get_term_meta($this->current_category, 'category_access', true);

        if (!empty($category_access)) {
            if ($category_access === 'loggedin' && !is_user_logged_in()) {
                $this->error = __('Sorry, only logged in users have access to this category.', 'asgaros-forum').'&nbsp;<a href="'.esc_url(wp_login_url($this->current_url)).'">&raquo; '.__('Login', 'asgaros-forum').'</a>';
                return false;
            }

            if ($category_access === 'moderator' && !AsgarosForumPermissions::isModerator('current')) {
                $this->error = __('Sorry, you dont have access to this area.', 'asgaros-forum');
                return false;
            }
        }

        // Check custom access.
        $custom_access = apply_filters('asgarosforum_filter_check_access', true, $this->current_category);

        if (!$custom_access) {
            $this->error = __('Sorry, you dont have access to this area.', 'asgaros-forum');
            return false;
        }
    }

    function enqueue_front_scripts() {
        if (!$this->execute_plugin()) {
            return;
        }

        wp_enqueue_script('asgarosforum-js', $this->directory.'js/script.js', array('jquery'));
        wp_enqueue_style('dashicons');
    }

    function change_wp_title($title, $sep, $seplocation) {
        if (!$this->execute_plugin()) {
            return $title;
        }

        return $this->get_title($title);
    }

    function change_document_title_parts($title) {
        if (!$this->execute_plugin()) {
            return $title;
        }

        $title['title'] = $this->get_title($title['title']);

        return $title;
    }

    function get_title($title) {
        $pre = '';

        if (!$this->error && $this->current_view) {
            if ($this->current_view == 'forum') {
                if ($this->current_forum) {
                    $pre = esc_html(stripslashes($this->get_name($this->current_forum, $this->table_forums))).' - ';
                }
            } else if ($this->current_view == 'thread') {
                if ($this->current_thread) {
                    $pre = esc_html(stripslashes($this->get_name($this->current_thread, $this->table_threads))).' - ';
                }
            } else if ($this->current_view == 'editpost') {
                $pre = __('Edit Post', 'asgaros-forum').' - ';
            } else if ($this->current_view == 'addpost') {
                $pre = __('Post Reply', 'asgaros-forum').' - ';
            } else if ($this->current_view == 'addthread') {
                $pre = __('New Thread', 'asgaros-forum').' - ';
            } else if ($this->current_view == 'movethread') {
                $pre = __('Move Thread', 'asgaros-forum').' - ';
            }
        }

        return $pre.$title;
    }

    function add_mce_buttons($buttons, $editor_id) {
        if (!$this->execute_plugin() || $editor_id !== 'message') {
            return $buttons;
        } else {
            $buttons[] = 'image';
            return $buttons;
        }
    }

    function disable_captions($args) {
        if (!$this->execute_plugin()) {
            return $args;
        } else {
            return true;
        }
    }

    function forum() {
        global $wpdb, $user_ID;

        ob_start();
        echo '<div id="af-wrapper">';

        if (!empty($this->error)) {
            echo '<div class="error">'.$this->error.'</div>';
        } else {
            echo $this->breadcrumbs();

            if (!empty($this->info)) {
                echo '<div class="info">'.$this->info.'</div>';
            }

            switch ($this->current_view) {
                case 'movethread':
                    $this->movethread();
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

        echo '</div>';
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    function overview() {
        $categories = $this->get_categories();

        if ($categories) {
            require('views/overview.php');
        } else {
            echo '<div class="notice">'.__('There are no categories yet!', 'asgaros-forum').'</div>';
        }
    }

    function showforum() {
        $threads = $this->get_threads($this->current_forum);
        $sticky_threads = $this->get_threads($this->current_forum, 'sticky');
        $counter_normal = count($threads);
        $counter_total = $counter_normal + count($sticky_threads);

        $this->showLoginMessage();

        require('views/forum.php');
    }

    function showthread() {
        global $wpdb, $wp_embed;
        $posts = $this->get_posts();

        if ($posts) {
            $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET views = views + 1 WHERE id = %d", $this->current_thread));

            $meClosed = ($this->get_status('closed')) ? '&nbsp;('.__('Thread closed', 'asgaros-forum').')' : '';

            $this->showLoginMessage();

            require('views/thread.php');
        } else {
            echo '<div class="notice">'.__('Sorry, but there are no posts.', 'asgaros-forum').'</div>';
        }
    }

    function showLoginMessage() {
        if (!is_user_logged_in()) {
            echo '<div class="info">'.__('You need to login in order to create posts and topics.', 'asgaros-forum').'&nbsp;<a href="'.esc_url(wp_login_url($this->current_url)).'">&raquo; '.__('Login', 'asgaros-forum').'</a></div>';
        }
    }

    function movethread() {
        if (AsgarosForumPermissions::isModerator('current')) {
            $strOUT = '<form method="post" action="'.$this->url_movethread.'&amp;move_thread">';
            $strOUT .= '<div class="title-element">'.sprintf(__('Move "<strong>%s</strong>" to new forum:', 'asgaros-forum'), esc_html(stripslashes($this->get_name($this->current_thread, $this->table_threads)))).'</div>';
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
        global $wpdb;

        if (!empty($id) && is_numeric($id) && $wpdb->get_row($wpdb->prepare("SELECT id FROM {$location} WHERE id = %d;", $id))) {
            return true;
        } else {
            return false;
        }
    }

    // TODO: optimize.
    function get_link($id, $location, $page = 1) {
        $page_appendix = ($page > 1) ? '&amp;part='.$page : '';
        return esc_url($location.$id.$page_appendix);
    }

    function get_postlink($thread_id, $post_id, $page = 0) {
        global $wpdb;

        if (!$page) {
            $wpdb->get_col($wpdb->prepare("SELECT id FROM {$this->table_posts} WHERE parent_id = %d;", $thread_id));
            $page = ceil($wpdb->num_rows / $this->options['posts_per_page']);
        }

        return $this->get_link($thread_id, $this->url_thread, $page) . '#postid-' . $post_id;
    }

    function get_widget_link($thread_id, $post_id, $target) {
        global $wpdb;
        $wpdb->get_col($wpdb->prepare("SELECT id FROM {$this->table_posts} WHERE parent_id = %d;", $thread_id));
        $page = ceil($wpdb->num_rows / $this->options['posts_per_page']);

        return $this->get_link($thread_id, add_query_arg(array('view' => 'thread'), $target).'&amp;id=', $page).'#postid-'.$post_id;
    }

    function get_all_categories_by_meta($key, $value, $compare = 'LIKE') {
        $categories = get_terms('asgarosforum-category', array(
            'fields' => 'ids',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => $key,
                    'value' => $value,
                    'compare' => $compare
                )
            )
        ));

        return $categories;
    }

    function get_categories($disable_hooks = false) {
        $filter = array();

        if (!$disable_hooks) {
            $filter = apply_filters('asgarosforum_filter_get_categories', $filter);
        }

        $categories = get_terms('asgarosforum-category', array('hide_empty' => false, 'exclude' => $filter));

        foreach ($categories as $key => $category) {
            $term_meta = get_term_meta($category->term_id);
            $category->order = $term_meta['order'][0];
            $category->category_access = (!empty($term_meta['category_access'][0])) ? $term_meta['category_access'][0] : 'everyone';

            // Remove categories from array where the user has no access.
            if (($category->category_access === 'loggedin' && !is_user_logged_in()) || ($category->category_access === 'moderator' && !AsgarosForumPermissions::isModerator('current'))) {
                unset($categories[$key]);
            }
        }

        usort($categories, array($this, 'categories_compare'));

        return $categories;
    }

    function categories_compare($a, $b) {
        return ($a->order < $b->order) ? -1 : (($a->order > $b->order) ? 1 : 0);
    }

    function get_forums($id = false, $parent_forum = 0) {
        global $wpdb;

        if ($id) {
            return $wpdb->get_results($wpdb->prepare("SELECT f.id, f.name, f.description, f.closed, f.sort, f.parent_forum, (SELECT COUNT(ct_t.id) FROM {$this->table_threads} AS ct_t, {$this->table_forums} AS ct_f WHERE ct_t.parent_id = ct_f.id AND (ct_f.id = f.id OR ct_f.parent_forum = f.id)) AS count_threads, (SELECT COUNT(cp_p.id) FROM {$this->table_posts} AS cp_p, {$this->table_threads} AS cp_t, {$this->table_forums} AS cp_f WHERE cp_p.parent_id = cp_t.id AND cp_t.parent_id = cp_f.id AND (cp_f.id = f.id OR cp_f.parent_forum = f.id)) AS count_posts, (SELECT COUNT(csf_f.id) FROM {$this->table_forums} AS csf_f WHERE csf_f.parent_forum = f.id) AS count_subforums FROM {$this->table_forums} AS f WHERE f.parent_id = %d AND f.parent_forum = %d GROUP BY f.id ORDER BY f.sort ASC;", $id, $parent_forum));
        } else {
            // Load all forums.
            return $wpdb->get_results("SELECT id, name FROM {$this->table_forums} ORDER BY sort ASC;");
        }
    }

    function get_threads($id, $type = 'normal') {
        global $wpdb;
        $limit = "";

        if ($type == 'normal') {
            $start = $this->current_page * $this->options['threads_per_page'];
            $end = $this->options['threads_per_page'];
            $limit = $wpdb->prepare("LIMIT %d, %d", $start, $end);
        }

        $order = apply_filters('asgarosforum_filter_get_threads_order', "(SELECT MAX(id) FROM {$this->table_posts} AS p WHERE p.parent_id = t.id) DESC");
        $results = $wpdb->get_results($wpdb->prepare("SELECT t.id, t.name, t.views, t.status FROM {$this->table_threads} AS t WHERE t.parent_id = %d AND t.status LIKE %s ORDER BY {$order} {$limit};", $id, $type.'%'));
        $results = apply_filters('asgarosforum_filter_get_threads', $results);
        return $results;
    }

    function get_posts() {
        global $wpdb;
        $start = $this->current_page * $this->options['posts_per_page'];
        $end = $this->options['posts_per_page'];

        $order = apply_filters('asgarosforum_filter_get_posts_order', 'p1.id ASC');
        $results = $wpdb->get_results($wpdb->prepare("SELECT p1.id, p1.text, p1.date, p1.date_edit, p1.author_id, (SELECT COUNT(p2.id) FROM {$this->table_posts} AS p2 WHERE p2.author_id = p1.author_id) AS author_posts, uploads FROM {$this->table_posts} AS p1 WHERE p1.parent_id = %d ORDER BY {$order} LIMIT %d, %d;", $this->current_thread, $start, $end));
        $results = apply_filters('asgarosforum_filter_get_posts', $results);
        return $results;
    }

    function is_first_post($post_id) {
        global $wpdb;
        $first_post_id = $wpdb->get_var("SELECT id FROM {$this->table_posts} WHERE parent_id = {$this->current_thread} ORDER BY id ASC LIMIT 1;");

        if ($first_post_id == $post_id) {
            return true;
        } else {
            return false;
        }
    }

    function get_name($id, $location) {
        global $wpdb;

        if (empty($this->cache['get_name'][$location][$id])) {
            $this->cache['get_name'][$location][$id] = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$location} WHERE id = %d;", $id));
        }

        return $this->cache['get_name'][$location][$id];
    }

    function cut_string($string, $length = 33) {
        if (strlen($string) > $length) {
            return mb_substr($string, 0, $length, 'UTF-8') . ' ...';
        }

        return $string;
    }

    function get_username($user_id, $widget = false) {
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
            return 'Deleted user';
        }
    }

    // TODO: optimize
    // For Widgets
    function get_last_posts($items = 1, $where = '') {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT p1.id, p1.date, p1.parent_id, p1.author_id, t.name FROM {$this->table_posts} AS p1 LEFT JOIN {$this->table_posts} AS p2 ON (p1.parent_id = p2.parent_id AND p1.id < p2.id) LEFT JOIN {$this->table_threads} AS t ON (t.id = p1.parent_id) LEFT JOIN {$this->table_forums} AS f ON (f.id = t.parent_id) WHERE p2.id IS NULL {$where} ORDER BY p1.id DESC LIMIT %d;", $items));
    }

    function get_lastpost($lastpost_data, $context = 'forum') {
        $lastpost = false;

        if ($lastpost_data) {
            $lastpost_link = $this->get_postlink($lastpost_data->parent_id, $lastpost_data->id);
            $lastpost = '<small>'.__('Last post by', 'asgaros-forum').'&nbsp;<strong>'.$this->get_username($lastpost_data->author_id).'</strong></small>';
            $lastpost .= ($context === 'forum') ? '<small>'.__('in', 'asgaros-forum').'&nbsp;<strong><a href="'.$lastpost_link.'">'.esc_html($this->cut_string(stripslashes($lastpost_data->name))).'</a></strong></small>' : '';
            $lastpost .= '<small>'.sprintf(__('on %s', 'asgaros-forum'), '<a href="'.$lastpost_link.'">'.$this->format_date($lastpost_data->date).'</a>').'</small>';
        } else if ($context === 'forum') {
            $lastpost = '<small>'.__('No threads yet!', 'asgaros-forum').'</small>';
        }

        return $lastpost;
    }

    function get_lastpost_in_thread($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT p.id, p.date, p.author_id, p.parent_id FROM {$this->table_posts} AS p INNER JOIN {$this->table_threads} AS t ON p.parent_id = t.id WHERE p.parent_id = %d ORDER BY p.id DESC LIMIT 1;", $id));
    }

    function get_lastpost_in_forum($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT p.id, p.date, p.parent_id, p.author_id, t.name FROM {$this->table_posts} AS p INNER JOIN {$this->table_threads} AS t ON p.parent_id = t.id INNER JOIN {$this->table_forums} AS f ON t.parent_id = f.id WHERE f.id = %d OR f.parent_forum = %d ORDER BY p.id DESC LIMIT 1;", $id, $id));
    }

    function get_thread_starter($thread_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT author_id FROM {$this->table_posts} WHERE parent_id = %d ORDER BY id ASC LIMIT 1;", $thread_id));
    }

    function post_menu($post_id, $author_id, $counter) {
        global $user_ID;

        $o = '';

        if ($user_ID && (!$this->get_status('closed') || AsgarosForumPermissions::isModerator('current')) && !AsgarosForumPermissions::isBanned('current')) {
            $o .= '<a href="'.$this->url_editor_post.'&amp;quote='.$post_id.'"><span class="dashicons-before dashicons-editor-quote"></span>'.__('Quote', 'asgaros-forum').'</a>';
        }

        if (($counter > 1 || $this->current_page >= 1) && AsgarosForumPermissions::isModerator('current')) {
            $o .= '<a onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');" href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;remove_post&amp;post='.$post_id.'"><span class="dashicons-before dashicons-trash"></span>'.__('Remove', 'asgaros-forum').'</a>';
        }

        if ((AsgarosForumPermissions::isModerator('current') || $user_ID == $author_id) && !AsgarosForumPermissions::isBanned('current')) {
            $o .= '<a href="'.$this->url_editor_edit.$post_id.'&amp;part='.($this->current_page + 1).'"><span class="dashicons-before dashicons-edit"></span>'.__('Edit', 'asgaros-forum').'</a>';
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
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT author_id FROM {$this->table_posts} WHERE id = %d;", $post_id));
    }

    function count_elements($id, $location, $where = 'parent_id') {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$location} WHERE {$where} = %d;", $id));
    }

    function forum_menu($location, $showallbuttons = true) {
        global $user_ID;
        $menu = '';

        if ($user_ID) {
            if ($location == 'forum' && !AsgarosForumPermissions::isBanned('current') && $this->get_forum_status()) {
                $menu .= '<a href="'.$this->url_editor_thread.'"><span class="dashicons-before dashicons-format-aside"></span><span>'.__('New Thread', 'asgaros-forum').'</span></a>';
            } else if ($location == 'thread') {
                if (AsgarosForumPermissions::isModerator('current') || (!$this->get_status('closed') && !AsgarosForumPermissions::isBanned('current'))) {
                    $menu .= '<a href="'.$this->url_editor_post.'"><span class="dashicons-before dashicons-format-aside"></span><span>'.__('Reply', 'asgaros-forum').'</span></a>';
                }

                if (AsgarosForumPermissions::isModerator('current') && $showallbuttons) {
                    $menu .= '<a href="'.$this->url_movethread.'"><span class="dashicons-before dashicons-randomize"></span><span>'.__('Move Thread', 'asgaros-forum').'</span></a>';
                    $menu .= '<a href="'.$this->url_thread.$this->current_thread.'&amp;delete_thread" onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');"><span class="dashicons-before dashicons-trash"></span><span>'.__('Delete Thread', 'asgaros-forum').'</span></a>';

                    if ($this->get_status('sticky')) {
                        $menu .= '<a href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;sticky"><span class="dashicons-before dashicons-sticky"></span><span>'.__('Undo Sticky', 'asgaros-forum').'</span></a>';
                    } else {
                        $menu .= '<a href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;sticky"><span class="dashicons-before dashicons-admin-post"></span><span>'.__('Sticky', 'asgaros-forum').'</span></a>';
                    }

                    if ($this->get_status('closed')) {
                        $menu .= '<a href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;closed"><span class="dashicons-before dashicons-unlock"></span><span>'.__('Re-open', 'asgaros-forum').'</span></a>';
                    } else {
                        $menu .= '<a href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;closed"><span class="dashicons-before dashicons-lock"></span><span>'.__('Close', 'asgaros-forum').'</span></a>';
                    }
                }
            }
        }

        return $menu;
    }

    function get_parent_id($id, $location, $value = 'parent_id') {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT {$value} FROM {$location} WHERE id = %d;", $id));
    }

    function breadcrumbs() {
        $trail = '<span class="dashicons-before dashicons-admin-home"></span><a href="'.$this->url_home.'">'.__('Forum', 'asgaros-forum').'</a>';

        if ($this->parent_forum && $this->parent_forum > 0) {
            $link = $this->get_link($this->parent_forum, $this->url_forum);
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;<a href="'.$link.'">'.esc_html(stripslashes($this->get_name($this->parent_forum, $this->table_forums))).'</a>';
        }

        if ($this->current_forum) {
            $link = $this->get_link($this->current_forum, $this->url_forum);
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;<a href="'.$link.'">'.esc_html(stripslashes($this->get_name($this->current_forum, $this->table_forums))).'</a>';
        }

        if ($this->current_thread) {
            $link = $this->get_link($this->current_thread, $this->url_thread);
            $name = stripslashes($this->get_name($this->current_thread, $this->table_threads));
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;<a href="'.$link.'" title="'.esc_html($name).'">'.esc_html($this->cut_string($name)).'</a>';
        }

        if ($this->current_view === 'addpost') {
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;' . __('Post Reply', 'asgaros-forum');
        } else if ($this->current_view === 'editpost') {
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;' . __('Edit Post', 'asgaros-forum');
        } else if ($this->current_view === 'addthread') {
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;' . __('New Thread', 'asgaros-forum');
        }

        return '<div class="breadcrumbs">'.$trail.'</div>';
    }

    function last_visit() {
        global $user_ID;

        if ($user_ID && isset($_COOKIE['wpafcookie'])) {
            return $_COOKIE['wpafcookie'];
        } else {
            return "0000-00-00 00:00:00";
        }
    }

    function pageing($location) {
        global $wpdb;
        $out = '<div class="pages">'.__('Pages:', 'asgaros-forum');
        $num_pages = 0;
        $select_source = '';
        $select_url = '';

        if ($location == $this->table_posts) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT count(id) FROM {$location} WHERE parent_id = %d;", $this->current_thread));
            $num_pages = ceil($count / $this->options['posts_per_page']);
            $select_source = $this->current_thread;
            $select_url = $this->url_thread;
        } else if ($location == $this->table_threads) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT count(id) FROM {$location} WHERE parent_id = %d AND status LIKE %s;", $this->current_forum, "normal%"));
            $num_pages = ceil($count / $this->options['threads_per_page']);
            $select_source = $this->current_forum;
            $select_url = $this->url_forum;
        }

        if ($num_pages > 1) {
            if ($num_pages <= 6) {
                for ($i = 0; $i < $num_pages; ++$i) {
                    if ($i == $this->current_page) {
                        $out .= ' <strong>'.($i + 1).'</strong>';
                    } else {
                        $out .= ' <a href="'.$this->get_link($select_source, $select_url, ($i + 1)).'">'.($i + 1).'</a>';
                    }
                }
            } else {
                if ($this->current_page >= 4) {
                    $out .= ' <a href="'.$this->get_link($select_source, $select_url).'">'.__('First', 'asgaros-forum').'</a> <<';
                }

                for ($i = 3; $i > 0; $i--) {
                    if ((($this->current_page + 1) - $i) > 0) {
                        $out .= ' <a href="'.$this->get_link($select_source, $select_url, (($this->current_page + 1) - $i)).'">'.(($this->current_page + 1) - $i).'</a>';
                    }
                }

                $out .= ' <strong>'.($this->current_page + 1).'</strong>';

                for ($i = 1; $i <= 3; $i++) {
                    if ((($this->current_page + 1) + $i) <= $num_pages) {
                        $out .= ' <a href="'.$this->get_link($select_source, $select_url, (($this->current_page + 1) + $i)).'">'.(($this->current_page + 1) + $i).'</a>';
                    }
                }

                if ($num_pages - $this->current_page >= 5) {
                    $out .= ' >> <a href="'.$this->get_link($select_source, $select_url, $num_pages).'">'.__('Last', 'asgaros-forum').'</a>';
                }
            }

            $out .= '</div>';
            return $out;
        } else {
            return '';
        }
    }

    function delete_thread($thread_id, $admin_action = false) {
        global $wpdb;

        if (AsgarosForumPermissions::isModerator('current')) {
            if ($thread_id) {
                // Delete uploads
                $posts = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$this->table_posts} WHERE parent_id = %d;", $thread_id));
                foreach ($posts as $post) {
                    AsgarosForumUploads::deletePostFiles($post);
                }

                $wpdb->delete($this->table_posts, array('parent_id' => $thread_id), array('%d'));
                $wpdb->delete($this->table_threads, array('id' => $thread_id), array('%d'));
                AsgarosForumNotifications::removeTopicSubscriptions($thread_id);

                if (!$admin_action) {
                    wp_redirect(html_entity_decode($this->url_forum . $this->current_forum));
                    exit;
                }
            }
        }
    }

    function move_thread() {
        global $wpdb;
        $newForumID = $_POST['newForumID'];

        if (AsgarosForumPermissions::isModerator('current') && $newForumID && $this->element_exists($newForumID, $this->table_forums)) {
            $wpdb->update($this->table_threads, array('parent_id' => $newForumID), array('id' => $this->current_thread), array('%d'), array('%d'));
            wp_redirect(html_entity_decode($this->url_thread . $this->current_thread));
            exit;
        }
    }

    function remove_post() {
        global $wpdb;
        $post_id = (isset($_GET['post']) && is_numeric($_GET['post'])) ? absint($_GET['post']) : 0;

        if (AsgarosForumPermissions::isModerator('current') && $this->element_exists($post_id, $this->table_posts)) {
            $wpdb->delete($this->table_posts, array('id' => $post_id), array('%d'));
            AsgarosForumUploads::deletePostFiles($post_id);
        }
    }

    function get_thread_image($lastpost_data, $status) {
        global $user_ID;
        $unread_status = '';

        if ($lastpost_data) {
            $lastpost_time = $lastpost_data->date;
            $lastpost_author_id = $lastpost_data->author_id;

            if ($lastpost_time && $user_ID != $lastpost_author_id) {
                $lp = strtotime($lastpost_time);
                $lv = strtotime($this->last_visit());

                if ($lp > $lv) {
                    $unread_status = ' unread';
                }
            }
        }

        echo '<span class="dashicons-before dashicons-'.$status.$unread_status.'"></span>';
    }

    function change_status($property) {
        global $wpdb;
        $new_status = '';

        if (AsgarosForumPermissions::isModerator('current')) {
            if ($property == 'sticky') {
                $new_status .= ($this->get_status('sticky')) ? 'normal_' : 'sticky_';
                $new_status .= ($this->get_status('closed')) ? 'closed' : 'open';
            } else if ($property == 'closed') {
                $new_status .= ($this->get_status('sticky')) ? 'sticky_' : 'normal_';
                $new_status .= ($this->get_status('closed')) ? 'open' : 'closed';
            }

            $wpdb->update($this->table_threads, array('status' => $new_status), array('id' => $this->current_thread), array('%s'), array('%d'));

            // Update cache
            $this->cache['get_status'][$this->current_thread] = $new_status;
        }
    }

    function get_status($property) {
        global $wpdb;

        if (empty($this->cache['get_status'][$this->current_thread])) {
            $this->cache['get_status'][$this->current_thread] = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$this->table_threads} WHERE id = %d;", $this->current_thread));
        }

        $status = $this->cache['get_status'][$this->current_thread];

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
            global $wpdb;
            $closed = intval($wpdb->get_var($wpdb->prepare("SELECT closed FROM {$this->table_forums} WHERE id = %d;", $this->current_forum)));

            if ($closed === 1) {
                return false;
            }
        }

        return true;
    }
}

?>
