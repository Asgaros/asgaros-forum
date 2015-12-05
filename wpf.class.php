<?php

if (!class_exists('asgarosforum')) {
    class asgarosforum {
        var $db_version = 1; // MANAGES DB VERSION
        var $delim = "";
        var $page_id = "";
        var $date_format = "";
        var $url_home = "";
        var $url_base = "";
        var $url_forum = "";
        var $url_thread = "";
        var $url_add_thread = "";
        var $url_post_reply = "";
        var $table_categories = "";
        var $table_forums = "";
        var $table_threads = "";
        var $table_posts = "";
        var $current_forum = "";
        var $current_thread = "";
        var $current_page = "";
        var $current_view = "";
        var $options = array();
        var $options_default = array(
            'forum_posts_per_page' => 10,
            'forum_threads_per_page' => 20,
            'forum_allow_image_uploads' => false,
            'forum_display_name' => 'user_login'
        );
        var $options_editor = array(
            'media_buttons' => false,
            'textarea_rows' => 5,
            'teeny' => true,
            'quicktags' => false
        );

        public function __construct() {
            global $wpdb;
            $this->options = array_merge($this->options_default, get_option('asgarosforum_options', array())); // Merge defaults with user's settings
            $this->page_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[asgarosforum]%' AND post_status = 'publish' AND post_type = 'page'");
            $this->date_format = get_option('date_format') . ', ' . get_option('time_format');
            $this->table_categories = $wpdb->prefix . "forum_categories";
            $this->table_forums = $wpdb->prefix . "forum_forums";
            $this->table_threads = $wpdb->prefix . "forum_threads";
            $this->table_posts = $wpdb->prefix . "forum_posts";
            $this->current_forum = false;
            $this->current_thread = false;
            $this->current_page = 0;
            $this->current_view = false;

            register_activation_hook(__FILE__, array($this, 'install'));
            add_action('plugins_loaded', array($this, 'install'));
            add_action("init", array($this, 'prepare'));
            add_action("wp_head", array($this, 'setup_header'));
            add_filter("wp_title", array($this, "get_pagetitle"), 10000, 2);
            add_shortcode('asgarosforum', array($this, "forum"));

            AFAdmin::load_hooks();
        }

        public function install() {
            global $wpdb;
            $installed_ver = get_option("asgarosforum_db_version");

            // Only run if we need to
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
                status varchar(20) NOT NULL default 'open',
                closed int(11) NOT NULL default '0',
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
            global $user_ID;
            global $wp_rewrite;

            if (isset($_GET['forumaction'])) {
                $this->current_view = $_GET['forumaction'];
            }

            if (isset($_GET['part']) && $_GET['part'] > 0) {
                $this->current_page = ($_GET['part'] - 1);
            }

            if ($this->current_view) {
                switch ($this->current_view) {
                    case 'viewforum':
                    case 'addthread':
                        $forum_id = $_GET['forum'];
                        if ($this->forum_exists($forum_id)) {
                            $this->current_forum = $forum_id;
                        }
                        break;
                    case 'movethread':
                    case 'viewthread':
                    case 'postreply':
                        $thread_id = $_GET['thread'];
                        if ($this->thread_exists($thread_id)) {
                            $this->current_forum = $this->get_parent_id(THREAD, $thread_id);
                            $this->current_thread = $thread_id;
                        }
                        break;
                    case 'editpost':
                        $post_id = $_GET['id'];
                        if ($this->post_exists($post_id)) {
                            $this->current_thread = $this->get_parent_id(POST, $post_id);
                            $this->current_forum = $this->get_parent_id(THREAD, $this->current_thread);
                        }
                        break;
                }
            }

            if ($wp_rewrite->using_permalinks()) {
                $this->delim = "?";
            } else {
                $this->delim = "&amp;";
            }

            $perm = get_permalink($this->page_id);
            $this->url_home = $perm;
            $this->url_base = $perm . $this->delim . "forumaction=";
            $this->url_forum = $this->url_base . "viewforum&amp;forum=";
            $this->url_thread = $this->url_base . "viewthread&amp;thread=";
            $this->url_add_thread = $this->url_base . "addthread&amp;forum={$this->current_forum}";
            $this->url_post_reply = $this->url_base . "postreply&amp;thread={$this->current_thread}";

            // Set cookie
            if ($user_ID && !isset($_COOKIE['wpafcookie'])) {
                $last = get_user_meta($user_ID, 'asgarosforum_lastvisit', true);
                setcookie("wpafcookie", $last, 0, "/");
                update_user_meta($user_ID, 'asgarosforum_lastvisit', $this->wpf_current_time_fixed());
            }

            // Handle inserts
            if (isset($_POST['add_thread_submit']) || isset($_POST['add_post_submit']) || isset($_POST['edit_post_submit'])) {
                require('wpf-insert.php');
            }

            if (isset($_GET['forumaction']) && $_GET['forumaction'] == "markallread") {
                $this->markallread();
            }

            if (isset($_GET['move_thread'])) {
                $this->move_thread();
            }

            if (isset($_GET['delete_thread'])) {
                $this->remove_thread();
            }
        }

        public function setup_header() {
            if (is_page($this->page_id)) {
                echo '<link rel="stylesheet" type="text/css" href="'.plugin_dir_url(__FILE__).'skin/style.css" />';
            }
        }

        public function forum_exists($id) {
            global $wpdb;

            if (!empty($id) && $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_forums} WHERE id = %d", $id))) {
                return true;
            } else {
                return false;
            }
        }

        public function thread_exists($id) {
            global $wpdb;

            if (!empty($id) && $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_threads} WHERE id = %d", $id))) {
                return true;
            } else {
                return false;
            }
        }

        public function post_exists($id) {
            global $wpdb;

            if (!empty($id) && $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_posts} WHERE id = %d", $id))) {
                return true;
            } else {
                return false;
            }
        }

        public function get_forumlink($id, $page = 1) {
            $page_appendix = "";

            if ($page > 1) {
                $page_appendix = '&amp;part=' . $page;
            }

            return $this->url_forum . $id . $page_appendix;
        }

        public function get_threadlink($id, $page = 1) {
            $page_appendix = "";

            if ($page > 1) {
                $page_appendix = '&amp;part=' . $page;
            }

            return $this->url_thread . $id . $page_appendix;
        }

        public function get_postlink($id, $postid, $page = 0) {
            $num = 0;

            if (!$page) {
                global $wpdb;
                $wpdb->query($wpdb->prepare("SELECT * FROM {$this->table_posts} WHERE parent_id = %d", $id));
                $num = ceil($wpdb->num_rows / $this->options['forum_posts_per_page']);
            } else {
                $num = $page;
            }

            return $this->get_threadlink($id, $num) . '#postid-' . $postid;
        }

        public function get_categories() {
            global $wpdb;

            return $wpdb->get_results("SELECT * FROM {$this->table_categories} ORDER BY sort DESC");
        }

        public function getable_forums($id = '') {
            global $wpdb;

            if ($id) {
                return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_forums} WHERE parent_id = %d ORDER BY SORT DESC", $id));
            } else {
                return $wpdb->get_results("SELECT * FROM {$this->table_forums} ORDER BY sort DESC");
            }
        }

        public function getable_threads($id, $type = 'open') {
            global $wpdb;
            $limit = "";

            if ($type == 'open') {
                $start = $this->current_page * $this->options['forum_threads_per_page'];
                $end = $this->options['forum_threads_per_page'];
                $limit = $wpdb->prepare("LIMIT %d, %d", $start, $end);
            }

            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_threads} AS t WHERE t.parent_id = %d AND t.status = '{$type}' ORDER BY (SELECT MAX(date) FROM {$this->table_posts} AS p WHERE p.parent_id = t.id) DESC {$limit}", $id));
        }

        public function getable_posts($thread_id) {
            global $wpdb;
            $start = $this->current_page * $this->options['forum_posts_per_page'];
            $end = $this->options['forum_posts_per_page'];

            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_posts} WHERE parent_id = %d ORDER BY date ASC LIMIT %d, %d", $thread_id, $start, $end));
        }

        public function get_name($id, $location) {
            global $wpdb;
            return $wpdb->get_var($wpdb->prepare("SELECT name FROM {$location} WHERE id = %d", $id));
        }

        public function cut_string($string, $length = 35) {
            if (strlen($string) > $length) {
                return substr($string, 0, $length) . ' ...';
            }

            return $string;
        }

        public function forum() {
            global $wpdb, $user_ID;

            echo '<div id="wpf-wrapper">';

            echo '<div id="top-elements">'.$this->breadcrumbs().'</div>';

            if ($this->current_view) {
                switch ($this->current_view) {
                    case 'movethread':
                        $this->movethread();
                        break;
                    case 'viewforum':
                        $this->showforum($_GET['forum']);
                        break;
                    case 'viewthread':
                        $this->showthread($_GET['thread']);
                        break;
                    case 'addthread':
                        include('views/editor.php');
                        break;
                    case 'postreply':
                        if ($this->is_closed($_GET['thread']) && !$this->is_moderator($user_ID)) {
                            echo '<div class="notice">'.__("Sorry, but you are not allowed to do this.", "asgarosforum").'</div>';
                        } else {
                            include('views/editor.php');
                        }
                        break;
                    case 'editpost':
                        include('views/editor.php');
                        break;
                }
            } else {
                $this->overview();
            }

            echo '</div>';
        }

        public function get_userdata($user_id, $data) {
            $user = get_userdata($user_id);

            if ($user) {
                return $user->$data;
            } else {
                return 'Deleted user';
            }
        }

        public function get_lastpost($thread_id) {
            global $wpdb;
            $post = $wpdb->get_row($wpdb->prepare("SELECT date, author_id, id FROM {$this->table_posts} WHERE parent_id = %d ORDER BY date DESC LIMIT 1", $thread_id));
            $link = $this->get_postlink($thread_id, $post->id);
            echo __("Last post by", "asgarosforum") . ' <strong>' . $this->profile_link($post->author_id) . '</strong><br />on <a href="'.$link.'">'.date_i18n($this->date_format, strtotime($post->date)).'&nbsp;Uhr</a>';
        }

        public function showforum($forum_id) {
            if ($this->forum_exists($forum_id)) {
                global $user_ID, $wpdb;

                $threads = $this->getable_threads($forum_id);
                $sticky_threads = $this->getable_threads($forum_id, 'sticky');
                $thread_counter = (count($threads) + count($sticky_threads));

                require('views/forum.php');
            } else {
                echo '<div class="notice">'.__("Sorry, but this forum does not exist.", "asgarosforum").'</div>';
            }
        }

        public function get_starter($thread_id) {
            global $wpdb;
            return $wpdb->get_var($wpdb->prepare("SELECT author_id FROM {$this->table_posts} WHERE parent_id = %d ORDER BY id ASC LIMIT 1", $thread_id));
        }

        public function check_unread($thread_id) {
            global $user_ID;
            $image = "";

            if ($user_ID) {
                $poster_id = $this->last_posterid($thread_id, $this->table_posts);

                if ($user_ID != $poster_id) {
                    $lp = strtotime($this->last_poster_in_thread($thread_id));
                    $lv = strtotime($this->last_visit());

                    if ($lp > $lv) {
                        return true;
                    }
                }
            }

            return false;

        }

        public function showthread($thread_id) {
            if ($this->thread_exists($thread_id)) {
                global $wpdb, $user_ID;

                if (isset($_GET['remove_post'])) {
                    $this->remove_post();
                }

                if (isset($_GET['sticky'])) {
                    $this->sticky_post();
                }

                if (isset($_GET['closed'])) {
                    $this->closed_post();
                }

                $posts = $this->getable_posts($thread_id);

                if ($posts) {
                    $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET views = views+1 WHERE id = %d", $thread_id));

                    $meClosed = "";

                    if ($this->is_closed()) {
                        $meClosed = "&nbsp;(" . __("Thread closed", "asgarosforum") . ") ";
                    } else {
                        $meClosed = "";
                    }

                    require('views/thread.php');
                } else {
                    echo '<div class="notice">'.__("Sorry, but there are no posts.", "asgarosforum").'</div>';
                }
            } else {
                echo '<div class="notice">'.__("Sorry, but this thread does not exist.", "asgarosforum").'</div>';
            }
        }

        public function get_postmeta($post_id, $author_id, $parent_id, $counter) {
            global $user_ID;

            $o = "<table><tr>";

            if ($user_ID && (!$this->is_closed() || $this->is_moderator($user_ID))) {
                $o .= "<td><span class='icon-quotes-left'></span><a href='{$this->url_post_reply}&amp;quote={$post_id}'>" . __("Quote", "asgarosforum") . "</a></td>";
            }

            if ($counter > 1) {
                if ($this->is_moderator($user_ID)) {
                    $o .= "<td><span class='icon-bin'></span><a onclick=\"return confirm('Are you sure you want to remove this?');\" href='" . $this->get_threadlink($this->current_thread) . "&amp;remove_post&amp;id={$post_id}'>" . __("Remove", "asgarosforum") . "</a></td>";
                }
            }

            if (($this->is_moderator($user_ID)) || ($user_ID == $author_id && $user_ID)) {
                $o .= "<td><span class='icon-pencil2'></span><a href='" . $this->url_base . "editpost&amp;id={$post_id}&amp;part=".($this->current_page + 1)."'>" . __("Edit", "asgarosforum") . "</a></td>";
            }

            $o .= "<td><a href='" . $this->get_postlink($parent_id, $post_id, ($this->current_page + 1)) . "' title='" . __("Permalink", "asgarosforum") . "'><span class='icon-link'></span></a></td>";
            $o .= "</tr></table>";

            return $o;
        }

        public function format_date($date) {
            return date_i18n($this->date_format, strtotime($date));
        }

        public function wpf_current_time_fixed() {
            return gmdate('Y-m-d H:i:s', (time() + (get_option('gmt_offset') * 3600)));
        }

        public function get_userposts_num($id) {
            global $wpdb;
            return $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$this->table_posts} WHERE author_id = %d", $id));
        }

        public function get_post_owner($id) {
            global $wpdb;
            return $wpdb->get_var($wpdb->prepare("SELECT author_id FROM {$this->table_posts} WHERE id = %d", $id));
        }

        public function overview() {
            global $user_ID;
            $grs = $this->get_categories();
            require('views/overview.php');
        }

        public function last_posterid($id, $location) {
            global $wpdb;
            return $wpdb->get_var($wpdb->prepare("SELECT {$this->table_posts}.author_id FROM {$this->table_posts} INNER JOIN {$this->table_threads} ON {$this->table_posts}.parent_id={$this->table_threads}.id WHERE {$location}.parent_id = %d ORDER BY {$this->table_posts}.date DESC", $id));
        }

        public function num_threads($forum) {
            global $wpdb;
            return $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$this->table_threads} WHERE parent_id = %d", $forum));
        }

        public function num_posts_forum($forum) {
            global $wpdb;
            return $wpdb->get_var($wpdb->prepare("SELECT COUNT({$this->table_posts}.id) FROM {$this->table_posts} INNER JOIN {$this->table_threads} ON {$this->table_posts}.parent_id={$this->table_threads}.id WHERE {$this->table_threads}.parent_id = %d ORDER BY {$this->table_posts}.date DESC", $forum));
        }

        public function num_posts($thread_id) {
            global $wpdb;
            return $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$this->table_posts} WHERE parent_id = %d", $thread_id));
        }

        public function last_poster_in_forum($forum, $post_date = false) {
            global $wpdb;

            $date = $wpdb->get_row($wpdb->prepare("SELECT {$this->table_posts}.date, {$this->table_posts}.id, {$this->table_posts}.parent_id, {$this->table_posts}.author_id FROM {$this->table_posts} INNER JOIN {$this->table_threads} ON {$this->table_posts}.parent_id={$this->table_threads}.id WHERE {$this->table_threads}.parent_id = %d ORDER BY {$this->table_posts}.date DESC", $forum));

            if ($post_date && is_object($date)) {
                return $date->date;
            }

            if (!$date) {
                return __("No threads yet!", "asgarosforum");
            }

            $d = date_i18n($this->date_format, strtotime($date->date));

            return "
            <strong>" . __("Last post", "asgarosforum") . "</strong> " . __("by", "asgarosforum") . " " . $this->profile_link($date->author_id) . "<br />
            " . __("in", "asgarosforum") . " <a href='" . $this->get_postlink($date->parent_id, $date->id) . "'>" . $this->cut_string($this->get_name($date->parent_id, $this->table_threads)) . "</a><br />
            " . __("on", "asgarosforum") . " {$d} Uhr";
        }

        public function last_poster_in_thread($thread_id) {
            global $wpdb;
            return $wpdb->get_var("SELECT date FROM {$this->table_posts} WHERE parent_id = {$thread_id} ORDER BY date DESC");
        }

        public function get_pagetitle($bef_title, $sep) {
            global $post;
            $default_title = $post->post_title;
            $action = "";
            $title = "";

            if (isset($_GET['forumaction']) && !empty($_GET['forumaction'])) {
                $action = $_GET['forumaction'];
            }

            switch ($action) {
                case "viewforum":
                    $title = $default_title . " - " . $this->get_name($_GET['forum'], $this->table_forums);
                    break;
                case "viewthread":
                    $title = $default_title . " - " . $this->get_name($this->get_parent_id(THREAD, $_GET['thread']), $this->table_forums) . " - " . $this->get_name($_GET['thread'], $this->table_threads);
                    break;
                case "editpost":
                    $title = $default_title . " - " . __("Edit Post", "asgarosforum");
                    break;
                case "postreply":
                    $title = $default_title . " - " . __("Post Reply", "asgarosforum");
                    break;
                case "addthread":
                    $title = $default_title . " - " . __("New Thread", "asgarosforum");
                    break;
                default:
                    $title = $default_title;
                    break;
            }

            return $title . ' | ';
        }

        public function is_moderator($user_id) {
            if ($user_id && is_super_admin($user_id)) {
                return true;
            }

            return false;
        }

        public function forum_menu() {
            global $user_ID;

            if ($user_ID) {
                $menu = "<table class='menu'><tr><td><a href='" . $this->url_add_thread . "'><span class='icon-file-empty'></span><span>" . __("New Thread", "asgarosforum") . "</span></a></td></tr></table>";
                return $menu;
            }
        }

        public function thread_menu() {
            global $user_ID;
            $menu = "";
            $stick = "";
            $closed = "";

            if ($user_ID) {
                if ($this->is_moderator($user_ID)) {
                    if ($this->is_sticky()) {
                        $stick = "<td><a href='" . $this->get_threadlink($this->current_thread) . "&amp;sticky'><span class='icon-pushpin'></span><span>" . __("Undo Sticky", "asgarosforum") . "</span></a></td>";
                    } else {
                        $stick = "<td><a href='" . $this->get_threadlink($this->current_thread) . "&amp;sticky'><span class='icon-pushpin'></span><span>" . __("Sticky", "asgarosforum") . "</span></a></td>";
                    }

                    if ($this->is_closed()) {
                        $closed = "<td><a href='" . $this->get_threadlink($this->current_thread) . "&amp;closed=0'><span class=' icon-unlocked'></span><span>" . __("Re-open", "asgarosforum") . "</span></a></td>";
                    } else {
                        $closed = "<td><a href='" . $this->get_threadlink($this->current_thread) . "&amp;closed=1'><span class='icon-lock'></span><span>" . __("Close", "asgarosforum") . "</span></a></td>";
                    }
                }

                $menu .= "<table class='menu'><tr>";

                if (!$this->is_closed() || $this->is_moderator($user_ID)) {
                    $menu .= "<td><a href='" . $this->url_post_reply . "'><span class='icon-bubble2'></span><span>" . __("Reply", "asgarosforum") . "</span></a></td>";
                }

                if ($this->is_moderator($user_ID)) {
                    $menu .= "<td><a href='" . $this->url_base . "movethread&amp;thread={$this->current_thread}'><span class='icon-shuffle'></span><span>" . __("Move Thread", "asgarosforum") . "</span></a></td>";
                    $menu .= "<td><a href='" . $this->url_thread . $this->current_thread . "&amp;delete_thread' onclick=\"return confirm('Are you sure you want to remove this?');\"><span class='icon-bin'></span><span>" . __("Delete Thread", "asgarosforum") . "</span></a></td>";
                }

                $menu .= $stick . $closed . "</tr></table>";
            }

            return $menu;
        }

        public function get_parent_id($type, $id) {
            global $wpdb;

            switch ($type) {
                case FORUM:
                    return $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$this->table_forums} WHERE id = %d", $id));
                    break;
                case THREAD:
                    return $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$this->table_threads} WHERE id = %d", $id));
                    break;
                case POST:
                    return $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$this->table_posts} WHERE id = %d", $id));
                    break;
            }
        }

        public function get_category_from_thread($thread_id) {
            global $wpdb;
            $parent = $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$this->table_threads} WHERE id = %d", $thread_id));
            return $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$this->table_forums} WHERE id = %d", $parent));
        }

        public function breadcrumbs() {
            $trail = "<span class='icon-home'></span><a href='" . $this->url_home . "'>" . __("Forum", "asgarosforum") . "</a>";

            if ($this->current_forum) {
                $link = $this->get_forumlink($this->current_forum);
                $trail .= "&nbsp;<span class='sep'>&rarr;</span>&nbsp;<a href='{$link}'>" . $this->get_name($this->current_forum, $this->table_forums) . "</a>";
            }

            if ($this->current_thread) {
                $link = $this->get_threadlink($this->current_thread);
                $trail .= "&nbsp;<span class='sep'>&rarr;</span>&nbsp;<a href='{$link}'>" . $this->cut_string($this->get_name($this->current_thread, $this->table_threads), 70) . "</a>";
            }

            if ($this->current_view == 'postreply') {
                $trail .= "&nbsp;<span class='sep'>&rarr;</span>&nbsp;" . __("Post Reply", "asgarosforum");
            }

            if ($this->current_view == 'editpost') {
                $trail .= "&nbsp;<span class='sep'>&rarr;</span>&nbsp;" . __("Edit Post", "asgarosforum");
            }

            if ($this->current_view == 'addthread') {
                $trail .= "&nbsp;<span class='sep'>&rarr;</span>&nbsp;" . __("New Thread", "asgarosforum");
            }

            return "<div class='breadcrumbs'>{$trail}</div>";
        }

        public function last_visit() {
            global $user_ID;

            if ($user_ID) {
                return $_COOKIE['wpafcookie'];
            } else {
                return "0000-00-00 00:00:00";
            }
        }

        public function markallread() {
            global $user_ID;

            if ($user_ID) {
                update_user_meta($user_ID, 'asgarosforum_lastvisit', $this->wpf_current_time_fixed());
                $last = get_user_meta($user_ID, 'asgarosforum_lastvisit', true);
                setcookie("wpafcookie", $last, 0, "/");
                header("Location: " . $this->url_home);
                exit;
            }
        }

        public function pageing($id, $source) {
            global $wpdb;
            $out = __("Pages:", "asgarosforum");
            $count = 0;
            $num_pages = 0;

            if ($source == 'post') {
                $count = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$this->table_posts} WHERE parent_id = %d", $id));
                $num_pages = ceil($count / $this->options['forum_posts_per_page']);
            } else if ($source == 'thread') {
                $count = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$this->table_threads} WHERE parent_id = %d AND status <> 'sticky'", $id));
                $num_pages = ceil($count / $this->options['forum_threads_per_page']);
            }

            if ($num_pages <= 6) {
                for ($i = 0; $i < $num_pages; ++$i) {
                    if ($i == $this->current_page) {
                        $out .= " <strong>" . ($i + 1) . "</strong>";
                    } else {
                        if ($source == 'post') {
                            $out .= " <a href='" . $this->get_threadlink($this->current_thread, ($i + 1)) . "'>" . ($i + 1) . "</a>";
                        } else if ($source == 'thread') {
                            $out .= " <a href='" . $this->get_forumlink($this->current_forum, ($i + 1)) . "'>" . ($i + 1) . "</a>";
                        }
                    }
                }
            } else {
                if ($this->current_page >= 4) {
                    if ($source == 'post') {
                        $out .= " <a href='" . $this->get_threadlink($this->current_thread) . "'>" . __("First", "asgarosforum") . "</a> << ";
                    } else if ($source == 'thread') {
                        $out .= " <a href='" . $this->get_forumlink($this->current_forum) . "'>" . __("First", "asgarosforum") . "</a> << ";
                    }
                }

                for ($i = 3; $i > 0; $i--) {
                    if ((($this->current_page + 1) - $i) > 0) {
                        if ($source == 'post') {
                            $out .= " <a href='" . $this->get_threadlink($this->current_thread, (($this->current_page + 1) - $i)) . "'>" . (($this->current_page + 1) - $i) . "</a>";
                        } else if ($source == 'thread') {
                            $out .= " <a href='" . $this->get_forumlink($this->current_forum, (($this->current_page + 1) - $i)) . "'>" . (($this->current_page + 1) - $i) . "</a>";
                        }
                    }
                }

                $out .= " <strong>" . ($this->current_page + 1) . "</strong>";

                for ($i = 1; $i <= 3; $i++) {
                    if ((($this->current_page + 1) + $i) <= $num_pages) {
                        if ($source == 'post') {
                            $out .= " <a href='" . $this->get_threadlink($this->current_thread, (($this->current_page + 1) + $i)) . "'>" . (($this->current_page + 1) + $i) . "</a>";
                        } else if ($source == 'thread') {
                            $out .= " <a href='" . $this->get_forumlink($this->current_forum, (($this->current_page + 1) + $i)) . "'>" . (($this->current_page + 1) + $i) . "</a>";
                        }
                    }
                }

                if ($num_pages - $this->current_page >= 5) {
                    if ($source == 'post') {
                        $out .= " >> <a href='" . $this->get_threadlink($this->current_thread, $num_pages) . "'>" . __("Last", "asgarosforum") . "</a>";
                    } else if ($source == 'thread') {
                        $out .= " >> <a href='" . $this->get_forumlink($this->current_forum, $num_pages) . "'>" . __("Last", "asgarosforum") . "</a>";
                    }
                }
            }

            return $out;
        }

        public function remove_thread() {
            global $user_ID, $wpdb;

            if ($this->is_moderator($user_ID)) {
                if ($this->thread_exists($this->current_thread)) {
                    $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_posts} WHERE parent_id = %d", $this->current_thread));
                    $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_threads} WHERE id = %d", $this->current_thread));
                    header("Location: " . $this->url_base . "viewforum&forum=" . $this->current_forum);
                    exit;
                } else {
                    echo '<div class="notice">'.__("This thread does not exist.", "asgarosforum").'</div>';
                }
            } else {
                echo '<div class="notice">'.__("You are not allowed to delete threads.", "asgarosforum").'</div>';
            }
        }

        public function movethread() {
            global $user_ID;

            if ($this->is_moderator($user_ID)) {
                $strOUT = '
                <form method="post" action="' . $this->url_base . 'movethread&amp;thread=' . $this->current_thread . '&amp;move_thread">
                Move "<strong>' . $this->get_name($this->current_thread, $this->table_threads) . '</strong>" to new forum:<br />
                <select name="newForumID">';

                $frs = $this->getable_forums();

                foreach ($frs as $f) {
                    $strOUT .= '<option value="' . $f->id . '"' . ($f->id == $this->current_forum ? ' selected="selected"' : '') . '>' . $f->name . '</option>';
                }

                $strOUT .= '</select><br /><input type="submit" value="Go!" /></form>';

                echo $strOUT;
            } else {
                echo '<div class="notice">'.__("You are not allowed to move threads.", "asgarosforum").'</div>';
            }
        }

        public function move_thread() {
            global $user_ID, $wpdb;
            $newForumID = !empty($_POST['newForumID']) ? (int) $_POST['newForumID'] : 0;

            if ($this->is_moderator($user_ID) && $newForumID && $this->forum_exists($newForumID)) {
                $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET parent_id = {$newForumID} WHERE id = %d", $this->current_thread));
                header("Location: " . $this->url_base . "viewthread&thread=" . $this->current_thread);
                exit;
            } else {
                echo '<div class="notice">'.__("You do not have permission to move this thread.", "asgarosforum").'</div>';
            }
        }

        public function remove_post() {
            global $user_ID, $wpdb;
            $id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : 0;
            if ($this->post_exists($id)) {
                $post = $wpdb->get_row($wpdb->prepare("SELECT author_id FROM {$this->table_posts} WHERE id = %d", $id));

                if ($this->is_moderator($user_ID) || $user_ID == $post->author_id) {
                    $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_posts} WHERE id = %d", $id));
                } else {
                    echo '<div class="notice">'.__("You do not have permission to delete this post.", "asgarosforum").'</div>';
                }
            }
        }

        public function sticky_post() {
            global $user_ID, $wpdb;

            if (!$this->is_moderator($user_ID)) {
                echo '<div class="notice">'.__("You are not allowed to do this.", "asgarosforum").'</div>';
            } else {
                if ($this->thread_exists($this->current_thread)) {
                    $status = $this->is_sticky($this->current_thread);

                    if ($status) {
                        $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET status = 'open' WHERE id = %d", $this->current_thread));
                    } else {
                        $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET status = 'sticky' WHERE id = %d", $this->current_thread));
                    }
                }
            }
        }

        public function is_sticky($thread_id = '') {
            global $wpdb;
            $id = "";

            if ($thread_id) {
                $id = $thread_id;
            } else {
                $id = $this->current_thread;
            }

            $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$this->table_threads} WHERE id = %d", $id));

            if ($status == "sticky") {
                return true;
            } else {
                return false;
            }
        }

        public function closed_post() {
            global $user_ID, $wpdb;

            if (!$this->is_moderator($user_ID)) {
                echo '<div class="notice">'.__("You are not allowed to do this.", "asgarosforum").'</div>';
            } else {
                if ($this->thread_exists($this->current_thread)) {
                    $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET closed = %d WHERE id = %d", (int) $_GET['closed'], $this->current_thread));
                }
            }
        }

        public function is_closed($thread_id = '') {
            global $wpdb;
            $id = "";

            if ($thread_id) {
                $id = $thread_id;
            } else {
                $id = $this->current_thread;
            }

            $closed = $wpdb->get_var($wpdb->prepare("SELECT closed FROM {$this->table_threads} WHERE id = %d", $id));

            if ($closed) {
                return true;
            } else {
                return false;
            }
        }

        public function profile_link($user_id, $toWrap = false) {
            if ($toWrap) {
                $user = wordwrap($this->get_userdata($user_id, $this->options['forum_display_name']), 22, "-<br/>", 1);
            } else {
                $user = $this->get_userdata($user_id, $this->options['forum_display_name']);
            }

            return $user;
        }

        public function get_thread_image($thread) {
            if ($this->check_unread($thread)) {
                if ($this->is_closed($thread)) {
                    return "<span class='icon-lock-big-yes'></span>";
                } else {
                    return "<span class='icon-files-empty-big-yes'></span>";
                }
            } else {
                if ($this->is_closed($thread)) {
                    return "<span class='icon-lock-big-no'></span>";
                } else {
                    return "<span class='icon-files-empty-big-no'></span>";
                }
            }
        }

        public function autoembed($string) {
            global $wp_embed;

            if (is_object($wp_embed)) {
                return $wp_embed->autoembed($string);
            } else {
                return $string;
            }
        }
    }
}
?>
