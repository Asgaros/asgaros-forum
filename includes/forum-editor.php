<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumEditor {
	private static $asgarosforum = null;

	public function __construct($object) {
		self::$asgarosforum = $object;
	}

    // Check permissions before loading the editor.
    private static function checkPermissions() {
        switch (self::$asgarosforum->current_view) {
            case 'addthread':
                // Error when the user is not logged-in and guest-posting is disabled.
                if (!is_user_logged_in() && !self::$asgarosforum->options['allow_guest_postings']) {
                    return false;
                    break;
                }

                // Error when the user is banned.
                if (AsgarosForumPermissions::isBanned('current')) {
                    return false;
                    break;
                }

                // Error when the forum is closed.
                if (!self::$asgarosforum->forumIsOpen()) {
                    return false;
                    break;
                }
                break;
            case 'addpost':
                // Error when user is not logged-in and guest-posting is disabled.
                if (!is_user_logged_in() && !self::$asgarosforum->options['allow_guest_postings']) {
                    return false;
                    break;
                }

                // Error when the user is banned.
                if (AsgarosForumPermissions::isBanned('current')) {
                    return false;
                    break;
                }

                // Error when the topic is closed and the user is not a moderator.
                if (self::$asgarosforum->get_status('closed') && !AsgarosForumPermissions::isModerator('current')) {
                    return false;
                    break;
                }
                break;
            case 'editpost':
                // Error when user is not logged-in.
                if (!is_user_logged_in()) {
                    return false;
                    break;
                }

                // Error when the user is banned.
                if (AsgarosForumPermissions::isBanned('current')) {
                    return false;
                    break;
                }

                // Error when the current user is not the author of the post and also not a moderator.
                if (AsgarosForumPermissions::$current_user_id != self::$asgarosforum->get_post_author(self::$asgarosforum->current_post) && !AsgarosForumPermissions::isModerator('current')) {
                    return false;
                    break;
                }
                break;
        }

        return true;
    }

    public static function showEditor() {
        if (!self::checkPermissions()) {
            echo '<div class="notice">'.__('You are not allowed to do this.', 'asgaros-forum').'</div>';
        } else {
            $post = false;
            $subject = (isset($_POST['subject'])) ? trim($_POST['subject']) : '';
            $message = (isset($_POST['message'])) ? trim($_POST['message']) : '';

            if (self::$asgarosforum->current_view === 'addpost') {
                if (!isset($_POST['message']) && isset($_GET['quote'])) {
                    $quoteData = self::$asgarosforum->db->get_row(self::$asgarosforum->db->prepare("SELECT text, author_id, date FROM ".self::$asgarosforum->tables->posts." WHERE id = %d;", absint($_GET['quote'])));

                    if ($quoteData) {
                        $message = '<blockquote><div class="quotetitle">'.__('Quote from', 'asgaros-forum').' '.self::$asgarosforum->get_username($quoteData->author_id).' '.sprintf(__('on %s', 'asgaros-forum'), self::$asgarosforum->format_date($quoteData->date)).'</div>'.$quoteData->text.'</blockquote><br />';
                    }
                }
            } else if (self::$asgarosforum->current_view === 'editpost') {
                $post = self::$asgarosforum->db->get_row(self::$asgarosforum->db->prepare("SELECT id, text, parent_id, author_id, uploads FROM ".self::$asgarosforum->tables->posts." WHERE id = %d;", self::$asgarosforum->current_post));

                if (!isset($_POST['message'])) {
                    $message = $post->text;
                }

                // TODO: Is first post query can get removed and get via the before query (get min(id)).
                if (!isset($_POST['subject']) && self::$asgarosforum->is_first_post($post->id)) {
                    $subject = self::$asgarosforum->db->get_var(self::$asgarosforum->db->prepare("SELECT name FROM ".self::$asgarosforum->tables->topics." WHERE id = %d;", $post->parent_id));
                }
            }

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
                                <input type="text" id="subject" name="subject" value="<?php echo esc_html(stripslashes($subject)); ?>">
                            </span>
                        </div>
                    <?php } ?>
                    <div class="editor-row no-padding">
                        <?php wp_editor(stripslashes($message), 'message', self::$asgarosforum->options_editor); ?>
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
