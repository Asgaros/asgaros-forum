<?php
global $wpdb;
$error = false;

function attach_files($post_id) {
    // Check for files first
    $files = array();
    $list = '';
    $links = '';

    if (isset($_FILES)) {
        foreach ($_FILES['forumfile']['name'] as $index =>$tmpName) {
            if (empty($_FILES['forumfile']['error'][$index]) && !empty($_FILES['forumfile']['name'][$index])) {
                $files[$index] = true;
            }
        }
    }

    // Upload them
    if (count($files) > 0) {
        $upload_dir = wp_upload_dir();
        $path = $upload_dir['basedir'].'/asgarosforum/'.$post_id.'/';
        $url = $upload_dir['baseurl'].'/asgarosforum/'.$post_id.'/';

        if (!is_dir($path)) {
            mkdir($path);
        }

        foreach($files as $index => $name) {
            $temp = $_FILES['forumfile']['tmp_name'][$index];
            $name = sanitize_file_name(stripslashes($_FILES['forumfile']['name'][$index]));

            if (!empty($name)) {
                move_uploaded_file($temp, $path.$name);
                $links .= '<li><a href="'.$url.$name.'" target="_blank">'.$name.'</a></li>';
            }
        }

        if (!empty($links)) {
            $list .= '<p><strong>'.__("Uploaded files:", "asgarosforum").'</strong></p>';
            $list .= '<ul>';
            $list .= $links;
            $list .= '</ul>';
        }
    }

    return $list;
}

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
        $content .= attach_files($post_id);

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
        $content .= attach_files($post_id);

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

        if (empty($content)) {
            $msg .= '<h2>'.__("An error occured", "asgarosforum").'</h2>';
            $msg .= '<div id="error"><p>'.__("You must enter a message.", "asgarosforum").'</p></div>';
            $error = true;
        } else if ($user_ID != $this->get_post_author($post_id) && !$this->is_moderator()) {
            $msg .= '<h2>'.__("An error occured", "asgarosforum").'</h2>';
            $msg .= '<div id="error"><p>'.__("You do not have permission to edit this post!", "asgarosforum").'</p></div>';
            $error = true;
        } else {
            $wpdb->query($wpdb->prepare("UPDATE {$this->table_posts} SET text = %s WHERE id = %d;", $content, $post_id));
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
