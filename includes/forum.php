<?php

if (!defined('ABSPATH')) exit;

class AsgarosForum {
    var $version = '1.13.3';
    var $executePlugin = false;
    var $db = null;
    var $tables = null;
    var $plugin_url = '';
    var $plugin_path = '';
    var $date_format = '';
    var $time_format = '';
    var $error = false;
    var $info = false;
    var $current_description = false;
    var $current_category = false;
    var $current_forum = false;
    var $current_forum_name = false;
    var $current_topic = false;
    var $current_topic_name = false;
    var $current_post = false;
    var $current_view = false;
    var $current_element = false;
    var $current_page = 0;
    var $parent_forum = false;
    var $parent_forum_name = false;
    var $category_access_level = false;
    var $parents_set = false;

    // We need this flag if want to prevent modifications of core queries in certain cases.
    var $prevent_query_modifications = false;

    var $options = array();
    var $options_default = array(
        'forum_title'                       => '',
        'location'                          => 0,
        'posts_per_page'                    => 10,
        'topics_per_page'                   => 20,
        'members_per_page'                  => 25,
        'minimalistic_editor'               => true,
        'allow_shortcodes'                  => false,
        'embed_content'                     => true,
        'allow_guest_postings'              => false,
        'allowed_filetypes'                 => 'jpg,jpeg,gif,png,bmp,pdf',
        'allow_file_uploads'                => false,
        'upload_permission'                 => 'loggedin',
        'hide_uploads_from_guests'          => false,
        'hide_profiles_from_guests'         => false,
        'hide_spoilers_from_guests'         => false,
        'uploads_maximum_number'            => 5,
        'uploads_maximum_size'              => 5,
        'uploads_show_thumbnails'           => true,
        'admin_subscriptions'               => false,
        'allow_subscriptions'               => true,
        'notification_sender_name'          => '',
        'notification_sender_mail'          => '',
        'receivers_admin_notifications'     => '',
        'mail_template_new_post_subject'    => '',
        'mail_template_new_post_message'    => '',
        'mail_template_new_topic_subject'   => '',
        'mail_template_new_topic_message'   => '',
        'mail_template_mentioned_subject'   => '',
        'mail_template_mentioned_message'   => '',
        'allow_signatures'                  => false,
        'signatures_html_allowed'           => false,
        'signatures_html_tags'              => '<br><a><i><b><u><s><img><strong>',
        'enable_avatars'                    => true,
        'enable_seo_urls'                   => true,
        'enable_mentioning'                 => true,
        'enable_reactions'                  => true,
        'enable_search'                     => true,
        'enable_profiles'                   => true,
        'enable_memberslist'                => true,
        'enable_rss'                        => false,
        'count_topic_views'                 => true,
        'reports_enabled'                   => true,
        'reports_notifications'             => true,
        'memberslist_loggedin_only'         => false,
        'show_login_button'                 => true,
        'show_logout_button'                => true,
        'show_register_button'              => true,
        'show_who_is_online'                => true,
        'show_statistics'                   => true,
        'enable_breadcrumbs'                => true,
        'breadcrumbs_show_category'         => true,
        'highlight_admin'                   => true,
        'highlight_authors'                 => true,
        'show_author_posts_counter'         => true,
        'show_edit_date'                    => true,
        'time_limit_edit_posts'             => 0,
        'show_description_in_forum'         => false,
        'require_login'                     => false,
        'require_login_posts'               => false,
        'create_blog_topics_id'             => 0,
        'enable_ads'                        => true,
        'ads_frequency_categories'          => 2,
        'ads_frequency_forums'              => 4,
        'ads_frequency_topics'              => 8,
        'ads_frequency_posts'               => 6,
        'approval_for'                      => 'guests',
        'enable_activity'                   => true,
        'activity_days'                     => 14
    );
    var $options_editor = array(
        'media_buttons' => false,
        'editor_height' => 250,
        'teeny'         => true,
        'quicktags'     => false
    );
    var $cache          = array();   // Used to store selected database queries.
    var $rewrite        = null;
    var $shortcode      = null;
    var $reports        = null;
    var $profile        = null;
    var $editor         = null;
    var $reactions      = null;
    var $mentioning     = null;
    var $notifications  = null;
    var $appearance     = null;
    var $uploads        = null;
    var $search         = null;
    var $online         = null;
    var $content        = null;
    var $breadcrumbs    = null;
    var $activity       = null;
    var $memberslist    = null;
    var $pagination     = null;
    var $unread         = null;
    var $feed           = null;
    var $permissions    = null;
    var $ads            = null;
    var $approval       = null;
    var $spoilers       = null;

