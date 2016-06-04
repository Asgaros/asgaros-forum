<?php

if (!defined('ABSPATH')) exit;

global $wpdb;
$post_id = esc_html($_GET['id']);
$subject = (isset($_POST['subject'])) ? trim($_POST['subject']) : '';
$content = trim($_POST['message']);
$redirect = '';

AsgarosForumInsert::validateData();

if (AsgarosForumInsert::getAction() === 'edit_post') {
    if (empty($this->error) && $user_ID != $this->get_post_author($post_id) && !AsgarosForumPermissions::isModerator('current')) {
        $this->error .= '<span>'.__('You are not allowed to do this.', 'asgaros-forum').'</span>';
    }
}

if (empty($this->error) && ((AsgarosForumInsert::getAction() === 'add_thread' && empty($subject)) || (AsgarosForumInsert::getAction() === 'edit_post' && isset($_POST['subject']) && empty($subject)))) {
    $this->error .= '<span>'.__('You must enter a subject.', 'asgaros-forum').'</span>';
}

if (empty($content)) {
    $this->error .= '<span>'.__('You must enter a message.', 'asgaros-forum').'</span>';
}

if (empty($this->error)) {
    $date = $this->current_time();

    if (AsgarosForumInsert::getAction() === 'add_thread') {
        $wpdb->insert($this->table_threads, array('name' => $subject, 'parent_id' => $this->current_forum), array('%s', '%d'));
        $this->current_thread = $wpdb->insert_id;

        $wpdb->insert($this->table_posts, array('text' => $content, 'parent_id' => $this->current_thread, 'date' => $date, 'author_id' => $user_ID), array('%s', '%d', '%s', '%d'));
        $post_id = $wpdb->insert_id;

        // Only handle uploads when the option is enabled.
        if ($this->options['allow_file_uploads']) {
            $uploads = maybe_serialize(AsgarosForumUploads::uploadFiles($post_id));
            $wpdb->update($this->table_posts, array('uploads' => $uploads), array('id' => $post_id), array('%s'), array('%d'));
        }

        $redirect = html_entity_decode($this->get_link($this->current_thread, $this->url_thread).'#postid-'.$post_id);

        // Send notification about new topic to administrator
        AsgarosForumNotifications::notifyAdministrator($subject, $content, $redirect);
    } else if (AsgarosForumInsert::getAction() === 'add_post') {
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
    } else if (AsgarosForumInsert::getAction() === 'edit_post') {
        $uploads = maybe_serialize(AsgarosForumUploads::uploadFiles($post_id));
        $wpdb->update($this->table_posts, array('text' => $content, 'uploads' => $uploads, 'date_edit' => $date), array('id' => $post_id), array('%s', '%s', '%s'), array('%d'));

        if ($this->is_first_post($post_id) && isset($_POST['subject']) && !empty($subject)) {
            $wpdb->update($this->table_threads, array('name' => $subject), array('id' => $this->current_thread), array('%s'), array('%d'));
        }

        $redirect = html_entity_decode($this->get_postlink($this->current_thread, $post_id, $_POST['part_id']));
    }

    AsgarosForumNotifications::updateSubscriptionStatus();

    do_action('asgarosforum_after_'.AsgarosForumInsert::getAction().'_submit', $post_id);

    wp_redirect($redirect);
    exit;
}

?>
