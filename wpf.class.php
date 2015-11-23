<?php

if (!class_exists('asgarosforum')) {
    class asgarosforum {
        var $db_version = 1; // MANAGES DB VERSION
        var $delim = "";
        var $page_id = "";
        var $home_url = "";
        var $forum_link = "";
        var $thread_link = "";
        var $add_topic_link = "";

        // DB tables
        var $t_categories = "";
        var $t_forums = "";
        var $t_threads = "";
        var $t_posts = "";
        var $t_usergroups = "";
        var $t_usergroup2user = "";

        // Misc
        var $o = "";
        var $current_group = "";
        var $current_forum = "";
        var $current_thread = "";
        var $curr_page = "";
        var $skin_url = "";
        var $dateFormat = "";
        var $current_view = "";
        var $base_url = "";

        // Options
        var $options = array();
        var $default_ops = array(
            'forum_posts_per_page' => 10,
            'forum_threads_per_page' => 20,
            'forum_require_registration' => true,
            'forum_use_gravatar' => true,
            'forum_use_seo_friendly_urls' => false,
            'forum_allow_image_uploads' => false,
            'forum_display_name' => 'user_login',
            'forum_db_version' => 0
        );

        public function __construct() {
            // Init options
            $this->options = array_merge($this->default_ops, get_option('asgarosforum_options', array())); // Merge defaults with user's settings
            $this->init();

            // Action hooks
            add_action("admin_menu", array($this, "add_admin_pages"));
            add_action("admin_init", array($this, "wp_forum_install"));
            add_action("wp_enqueue_scripts", array($this, 'enqueue_front_scripts'));
            add_action("wp_head", array($this, "setup_header"));
            add_action("init", array($this, "kill_canonical_urls"));
            add_action('init', array($this, "set_cookie"));
            add_action('init', array($this, "run_wpf_insert"));
            add_action('wp', array($this, "before_go")); // Redirects Old URL's to SEO URL's

            //Filter hooks
            add_filter("rewrite_rules_array", array($this, "set_seo_friendly_rules"));
            add_filter("wp_title", array($this, "get_pagetitle"), 10000, 2);

            // Shortcode hooks
            add_shortcode('asgarosforum', array($this, "go"));

            AFAdmin::load_hooks();
        }

        // Initialize varables
        public function init() {
            global $wpdb;
            $this->page_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[asgarosforum]%' AND post_status = 'publish' AND post_type = 'page'");
            $this->t_categories = $wpdb->prefix . "forum_categories";
            $this->t_forums = $wpdb->prefix . "forum_forums";
            $this->t_threads = $wpdb->prefix . "forum_threads";
            $this->t_posts = $wpdb->prefix . "forum_posts";
            $this->t_usergroups = $wpdb->prefix . "forum_usergroups";
            $this->t_usergroup2user = $wpdb->prefix . "forum_usergroup2user";
            $this->current_group = false;
            $this->current_forum = false;
            $this->current_thread = false;
            $this->curr_page = 0;
            $this->skin_url = plugin_dir_url(__FILE__) . 'skin';
            $this->dateFormat = get_option('date_format') . ', ' . get_option('time_format');
        }

        public function kill_canonical_urls() {
            global $post;

            if (isset($post) && $post instanceof WP_Post && $post->ID == $this->page_id) {
                remove_filter('template_redirect', 'redirect_canonical');
            }
        }

        // Add admin pages
        public function add_admin_pages() {
            add_menu_page(__("Forum - Options", "asgarosforum"), "Forum", "administrator", "asgarosforum", 'AFAdmin::options_page', WPFURL . "admin/images/logo.png");
            add_submenu_page("asgarosforum", __("Forum - Options", "asgarosforum"), __("Options", "asgarosforum"), "administrator", 'asgarosforum', 'AFAdmin::options_page');
            add_submenu_page("asgarosforum", __("Structure - Categories & Forums", "asgarosforum"), __("Structure", "asgarosforum"), "administrator", 'asgarosforum-structure', 'AFAdmin::structure_page');
            add_submenu_page("asgarosforum", __("User Groups", "asgarosforum"), __("User Groups", "asgarosforum"), "administrator", 'asgarosforum-user-groups', 'AFAdmin::user_groups_page');
        }

        public function enqueue_front_scripts() {
            if (is_page($this->page_id)) {
                wp_enqueue_script('asgarosforum-js', WPFURL . "js/script.js");
            }
        }

        public function setup_header() {
            if (is_page($this->page_id)): ?>
                <link rel="stylesheet" type="text/css" href="<?php echo "{$this->skin_url}/style.css"; ?>"  />
            <?php endif;
        }

        public function setup_links() {
            global $wp_rewrite;

            // We need to change all of these $this->delim to use a regex on the request URI instead. This is preventing the forum from working as the home page.
            if ($wp_rewrite->using_permalinks()) {
                $this->delim = "?";
            } else {
                $this->delim = "&";
            }

            $perm = get_permalink($this->page_id);
            $this->forum_link = $perm . $this->delim . "forumaction=viewforum&f=";
            $this->thread_link = $perm . $this->delim . "forumaction=viewtopic&t=";
            $this->add_topic_link = $perm . $this->delim . "forumaction=addtopic&forum={$this->current_forum}";
            $this->post_reply_link = $perm . $this->delim . "forumaction=postreply&thread={$this->current_thread}";
            $this->base_url = $perm . $this->delim . "forumaction=";
            $this->home_url = $perm;
        }

        public function run_wpf_insert() {
            $this->setup_links();

            global $wpdb, $user_ID;
            $error = false;

            if (isset($_POST['add_topic_submit']) || isset($_POST['add_post_submit']) || isset($_POST['edit_post_submit'])) {
                require('wpf-insert.php');
            }
        }

        public function forum_exists($id) {
            global $wpdb;

            if (!empty($id) && $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->t_forums} WHERE id = %d", $id))) {
                return true;
            } else {
                return false;
            }
        }

        public function get_forumlink($id, $page = '0') {
            if ($this->options['forum_use_seo_friendly_urls']) {
                $group = $this->get_seo_friendly_title($this->get_groupname($this->get_parent_id(FORUM, $id)));
                $forum = $this->get_seo_friendly_title($this->get_forumname($id) . "-forum" . $id);

                return rtrim($this->home_url, '/') . '/' . $group . '/' . $forum . '.' . $page;
            } else {
                return $this->forum_link . $id . '.' . $page;
            }
        }

        public function get_threadlink($id, $page = '0') {
            if ($this->options['forum_use_seo_friendly_urls']) {
                $group = $this->get_seo_friendly_title($this->get_groupname($this->get_parent_id(FORUM, $this->get_parent_id(THREAD, $id))));
                $forum = $this->get_seo_friendly_title($this->get_forumname($this->get_parent_id(THREAD, $id)) . "-forum" . $this->get_parent_id(THREAD, $id));
                $thread = $this->get_seo_friendly_title($this->get_subject($id) . "-thread" . $id);

                return rtrim($this->home_url, '/') . '/' . $group . '/' . $forum . '/' . $thread . '.' . $page;
            } else {
                return $this->thread_link . $id . '.' . $page;
            }
        }

        public function get_postlink($id, $postid, $page = 'N/A') {
            $num = 0;

            if ($page == 'N/A') {
                global $wpdb;
                $wpdb->query($wpdb->prepare("SELECT * FROM {$this->t_posts} WHERE parent_id = %d", $id));
                $num = ceil($wpdb->num_rows / $this->options['forum_posts_per_page']) - 1;

                if ($num < 0) {
                    $num = 0;
                }
            } else {
                $num = $page;
            }

            return $this->get_threadlink($id, $num) . '#postid-' . $postid;
        }

        public function get_groups() {
            global $wpdb;

            return $wpdb->get_results("SELECT * FROM {$this->t_categories} ORDER BY sort DESC");
        }

        public function get_forums($id = '') {
            global $wpdb;

            if ($id) {
                return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->t_forums} WHERE parent_id = %d ORDER BY SORT DESC", $id));
            } else {
                return $wpdb->get_results("SELECT * FROM {$this->t_forums} ORDER BY sort DESC");
            }
        }

        public function get_threads($id, $type = 'open') {
            global $wpdb;
            $limit = "";

            if ($type == 'open') {
                $start = $this->curr_page * $this->options['forum_threads_per_page'];
                $end = $this->options['forum_threads_per_page'];
                $limit = $wpdb->prepare("LIMIT %d, %d", $start, $end);
            }

            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->t_threads} AS t WHERE t.parent_id = %d AND t.status = '{$type}' ORDER BY (SELECT MAX(date) FROM {$this->t_posts} AS p WHERE p.parent_id = t.id) DESC {$limit}", $id));
        }

        public function get_posts($thread_id) {
            global $wpdb;
            $start = $this->curr_page * $this->options['forum_posts_per_page'];
            $end = $this->options['forum_posts_per_page'];

            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->t_posts} WHERE parent_id = %d ORDER BY date ASC LIMIT %d, %d", $thread_id, $start, $end));
        }

        public function get_groupname($id) {
            global $wpdb;
            return $this->output_filter($wpdb->get_var($wpdb->prepare("SELECT name FROM {$this->t_categories} WHERE id = %d", $id)));
        }

        public function get_forumname($id) {
            global $wpdb;
            return $this->output_filter($wpdb->get_var($wpdb->prepare("SELECT name FROM {$this->t_forums} WHERE id = %d", $id)));
        }

        public function get_threadname($id) {
            global $wpdb;
            return $this->output_filter($wpdb->get_var($wpdb->prepare("SELECT subject FROM {$this->t_threads} WHERE id = %d", $id)));
        }

        public function cut_string($string, $length = 35) {
            if (strlen($string) > $length) {
                return substr($string, 0, $length) . ' ...';
            }

            return $string;
        }

        public function check_parms($parm) {
            $regexp = "/^([+-]?((([0-9]+(\.)?)|([0-9]*\.[0-9]+))([eE][+-]?[0-9]+)?))$/";

            if (!preg_match($regexp, $parm)) {
                wp_die("Bad request, please re-enter.");
            }

            $p = explode(".", $parm);

            if (count($p) > 1) {
                $this->curr_page = $p[1];
            } else {
                $this->curr_page = 0;
            }

            return $p[0];
        }

        public function before_go() {
            $this->setup_links();
            $action = "";
            $whereto = "";

            if (isset($_GET['markallread']) && $_GET['markallread'] == "true") {
                $this->markallread();
            }

            if (isset($_GET['forumaction'])) {
                $action = $_GET['forumaction'];
            } else {
                $action = false;
            }

            if ($action != false && $this->options['forum_use_seo_friendly_urls']) {
                if (!isset($_GET['getNewForumID']) && !isset($_GET['delete_topic']) && !isset($_GET['remove_post']) && !isset($_GET['sticky']) && !isset($_GET['closed'])) {
                    switch ($action) {
                        case 'viewforum':
                            $whereto = $this->get_forumlink($this->check_parms($_GET['f']));
                            break;
                        case 'viewtopic':
                            $whereto = $this->get_threadlink($this->check_parms($_GET['t']));
                            break;
                    }

                    if (!empty($whereto)) {
                        header("HTTP/1.1 301 Moved Permanently");

                        if ($this->curr_page > 0) {
                            header("Location: " . $whereto . "." . $this->curr_page);
                        } else {
                            header("Location: " . $whereto);
                        }
                    }
                }
            }
        }

        public function go() {
            global $wpdb, $user_ID;
            $this->o = "";

            if (isset($_GET['forumaction'])) {
                $action = $_GET['forumaction'];
            } else {
                $action = false;
            }

            if ($action == false) {
                if ($this->options['forum_use_seo_friendly_urls']) {
                    $uri = $this->get_seo_friendly_query();

                    if (!empty($uri) && $uri['action'] && $uri['id']) {
                        switch ($uri['action']) {
                            case 'forum':
                                $action = 'viewforum';
                                $_GET['f'] = $uri['id'];
                                break;
                            case 'thread':
                                $action = 'viewtopic';
                                $_GET['t'] = $uri['id'];
                                break;
                        }
                    }
                }
            }

            if ($action) {
                switch ($action) {
                    case 'viewforum':
                        $this->showforum($this->check_parms($_GET['f']));
                        break;
                    case 'viewtopic':
                        $this->showthread($this->check_parms($_GET['t']));
                        break;
                    case 'addtopic':
                        include('views/wpf-thread.php');
                        break;
                    case 'postreply':
                        if ($this->is_closed($_GET['thread']) && !$this->is_moderator($user_ID, $this->get_parent_id(THREAD, (int) $_GET['thread']))) {
                            wp_die(__("An unknown error has occured. Please try again.", "asgarosforum"));
                        } else {
                            $this->current_thread = $this->check_parms($_GET['thread']);
                            include('views/wpf-post.php');
                        }
                        break;
                    case 'editpost':
                        include('views/wpf-post.php');
                        break;
                    case 'search':
                        $this->search_results();
                        break;
                }
            } else {
                $this->overview();
            }

            echo '<div id="wpf-wrapper">';
            echo '<div id="top-elements">';
            echo $this->trail();
            echo "<div class='wpf_search'>
            <form name='wpf_search_form' method='post' action='{$this->base_url}" . "search'>
            <input onfocus='placeHolder(this)' onblur='placeHolder(this)' type='text' name='search_words' class='wpf-input mf_search' value='" . __("Search forums", "asgarosforum") . "' />
            </form>
            </div></div>";

            echo $this->o . '</div>';
        }

        public function get_userdata($user_id, $data) {
            $user = get_userdata($user_id);

            if (!$user) {
                return __("Guest", "asgarosforum");
            }

            return $user->$data;
        }

        public function get_lastpost($thread_id) {
            global $wpdb;
            $post = $wpdb->get_row($wpdb->prepare("SELECT date, author_id, id FROM {$this->t_posts} WHERE parent_id = %d ORDER BY date DESC LIMIT 1", $thread_id));
            $link = $this->get_postlink($thread_id, $post->id);
            require('views/lastpost.php');
        }

        public function showforum($forum_id) {
            if ($this->forum_exists($forum_id)) {
                global $user_ID, $wpdb;

                if (isset($_GET['delete_topic'])) {
                    $this->remove_topic($forum_id);
                }

                if (isset($_GET['move_topic'])) {
                    $this->move_topic($forum_id);
                }

                $out = "";
                $threads = $this->get_threads($forum_id);
                $sticky_threads = $this->get_threads($forum_id, 'sticky');
                $this->current_group = $this->get_parent_id(FORUM, $forum_id);
                $this->current_forum = $forum_id;

                if (isset($_GET['getNewForumID'])) {
                    $out .= $this->getNewForumID();
                } else {
                    ob_start();
                    if (!$this->have_access($this->current_group)) {
                        wp_die(__("Sorry, but you don't have access to this forum", "asgarosforum"));
                    }

                    require('views/showforum.php');
                    $out .= ob_get_clean();
                }

                $this->o .= $out;
            } else {
                wp_die(__("Sorry, but this forum does not exist.", "asgarosforum"));
            }
        }

        public function get_starter($thread_id) {
            global $wpdb;
            return $wpdb->get_var($wpdb->prepare("SELECT author_id FROM {$this->t_posts} WHERE parent_id = %d ORDER BY id ASC LIMIT 1", $thread_id));
        }

        public function check_unread($thread_id) {
            global $user_ID;
            $image = "";

            if ($user_ID) {
                $poster_id = $this->last_posterid_thread($thread_id);

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

        public function get_subject($id) {
            global $wpdb;
            return stripslashes($wpdb->get_var($wpdb->prepare("SELECT subject FROM {$this->t_threads} WHERE id = %d", $id)));
        }

        public function showthread($thread_id) {
            global $wpdb, $user_ID;
            $this->current_group = $this->forum_get_group_from_post($thread_id);
            $this->current_forum = $this->get_parent_id(THREAD, $thread_id);
            $this->current_thread = $thread_id;
            $out = "";

            if (isset($_GET['remove_post'])) {
                $this->remove_post();
            }

            if (isset($_GET['sticky'])) {
                $this->sticky_post();
            }

            if (isset($_GET['closed'])) {
                $this->closed_post();
            }

            $posts = $this->get_posts($thread_id);

            if ($posts) {
                $wpdb->query($wpdb->prepare("UPDATE {$this->t_threads} SET views = views+1 WHERE id = %d", $thread_id));

                if (!$this->have_access($this->current_group)) {
                    wp_die(__("Sorry, but you don't have access to this thread.", "asgarosforum"));
                }

                $out .= "<table><tr class='pop_menus'>";
                $out .= "<td>" . $this->post_pageing($thread_id) . "</td>";
                $out .= "<td>" . $this->topic_menu($thread_id) . "</td>";
                $out .= "</tr></table>";

                if ($this->is_closed()) {
                    $meClosed = "&nbsp;(" . __("Topic closed", "asgarosforum") . ") ";
                } else {
                    $meClosed = "";
                }

                $out .= "<div id='thread-title'>" . $this->cut_string($this->get_subject($thread_id), 70) . $meClosed . "</div>";
                $out .= "<div id='thread-content'>";

                foreach ($posts as $post) {
                    $out .= "
                    <table class='wpf-post-table' id='postid-{$post->id}'>
                        <tr>
                            <td colspan='2' class='wpf-bright author'>
                                <span class='post-data-format'>" . date_i18n($this->dateFormat, strtotime($post->date)) . "</span>
                                <div class='wpf-meta'>" . $this->get_postmeta($post->id, $post->author_id, $post->parent_id) . "</div>
                            </td>
                        </tr>
                        <tr>
                            <td class='autorpostbox'>
                                <div class='wpf-small'>";
                                    if ($this->options["forum_use_gravatar"]) {
                                        $out .= $this->get_avatar($post->author_id);
                                    }
                                    $out .= "<br /><strong>" . $this->profile_link($post->author_id, true) ."</strong><br />";
                                    $out .=__("Posts:", "asgarosforum") . "&nbsp;" . $this->get_userposts_num($post->author_id);
                                    $out .= "
                                </div>
                            </td>
                            <td valign='top' class='topic_text'>";
                                $out .= make_clickable(wpautop($this->autoembed($this->output_filter($post->text))));
                                $out .= "
                            </td>
                        </tr>
                    </table>";
                }

                $out .= "</div>";

                $quick_thread = $this->check_parms($_GET['t']);

                // QUICK REPLY AREA
                if ((!$this->is_closed() || $this->is_moderator($user_ID, $this->current_forum)) && ($user_ID || $this->allow_unreg())) {
                    $out .= "
                    <div id='thread-reply'>
                    <form action='' name='addform' method='post'>
                        <strong>" . __("Quick Reply", "asgarosforum") . ": </strong><br/>" .
                        $this->form_buttons() . $this->form_smilies() . "
                        <textarea name='message'></textarea>" .
                        $this->get_quick_reply_captcha() . "
                        <input type='submit' id='quick-reply-submit' name='add_post_submit' value='" . __("Submit Quick Reply", "asgarosforum") . "' />
                        <input type='hidden' name='add_post_forumid' value='" . floor($quick_thread) . "'/>
                    </form>
                    </div>";
                }

                $out .= "<table><tr class='pop_menus'>
                    <td>" . $this->post_pageing($thread_id) . "</td>
                    <td>" . $this->topic_menu($thread_id) . "</td>
                </tr></table>";

                $this->o .= $out;
            }
        }








    public function get_postmeta($post_id, $author_id, $parent_id)
    {
      global $user_ID;
      $this->setup_links();
      $o = "<table class='wpf-meta-button'width='100%' style='margin:0; padding:0; border-collapse:collapse:' border='0'><tr>";

      if ($this->options['forum_use_seo_friendly_urls'])
      {
        if (($user_ID || $this->allow_unreg()) && (!$this->is_closed() || $this->is_moderator($user_ID, $this->current_forum)))
          $o .= "<td nowrap='nowrap'><img src='{$this->skin_url}/images/buttons/quote.png' alt='' align='left'><a href='{$this->post_reply_link}&quote={$post_id}.{$this->curr_page}'> " . __("Quote", "asgarosforum") . "</a></td>";
        if ($this->is_moderator($user_ID, $this->current_forum))
          $o .= "<td nowrap='nowrap'><img src='{$this->skin_url}/images/buttons/delete.png' alt='' align='left'><a onclick=\"return wpf_confirm();\" href='" . $this->thread_link . $this->current_thread . "&remove_post&id={$post_id}'> " . __("Remove", "asgarosforum") . "</a></td>";
        if (($this->is_moderator($user_ID, $this->current_forum)) || ($user_ID == $author_id && $user_ID))
          $o .= "<td nowrap='nowrap'><img src='{$this->skin_url}/images/buttons/modify.png' alt='' align='left'><a href='" . $this->base_url . "editpost&id={$post_id}&t={$this->current_thread}.{$this->curr_page}'>" . __("Edit", "asgarosforum") . "</a></td>";


      }
      else
      {
        if (($user_ID || $this->allow_unreg()) && (!$this->is_closed() || $this->is_moderator($user_ID, $this->current_forum)))
          $o .= "<td nowrap='nowrap'><img src='{$this->skin_url}/images/buttons/quote.png' alt='' align='left'><a href='{$this->post_reply_link}&quote={$post_id}.{$this->curr_page}'> " . __("Quote", "asgarosforum") . "</a></td>";
        if ($this->is_moderator($user_ID, $this->current_forum))
          $o .= "<td nowrap='nowrap'><img src='{$this->skin_url}/images/buttons/delete.png' alt='' align='left'><a onclick=\"return wpf_confirm();\" href='" . $this->get_threadlink($this->current_thread) . "&remove_post&id={$post_id}'> " . __("Remove", "asgarosforum") . "</a></td>";
        if (($this->is_moderator($user_ID, $this->current_forum)) || ($user_ID == $author_id && $user_ID))
          $o .= "<td nowrap='nowrap'><img src='{$this->skin_url}/images/buttons/modify.png' alt='' align='left'><a href='" . $this->base_url . "editpost&id={$post_id}&t={$this->current_thread}.{$this->curr_page}'>" . __("Edit", "asgarosforum") . "</a></td>";
      }

$o .= "<td nowrap='nowrap'><a href='" . $this->get_postlink($parent_id, $post_id, $this->curr_page) . "' title='" . __("Permalink", "asgarosforum") . "'><img alt='' align='top' src='{$this->skin_url}/images/bbc/url.png' /> </a></td>";

      $o .= "</tr></table>";

      return $o;
    }

    public function get_postdate($post)
    {
      global $wpdb;

      return $this->format_date($wpdb->get_var($wpdb->prepare("SELECT `date` FROM {$this->t_posts} WHERE id = %d", $post)));
    }

    public function format_date($date)
    {
      if ($date)
        return date_i18n($this->dateFormat, strtotime($date));
      else
        return false;
    }

    public function wpf_current_time_fixed($type, $gmt = 0)
    {
      $t = ($gmt) ? gmdate('Y-m-d H:i:s') : gmdate('Y-m-d H:i:s', (time() + (get_option('gmt_offset') * 3600)));

      switch ($type)
      {
        case 'mysql':
          return $t;
          break;
        case 'timestamp':
          return strtotime($t);
          break;
      }
    }

    public function get_userposts_num($id)
    {
      global $wpdb;

      return $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$this->t_posts} WHERE author_id = %d", $id));
    }

    public function get_post_owner($id)
    {
      global $wpdb;

      return $wpdb->get_var($wpdb->prepare("SELECT `author_id` FROM {$this->t_posts} WHERE `id` = %d", $id));
    }

    public function overview()
    {
        global $user_ID, $wp_rewrite;
        $alt = "";

        if ($wp_rewrite->using_permalinks()) {
            $delim = "?";
        } else {
            $delim = "&";
        }

        $grs = $this->get_groups();

        if (count($grs) > 0) {
            foreach ($grs as $g) {
                if ($this->have_access($g->id)) {
                    $this->o .= "<div><table width='100%' class='wpf-table forumsList'>";
                    $this->o .= "<tr><td class='forumtitle' colspan='4'><span>" . $this->output_filter($g->name) . "</span></td></tr>";

                    $frs = $this->get_forums($g->id);

                    if (count($frs) > 0) {
                        foreach ($frs as $f) {
                            $alt = ($alt == "alt even") ? "odd" : "alt even";
                            $this->o .= "<tr class='{$alt}'>";
                            $image = "new_none.png";

                            if ($user_ID) {
                                $lpif = $this->last_poster_in_forum($f->id, true);
                                $last_posterid = $this->last_posterid($f->id);

                                if ($last_posterid != $user_ID) {
                                    $lp = strtotime($lpif); // date
                                    $lv = strtotime($this->last_visit());

                                    if ($lv < $lp) {
                                        $image = "new_some.png";
                                    } else {
                                        $image = "new_none.png";
                                    }
                                }
                            }

                            $this->o .= "<td class='forumIcon' width='6%' align='center'><img alt='' src='{$this->skin_url}/images/{$image}' /></td>
                            <td valign='top' class='wpf-category-title' ><strong><a href='" . $this->get_forumlink($f->id) . "'>"
                            . $this->output_filter($f->name) . "</a></strong><br />"
                            . $this->output_filter($f->description);

                            if ($f->description != "") {
                                $this->o .= "<br/>";
                            }

                            $this->o .= "</td>";
                            $this->o .= "<td nowrap='nowrap' width='11%' align='left' class='forumstats'>" . __("Topics: ", "asgarosforum") . "" . $this->num_threads($f->id) . "<br />" . __("Posts: ", "asgarosforum") . $this->num_posts_forum($f->id) . "</td>";
                            $this->o .= "<td  class='poster_in_forum' width='29%' style='vertical-align:middle;' >" . $this->last_poster_in_forum($f->id) . "</td>";
                            $this->o .= "</tr>";
                        }
                    } else {
                        $this->o .= "<tr><td id='wpf_notice' colspan='4'>".__("There are no forums yet!", "asgarosforum")."</td></tr>";
                    }

                    $this->o .= "</table></div><br class='clear'/>";
                }
            }
        } else {
            $this->o .= "<div id='wpf_notice'>".__("There are no categories yet!", "asgarosforum")."</div>";
        }

        $this->o .= apply_filters('wpwf_new_posts', "<table>
        <tr>
        <td><span class='info-poster_in_forum'><img alt='' align='top' src='{$this->skin_url}/images/new_some.png' /> " . __("New posts", "asgarosforum") . " <img alt='' align='top' src='{$this->skin_url}/images/new_none.png' /> " . __("No new posts", "asgarosforum") . "</span> - <span aria-hidden='true' class='icon-checkmark'><a href='" . get_permalink($this->page_id) . $delim . "markallread=true'>" . __("Mark All Read", "asgarosforum") . "</a></span></td>
        </tr>
        </table><br class='clear'/>");
    }

    public function output_filter($string)
    {
      $parser = new cartpaujBBCodeParser();

      return stripslashes($parser->bbc2html($string));
    }

    public function input_filter($string)
    {
      $Find = array("<", "%", "$");
      $Replace = array("&#60;", "&#37;", "&#36;");
      $newStr = str_replace($Find, $Replace, $string);

      return $newStr;
    }

    public function last_posterid($forum)
    {
      global $wpdb;

      return $wpdb->get_var($wpdb->prepare("SELECT {$this->t_posts}.author_id FROM {$this->t_posts} INNER JOIN {$this->t_threads} ON {$this->t_posts}.parent_id={$this->t_threads}.id WHERE {$this->t_threads}.parent_id = %d ORDER BY {$this->t_posts}.date DESC", $forum));
    }

    public function last_posterid_thread($thread_id)
    {
      global $wpdb;

      return $wpdb->get_var($wpdb->prepare("SELECT {$this->t_posts}.author_id FROM {$this->t_posts} INNER JOIN {$this->t_threads} ON {$this->t_posts}.parent_id={$this->t_threads}.id WHERE {$this->t_posts}.parent_id = %d ORDER BY {$this->t_posts}.date DESC", $thread_id));
    }

    public function num_threads($forum)
    {
      global $wpdb;

      return $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$this->t_threads} WHERE parent_id = %d", $forum));
    }

    public function num_posts_forum($forum)
    {
      global $wpdb;

      return $wpdb->get_var($wpdb->prepare("SELECT COUNT({$this->t_posts}.id) FROM {$this->t_posts} INNER JOIN {$this->t_threads} ON {$this->t_posts}.parent_id={$this->t_threads}.id WHERE {$this->t_threads}.parent_id = %d ORDER BY {$this->t_posts}.date DESC", $forum));
    }

    public function num_posts($thread_id)
    {
      global $wpdb;

      return $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$this->t_posts} WHERE parent_id = %d", $thread_id));
    }

    public function last_poster_in_forum($forum, $post_date = false)
    {
      global $wpdb;

      $date = $wpdb->get_row($wpdb->prepare("SELECT {$this->t_posts}.date, {$this->t_posts}.id, {$this->t_posts}.parent_id, {$this->t_posts}.author_id FROM {$this->t_posts} INNER JOIN {$this->t_threads} ON {$this->t_posts}.parent_id={$this->t_threads}.id WHERE {$this->t_threads}.parent_id = %d ORDER BY {$this->t_posts}.date DESC", $forum));

      if ($post_date && is_object($date))
        return $date->date;
      if (!$date)
        return "<small>" . __("No topics yet", "asgarosforum") . "</small>";

      $d = date_i18n($this->dateFormat, strtotime($date->date));

      return "<div class='wpf-item'><div class='wpf-item-title'><small><strong>" . __("Last post", "asgarosforum") . "</strong> " . __("by", "asgarosforum") . " " . $this->profile_link($date->author_id) . "</small></div>
      <div class='wpf-item-title'><small>" . __("in", "asgarosforum") . " <a href='" . $this->get_postlink($date->parent_id, $date->id) . "'>" . $this->cut_string($this->get_threadname($date->parent_id)) . "</a></small></div><div class='wpf-item-title'><small>" . __("on", "asgarosforum") . " {$d}</small></div></div>";
    }

    public function last_poster_in_thread($thread_id)
    {
      global $wpdb;

      return $wpdb->get_var("SELECT `date` FROM {$this->t_posts} WHERE parent_id = {$thread_id} ORDER BY `date` DESC");
    }

    public function have_access($groupid)
    {
      global $wpdb, $user_ID;

      if (is_super_admin())
        return true;

      $user_groups = maybe_unserialize($wpdb->get_var("SELECT usergroups FROM {$this->t_categories} WHERE id = {$groupid}"));
      if (!$user_groups)
        return true;

      foreach ($user_groups as $user_group)
        if ($this->is_user_ingroup($user_ID, $user_group))
          return true;

      return false;
    }

    public function get_usergroups()
    {
      global $wpdb;

      return $wpdb->get_results("SELECT * FROM {$this->t_usergroups} ORDER BY id ASC");
    }

    public function get_usergroup($id)
    {
      global $wpdb;

      $q = "SELECT *
              FROM {$this->t_usergroups}
              WHERE `id` = %d";

      return $wpdb->get_row($wpdb->prepare($q, $id));
    }

    public function get_members($usergroup)
    {
      global $wpdb;

      $q = "SELECT ug2u.user_id, u.user_login
              FROM {$this->t_usergroup2user} AS ug2u JOIN {$wpdb->users} AS u
                ON ug2u.user_id = u.ID
              WHERE ug2u.group_id = %d
            ORDER BY u.user_login";

      return $wpdb->get_results($wpdb->prepare($q, $usergroup));
    }

    public function get_all_users_list()
    {
      global $wpdb;

      $q = "SELECT user_login
              FROM {$wpdb->users}
            ORDER BY user_login";

      return $wpdb->get_col($q);
    }

    public function is_user_ingroup($user_id = "0", $user_group_id)
    {
      global $wpdb;

      if (!$user_id)
        return false;

      $id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$this->t_usergroup2user} WHERE user_id = %d AND `group_id` = %d", $user_id, $user_group_id));

      if ($id)
        return true;

      return false;
    }

