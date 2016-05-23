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
    if ($_GET['view'] == "addthread") {
        if (!$this->current_forum || !$this->access) {
            $error = true;
            echo '<div class="notice">'.__('Sorry, this forum does not exist.', 'asgaros-forum').'</div>';
        }

        if (!$error && (!$this->get_forum_status() || AsgarosForumPermissions::isBanned('current'))) {
            $error = true;
            echo '<div class="notice">'.__('You are not allowed to do this.', 'asgaros-forum').'</div>';
        }
    } else if ($_GET['view'] == "addpost") {
        if (!$this->current_thread || !$this->access) {
            $error = true;
            echo '<div class="notice">'.__('Sorry, this thread does not exist.', 'asgaros-forum').'</div>';
        }

        if (!$error && (($this->get_status('closed') && !AsgarosForumPermissions::isModerator('current')) || AsgarosForumPermissions::isBanned('current'))) {
            $error = true;
            echo '<div class="notice">'.__('You are not allowed to do this.', 'asgaros-forum').'</div>';
        }

        if (!$error) {
            if (!isset($_POST['message']) && isset($_GET['quote']) && $this->element_exists($_GET['quote'], $this->table_posts)) {
                $quote_id = esc_html($_GET['quote']);
                $text = $wpdb->get_row($wpdb->prepare("SELECT text, author_id, date FROM {$this->table_posts} WHERE id = %d;", $quote_id));
                $display_name = $this->get_username($text->author_id);
                $threadcontent = '<blockquote><div class="quotetitle">'.__('Quote from', 'asgaros-forum').' '.$display_name.' '.sprintf(__('on %s', 'asgaros-forum'), $this->format_date($text->date)).'</div>'.$text->text.'</blockquote><br />';
            }
        }
    } else if ($_GET['view'] == "editpost") {
        if (!$this->element_exists($_GET['id'], $this->table_posts) || !$this->access) {
            $error = true;
            echo '<div class="notice">'.__('Sorry, this post does not exist.', 'asgaros-forum').'</div>';
        }

        if (!$error) {
            $id = (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) ? (int)$_GET['id'] : 0;
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

if (!$error) {
    if (!empty($this->error)) {
        echo '<div class="info">'.$this->error.'</div>';
    }

     ?>
    <form name="addform" method="post" enctype="multipart/form-data">
        <div class="title-element">
            <?php
            if ($_GET['view'] == "addthread") {
                _e('Post new Thread', 'asgaros-forum');
            } else if ($_GET['view'] == "addpost") {
                echo __('Post Reply:', 'asgaros-forum').' '.esc_html(stripslashes($this->get_name($this->current_thread, $this->table_threads)));
            } else if ($_GET['view'] == "editpost") {
                _e('Edit Post', 'asgaros-forum');
            }
            ?>
        </div>
        <div class="content-element">
            <?php if ($_GET['view'] == "addthread" || ($_GET['view'] == "editpost" && $this->is_first_post($post->id))) { ?>
                <div class="editor-row">
                    <div class="editor-cell"><span><?php _e('Subject:', 'asgaros-forum'); ?></span></div>
                    <div class="editor-cell"><input type="text" name="subject" value="<?php echo esc_html(stripslashes($threadname)); ?>"></div>
                </div>
            <?php } ?>
            <div class="editor-row">
                <div class="editor-cell"><span><?php _e('Message:', 'asgaros-forum'); ?></span></div>
                <div class="editor-cell message-editor">
                    <?php wp_editor(stripslashes($threadcontent), 'message', $this->options_editor); ?>
                </div>
            </div>
            <?php if ($_GET['view'] == "editpost") { ?>
                <?php AsgarosForumUploads::getFileList($post->id, $post->uploads); ?>
            <?php } ?>
            <?php if ($this->options['allow_file_uploads']) { ?>
    		<div class="editor-row">
    			<div class="editor-cell"><span><?php _e('Upload Files:', 'asgaros-forum'); ?></span></div>
    			<div class="editor-cell">
                    <?php echo __('Allowed filetypes:', 'asgaros-forum').'&nbsp'.esc_html($this->options['allowed_filetypes']).'<br />'; ?>
                    <input type="file" name="forumfile[]"><br />
                    <a id="add_file_link" href="#"><?php _e('Add another file ...', 'asgaros-forum'); ?></a>
    			</div>
    		</div>
            <?php } ?>
            <div class="editor-row">
                <div class="editor-cell"></div>
                <div class="editor-cell">
                <?php if ($_GET['view'] == "addthread") { ?>
                    <input type="submit" name="add_thread_submit" value="<?php _e('Submit', 'asgaros-forum'); ?>">
                <?php } else if ($_GET['view'] == "addpost") { ?>
                    <input type="submit" name="add_post_submit" value="<?php _e('Submit', 'asgaros-forum'); ?>">
                <?php } else if ($_GET['view'] == "editpost") { ?>
                    <input type="submit" name="edit_post_submit" value="<?php _e('Submit', 'asgaros-forum'); ?>">
                    <input type="hidden" name="part_id" value="<?php echo $_GET['part']; ?>">
                <?php } ?>
                </div>
            </div>
        </div>
    </form>
<?php } ?>
