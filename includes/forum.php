<?php

if (!defined('ABSPATH')) exit;

class asgarosforum {
    var $directory = '';
    var $db_version = 2;
    var $date_format = "";
    var $access = true;
    var $error = '';
    var $url_home = '';
    var $url_forum = '';
    var $url_thread = '';
    var $url_editor_thread = '';
    var $url_editor_post = '';
    var $url_editor_edit = '';
    var $url_markallread = '';
    var $url_movethread = '';
    var $upload_path = "";
    var $upload_url = "";
    var $table_forums = "";
    var $table_threads = "";
    var $table_posts = "";
    var $current_category = false;
    var $current_forum = false;
    var $current_thread = false;
    var $current_view = false;
    var $current_page = 0;
    var $options = array();
    var $options_default = array(
        'posts_per_page'        => 10,
        'threads_per_page'      => 20,
        'allow_file_uploads'    => false,
        'highlight_admin'       => true,
        'show_edit_date'        => true,
        'require_login'         => false,
        'minimalistic_editor'   => true,
        'custom_color'          => '#2d89cc'
    );
    var $options_editor = array(
        'media_buttons' => false,
        'textarea_rows' => 12,
        'teeny'         => true,
        'quicktags'     => false
    );

    function __construct($directory) {
        global $wpdb;
        $this->directory = $directory;
        $this->options = array_merge($this->options_default, get_option('asgarosforum_options', array()));
        $this->options_editor['teeny'] = $this->options['minimalistic_editor'];  // Override editor settings
        $this->date_format = get_option('date_format') . ', ' . get_option('time_format');
        $this->table_forums = $wpdb->prefix . 'forum_forums';
        $this->table_threads = $wpdb->prefix . 'forum_threads';
        $this->table_posts = $wpdb->prefix . 'forum_posts';

        $upload_dir = wp_upload_dir();
        $this->upload_path = $upload_dir['basedir'].'/asgarosforum/';
        $this->upload_url = $upload_dir['baseurl'].'/asgarosforum/';

        register_activation_hook(__FILE__, array($this, 'install'));
        add_action('plugins_loaded', array($this, 'install'));
        add_action('init', array($this, 'register_category_taxonomy'));
        add_action('wp', array($this, 'prepare'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_front_scripts'));
        add_action('wp_head', array($this, 'setup_header'));
        add_filter('wp_title', array($this, 'get_pagetitle'), 10, 3);
        add_filter('teeny_mce_buttons', array($this, 'add_mce_buttons'), 9999, 2);
        add_filter('mce_buttons', array($this, 'add_mce_buttons'), 9999, 2);
        add_filter('disable_captions', array($this, 'disable_captions'));
        add_shortcode('forum', array($this, 'forum'));
    }

    function execute_plugin() {
        global $post;

        if (!(is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'forum'))) {
            return false;
        } else {
            return true;
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

    function install() {
        global $wpdb;
        $installed_ver = get_option("asgarosforum_db_version");

        if ($installed_ver != $this->db_version) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql1 = "
            CREATE TABLE $this->table_forums (
            id int(11) NOT NULL auto_increment,
            name varchar(255) NOT NULL default '',
            parent_id int(11) NOT NULL default '0',
            description varchar(255) NOT NULL default '',
            sort int(11) NOT NULL default '0',
            PRIMARY KEY  (id)
            ) $charset_collate;";

            $sql2 = "
            CREATE TABLE $this->table_threads (
            id int(11) NOT NULL auto_increment,
            parent_id int(11) NOT NULL default '0',
            views int(11) NOT NULL default '0',
            name varchar(255) NOT NULL default '',
            status varchar(20) NOT NULL default 'normal_open',
            PRIMARY KEY  (id)
            ) $charset_collate;";

            $sql3 = "
            CREATE TABLE $this->table_posts (
            id int(11) NOT NULL auto_increment,
            text longtext,
            parent_id int(11) NOT NULL default '0',
            date datetime NOT NULL default '0000-00-00 00:00:00',
            date_edit datetime NOT NULL default '0000-00-00 00:00:00',
            author_id int(11) NOT NULL default '0',
            uploads longtext,
            PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            dbDelta($sql1);
            dbDelta($sql2);
            dbDelta($sql3);

            update_option("asgarosforum_db_version", $this->db_version);
        }
    }

    function prepare() {
        if (!$this->execute_plugin()) {
            return;
        }

        global $user_ID;

        if (isset($_GET['view'])) {
            $this->current_view = $_GET['view'];
        }

        if (isset($_GET['part']) && $_GET['part'] > 0) {
            $this->current_page = ($_GET['part'] - 1);
        }

        switch ($this->current_view) {
            case 'forum':
            case 'addthread':
                $forum_id = $_GET['id'];
                if ($this->element_exists($forum_id, $this->table_forums)) {
                    $this->current_forum = $forum_id;
                    $this->current_category = $this->get_parent_id($this->current_forum, $this->table_forums);
                }
                break;
            case 'movethread':
            case 'thread':
            case 'addpost':
                $thread_id = $_GET['id'];
                if ($this->element_exists($thread_id, $this->table_threads)) {
                    $this->current_thread = $thread_id;
                    $this->current_forum = $this->get_parent_id($this->current_thread, $this->table_threads);
                    $this->current_category = $this->get_parent_id($this->current_forum, $this->table_forums);
                }
                break;
            case 'editpost':
                $post_id = $_GET['id'];
                if ($this->element_exists($post_id, $this->table_posts)) {
                    $this->current_thread = $this->get_parent_id($post_id, $this->table_posts);
                    $this->current_forum = $this->get_parent_id($this->current_thread, $this->table_threads);
                    $this->current_category = $this->get_parent_id($this->current_forum, $this->table_forums);
                }
                break;
        }

        $this->url_home = get_permalink();
        $this->url_forum = add_query_arg(array('view' => 'forum'), $this->url_home).'&amp;id=';
        $this->url_thread = add_query_arg(array('view' => 'thread'), $this->url_home).'&amp;id=';
        $this->url_editor_thread = add_query_arg(array('view' => 'addthread', 'id' => $this->current_forum), $this->url_home);
        $this->url_editor_post = add_query_arg(array('view' => 'addpost', 'id' => $this->current_thread), $this->url_home);
        $this->url_editor_edit = add_query_arg(array('view' => 'editpost'), $this->url_home).'&amp;id=';
        $this->url_markallread = add_query_arg(array('view' => 'markallread'), $this->url_home);
        $this->url_movethread = add_query_arg(array('view' => 'movethread', 'id' => $this->current_thread), $this->url_home);

        // Set cookie
        if ($user_ID && !isset($_COOKIE['wpafcookie'])) {
            $last = get_user_meta($user_ID, 'asgarosforum_lastvisit', true);
            setcookie("wpafcookie", $last, 0, "/");
            update_user_meta($user_ID, 'asgarosforum_lastvisit', $this->current_time());
        }

        if ((isset($_POST['add_thread_submit']) || isset($_POST['add_post_submit']) || isset($_POST['edit_post_submit'])) && $user_ID) {
            require('insert.php');
        } else if (isset($_GET['view']) && $_GET['view'] == "markallread" && $user_ID) {
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
        }

        $this->check_access();
    }

    function check_access() {
        $access = (!$this->options['require_login'] || ($this->options['require_login'] && is_user_logged_in())) ? true : false;
        $this->access = apply_filters('asgarosforum_filter_check_access', $access, $this->current_category);
    }

    function enqueue_front_scripts() {
        if (!$this->execute_plugin()) {
            return;
        }

        wp_enqueue_script('asgarosforum-js', $this->directory.'js/script.js', array('jquery'));
    }

    function setup_header() {
        echo '<link rel="stylesheet" type="text/css" href="'.$this->directory.'skin/widgets.css" />';

        if (!$this->execute_plugin()) {
            return;
        }

        echo '<link rel="stylesheet" type="text/css" href="'.$this->directory.'skin/style.css" />';

        if ($this->options['custom_color'] !== $this->options_default['custom_color']) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->directory.'skin/custom-color.php?color='.substr($this->options['custom_color'], 1).'" />';
        }

        if (wp_is_mobile()) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->directory.'skin/mobile.css" />';
        }
    }

    function get_pagetitle($title, $sep, $seplocation) {
        if (!$this->execute_plugin()) {
            return $title;
        }

        $pre = '';

        if ($this->access && $this->current_view) {
            if ($this->current_view == "forum") {
                if ($this->current_forum) {
                    $pre = $this->get_name($this->current_forum, $this->table_forums) . " - ";
                }
            } else if ($this->current_view == "thread") {
                if ($this->current_thread) {
                    $pre = $this->get_name($this->current_thread, $this->table_threads) . " - ";
                }
            } else if ($this->current_view == "editpost") {
                $pre = __('Edit Post', 'asgaros-forum') . ' - ';
            } else if ($this->current_view == "addpost") {
                $pre = __('Post Reply', 'asgaros-forum') . ' - ';
            } else if ($this->current_view == "addthread") {
                $pre = __('New Thread', 'asgaros-forum') . ' - ';
            } else if ($this->current_view == "movethread") {
                $pre = __('Move Thread', 'asgaros-forum') . ' - ';
            }
        }

        return $pre . $title;
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

        if ($this->access) {
            echo $this->breadcrumbs();

            switch ($this->current_view) {
                case 'movethread':
                    $this->movethread();
                    break;
                case 'forum':
                    $this->showforum($this->current_forum);
                    break;
                case 'thread':
                    $this->showthread($this->current_thread);
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
        } else {
            echo '<div class="info">'.__('Sorry, only logged in users have access to the forum.', 'asgaros-forum').'&nbsp;<a href="'.wp_login_url(get_permalink()).'">&raquo; '.__('Login', 'asgaros-forum').'</a></div>';
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
        if ($this->current_forum && $this->access) {
            $threads = $this->get_threads($this->current_forum);
            $sticky_threads = $this->get_threads($this->current_forum, 'sticky');
            $counter_normal = count($threads);
            $counter_total = $counter_normal + count($sticky_threads);

            require('views/forum.php');
        } else {
            echo '<div class="notice">'.__('Sorry, this forum does not exist.', 'asgaros-forum').'</div>';
        }
    }

    function showthread() {
        if ($this->current_thread && $this->access) {
            global $wpdb, $wp_embed;
            $posts = $this->get_posts();

            if ($posts) {
                $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET views = views+1 WHERE id = %d", $this->current_thread));

                $meClosed = ($this->get_status('closed')) ? '&nbsp;('.__('Thread closed', 'asgaros-forum').')' : '';

                require('views/thread.php');
            } else {
                echo '<div class="notice">'.__('Sorry, but there are no posts.', 'asgaros-forum').'</div>';
            }
        } else {
            echo '<div class="notice">'.__('Sorry, this thread does not exist.', 'asgaros-forum').'</div>';
        }
    }

    function movethread() {
        if ($this->is_moderator() && $this->access) {
            $strOUT = '<form method="post" action="'.$this->url_movethread.'&amp;move_thread">';
            $strOUT .= sprintf(__('Move "<strong>%s</strong>" to new forum:', 'asgaros-forum'), $this->get_name($this->current_thread, $this->table_threads)).'<br />';
            $strOUT .= '<select name="newForumID">';

            $frs = $this->get_forums();

            foreach ($frs as $f) {
                $strOUT .= '<option value="' . $f->id . '"' . ($f->id == $this->current_forum ? ' selected="selected"' : '') . '>' . $f->name . '</option>';
            }

            $strOUT .= '</select><br /><input type="submit" value="'.__('Move', 'asgaros-forum').'" /></form>';

            echo $strOUT;
        } else {
            echo '<div class="notice">'.__('You are not allowed to move threads.', 'asgaros-forum').'</div>';
        }
    }

    function element_exists($id, $location) {
        global $wpdb;

        if (!empty($id) && $wpdb->get_row($wpdb->prepare("SELECT id FROM {$location} WHERE id = %d;", $id))) {
            return true;
        } else {
            return false;
        }
    }

    // TODO: optimize.
    function get_link($id, $location, $page = 1) {
        $page_appendix = ($page > 1) ? '&amp;part='.$page : '';
        return $location . $id . $page_appendix;
    }

    function get_postlink($thread_id, $post_id, $page = 0) {
        global $wpdb;

        if (!$page) {
            $wpdb->query($wpdb->prepare("SELECT id FROM {$this->table_posts} WHERE parent_id = %d;", $thread_id));
            $page = ceil($wpdb->num_rows / $this->options['posts_per_page']);
        }

        return $this->get_link($thread_id, $this->url_thread, $page) . '#postid-' . $post_id;
    }

    function get_widget_link($thread_id, $post_id, $target) {
        global $wpdb;
        $wpdb->query($wpdb->prepare("SELECT id FROM {$this->table_posts} WHERE parent_id = %d;", $thread_id));
        $page = ceil($wpdb->num_rows / $this->options['posts_per_page']);

        return $this->get_link($thread_id, add_query_arg(array('view' => 'thread'), $target).'&amp;id=', $page).'#postid-'.$post_id;
    }

    function get_categories($disable_hooks = false) {
        $filter = array();

        if (!$disable_hooks) {
            $filter = apply_filters('asgarosforum_filter_get_categories', $filter);
        }

        $categories = get_terms('asgarosforum-category', array('hide_empty' => false, 'exclude' => $filter));

        foreach ($categories as $category) {
            $category->order = get_term_meta($category->term_id, 'order', true);
        }

        usort($categories, 'self::categories_compare');

        return $categories;
    }

    function categories_compare($a, $b) {
        return strcmp($a->order, $b->order);
    }

    function get_forums($id = false) {
        global $wpdb;

        if ($id) {
            return $wpdb->get_results($wpdb->prepare("SELECT id, name, description FROM {$this->table_forums} WHERE parent_id = %d ORDER BY sort ASC;", $id));
        } else {
            return $wpdb->get_results("SELECT id, name, description FROM {$this->table_forums} ORDER BY sort ASC;");
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
        $results = $wpdb->get_results($wpdb->prepare("SELECT t.id, t.name, t.views, t.status FROM {$this->table_threads} AS t WHERE t.parent_id = %d AND t.status LIKE %s ORDER BY {$order} {$limit};", $id, $type . '%'));
        $results = apply_filters('asgarosforum_filter_get_threads', $results);
        return $results;
    }

    function get_posts() {
        global $wpdb;
        $start = $this->current_page * $this->options['posts_per_page'];
        $end = $this->options['posts_per_page'];

        return $wpdb->get_results($wpdb->prepare("SELECT id, text, date, date_edit, author_id, uploads FROM {$this->table_posts} WHERE parent_id = %d ORDER BY id ASC LIMIT %d, %d;", $this->current_thread, $start, $end));
    }

    function is_first_post($post_id) {
        global $wpdb;
        $first_post = $wpdb->get_row("SELECT id FROM {$this->table_posts} WHERE parent_id = {$this->current_thread} ORDER BY id ASC LIMIT 1;");

        if ($first_post->id == $post_id) {
            return true;
        } else {
            return false;
        }
    }

    function get_name($id, $location) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT name FROM {$location} WHERE id = %d;", $id));
    }

    function cut_string($string, $length = 35) {
        if (strlen($string) > $length) {
            return substr($string, 0, $length) . ' ...';
        }

        return $string;
    }

    function get_username($user_id, $wrap = false, $widget = false) {
        $user = get_userdata($user_id);

        if ($user) {
            $username = ($wrap) ? wordwrap($user->display_name, 22, "-<br/>", true) : $user->display_name;

            if ($this->options['highlight_admin'] && !$widget) {
                if (user_can($user_id, 'manage_options')) {
                    $username = '<span class="highlight-admin">'.$username.'</span>';
                } else if (get_user_meta($user_id, 'asgarosforum_moderator', true) == 1) {
                    $username = '<span class="highlight-moderator">'.$username.'</span>';
                }
            }

            return $username;
        } else {
            return 'Deleted user';
        }
    }

    // TODO: optimize
    function get_last_posts($items = 1) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT p1.id, p1.date, p1.parent_id, p1.author_id, (SELECT t.name FROM {$this->table_threads} AS t WHERE t.id = p1.parent_id) AS name FROM {$this->table_posts} AS p1 LEFT JOIN {$this->table_posts} AS p2 ON (p1.parent_id = p2.parent_id AND p1.id < p2.id) WHERE p2.id IS NULL ORDER BY p1.id DESC LIMIT %d;", $items));
    }

    function get_lastpost_in_thread($thread_id) {
        global $wpdb;
        $post = $wpdb->get_row($wpdb->prepare("SELECT id, date, author_id FROM {$this->table_posts} WHERE parent_id = %d ORDER BY id DESC LIMIT 1;", $thread_id));

        if ($post) {
            return '<small>'.__('Last post by', 'asgaros-forum').'&nbsp;<strong>'.$this->get_username($post->author_id).'</strong></small>
            <small>'.sprintf(__('on %s', 'asgaros-forum'), '<a href="'.$this->get_postlink($thread_id, $post->id).'">'.$this->format_date($post->date).'</a>').'</small>';
        } else {
            return false;
        }
    }

    function get_lastpost_in_forum($forum_id) {
        global $wpdb;
        $post = $wpdb->get_row($wpdb->prepare("SELECT p.id, p.date, p.parent_id, p.author_id, t.name FROM {$this->table_posts} AS p INNER JOIN {$this->table_threads} AS t ON p.parent_id=t.id WHERE t.parent_id = %d ORDER BY p.id DESC LIMIT 1;", $forum_id));

        if ($post) {
            return '<small>'.__('Last post by', 'asgaros-forum').'&nbsp;<strong>'.$this->get_username($post->author_id).'</strong></small>
            <small>'.__('in', 'asgaros-forum').'&nbsp;<strong>'.$this->cut_string($post->name).'</strong></small>
            <small>'.sprintf(__('on %s', 'asgaros-forum'), '<a href="'.$this->get_postlink($post->parent_id, $post->id).'">'.$this->format_date($post->date).'</a>').'</small>';
        } else {
            return '<small>'.__('No threads yet!', 'asgaros-forum').'</small>';
        }
    }

    function get_lastpost_data($id, $data, $location) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT {$data} FROM {$this->table_posts} AS p INNER JOIN {$this->table_threads} AS t ON p.parent_id=t.id WHERE {$location}.parent_id = %d ORDER BY p.id DESC LIMIT 1;", $id));
    }

    function get_thread_starter($thread_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT author_id FROM {$this->table_posts} WHERE parent_id = %d ORDER BY id ASC LIMIT 1;", $thread_id));
    }

    function post_menu($post_id, $author_id, $counter) {
        global $user_ID;

        $o = '';

        if ($user_ID && (!$this->get_status('closed') || $this->is_moderator())) {
            $o .= '<a href="'.$this->url_editor_post.'&amp;quote='.$post_id.'"><span class="icon-quotes-left"></span>'.__('Quote', 'asgaros-forum').'</a>';
        }

        if (($counter > 1 || $this->current_page >= 1) && $this->is_moderator()) {
            $o .= '<a onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');" href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;remove_post&amp;post='.$post_id.'"><span class="icon-bin"></span>'.__('Remove', 'asgaros-forum').'</a>';
        }

        if (($this->is_moderator()) || $user_ID == $author_id) {
            $o .= '<a href="'.$this->url_editor_edit.$post_id.'&amp;part='.($this->current_page + 1).'"><span class="icon-pencil2"></span>'.__('Edit', 'asgaros-forum').'</a>';
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

    function count_userposts($author_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT count(id) FROM {$this->table_posts} WHERE author_id = %d;", $author_id));
    }

    function get_post_author($post_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT author_id FROM {$this->table_posts} WHERE id = %d;", $post_id));
    }

    function count_elements($id, $location) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$location} WHERE parent_id = %d;", $id));
    }

