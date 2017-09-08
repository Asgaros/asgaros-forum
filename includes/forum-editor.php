<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumEditor {
	private static $asgarosforum = null;

	public function __construct($object) {
		self::$asgarosforum = $object;

		add_action('init', array($this, 'initialize'));
	}

	public function initialize() {
        // Empty ...
    }

    // Check permissions before loading the editor.
    private static function checkPermissions($editorView) {
        switch ($editorView) {
            case 'addtopic':
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
                if (AsgarosForumPermissions::$currentUserID != self::$asgarosforum->get_post_author(self::$asgarosforum->current_post) && !AsgarosForumPermissions::isModerator('current')) {
                    return false;
                    break;
                }
                break;
        }

        return true;
    }

    public static function showEditor($editorView = false, $inOtherView = false) {
		$editorView = ($editorView) ? $editorView : self::$asgarosforum->current_view;

        if (!self::checkPermissions($editorView) && !$inOtherView) {
            echo '<div class="notice">'.__('You are not allowed to do this.', 'asgaros-forum').'</div>';
        } else {
            $post = false;
            $subject = (isset($_POST['subject'])) ? trim($_POST['subject']) : '';
            $message = (isset($_POST['message'])) ? trim($_POST['message']) : '';

            if ($editorView === 'addpost') {
                if (!isset($_POST['message']) && isset($_GET['quote'])) {
                    $quoteData = self::$asgarosforum->db->get_row(self::$asgarosforum->db->prepare("SELECT text, author_id, date FROM ".self::$asgarosforum->tables->posts." WHERE id = %d;", absint($_GET['quote'])));

                    if ($quoteData) {
                        $message = '<blockquote><div class="quotetitle">'.__('Quote from', 'asgaros-forum').' '.self::$asgarosforum->getUsername($quoteData->author_id).' '.sprintf(__('on %s', 'asgaros-forum'), self::$asgarosforum->format_date($quoteData->date)).'</div>'.stripslashes($quoteData->text).'</blockquote><br />';
					}
                }
            } else if ($editorView === 'editpost') {
                $post = self::$asgarosforum->db->get_row(self::$asgarosforum->db->prepare("SELECT id, text, parent_id, author_id, uploads FROM ".self::$asgarosforum->tables->posts." WHERE id = %d;", self::$asgarosforum->current_post));

				if (!isset($_POST['message'])) {
                    $message = $post->text;
                }

                // TODO: Is first post query can get removed and get via the before query (get min(id)).
                if (!isset($_POST['subject']) && self::$asgarosforum->is_first_post($post->id)) {
                    $subject = self::$asgarosforum->current_topic_name;
                }
            }

			$editorTitle = '';
            if ($editorView === 'addtopic') {
                $editorTitle = __('New Topic', 'asgaros-forum');
            } else if ($editorView === 'addpost') {
                $editorTitle = __('Post Reply:', 'asgaros-forum').' '.esc_html(stripslashes(self::$asgarosforum->current_topic_name));
            } else if ($editorView === 'editpost') {
                $editorTitle = __('Edit Post', 'asgaros-forum');
            }

			$actionURL = '';
			if ($editorView == 'addpost') {
				$actionURL = self::$asgarosforum->getLink('topic', self::$asgarosforum->current_topic);
			} else if ($editorView == 'editpost') {
				$actionURL = self::$asgarosforum->getLink('post_edit', self::$asgarosforum->current_post);
			} else if ($editorView == 'addtopic') {
				$actionURL = self::$asgarosforum->getLink('forum', self::$asgarosforum->current_forum);
			}

			// We need the tabindex attribute in the form for scrolling.
			?>
            <form id="forum-editor-form" tabindex="-1" name="addform" method="post" action="<?php echo $actionURL; ?>" enctype="multipart/form-data"<?php if ($inOtherView && !isset($_POST['subject']) && !isset($_POST['message'])) { echo ' style="display: none;"'; } ?>>
                <div class="title-element"><?php if ($inOtherView) { echo $editorTitle; } ?></div>
                <div class="content-element">
                    <?php if ($editorView === 'addtopic' || ($editorView == 'editpost' && self::$asgarosforum->is_first_post($post->id))) { ?>
                        <div class="editor-row-subject">
                            <label for="subject"><?php _e('Subject:', 'asgaros-forum'); ?></label>
                            <span>
                                <input type="text" id="subject" maxlength="255" name="subject" value="<?php echo esc_html(stripslashes($subject)); ?>">
                            </span>
                        </div>
                    <?php
					}

					echo '<div class="editor-row no-padding">';
                        wp_editor(stripslashes($message), 'message', self::$asgarosforum->options_editor);
                    echo '</div>';

                    AsgarosForumUploads::showEditorUploadForm($post);
                    AsgarosForumNotifications::showEditorSubscriptionOption();
                    do_action('asgarosforum_editor_custom_content_bottom');

                    echo '<div class="editor-row">';
                        if ($editorView === 'addtopic') {
                            echo '<input type="hidden" name="submit_action" value="add_thread">';
                        } else if ($editorView === 'addpost') {
                            echo '<input type="hidden" name="submit_action" value="add_post">';
                        } else if ($editorView === 'editpost') {
                            echo '<input type="hidden" name="submit_action" value="edit_post">';
                            echo '<input type="hidden" name="part_id" value="'.(self::$asgarosforum->current_page + 1).'">';
                        }

						echo '<div class="left">';
						if ($inOtherView) {
							echo '<a href="'.$actionURL.'" class="cancel">'.__('Cancel', 'asgaros-forum').'</a>';
						} else {
							if ($editorView === 'editpost') {
								$actionURL = self::$asgarosforum->getLink('topic', self::$asgarosforum->current_topic);
							}
							echo '<a href="'.$actionURL.'" class="cancel-back">'.__('Cancel', 'asgaros-forum').'</a>';
						}
						echo '</div>';
	                    echo '<div class="right"><input type="submit" value="'.__('Submit', 'asgaros-forum').'"></div>';
                    echo '</div>';
                echo '</div>';
            echo '</form>';
        }
    }
}
