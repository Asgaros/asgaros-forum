<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumNotifications {
    private static $instance = null;

    // AsgarosForumNotifications instance creator
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self;
		} else {
			return self::$instance;
		}
	}

    // AsgarosForumNotifications constructor
	private function __construct() {}

    // Generates an (un)subscription link based on subscription status.
    public static function showSubscriptionLink() {
        global $asgarosforum;

        // Check if this functionality is enabled and user is logged in
        if ($asgarosforum->options['allow_subscriptions'] && is_user_logged_in()) {
            echo '<div id="topic-subscription">';

            if (self::isSubscribed($asgarosforum->current_thread)) {
                // User has subscription for this topic
                echo '<a href="'.$asgarosforum->get_link($asgarosforum->current_thread, $asgarosforum->url_thread).'&amp;unsubscribe_topic">';
                _e('<b>Unsubscribe</b> from this topic.', 'asgaros-forum');
                echo '</a>';
            } else {
                // User has no subscription for this topic
                echo '<a href="'.$asgarosforum->get_link($asgarosforum->current_thread, $asgarosforum->url_thread).'&amp;subscribe_topic">';
                _e('<b>Subscribe</b> to this topic.', 'asgaros-forum');
                echo '</a>';
            }

            echo '</div>';
        }
    }

    // Generates an subscription option in the editor based on subscription status.
    public static function showEditorSubscriptionOption() {
        global $asgarosforum;

        // Check if this functionality is enabled.
        if ($asgarosforum->options['allow_subscriptions']) {
            echo '<div class="editor-row">';
            echo '<span class="row-title">'.__('Subscription:', 'asgaros-forum').'</span>';
            echo '<input type="checkbox" name="subscribe_checkbox" id="subscribe_checkbox" '.checked(self::isSubscribed($asgarosforum->current_thread), true, false).'>';
            echo '<label for="subscribe_checkbox">'.__('<b>Subscribe</b> to this topic.', 'asgaros-forum').'</label>';
            echo '</div>';
        }
    }

    // Checks if the current user has a subscription for the current topic.
    public static function isSubscribed($topic_id) {
        if ($topic_id) {
            $user_id = get_current_user_id();
            $status = get_user_meta($user_id, 'asgarosforum_subscription_topic');

            if (in_array($topic_id, $status)) {
                return true;
            }
        }

        return false;
    }

    // Subscribes the current user to the current topic.
    public static function subscribeTopic() {
        global $asgarosforum;
        $user_id = get_current_user_id();
        $topic_id = $asgarosforum->current_thread;

        // Only subscribe user if he is not already subscribed for this topic.
        if (!self::isSubscribed($topic_id)) {
            add_user_meta($user_id, 'asgarosforum_subscription_topic', $topic_id);
        }
    }

    // Unsubscribes the current user from the current topic.
    public static function unsubscribeTopic() {
        global $asgarosforum;
        $user_id = get_current_user_id();
        $topic_id = $asgarosforum->current_thread;

        delete_user_meta($user_id, 'asgarosforum_subscription_topic', $topic_id);
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

    // Notify all users which are subscribed to a topic.
    public static function notifyTopicSubscribers($answer_text, $answer_link) {
        global $asgarosforum;

        // Check if this functionality is enabled
        if ($asgarosforum->options['allow_subscriptions']) {
            $thread_name = $asgarosforum->get_name($asgarosforum->current_thread, $asgarosforum->table_threads);

            $notification_subject = sprintf(__('[%s] New answer: %s', 'asgaros-forum'), get_bloginfo('name'), $thread_name);
            $notification_message = sprintf(__('Hello,<br /><br />you got this mail because there is a new answer in a forum-topic you have subscribed to:<br />%s<br /><br />Answer:<br />%s<br /><br />Link to the new answer:<br />%s<br /><br />You can unsubscribe from this topic using the unsubscribe-link at the end of the topic as a logged-in user. Please dont answer to this mail!', 'asgaros-forum'), $thread_name, wpautop($answer_text), $answer_link);
            $notification_message = apply_filters('asgarosforum_filter_notify_topic_subscribers_message', $notification_message, $thread_name, $answer_text, $answer_link);

            // Get subscribed users
            $topic_subscribers = get_users(
                array(
                    'meta_key'      => 'asgarosforum_subscription_topic',
                    'meta_value'    => $asgarosforum->current_thread,
                    'fields'        => array('user_email'),
                    'exclude'       => array(get_current_user_id())
                )
            );

            add_filter('wp_mail_content_type', array('AsgarosForumNotifications', 'wpdocs_set_html_mail_content_type'));

            foreach($topic_subscribers as $subscriber) {
                wp_mail($subscriber->user_email, $notification_subject, $notification_message);
            }

            remove_filter('wp_mail_content_type', array('AsgarosForumNotifications', 'wpdocs_set_html_mail_content_type'));
        }
    }

    public static function notifyAdministrator($topic_name, $topic_text, $topic_link) {
        global $asgarosforum;

        // Check if this functionality is enabled
        if ($asgarosforum->options['admin_subscriptions']) {
            $notification_subject = sprintf(__('[%s] New topic: %s', 'asgaros-forum'), get_bloginfo('name'), $topic_name);
            $notification_message = sprintf(__('Hello,<br /><br />you got this mail because there is a new forum-topic:<br />%s<br /><br />Text:<br />%s<br /><br />Link to the new topic:<br />%s', 'asgaros-forum'), $topic_name, wpautop($topic_text), $topic_link);
            $notification_message = apply_filters('asgarosforum_filter_notify_administrator_message', $notification_message, $topic_name, $topic_text, $topic_link);

            add_filter('wp_mail_content_type', array('AsgarosForumNotifications', 'wpdocs_set_html_mail_content_type'));

            wp_mail(get_bloginfo('admin_email'), $notification_subject, $notification_message);

            remove_filter('wp_mail_content_type', array('AsgarosForumNotifications', 'wpdocs_set_html_mail_content_type'));
        }
    }

    public static function wpdocs_set_html_mail_content_type() {
        return 'text/html';
    }
}

?>
