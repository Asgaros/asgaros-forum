<?php
global $wpdb;
$error = false;

if (isset($_POST['add_thread_submit'])) { // Adding a new thread
    $subject = $_POST['subject'];
    $content = $_POST['message'];
    $thread_id = '';
    $post_id = '';
    $msg = '';

    if (empty($subject) || empty($content)) {
        $msg .= '<h2>'.__("An error occured", "asgarosforum").'</h2>';
        $msg .= '<div id="error"><p>'.__("You must enter a subject and a message.", "asgarosforum").'</p></div>';
        $error = true;
    } else {
        $wpdb->query($wpdb->prepare("INSERT INTO {$this->table_threads} (name, parent_id) VALUES (%s, %d);", $subject, $this->current_forum));
        $thread_id = $wpdb->insert_id;
        $date = $this->current_time();

        $wpdb->query($wpdb->prepare("INSERT INTO {$this->table_posts} (parent_id, date, author_id) VALUES (%d, %s, %d);", $thread_id, $date, $user_ID));
        $post_id = $wpdb->insert_id;
        $content .= $this->attach_files($post_id);

        $wpdb->query($wpdb->prepare("UPDATE {$this->table_posts} SET text = %s WHERE id = %d;", $content, $post_id));
    }

    if (!$error) {
        wp_redirect(html_entity_decode($this->get_link($thread_id, $this->url_thread)."#postid-".$post_id));
        exit;
    } else {
        wp_die($msg);
    }
} else if (isset($_POST['add_post_submit'])) { // Adding a post reply
    $content = $_POST['message'];
    $post_id = '';
    $msg = '';

    if (empty($content)) {
        $msg .= '<h2>'.__("An error occured", "asgarosforum").'</h2>';
        $msg .= '<div id="error"><p>'.__("You must enter a message.", "asgarosforum").'</p></div>';
        $error = true;
    } else {
        $date = $this->current_time();

        $wpdb->query($wpdb->prepare("INSERT INTO {$this->table_posts} (parent_id, date, author_id) VALUES (%d, %s, %d);", $this->current_thread, $date, $user_ID));
        $post_id = $wpdb->insert_id;
        $content .= $this->attach_files($post_id);

        $wpdb->query($wpdb->prepare("UPDATE {$this->table_posts} SET text = %s WHERE id = %d;", $content, $post_id));
    }

    if (!$error) {
        wp_redirect(html_entity_decode($this->get_postlink($this->current_thread, $post_id)));
        exit;
    } else {
        wp_die($msg);
    }
} else if (isset($_POST['edit_post_submit'])) { // Editing a post
    $post_id = $_GET['id'];

    if ($this->element_exists($post_id, $this->table_posts)) {
        $content = $_POST['message'];
        $msg = '';

        if (empty($content) || (isset($_POST['subject']) && empty($_POST['subject']))) {
            $msg .= '<h2>'.__("An error occured", "asgarosforum").'</h2>';
            $msg .= '<div id="error"><p>'.__("You must enter a subject/message.", "asgarosforum").'</p></div>';
            $error = true;
        } else if ($user_ID != $this->get_post_author($post_id) && !$this->is_moderator()) {
            $msg .= '<h2>'.__("An error occured", "asgarosforum").'</h2>';
            $msg .= '<div id="error"><p>'.__("You do not have permission to edit this post!", "asgarosforum").'</p></div>';
            $error = true;
        } else {
            $content .= $this->attach_files($post_id);
            $wpdb->query($wpdb->prepare("UPDATE {$this->table_posts} SET text = %s WHERE id = %d;", $content, $post_id));

            if (isset($_POST['subject']) && !empty($_POST['subject'])) {
                $wpdb->query($wpdb->prepare("UPDATE {$this->table_threads} SET name = %s WHERE id = %d;", $_POST['subject'], $this->current_thread));
            }
        }
    } else {
        $msg .= '<h2>'.__("An error occured", "asgarosforum").'</h2>';
        $msg .= '<div id="error"><p>'.__("This post does not exist!", "asgarosforum").'</p></div>';
        $error = true;
    }

    if (!$error) {
        wp_redirect(html_entity_decode($this->get_postlink($this->current_thread, $post_id, $_POST['page_id'])));
        exit;
    } else {
        wp_die($msg);
    }
}
?>
