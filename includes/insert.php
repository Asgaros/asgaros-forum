<?php

if (!defined('ABSPATH')) exit;

global $wpdb;
$post_id = esc_html($_GET['id']);
$subject = (isset($_POST['subject'])) ? trim($_POST['subject']) : '';
$content = trim($_POST['message']);
$redirect = '';

// Cancel if user is banned ...
if (empty($this->error) && AsgarosForumPermissions::isBanned('current')) {
    $this->error .= '<span>'.__('You are banned!', 'asgaros-forum').'</span>';
}

if (isset($_POST['edit_post_submit'])) {
    if (!$this->element_exists($post_id, $this->table_posts)) {
        $this->error .= '<span>'.__('Sorry, this post does not exist.', 'asgaros-forum').'</span>';
    }

    if (empty($this->error) && $user_ID != $this->get_post_author($post_id) && !AsgarosForumPermissions::isModerator('current')) {
        $this->error .= '<span>'.__('You are not allowed to do this.', 'asgaros-forum').'</span>';
    }
}

if (empty($this->error) && ((isset($_POST['add_thread_submit']) && empty($subject)) || (isset($_POST['edit_post_submit']) && isset($_POST['subject']) && empty($subject)))) {
    $this->error .= '<span>'.__('You must enter a subject.', 'asgaros-forum').'</span>';
}

if (empty($content)) {
    $this->error .= '<span>'.__('You must enter a message.', 'asgaros-forum').'</span>';
}

if (empty($this->error)) {
    if (isset($_POST['add_thread_submit'])) {
        $date = $this->current_time();
        $wpdb->insert($this->table_threads, array('name' => $subject, 'parent_id' => $this->current_forum), array('%s', '%d'));
        $thread_id = $wpdb->insert_id;

        $wpdb->insert($this->table_posts, array('text' => $content, 'parent_id' => $thread_id, 'date' => $date, 'author_id' => $user_ID), array('%s', '%d', '%s', '%d'));
        $post_id = $wpdb->insert_id;

        // Only handle uploads when the option is enabled.
        if ($this->options['allow_file_uploads']) {
            $uploads = maybe_serialize(AsgarosForumUploads::uploadFiles($post_id));
            $wpdb->update($this->table_posts, array('uploads' => $uploads), array('id' => $post_id), array('%s'), array('%d'));
        }

        $redirect = html_entity_decode($this->get_link($thread_id, $this->url_thread).'#postid-'.$post_id);

        // Send notification about new topic to administrator
        AsgarosForumNotifications::notifyAdministrator($subject, $content, $redirect);

        do_action('asgarosforum_after_thread_submit', $post_id);
    } else if (isset($_POST['add_post_submit'])) {
        $date = $this->current_time();
        $wpdb->insert($this->table_posts, array('text' => $content, 'parent_id' => $this->current_thread, 'date' => $date, 'author_id' => $user_ID), array('%s', '%d', '%s', '%d'));
        $post_id = $wpdb->insert_id;

        // Only handle uploads when the option is enabled.
        if ($this->options['allow_file_uploads']) {
            $uploads = maybe_serialize(AsgarosForumUploads::uploadFiles($post_id));
            $wpdb->update($this->table_posts, array('uploads' => $uploads), array('id' => $post_id), array('%s'), array('%d'));
        }

        $redirect = html_entity_decode($this->get_postlink($this->current_thread, $post_id));

        // Send notification about new post to subscribers
        AsgarosForumNotifications::notifyTopicSubscribers($content, $redirect);

        do_action('asgarosforum_after_post_submit', $post_id);
    } else if (isset($_POST['edit_post_submit'])) {
        $uploads = maybe_serialize(AsgarosForumUploads::uploadFiles($post_id));
        $date = $this->current_time();
        $wpdb->update($this->table_posts, array('text' => $content, 'uploads' => $uploads, 'date_edit' => $date), array('id' => $post_id), array('%s', '%s', '%s'), array('%d'));

        if (isset($_POST['subject']) && !empty($subject)) {
            $wpdb->update($this->table_threads, array('name' => $subject), array('id' => $this->current_thread), array('%s'), array('%d'));
        }

        do_action('asgarosforum_after_edit_submit', $post_id);

        $redirect = html_entity_decode($this->get_postlink($this->current_thread, $post_id, $_POST['part_id']));
    }

    wp_redirect($redirect);
    exit;
}

?>