    function count_posts_in_forum($forum_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT({$this->table_posts}.id) FROM {$this->table_posts} INNER JOIN {$this->table_threads} ON {$this->table_posts}.parent_id={$this->table_threads}.id WHERE {$this->table_threads}.parent_id = %d;", $forum_id));
    }

    function is_moderator() {
        global $user_ID;

        if ($user_ID && (is_super_admin($user_ID) || get_user_meta($user_ID, 'asgarosforum_moderator', true) == 1)) {
            return true;
        }

        return false;
    }

    function forum_menu($location) {
        global $user_ID;
        $menu = '';

        if ($user_ID) {
            if ($location == 'forum') {
                $menu .= '<a href="'.$this->url_editor_thread.'"><span class="icon-file-empty"></span><span>'.__('New Thread', 'asgaros-forum').'</span></a>';
            } else if ($location == 'thread') {
                if (!$this->get_status('closed') || $this->is_moderator()) {
                    $menu .= '<a href="'.$this->url_editor_post.'"><span class="icon-bubble2"></span><span>'.__('Reply', 'asgaros-forum').'</span></a>';
                }

                if ($this->is_moderator()) {
                    $menu .= '<a href="'.$this->url_movethread.'"><span class="icon-shuffle"></span><span>'.__('Move Thread', 'asgaros-forum').'</span></a>';
                    $menu .= '<a href="'.$this->url_thread.$this->current_thread.'&amp;delete_thread" onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');"><span class="icon-bin"></span><span>'.__('Delete Thread', 'asgaros-forum').'</span></a>';

                    $menu .= '<a href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;sticky"><span class="icon-pushpin"></span><span>';
                    $menu .= ($this->get_status('sticky')) ? __('Undo Sticky', 'asgaros-forum') : __('Sticky', 'asgaros-forum');
                    $menu .= '</span></a>';

                    if ($this->get_status('closed')) {
                        $menu .= '<a href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;closed"><span class="icon-unlocked"></span><span>'.__('Re-open', 'asgaros-forum').'</span></a>';
                    } else {
                        $menu .= '<a href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;closed"><span class="icon-lock"></span><span>'.__('Close', 'asgaros-forum').'</span></a>';
                    }
                }
            }
        }