// Some SEO friendly stuff
public function get_pagetitle($bef_title, $sep)
{
    global $post;
    $default_title = $post->post_title;
    $action = "";
    $title = "";

    if (isset($_GET['forumaction']) && !empty($_GET['forumaction'])) {
        $action = $_GET['forumaction'];
    } else if ($this->options['forum_use_seo_friendly_urls']) {
        $uri = $this->get_seo_friendly_query();

        if (!empty($uri) && $uri['action'] && $uri['id']) {
            switch ($uri['action']) {
                case 'forum':
                    $action = 'viewforum';
                    $_GET['f'] = $uri['id'];
                    break;
                case 'thread':
                    $action = 'viewtopic';
                    $_GET['t'] = $uri['id'];
                    break;
            }
        }
    }

    switch ($action) {
        case "viewforum":
            $title = $default_title . " - " . $this->get_forumname($this->check_parms($_GET['f']));
            break;
        case "viewtopic":
            $title = $default_title . " - " . $this->get_forumname($this->get_parent_id(THREAD, $this->check_parms($_GET['t']))) . " - " . $this->get_threadname($this->check_parms($_GET['t']));
            break;
        case "search":
            $terms = esc_html($_POST['search_words']);
            $title = $default_title . " - " . __("Search Results", "asgarosforum");
            break;
        case "editpost":
            $title = $default_title . " - " . __("Edit Post", "asgarosforum");
            break;
        case "postreply":
            $title = $default_title . " - " . __("Post Reply", "asgarosforum");
            break;
        case "addtopic":
            $title = $default_title . " - " . __("New Topic", "asgarosforum");
            break;
        default:
            $title = $default_title;
            break;
      }

      return $title . ' | ';
  }

    public function get_usergroup_name($usergroup_id)
    {
      global $wpdb;

      return $wpdb->get_var($wpdb->prepare("SELECT name FROM {$this->t_usergroups} WHERE id = %d", $usergroup_id));
    }

    public function get_usergroup_description($usergroup_id)
    {
      global $wpdb;

      return $wpdb->get_var($wpdb->prepare("SELECT description FROM {$this->t_usergroups} WHERE id = %d", $usergroup_id));
    }

    public function is_moderator($user_id, $forum_id = '')
    {
      if (!$user_id || !$forum_id) //If guest or no forum ID
        return false;

      if (is_super_admin($user_id))
        return true;

      return false;
    }

