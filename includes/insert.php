<?php
global $wpdb;
$post_id = $_GET['id'];
$subject = (isset($_POST['subject'])) ? $_POST['subject'] : '';
$content = $_POST['message'];
$redirect = '';
$msg = '<h2>'.__('An error occured!', 'asgarosforum').'</h2>';
$error = false;

if (isset($_POST['edit_post_submit']) && !$this->element_exists($post_id, $this->table_posts)) {
    $msg .= '<div id="error"><p>'.__('Sorry, this post does not exist.', 'asgarosforum').'</p></div>';
    $error = true;
}

if (isset($_POST['edit_post_submit']) && $user_ID != $this->get_post_author($post_id) && !$this->is_moderator()) {
    $msg .= '<div id="error"><p>'.__('You are not allowed to do this.', 'asgarosforum').'</p></div>';
    $error = true;
}

if ((isset($_POST['add_thread_submit']) && empty($subject)) || (isset($_POST['edit_post_submit']) && isset($_POST['subject']) && empty($_POST['subject']))) {
    $msg .= '<div id="error"><p>'.__('You must enter a subject.', 'asgarosforum').'</p></div>';
    $error = true;
}

if (empty($content)) {
    $msg .= '<div id="error"><p>'.__('You must enter a message.', 'asgarosforum').'</p></div>';
    $error = true;
}

if ($error) {
    wp_die($msg);
}

if (isset($_POST['add_thread_submit'])) {
    $date = $this->current_time();
    $wpdb->query($wpdb->prepare("INSERT INTO {$this->table_threads} (name, parent_id) VALUES (%s, %d);", $subject, $this->current_forum));
    $thread_id = $wpdb->insert_id;

    $wpdb->query($wpdb->prepare("INSERT INTO {$this->table_posts} (text, parent_id, date, author_id) VALUES (%s, %d, %s, %d);", $content, $thread_id, $date, $user_ID));
    $post_id = $wpdb->insert_id;

    $uploads = maybe_serialize($this->attach_files($post_id));
    $wpdb->query($wpdb->prepare("UPDATE {$this->table_posts} SET uploads = %s WHERE id = %d;", $uploads, $post_id));

    $redirect = html_entity_decode($this->get_link($thread_id, $this->url_thread)."#postid-".$post_id);
} else if (isset($_POST['add_post_submit'])) {
    $date = $this->current_time();
    $wpdb->query($wpdb->prepare("INSERT INTO {$this->table_posts} (text, parent_id, date, author_id) VALUES (%s, %d, %s, %d);", $content, $this->current_thread, $date, $user_ID));
    $post_id = $wpdb->insert_id;

    $uploads = maybe_serialize($this->attach_files($post_id));
    $wpdb->query($wpdb->prepare("UPDATE {$this->table_posts} SET uploads = %s WHERE id = %d;", $uploads, $post_id));

    $redirect = html_entity_decode($this->get_postlink($this->current_thread, $post_id));
} else if (isset($_POST['edit_post_submit'])) {
    $uploads = maybe_serialize($this->attach_files($post_id));
    $wpdb->query($wpdb->prepare("UPDATE {$this->table_posts} SET text = %s, uploads = %s WHERE id = %d;", $content, $uploads, $post_id));

    if (isset($_POST['subject']) && !empty($_POST['subject'])) {
        $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET name = %s WHERE id = %d;", $_POST['subject'], $this->current_thread));
    }

    $redirect = html_entity_decode($this->get_postlink($this->current_thread, $post_id, $_POST['part_id']));
}

wp_redirect($redirect);
exit;

?>
