<?php
class asgarosforum {
    var $db_version = 1;
    var $delim = "";
    var $page_id = "";
    var $date_format = "";
    var $access = true;
    var $url_home = "";
    var $url_base = "";
    var $url_forum = "";
    var $url_thread = "";
    var $url_editor_thread = "";
    var $url_editor_post = "";
    var $upload_path = "";
    var $upload_url = "";
    var $table_categories = "";
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
        'forum_posts_per_page' => 10,
        'forum_threads_per_page' => 20,
        'forum_allow_file_uploads' => false
    );
    var $options_editor = array(
        'media_buttons' => false,
        'textarea_rows' => 5,
        'teeny' => true,
        'quicktags' => false
    );

    public function __construct() {
        global $wpdb;
        $this->options = array_merge($this->options_default, get_option('asgarosforum_options', array()));
        $this->page_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[forum]%' AND post_status = 'publish' AND post_type = 'page';");
        $this->date_format = get_option('date_format') . ', ' . get_option('time_format');
        $this->table_categories = $wpdb->prefix . "forum_categories";
        $this->table_forums = $wpdb->prefix . "forum_forums";
        $this->table_threads = $wpdb->prefix . "forum_threads";
        $this->table_posts = $wpdb->prefix . "forum_posts";

        register_activation_hook(__FILE__, array($this, 'install'));
        add_action('plugins_loaded', array($this, 'install'));
        add_action("init", array($this, 'prepare'));
        add_action("wp", array($this, 'check_access'));
        add_action("wp_enqueue_scripts", array($this, 'enqueue_front_scripts'));
        add_action("wp_head", array($this, 'setup_header'));
        add_filter("wp_title", array($this, "get_pagetitle"));
        add_shortcode('forum', array($this, "forum"));

        if (is_admin()) {
            asgarosforum_admin::load_hooks();
        }
    }

    public function install() {
        global $wpdb;
        $installed_ver = get_option("asgarosforum_db_version");

        if ($installed_ver != $this->db_version) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql1 = "
            CREATE TABLE $this->table_categories (
            id int(11) NOT NULL auto_increment,
            name varchar(255) NOT NULL default '',
            sort int(11) NOT NULL default '0',
            PRIMARY KEY  (id)
            ) $charset_collate;";

            $sql2 = "
            CREATE TABLE $this->table_forums (
            id int(11) NOT NULL auto_increment,
            name varchar(255) NOT NULL default '',
            parent_id int(11) NOT NULL default '0',
            description varchar(255) NOT NULL default '',
            sort int(11) NOT NULL default '0',
            PRIMARY KEY  (id)
            ) $charset_collate;";

            $sql3 = "
            CREATE TABLE $this->table_threads (
            id int(11) NOT NULL auto_increment,
            parent_id int(11) NOT NULL default '0',
            views int(11) NOT NULL default '0',
            name varchar(255) NOT NULL default '',
            status varchar(20) NOT NULL default 'normal_open',
            PRIMARY KEY  (id)
            ) $charset_collate;";

            $sql4 = "
            CREATE TABLE $this->table_posts (
            id int(11) NOT NULL auto_increment,
            text longtext,
            parent_id int(11) NOT NULL default '0',
            date datetime NOT NULL default '0000-00-00 00:00:00',
            author_id int(11) NOT NULL default '0',
            PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            dbDelta($sql1);
            dbDelta($sql2);
            dbDelta($sql3);
            dbDelta($sql4);

            update_option("asgarosforum_db_version", $this->db_version);
        }
    }

    public function prepare() {
        global $user_ID, $wp_rewrite;

        if (isset($_GET['forumaction'])) {
            $this->current_view = $_GET['forumaction'];
        }

        if (isset($_GET['part']) && $_GET['part'] > 0) {
            $this->current_page = ($_GET['part'] - 1);
        }

        switch ($this->current_view) {
            case 'viewforum':
            case 'addthread':
                $forum_id = $_GET['forum'];
                if ($this->element_exists($forum_id, $this->table_forums)) {
                    $this->current_forum = $forum_id;
                    $this->current_category = $this->get_parent_id($this->current_forum, $this->table_forums);
                }
                break;
            case 'movethread':
            case 'viewthread':
            case 'addpost':
                $thread_id = $_GET['thread'];
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

        if ($wp_rewrite->using_permalinks()) {
            $this->delim = "?";
        } else {
            $this->delim = "&amp;";
        }

        $this->url_home = get_permalink($this->page_id);;
        $this->url_base = $this->url_home . $this->delim . "forumaction=";
        $this->url_forum = $this->url_base . "viewforum&amp;forum=";
        $this->url_thread = $this->url_base . "viewthread&amp;thread=";
        $this->url_editor_thread = $this->url_base . "addthread&amp;forum={$this->current_forum}";
        $this->url_editor_post = $this->url_base . "addpost&amp;thread={$this->current_thread}";

        $upload_dir = wp_upload_dir();
        $this->upload_path = $upload_dir['basedir'].'/asgarosforum/';
        $this->upload_url = $upload_dir['baseurl'].'/asgarosforum/';

        // Set cookie
        if ($user_ID && !isset($_COOKIE['wpafcookie'])) {
            $last = get_user_meta($user_ID, 'asgarosforum_lastvisit', true);
            setcookie("wpafcookie", $last, 0, "/");
            update_user_meta($user_ID, 'asgarosforum_lastvisit', $this->current_time());
        }

        if ((isset($_POST['add_thread_submit']) || isset($_POST['add_post_submit']) || isset($_POST['edit_post_submit'])) && $user_ID) {
            require('insert.php');
        } else if (isset($_GET['forumaction']) && $_GET['forumaction'] == "markallread" && $user_ID) {
            $time = $this->current_time();
            setcookie("wpafcookie", $time, 0, "/");
            update_user_meta($user_ID, 'asgarosforum_lastvisit', $time);
            wp_redirect(html_entity_decode($this->url_home));
            exit;
        } else if (isset($_GET['move_thread'])) {
            $this->move_thread();
        } else if (isset($_GET['delete_thread'])) {
            $this->remove_thread();
        } else if (isset($_GET['remove_post'])) {
            $this->remove_post();
        } else if (isset($_GET['sticky'])) {
            $this->change_status('sticky');
        } else if (isset($_GET['closed'])) {
            $this->change_status('closed');
        }
    }

    public function check_access() {
        $this->access = apply_filters('asgarosforum_filter_check_access', true, $this->current_category);;
    }

    public function enqueue_front_scripts() {
        if (is_page($this->page_id)) {
            wp_enqueue_script('asgarosforum-js', plugin_dir_url(__FILE__).'js/script.js', array('jquery'));
        }
    }

    public function setup_header() {
        if (is_page($this->page_id)) {
            echo '<link rel="stylesheet" type="text/css" href="'.plugin_dir_url(__FILE__).'skin/style.css" />';
        }
    }

    public function get_pagetitle() {
        global $post;
        $default_title = $post->post_title;
        $title = "";

        switch ($this->current_view) {
            case "viewforum":
                if ($this->current_forum && $this->access) {
                    $title = $this->get_name($this->current_forum, $this->table_forums) . " - ";
                }
                break;
            case "viewthread":
                if ($this->current_thread && $this->access) {
                    $title = $this->get_name($this->current_thread, $this->table_threads) . " - ";
                }
                break;
            case "editpost":
                if ($this->access) {
                    $title = __("Edit Post", "asgarosforum") . " - ";
                }
                break;
            case "addpost":
                if ($this->access) {
                    $title = __("Post Reply", "asgarosforum") . " - ";
                }
                break;
            case "addthread":
                if ($this->access) {
                    $title = __("New Thread", "asgarosforum") . " - ";
                }
                break;
            case "movethread":
                if ($this->access) {
                    $title = __("Move Thread", "asgarosforum") . " - ";
                }
                break;
        }

        return $title . $default_title . ' | ';
    }

    public function forum() {
        global $wpdb, $user_ID;

        echo '<div id="wpf-wrapper">';
        echo '<div id="top-elements">'.$this->breadcrumbs().'</div>';

        switch ($this->current_view) {
            case 'movethread':
                $this->movethread();
                break;
            case 'viewforum':
                $this->showforum($this->current_forum);
                break;
            case 'viewthread':
                $this->showthread($this->current_thread);
                break;
            case 'addthread':
                include('views/editor.php');
                break;
            case 'addpost':
                include('views/editor.php');
                break;
            case 'editpost':
                include('views/editor.php');
                break;
            default:
                $this->overview();
                break;
        }

        echo '</div>';
    }

    public function overview() {
        $categories = $this->get_categories();

        if ($categories) {
            require('views/overview.php');
        } else {
            echo '<div class="notice">'.__("There are no categories yet!", "asgarosforum").'</div>';
        }
    }

    public function showforum() {
        if ($this->current_forum && $this->access) {
            $threads = $this->get_threads($this->current_forum);
            $sticky_threads = $this->get_threads($this->current_forum, 'sticky');
            $counter_normal = count($threads);
            $counter_total = $counter_normal + count($sticky_threads);

            require('views/forum.php');
        } else {
            echo '<div class="notice">'.__("Sorry, but this forum does not exist.", "asgarosforum").'</div>';
        }
    }

    public function showthread() {
        if ($this->current_thread && $this->access) {
            global $wpdb;
            $posts = $this->get_posts();

            if ($posts) {
                $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET views = views+1 WHERE id = %d", $this->current_thread));

                $meClosed = "";

                if ($this->get_status('closed')) {
                    $meClosed = '&nbsp;('.__("Thread closed", "asgarosforum").')';
                }

                require('views/thread.php');
            } else {
                echo '<div class="notice">'.__("Sorry, but there are no posts.", "asgarosforum").'</div>';
            }
        } else {
            echo '<div class="notice">'.__("Sorry, but this thread does not exist.", "asgarosforum").'</div>';
        }
    }

    public function movethread() {
        if ($this->is_moderator() && $this->access) {
            $strOUT = '
            <form method="post" action="' . $this->url_base . 'movethread&amp;thread=' . $this->current_thread . '&amp;move_thread">
            Move "<strong>' . $this->get_name($this->current_thread, $this->table_threads) . '</strong>" to new forum:<br />
            <select name="newForumID">';

            $frs = $this->get_forums();

            foreach ($frs as $f) {
                $strOUT .= '<option value="' . $f->id . '"' . ($f->id == $this->current_forum ? ' selected="selected"' : '') . '>' . $f->name . '</option>';
            }

            $strOUT .= '</select><br /><input type="submit" value="Go!" /></form>';

            echo $strOUT;
        } else {
            echo '<div class="notice">'.__("You are not allowed to move threads.", "asgarosforum").'</div>';
        }
    }

    public function element_exists($id, $location) {
        global $wpdb;

        if (!empty($id) && $wpdb->get_row($wpdb->prepare("SELECT id FROM {$location} WHERE id = %d;", $id))) {
            return true;
        } else {
            return false;
        }
    }

    public function get_link($id, $location, $page = 1) {
        $page_appendix = "";

        if ($page > 1) {
            $page_appendix = '&amp;part=' . $page;
        }

        return $location . $id . $page_appendix;
    }

    public function get_postlink($thread_id, $post_id, $page = 0) {
        global $wpdb;

        if (!$page) {
            $wpdb->query($wpdb->prepare("SELECT id FROM {$this->table_posts} WHERE parent_id = %d;", $thread_id));
            $page = ceil($wpdb->num_rows / $this->options['forum_posts_per_page']);
        }

        return $this->get_link($thread_id, $this->url_thread, $page) . '#postid-' . $post_id;
    }

    public function get_categories($disable_hooks = false) {
        global $wpdb;
        $filter = '';

        if (!$disable_hooks) {
            $filter = apply_filters('asgarosforum_filter_get_categories', $filter);
        }

        return $wpdb->get_results("SELECT name, id FROM {$this->table_categories} {$filter} ORDER BY sort ASC;");
    }

    public function get_forums($id = false) {
        global $wpdb;

        if ($id) {
            return $wpdb->get_results($wpdb->prepare("SELECT id, name, description FROM {$this->table_forums} WHERE parent_id = %d ORDER BY sort ASC;", $id));
        } else {
            return $wpdb->get_results("SELECT id, name, description FROM {$this->table_forums} ORDER BY sort ASC;");
        }
    }

    public function get_threads($id, $type = 'normal') {
        global $wpdb;
        $limit = "";

        if ($type == 'normal') {
            $start = $this->current_page * $this->options['forum_threads_per_page'];
            $end = $this->options['forum_threads_per_page'];
            $limit = $wpdb->prepare("LIMIT %d, %d", $start, $end);
        }

        return $wpdb->get_results($wpdb->prepare("SELECT t.id, t.name, t.views, t.status FROM {$this->table_threads} AS t WHERE t.parent_id = %d AND t.status LIKE %s ORDER BY (SELECT MAX(id) FROM {$this->table_posts} AS p WHERE p.parent_id = t.id) DESC {$limit};", $id, $type . '%'));
    }

    public function get_posts() {
        global $wpdb;
        $start = $this->current_page * $this->options['forum_posts_per_page'];
        $end = $this->options['forum_posts_per_page'];

        return $wpdb->get_results($wpdb->prepare("SELECT id, text, date, author_id FROM {$this->table_posts} WHERE parent_id = %d ORDER BY id ASC LIMIT %d, %d;", $this->current_thread, $start, $end));
    }

    public function is_first_post($post_id) {
        global $wpdb;
        $first_post = $wpdb->get_row("SELECT id FROM {$this->table_posts} WHERE parent_id = {$this->current_thread} ORDER BY id ASC LIMIT 1;");

        if ($first_post->id == $post_id) {
            return true;
        } else {
            return false;
        }
    }

    public function get_name($id, $location) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT name FROM {$location} WHERE id = %d;", $id));
    }

    public function cut_string($string, $length = 35) {
        if (strlen($string) > $length) {
            return substr($string, 0, $length) . ' ...';
        }

        return $string;
    }

    public function get_username($user_id, $wrap = false) {
        $user = get_userdata($user_id);

        if ($user) {
            if ($wrap) {
                return wordwrap($user->display_name, 22, "-<br/>", true);
            } else {
                return $user->display_name;
            }
        } else {
            return 'Deleted user';
        }
    }

    public function get_lastpost_in_thread($thread_id, $date_only = false) {
        global $wpdb;
        $post = $wpdb->get_row($wpdb->prepare("SELECT id, date, author_id FROM {$this->table_posts} WHERE parent_id = %d ORDER BY id DESC LIMIT 1;", $thread_id));

        if ($post) {
            if ($date_only) {
                return $post->date;
            } else {
                $link = $this->get_postlink($thread_id, $post->id);
                return __("Last post by", "asgarosforum").'&nbsp;<strong>'.$this->get_username($post->author_id).'</strong><br />'.__("on", "asgarosforum").'&nbsp;<a href="'.$link.'">'.$this->format_date($post->date).'&nbsp;'.__("Uhr", "asgarosforum").'</a>';
            }
        } else {
            return false;
        }
    }

    public function get_lastpost_in_forum($forum_id) {
        global $wpdb;
        $post = $wpdb->get_row($wpdb->prepare("SELECT p.id, p.date, p.parent_id, p.author_id FROM {$this->table_posts} AS p INNER JOIN {$this->table_threads} AS t ON p.parent_id=t.id WHERE t.parent_id = %d ORDER BY p.id DESC LIMIT 1;", $forum_id));

        if (!$post) {
            return __("No threads yet!", "asgarosforum");
        }

        $date = $this->format_date($post->date);

        return __("Last post by", "asgarosforum")."&nbsp;<strong>".$this->get_username($post->author_id)."</strong><br />
        ".__("in", "asgarosforum")."&nbsp;<strong>".$this->cut_string($this->get_name($post->parent_id, $this->table_threads))."</strong><br />
        ".__("on", "asgarosforum")."&nbsp;<a href='".$this->get_postlink($post->parent_id, $post->id)."'>".$date."&nbsp;".__("Uhr", "asgarosforum")."</a>";
    }

    public function get_lastpost_data($id, $data, $location) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT {$this->table_posts}.{$data} FROM {$this->table_posts} INNER JOIN {$this->table_threads} ON {$this->table_posts}.parent_id={$this->table_threads}.id WHERE {$location}.parent_id = %d ORDER BY {$this->table_posts}.date DESC LIMIT 1;", $id));
    }

    public function get_thread_starter($thread_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT author_id FROM {$this->table_posts} WHERE parent_id = %d ORDER BY id ASC LIMIT 1;", $thread_id));
    }

    public function check_unread($thread_id) {
        global $user_ID;
        $lastpost_time = $this->get_lastpost_in_thread($thread_id, true);
        $lastpost_author_id = $this->get_lastpost_data($thread_id, 'author_id', $this->table_posts);

        if ($lastpost_time && $user_ID != $lastpost_author_id) {
            $lp = strtotime($lastpost_time);
            $lv = strtotime($this->last_visit());

            if ($lp > $lv) {
                return 'yes';
            }
        }

        return 'no';
    }

    public function post_menu($post_id, $author_id, $counter) {
        global $user_ID;

        $o = '<table><tr>';

        if ($user_ID && (!$this->get_status('closed') || $this->is_moderator())) {
            $o .= '<td><span class="icon-quotes-left"></span><a href="'.$this->url_editor_post.'&amp;quote='.$post_id.'">'.__("Quote", "asgarosforum").'</a></td>';
        }

        if (($counter > 1 || $this->current_page >= 1) && $this->is_moderator()) {
            $o .= '<td><span class="icon-bin"></span><a onclick="return confirm(\'Are you sure you want to remove this?\');" href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;remove_post&amp;id='.$post_id.'">'.__("Remove", "asgarosforum").'</a></td>';
        }

        if (($this->is_moderator()) || $user_ID == $author_id) {
            $o .= '<td><span class="icon-pencil2"></span><a href="'.$this->url_base.'editpost&amp;id='.$post_id.'&amp;part='.($this->current_page + 1).'">'.__("Edit", "asgarosforum").'</a></td>';
        }

        $o .= '<td><a href="'.$this->get_postlink($this->current_thread, $post_id, ($this->current_page + 1)).'" title="'.__("Permalink", "asgarosforum").'"><span class="icon-link"></span></a></td>';
        $o .= '</tr></table>';

        return $o;
    }

    public function format_date($date) {
        return date_i18n($this->date_format, strtotime($date));
    }

    public function current_time() {
        return current_time('Y-m-d H:i:s');
    }

    public function count_userposts($author_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT count(id) FROM {$this->table_posts} WHERE author_id = %d;", $author_id));
    }

    public function get_post_author($post_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT author_id FROM {$this->table_posts} WHERE id = %d;", $post_id));
    }

    public function count_elements($id, $location) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$location} WHERE parent_id = %d;", $id));
    }

    public function count_posts_in_forum($forum_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT({$this->table_posts}.id) FROM {$this->table_posts} INNER JOIN {$this->table_threads} ON {$this->table_posts}.parent_id={$this->table_threads}.id WHERE {$this->table_threads}.parent_id = %d;", $forum_id));
    }

    public function is_moderator() {
        global $user_ID;

        if ($user_ID && is_super_admin($user_ID)) {
            return true;
        }

        return false;
    }

    public function forum_menu() {
        global $user_ID;

        if ($user_ID) {
            echo '<table><tr><td><a href="'.$this->url_editor_thread.'"><span class="icon-file-empty"></span><span>'.__("New Thread", "asgarosforum").'</span></a></td></tr></table>';
        }
    }

    public function thread_menu() {
        global $user_ID;
        $menu = '';

        if ($user_ID) {
            $menu .= '<table><tr>';

            if (!$this->get_status('closed') || $this->is_moderator()) {
                $menu .= '<td><a href="'.$this->url_editor_post.'"><span class="icon-bubble2"></span><span>'.__("Reply", "asgarosforum").'</span></a></td>';
            }

            if ($this->is_moderator()) {
                $menu .= '<td><a href="'.$this->url_base.'movethread&amp;thread='.$this->current_thread.'"><span class="icon-shuffle"></span><span>'.__("Move Thread", "asgarosforum").'</span></a></td>';
                $menu .= '<td><a href="'.$this->url_thread.$this->current_thread.'&amp;delete_thread" onclick="return confirm(\'Are you sure you want to remove this?\');"><span class="icon-bin"></span><span>'.__("Delete Thread", "asgarosforum").'</span></a></td>';

                $menu .= '<td><a href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;sticky"><span class="icon-pushpin"></span><span>';
                if ($this->get_status('sticky')) {
                    $menu .= __("Undo Sticky", "asgarosforum");
                } else {
                    $menu .= __("Sticky", "asgarosforum");
                }
                $menu .= '</span></a></td>';

                if ($this->get_status('closed')) {
                    $menu .= '<td><a href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;closed"><span class="icon-unlocked"></span><span>'.__("Re-open", "asgarosforum").'</span></a></td>';
                } else {
                    $menu .= '<td><a href="'.$this->get_link($this->current_thread, $this->url_thread).'&amp;closed"><span class="icon-lock"></span><span>'.__("Close", "asgarosforum").'</span></a></td>';
                }
            }

            $menu .= '</tr></table>';
        }

        return $menu;
    }

    public function get_parent_id($id, $location) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$location} WHERE id = %d;", $id));
    }

    public function breadcrumbs() {
        $trail = '<span class="icon-home"></span><a href="' . $this->url_home . '">' . __("Forum", "asgarosforum") . '</a>';

        if ($this->current_forum && $this->access) {
            $link = $this->get_link($this->current_forum, $this->url_forum);
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;<a href="'.$link.'">' . $this->get_name($this->current_forum, $this->table_forums) . '</a>';
        }

        if ($this->current_thread && $this->access) {
            $link = $this->get_link($this->current_thread, $this->url_thread);
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;<a href="'.$link.'">' . $this->cut_string($this->get_name($this->current_thread, $this->table_threads), 70) . '</a>';
        }

        if ($this->current_view == 'addpost' && $this->access) {
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;' . __("Post Reply", "asgarosforum");
        } else if ($this->current_view == 'editpost' && $this->access) {
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;' . __("Edit Post", "asgarosforum");
        } else if ($this->current_view == 'addthread' && $this->access) {
            $trail .= '&nbsp;<span class="sep">&rarr;</span>&nbsp;' . __("New Thread", "asgarosforum");
        }

        return '<div class="breadcrumbs">'.$trail.'</div>';
    }

    public function last_visit() {
        global $user_ID;

        if ($user_ID && isset($_COOKIE['wpafcookie'])) {
            return $_COOKIE['wpafcookie'];
        } else {
            return "0000-00-00 00:00:00";
        }
    }

    public function pageing($location) {
        global $wpdb;
        $out = __("Pages:", "asgarosforum");
        $num_pages = 0;
        $select_source = '';
        $select_url = '';

        if ($location == $this->table_posts) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT count(id) FROM {$location} WHERE parent_id = %d;", $this->current_thread));
            $num_pages = ceil($count / $this->options['forum_posts_per_page']);
            $select_source = $this->current_thread;
            $select_url = $this->url_thread;
        } else if ($location == $this->table_threads) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT count(id) FROM {$location} WHERE parent_id = %d AND status LIKE %s;", $this->current_forum, "normal%"));
            $num_pages = ceil($count / $this->options['forum_threads_per_page']);
            $select_source = $this->current_forum;
            $select_url = $this->url_forum;
        }

        if ($num_pages <= 6) {
            for ($i = 0; $i < $num_pages; ++$i) {
                if ($i == $this->current_page) {
                    $out .= " <strong>" . ($i + 1) . "</strong>";
                } else {
                    $out .= " <a href='" . $this->get_link($select_source, $select_url, ($i + 1)) . "'>" . ($i + 1) . "</a>";
                }
            }
        } else {
            if ($this->current_page >= 4) {
                $out .= " <a href='" . $this->get_link($select_source, $select_url) . "'>" . __("First", "asgarosforum") . "</a> <<";
            }

            for ($i = 3; $i > 0; $i--) {
                if ((($this->current_page + 1) - $i) > 0) {
                    $out .= " <a href='" . $this->get_link($select_source, $select_url, (($this->current_page + 1) - $i)) . "'>" . (($this->current_page + 1) - $i) . "</a>";
                }
            }

            $out .= " <strong>" . ($this->current_page + 1) . "</strong>";

            for ($i = 1; $i <= 3; $i++) {
                if ((($this->current_page + 1) + $i) <= $num_pages) {
                    $out .= " <a href='" . $this->get_link($select_source, $select_url, (($this->current_page + 1) + $i)) . "'>" . (($this->current_page + 1) + $i) . "</a>";
                }
            }

            if ($num_pages - $this->current_page >= 5) {
                $out .= " >> <a href='" . $this->get_link($select_source, $select_url, $num_pages) . "'>" . __("Last", "asgarosforum") . "</a>";
            }
        }

        return $out;
    }

    public function remove_thread() {
        global $wpdb;

        if ($this->is_moderator()) {
            if ($this->current_thread) {
                // Delete uploads
                $posts = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$this->table_posts} WHERE parent_id = %d;", $this->current_thread));
                foreach ($posts as $post) {
                    $this->remove_post_files($post->id);
                }

                $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_posts} WHERE parent_id = %d;", $this->current_thread));
                $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_threads} WHERE id = %d;", $this->current_thread));
                wp_redirect(html_entity_decode($this->url_forum . $this->current_forum));
                exit;
            }
        }
    }

    public function move_thread() {
        global $wpdb;
        $newForumID = $_POST['newForumID'];

        if ($this->is_moderator() && $newForumID && $this->element_exists($newForumID, $this->table_forums)) {
            $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET parent_id = {$newForumID} WHERE id = %d;", $this->current_thread));
            wp_redirect(html_entity_decode($this->url_thread . $this->current_thread));
            exit;
        }
    }

    public function remove_post() {
        global $wpdb;
        $post_id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : 0;

        if ($this->is_moderator() && $this->element_exists($post_id, $this->table_posts)) {
            $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_posts} WHERE id = %d;", $post_id));
            $this->remove_post_files($post_id);
        }
    }

    public function remove_post_files($post_id) {
        $path = $this->upload_path.$post_id.'/';

        if (is_dir($path)) {
            $files = array_diff(scandir($path), array('..', '.'));

            foreach ($files as $file) {
                unlink($path.basename($file));
            }

            rmdir($path);
        }
    }

    public function get_thread_image($thread_id, $status) {
        $unread_status = $this->check_unread($thread_id);

        echo '<span class="icon-' . $status . '-' . $unread_status . '"></span>';
    }

    public function autoembed($string) {
        global $wp_embed;
        return $wp_embed->autoembed($string);
    }

    public function change_status($property) {
        global $wpdb;
        $new_status = '';

        if ($this->is_moderator()) {
            if ($property == 'sticky') {
                if ($this->get_status('sticky')) {
                    $new_status .= 'normal_';
                } else {
                    $new_status .= 'sticky_';
                }

                if ($this->get_status('closed')) {
                    $new_status .= 'closed';
                } else {
                    $new_status .= 'open';
                }
            } else if ($property == 'closed') {
                if ($this->get_status('sticky')) {
                    $new_status .= 'sticky_';
                } else {
                    $new_status .= 'normal_';
                }

                if ($this->get_status('closed')) {
                    $new_status .= 'open';
                } else {
                    $new_status .= 'closed';
                }
            }

            $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET status = %s WHERE id = %d;", $new_status, $this->current_thread));
        }
    }

    public function get_status($property) {
        global $wpdb;
        $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$this->table_threads} WHERE id = %d;", $this->current_thread));

        if ($property == 'sticky') {
            if ($status == 'sticky_open' || $status == 'sticky_closed') {
                return true;
            } else {
                return false;
            }
        } else if ($property == 'closed') {
            if ($status == 'normal_closed' || $status == 'sticky_closed') {
                return true;
            } else {
                return false;
            }
        }
    }

    public function attach_files($post_id) {
        $files = array();
        $path = $this->upload_path.$post_id.'/';
        $url = $this->upload_url.$post_id.'/';
        $list = '';
        $links = '';

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
            if (!is_dir($path)) {
                mkdir($path);
            }

            foreach($files as $index => $name) {
                $temp = $_FILES['forumfile']['tmp_name'][$index];
                $name = sanitize_file_name(stripslashes($_FILES['forumfile']['name'][$index]));

                if (!empty($name)) {
                    move_uploaded_file($temp, $path.$name);
                    $links .= '<li><a href="'.$url.$name.'" target="_blank">'.$name.'</a></li>';
                }
            }

            if (!empty($links)) {
                $list .= '<p><strong>'.__("Uploaded files:", "asgarosforum").'</strong></p>';
                $list .= '<ul>';
                $list .= $links;
                $list .= '</ul>';
            }
        }

        // Remove folder if it is empty
        if (is_dir($path) && count(array_diff(scandir($path), array('..', '.'))) == 0) {
            rmdir($path);
        }

        return $list;
    }

    public function file_list($post_id) {
        $path = $this->upload_path.$post_id.'/';
        $url = $this->upload_url.$post_id.'/';

        if (is_dir($path) && count(glob($path.'*')) != 0) {
            $files = array_diff(scandir($path), array('..', '.'));

            echo '<tr>';
            echo '<td>'.__("Uploaded files:", "asgarosforum").'</td>';
            echo '<td>';
            echo '<div class="files-to-delete"></div>';
            foreach ($files as $file) {
                echo '<div class="uploaded-file">';
                echo '<a href="'.$url.$file.'" target="_blank">'.$file.'</a> &middot; <a filename="'.$file.'" class="delete">['.__("Delete", "asgarosforum").']</a>';
                echo '</div>';
            }
            echo '</td>';
            echo '</tr>';
        }
    }
}
?>