    function __construct() {
        // Initialize database.
        global $wpdb;
        $database = new AsgarosForumDatabase();
        $this->tables = $database->getTables();
        $this->db = $wpdb;

        $this->plugin_url = plugin_dir_url(dirname(__FILE__));
        $this->plugin_path = plugin_dir_path(dirname(__FILE__));
        $this->loadOptions();
        $this->date_format = get_option('date_format');
        $this->time_format = get_option('time_format');

        add_action('wp', array($this, 'prepare'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_front_scripts'));

        // Add filters for modifying the title of the page.
        add_filter('wp_title', array($this, 'change_wp_title'), 100, 3);
        add_filter('document_title_parts', array($this, 'change_document_title_parts'), 100);
        add_filter('pre_get_document_title', array($this, 'change_pre_get_document_title'), 100);

        // Add hook when topics should get created for new blog posts.
        if ($this->options['create_blog_topics_id'] != 0) {
            add_action('transition_post_status', array($this, 'createBlogTopic'), 10, 3);
        }

        add_filter('oembed_dataparse', array($this, 'prevent_oembed_dataparse'), 10, 3);

        // Deleting an user.
        add_action('delete_user_form', array($this, 'delete_user_form_reassign'), 10, 2);
        add_action('deleted_user', array($this, 'deleted_user_reassign'), 10, 2);

        new AsgarosForumCompatibility($this);
        new AsgarosForumStatistics($this);
        new AsgarosForumUserGroups($this);
        new AsgarosForumWidgets($this);

        $this->rewrite          = new AsgarosForumRewrite($this);
        $this->shortcode        = new AsgarosForumShortcodes($this);
        $this->reports          = new AsgarosForumReports($this);
        $this->profile          = new AsgarosForumProfile($this);
        $this->editor           = new AsgarosForumEditor($this);
        $this->reactions        = new AsgarosForumReactions($this);
        $this->mentioning       = new AsgarosForumMentioning($this);
        $this->notifications    = new AsgarosForumNotifications($this);
        $this->appearance       = new AsgarosForumAppearance($this);
        $this->uploads          = new AsgarosForumUploads($this);
        $this->search           = new AsgarosForumSearch($this);
        $this->online           = new AsgarosForumOnline($this);
        $this->content          = new AsgarosForumContent($this);
        $this->breadcrumbs      = new AsgarosForumBreadCrumbs($this);
        $this->activity         = new AsgarosForumActivity($this);
        $this->memberslist      = new AsgarosForumMembersList($this);
        $this->pagination       = new AsgarosForumPagination($this);
        $this->unread           = new AsgarosForumUnread($this);
        $this->feed             = new AsgarosForumFeed($this);
        $this->permissions      = new AsgarosForumPermissions($this);
        $this->ads              = new AsgarosForumAds($this);
        $this->approval         = new AsgarosForumApproval($this);
        $this->spoilers         = new AsgarosForumSpoilers($this);
    }

    //======================================================================
    // FUNCTIONS FOR GETTING AND SETTING OPTIONS.
    //======================================================================

    function loadOptions() {
        // Get current options.
		$current_options = get_option('asgarosforum_options', array());

		// Merge options if current options are present, otherwise use default-options.
		if (!empty($current_options)) {
			$this->options = array_merge($this->options_default, $current_options);
		} else {
			$this->options = $this->options_default;
		}

        $this->options_editor['teeny'] = $this->options['minimalistic_editor'];

        // Ensure default values if some needed files got deleted.
        if (empty($this->options['forum_title'])) {
            $this->options['forum_title'] = __('Forum', 'asgaros-forum');
        }

        if (empty($this->options['notification_sender_name'])) {
            $this->options['notification_sender_name'] = get_bloginfo('name');
        }

        if (empty($this->options['notification_sender_mail'])) {
            $this->options['notification_sender_mail'] = get_bloginfo('admin_email');
        }

        if (empty($this->options['receivers_admin_notifications'])) {
            $this->options['receivers_admin_notifications'] = get_bloginfo('admin_email');
        }

        if (empty($this->options['mail_template_new_post_subject'])) {
            $this->options['mail_template_new_post_subject'] = __('New answer: ###TITLE###', 'asgaros-forum');
        }

        if (empty($this->options['mail_template_new_post_message'])) {
            $this->options['mail_template_new_post_message'] = __('Hello ###USERNAME###,<br><br>You received this message because there is a new answer in a forum-topic you have subscribed to.<br><br>Topic:<br>###TITLE###<br><br>Author:<br>###AUTHOR###<br><br>Answer:<br>###CONTENT###<br><br>Link:<br>###LINK###<br><br>If you dont want to receive these mails anymore you can unsubscribe via the subscription-area. Please dont answer to this mail!', 'asgaros-forum');
        }

        if (empty($this->options['mail_template_new_topic_subject'])) {
            $this->options['mail_template_new_topic_subject'] = __('New topic: ###TITLE###', 'asgaros-forum');
        }

        if (empty($this->options['mail_template_new_topic_message'])) {
            $this->options['mail_template_new_topic_message'] = __('Hello ###USERNAME###,<br><br>You received this message because there is a new forum-topic.<br><br>Topic:<br>###TITLE###<br><br>Author:<br>###AUTHOR###<br><br>Text:<br>###CONTENT###<br><br>Link:<br>###LINK###<br><br>If you dont want to receive these mails anymore you can unsubscribe via the subscription-area. Please dont answer to this mail!', 'asgaros-forum');
        }

        if (empty($this->options['mail_template_mentioned_subject'])) {
            $this->options['mail_template_mentioned_subject'] = __('You have been mentioned!', 'asgaros-forum');
        }

        if (empty($this->options['mail_template_mentioned_message'])) {
            $this->options['mail_template_mentioned_message'] = __('Hello ###USERNAME###,<br><br>You have been mentioned in a forum-post.<br><br>Topic:<br>###TITLE###<br><br>Author:<br>###AUTHOR###<br><br>Text:<br>###CONTENT###<br><br>Link:<br>###LINK###', 'asgaros-forum');
        }
    }

    function saveOptions($options) {
        update_option('asgarosforum_options', $options);

        // Reload options after saving them.
        $this->loadOptions();
    }

    //======================================================================
    // FUNCTIONS FOR PAGE TITLE.
    //======================================================================

    function change_wp_title($title, $sep, $seplocation) {
        return $this->get_title($title);
    }

    function change_document_title_parts($title) {
        $title['title'] = $this->get_title($title['title']);
        return $title;
    }

    function change_pre_get_document_title($title) {
        // Only modify it when a title is already set.
        if (!empty($title)) {
            $title = $this->get_title($title);
        }

        return $title;
    }

    function get_title($title) {
        if ($this->executePlugin) {
            $metaTitle = $this->getMetaTitle();

            if ($metaTitle) {
                $title = $metaTitle.' - '.$title;
            }
        }

        return $title;
    }

    // Gets the pages meta title.
    public function getMetaTitle() {
        // Get the main title by default with disabled default title generation.
        $metaTitle = $this->getMainTitle(false);

        // Apply custom modifications.
        if (!$this->error && $this->current_view) {
            if ($this->current_view === 'forum' && $this->current_forum) {
                $metaTitle = $this->addCurrentPageToString($metaTitle);
            } else if ($this->current_view === 'topic' && $this->current_topic) {
                $metaTitle = $this->addCurrentPageToString($metaTitle);
            }
        }

        return $metaTitle;
    }

    function prepare() {
        global $post;

        if (is_a($post, 'WP_Post') && $this->shortcode->checkForShortcode($post)) {
            $this->executePlugin = true;
            $this->options['location'] = $post->ID;
        }

        // Set all base links.
        if ($this->executePlugin || get_post($this->options['location'])) {
            $this->rewrite->set_links();
        }

        if (!$this->executePlugin) {
            return;
        }

        // Parse the URL.
        $this->rewrite->parse_url();

        // Update online status.
        $this->online->update_online_status();

        do_action('asgarosforum_prepare');

        switch ($this->current_view) {
            case 'forum':
            case 'addtopic':
                $this->setParents($this->current_element, 'forum');
            break;
            case 'movetopic':
            case 'topic':
            case 'addpost':
                $this->setParents($this->current_element, 'topic');
            break;
            case 'editpost':
                $this->setParents($this->current_element, 'post');
            break;
            case 'markallread':
            case 'unread':
            break;
            case 'subscriptions':
                // Go back to the overview when this functionality is not enabled or the user is not logged-in.
                if (!$this->options['allow_subscriptions'] || !is_user_logged_in()) {
                    $this->current_view = 'overview';
                }
            break;
            case 'search':
                // Go back to the overview when this functionality is not enabled.
                if (!$this->options['enable_search']) {
                    $this->current_view = 'overview';
                }
            break;
            case 'profile':
            case 'history':
                if (!$this->profile->functionalityEnabled()) {
                    $this->current_view = 'overview';
                }
            break;
            case 'members':
                // Go back to the overview when this functionality is not enabled.
                if (!$this->memberslist->functionality_enabled()) {
                    $this->current_view = 'overview';
                }
            break;
            case 'activity':
                if (!$this->activity->functionality_enabled()) {
                    $this->current_view = 'overview';
                }
            break;
            case 'unapproved':
                // Ensure that the user is at least a moderator.
                if (!$this->permissions->isModerator('current')) {
                    $this->current_view = 'overview';
                }
            break;
            case 'reports':
                // Ensure that the user is at least a moderator.
                if (!$this->permissions->isModerator('current')) {
                    $this->current_view = 'overview';
                }
            break;
            default:
                $this->current_view = 'overview';
            break;
        }

        $this->shortcode->handleAttributes();

        // Check access.
        $this->check_access();

        // Override editor settings.
        $this->options_editor = apply_filters('asgarosforum_filter_editor_settings', $this->options_editor);

        // Prevent generation of some head-elements.
        remove_action('wp_head', 'rel_canonical');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');

        if (isset($_POST['submit_action'])) {
            $this->content->do_insertion();
        } else if (isset($_GET['move_topic'])) {
            $this->moveTopic();
        } else if (isset($_GET['delete_topic'])) {
            $this->delete_topic($this->current_topic);
        } else if (isset($_GET['remove_post'])) {
            $post_id = (!empty($_GET['post'])) ? absint($_GET['post']) : 0;
            $this->remove_post($post_id);
        } else if (!empty($_POST['sticky_topic']) || isset($_GET['sticky_topic'])) {
            $sticky_mode = 1;

            if (!empty($_POST['sticky_topic'])) {
                $sticky_mode = intval($_POST['sticky_topic']);
            }

            $this->set_sticky($this->current_topic, $sticky_mode);
        } else if (isset($_GET['unsticky_topic'])) {
            $this->set_sticky($this->current_topic, 0);
        } else if (isset($_GET['open_topic'])) {
            $this->change_status('open');
        } else if (isset($_GET['close_topic'])) {
            $this->change_status('closed');
        } else if (isset($_GET['approve_topic'])) {
            $this->approval->approve_topic($this->current_topic);
        } else if (isset($_GET['subscribe_topic'])) {
            $topic_id = $_GET['subscribe_topic'];
            $this->notifications->subscribe_topic($topic_id);
        } else if (isset($_GET['unsubscribe_topic'])) {
            $topic_id = $_GET['unsubscribe_topic'];
            $this->notifications->unsubscribe_topic($topic_id);
        } else if (isset($_GET['subscribe_forum'])) {
            $forum_id = $_GET['subscribe_forum'];
            $this->notifications->subscribe_forum($forum_id);
        } else if (isset($_GET['unsubscribe_forum'])) {
            $forum_id = $_GET['unsubscribe_forum'];
            $this->notifications->unsubscribe_forum($forum_id);
        } else if (isset($_GET['report_add'])) {
            $post_id = (!empty($_GET['post'])) ? absint($_GET['post']) : 0;
            $reporter_id = get_current_user_id();

            $this->reports->add_report($post_id, $reporter_id);
        } else if (!empty($_GET['report_delete']) && is_numeric($_GET['report_delete'])) {
            $this->reports->remove_report($_GET['report_delete']);
        }

        do_action('asgarosforum_prepare_'.$this->current_view);
    }

    function check_access() {
        // Check login access.
        if ($this->options['require_login'] && !is_user_logged_in()) {
            $this->error = __('Sorry, only logged-in users can access the forum.', 'asgaros-forum');
            $this->error = apply_filters('asgarosforum_filter_error_message_require_login', $this->error);
            return;
        }

        // Check category access.
        $this->category_access_level = get_term_meta($this->current_category, 'category_access', true);

        if ($this->category_access_level) {
            if ($this->category_access_level === 'loggedin' && !is_user_logged_in()) {
                $this->error = __('Sorry, only logged-in users can access this category.', 'asgaros-forum');
                return;
            }

            if ($this->category_access_level === 'moderator' && !$this->permissions->isModerator('current')) {
                $this->error = __('Sorry, you cannot access this area.', 'asgaros-forum');
                return;
            }
        }

        // Check usergroups access.
        if (!AsgarosForumUserGroups::checkAccess($this->current_category)) {
            $this->error = __('Sorry, you cannot access this area.', 'asgaros-forum');
            return;
        }

        if ($this->current_view === 'topic' || $this->current_view === 'addpost') {
            // Check topic-access.
            if ($this->options['require_login_posts'] && !is_user_logged_in()) {
                $this->error = __('Sorry, only logged-in users can access this topic.', 'asgaros-forum');
                return;
            }

            // Check unapproved-topic access.
            if (!$this->approval->is_topic_approved($this->current_topic) && !$this->permissions->isModerator('current')) {
                $this->rewrite->redirect('overview');
            }
        }

        // Check custom access.
        $custom_access = apply_filters('asgarosforum_filter_check_access', true, $this->current_category);

        if (!$custom_access) {
            $this->error = __('Sorry, you cannot access this area.', 'asgaros-forum');
            return;
        }
    }

    function enqueue_front_scripts() {
        if (!$this->executePlugin) {
            return;
        }

        wp_enqueue_script('asgarosforum-js', $this->plugin_url.'js/script.js', array('jquery'), $this->version);
        wp_enqueue_style('dashicons');
    }

    // Gets the pages main title.
    public function getMainTitle($setDefaultTitle = true) {
        $mainTitle = false;

        if ($setDefaultTitle) {
            $mainTitle = $this->options['forum_title'];
        }

        if (!$this->error && $this->current_view) {
            if ($this->current_view === 'forum' && $this->current_forum) {
                $mainTitle = esc_html(stripslashes($this->current_forum_name));
            } else if ($this->current_view === 'topic' && $this->current_topic) {
                $mainTitle = esc_html(stripslashes($this->current_topic_name));
            } else if ($this->current_view === 'editpost') {
                $mainTitle = __('Edit Post', 'asgaros-forum');
            } else if ($this->current_view === 'addpost') {
                $mainTitle = __('Post Reply', 'asgaros-forum').': '.esc_html(stripslashes($this->current_topic_name));
            } else if ($this->current_view === 'addtopic') {
                $mainTitle = __('New Topic', 'asgaros-forum');
            } else if ($this->current_view === 'movetopic') {
                $mainTitle = __('Move Topic', 'asgaros-forum');
            } else if ($this->current_view === 'search') {
                $mainTitle = __('Search', 'asgaros-forum');
            } else if ($this->current_view === 'subscriptions') {
                $mainTitle = __('Subscriptions', 'asgaros-forum');
            } else if ($this->current_view === 'profile') {
                $mainTitle = $this->profile->get_profile_title();
            } else if ($this->current_view === 'history') {
                $mainTitle = $this->profile->get_history_title();
            } else if ($this->current_view === 'members') {
                $mainTitle = __('Members', 'asgaros-forum');
            } else if ($this->current_view === 'activity') {
                $mainTitle = __('Activity', 'asgaros-forum');
            } else if ($this->current_view === 'unread') {
                $mainTitle = __('Unread Topics', 'asgaros-forum');
            } else if ($this->current_view === 'unapproved') {
                $mainTitle = __('Unapproved Topics', 'asgaros-forum');
            } else if ($this->current_view === 'reports') {
                $mainTitle = __('Reports', 'asgaros-forum');
            }
        }

        return $mainTitle;
    }

    // Adds the current page to a string.
    function addCurrentPageToString($someString) {
        if ($this->current_page > 0) {
            $currentPage = $this->current_page + 1;
            $someString .= ' - '.__('Page', 'asgaros-forum').' '.$currentPage;
        }

        return $someString;
    }

    // Shows all kind of notices in the upper area.
    function show_notices() {
        if (!empty($this->info)) {
            echo '<div class="info">'.$this->info.'</div>';
        }

        // Shows an info-message when a new unapproved topic got created.
        $this->approval->notice_for_topic_creator();

        // Shows an info-message when there are unapproved topics.
        $this->approval->notice_for_moderators();

        // Shows an info-message when there are new reports.
        $this->reports->notice_for_moderators();

        if (!is_user_logged_in() && !$this->options['allow_guest_postings']) {
            $loginMessage = '<div class="info">'.__('You need to log in to create posts and topics.', 'asgaros-forum').'</div>';
            $loginMessage = apply_filters('asgarosforum_filter_login_message', $loginMessage);
            echo $loginMessage;
        }
    }

    function forum() {
        ob_start();
        echo '<div id="af-wrapper">';

        do_action('asgarosforum_content_top');
        do_action('asgarosforum_'.$this->current_view.'_custom_content_top');

        // Show Header Area except for single posts.
        if ($this->current_view !== 'post') {
            $this->showHeader();

            do_action('asgarosforum_content_header');
        }

        if (!empty($this->error)) {
            echo '<div class="error">'.$this->error.'</div>';
        } else {
            if ($this->current_view === 'post') {
                $this->showSinglePost();
            } else {
                $this->show_notices();
                $this->showMainTitleAndDescription();

                switch ($this->current_view) {
                    case 'search':
                        $this->search->show_search_results();
                    break;
                    case 'subscriptions':
                        $this->notifications->show_subscription_overview();
                    break;
                    case 'movetopic':
                        $this->showMoveTopic();
                    break;
                    case 'forum':
                        $this->show_forum();
                    break;
                    case 'topic':
                        $this->showTopic();
                    break;
                    case 'addtopic':
                    case 'addpost':
                    case 'editpost':
                        $this->editor->showEditor($this->current_view);
                    break;
                    case 'profile':
                        $this->profile->showProfile();
                    break;
                    case 'history':
                        $this->profile->show_history();
                    break;
                    case 'members':
                        $this->memberslist->show_memberslist();
                    break;
                    case 'activity':
                        $this->activity->show_activity();
                    break;
                    case 'unread':
                        $this->unread->show_unread_topics();
                    break;
                    case 'unapproved':
                        $this->approval->show_unapproved_topics();
                    break;
                    case 'reports':
                        $this->reports->show_reports();
                    break;
                    default:
                        $this->overview();
                    break;
                }

                // Action hook for optional bottom navigation elements.
                echo '<div id="bottom-navigation">';
                do_action('asgarosforum_bottom_navigation', $this->current_view);
                echo '</div>';
            }
        }

        do_action('asgarosforum_content_bottom');
        do_action('asgarosforum_'.$this->current_view.'_custom_content_bottom');

        echo '<div class="clear"></div>';
        echo '</div>';
        return ob_get_clean();
    }

    function showMainTitleAndDescription() {
        $mainTitle = $this->getMainTitle();

        // Show lock symbol for closed topics.
        if ($this->current_view == 'topic' && $this->is_topic_closed($this->current_topic)) {
            echo '<h1 class="main-title main-title-'.$this->current_view.' dashicons-before dashicons-lock">'.$mainTitle.'</h1>';
        } else {
            echo '<h1 class="main-title main-title-'.$this->current_view.'">'.$mainTitle.'</h1>';
        }

        if ($this->current_view === 'forum' && $this->options['show_description_in_forum'] && !empty($this->current_description)) {
            $forum_object = $this->content->get_forum($this->current_forum);

            echo '<div class="main-description">'.stripslashes($forum_object->description).'</div>';
        }
    }

    function overview() {
        $categories = $this->content->get_categories();

        require('views/overview.php');
    }

    function showSinglePost() {
        $counter = 0;
        $topicStarter = $this->get_topic_starter($this->current_topic);
        $post = $this->content->get_post($this->current_post);

        echo '<div class="title-element"></div>';
        require('views/post-element.php');
    }

    function show_forum() {
        require('views/forum.php');
    }

    function render_topic_element($topic_object, $topic_type = 'topic-normal', $show_topic_location = false) {
        $lastpost_data = $this->get_lastpost_in_topic($topic_object->id);
        $unread_status = $this->unread->get_status_topic($topic_object->id);
        $topic_title = esc_html(stripslashes($topic_object->name));

        echo '<div class="topic '.$topic_type.'">';
            echo '<div class="topic-status dashicons-before '.$this->get_status_icon($topic_object).' '.$unread_status.'"></div>';
            echo '<div class="topic-name">';
                echo '<a href="'.$this->get_link('topic', $topic_object->id).'" title="'.$topic_title.'">'.$topic_title.'</a>';
                echo '<small>';
                echo __('By', 'asgaros-forum').'&nbsp;'.$this->getUsername($topic_object->author_id);

                // Show the name of the forum in which a topic is located in. This is currently only used for search results.
                if ($show_topic_location) {
                    echo '&nbsp;&middot;&nbsp;';
                    echo __('In', 'asgaros-forum').'&nbsp;';
                    echo '<a href="'.$this->rewrite->get_link('forum', $topic_object->forum_id).'">';
                    echo esc_html(stripslashes($topic_object->forum_name));
                    echo '</a>';
                }

                $this->pagination->renderTopicOverviewPagination($topic_object->id, ($topic_object->answers + 1));

                echo '</small>';

                // Show topic stats.
                echo '<small class="topic-stats">';
                    $count_answers_i18n = number_format_i18n($topic_object->answers);
                    echo sprintf(_n('%s Answer', '%s Answers', $topic_object->answers, 'asgaros-forum'), $count_answers_i18n);

                    if ($this->options['count_topic_views']) {
                        $count_views_i18n = number_format_i18n($topic_object->views);
                        echo '&nbsp;&middot;&nbsp;';
                        echo sprintf(_n('%s View', '%s Views', $topic_object->views, 'asgaros-forum'), $count_views_i18n);
                    }
                echo '</small>';

                // Show lastpost info.
                echo '<small class="topic-lastpost-small">';
                    echo $this->render_lastpost_in_topic($topic_object->id, true);
                echo '</small>';
            echo '</div>';
            do_action('asgarosforum_custom_topic_column', $topic_object->id);
            echo '<div class="topic-poster">'.$this->render_lastpost_in_topic($topic_object->id).'</div>';
        echo '</div>';

        do_action('asgarosforum_after_topic');
    }

    function showTopic() {
        // Create a unique slug for this topic if necessary.
        $topic = $this->content->get_topic($this->current_topic);

        if (empty($topic->slug)) {
            $slug = $this->rewrite->create_unique_slug($topic->name, $this->tables->topics, 'topic');
            $this->db->update($this->tables->topics, array('slug' => $slug), array('id' => $topic->id), array('%s'), array('%d'));
        }

        // Get posts of topic.
        $posts = $this->get_posts();

        if ($posts) {
            $this->incrementTopicViews();

            require('views/topic.php');
        } else {
            echo '<div class="notice">'.__('Sorry, but there are no posts.', 'asgaros-forum').'</div>';
        }
    }

    public function incrementTopicViews() {
        if ($this->options['count_topic_views']) {
            $this->db->query($this->db->prepare("UPDATE {$this->tables->topics} SET views = views + 1 WHERE id = %d", $this->current_topic));
        }
    }

    function showMoveTopic() {
        if ($this->permissions->isModerator('current')) {
            $strOUT = '<form method="post" action="'.$this->get_link('movetopic', $this->current_topic, array('move_topic' => 1)).'">';
            $strOUT .= '<div class="title-element">'.sprintf(__('Move "<strong>%s</strong>" to new forum:', 'asgaros-forum'), esc_html(stripslashes($this->current_topic_name))).'</div>';
            $strOUT .= '<div class="content-element"><div class="notice">';
            $strOUT .= '<select name="newForumID">';

            $categories = $this->content->get_categories();

            if ($categories) {
                foreach ($categories as $category) {
                    $forums = $this->get_forums($category->term_id, 0);

                    if ($forums) {
                        foreach ($forums as $forum) {
                            $strOUT .= '<option value="'.$forum->id.'"'.($forum->id == $this->current_forum ? ' selected="selected"' : '').'>'.esc_html($forum->name).'</option>';

                            if ($forum->count_subforums > 0) {
                                $subforums = $this->get_forums($category->term_id, $forum->id);

                                foreach ($subforums as $subforum) {
                                    $strOUT .= '<option value="'.$subforum->id.'"'.($subforum->id == $this->current_forum ? ' selected="selected"' : '').'>&mdash; '.esc_html($subforum->name).'</option>';
                                }
                            }
                        }
                    }
                }
            }

            $strOUT .= '</select><br><input type="submit" value="'.__('Move', 'asgaros-forum').'"></div></div></form>';

            echo $strOUT;
        } else {
            echo '<div class="notice">'.__('You are not allowed to move topics.', 'asgaros-forum').'</div>';
        }
    }

    function get_category_name($category_id) {
        $category = get_term($category_id, 'asgarosforum-category');

        if ($category) {
            return $category->name;
        } else {
            return false;
        }
    }

    var $topic_counters_cache = false;
    function get_topic_counters() {
        // If the cache is not set yet, create it.
        if ($this->topic_counters_cache === false) {
            // Get all topic-counters of each forum first.
            $topic_counters = $this->db->get_results("SELECT parent_id AS forum_id, COUNT(*) AS topic_counter FROM {$this->tables->topics} WHERE approved = 1 GROUP BY parent_id;");

            // Assign topic-counter for each forum.
            if (!empty($topic_counters)) {
                foreach ($topic_counters as $counter) {
                    $this->topic_counters_cache[$counter->forum_id] = $counter->topic_counter;
                }
            }

            // Now get all subforums.
            $subforums = $this->content->get_all_subforums();

            // Re-assign topic-counter for each forum based on the topic-counters in its subforums.
            if (!empty($subforums)) {
                foreach ($subforums as $subforum) {
                    // Continue if the subforum has no topics.
                    if (!isset($this->topic_counters_cache[$subforum->id])) {
                        continue;
                    }

                    // Re-assign value when the parent-forum has no topics.
                    if (!isset($this->topic_counters_cache[$subforum->parent_forum])) {
                        $this->topic_counters_cache[$subforum->parent_forum] = $this->topic_counters_cache[$subforum->id];
                        continue;
                    }

                    // Otherwise add subforum-topics to the counter of the parent forum.
                    $this->topic_counters_cache[$subforum->parent_forum] = ($this->topic_counters_cache[$subforum->parent_forum] + $this->topic_counters_cache[$subforum->id]);
                }
            }
        }

        return $this->topic_counters_cache;
    }

    function get_forum_topic_counter($forum_id) {
        $counters = $this->get_topic_counters();

        if (isset($counters[$forum_id])) {
            return intval($counters[$forum_id]);
        } else {
            return 0;
        }
    }

    var $post_counters_cache = false;
    function get_post_counters() {
        // If the cache is not set yet, create it.
        if ($this->post_counters_cache === false) {
            // Get all post-counters of each forum first.
            $post_counters = $this->db->get_results("SELECT t.parent_id AS forum_id, COUNT(*) AS post_counter FROM {$this->tables->posts} AS p, {$this->tables->topics} AS t WHERE p.parent_id = t.id AND t.approved = 1 GROUP BY t.parent_id;");

            // Assign post-counter for each forum.
            if (!empty($post_counters)) {
                foreach ($post_counters as $counter) {
                    $this->post_counters_cache[$counter->forum_id] = $counter->post_counter;
                }
            }

            // Now get all subforums.
            $subforums = $this->content->get_all_subforums();

            // Re-assign post-counter for each forum based on the post-counters in its subforums.
            if (!empty($subforums)) {
                foreach ($subforums as $subforum) {
                    // Continue if the subforum has no posts.
                    if (!isset($this->post_counters_cache[$subforum->id])) {
                        continue;
                    }

                    // Re-assign value when the parent-forum has no posts.
                    if (!isset($this->post_counters_cache[$subforum->parent_forum])) {
                        $this->post_counters_cache[$subforum->parent_forum] = $this->post_counters_cache[$subforum->id];
                        continue;
                    }

                    // Otherwise add subforum-posts to the counter of the parent forum.
                    $this->post_counters_cache[$subforum->parent_forum] = ($this->post_counters_cache[$subforum->parent_forum] + $this->post_counters_cache[$subforum->id]);
                }
            }
        }

        return $this->post_counters_cache;
    }

    function get_forum_post_counter($forum_id) {
        $counters = $this->get_post_counters();

        if (isset($counters[$forum_id])) {
            return intval($counters[$forum_id]);
        } else {
            return 0;
        }
    }

    function get_forums($id = false, $parent_forum = 0, $output_type = OBJECT) {
        if ($id) {
            $query_count_subforums = "SELECT COUNT(*) FROM {$this->tables->forums} WHERE parent_forum = f.id";
            return $this->db->get_results($this->db->prepare("SELECT f.*, ({$query_count_subforums}) AS count_subforums FROM {$this->tables->forums} AS f WHERE f.parent_id = %d AND f.parent_forum = %d ORDER BY f.sort ASC;", $id, $parent_forum), $output_type);
        }
    }

    function getSpecificForums($ids) {
        $results = $this->db->get_results("SELECT id, parent_id AS category_id, name FROM {$this->tables->forums} WHERE id IN (".implode(',', $ids).") ORDER BY id ASC;");
        return $results;
    }

    function getSpecificTopics($ids) {
        $results = $this->db->get_results("SELECT t.id, f.parent_id AS category_id, t.name FROM {$this->tables->topics} AS t LEFT JOIN {$this->tables->forums} AS f ON (f.id = t.parent_id) WHERE t.id IN (".implode(',', $ids).") ORDER BY t.id ASC;");
        return $results;
    }

    function get_posts() {
        $start = $this->current_page * $this->options['posts_per_page'];
        $end = $this->options['posts_per_page'];

        $order = apply_filters('asgarosforum_filter_get_posts_order', 'p1.id ASC');
        $results = $this->db->get_results($this->db->prepare("SELECT p1.id, p1.text, p1.date, p1.date_edit, p1.author_id, p1.author_edit, (SELECT COUNT(*) FROM {$this->tables->posts} AS p2 WHERE p2.author_id = p1.author_id) AS author_posts, p1.uploads FROM {$this->tables->posts} AS p1 WHERE p1.parent_id = %d ORDER BY {$order} LIMIT %d, %d;", $this->current_topic, $start, $end));
        $results = apply_filters('asgarosforum_filter_get_posts', $results);
        return $results;
    }

    private $is_first_post_cache = array();
    function is_first_post($post_id, $topic_id = false) {
        // When we dont know the topic-id, we need to figure it out.
        if (!$topic_id) {
            // Check if there exists an current-topic-id.
            if ($this->current_topic) {
                $topic_id = $this->current_topic;
            }
        }

        if (empty($this->is_first_post_cache[$topic_id])) {
            $this->is_first_post_cache[$topic_id] = $this->db->get_var("SELECT id FROM {$this->tables->posts} WHERE parent_id = {$topic_id} ORDER BY id ASC LIMIT 1;");
        }

        if ($post_id == $this->is_first_post_cache[$topic_id]) {
            return true;
        } else {
            return false;
        }
    }

    function cut_string($string, $length = 33) {
        if (strlen($string) > $length) {
            return mb_substr($string, 0, $length, 'UTF-8') . ' &#8230;';
        }

        return $string;
    }

    // TODO: Clean up the complete username logic ...

    function get_plain_username($user_id) {
        if ($user_id) {
            $user = get_userdata($user_id);

            if ($user) {
                return $user->display_name;
            } else {
                return __('Deleted user', 'asgaros-forum');
            }
        } else {
            return __('Guest', 'asgaros-forum');
        }
    }

    /**
     * Returns and caches a username.
     */
    var $cacheGetUsername = array();
    function getUsername($user_id) {
        if ($user_id) {
            if (empty($this->cacheGetUsername[$user_id])) {
                $user = get_userdata($user_id);

                if ($user) {
                    $this->cacheGetUsername[$user_id] = $this->renderUsername($user);
                } else {
                    $this->cacheGetUsername[$user_id] = __('Deleted user', 'asgaros-forum');
                }
            }

            return $this->cacheGetUsername[$user_id];
        } else {
            return __('Guest', 'asgaros-forum');
        }
    }

    /**
     * Renders a username.
     */
    function renderUsername($userObject, $custom_name = false) {
        $user_name = $userObject->display_name;

        if ($custom_name) {
            $user_name = $custom_name;
        }

        $renderedUserName = $user_name;

        $profileLink = $this->profile->getProfileLink($userObject);

        if ($profileLink) {
            $renderedUserName = '<a class="profile-link" href="'.$profileLink.'">'.$user_name.'</a>';;
        } else {
            $renderedUserName = $user_name;
        }

        $renderedUserName = $this->highlight_username($userObject, $renderedUserName);

        return $renderedUserName;
    }

    /**
     * Highlights a username when he is an administrator/moderator.
     */
    function highlight_username($user, $string) {
        if ($this->options['highlight_admin']) {
            if ($this->permissions->isAdministrator($user->ID)) {
                return '<span class="highlight-admin">'.$string.'</span>';
            } else if ($this->permissions->isModerator($user->ID)) {
                return '<span class="highlight-moderator">'.$string.'</span>';
            }
        }

        return $string;
    }

    private $lastpost_forum_cache = false;
    function lastpost_forum_cache() {
        if ($this->lastpost_forum_cache === false) {
            // Get all lastpost-elements of each forum first. Selection on topics is needed here because we only want posts of approved topics.
            $lastpost_elements = $this->db->get_results("SELECT t.parent_id AS forum_id, MAX(p.id) AS id FROM {$this->tables->posts} AS p, {$this->tables->topics} AS t WHERE p.parent_id = t.id AND t.approved = 1 GROUP BY t.parent_id;");

            // Assign lastpost-ids for each forum.
            if (!empty($lastpost_elements)) {
                foreach ($lastpost_elements as $element) {
                    $this->lastpost_forum_cache[$element->forum_id] = $element->id;
                }
            }

            // Now get all subforums.
            $subforums = $this->content->get_all_subforums();

            // Re-assign lastpost-ids for each forum based on the lastposts in its subforums.
            if (!empty($subforums)) {
                foreach ($subforums as $subforum) {
                    // Continue if the subforum has no posts.
                    if (!isset($this->lastpost_forum_cache[$subforum->id])) {
                        continue;
                    }

                    // Re-assign value when the parent-forum has no posts.
                    if (!isset($this->lastpost_forum_cache[$subforum->parent_forum])) {
                        $this->lastpost_forum_cache[$subforum->parent_forum] = $this->lastpost_forum_cache[$subforum->id];
                    }

                    // Otherwise re-assign value when a subforum has a more recent post.
                    if ($this->lastpost_forum_cache[$subforum->id] > $this->lastpost_forum_cache[$subforum->parent_forum]) {
                        $this->lastpost_forum_cache[$subforum->parent_forum] = $this->lastpost_forum_cache[$subforum->id];
                    }
                }
            }
        }
    }

    private $get_lastpost_in_forum_cache = array();
    function get_lastpost_in_forum($forum_id) {
        if (!isset($this->get_lastpost_in_forum_cache[$forum_id])) {
            $this->lastpost_forum_cache();

            if (isset($this->lastpost_forum_cache[$forum_id])) {
                $this->get_lastpost_in_forum_cache[$forum_id] = $this->db->get_row("SELECT p.id, p.date, p.parent_id, p.author_id, t.name FROM {$this->tables->posts} AS p, {$this->tables->topics} AS t WHERE p.id = ".$this->lastpost_forum_cache[$forum_id]." AND t.id = p.parent_id;");
            } else {
                $this->get_lastpost_in_forum_cache[$forum_id] = false;
            }
        }

        return $this->get_lastpost_in_forum_cache[$forum_id];
    }

    function render_lastpost_in_forum($forum_id, $compact = false) {
        $lastpost = $this->get_lastpost_in_forum($forum_id);

        if ($lastpost === false) {
            return '<small class="no-topics">'.__('No topics yet!', 'asgaros-forum').'</small>';
        } else {
            $output = '';
            $post_link = $this->rewrite->get_post_link($lastpost->id, $lastpost->parent_id);

            if ($compact === true) {
                $output .= __('Last post:', 'asgaros-forum');
                $output .= '&nbsp;';
                $output .= '<a href="'.$post_link.'">'.esc_html($this->cut_string(stripslashes($lastpost->name), 34)).'</a>';
                $output .= '&nbsp;&middot;&nbsp;';
                $output .= '<a href="'.$post_link.'">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($lastpost->date), current_time('timestamp'))).'</a>';
                $output .= '&nbsp;&middot;&nbsp;';
                $output .= $this->getUsername($lastpost->author_id);
            } else {
                // Avatar
                if ($this->options['enable_avatars']) {
                    $output .= '<div class="forum-poster-avatar">'.get_avatar($lastpost->author_id, 40, '', '', array('force_display' => true)).'</div>';
                }

                // Summary
                $output .= '<div class="forum-poster-summary">';
                $output .= '<a href="'.$post_link.'">'.esc_html($this->cut_string(stripslashes($lastpost->name), 25)).'</a><br>';
                $output .= '<small>';
                $output .= '<a href="'.$post_link.'">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($lastpost->date), current_time('timestamp'))).'</a>';
                $output .= '&nbsp;&middot;&nbsp;';
                $output .= $this->getUsername($lastpost->author_id);
                $output .= '</small>';
                $output .= '</div>';
            }

            return $output;
        }
    }

    function get_lastpost_in_topic($topic_id) {
        if (empty($this->cache['get_lastpost_in_topic'][$topic_id])) {
            $this->cache['get_lastpost_in_topic'][$topic_id] = $this->db->get_row("SELECT id, date, author_id, parent_id FROM {$this->tables->posts} WHERE parent_id = {$topic_id} ORDER BY id DESC LIMIT 1;");
        }

        return $this->cache['get_lastpost_in_topic'][$topic_id];
    }

    function render_lastpost_in_topic($topic_id, $compact = false) {
        $lastpost = $this->get_lastpost_in_topic($topic_id);
        $output = '';
        $post_link = $this->rewrite->get_post_link($lastpost->id, $lastpost->parent_id);

        if ($compact === true) {
            $output .= __('Last post:', 'asgaros-forum');
            $output .= '&nbsp;';
            $output .= '<a href="'.$post_link.'">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($lastpost->date), current_time('timestamp'))).'</a>';
            $output .= '&nbsp;&middot;&nbsp;';
            $output .= $this->getUsername($lastpost->author_id);

        } else {
            // Avatar
            if ($this->options['enable_avatars']) {
                $output .= '<div class="topic-poster-avatar">'.get_avatar($lastpost->author_id, 40, '', '', array('force_display' => true)).'</div>';
            }

            // Summary
            $output .= '<div class="forum-poster-summary">';
            $output .= '<a href="'.$post_link.'">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($lastpost->date), current_time('timestamp'))).'</a><br>';
            $output .= '<small>';
            $output .= $this->getUsername($lastpost->author_id);
            $output .= '</small>';
            $output .= '</div>';
        }

        return $output;
    }

    function get_topic_starter($topic_id) {
        return $this->db->get_var($this->db->prepare("SELECT author_id FROM {$this->tables->posts} WHERE parent_id = %d ORDER BY id ASC LIMIT 1;", $topic_id));
    }

    function format_date($date, $full = true) {
        if ($full) {
            return date_i18n($this->date_format.', '.$this->time_format, strtotime($date));
        } else {
            return date_i18n($this->date_format, strtotime($date));
        }
    }

    function current_time() {
        return current_time('Y-m-d H:i:s');
    }

    function get_post_author($post_id) {
        return $this->db->get_var($this->db->prepare("SELECT author_id FROM {$this->tables->posts} WHERE id = %d;", $post_id));
    }

    function get_post_date($post_id) {
        return $this->db->get_var($this->db->prepare("SELECT `date` FROM {$this->tables->posts} WHERE id = %d;", $post_id));
    }

    // Returns the topics created by a user.
    function countTopicsByUser($user_id) {
        return $this->db->get_var("SELECT COUNT(*) FROM {$this->tables->posts} WHERE id IN (SELECT MIN(id) FROM {$this->tables->posts} GROUP BY parent_id) AND author_id = {$user_id};");
    }

    function countPostsByUser($userID) {
        return $this->db->get_var("SELECT COUNT(*) FROM {$this->tables->posts} WHERE author_id = {$userID};");
    }

    /**
     * Generating menus for forums, topics and posts.
     */

    function showForumMenu() {
        $menu = '';

        if ($this->forumIsOpen()) {
            if ((is_user_logged_in() && !$this->permissions->isBanned('current')) || (!is_user_logged_in() && $this->options['allow_guest_postings'])) {
                // New topic button.
                $menu .= '<div class="forum-menu">';
                $menu .= '<a class="forum-editor-button dashicons-before dashicons-plus-alt button-normal" href="'.$this->get_link('topic_add', $this->current_forum).'">';
                $menu .= __('New Topic', 'asgaros-forum');
                $menu .= '</a>';
                $menu .= '</div>';
            }
        }

        $menu = apply_filters('asgarosforum_filter_forum_menu', $menu);

        return $menu;
    }

    public function render_sticky_panel() {
        // Cancel if the current user is not at least a moderator.
        if (!$this->permissions->isModerator('current')) {
            return;
        }

        echo '<div id="sticky-panel">';
            echo '<div class="title-element title-element-dark dashicons-before dashicons-admin-post">'.__('Select Sticky Mode:', 'asgaros-forum').'</div>';
            echo '<div class="content-element">';
                echo '<form method="post" action="'.$this->get_link('topic', $this->current_topic).'">';
                    echo '<div class="action-panel">';
                        echo '<label class="action-panel-option">';
                            echo '<input type="radio" name="sticky_topic" value="1">';
                            echo '<span class="action-panel-title dashicons-before dashicons-admin-post">'.__('Sticky', 'asgaros-forum').'</span>';
                            echo '<span class="action-panel-description">';
                                _e('The topic will be sticked to the current forum.', 'asgaros-forum');
                            echo '</span>';
                        echo '</label>';
                        echo '<label class="action-panel-option">';
                            echo '<input type="radio" name="sticky_topic" value="2">';
                            echo '<span class="action-panel-title dashicons-before dashicons-admin-site">'.__('Global Sticky', 'asgaros-forum').'</span>';
                            echo '<span class="action-panel-description">';
                                _e('The topic will be sticked to all forums.', 'asgaros-forum');
                            echo '</span>';
                        echo '</label>';
                    echo '</div>';
                echo '</form>';
            echo '</div>';
        echo '</div>';


    }

    function show_topic_menu($show_all_buttons = true) {
        $menu = '';

        $current_user_id = get_current_user_id();

        // Show answer and certain moderation-buttons only for approved topics.
        if ($this->approval->is_topic_approved($this->current_topic)) {
            if ($this->permissions->can_create_post($current_user_id)) {
                // Reply button.
                $menu .= '<a class="forum-editor-button dashicons-before dashicons-plus-alt button-normal" href="'.$this->get_link('post_add', $this->current_topic).'">';
                $menu .= __('Reply', 'asgaros-forum');
                $menu .= '</a>';
            }

            if ($this->permissions->isModerator('current') && $show_all_buttons) {
                // Move button.
                $menu .= '<a class="dashicons-before dashicons-randomize button-normal" href="'.$this->get_link('movetopic', $this->current_topic).'">';
                $menu .= __('Move', 'asgaros-forum');
                $menu .= '</a>';

                if ($this->is_topic_sticky($this->current_topic)) {
                    // Undo sticky button.
                    $menu .= '<a class="dashicons-before dashicons-sticky button-normal topic-button-unsticky" href="'.$this->get_link('topic', $this->current_topic, array('unsticky_topic' => 1)).'">';
                    $menu .= __('Unsticky', 'asgaros-forum');
                    $menu .= '</a>';
                } else {
                    // Sticky button.
                    $menu .= '<a class="dashicons-before dashicons-admin-post button-normal topic-button-sticky" href="'.$this->get_link('topic', $this->current_topic, array('sticky_topic' => 1)).'">';
                    $menu .= __('Sticky', 'asgaros-forum');
                    $menu .= '</a>';
                }

                if ($this->is_topic_closed($this->current_topic)) {
                    // Open button.
                    $menu .= '<a class="dashicons-before dashicons-unlock button-normal" href="'.$this->get_link('topic', $this->current_topic, array('open_topic' => 1)).'">';
                    $menu .= __('Open', 'asgaros-forum');
                    $menu .= '</a>';
                } else {
                    // Close button.
                    $menu .= '<a class="dashicons-before dashicons-lock button-normal" href="'.$this->get_link('topic', $this->current_topic, array('close_topic' => 1)).'">';
                    $menu .= __('Close', 'asgaros-forum');
                    $menu .= '</a>';
                }
            }
        } else {
            if ($this->permissions->isModerator('current') && $show_all_buttons) {
                // Approve button.
                if (!$this->approval->is_topic_approved($this->current_topic)) {
                    $menu .= '<a class="dashicons-before dashicons-yes button-approve" href="'.$this->get_link('topic', $this->current_topic, array('approve_topic' => 1)).'">';
                    $menu .= __('Approve', 'asgaros-forum');
                    $menu .= '</a>';
                }
            }
        }

        if ($this->permissions->isModerator('current') && $show_all_buttons) {
            // Delete button.
            $menu .= '<a class="dashicons-before dashicons-trash button-delete" href="'.$this->get_link('topic', $this->current_topic, array('delete_topic' => 1)).'" onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');">';
            $menu .= __('Delete', 'asgaros-forum');
            $menu .= '</a>';
        }

        $menu = (!empty($menu)) ? '<div class="forum-menu">'.$menu.'</div>' : $menu;
        $menu = apply_filters('asgarosforum_filter_topic_menu', $menu);

        return $menu;
    }

    function show_post_menu($post_id, $author_id, $counter, $post_date) {
        $menu = '';

        // Only show post-menu when the topic is approved.
        if ($this->approval->is_topic_approved($this->current_topic)) {
            if (is_user_logged_in()) {
                if ($this->permissions->isModerator('current') && ($counter > 1 || $this->current_page >= 1)) {
                    // Delete button.
                    $menu .= '<a class="dashicons-before dashicons-trash" onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');" href="'.$this->get_link('topic', $this->current_topic, array('post' => $post_id, 'remove_post' => 1)).'">';
                    $menu .= __('Delete', 'asgaros-forum');
                    $menu .= '</a>';
                }

                $current_user_id = get_current_user_id();
                if ($this->permissions->can_edit_post($current_user_id, $post_id, $author_id, $post_date)) {
                    // Edit button.
                    $menu .= '<a class="dashicons-before dashicons-edit" href="'.$this->get_link('post_edit', $post_id, array('part' => ($this->current_page + 1))).'">';
                    $menu .= __('Edit', 'asgaros-forum');
                    $menu .= '</a>';
                }
            }

            if ($this->permissions->isModerator('current') || (!$this->is_topic_closed($this->current_topic) && ((is_user_logged_in() && !$this->permissions->isBanned('current')) || (!is_user_logged_in() && $this->options['allow_guest_postings'])))) {
                // Quote button.
                $menu .= '<a class="forum-editor-quote-button dashicons-before dashicons-editor-quote" data-value-id="'.$post_id.'" href="'.$this->get_link('post_add', $this->current_topic, array('quote' => $post_id)).'">';
                $menu .= __('Quote', 'asgaros-forum');
                $menu .= '</a>';
            }
        }

        $menu = (!empty($menu)) ? '<div class="forum-post-menu">'.$menu.'</div>' : $menu;
        $menu = apply_filters('asgarosforum_filter_post_menu', $menu);

        return $menu;
    }

