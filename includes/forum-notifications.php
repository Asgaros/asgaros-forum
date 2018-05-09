<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumNotifications {
    private $asgarosforum = null;
    public $mailing_list = array();

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_action('asgarosforum_prepare_subscriptions', array($this, 'set_subscription_level'));
    }

    // Generates an (un)subscription link based on subscription status for topics.
    public function show_topic_subscription_link($element_id) {
        // Check if this functionality is enabled and if the user is logged-in.
        if ($this->asgarosforum->options['allow_subscriptions'] && is_user_logged_in()) {
            echo '<div id="topic-subscription" class="dashicons-before dashicons-email-alt">';

            $link = '';
            $text = '';
            $subscription_level = $this->get_subscription_level();

            if ($subscription_level == 3) {
                $link = $this->asgarosforum->get_link('subscriptions');
                $text = __('You are subscribed to <b>all</b> topics.', 'asgaros-forum');
            } else {
                if ($this->is_subscribed('topic', $element_id)) {
                    $link = $this->asgarosforum->get_link('topic', $element_id, array('unsubscribe_topic' => $element_id));
                    $text = __('<b>Unsubscribe</b> from this topic.', 'asgaros-forum');
                } else {
                    $link = $this->asgarosforum->get_link('topic', $element_id, array('subscribe_topic' => $element_id));
                    $text = __('<b>Subscribe</b> to this topic.', 'asgaros-forum');
                }
            }

            echo '<a href="'.$link.'">'.$text.'</a>';

            echo '</div>';
        }
    }

    // Generates an (un)subscription link based on subscription status for forums.
    public function show_forum_subscription_link($element_id) {
        // Check if this functionality is enabled and if the user is logged-in.
        if ($this->asgarosforum->options['allow_subscriptions'] && is_user_logged_in()) {
            echo '<div id="forum-subscription" class="dashicons-before dashicons-email-alt">';

            $link = '';
            $text = '';
            $subscription_level = $this->get_subscription_level();

            if ($subscription_level > 1) {
                $link = $this->asgarosforum->get_link('subscriptions');
                $text = __('You are subscribed to <b>all</b> forums.', 'asgaros-forum');
            } else {
                if ($this->is_subscribed('forum', $element_id)) {
                    $link = $this->asgarosforum->get_link('forum', $element_id, array('unsubscribe_forum' => $element_id));
                    $text = __('<b>Unsubscribe</b> from this forum.', 'asgaros-forum');
                } else {
                    $link = $this->asgarosforum->get_link('forum', $element_id, array('subscribe_forum' => $element_id));
                    $text = __('<b>Subscribe</b> to this forum.', 'asgaros-forum');
                }
            }

            echo '<a href="'.$link.'">'.$text.'</a>';

            echo '</div>';
        }
    }

    // Generates an subscription option in the editor based on subscription status.
    public function show_editor_subscription_option() {
        // Check if this functionality is enabled and if the user is logged-in.
        if ($this->asgarosforum->options['allow_subscriptions'] && is_user_logged_in()) {
            echo '<div class="editor-row">';
            echo '<span class="row-title">'.__('Subscription:', 'asgaros-forum').'</span>';

            $subscription_level = $this->get_subscription_level();

            if ($subscription_level == 3) {
                $link = $this->asgarosforum->get_link('subscriptions');
                echo '<a href="'.$link.'">'.__('You are subscribed to <b>all</b> topics.', 'asgaros-forum').'</a>';
            } else {
                echo '<label><input type="checkbox" name="subscribe_checkbox" '.checked($this->is_subscribed('topic', $this->asgarosforum->current_topic), true, false).'>'.__('<b>Subscribe</b> to this topic.', 'asgaros-forum').'</label>';
            }

            echo '</div>';
        }
    }

    // Checks if the current user has a subscription for the current topic/forum.
    public function is_subscribed($checkFor, $elementID) {
        if ($elementID) {
            $status = get_user_meta(get_current_user_id(), 'asgarosforum_subscription_'.$checkFor);

            if ($status && in_array($elementID, $status)) {
                return true;
            }
        }

        return false;
    }

    // Subscribes the current user to the current topic.
    public function subscribe_topic($topic_id) {
        // Check first if this topic exists.
        if ($this->asgarosforum->content->topic_exists($topic_id)) {
            // Only subscribe user if he is not already subscribed for this topic.
            if (!$this->is_subscribed('topic', $topic_id)) {
                add_user_meta(get_current_user_id(), 'asgarosforum_subscription_topic', $topic_id);
            }
        }
    }

    // Subscribes the current user to the current forum.
    public function subscribe_forum($forum_id) {
        // Check first if this forum exists.
        if ($this->asgarosforum->content->forum_exists($forum_id)) {
            // Only subscribe user if he is not already subscribed for this forum.
            if (!$this->is_subscribed('forum', $forum_id)) {
                add_user_meta(get_current_user_id(), 'asgarosforum_subscription_forum', $forum_id);
            }
        }
    }

    // Unsubscribes the current user from the current topic.
    public function unsubscribe_topic($topic_id) {
        // Check first if this topic exists.
        if ($this->asgarosforum->content->topic_exists($topic_id)) {
            delete_user_meta(get_current_user_id(), 'asgarosforum_subscription_topic', $topic_id);
        }
    }

    // Unsubscribes the current user from the current forum.
    public function unsubscribe_forum($forum_id) {
        // Check first if this forum exists.
        if ($this->asgarosforum->content->forum_exists($forum_id)) {
            delete_user_meta(get_current_user_id(), 'asgarosforum_subscription_forum', $forum_id);
        }
    }

    // Update the subscription-status for a topic based on the editor-checkbox.
    public function update_topic_subscription_status($topic_id) {
        if (isset($_POST['subscribe_checkbox']) && $_POST['subscribe_checkbox']) {
            $this->subscribe_topic($topic_id);
        } else {
            $this->unsubscribe_topic($topic_id);
        }
    }

    // Removes all subscriptions for a topic. This is used when a topic gets deleted.
    public function remove_all_topic_subscriptions($topic_id) {
        delete_metadata('user', 0, 'asgarosforum_subscription_topic', $topic_id, true);
    }

    // Removes all subscriptions for a forum. This is used when a forum gets deleted.
    public function remove_all_forum_subscriptions($forum_id) {
        delete_metadata('user', 0, 'asgarosforum_subscription_forum', $forum_id, true);
    }

    // TODO: This function generates tons of queries (especially the filtering). We need some improvements.
    public function notify_about_new_post($answer_text, $answer_link, $answer_author) {
        // Check if this functionality is enabled
        if ($this->asgarosforum->options['allow_subscriptions']) {
            $topic_name = $this->asgarosforum->current_topic_name;
            $author_name = $this->asgarosforum->getUsername($answer_author);
            $notification_subject = sprintf(__('New answer: %s', 'asgaros-forum'), wp_specialchars_decode(esc_html(stripslashes($topic_name)), ENT_QUOTES));
            $notification_message = sprintf(__('Hello,<br /><br />You received this message because there is a new answer in a forum-topic you have subscribed to:<br />%s<br /><br />Author:<br />%s<br /><br />Answer:<br />%s<br /><br />Link to the new answer:<br /><a href="%s">%s</a><br /><br />You can unsubscribe from this topic using the unsubscribe-link at the end of the topic as a logged-in user. Please dont answer to this mail!', 'asgaros-forum'), esc_html(stripslashes($topic_name)), $author_name, wpautop(stripslashes($answer_text)), $answer_link, $answer_link);
            $notification_message = apply_filters('asgarosforum_filter_notify_topic_subscribers_message', $notification_message, $topic_name, $answer_text, $answer_link, $author_name);

            $topic_subscribers = array();

            // Get topic subscribers.
            $topic_subscribers_query = array(
                'fields'        => array('id', 'user_email'),
                'exclude'       => array(get_current_user_id()),
                'meta_key'      => 'asgarosforum_subscription_topic',
                'meta_value'    => $this->asgarosforum->current_topic,
                'meta_compare'  => '='
            );

            $get_users_result = get_users($topic_subscribers_query);

            if (!empty($get_users_result)) {
                $topic_subscribers = array_merge($topic_subscribers, $get_users_result);
            }

            // Get global post subscribers.
            $topic_subscribers_query = array(
                'fields'        => array('id', 'user_email'),
                'exclude'       => array(get_current_user_id()),
                'meta_key'      => 'asgarosforum_subscription_global_posts',
                'meta_compare'  => 'EXISTS'
            );

            $get_users_result = get_users($topic_subscribers_query);

            if (!empty($get_users_result)) {
                $topic_subscribers = array_merge($topic_subscribers, $get_users_result);
            }

            // Remove banned users from mailing list.
            foreach ($topic_subscribers as $key => $subscriber) {
                if (AsgarosForumPermissions::isBanned($subscriber->id)) {
                    unset($topic_subscribers[$key]);
                }
            }

            // Remove non-moderators from mailing list.
            if ($this->asgarosforum->category_access_level == 'moderator') {
                foreach ($topic_subscribers as $key => $subscriber) {
                    if (!AsgarosForumPermissions::isModerator($subscriber->id)) {
                        unset($topic_subscribers[$key]);
                    }
                }
            }

            // Generate mailing list.
            foreach($topic_subscribers as $subscriber) {
                $this->add_to_mailing_list($subscriber->user_email);
            }

            // Filter mailing list based on user groups configuration.
            $this->mailing_list = AsgarosForumUserGroups::filterSubscriberMails($this->mailing_list, $this->asgarosforum->current_category);

            // Apply custom filters before sending.
            $this->mailing_list = apply_filters('asgarosforum_subscriber_mails_new_post', $this->mailing_list);

            // Send notifications.
            $this->send_notifications($this->mailing_list, $notification_subject, $notification_message);
        }
    }

    // TODO: This function generates tons of queries (especially the filtering). We need some improvements.
    public function notify_about_new_topic($topic_name, $topic_text, $topic_link, $topic_author) {
        // Check if this functionality is enabled
        if ($this->asgarosforum->options['admin_subscriptions'] || $this->asgarosforum->options['allow_subscriptions']) {
            $author_name = $this->asgarosforum->getUsername($topic_author);
            $notification_subject = sprintf(__('New topic: %s', 'asgaros-forum'), wp_specialchars_decode(esc_html(stripslashes($topic_name)), ENT_QUOTES));
            $notification_message = sprintf(__('Hello,<br /><br />You received this message because there is a new forum-topic:<br />%s<br /><br />Author:<br />%s<br /><br />Text:<br />%s<br /><br />Link to the new topic:<br /><a href="%s">%s</a>', 'asgaros-forum'), esc_html(stripslashes($topic_name)), $author_name, wpautop(stripslashes($topic_text)), $topic_link, $topic_link);
            $notification_message = apply_filters('asgarosforum_filter_notify_global_topic_subscribers_message', $notification_message, $topic_name, $topic_text, $topic_link, $author_name);

            if ($this->asgarosforum->options['allow_subscriptions']) {
                $forum_subscribers = array();

                // Get forum subscribers.
                $forum_subscribers_query = array(
                    'fields'        => array('id', 'user_email'),
                    'exclude'       => array(get_current_user_id()),
                    'meta_key'      => 'asgarosforum_subscription_forum',
                    'meta_value'    => $this->asgarosforum->current_forum,
                    'meta_compare'  => '='
                );

                $get_users_result = get_users($forum_subscribers_query);

                if (!empty($get_users_result)) {
                    $forum_subscribers = array_merge($forum_subscribers, $get_users_result);
                }

                // Get global post subscribers.
                $forum_subscribers_query = array(
                    'fields'        => array('id', 'user_email'),
                    'exclude'       => array(get_current_user_id()),
                    'meta_key'      => 'asgarosforum_subscription_global_posts',
                    'meta_compare'  => 'EXISTS'
                );

                $get_users_result = get_users($forum_subscribers_query);

                if (!empty($get_users_result)) {
                    $forum_subscribers = array_merge($forum_subscribers, $get_users_result);
                }

                // Get global topic subscribers.
                $forum_subscribers_query = array(
                    'fields'        => array('id', 'user_email'),
                    'exclude'       => array(get_current_user_id()),
                    'meta_key'      => 'asgarosforum_subscription_global_topics',
                    'meta_compare'  => 'EXISTS'
                );

                $get_users_result = get_users($forum_subscribers_query);

                if (!empty($get_users_result)) {
                    $forum_subscribers = array_merge($forum_subscribers, $get_users_result);
                }

                // Remove banned users from mailing list.
                foreach ($forum_subscribers as $key => $subscriber) {
                    if (AsgarosForumPermissions::isBanned($subscriber->id)) {
                        unset($forum_subscribers[$key]);
                    }
                }

                // Remove non-moderators from mailing list.
                if ($this->asgarosforum->category_access_level == 'moderator') {
                    foreach ($forum_subscribers as $key => $subscriber) {
                        if (!AsgarosForumPermissions::isModerator($subscriber->id)) {
                            unset($forum_subscribers[$key]);
                        }
                    }
                }

                // Generate mailing list.
                foreach($forum_subscribers as $subscriber) {
                    $this->add_to_mailing_list($subscriber->user_email);
                }

                // Filter mailing list based on user groups configuration.
                $this->mailing_list = AsgarosForumUserGroups::filterSubscriberMails($this->mailing_list, $this->asgarosforum->current_category);
            }

            // Add site-owner to mailing list when option is enabled.
            if ($this->asgarosforum->options['admin_subscriptions']) {
                $this->add_to_mailing_list(get_bloginfo('admin_email'));
            }

            // Apply custom filters before sending.
            $this->mailing_list = apply_filters('asgarosforum_subscriber_mails_new_topic', $this->mailing_list);

            // Send notifications.
            $this->send_notifications($this->mailing_list, $notification_subject, $notification_message);
        }
    }

    // Adds a mail to a mailing list. Ensures that this mail is not already included.
    public function add_to_mailing_list($mail) {
        if (!in_array($mail, $this->mailing_list)) {
            $this->mailing_list[] = $mail;
        }
    }

    public function send_notifications($mails, $subject, $message) {
        add_filter('wp_mail_content_type', array($this, 'wpdocs_set_html_mail_content_type'));

        $mail_headers = $this->get_mail_headers();

        if (is_array($mails)) {
            foreach($mails as $mail) {
                wp_mail($mail, $subject, $message, $mail_headers);
            }
        } else {
            wp_mail($mails, $subject, $message, $mail_headers);
        }

        remove_filter('wp_mail_content_type', array($this, 'wpdocs_set_html_mail_content_type'));

        // Clear mailing-list after sending notifications.
        $this->mailing_list = array();
    }

    public function wpdocs_set_html_mail_content_type() {
        return 'text/html';
    }

    public function get_mail_headers() {
        $header = array();
        $sender_name = '';
        $sender_mail = '';

        if (empty($this->asgarosforum->options['notification_sender_name'])) {
            $sender_name = get_bloginfo('name');
        } else {
            $sender_name = wp_specialchars_decode(esc_html(stripslashes($this->asgarosforum->options['notification_sender_name'])), ENT_QUOTES);
        }

        if (empty($this->asgarosforum->options['notification_sender_mail'])) {
            $sender_mail = get_bloginfo('admin_email');
        } else {
            $sender_mail = wp_specialchars_decode(esc_html(stripslashes($this->asgarosforum->options['notification_sender_mail'])), ENT_QUOTES);
        }

        $header[] = 'From: '.$sender_name.' <'.$sender_mail.'>';

        return $header;
    }

    public function show_subscription_overview_link() {
        if ($this->asgarosforum->options['allow_subscriptions'] && is_user_logged_in()) {
            echo '<a href="'.$this->asgarosforum->get_link('subscriptions').'">'.__('Subscriptions', 'asgaros-forum').'</a>';
        }
    }

    // Shows all subscriptions of a user (topics/forums).
    public function show_subscription_overview() {
        $user_id = get_current_user_id();

        // When site-owner notifications are enabled and we are the site owner, we need to print a notice.
        if ($this->asgarosforum->options['admin_subscriptions']) {
            $current_user = wp_get_current_user();

            if ($current_user->user_email == get_bloginfo('admin_email')) {
                echo '<div class="info">'.__('Based on your settings you will automatically get notified about new topics as a site-owner.', 'asgaros-forum').'</div>';
            }
        }

        // Get the subscription level.
        $subscription_level = $this->get_subscription_level();

        // Render subscription settings.
        echo '<div class="title-element title-element-dark dashicons-before dashicons-email-alt">'.__('Subscription Settings', 'asgaros-forum').'</div>';
        echo '<div class="content-element">';
            echo '<form method="post" action="'.$this->asgarosforum->get_link('subscriptions').'">';
                echo '<div id="subscription-settings">';
                    echo '<label class="subscription-option">';
                        echo '<input type="radio" name="subscription_level" value="1" '.checked($subscription_level, 1, false).'>'.__('Individual Subscriptions', 'asgaros-forum');
                        echo '<span class="subscription-option-description">';
                            _e('You get notified about activity in forums and topics you are subscribed to.', 'asgaros-forum');
                        echo '</span>';
                    echo '</label>';
                    echo '<label class="subscription-option">';
                        echo '<input type="radio" name="subscription_level" value="2" '.checked($subscription_level, 2, false).'>'.__('New Topics', 'asgaros-forum');
                        echo '<span class="subscription-option-description">';
                            _e('You get notified about all new topics.', 'asgaros-forum');
                        echo '</span>';
                    echo '</label>';
                    echo '<label class="subscription-option">';
                        echo '<input type="radio" name="subscription_level" value="3" '.checked($subscription_level, 3, false).'>'.__('New Topics & Posts', 'asgaros-forum');
                        echo '<span class="subscription-option-description">';
                            _e('You get notified about all new topics and posts.', 'asgaros-forum');
                        echo '</span>';
                    echo '</label>';
                echo '</div>';
            echo '</form>';
        echo '</div>';

        // Topic subscriptions list always available when we are not subscribed to everything.
        $title = __('Notify about new posts in:', 'asgaros-forum');
        $subscribedTopics = get_user_meta($user_id, 'asgarosforum_subscription_topic');
        $all = ($subscription_level == 3) ? true : false;

        if (!empty($subscribedTopics)) {
            $subscribedTopics = $this->asgarosforum->getSpecificTopics($subscribedTopics);
            $subscribedTopics = $this->filter_list($subscribedTopics, $user_id);
        }

        $this->render_subscriptions_list($title, $subscribedTopics, 'topic', $all);

        $title = __('Notify about new topics in:', 'asgaros-forum');
        $subscribedForums = get_user_meta($user_id, 'asgarosforum_subscription_forum');
        $all = ($subscription_level > 1) ? true : false;

        if (!empty($subscribedForums)) {
            $subscribedForums = $this->asgarosforum->getSpecificForums($subscribedForums);
            $subscribedForums = $this->filter_list($subscribedForums, $user_id);
        }

        $this->render_subscriptions_list($title, $subscribedForums, 'forum', $all);
    }

    public function set_subscription_level() {
        if (isset($_POST['subscription_level'])) {
            $user_id = get_current_user_id();

            if ($_POST['subscription_level'] == 1) {
                delete_user_meta($user_id, 'asgarosforum_subscription_global_posts');
                delete_user_meta($user_id, 'asgarosforum_subscription_global_topics');
            } else if ($_POST['subscription_level'] == 2) {
                delete_user_meta($user_id, 'asgarosforum_subscription_global_posts');
                update_user_meta($user_id, 'asgarosforum_subscription_global_topics', 1);
            } else if ($_POST['subscription_level'] == 3) {
                update_user_meta($user_id, 'asgarosforum_subscription_global_posts', 1);
                delete_user_meta($user_id, 'asgarosforum_subscription_global_topics');
            }
        }
    }

    public function get_subscription_level() {
        $user_id = get_current_user_id();

        $subscription_level = 1;
        $subscription_level_check = get_user_meta($user_id, 'asgarosforum_subscription_global_topics', true);

        if (!empty($subscription_level_check)) {
            $subscription_level = 2;
        } else {
            $subscription_level_check = get_user_meta($user_id, 'asgarosforum_subscription_global_posts', true);

            if (!empty($subscription_level_check)) {
                $subscription_level = 3;
            }
        }

        return $subscription_level;
    }

    // Renders a list of a certain subscription type for the current user.
    public function render_subscriptions_list($title, $data, $type, $all = false) {
        echo '<div class="title-element">'.$title.'</div>';
        echo '<div class="content-element">';

        if ($all) {
            if ($type == 'forum') {
                echo '<div class="notice">'. __('You get notified about <b>all</b> new topics.', 'asgaros-forum').'</div>';
            } else if ($type == 'topic') {
                echo '<div class="notice">'. __('You get notified about <b>all</b> new posts.', 'asgaros-forum').'</div>';
            }
        } else if (empty($data)) {
            echo '<div class="notice">'.__('No subscriptions yet!', 'asgaros-forum').'</div>';
        } else {
            foreach ($data as $item) {
                echo '<div class="subscription">';
                    echo '<a href="'.$this->asgarosforum->get_link($type, $item->id).'" title="'.esc_html(stripslashes($item->name)).'">'.esc_html(stripslashes($item->name)).'</a>';
                    echo '<a class="unsubscribe-link" href="'.$this->asgarosforum->get_link('subscriptions', false, array('unsubscribe_'.$type => $item->id)).'">'.__('Unsubscribe', 'asgaros-forum').'</a>';
                echo '</div>';
            }
        }

        echo '</div>';
    }

    public function filter_list($data, $user_id) {
        // Filter the list based on category.
        foreach ($data as $key => $item) {
            $canAccess = AsgarosForumUserGroups::canUserAccessForumCategory($user_id, $item->category_id);

            if (!$canAccess) {
                unset($data[$key]);
            } else {
                $canPermAccess = AsgarosForumPermissions::canUserAccessForumCategory($user_id, $item->category_id);

                if (!$canPermAccess) {
                    unset($data[$key]);
                }
            }
        }

        return $data;
    }
}

?>
