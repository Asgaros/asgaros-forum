<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumEditor {
	private static $asgarosforum = null;

	public function __construct($object) {
		self::$asgarosforum = $object;
	}

    public static function showEditor() {
        $post = false;
        $thread = "";
        $threadname = (isset($_POST['subject'])) ? trim($_POST['subject']) : '';
        $threadcontent = (isset($_POST['message'])) ? trim($_POST['message']) : '';
        $error = false;

        if (!is_user_logged_in() && (!self::$asgarosforum->options['allow_guest_postings'] || self::$asgarosforum->current_view === 'editpost')) {
            $error = true;
            echo '<div class="notice">'.__('You are not allowed to do this.', 'asgaros-forum').'</div>';
        }

        if (!$error) {
            if (self::$asgarosforum->current_view === 'addthread') {
                if (!$error && (!self::$asgarosforum->get_forum_status() || (is_user_logged_in() && AsgarosForumPermissions::isBanned('current')))) {
                    $error = true;
                    echo '<div class="notice">'.__('You are not allowed to do this.', 'asgaros-forum').'</div>';
                }
            } else if (self::$asgarosforum->current_view === 'addpost') {
                if (!$error && ((is_user_logged_in() && ((self::$asgarosforum->get_status('closed') && !AsgarosForumPermissions::isModerator('current')) || AsgarosForumPermissions::isBanned('current'))) || (!is_user_logged_in() && self::$asgarosforum->get_status('closed')))) {
                    $error = true;
                    echo '<div class="notice">'.__('You are not allowed to do this.', 'asgaros-forum').'</div>';
                }

                if (!$error) {
                    if (!isset($_POST['message']) && isset($_GET['quote']) && self::$asgarosforum->element_exists($_GET['quote'], self::$asgarosforum->tables->posts)) {
                        $quote_id = absint($_GET['quote']);
                        $text = self::$asgarosforum->db->get_row(self::$asgarosforum->db->prepare("SELECT text, author_id, date FROM ".self::$asgarosforum->tables->posts." WHERE id = %d;", $quote_id));
                        $display_name = self::$asgarosforum->get_username($text->author_id);
                        $threadcontent = '<blockquote><div class="quotetitle">'.__('Quote from', 'asgaros-forum').' '.$display_name.' '.sprintf(__('on %s', 'asgaros-forum'), self::$asgarosforum->format_date($text->date)).'</div>'.$text->text.'</blockquote><br />';
                    }
                }
            } else if (self::$asgarosforum->current_view === 'editpost') {
                if (!$error) {
                    $id = (!empty($_GET['id']) && is_numeric($_GET['id'])) ? absint($_GET['id']) : 0;
                    $post = self::$asgarosforum->db->get_row(self::$asgarosforum->db->prepare("SELECT id, text, parent_id, author_id, uploads FROM ".self::$asgarosforum->tables->posts." WHERE id = %d;", $id));

                    if (!is_user_logged_in() || (get_current_user_id() != $post->author_id && !AsgarosForumPermissions::isModerator('current')) || AsgarosForumPermissions::isBanned('current')) {
                        $error = true;
                        echo '<div class="notice">'.__('Sorry, you are not allowed to edit this post.', 'asgaros-forum').'</div>';
                    }
                }

                if (!$error) {
                    if (!isset($_POST['message'])) {
                        $threadcontent = $post->text;
                    }

                    if (!isset($_POST['subject']) && self::$asgarosforum->is_first_post($post->id)) {
                        $threadname = self::$asgarosforum->db->get_var(self::$asgarosforum->db->prepare("SELECT name FROM ".self::$asgarosforum->tables->topics." WHERE id = %d;", $post->parent_id));
                    }
                }
            }
        }

        if (!empty(self::$asgarosforum->error)) {
            echo '<div class="info">'.self::$asgarosforum->error.'</div>';
        }

        if (!$error) {
            echo '<h1 class="main-title">';
            if (self::$asgarosforum->current_view === 'addthread') {
                _e('New Topic', 'asgaros-forum');
            } else if (self::$asgarosforum->current_view === 'addpost') {
                echo __('Post Reply:', 'asgaros-forum').' '.esc_html(stripslashes(self::$asgarosforum->get_name(self::$asgarosforum->current_topic, self::$asgarosforum->tables->topics)));
            } else if (self::$asgarosforum->current_view === 'editpost') {
                _e('Edit Post', 'asgaros-forum');
            }
            echo '</h1>'; ?>
            <form id="forum-editor-form" name="addform" method="post" enctype="multipart/form-data">
                <div class="title-element"></div>
                <div class="content-element">
                    <?php if (self::$asgarosforum->current_view === 'addthread' || (self::$asgarosforum->current_view == 'editpost' && self::$asgarosforum->is_first_post($post->id))) { ?>
                        <div class="editor-row-subject">
                            <label for="subject"><?php _e('Subject:', 'asgaros-forum'); ?></label>
                            <span>
                                <input type="text" id="subject" name="subject" value="<?php echo esc_html(stripslashes($threadname)); ?>">
                            </span>
                        </div>
                    <?php } ?>
                    <div class="editor-row no-padding">
                        <?php wp_editor(stripslashes($threadcontent), 'message', self::$asgarosforum->options_editor); ?>
                    </div>
                    <?php
                    AsgarosForumUploads::showEditorUploadForm($post);
                    AsgarosForumNotifications::showEditorSubscriptionOption();

                    do_action('asgarosforum_editor_custom_content_bottom');
                    ?>
                    <div class="editor-row">
                        <?php if (self::$asgarosforum->current_view === 'addthread') { ?>
                            <input type="hidden" name="submit_action" value="add_thread">
                        <?php } else if (self::$asgarosforum->current_view === 'addpost') { ?>
                            <input type="hidden" name="submit_action" value="add_post">
                        <?php } else if (self::$asgarosforum->current_view === 'editpost') { ?>
                            <input type="hidden" name="submit_action" value="edit_post">
                            <input type="hidden" name="part_id" value="<?php echo (self::$asgarosforum->current_page + 1); ?>">
                        <?php } ?>
                        <input type="submit" value="<?php _e('Submit', 'asgaros-forum'); ?>">
                    </div>
                </div>
            </form>
        <?php
        }
    }
}