    function showHeader() {
        echo '<div id="forum-header">';
            echo '<div id="forum-navigation-mobile">';
                echo '<a class="dashicons-before dashicons-menu">'.__('Menu', 'asgaros-forum').'</a>';
            echo '</div>';

            echo '<span class="screen-reader-text">'.__('Forum Navigation', 'asgaros-forum').'</span>';

            echo '<div id="forum-navigation">';
                echo '<a class="home-link" href="'.$this->get_link('home').'">'.__('Forum', 'asgaros-forum').'</a>';

                $this->profile->myProfileLink();
                $this->memberslist->show_memberslist_link();
                $this->notifications->show_subscription_overview_link();
                $this->activity->show_activity_link();

                $this->showLoginLink();
                $this->showRegisterLink();
                $this->showLogoutLink();

                do_action('asgarosforum_custom_header_menu');
            echo '</div>';
            $this->search->show_search_input();

            echo '<div class="clear"></div>';
        echo '</div>';

        $this->breadcrumbs->show_breadcrumbs();
    }

    function showLogoutLink() {
        if (is_user_logged_in() && $this->options['show_logout_button']) {
            echo '<a class="logout-link" href="'.wp_logout_url($this->get_link('current', false, false, '', false)).'">'.__('Logout', 'asgaros-forum').'</a>';
        }
    }

