<?php

if (!defined('ABSPATH')) exit;

global $wpdb;
$post_id = $_GET['id'];
$subject = (isset($_POST['subject'])) ? trim($_POST['subject']) : '';
$content = trim($_POST['message']);
$redirect = '';

if (isset($_POST['edit_post_submit'])) {
    if (!$this->element_exists($post_id, $this->table_posts)) {
        $this->error .= '<span>'.__('Sorry, this post does not exist.', 'asgaros-forum').'</span>';
    }

    if (empty($this->error) && $user_ID != $this->get_post_author($post_id) && !$this->is_moderator()) {
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
        $wpdb->query($wpdb->prepare("INSERT INTO {$this->table_threads} (name, parent_id) VALUES (%s, %d);", $subject, $this->current_forum));
        $thread_id = $wpdb->insert_id;

        $wpdb->query($wpdb->prepare("INSERT INTO {$this->table_posts} (text, parent_id, date, author_id) VALUES (%s, %d, %s, %d);", $content, $thread_id, $date, $user_ID));
        $post_id = $wpdb->insert_id;

        $uploads = maybe_serialize($this->attach_files($post_id));
        $wpdb->query($wpdb->prepare("UPDATE {$this->table_posts} SET uploads = %s WHERE id = %d;", $uploads, $post_id));

        $redirect = html_entity_decode($this->get_link($thread_id, $this->url_thread).'#postid-'.$post_id);
    } else if (isset($_POST['add_post_submit'])) {
        $date = $this->current_time();
        $wpdb->query($wpdb->prepare("INSERT INTO {$this->table_posts} (text, parent_id, date, author_id) VALUES (%s, %d, %s, %d);", $content, $this->current_thread, $date, $user_ID));
        $post_id = $wpdb->insert_id;

        // TODO: Dont add upload stuff when upload is deactivated
        $uploads = maybe_serialize($this->attach_files($post_id));
        $wpdb->query($wpdb->prepare("UPDATE {$this->table_posts} SET uploads = %s WHERE id = %d;", $uploads, $post_id));

        $redirect = html_entity_decode($this->get_postlink($this->current_thread, $post_id));
    } else if (isset($_POST['edit_post_submit'])) {
        $uploads = maybe_serialize($this->attach_files($post_id));
        $date = $this->current_time();
        $wpdb->query($wpdb->prepare("UPDATE {$this->table_posts} SET text = %s, uploads = %s, date_edit = %s WHERE id = %d;", $content, $uploads, $date, $post_id));

        if (isset($_POST['subject']) && !empty($_POST['subject'])) {
            $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET name = %s WHERE id = %d;", $_POST['subject'], $this->current_thread));
        }

        $redirect = html_entity_decode($this->get_postlink($this->current_thread, $post_id, $_POST['part_id']));
    }

    wp_redirect($redirect);
    exit;
}

?>
