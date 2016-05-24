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

    // Checks if the current user has a subscription for the current topic.
    public static function isSubscribed($topic_id) {
        $user_id = get_current_user_id();
        $status = get_user_meta($user_id, 'asgarosforum_subscription_topic');

        if (in_array($topic_id, $status)) {
            return true;
        } else {
            return false;
        }
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
            $notification_message = sprintf(__("Hello,\r\n\r\nyou got this mail because there is a new answer in a forum-topic you have subscribed to:\r\n%s\r\n\r\nAnswer:\r\n%s\r\n\r\nLink to the new answer:\r\n%s\r\n\r\nYou can unsubscribe from this topic using the unsubscribe-link at the end of the topic as a logged-in user. Please dont answer to this mail!", 'asgaros-forum'), $thread_name, $answer_text, $answer_link);

            // Get subscribed users
            $topic_subscribers = get_users(
                array(
                    'meta_key'      => 'asgarosforum_subscription_topic',
                    'meta_value'    => $asgarosforum->current_thread,
                    'fields'        => array('user_email'),
                    'exclude'       => array(get_current_user_id())
                )
            );

            foreach($topic_subscribers as $subscriber) {
                wp_mail($subscriber->user_email, $notification_subject, $notification_message);
            }
        }
    }

    public static function notifyAdministrator($topic_name, $topic_text, $topic_link) {
        global $asgarosforum;

        // Check if this functionality is enabled
        if ($asgarosforum->options['admin_subscriptions']) {
            $notification_subject = sprintf(__('[%s] New topic: %s', 'asgaros-forum'), get_bloginfo('name'), $topic_name);
            $notification_message = sprintf(__("Hello,\r\n\r\nyou got this mail because there is a new forum-topic:\r\n%s\r\n\r\nText:\r\n%s\r\n\r\nLink to the new topic:\r\n%s", 'asgaros-forum'), $topic_name, $topic_text, $topic_link);

            wp_mail(get_bloginfo('admin_email'), $notification_subject, $notification_message);
        }
    }
}

?>