    function showLoginLink() {
        if (!is_user_logged_in() && $this->options['show_login_button']) {
            echo '<a class="login-link" href="'.wp_login_url($this->get_link('current', false, false, '', false)).'">'.__('Login', 'asgaros-forum').'</a>';
        }
    }

    function showRegisterLink() {
        if (!is_user_logged_in() && $this->options['show_register_button']) {
            echo '<a class="register-link" href="'.wp_registration_url().'">'.__('Register', 'asgaros-forum').'</a>';
        }
    }

    function delete_topic($topic_id, $admin_action = false) {
        if ($this->permissions->isModerator('current')) {
            if ($topic_id) {
                do_action('asgarosforum_before_delete_topic', $topic_id);

                // Delete uploads and reports.
                $posts = $this->db->get_col($this->db->prepare("SELECT id FROM {$this->tables->posts} WHERE parent_id = %d;", $topic_id));
                foreach ($posts as $post) {
                    $this->remove_post($post);
                }

                $this->db->delete($this->tables->topics, array('id' => $topic_id), array('%d'));
                $this->notifications->remove_all_topic_subscriptions($topic_id);

                do_action('asgarosforum_after_delete_topic', $topic_id);

                if (!$admin_action) {
                    wp_redirect(html_entity_decode($this->get_link('forum', $this->current_forum)));
                    exit;
                }
            }
        }
    }

