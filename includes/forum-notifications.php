<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumNotifications {
    // Generates an (un)subscription link based on subscription status for topics.
    public static function showTopicSubscriptionLink() {
        global $asgarosforum;

        // Check if this functionality is enabled and user is logged in
        if ($asgarosforum->options['allow_subscriptions'] && is_user_logged_in()) {
            echo '<div id="topic-subscription" class="dashicons-before dashicons-email-alt">';

            if (self::isSubscribed('topic', $asgarosforum->current_topic)) {
                // User has subscription for this topic
                echo '<a href="'.$asgarosforum->getLink('topic', $asgarosforum->current_topic, array('unsubscribe_topic' => 1)).'">';
                _e('<b>Unsubscribe</b> from this topic.', 'asgaros-forum');
                echo '</a>';
            } else {
                // User has no subscription for this topic
                echo '<a href="'.$asgarosforum->getLink('topic', $asgarosforum->current_topic, array('subscribe_topic' => 1)).'">';
                _e('<b>Subscribe</b> to this topic.', 'asgaros-forum');
                echo '</a>';
            }

            echo '</div>';
        }
    }

    // Generates an (un)subscription link based on subscription status for forums.
    public static function showForumSubscriptionLink() {
        global $asgarosforum;

        // Check if this functionality is enabled and user is logged in
        if ($asgarosforum->options['allow_subscriptions'] && is_user_logged_in()) {
            echo '<div id="forum-subscription" class="dashicons-before dashicons-email-alt">';

            if (self::isSubscribed('forum', $asgarosforum->current_forum)) {
                // User has subscription for this topic
                echo '<a href="'.$asgarosforum->getLink('forum', $asgarosforum->current_forum, array('unsubscribe_forum' => 1)).'">';
                _e('<b>Unsubscribe</b> from this forum.', 'asgaros-forum');
                echo '</a>';
            } else {
                // User has no subscription for this topic
                echo '<a href="'.$asgarosforum->getLink('forum', $asgarosforum->current_forum, array('subscribe_forum' => 1)).'">';
                _e('<b>Subscribe</b> to this forum.', 'asgaros-forum');
                echo '</a>';
            }

            echo '</div>';
        }
    }

    // Generates an subscription option in the editor based on subscription status.
    public static function showEditorSubscriptionOption() {
        global $asgarosforum;

        // Check if this functionality is enabled.
        if (is_user_logged_in() && $asgarosforum->options['allow_subscriptions']) {
            echo '<div class="editor-row">';
            echo '<span class="row-title">'.__('Subscription:', 'asgaros-forum').'</span>';
            echo '<input type="checkbox" name="subscribe_checkbox" id="subscribe_checkbox" '.checked(self::isSubscribed('topic', $asgarosforum->current_topic), true, false).'>';
            echo '<label for="subscribe_checkbox">'.__('<b>Subscribe</b> to this topic.', 'asgaros-forum').'</label>';
            echo '</div>';
        }
    }

    // Checks if the current user has a subscription for the current topic/forum.
    public static function isSubscribed($checkFor, $elementID) {
        if ($elementID) {
            $status = get_user_meta(get_current_user_id(), 'asgarosforum_subscription_'.$checkFor);

            if ($status && in_array($elementID, $status)) {
                return true;
            }
        }

        return false;
    }

    // Subscribes the current user to the current topic.
    public static function subscribeTopic() {
        global $asgarosforum;
        $topic_id = $asgarosforum->current_topic;

        // Only subscribe user if he is not already subscribed for this topic.
        if (!self::isSubscribed('topic', $topic_id)) {
            add_user_meta(get_current_user_id(), 'asgarosforum_subscription_topic', $topic_id);
        }
    }

    // Subscribes the current user to the current forum.
    public static function subscribeForum() {
        global $asgarosforum;
        $forumID = $asgarosforum->current_forum;

        // Only subscribe user if he is not already subscribed for this forum.
        if (!self::isSubscribed('forum', $forumID)) {
            add_user_meta(get_current_user_id(), 'asgarosforum_subscription_forum', $forumID);
        }
    }

    // Unsubscribes the current user from the current topic.
    public static function unsubscribeTopic() {
        global $asgarosforum;
        $topic_id = $asgarosforum->current_topic;

        delete_user_meta(get_current_user_id(), 'asgarosforum_subscription_topic', $topic_id);
    }

    // Unsubscribes the current user from the current forum.
    public static function unsubscribeForum() {
        global $asgarosforum;
        $forumID = $asgarosforum->current_forum;

        delete_user_meta(get_current_user_id(), 'asgarosforum_subscription_forum', $forumID);
    }

    // Update the subscription-status based on the editor checkbox
    public static function updateSubscriptionStatus() {
        if (isset($_POST['subscribe_checkbox']) && $_POST['subscribe_checkbox']) {
            self::subscribeTopic();
        } else {
            self::unsubscribeTopic();
        }
    }

    // Removes all subscriptions for a topic. This is used when a topic gets deleted.
    public static function removeTopicSubscriptions($topic_id) {
        delete_metadata('user', 0, 'asgarosforum_subscription_topic', $topic_id, true);
    }

    // Removes all subscriptions for a forum. This is used when a forum gets deleted.
    public static function removeForumSubscriptions($forumID) {
        delete_metadata('user', 0, 'asgarosforum_subscription_forum', $forumID, true);
    }

    // Notify all users which are subscribed to a topic.
    public static function notifyTopicSubscribers($answer_text, $answer_link, $answer_author) {
        global $asgarosforum;

        // Check if this functionality is enabled
        if ($asgarosforum->options['allow_subscriptions']) {
            $subscriberMails = array();
            $topic_name = $asgarosforum->current_topic_name;
            $author_name = $asgarosforum->getUsername($answer_author);
            $notification_subject = sprintf(__('New answer: %s', 'asgaros-forum'), wp_specialchars_decode(esc_html(stripslashes($topic_name)), ENT_QUOTES));
            $notification_message = sprintf(__('Hello,<br /><br />You received this message because there is a new answer in a forum-topic you have subscribed to:<br />%s<br /><br />Author:<br />%s<br /><br />Answer:<br />%s<br /><br />Link to the new answer:<br /><a href="%s">%s</a><br /><br />You can unsubscribe from this topic using the unsubscribe-link at the end of the topic as a logged-in user. Please dont answer to this mail!', 'asgaros-forum'), esc_html(stripslashes($topic_name)), $author_name, wpautop(stripslashes($answer_text)), $answer_link, $answer_link);
            $notification_message = apply_filters('asgarosforum_filter_notify_topic_subscribers_message', $notification_message, $topic_name, $answer_text, $answer_link, $author_name);

            $topic_subscribers_meta_query = array(
                'relation'  => 'AND',
                array(
                    'key'       => 'asgarosforum_subscription_topic',
                    'value'     => $asgarosforum->current_topic,
                    'compare'   => '='
                ),
                array(
                    'key'       => 'asgarosforum_banned',
                    'compare'   => 'NOT EXISTS'
                )
            );

            // Only get moderators when this is a restricted category.
            if ($asgarosforum->category_access_level == 'moderator') {
                $topic_subscribers_meta_query[] = array(
                    'key'       => 'asgarosforum_moderator',
                    'compare'   => 'EXISTS'
                );
            }

            $topic_subscribers_meta_query = apply_filters('asgarosforum_filter_subscribers_query_new_post', $topic_subscribers_meta_query);

            // Get subscribed users
            $topic_subscribers = get_users(
                array(
                    'fields'        => array('user_email'),
                    'exclude'       => array(get_current_user_id()),
                    'meta_query'    => $topic_subscribers_meta_query
                )
            );

            foreach($topic_subscribers as $subscriber) {
                if (!in_array($subscriber->user_email, $subscriberMails)) {
                    $subscriberMails[] = $subscriber->user_email;
                }
            }

            $subscriberMails = AsgarosForumUserGroups::filterSubscriberMails($subscriberMails);
            $subscriberMails = apply_filters('asgarosforum_subscriber_mails_new_post', $subscriberMails);

            add_filter('wp_mail_content_type', array('AsgarosForumNotifications', 'wpdocs_set_html_mail_content_type'));

            $mailHeaders = self::getMailHeaders();

            foreach($subscriberMails as $subscriberMail) {
                wp_mail($subscriberMail, $notification_subject, $notification_message, $mailHeaders);
            }

            remove_filter('wp_mail_content_type', array('AsgarosForumNotifications', 'wpdocs_set_html_mail_content_type'));
        }
    }

    public static function notifyGlobalTopicSubscribers($topic_name, $topic_text, $topic_link, $topic_author) {
        global $asgarosforum;

        // Check if this functionality is enabled
        if ($asgarosforum->options['admin_subscriptions'] || $asgarosforum->options['allow_subscriptions']) {
            $subscriberMails = array();
            $author_name = $asgarosforum->getUsername($topic_author);
            $notification_subject = sprintf(__('New topic: %s', 'asgaros-forum'), wp_specialchars_decode(esc_html(stripslashes($topic_name)), ENT_QUOTES));
            $notification_message = sprintf(__('Hello,<br /><br />You received this message because there is a new forum-topic:<br />%s<br /><br />Author:<br />%s<br /><br />Text:<br />%s<br /><br />Link to the new topic:<br /><a href="%s">%s</a>', 'asgaros-forum'), esc_html(stripslashes($topic_name)), $author_name, wpautop(stripslashes($topic_text)), $topic_link, $topic_link);
            $notification_message = apply_filters('asgarosforum_filter_notify_global_topic_subscribers_message', $notification_message, $topic_name, $topic_text, $topic_link, $author_name);

            if ($asgarosforum->options['allow_subscriptions']) {
                // Get global subscribers.
                $global_topic_subscribers_meta_query = array(
                    'relation'  => 'AND',
                    array(
                        'key'       => 'asgarosforum_subscription_global_topics',
                        'compare'   => 'EXISTS'
                    ),
                    array(
                        'key'       => 'asgarosforum_banned',
                        'compare'   => 'NOT EXISTS'
                    )
                );

                // Only get moderators when this is a restricted category.
                if ($asgarosforum->category_access_level == 'moderator') {
                    $global_topic_subscribers_meta_query[] = array(
                        'key'       => 'asgarosforum_moderator',
                        'compare'   => 'EXISTS'
                    );
                }

                $global_topic_subscribers_meta_query = apply_filters('asgarosforum_filter_subscribers_query_new_topic', $global_topic_subscribers_meta_query);

                // Get subscribed users
                $global_topic_subscribers = get_users(
                    array(
                        'fields'        => array('user_email'),
                        'exclude'       => array(get_current_user_id()),
                        'meta_query'    => $global_topic_subscribers_meta_query
                    )
                );

                // TODO: array can be optimized so that only the value gets returned. Look in query generation.
                foreach($global_topic_subscribers as $subscriber) {
                    if (!in_array($subscriber->user_email, $subscriberMails)) {
                        $subscriberMails[] = $subscriber->user_email;
                    }
                }

                // Get forum subscribers.
                $forum_subscribers_meta_query = array(
                    'relation'  => 'AND',
                    array(
                        'key'       => 'asgarosforum_subscription_forum',
                        'value'     => $asgarosforum->current_forum,
                        'compare'   => 'EXISTS'
                    ),
                    array(
                        'key'       => 'asgarosforum_banned',
                        'compare'   => 'NOT EXISTS'
                    )
                );

                // Only get moderators when this is a restricted category.
                if ($asgarosforum->category_access_level == 'moderator') {
                    $forum_subscribers_meta_query[] = array(
                        'key'       => 'asgarosforum_moderator',
                        'compare'   => 'EXISTS'
                    );
                }

                $forum_subscribers_meta_query = apply_filters('asgarosforum_filter_subscribers_query_new_topic', $forum_subscribers_meta_query);

                // Get subscribed users
                $forum_subscribers = get_users(
                    array(
                        'fields'        => array('user_email'),
                        'exclude'       => array(get_current_user_id()),
                        'meta_query'    => $forum_subscribers_meta_query
                    )
                );

                foreach($forum_subscribers as $subscriber) {
                    if (!in_array($subscriber->user_email, $subscriberMails)) {
                        $subscriberMails[] = $subscriber->user_email;
                    }
                }
            }

            $subscriberMails = AsgarosForumUserGroups::filterSubscriberMails($subscriberMails);
            $subscriberMails = apply_filters('asgarosforum_subscriber_mails_new_topic', $subscriberMails);

            if ($asgarosforum->options['admin_subscriptions']) {
                if (!in_array(get_bloginfo('admin_email'), $subscriberMails)) {
                    $subscriberMails[] = get_bloginfo('admin_email');
                }
            }

            add_filter('wp_mail_content_type', array('AsgarosForumNotifications', 'wpdocs_set_html_mail_content_type'));

            $mailHeaders = self::getMailHeaders();

            foreach($subscriberMails as $subscriberMail) {
                wp_mail($subscriberMail, $notification_subject, $notification_message, $mailHeaders);
            }

            remove_filter('wp_mail_content_type', array('AsgarosForumNotifications', 'wpdocs_set_html_mail_content_type'));
        }
    }

    public static function wpdocs_set_html_mail_content_type() {
        return 'text/html';
    }

    public static function getMailHeaders() {
        global $asgarosforum;

        $header = array();
        $sender_name = '';
        $sender_mail = '';

        if (empty($asgarosforum->options['notification_sender_name'])) {
            $sender_name = get_bloginfo('name');
        } else {
            $sender_name = wp_specialchars_decode(esc_html(stripslashes($asgarosforum->options['notification_sender_name'])), ENT_QUOTES);
        }

        if (empty($asgarosforum->options['notification_sender_mail'])) {
            $sender_mail = get_bloginfo('admin_email');
        } else {
            $sender_mail = wp_specialchars_decode(esc_html(stripslashes($asgarosforum->options['notification_sender_mail'])), ENT_QUOTES);
        }

        $header[] = 'From: '.$sender_name.' <'.$sender_mail.'>';

        return $header;
    }

    public static function showSubscriptionOverviewLink() {
        global $asgarosforum;

        if ($asgarosforum->options['allow_subscriptions'] && is_user_logged_in()) {
            echo '<div id="subscription-overview-link">';
            echo '<a href="'.$asgarosforum->getLink('subscriptions').'"><span class="dashicons-before dashicons-email-alt"></span></a>';
            echo '</div>';
        }
    }

    // Shows all subscriptions of a user (topics/forums).
    public static function showSubscriptions() {
        global $asgarosforum;
        $subscribedTopics = get_user_meta(get_current_user_id(), 'asgarosforum_subscription_topic');
        $subscribedForums = get_user_meta(get_current_user_id(), 'asgarosforum_subscription_forum');

        $title = __('Topics', 'asgaros-forum');

        if (!empty($subscribedTopics)) {
            $subscribedTopics = $asgarosforum->getSpecificTopics($subscribedTopics);
        }

        self::renderSubscriptionsList($title, $subscribedTopics, 'topic');

        $title = __('Forums', 'asgaros-forum');

        if (!empty($subscribedForums)) {
            $subscribedForums = $asgarosforum->getSpecificForums($subscribedForums);
        }

        self::renderSubscriptionsList($title, $subscribedForums, 'forum');
    }

    // Renders a list of a certain subscription type for the current user.
    public static function renderSubscriptionsList($title, $data, $type) {
        global $asgarosforum;

        echo '<div class="title-element">'.$title.'</div>';
        echo '<div class="content-element">';

        if (empty($data)) {
            echo '<div class="notice">'.__('No subscriptions yet!', 'asgaros-forum').'</div>';
        } else {
            foreach ($data as $item) {
                echo '<div class="subscription">';
                echo '<a href="'.$asgarosforum->getLink($type, $item->id).'" title="'.esc_html(stripslashes($item->name)).'">'.esc_html(stripslashes($item->name)).'</a>';
                echo '</div>';
            }
        }

        echo '</div>';
    }
}

?>
