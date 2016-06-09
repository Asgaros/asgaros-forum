<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumInsert {
    private static $action = false;

    // Data for insertion is stored here.
    private static $dataSubject;
    private static $dataContent;

    public static function determineAction() {
        if ($_POST['submit_action'] === 'add_thread' || $_POST['submit_action'] === 'add_post' || $_POST['submit_action'] === 'edit_post') {
            self::$action = $_POST['submit_action'];
        }
    }

    public static function getAction() {
        return self::$action;
    }

    public static function setData() {
        if (isset($_POST['subject'])) {
            self::$dataSubject = trim($_POST['subject']);
        }

        if (isset($_POST['message'])) {
            self::$dataContent = trim($_POST['message']);
        }
    }

    public static function validateExecution() {
        global $user_ID;
        global $asgarosforum;

        // Cancel if there is already an error.
        if (!empty($asgarosforum->error)) {
            return false;
        }

        // Cancel if the current user is banned.
        if (AsgarosForumPermissions::isBanned('current')) {
            $asgarosforum->error = __('You are banned!', 'asgaros-forum');
            return false;
        }

        // Cancel if the current user is not allowed to edit that post.
        if (self::getAction() === 'edit_post' && !AsgarosForumPermissions::isModerator('current') && $user_ID != $asgarosforum->get_post_author($asgarosforum->current_post)) {
            $asgarosforum->error = __('You are not allowed to do this.', 'asgaros-forum');
            return false;
        }

        // Cancel if subject is empty.
        if ((self::getAction() === 'add_thread' || (self::getAction() === 'edit_post' && $asgarosforum->is_first_post($asgarosforum->current_post))) && empty(self::$dataSubject)) {
            $asgarosforum->info = __('You must enter a subject.', 'asgaros-forum');
            return false;
        }

        // Cancel if content is empty.
        if (empty(self::$dataContent)) {
            $asgarosforum->info = __('You must enter a message.', 'asgaros-forum');
            return false;
        }

        return true;
    }

    public static function insertData() {
        global $user_ID;
        global $wpdb;
        global $asgarosforum;

        $redirect = '';

        $date = $asgarosforum->current_time();

        if (self::getAction() === 'add_thread') {
            $wpdb->insert($asgarosforum->table_threads, array('name' => self::$dataSubject, 'parent_id' => $asgarosforum->current_forum), array('%s', '%d'));
            $asgarosforum->current_thread = $wpdb->insert_id;

            $wpdb->insert($asgarosforum->table_posts, array('text' => self::$dataContent, 'parent_id' => $asgarosforum->current_thread, 'date' => $date, 'author_id' => $user_ID), array('%s', '%d', '%s', '%d'));
            $asgarosforum->current_post = $wpdb->insert_id;

            // Only handle uploads when the option is enabled.
            if ($asgarosforum->options['allow_file_uploads']) {
                $uploads = maybe_serialize(AsgarosForumUploads::uploadFiles($asgarosforum->current_post));
                $wpdb->update($asgarosforum->table_posts, array('uploads' => $uploads), array('id' => $asgarosforum->current_post), array('%s'), array('%d'));
            }

            $redirect = html_entity_decode($asgarosforum->get_link($asgarosforum->current_thread, $asgarosforum->url_thread).'#postid-'.$asgarosforum->current_post);

            // Send notification about new topic to administrator
            AsgarosForumNotifications::notifyAdministrator(self::$dataSubject, self::$dataContent, $redirect);
        } else if (self::getAction() === 'add_post') {
            $wpdb->insert($asgarosforum->table_posts, array('text' => self::$dataContent, 'parent_id' => $asgarosforum->current_thread, 'date' => $date, 'author_id' => $user_ID), array('%s', '%d', '%s', '%d'));
            $asgarosforum->current_post = $wpdb->insert_id;

            // Only handle uploads when the option is enabled.
            if ($asgarosforum->options['allow_file_uploads']) {
                $uploads = maybe_serialize(AsgarosForumUploads::uploadFiles($asgarosforum->current_post));
                $wpdb->update($asgarosforum->table_posts, array('uploads' => $uploads), array('id' => $asgarosforum->current_post), array('%s'), array('%d'));
            }

            $redirect = html_entity_decode($asgarosforum->get_postlink($asgarosforum->current_thread, $asgarosforum->current_post));

            // Send notification about new post to subscribers
            AsgarosForumNotifications::notifyTopicSubscribers(self::$dataContent, $redirect);
        } else if (self::getAction() === 'edit_post') {
            $uploads = maybe_serialize(AsgarosForumUploads::uploadFiles($asgarosforum->current_post));
            $wpdb->update($asgarosforum->table_posts, array('text' => self::$dataContent, 'uploads' => $uploads, 'date_edit' => $date), array('id' => $asgarosforum->current_post), array('%s', '%s', '%s'), array('%d'));

            if ($asgarosforum->is_first_post($asgarosforum->current_post) && !empty(self::$dataSubject)) {
                $wpdb->update($asgarosforum->table_threads, array('name' => self::$dataSubject), array('id' => $asgarosforum->current_thread), array('%s'), array('%d'));
            }

            $redirect = html_entity_decode($asgarosforum->get_postlink($asgarosforum->current_thread, $asgarosforum->current_post, $_POST['part_id']));
        }

        AsgarosForumNotifications::updateSubscriptionStatus();

        do_action('asgarosforum_after_'.self::getAction().'_submit', $asgarosforum->current_post);

        wp_redirect($redirect);
        exit;
    }
}

?>