    function moveTopic() {
        $newForumID = $_POST['newForumID'];

        if ($this->permissions->isModerator('current') && $newForumID && $this->content->forum_exists($newForumID)) {
            $this->db->update($this->tables->topics, array('parent_id' => $newForumID), array('id' => $this->current_topic), array('%d'), array('%d'));
            $this->db->update($this->tables->posts, array('forum_id' => $newForumID), array('parent_id' => $this->current_topic), array('%d'), array('%d'));
            wp_redirect(html_entity_decode($this->get_link('topic', $this->current_topic)));
            exit;
        }
    }

    function remove_post($post_id) {
        if ($this->permissions->isModerator('current') && $this->content->post_exists($post_id)) {
            do_action('asgarosforum_before_delete_post', $post_id);
            $this->uploads->delete_post_files($post_id);
            $this->reports->remove_report($post_id);
            $this->reactions->remove_all_reactions($post_id);
            $this->db->delete($this->tables->posts, array('id' => $post_id), array('%d'));
            do_action('asgarosforum_after_delete_post', $post_id);
        }
    }

    function change_status($property) {
        if ($this->permissions->isModerator('current')) {
            if ($property == 'closed') {
                $this->db->update($this->tables->topics, array('closed' => 1), array('id' => $this->current_topic), array('%d'), array('%d'));
            } else if ($property == 'open') {
                $this->db->update($this->tables->topics, array('closed' => 0), array('id' => $this->current_topic), array('%d'), array('%d'));
            }
        }
    }