public function wp_forum_install()
{
    global $wpdb;

    // Only run if we need to
    if ($this->options['forum_db_version'] < $this->db_version) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql1 = "
        CREATE TABLE $this->t_categories (
        id int(11) NOT NULL auto_increment,
        name varchar(255) NOT NULL default '',
        description varchar(255) default '',
        usergroups varchar(255) default '',
        sort int(11) NOT NULL default '0',
        PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql2 = "
        CREATE TABLE $this->t_forums (
        id int(11) NOT NULL auto_increment,
        name varchar(255) NOT NULL default '',
        parent_id int(11) NOT NULL default '0',
        description varchar(255) NOT NULL default '',
        sort int(11) NOT NULL default '0',
        PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql3 = "
        CREATE TABLE $this->t_threads (
        id int(11) NOT NULL auto_increment,
        parent_id int(11) NOT NULL default '0',
        views int(11) NOT NULL default '0',
        subject varchar(255) NOT NULL default '',
        status varchar(20) NOT NULL default 'open',
        closed int(11) NOT NULL default '0',
        PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql4 = "
        CREATE TABLE $this->t_posts (
        id int(11) NOT NULL auto_increment,
        text longtext,
        parent_id int(11) NOT NULL default '0',
        date datetime NOT NULL default '0000-00-00 00:00:00',
        author_id int(11) NOT NULL default '0',
        PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql5 = "
        CREATE TABLE $this->t_usergroups (
        id int(11) NOT NULL auto_increment,
        name varchar(255) NOT NULL,
        description varchar(255) default NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql6 = "
        CREATE TABLE $this->t_usergroup2user (
        id int(11) NOT NULL auto_increment,
        user_id int(11) NOT NULL,
        group_id varchar(255) NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        if ($this->options['forum_db_version'] < 1) {
            dbDelta($sql1);
            dbDelta($sql2);
            dbDelta($sql3);
            dbDelta($sql4);
            dbDelta($sql5);
            dbDelta($sql6);

            //We need to kill this one after we fix how the forum search works
            $wpdb->query("ALTER TABLE {$this->t_posts} ENGINE = MyISAM"); //InnoDB doesn't support FULLTEXT
            $wpdb->query("ALTER TABLE {$this->t_posts} ADD FULLTEXT (text)");
        }

        $this->options['forum_db_version'] = $this->db_version;
        update_option('asgarosforum_options', $this->options);
    }
}

    public function forum_menu($group)
    {
      global $user_ID;
      $this->setup_links();

      $menu = "";
      if ($user_ID || $this->allow_unreg())
      {


        $menu = "<table id='forummenu'>";
        $menu .= "<tr>
                <td valign='top' class='tab_back' nowrap='nowrap'><a href='" . $this->add_topic_link . "'><span  aria-hidden='true' class='icon-topic'>" . __("New Topic", "asgarosforum") . "</span></a></td>";

        $menu .= "
          </tr>
          </table>";
      }
      return $menu;
    }

    public function topic_menu($thread)
    {
      global $user_ID;
      $this->setup_links();
      $menu = "";
      $stick = "";
      $closed = "";

      if ($user_ID || $this->allow_unreg())
      {


        if ($this->is_moderator($user_ID, $this->current_forum))
        {
          if ($this->options['forum_use_seo_friendly_urls'])
          {
            if ($this->is_sticky())
              $stick = "<td class='tab_back' nowrap='nowrap'><a href='" . $this->thread_link . $this->current_thread . "." . $this->curr_page . "&sticky&id={$this->current_thread}'><span class='icon-undo-sticky' aria-hidden='true'>" . __("Undo Sticky", "asgarosforum") . "</span></a></td>";
            else
              $stick = "<td class='tab_back' nowrap='nowrap'><a href='" . $this->thread_link . $this->current_thread . "." . $this->curr_page . "&sticky&id={$this->current_thread}'><span class='icon-sticky' aria-hidden='true'>" . __("Sticky", "asgarosforum") . "</span></a></td>";

            if ($this->is_closed())
              $closed = "<td class='tab_back' nowrap='nowrap'><a href='" . $this->thread_link . $this->current_thread . "." . $this->curr_page . "&closed=0&id={$this->current_thread}'><span class='icon-re-open' aria-hidden='true'>" . __("Re-open", "asgarosforum") . "</span></a></td>";
            else
              $closed = "<td class='tab_back' nowrap='nowrap'><a href='" . $this->thread_link . $this->current_thread . "." . $this->curr_page . "&closed=1&id={$this->current_thread}'><span class='icon-close' aria-hidden='true'>" . __("Close", "asgarosforum") . "</span></a></td>";
          }
          else
          {
            if ($this->is_sticky())
              $stick = "<td class='tab_back' nowrap='nowrap'><a href='" . $this->get_threadlink($this->current_thread) . "&sticky&id={$this->current_thread}'><span class='icon-undo-sticky' aria-hidden='true'>" . __("Undo Sticky", "asgarosforum") . "</span></a></td>";
            else
              $stick = "<td class='tab_back' nowrap='nowrap'><a href='" . $this->get_threadlink($this->current_thread) . "&sticky&id={$this->current_thread}'><span class='icon-sticky' aria-hidden='true'>" . __("Sticky", "asgarosforum") . "</span></a></td>";

            if ($this->is_closed())
              $closed = "<td class='tab_back' nowrap='nowrap'><a href='" . $this->get_threadlink($this->current_thread) . "&closed=0&id={$this->current_thread}'><span class=' icon-re-open' aria-hidden='true'>" . __("Re-open", "asgarosforum") . "</span></a></td>";
            else
              $closed = "<td class='tab_back' nowrap='nowrap'><a href='" . $this->get_threadlink($this->current_thread) . "&closed=1&id={$this->current_thread}'><span class='icon-close' aria-hidden='true'>" . __("Close", "asgarosforum") . "</span></a></td>";
          }
        }

        $menu .= "<table id='topicmenu'>";
        $menu .= "<tr>";

          if (!$this->is_closed() || $this->is_moderator($user_ID, $this->current_forum))
            $menu .= "<td valign='top' class='tab_back' nowrap='nowrap'><a href='" . $this->post_reply_link . "'><span class='icon-reply' aria-hidden='true' >" . __("Reply", "asgarosforum") . "</span></a></td>";



          if ($this->is_moderator($user_ID, $this->current_forum)) {
              $menu .= "<td valign='top' class='tab_back' nowrap='nowrap'><a href='" . $this->forum_link . $this->current_forum . "." . $this->curr_page . "&getNewForumID&topic={$this->current_thread}'><span class='icon-move-topic' aria-hidden='true' >" . __("Move Topic", "asgarosforum") . "</span></a></td>";
          }

        $menu .= $stick . $closed . "</tr></table>";
      }

      return $menu;
    }

    public function get_parent_id($type, $id)
    {
      global $wpdb;

      switch ($type)
      {
        case FORUM:
          return $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$this->t_forums} WHERE id = %d", $id));
          break;
        case THREAD:
          return $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$this->t_threads} WHERE id = %d", $id));
          break;
      }
    }

    public function get_userrole($user_id)
    {
      if (!$user_id)
        return __('Guest', 'asgarosforum');

      $user = get_userdata($user_id);

      if ($user->user_level >= 9)
        return __("Administrator", "asgarosforum");
      else
      {
        return "";
      }
    }

    public function forum_get_group_id($group)
    {
      global $wpdb;

      $group = ($group) ? $group : 0;

      return $wpdb->get_var($wpdb->prepare("SELECT id FROM {$this->t_categories} WHERE id = %d", $group));
    }

    public function forum_get_parent($forum)
    {
      global $wpdb;

      $forum = ($forum) ? $forum : 0;

      return $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$this->t_forums} WHERE id = %d", $forum));
    }

    public function forum_get_forum_from_post($thread)
    {
      global $wpdb;

      $thread = ($thread) ? $thread : 0;

      return $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$this->t_threads} WHERE id = %d", $thread));
    }

    public function forum_get_group_from_post($thread_id)
    {
      return $this->forum_get_group_id($this->forum_get_parent($this->forum_get_forum_from_post($thread_id)));
    }

    public function trail()
    {
      global $wpdb;

      $this->setup_links();

      $trail = "<a aria-hidden='true' class='icon-forum-home' href='" . get_permalink($this->page_id) . "'>" . __("Forum Home", "asgarosforum") . "</a>";

      if ($this->current_forum) {
        $link = $this->get_forumlink($this->current_forum);
        $trail .= " <span class='wpf_nav_sep'>&rarr;</span> <a href='{$link}'>" . $this->get_forumname($this->current_forum) . "</a>";
}

      if ($this->current_thread) {
        $link = $this->get_threadlink($this->current_thread);
        $trail .= " <span class='wpf_nav_sep'>&rarr;</span> <a href='{$link}'>" . $this->cut_string($this->get_threadname($this->current_thread), 70) . "</a>";
}

      if ($this->current_view == SEARCH)
      {
        $terms = "";

        if (isset($_POST['search_words']))
          $terms = esc_html(esc_sql($_POST['search_words']));

        $trail .= " <span class='wpf_nav_sep'>&rarr;</span> " . __("Search Results", "asgarosforum") . " &rarr; $terms";
      }

      if ($this->current_view == POSTREPLY)
        $trail .= " <span class='wpf_nav_sep'>&rarr;</span> " . __("Post Reply", "asgarosforum");

      if ($this->current_view == EDITPOST)
        $trail .= " <span class='wpf_nav_sep'>&rarr;</span> " . __("Edit Post", "asgarosforum");

      if ($this->current_view == NEWTOPIC)
        $trail .= " <span class='wpf_nav_sep'>&rarr;</span> " . __("New Topic", "asgarosforum");

      return "<div id='trail' class='breadcrumbs'>{$trail}</div>";
    }

    public function last_visit()
    {
      global $user_ID;

      if ($user_ID)
        return $_COOKIE['wpafcookie'];
      else
        return "0000-00-00 00:00:00";
    }

public function set_cookie()
{
    global $user_ID;

    if ($user_ID && !isset($_COOKIE['wpafcookie'])) {
        $last = get_user_meta($user_ID, 'lastvisit', true);
        setcookie("wpafcookie", $last, 0, "/");
        update_user_meta($user_ID, 'lastvisit', $this->wpf_current_time_fixed('mysql', 0));
    }
}

    public function markallread()
    {
      global $user_ID;

      if ($user_ID)
      {
        update_user_meta($user_ID, 'lastvisit', $this->wpf_current_time_fixed('mysql', 0));

        $last = get_user_meta($user_ID, 'lastvisit', true);

        setcookie("wpafcookie", $last, 0, "/");
      }
    }

    public function get_avatar($user_id, $size = 60)
    {
      if ($this->options['forum_use_gravatar'] == 'true')
        return get_avatar($user_id, $size);
      else
        return "";
    }

    public function post_pageing($thread_id)
    {
      global $wpdb;

      $out = __("Pages:", "asgarosforum");
      $count = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$this->t_posts} WHERE parent_id = %d", $thread_id));
      $num_pages = ceil($count / $this->options['forum_posts_per_page']);

      if ($num_pages <= 6)
      {
        for ($i = 0; $i < $num_pages; ++$i)
          if ($i == $this->curr_page)
            $out .= " <strong>" . ($i + 1) . "</strong>";
          else
            $out .= " <a href='" . $this->get_threadlink($this->current_thread, $i) . "'>" . ($i + 1) . "</a>";
      }
      else
      {
        if ($this->curr_page >= 4)
          $out .= " <a href='" . $this->get_threadlink($this->current_thread) . "'>" . __("First", "asgarosforum") . "</a> << ";

        for ($i = 3; $i > 0; $i--)
          if ((($this->curr_page + 1) - $i) > 0)
            $out .= " <a href='" . $this->get_threadlink($this->current_thread, ($this->curr_page - $i)) . "'>" . (($this->curr_page + 1) - $i) . "</a>";

        $out .= " <strong>" . ($this->curr_page + 1) . "</strong>";

        for ($i = 1; $i <= 3; $i++)
          if ((($this->curr_page + 1) + $i) <= $num_pages)
            $out .= " <a href='" . $this->get_threadlink($this->current_thread, ($this->curr_page + $i)) . "'>" . (($this->curr_page + 1) + $i) . "</a>";

        if ($num_pages - $this->curr_page >= 5)
          $out .= " >> <a href='" . $this->get_threadlink($this->current_thread, ($num_pages - 1)) . "'>" . __("Last", "asgarosforum") . "</a>";
      }

      return "<span class='wpf-pages'>" . $out . "</span>";
    }

    public function thread_pageing($forum_id)
    {
      global $wpdb;

      $out = __("Pages:", "asgarosforum");
      $count = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$this->t_threads} WHERE parent_id = %d AND `status` <> 'sticky'", $forum_id));
      $num_pages = ceil($count / $this->options['forum_threads_per_page']);

      if ($num_pages <= 6)
      {
        for ($i = 0; $i < $num_pages; ++$i)
          if ($i == $this->curr_page)
            $out .= " <strong>" . ($i + 1) . "</strong>";
          else
            $out .= " <a href='" . $this->get_forumlink($this->current_forum, $i) . "'>" . ($i + 1) . "</a>";
      }
      else
      {
        if ($this->curr_page >= 4)
          $out .= " <a href='" . $this->get_forumlink($this->current_forum, "0") . "'>" . __("First", "asgarosforum") . "</a> << ";

        for ($i = 3; $i > 0; $i--)
          if ((($this->curr_page + 1) - $i) > 0)
            $out .= " <a href='" . $this->get_forumlink($this->current_forum, ($this->curr_page - $i)) . "'>" . (($this->curr_page + 1) - $i) . "</a>";

        $out .= " <strong>" . ($this->curr_page + 1) . "</strong>";

        for ($i = 1; $i <= 3; $i++)
          if ((($this->curr_page + 1) + $i) <= $num_pages)
            $out .= " <a href='" . $this->get_forumlink($this->current_forum, ($this->curr_page + $i)) . "'>" . (($this->curr_page + 1) + $i) . "</a>";

        if ($num_pages - $this->curr_page >= 5)
          $out .= " >> <a href='" . $this->get_forumlink($this->current_forum, ($num_pages - 1)) . "'>" . __("Last", "asgarosforum") . "</a>";
      }

      return "<span class='wpf-pages'>" . $out . "</span>";
    }

    public function remove_topic($forum_id)
    {
      global $user_ID, $wpdb;

      $topic = $_GET['topic'];

      if ($this->is_moderator($user_ID, $forum_id))
      {
        $wpdb->query($wpdb->prepare("DELETE FROM {$this->t_posts} WHERE `parent_id` = %d", $topic));
        $wpdb->query($wpdb->prepare("DELETE FROM {$this->t_threads} WHERE `id` = %d", $topic));
      }
      else
        wp_die(__("An unknown error has occured. Please try again.", "asgarosforum"));
    }

    public function getNewForumID()
    {
      global $user_ID;

      $topic = !empty($_GET['topic']) ? (int) $_GET['topic'] : 0;
      $topic = !empty($_GET['t']) ? (int) $_GET['t'] : $topic;

      if ($this->is_moderator($user_ID, $this->current_forum))
      {
        $currentForumID = $this->check_parms($_GET['f']);
        $strOUT = '
        <form id="" method="post" action="' . $this->base_url . 'viewforum&f=' . $currentForumID . '&move_topic&topic=' . $topic . '">
        Move "<strong>' . $this->get_subject($topic) . '</strong>" to new forum: <select id="newForumID" name="newForumID" onchange="location=\'' . $this->base_url . 'viewforum&f=' . $currentForumID . '&move_topic&topic=' . $topic . '&newForumID=\'+this.options[this.selectedIndex].value">';
        $frs = $this->get_forums();

        foreach ($frs as $f)
          $strOUT .= '<option value="' . $f->id . '"' . ($f->id == $currentForumID ? ' selected="selected"' : '') . '>' . $f->name . '</option>';

        $strOUT .= '</select><noscript><input type="submit" value="Go!" /></noscript></form>';

        return $strOUT;
      }
      else
        wp_die(__("An unknown error has occured. Please try again.", "asgarosforum"));
    }

    public function move_topic($forum_id)
    {
      global $user_ID, $wpdb;

      $topic = $_GET['topic'];
      $newForumID = !empty($_GET['newForumID']) ? (int) $_GET['newForumID'] : 0;
      $newForumID = !empty($_POST['newForumID']) ? (int) $_POST['newForumID'] : $newForumID;

      if ($this->is_moderator($user_ID, $forum_id))
      {
        $strSQL = $wpdb->prepare("UPDATE {$this->t_threads} SET `parent_id` = {$newForumID} WHERE id = %d", $topic);
        $wpdb->query($strSQL);
        header("Location: " . $this->base_url . "viewforum&f=" . $newForumID);
        exit;
      }
      else
        wp_die(__("You do not have permission to move this topic.", "asgarosforum"));
    }

    public function remove_post()
    {
      global $user_ID, $wpdb;

      $id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : 0;
      $post = $wpdb->get_row($wpdb->prepare("SELECT author_id, parent_id FROM {$this->t_posts} WHERE id = %d", $id));

      if ($this->is_moderator($user_ID, $this->current_forum) || $user_ID == $post->author_id)
      {
        $wpdb->query($wpdb->prepare("DELETE FROM {$this->t_posts} WHERE id = %d", $id));
        $nbmsg = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->t_posts} WHERE parent_id = %d", $post->parent_id));

        if (!$nbmsg)
          $wpdb->query($wpdb->prepare("DELETE FROM {$this->t_threads} WHERE id = %d", $post->parent_id));

        $this->o .= "<div class='wpf-info'><div class='updated'><span aria-hidden='true' class='icon-warning'>" . __("Post deleted", "asgarosforum") . "</div></div>";
      }
      else
        wp_die(__("You do not have permission to delete this post.", "asgarosforum"));
    }

    public function sticky_post()
    {
      global $user_ID, $wpdb;

      if (!$this->is_moderator($user_ID, $this->current_forum))
        wp_die(__("An unknown error has occured. Please try again.", "asgarosforum"));

      $id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : 0;
      $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$this->t_threads} WHERE id = %d", $id));

      switch ($status)
      {
        case 'sticky':
          $wpdb->query($wpdb->prepare("UPDATE {$this->t_threads} SET status = 'open' WHERE id = %d", $id));
          break;
        case 'open':
          $wpdb->query($wpdb->prepare("UPDATE {$this->t_threads} SET status = 'sticky' WHERE id = %d", $id));
          break;
      }
    }

    public function is_sticky($thread_id = '')
    {
      global $wpdb;

      if ($thread_id)
        $id = $thread_id;
      else
        $id = $this->current_thread;

      $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$this->t_threads} WHERE id = %d", $id));

      if ($status == "sticky")
        return true;
      else
        return false;
    }

    public function closed_post()
    {
      global $user_ID, $wpdb;

      if (!$this->is_moderator($user_ID, $this->current_forum))
        wp_die(__("An unknown error has occured. Please try again.", "asgarosforum"));

      $strSQL = "UPDATE {$this->t_threads} SET closed = %d WHERE id = %d";

      $wpdb->query($wpdb->prepare($strSQL, (int) $_GET['closed'], (int) $_GET['id']));
    }

    public function is_closed($thread_id = '')
    {
      global $wpdb;

      if ($thread_id)
        $id = $thread_id;
      else
        $id = $this->current_thread;

      $strSQL = $wpdb->prepare("SELECT closed FROM {$this->t_threads} WHERE id = %d", $id);
      $closed = $wpdb->get_var($strSQL);

      if ($closed)
        return true;
      else
        return false;
    }

    public function allow_unreg()
    {
      if ($this->options['forum_require_registration'] == false)
        return true;

      return false;
    }

    public function profile_link($user_id, $toWrap = false)
    {
      if ($toWrap)
        $user = wordwrap($this->get_userdata($user_id, $this->options['forum_display_name']), 22, "-<br/>", 1);
      else
        $user = $this->get_userdata($user_id, $this->options['forum_display_name']);

      $link = "{$user}";

      if ($user == __("Guest", "asgarosforum"))
        return $user;

      return $link;
    }

    public function form_buttons()
    {
      $button = '<div class="forum_buttons"><a title="' . __("Bold", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[b]", "[/b]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/b.png" /></a><a title="' . __("Italic", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[i]", "[/i]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/i.png" /></a><a title="' . __("Underline", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[u]", "[/u]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/u.png" /></a><a title="' . __("Strikethrough", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[s]", "[/s]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/s.png" /></a><a title="' . __("Font size in pixels") . '" href="javascript:void(0);" onclick=\'surroundText("[font size=]", "[/font]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/size.png" /></a><a title="Image or text to center" href="javascript:void(0);" onclick=\'surroundText("[center]", "[/center]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/center.png" /></a><a title="Align image or text left" href="javascript:void(0);" onclick=\'surroundText("[left]", "[/left]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/left.png" /></a><a title="Right align image or text " href="javascript:void(0);" onclick=\'surroundText("[right]", "[/right]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/right.png" /></a><a title="' . __("Code", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[code]", "[/code]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/code.png" /></a><a title="' . __("Quote", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[quote]", "[/quote]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/quote.png" /></a><a title="' . __("Quote Title", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[quotetitle]", "[/quotetitle]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/quotetitle.png" /></a><a title="' . __("List", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[list]", "[/list]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/list.png" /></a><a title="' . __("List item", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[*]", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/li.png" /></a><a title="' . __("Link", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[url]", "[/url]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/url.png" /></a><a title="' . __("Image", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[img]", "[/img]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/img.png" /></a><a title="' . __("Email", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[email]", "[/email]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/email.png" /></a><a title="' . __("Add Hex Color", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[color=#]", "[/color]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/color.png" /></a><a title="' . __("Embed YouTube Video", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[embed]", "[/embed]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/yt.png" /></a><a title="' . __("Embed Google Map", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText("[map]", "[/map]", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/bbc/gm.png" /></a></div>';

      return $button;
    }

    public function form_smilies()
    {
      $button = '<div class="forum_smilies"><a title="' . __("Smile", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :) ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/smile.gif" /></a><a title="' . __("Big Grin", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :D ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/biggrin.gif" /></a><a title="' . __("Sad", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :( ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/sad.gif" /></a><a title="' . __("Neutral", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :| ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/neutral.gif" /></a><a title="' . __("Razz", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :P ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/razz.gif" /></a><a title="' . __("Mad", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :x ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/mad.gif" /></a><a title="' . __("Confused", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :? ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/confused.gif" /></a><a title="' . __("Eek!", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" 8O ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/eek.gif" /></a><a title="' . __("Wink", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" ;) ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/wink.gif" /></a><a title="' . __("Surprised", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :o ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/surprised.gif" /></a><a title="' . __("Cool", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" 8-) ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/cool.gif" /></a><a title="' . __("confused", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :? ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/confused.gif" /></a><a title="' . __("Lol", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :lol: ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/lol.gif" /></a><a title="' . __("Cry", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :cry: ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/cry.gif" /></a><a title="' . __("redface", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :oops: ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/redface.gif" /></a><a title="' . __("rolleyes", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :roll: ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/rolleyes.gif" /></a><a title="' . __("exclaim", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :!: ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/exclaim.gif" /></a><a title="' . __("question", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :?: ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/question.gif" /></a><a title="' . __("idea", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :idea: ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/idea.gif" /></a><a title="' . __("arrow", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :arrow: ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/arrow.gif" /></a><a title="' . __("mrgreen", "asgarosforum") . '" href="javascript:void(0);" onclick=\'surroundText(" :mrgreen: ", "", document.forms.addform.message); return false;\'><img src="' . $this->skin_url . '/images/smilies/mrgreen.gif" /></a></div>';

      return $button;
    }

    public function num_post_user($user)
    {
      global $wpdb;

      return $wpdb->get_var($wpdb->prepare("SELECT COUNT(author_id) FROM {$this->t_posts} WHERE author_id = %d", $user));
    }

    public function search_results()
    {
      global $wpdb;

      $o = "";
      $this->current_view = SEARCH;
      $search_string = esc_sql($_POST['search_words']);

      $sql = $wpdb->prepare("SELECT {$this->t_posts}.id, `text`, {$this->t_threads}.subject, {$this->t_posts}.parent_id, {$this->t_posts}.`date`, {$this->t_posts}.author_id, MATCH (`text`) AGAINST (%s) AS score
      FROM {$this->t_posts} JOIN {$this->t_threads} ON {$this->t_posts}.parent_id = {$this->t_threads}.id
      AND MATCH (`text`) AGAINST (%s)
      ORDER BY score DESC LIMIT 50", $search_string, $search_string);

      $results = $wpdb->get_results($sql);
      $max = 0;

      foreach ($results as $result)
        if ($result->score > $max)
          $max = $result->score;

      if ($results)
        $const = 100 / $max;

      $o .= "<table class='wpf-table' width='100%'>
          <tr>
            <th width='7%'>Status</th>
            <th width='54%'>" . __("Subject", "asgarosforum") . "</th>
            <th>" . __("Relevance", "asgarosforum") . "</th>
            <th>" . __("Started by", "asgarosforum") . "</th>
            <th>" . __("Posted", "asgarosforum") . "</th>
          </tr>";

      foreach ($results as $result)
      {
        if ($this->have_access($this->forum_get_group_from_post($result->parent_id)))
        {
          $o .= "<tr>
                <td valign='top' align='center'>" . $this->get_topic_image($result->parent_id) . "</td>
                <td valign='top'><a href='" . $this->get_threadlink($result->parent_id) . "'>" . stripslashes($result->subject) . "</a>
                </td>
                <td valign='top'><small>" . round($result->score * $const, 1) . "%</small></td>
                <td valign='top' nowrap='nowrap'><span class='img-avatar-forumstats' >" . $this->get_avatar($result->author_id, 15) . "</span>" . $this->profile_link($result->author_id) . "</td>
                <td valign='top' nowrap='nowrap'>" . $this->format_date($result->date) . "</td>
              </tr>";
        }
      }

      $o .= "</table>";
      $this->o .= $o;
    }

    public function get_topic_image($thread)
    {
      if ($this->is_closed($thread))
        return "<img src='{$this->skin_url}/images/topic/closed.png' alt='" . __("Closed topic", "asgarosforum") . "' title='" . __("Closed topic", "asgarosforum") . "'>";

        if ($this->check_unread($thread)) {
            return "<img src='{$this->skin_url}/images/new_some.png' alt='" . __("Normal unread topic", "asgarosforum") . "' title='" . __("Normal unread topic", "asgarosforum") . "'>";
        } else {
            return "<img src='{$this->skin_url}/images/new_none.png' alt='" . __("Normal topic", "asgarosforum") . "' title='" . __("Normal topic", "asgarosforum") . "'>";
        }
    }

    public function get_captcha()
    {
      global $user_ID;

      $out = "";

      if (!$user_ID)
      {
        include_once("captcha/shared.php");
        include_once("captcha/captcha_code.php");
        $wpf_captcha = new CaptchaCode();
        $wpf_code = wpf_str_encrypt($wpf_captcha->generateCode(6));

        $out .= "<tr>
              <td><img alt='' src='" . WPFURL . "captcha/captcha_images.php?width=120&height=40&code=" . $wpf_code . "' />
              <input type='hidden' name='wpf_security_check' value='" . $wpf_code . "'></td>
              <td>" . __("Security Code:", "asgarosforum") . "<input id='wpf_security_code' name='wpf_security_code' type='text' class='wpf-input'/></td>
              </tr>";
      }

      return $out;
    }

    public function get_quick_reply_captcha()
    {
      global $user_ID;

      $out = "";
      $out .= apply_filters('wpwf_quick_form_guestinfo', ""); //--weaver-- show the guest info form

      if (!$user_ID)
      {
        include_once("captcha/shared.php");
        include_once("captcha/captcha_code.php");
        $wpf_captcha = new CaptchaCode();
        $wpf_code = wpf_str_encrypt($wpf_captcha->generateCode(6));
        $out .= "
                  <img src='" . WPFURL . "captcha/captcha_images.php?width=120&height=40&code=" . $wpf_code . "' />
                  <input type='hidden' name='wpf_security_check' value='" . $wpf_code . "'><br/>
                  <input id='wpf_security_code' name='wpf_security_code' type='text' class='wpf-input'/>" . __("Enter Security Code: (required)", "asgarosforum");
      }

      return $out;
    }

    public function autoembed($string)
    {
      global $wp_embed;

      if (is_object($wp_embed))
        return $wp_embed->autoembed($string);
      else
        return $string;
    }

    public function rewriting_on()
    {
      $permalink_structure = get_option('permalink_structure');

      return ($permalink_structure and !empty($permalink_structure));
    }

    //SEO Friendly URL stuff
    public function get_seo_friendly_query()
    {
      $end = array();
      $request_uri = $_SERVER['REQUEST_URI'];
      $link = str_replace(site_url(), '', get_permalink($this->page_id));
      $uri = explode('/', trim(str_replace($link, '', $request_uri), '/'));

      if (array_count_values($uri))
      {
        $m = end($uri);
        $found = '';
        preg_match("/.*-(forum|thread)(\d*(\.?\d+)?)$/", $m, $found);
      }

      if (!empty($found))
        $end = array('action' => $found[1], 'id' => $found[2]);

      return $end;
    }

    public function get_seo_friendly_title($str, $replace = array())
    {
      if (!empty($replace)) //Currently not used
        $str = str_replace((array) $replace, ' ', $str);

      if (function_exists('ctl_sanitize_title')) //perfect for crillic languages
        return ctl_sanitize_title($str);

      return sanitize_title_with_dashes($str); //Seems to work for most other languages
    }

    public function flush_wp_rewrite_rules()
    {
      global $wp_rewrite;

      $wp_rewrite->flush_rules();
    }

public function set_seo_friendly_rules($args)
{
    $new = array();
    $link = trim(str_replace(array(site_url(), 'index.php/'), '', get_permalink($this->page_id)), '/');
    $new['(' . $link . ')(/[-/0-9a-zA-Z]+)?/(.*)$'] = 'index.php?pagename=$matches[1]&page=$matches[2]';

    return $new + $args;
}


}

  // End class
} // End
?>
