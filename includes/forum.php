<?php

if (!defined('ABSPATH')) exit;

class AsgarosForum {
    var $version = '1.9.3';
    var $executePlugin = false;
    var $db = null;
    var $tables = null;
    var $directory = '';
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
    var $options = array();
    var $options_default = array(
        'location'                  => 0,
        'posts_per_page'            => 10,
        'topics_per_page'           => 20,
        'members_per_page'          => 25,
        'minimalistic_editor'       => true,
        'allow_shortcodes'          => false,
        'allow_guest_postings'      => false,
        'allowed_filetypes'         => 'jpg,jpeg,gif,png,bmp,pdf',
        'allow_file_uploads'        => false,
        'upload_permission'         => 'loggedin',
        'hide_uploads_from_guests'  => false,
        'hide_profiles_from_guests' => false,
        'uploads_maximum_number'    => 5,
        'uploads_maximum_size'      => 5,
        'uploads_show_thumbnails'   => true,
        'admin_subscriptions'       => false,
        'allow_subscriptions'       => true,
        'notification_sender_name'  => '',
        'notification_sender_mail'  => '',
        'allow_signatures'          => false,
        'enable_seo_urls'           => true,
        'enable_mentioning'         => true,
        'enable_reactions'          => true,
        'enable_search'             => true,
        'enable_profiles'           => true,
        'enable_memberslist'        => true,
        'enable_activity'           => false,
        'reports_enabled'           => true,
        'reports_notifications'     => true,
        'memberslist_loggedin_only' => false,
        'show_login_button'         => true,
        'show_logout_button'        => true,
        'show_register_button'      => true,
        'show_who_is_online'        => true,
        'show_statistics'           => true,
        'enable_breadcrumbs'        => true,
        'breadcrumbs_show_category' => true,
        'highlight_admin'           => true,
        'highlight_authors'         => true,
        'show_author_posts_counter' => true,
        'show_edit_date'            => true,
        'time_limit_edit_posts'     => 0,
        'show_description_in_forum' => false,
        'require_login'             => false,
        'create_blog_topics'        => false,
        'create_blog_topics_id'     => 0
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

    function __construct() {
        // Initialize database.
        global $wpdb;
        $database = new AsgarosForumDatabase();
        $this->tables = $database->getTables();
        $this->db = $wpdb;

        $this->directory = plugin_dir_url(dirname(__FILE__));
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
        if ($this->options['create_blog_topics']) {
            add_action('transition_post_status', array($this, 'createBlogTopic'), 10, 3);
        }

        new AsgarosForumCompatibility($this);
        new AsgarosForumPermissions($this);
        new AsgarosForumUnread($this);
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
    }

    //======================================================================
    // FUNCTIONS FOR GETTING AND SETTING OPTIONS.
    //======================================================================

    function loadOptions() {
        $this->options = array_merge($this->options_default, get_option('asgarosforum_options', array()));
        $this->options_editor['teeny'] = $this->options['minimalistic_editor'];
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

        // Prepare unread status.
        AsgarosForumUnread::prepareUnreadStatus();

        // Update online status.
        $this->online->update_online_status();

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
                $this->profile->setCurrentView();
                break;
            case 'members':
                // Go back to the overview when this functionality is not enabled.
                if (!$this->memberslist->functionalityEnabled()) {
                    $this->current_view = 'overview';
                }
                break;
            case 'activity':
                if (!$this->activity->functionality_enabled()) {
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
        }

        // Mark visited topic as read.
        if ($this->current_view === 'topic' && $this->current_topic) {
            AsgarosForumUnread::markTopicRead();
        }

        do_action('asgarosforum_prepare_'.$this->current_view);
    }

    function check_access() {
        // Check login access.
        if ($this->options['require_login'] && !is_user_logged_in()) {
            $this->error = __('Sorry, only logged in users have access to the forum.', 'asgaros-forum');
            $this->error = apply_filters('asgarosforum_filter_error_message_require_login', $this->error);
            return;
        }

        // Check category access.
        $this->category_access_level = get_term_meta($this->current_category, 'category_access', true);

        if ($this->category_access_level) {
            if ($this->category_access_level === 'loggedin' && !is_user_logged_in()) {
                $this->error = __('Sorry, only logged in users have access to this category.', 'asgaros-forum');
                return;
            }

            if ($this->category_access_level === 'moderator' && !AsgarosForumPermissions::isModerator('current')) {
                $this->error = __('Sorry, you dont have access to this area.', 'asgaros-forum');
                return;
            }
        }

        // Check user groups access.
        if (!AsgarosForumUserGroups::checkAccess($this->current_category)) {
            $this->error = __('Sorry, you dont have access to this area.', 'asgaros-forum');
            return;
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

    // Gets the pages main title.
    public function getMainTitle($setDefaultTitle = true) {
        $mainTitle = false;

        if ($setDefaultTitle) {
            $mainTitle = __('Overview', 'asgaros-forum');
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
                $mainTitle = $this->profile->getCurrentTitle();
            } else if ($this->current_view === 'members') {
                $mainTitle = __('Members', 'asgaros-forum');
            } else if ($this->current_view === 'activity') {
                $mainTitle = __('Activity', 'asgaros-forum');
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

    function forum() {
        ob_start();
        echo '<div id="af-wrapper">';

        do_action('asgarosforum_'.$this->current_view.'_custom_content_top');

        // Show Header Area except for single posts.
        if ($this->current_view !== 'post') {
            $this->showHeader();
        }

        if (!empty($this->error)) {
            echo '<div class="error">'.$this->error.'</div>';
        } else {
            if ($this->current_view === 'post') {
                $this->showSinglePost();
            } else {
                if (!empty($this->info)) {
                    echo '<div class="info">'.$this->info.'</div>';
                }

                $this->showLoginMessage();
                $this->showMainTitleAndDescription();

                switch ($this->current_view) {
                    case 'search':
                        include('views/search.php');
                    break;
                    case 'subscriptions':
                        $this->notifications->show_subscription_overview();
                    break;
                    case 'movetopic':
                        $this->showMoveTopic();
                    break;
                    case 'forum':
                        $this->showforum();
                    break;
                    case 'topic':
                        $this->showTopic();
                    break;
                    case 'addtopic':
                    case 'addpost':
                    case 'editpost':
                        $this->editor->showEditor();
                    break;
                    case 'profile':
                        $this->profile->showProfile();
                    break;
                    case 'members':
                        $this->memberslist->showMembersList();
                    break;
                    case 'activity':
                        $this->activity->show_activity();
                    break;
                    default:
                        $this->overview();
                    break;
                }
            }
        }

        do_action('asgarosforum_'.$this->current_view.'_custom_content_bottom');

        echo '<div class="clear"></div>';
        echo '</div>';
        return ob_get_clean();
    }

    function showMainTitleAndDescription() {
        $mainTitle = $this->getMainTitle();

        // Show lock symbol for closed topics.
        if ($this->current_view == 'topic' && $this->get_status('closed')) {
            echo '<h1 class="main-title dashicons-before dashicons-lock">'.$mainTitle.'</h1>';
        } else {
            echo '<h1 class="main-title">'.$mainTitle.'</h1>';
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
        $avatars_available = get_option('show_avatars');
        $topicStarter = $this->get_topic_starter($this->current_topic);
        $post = $this->content->get_post($this->current_post);

        echo '<div class="title-element"></div>';
        require('views/post-element.php');
    }

    function showforum() {
        $topics = $this->get_topics($this->current_forum);
        $sticky_topics = $this->content->get_sticky_topics($this->current_forum);
        $counter_normal = count($topics);
        $counter_total = $counter_normal + count($sticky_topics);

        require('views/forum.php');
    }

    function render_topic_element($topic_object, $topic_type = 'topic-normal') {
        $lastpost_data = $this->get_lastpost_in_topic($topic_object->id);
        $unread_status = AsgarosForumUnread::getStatusTopic($topic_object->id);
        $topic_title = esc_html(stripslashes($topic_object->name));

        echo '<div class="topic '.$topic_type.'">';
            echo '<div class="topic-status dashicons-before dashicons-'.$topic_object->status.' '.$unread_status.'"></div>';
            echo '<div class="topic-name">';
                echo '<a href="'.$this->get_link('topic', $topic_object->id).'" title="'.$topic_title.'">'.$topic_title.'</a>';
                echo '<small>';
                echo __('By', 'asgaros-forum').'&nbsp;'.$this->getUsername($topic_object->author_id);
                $topic_pagination = new AsgarosForumPagination($this);
                $topic_pagination->renderTopicOverviewPagination($topic_object->id);
                echo '</small>';

                // Show topic stats.
                echo '<small class="topic-stats">';
                    $count_answers_i18n = number_format_i18n($topic_object->answers);
                    $count_views_i18n = number_format_i18n($topic_object->views);
                    echo sprintf(_n('%s Answer', '%s Answers', $topic_object->answers, 'asgaros-forum'), $count_answers_i18n);
                    echo '&nbsp;&middot;&nbsp;';
                    echo sprintf(_n('%s View', '%s Views', $topic_object->views, 'asgaros-forum'), $count_views_i18n);
                echo '</small>';

                // Show lastpost info.
                echo '<small class="topic-lastpost-small">';
                    echo $this->get_lastpost($lastpost_data, 'topic', true);
                echo '</small>';
            echo '</div>';
            do_action('asgarosforum_custom_topic_column', $topic_object->id);
            echo '<div class="topic-poster">'.$this->get_lastpost($lastpost_data, 'topic').'</div>';
        echo '</div>';
    }

    function showTopic() {
        // Create a unique slug for this topic if necessary.
        $topic = $this->getTopic($this->current_topic);

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
        $this->db->query($this->db->prepare("UPDATE {$this->tables->topics} SET views = views + 1 WHERE id = %d", $this->current_topic));
    }

    function showLoginMessage() {
        if (!is_user_logged_in() && !$this->options['allow_guest_postings']) {
            $loginMessage = '<div class="info">'.__('You need to log in to create posts and topics.', 'asgaros-forum').'</div>';
            $loginMessage = apply_filters('asgarosforum_filter_login_message', $loginMessage);
            echo $loginMessage;
        }
    }

    function showMoveTopic() {
        if (AsgarosForumPermissions::isModerator('current')) {
            $strOUT = '<form method="post" action="'.$this->get_link('movetopic', $this->current_topic, array('move_topic' => 1)).'">';
            $strOUT .= '<div class="title-element">'.sprintf(__('Move "<strong>%s</strong>" to new forum:', 'asgaros-forum'), esc_html(stripslashes($this->current_topic_name))).'</div>';
            $strOUT .= '<div class="content-element"><div class="notice">';
            $strOUT .= '<select name="newForumID">';

            $categories = $this->content->get_categories();

            if ($categories) {
                foreach ($categories as $category) {
                    $forums = $this->get_forums($category->term_id, 0, true);

                    if ($forums) {
                        foreach ($forums as $forum) {
                            $strOUT .= '<option value="'.$forum->id.'"'.($forum->id == $this->current_forum ? ' selected="selected"' : '').'>'.esc_html($forum->name).'</option>';

                            if ($forum->count_subforums > 0) {
                                $subforums = $this->get_forums($category->term_id, $forum->id, true);

                                foreach ($subforums as $subforum) {
                                    $strOUT .= '<option value="'.$subforum->id.'"'.($subforum->id == $this->current_forum ? ' selected="selected"' : '').'>&mdash; '.esc_html($subforum->name).'</option>';
                                }
                            }
                        }
                    }
                }
            }

            $strOUT .= '</select><br /><input type="submit" value="'.__('Move', 'asgaros-forum').'"></div></div></form>';

            echo $strOUT;
        } else {
            echo '<div class="notice">'.__('You are not allowed to move topics.', 'asgaros-forum').'</div>';
        }
    }

    function get_postlink($topic_id, $post_id, $page = 0) {
        if (!$page) {
            $postNumber = $this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM {$this->tables->posts} WHERE parent_id = %d;", $topic_id));
            $page = ceil($postNumber / $this->options['posts_per_page']);
        }

        return $this->get_link('topic', $topic_id, array('part' => $page), '#postid-'.$post_id);
    }

    function get_category_name($category_id) {
        $category = get_term($category_id, 'asgarosforum-category');

        if ($category) {
            return $category->name;
        } else {
            return false;
        }
    }

    function get_forums($id = false, $parent_forum = 0, $compact = false, $output_type = OBJECT) {
        if ($id) {
            // The compact mode only loads the fields in the forums-table and counts its existing subforums.
            if ($compact) {
                return $this->db->get_results($this->db->prepare("SELECT f.*, (SELECT COUNT(*) FROM {$this->tables->forums} AS sub_f WHERE sub_f.parent_forum = f.id) AS count_subforums FROM {$this->tables->forums} AS f WHERE f.parent_id = %d AND f.parent_forum = %d ORDER BY f.sort ASC;", $id, $parent_forum), $output_type);
            } else {
                return $this->db->get_results($this->db->prepare("SELECT f.*, (SELECT COUNT(*) FROM {$this->tables->topics} AS ct_t, {$this->tables->forums} AS ct_f WHERE ct_t.parent_id = ct_f.id AND (ct_f.id = f.id OR ct_f.parent_forum = f.id)) AS count_topics, (SELECT COUNT(*) FROM {$this->tables->posts} AS cp_p, {$this->tables->topics} AS cp_t, {$this->tables->forums} AS cp_f WHERE cp_p.parent_id = cp_t.id AND cp_t.parent_id = cp_f.id AND (cp_f.id = f.id OR cp_f.parent_forum = f.id)) AS count_posts, (SELECT COUNT(*) FROM {$this->tables->forums} AS csf_f WHERE csf_f.parent_forum = f.id) AS count_subforums FROM {$this->tables->forums} AS f WHERE f.parent_id = %d AND f.parent_forum = %d GROUP BY f.id ORDER BY f.sort ASC;", $id, $parent_forum), $output_type);
            }
        }
    }

    function getSpecificForums($ids) {
        $results = $this->db->get_results("SELECT id, parent_id AS category_id, name FROM {$this->tables->forums} WHERE id IN (".implode(',', $ids).") ORDER BY id ASC;");
        return $results;
    }

    function get_topics($id) {
        $start = $this->current_page * $this->options['topics_per_page'];
        $end = $this->options['topics_per_page'];
        $limit = $this->db->prepare("LIMIT %d, %d", $start, $end);

        $order = apply_filters('asgarosforum_filter_get_threads_order', "(SELECT MAX(id) FROM {$this->tables->posts} AS p WHERE p.parent_id = t.id) DESC");
        $results = $this->db->get_results($this->db->prepare("SELECT t.id, t.name, t.views, t.status, (SELECT author_id FROM {$this->tables->posts} WHERE parent_id = t.id ORDER BY id ASC LIMIT 1) AS author_id, (SELECT (COUNT(*) - 1) FROM {$this->tables->posts} WHERE parent_id = t.id) AS answers FROM {$this->tables->topics} AS t WHERE t.parent_id = %d AND t.status LIKE 'normal%' ORDER BY {$order} {$limit};", $id));
        $results = apply_filters('asgarosforum_filter_get_threads', $results);
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
            return mb_substr($string, 0, $length, 'UTF-8') . ' &hellip;';
        }

        return $string;
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
            if (is_super_admin($user->ID) || user_can($user->ID, 'administrator')) {
                return '<span class="highlight-admin">'.$string.'</span>';
            } else if (AsgarosForumPermissions::isModerator($user->ID)) {
                return '<span class="highlight-moderator">'.$string.'</span>';
            }
        }

        return $string;
    }

    function get_lastpost($lastpost_data, $context = 'forum', $compact = false) {
        $lastpost = false;

        if ($lastpost_data) {
            $lastpost_link = $this->get_link('topic', $lastpost_data->parent_id, array('part' => ceil($lastpost_data->number_of_posts/$this->options['posts_per_page'])), '#postid-'.$lastpost_data->id);

            if ($compact) {
                if ($context === 'forum') {
                    $lastpost = __('Last post in', 'asgaros-forum').'&nbsp;';
                    $lastpost .= '<a href="'.$lastpost_link.'">'.esc_html($this->cut_string(stripslashes($lastpost_data->name), 34)).'</a>&nbsp;';
                    $lastpost .= __('by', 'asgaros-forum').'&nbsp;'.$this->getUsername($lastpost_data->author_id).',&nbsp;';
                    $lastpost .= '<a href="'.$lastpost_link.'">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($lastpost_data->date), current_time('timestamp'))).'</a>';
                } else if ($context === 'topic') {
                    $lastpost = __('Last post by', 'asgaros-forum').'&nbsp;';
                    $lastpost .= $this->getUsername($lastpost_data->author_id).',&nbsp;';
                    $lastpost .= '<a href="'.$lastpost_link.'">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($lastpost_data->date), current_time('timestamp'))).'</a>';
                }
            } else {
                if ($context === 'forum') {
                    $lastpost = '<a href="'.$lastpost_link.'">'.esc_html($this->cut_string(stripslashes($lastpost_data->name), 34)).'</a><br>';
                }

                $lastpost .= '<span class="dashicons-before dashicons-admin-users">'.__('By', 'asgaros-forum').'&nbsp;'.$this->getUsername($lastpost_data->author_id).'</span><br>';
                $lastpost .= '<span class="dashicons-before dashicons-calendar-alt"><a href="'.$lastpost_link.'">'.sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($lastpost_data->date), current_time('timestamp'))).'</a></span>';
            }
        } else if ($context === 'forum') {
            $lastpost = __('No topics yet!', 'asgaros-forum');
        }

        return $lastpost;
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
    function getTopicsByUser($userID) {
        return $this->db->get_results("SELECT * FROM {$this->tables->posts} GROUP BY parent_id HAVING author_id = {$userID};");
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
            if ((is_user_logged_in() && !AsgarosForumPermissions::isBanned('current')) || (!is_user_logged_in() && $this->options['allow_guest_postings'])) {
                // New topic button.
                $menu .= '<div class="forum-menu">';
                $menu .= '<a class="forum-editor-button dashicons-before dashicons-plus-alt" href="'.$this->get_link('topic_add', $this->current_forum).'">';
                $menu .= __('New Topic', 'asgaros-forum');
                $menu .= '</a>';
                $menu .= '</div>';
            }
        }

        $menu = apply_filters('asgarosforum_filter_forum_menu', $menu);

        return $menu;
    }

    function showTopicMenu($showAllButtons = true) {
        $menu = '';

        if (AsgarosForumPermissions::isModerator('current') || (!$this->get_status('closed') && ((is_user_logged_in() && !AsgarosForumPermissions::isBanned('current')) || (!is_user_logged_in() && $this->options['allow_guest_postings'])))) {
            // Reply button.
            $menu .= '<a class="forum-editor-button dashicons-before dashicons-plus-alt" href="'.$this->get_link('post_add', $this->current_topic).'">';
            $menu .= __('Reply', 'asgaros-forum');
            $menu .= '</a>';
        }

        if (AsgarosForumPermissions::isModerator('current') && $showAllButtons) {
            // Move button.
            $menu .= '<a class="dashicons-before dashicons-randomize" href="'.$this->get_link('movetopic', $this->current_topic).'">';
            $menu .= __('Move', 'asgaros-forum');
            $menu .= '</a>';

            // Delete button.
            $menu .= '<a class="dashicons-before dashicons-trash" href="'.$this->get_link('topic', $this->current_topic, array('delete_topic' => 1)).'" onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');">';
            $menu .= __('Delete', 'asgaros-forum');
            $menu .= '</a>';

            if ($this->get_status('sticky')) {
                // Undo sticky button.
                $menu .= '<a class="dashicons-before dashicons-sticky" href="'.$this->get_link('topic', $this->current_topic, array('unsticky_topic' => 1)).'">';
                $menu .= __('Undo Sticky', 'asgaros-forum');
                $menu .= '</a>';
            } else {
                // Sticky button.
                $menu .= '<a class="dashicons-before dashicons-admin-post" href="'.$this->get_link('topic', $this->current_topic, array('sticky_topic' => 1)).'">';
                $menu .= __('Sticky', 'asgaros-forum');
                $menu .= '</a>';
            }

            if ($this->get_status('closed')) {
                // Open button.
                $menu .= '<a class="dashicons-before dashicons-unlock" href="'.$this->get_link('topic', $this->current_topic, array('open_topic' => 1)).'">';
                $menu .= __('Open', 'asgaros-forum');
                $menu .= '</a>';
            } else {
                // Close button.
                $menu .= '<a class="dashicons-before dashicons-lock" href="'.$this->get_link('topic', $this->current_topic, array('close_topic' => 1)).'">';
                $menu .= __('Close', 'asgaros-forum');
                $menu .= '</a>';
            }
        }

        $menu = (!empty($menu)) ? '<div class="forum-menu">'.$menu.'</div>' : $menu;
        $menu = apply_filters('asgarosforum_filter_topic_menu', $menu);

        return $menu;
    }

    function show_post_menu($post_id, $author_id, $counter, $post_date) {
        $menu = '';

        if (is_user_logged_in()) {
            if (AsgarosForumPermissions::isModerator('current') && ($counter > 1 || $this->current_page >= 1)) {
                // Delete button.
                $menu .= '<a class="dashicons-before dashicons-trash" onclick="return confirm(\''.__('Are you sure you want to remove this?', 'asgaros-forum').'\');" href="'.$this->get_link('topic', $this->current_topic, array('post' => $post_id, 'remove_post' => 1)).'">';
                $menu .= __('Delete', 'asgaros-forum');
                $menu .= '</a>';
            }

            $current_user_id = get_current_user_id();
            if (AsgarosForumPermissions::can_edit_post($current_user_id, $post_id, $author_id, $post_date)) {
                // Edit button.
                $menu .= '<a class="dashicons-before dashicons-edit" href="'.$this->get_link('post_edit', $post_id, array('part' => ($this->current_page + 1))).'">';
                $menu .= __('Edit', 'asgaros-forum');
                $menu .= '</a>';
            }
        }

        if (AsgarosForumPermissions::isModerator('current') || (!$this->get_status('closed') && ((is_user_logged_in() && !AsgarosForumPermissions::isBanned('current')) || (!is_user_logged_in() && $this->options['allow_guest_postings'])))) {
            // Quote button.
            $menu .= '<a class="forum-editor-quote-button dashicons-before dashicons-editor-quote" data-value-id="'.$post_id.'" href="'.$this->get_link('post_add', $this->current_topic, array('quote' => $post_id)).'">';
            $menu .= __('Quote', 'asgaros-forum');
            $menu .= '</a>';
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
            echo '<div id="forum-navigation">';
                echo '<a href="'.$this->get_link('home').'">'.__('Forum', 'asgaros-forum').'</a>';

                $this->profile->renderCurrentUsersProfileLink();
                $this->memberslist->renderMembersListLink();
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
            echo '<a href="'.wp_logout_url($this->get_link('current', false, false, '', false)).'">'.__('Logout', 'asgaros-forum').'</a>';
        }
    }

    function showLoginLink() {
        if (!is_user_logged_in() && $this->options['show_login_button']) {
            echo '<a href="'.wp_login_url($this->get_link('current', false, false, '', false)).'">'.__('Login', 'asgaros-forum').'</a>';
        }
    }

    function showRegisterLink() {
        if (!is_user_logged_in() && get_option('users_can_register') && $this->options['show_register_button']) {
            echo '<a href="'.wp_registration_url().'">'.__('Register', 'asgaros-forum').'</a>';
        }
    }

    function delete_topic($topic_id, $admin_action = false) {
        if (AsgarosForumPermissions::isModerator('current')) {
            if ($topic_id) {
                do_action('asgarosforum_before_delete_topic', $topic_id);

                // Delete uploads and reports.
                $posts = $this->db->get_col($this->db->prepare("SELECT id FROM {$this->tables->posts} WHERE parent_id = %d;", $topic_id));
                foreach ($posts as $post) {
                    $this->uploads->delete_post_files($post);
                    $this->reports->remove_report($post);
                    $this->reactions->remove_all_reactions($post);
                }

                $this->db->delete($this->tables->posts, array('parent_id' => $topic_id), array('%d'));
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

        if (AsgarosForumPermissions::isModerator('current') && $newForumID && $this->content->forum_exists($newForumID)) {
            $this->db->update($this->tables->topics, array('parent_id' => $newForumID), array('id' => $this->current_topic), array('%d'), array('%d'));
            wp_redirect(html_entity_decode($this->get_link('topic', $this->current_topic)));
            exit;
        }
    }

    function remove_post() {
        $post_id = (!empty($_GET['post'])) ? absint($_GET['post']) : 0;

        if (AsgarosForumPermissions::isModerator('current') && $this->content->post_exists($post_id)) {
            do_action('asgarosforum_before_delete_post', $post_id);
            $this->db->delete($this->tables->posts, array('id' => $post_id), array('%d'));
            $this->uploads->delete_post_files($post_id);
            $this->reports->remove_report($post_id);
            $this->reactions->remove_all_reactions($post_id);
            do_action('asgarosforum_after_delete_post', $post_id);
        }
    }

    // TODO: Optimize sql-query same as widget-query. (http://stackoverflow.com/a/28090544/4919483)
    function get_lastpost_in_topic($id) {
        if (empty($this->cache['get_lastpost_in_topic'][$id])) {
            $this->cache['get_lastpost_in_topic'][$id] = $this->db->get_row($this->db->prepare("SELECT (SELECT COUNT(*) FROM {$this->tables->posts} AS p_inner WHERE p_inner.parent_id = p.parent_id) AS number_of_posts, p.id, p.date, p.author_id, p.parent_id FROM {$this->tables->posts} AS p INNER JOIN {$this->tables->topics} AS t ON p.parent_id = t.id WHERE p.parent_id = %d ORDER BY p.id DESC LIMIT 1;", $id));
        }

        return $this->cache['get_lastpost_in_topic'][$id];
    }

    // TODO: Optimize sql-query same as widget-query. (http://stackoverflow.com/a/28090544/4919483)
    function get_lastpost_in_forum($id) {
        if (empty($this->cache['get_lastpost_in_forum'][$id])) {
            return $this->db->get_row($this->db->prepare("SELECT (SELECT COUNT(*) FROM {$this->tables->posts} AS p_inner WHERE p_inner.parent_id = p.parent_id) AS number_of_posts, p.id, p.date, p.parent_id, p.author_id, t.name FROM {$this->tables->posts} AS p, {$this->tables->topics} AS t WHERE p.id = (SELECT p_id_query.id FROM {$this->tables->posts} AS p_id_query INNER JOIN {$this->tables->topics} AS t_id_query ON p_id_query.parent_id = t_id_query.id INNER JOIN {$this->tables->forums} AS f_id_query ON t_id_query.parent_id = f_id_query.id WHERE f_id_query.id = %d OR f_id_query.parent_forum = %d ORDER BY p_id_query.id DESC LIMIT 1) AND t.id = p.parent_id;", $id, $id));
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

    // Gets all data of a topic based on its ID.
    public function getTopic($topicID) {
        return $this->db->get_row($this->db->prepare("SELECT * FROM {$this->tables->topics} WHERE id = %d;", $topicID));
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
        $users = count_users();
        return $users['total_users'];
    }
}