    function set_sticky($topic_id, $sticky_mode) {
        if (!$this->permissions->isModerator('current')) {
            return;
        }

        // Ensure that only correct values can get set.
        switch ($sticky_mode) {
            case 0:
            case 1:
            case 2:
                $this->db->update($this->tables->topics, array('sticky' => $sticky_mode), array('id' => $topic_id), array('%d'), array('%d'));
            break;
        }
    }

    function is_topic_sticky($topic_id) {
        $status = $this->db->get_var("SELECT sticky FROM {$this->tables->topics} WHERE id = {$topic_id};");

        if (intval($status) > 0) {
            return true;
        } else {
            return false;
        }
    }

    private $is_topic_closed_cache = array();
    function is_topic_closed($topic_id) {
        if (!isset($this->is_topic_closed_cache[$topic_id])) {
            $status = $this->db->get_var("SELECT closed FROM {$this->tables->topics} WHERE id = {$topic_id};");

            if (intval($status) === 1) {
                $this->is_topic_closed_cache[$topic_id] = true;
            } else {
                $this->is_topic_closed_cache[$topic_id] = false;
            }
        }

        return $this->is_topic_closed_cache[$topic_id];
    }

    function get_status_icon($topic_object) {
        if ($topic_object->sticky > 0) {
            return 'dashicons-topic-sticky';
        } else if ($topic_object->closed == 1) {
            return 'dashicons-topic-closed';
        } else {
            return 'dashicons-topic-normal';
        }
    }

