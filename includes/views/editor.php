<?php

if (!defined('ABSPATH')) exit;

$post = "";
$thread = "";
$threadname = (isset($_POST['subject'])) ? trim($_POST['subject']) : '';
$threadcontent = (isset($_POST['message'])) ? trim($_POST['message']) : '';
$error = false;

if (!$user_ID) {
    $error = true;
    echo '<div class="notice">'.__('Sorry, you don\'t have permission to post.', 'asgaros-forum').'</div>';
}

if (!$error) {
    if ($this->current_view === 'addthread') {
        if (!$error && (!$this->get_forum_status() || AsgarosForumPermissions::isBanned('current'))) {
            $error = true;
            echo '<div class="notice">'.__('You are not allowed to do this.', 'asgaros-forum').'</div>';
        }
    } else if ($this->current_view === 'addpost') {
        if (!$error && (($this->get_status('closed') && !AsgarosForumPermissions::isModerator('current')) || AsgarosForumPermissions::isBanned('current'))) {
            $error = true;
            echo '<div class="notice">'.__('You are not allowed to do this.', 'asgaros-forum').'</div>';
        }

        if (!$error) {
            if (!isset($_POST['message']) && isset($_GET['quote']) && $this->element_exists($_GET['quote'], $this->table_posts)) {
                $quote_id = absint($_GET['quote']);
                $text = $wpdb->get_row($wpdb->prepare("SELECT text, author_id, date FROM {$this->table_posts} WHERE id = %d;", $quote_id));
                $display_name = $this->get_username($text->author_id);
                $threadcontent = '<blockquote><div class="quotetitle">'.__('Quote from', 'asgaros-forum').' '.$display_name.' '.sprintf(__('on %s', 'asgaros-forum'), $this->format_date($text->date)).'</div>'.$text->text.'</blockquote><br />';
            }
        }
    } else if ($this->current_view === 'editpost') {
        if (!$error) {
            $id = (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) ? absint($_GET['id']) : 0;
            $post = $wpdb->get_row($wpdb->prepare("SELECT id, text, parent_id, author_id, uploads FROM {$this->table_posts} WHERE id = %d;", $id));

            if (($user_ID != $post->author_id && !AsgarosForumPermissions::isModerator('current')) || AsgarosForumPermissions::isBanned('current')) {
                $error = true;
                echo '<div class="notice">'.__('Sorry, you are not allowed to edit this post.', 'asgaros-forum').'</div>';
            }
        }

        if (!$error) {
            if (!isset($_POST['message'])) {
                $threadcontent = $post->text;
            }

            if (!isset($_POST['subject']) && $this->is_first_post($post->id)) {
                $threadname = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$this->table_threads} WHERE id = %d;", $post->parent_id));
            }
        }
    }
}

if (!empty($this->error)) {
    echo '<div class="info">'.$this->error.'</div>';
}

if (!$error) {
     ?>
    <form id="forum-editor-form" name="addform" method="post" enctype="multipart/form-data">
        <div class="title-element">
            <?php
            if ($this->current_view === 'addthread') {
                _e('New Thread', 'asgaros-forum');
            } else if ($this->current_view === 'addpost') {
                echo __('Post Reply:', 'asgaros-forum').' '.esc_html(stripslashes($this->get_name($this->current_thread, $this->table_threads)));
            } else if ($this->current_view === 'editpost') {
                _e('Edit Post', 'asgaros-forum');
            }
            ?>
        </div>
        <div class="content-element">
            <?php if ($this->current_view === 'addthread' || ($this->current_view == 'editpost' && $this->is_first_post($post->id))) { ?>
                <div class="editor-row-subject">
                    <label for="subject"><?php _e('Subject:', 'asgaros-forum'); ?></label>
                    <span>
                        <input type="text" id="subject" name="subject" value="<?php echo esc_html(stripslashes($threadname)); ?>">
                    </span>
                </div>
            <?php } ?>
            <div class="editor-row no-padding">
                <?php wp_editor(stripslashes($threadcontent), 'message', $this->options_editor); ?>
            </div>
            <?php
            if ($this->current_view === 'editpost') {
                AsgarosForumUploads::getFileList($post->id, $post->uploads);
            }
            AsgarosForumUploads::showEditorUploadForm();
            AsgarosForumNotifications::showEditorSubscriptionOption();
            ?>
            <div class="editor-row">
                <?php if ($this->current_view === 'addthread') { ?>
                    <input type="hidden" name="submit_action" value="add_thread">
                <?php } else if ($this->current_view === 'addpost') { ?>
                    <input type="hidden" name="submit_action" value="add_post">
                <?php } else if ($this->current_view === 'editpost') { ?>
                    <input type="hidden" name="submit_action" value="edit_post">
                    <input type="hidden" name="part_id" value="<?php echo ($this->current_page + 1); ?>">
                <?php } ?>
                <input type="submit" value="<?php _e('Submit', 'asgaros-forum'); ?>">
            </div>
        </div>
    </form>
<?php } ?>