        return $menu;
    }

    function get_parent_id($id, $location) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$location} WHERE id = %d;", $id));
    }

    function breadcrumbs() {
        $trail = '<span class="icon-home"></span><a href="'.$this->url_home.'">'.__('Forum', 'asgaros-forum').'</a>';

        if ($this->current_forum && $this->access) {
            $link = $this->get_link($this->current_forum, $this->url_forum);
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;<a href="'.$link.'">' . $this->get_name($this->current_forum, $this->table_forums) . '</a>';
        }

        if ($this->current_thread && $this->access) {
            $link = $this->get_link($this->current_thread, $this->url_thread);
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;<a href="'.$link.'">' . $this->cut_string($this->get_name($this->current_thread, $this->table_threads), 70) . '</a>';
        }

        if ($this->current_view == 'addpost' && $this->access) {
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;' . __('Post Reply', 'asgaros-forum');
        } else if ($this->current_view == 'editpost' && $this->access) {
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;' . __('Edit Post', 'asgaros-forum');
        } else if ($this->current_view == 'addthread' && $this->access) {
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
        $out = __('Pages:', 'asgaros-forum');
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

        return $out;
    }

    function delete_thread($thread_id, $admin_action = false) {
        global $wpdb;

        if ($this->is_moderator()) {
            if ($thread_id) {
                // Delete uploads
                $posts = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$this->table_posts} WHERE parent_id = %d;", $thread_id));
                foreach ($posts as $post) {
                    $this->remove_post_files($post->id);
                }

                $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_posts} WHERE parent_id = %d;", $thread_id));
                $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_threads} WHERE id = %d;", $thread_id));

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

        if ($this->is_moderator() && $newForumID && $this->element_exists($newForumID, $this->table_forums)) {
            $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET parent_id = {$newForumID} WHERE id = %d;", $this->current_thread));
            wp_redirect(html_entity_decode($this->url_thread . $this->current_thread));
            exit;
        }
    }

    function remove_post() {
        global $wpdb;
        $post_id = (isset($_GET['post']) && is_numeric($_GET['post'])) ? $_GET['post'] : 0;

        if ($this->is_moderator() && $this->element_exists($post_id, $this->table_posts)) {
            $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_posts} WHERE id = %d;", $post_id));
            $this->remove_post_files($post_id);
        }
    }

    function remove_post_files($post_id) {
        $path = $this->upload_path.$post_id.'/';

        if (is_dir($path)) {
            $files = array_diff(scandir($path), array('..', '.'));

            foreach ($files as $file) {
                unlink($path.basename($file));
            }

            rmdir($path);
        }
    }

    function get_thread_image($thread_id, $status) {
        global $user_ID;
        $unread_status = '';

        if ($thread_id) {
            $lastpost_data = $this->get_lastpost_data($thread_id, 'p.date, p.author_id', 'p');
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

        echo '<span class="icon-'.$status.$unread_status.'"></span>';
    }

    function change_status($property) {
        global $wpdb;
        $new_status = '';

        if ($this->is_moderator()) {
            if ($property == 'sticky') {
                $new_status .= ($this->get_status('sticky')) ? 'normal_' : 'sticky_';
                $new_status .= ($this->get_status('closed')) ? 'closed' : 'open';
            } else if ($property == 'closed') {
                $new_status .= ($this->get_status('sticky')) ? 'sticky_' : 'normal_';
                $new_status .= ($this->get_status('closed')) ? 'open' : 'closed';
            }

            $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET status = %s WHERE id = %d;", $new_status, $this->current_thread));
        }
    }

    function get_status($property) {
        global $wpdb;
        $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$this->table_threads} WHERE id = %d;", $this->current_thread));

        if ($property == 'sticky' && ($status == 'sticky_open' || $status == 'sticky_closed')) {
            return true;
        } else if ($property == 'closed' && ($status == 'normal_closed' || $status == 'sticky_closed')) {
            return true;
        } else {
            return false;
        }
    }

    function attach_files($post_id) {
        $files = array();
        $links = array();
        $path = $this->upload_path.$post_id.'/';

        // Register existing files
        if (isset($_POST['existingfile']) && !empty($_POST['existingfile'])) {
            foreach ($_POST['existingfile'] as $file) {
                if (is_dir($path) && file_exists($path.basename($file))) {
                    $links[] = $file;
                }
            }
        }

        // Remove deleted files
        if (isset($_POST['deletefile']) && !empty($_POST['deletefile'])) {
            foreach ($_POST['deletefile'] as $file) {
                if (is_dir($path) && file_exists($path.basename($file))) {
                    unlink($path.basename($file));
                }
            }
        }

        // Check for files to upload
        if (isset($_FILES['forumfile'])) {
            foreach ($_FILES['forumfile']['name'] as $index =>$tmpName) {
                if (empty($_FILES['forumfile']['error'][$index]) && !empty($_FILES['forumfile']['name'][$index])) {
                    $files[$index] = true;
                }
            }
        }

        // Upload them
        if (count($files) > 0) {
            if (!is_dir($this->upload_path)) {
                mkdir($this->upload_path);
            }

            if (!is_dir($path)) {
                mkdir($path);
            }

            foreach($files as $index => $name) {
                $temp = $_FILES['forumfile']['tmp_name'][$index];
                $name = sanitize_file_name(stripslashes($_FILES['forumfile']['name'][$index]));

                if (!empty($name)) {
                    move_uploaded_file($temp, $path.$name);
                    $links[] = $name;
                }
            }
        }

        // Remove folder if it is empty
        if (is_dir($path) && count(array_diff(scandir($path), array('..', '.'))) == 0) {
            rmdir($path);
        }

        return $links;
    }

    function file_list($post_id, $uploads, $frontend = false) {
        $path = $this->upload_path.$post_id.'/';
        $url = $this->upload_url.$post_id.'/';
        $uploads = maybe_unserialize($uploads);
        $upload_list = '';
        $upload_list_elements = '';

        if (!empty($uploads) && is_dir($path)) {
            foreach ($uploads as $upload) {
                if (file_exists($path.basename($upload))) {
                    if ($frontend) {
                        $upload_list_elements .= '<li><a href="'.$url.$upload.'" target="_blank">'.$upload.'</a></li>';
                    } else {
                        $upload_list_elements .= '<div class="uploaded-file">';
                        $upload_list_elements .= '<a href="'.$url.$upload.'" target="_blank">'.$upload.'</a> &middot; <a filename="'.$upload.'" class="delete">['.__('Delete', 'asgaros-forum').']</a>';
                        $upload_list_elements .= '<input type="hidden" name="existingfile[]" value="'.$upload.'" />';
                        $upload_list_elements .= '</div>';
                    }
                }
            }

            if (!empty($upload_list_elements)) {
                if ($frontend) {
                    $upload_list .= '<strong>'.__('Uploaded files:', 'asgaros-forum').'</strong>';
                    $upload_list .= '<ul>'.$upload_list_elements.'</ul>';
                } else {
                    $upload_list .= '<div class="editor-row">';
                    $upload_list .= '<div class="editor-cell"><span>'.__('Uploaded files:', 'asgaros-forum').'</span></div>';
                    $upload_list .= '<div class="editor-cell">';
                    $upload_list .= '<div class="files-to-delete"></div>';
                    $upload_list .= $upload_list_elements;
                    $upload_list .= '</div>';
                    $upload_list .= '</div>';
                }
            }
        }

        echo $upload_list;
    }
}
?>