    // Returns TRUE if the forum is opened or the user has at least moderator rights.
    function forumIsOpen() {
        if (!$this->permissions->isModerator('current')) {
            $closed = intval($this->db->get_var($this->db->prepare("SELECT closed FROM {$this->tables->forums} WHERE id = %d;", $this->current_forum)));

            if ($closed === 1) {
                return false;
            }
        }

        return true;
    }

    // Builds and returns a requested link.
    public function get_link($type, $elementID = false, $additionalParameters = false, $appendix = '', $escapeURL = true) {
        return $this->rewrite->get_link($type, $elementID, $additionalParameters, $appendix, $escapeURL);
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
                $this->parents_set          = true;
                return;
            }
        }

        // Assign error message, because when this location is reached, no parents has been set.
        $this->error = $error[$contentType];
    }

    public function createBlogTopic($new_status, $old_status, $post) {
        if ($post->post_type == 'post' && $new_status == 'publish' && $old_status != 'publish') {
            $forumID = $this->options['create_blog_topics_id'];

            $post_title = apply_filters('asgarosforum_filter_automatic_topic_title', $post->post_title, $post);
            $post_content = apply_filters('asgarosforum_filter_automatic_topic_content', $post->post_content, $post);

            if ($this->content->forum_exists($forumID)) {
            	$this->content->insert_topic($forumID, $post_title, $post_content, $post->post_author);
            }
        }
    }

    // Returns the amount of users.
    public function countUsers() {
        $users = count_users('memory');
        return $users['total_users'];
    }

    // Prevents oembed dataparsing for links which points to the own forum.
    function prevent_oembed_dataparse($return, $data, $url) {
        $url_check = strpos($url, $this->rewrite->get_link('home'));

        if ($url_check !== false) {
            return $url;
        }

        return $return;
    }

    public function create_file($path, $content) {
        // Create binding for the file first.
        $binding = @fopen($path, 'wb');

        // Check if the binding could get created.
        if ($binding) {
            // Write the content to the file.
            $writing = @fwrite($binding, $content);

            // Close the file.
            fclose($binding);

            // Clear stat cache so we can set the correct permissions.
            clearstatcache();

            // Get information about the file.
            $file_stats = @stat(dirname($path));

            // Update the permissions of the file.
            $file_permissions = $file_stats['mode'] & 0007777;
    		$file_permissions = $file_permissions & 0000666;
    		@chmod($path, $file_permissions);

            // Clear stat cache again so PHP is aware of the new permissions.
            clearstatcache();

            return true;
        }

        return false;
    }

    public function read_file($path) {
        // Create binding for the file first.
    	$binding = @fopen($path, 'r');

        // Check if the binding could get created.
    	if ($binding) {
            // Ensure that the file is not empty.
    		$file_size = @filesize($path);

    		if (isset($file_size) && $file_size > 0) {
                // Read the complete file.
    			$file_data = fread($binding, $file_size);

                // Close the file.
    			fclose($binding);

                // Return the file data.
    			return $file_data;
    		}
    	}

        return false;
    }

    public function delete_file($path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function delete_user_form_reassign($current_user, $userids) {
        // Remove own ID from users which should get deleted.
        $userids = array_diff($userids, array($current_user->ID));

        // Cancel if there are no users to delete.
        if (empty($userids)) {
            return;
        }

        // Check if users have posts.
        $users_have_content = false;

		if ($this->db->get_var("SELECT ID FROM {$this->tables->posts} WHERE author_id IN(".implode(',', $userids).") LIMIT 1;")) {
			$users_have_content = true;
		}

        // Cancel if users have no posts.
        if ($users_have_content === false) {
            return;
        }

        // Show reassign-options.
        echo '<h2>'.__('Forum', 'asgaros-forum').'</h2>';

        echo '<fieldset>';
        echo '<p><legend>';

        // Count users to delete.
		$go_delete = count($userids);

        if ($go_delete === 1) {
            echo __('What should be done with forum posts owned by this user?', 'asgaros-forum');
        } else {
            echo __('What should be done with forum posts owned by these users?', 'asgaros-forum');
        }
        echo '</legend></p>';

	    echo '<ul style="list-style: none;">';
		echo '<li><input type="radio" id="forum_reassign0" name="forum_reassign" value="no" checked="checked" />';
        echo '<label for="forum_reassign0">'.__('Do not reassign forum posts.', 'asgaros-forum').'</label></li>';

		echo '<li><input type="radio" id="forum_reassign1" name="forum_reassign" value="yes" />';
		echo '<label for="forum_reassign1">'.__('Reassign all forum posts to:', 'asgaros-forum').'</label>&nbsp;';
        wp_dropdown_users(array('name' => 'forum_reassign_user', 'exclude' => $userids, 'show' => 'display_name_with_login'));
		echo '</li></ul></fieldset>';
    }

    public function deleted_user_reassign($id, $reassign) {

        // Ensure that correct values are passed.
        if (empty($_POST['forum_reassign'])) {
            return;
        }

        if ($_POST['forum_reassign'] != 'yes') {
            return;
        }

        if (empty($_POST['forum_reassign_user'])) {
            return;
        }

        // Reassign forum posts.
        $this->db->update($this->tables->posts, array('author_id' => $_POST['forum_reassign_user']), array('author_id' => $id), array('%d'), array('%d'));
    }
}
