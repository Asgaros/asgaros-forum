<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumEditor {
	private $asgarosforum = null;

	public function __construct($object) {
		$this->asgarosforum = $object;

		add_filter('teeny_mce_buttons', array($this, 'custom_mce_buttons'), 9999, 2);
        add_filter('mce_buttons', array($this, 'custom_mce_buttons'), 9999, 2);
        add_filter('disable_captions', array($this, 'disable_captions'));
	}

	public function custom_mce_buttons($buttons, $editor_id) {
        if ($this->asgarosforum->executePlugin && $editor_id === 'message') {
			// Add image button.
            $buttons[] = 'image';

            // Remove the read-more button.
            $searchKey = array_search('wp_more', $buttons);

            if ($searchKey !== false) {
                unset($buttons[$searchKey]);
            }
        }

		return $buttons;
    }

	public function disable_captions($args) {
        if ($this->asgarosforum->executePlugin) {
            return true;
        } else {
            return $args;
        }
    }

    // Check permissions before loading the editor.
    private function checkPermissions($editorView) {
        switch ($editorView) {
            case 'addtopic':
                // Error when the user is not logged-in and guest-posting is disabled.
                if (!is_user_logged_in() && !$this->asgarosforum->options['allow_guest_postings']) {
                    return false;
                    break;
                }

                // Error when the user is banned.
                if (AsgarosForumPermissions::isBanned('current')) {
                    return false;
                    break;
                }

                // Error when the forum is closed.
                if (!$this->asgarosforum->forumIsOpen()) {
                    return false;
                    break;
                }
                break;
            case 'addpost':
                // Error when user is not logged-in and guest-posting is disabled.
                if (!is_user_logged_in() && !$this->asgarosforum->options['allow_guest_postings']) {
                    return false;
                    break;
                }

                // Error when the user is banned.
                if (AsgarosForumPermissions::isBanned('current')) {
                    return false;
                    break;
                }

                // Error when the topic is closed and the user is not a moderator.
                if ($this->asgarosforum->get_status('closed') && !AsgarosForumPermissions::isModerator('current')) {
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

                // Error when the user cannot edit a post.
				$user_id = AsgarosForumPermissions::$currentUserID;

                if (!AsgarosForumPermissions::can_edit_post($user_id, $this->asgarosforum->current_post)) {
                    return false;
                    break;
                }
                break;
        }

        return true;
    }

    public function showEditor($editorView = false, $inOtherView = false) {
		$editorView = ($editorView) ? $editorView : $this->asgarosforum->current_view;

        if (!$this->checkPermissions($editorView) && !$inOtherView) {
            echo '<div class="notice">'.__('You are not allowed to do this.', 'asgaros-forum').'</div>';
        } else {
            $post = false;
            $subject = (isset($_POST['subject'])) ? trim($_POST['subject']) : '';
            $message = (isset($_POST['message'])) ? trim($_POST['message']) : '';

            if ($editorView === 'addpost') {
                if (!isset($_POST['message']) && isset($_GET['quote'])) {
                    $quoteData = $this->asgarosforum->db->get_row($this->asgarosforum->db->prepare("SELECT text, author_id, date FROM ".$this->asgarosforum->tables->posts." WHERE id = %d;", absint($_GET['quote'])));

                    if ($quoteData) {
                        $message = '<blockquote><div class="quotetitle">'.__('Quote from', 'asgaros-forum').' '.$this->asgarosforum->getUsername($quoteData->author_id).' '.sprintf(__('on %s', 'asgaros-forum'), $this->asgarosforum->format_date($quoteData->date)).'</div>'.stripslashes($quoteData->text).'</blockquote><br />';
					}
                }
            } else if ($editorView === 'editpost') {
                $post = $this->asgarosforum->db->get_row($this->asgarosforum->db->prepare("SELECT id, text, parent_id, author_id, uploads FROM ".$this->asgarosforum->tables->posts." WHERE id = %d;", $this->asgarosforum->current_post));

				if (!isset($_POST['message'])) {
                    $message = $post->text;
                }

                // TODO: Is first post query can get removed and get via the before query (get min(id)).
                if (!isset($_POST['subject']) && $this->asgarosforum->is_first_post($post->id)) {
                    $subject = $this->asgarosforum->current_topic_name;
                }
            }

			$editorTitle = '';
            if ($editorView === 'addtopic') {
                $editorTitle = __('New Topic', 'asgaros-forum');
            } else if ($editorView === 'addpost') {
                $editorTitle = __('Post Reply:', 'asgaros-forum').' '.esc_html(stripslashes($this->asgarosforum->current_topic_name));
            } else if ($editorView === 'editpost') {
                $editorTitle = __('Edit Post', 'asgaros-forum');
            }

			$actionURL = '';
			if ($editorView == 'addpost') {
				$actionURL = $this->asgarosforum->get_link('topic', $this->asgarosforum->current_topic);
			} else if ($editorView == 'editpost') {
				$actionURL = $this->asgarosforum->get_link('post_edit', $this->asgarosforum->current_post);
			} else if ($editorView == 'addtopic') {
				$actionURL = $this->asgarosforum->get_link('forum', $this->asgarosforum->current_forum);
			}

			// We need the tabindex attribute in the form for scrolling.
			?>
            <form id="forum-editor-form" tabindex="-1" name="addform" method="post" action="<?php echo $actionURL; ?>" enctype="multipart/form-data"<?php if ($inOtherView && !isset($_POST['subject']) && !isset($_POST['message'])) { echo ' style="display: none;"'; } ?>>
                <div class="title-element"><?php if ($inOtherView) { echo $editorTitle; } ?></div>
                <div class="content-element">
                    <?php if ($editorView === 'addtopic' || ($editorView == 'editpost' && $this->asgarosforum->is_first_post($post->id))) { ?>
                        <div class="editor-row-subject">
                            <label for="subject"><?php _e('Subject:', 'asgaros-forum'); ?></label>
                            <span>
                                <input type="text" id="subject" maxlength="255" name="subject" value="<?php echo esc_html(stripslashes($subject)); ?>">
                            </span>
                        </div>
                    <?php
					}

					echo '<div class="editor-row no-padding">';
                        wp_editor(stripslashes($message), 'message', $this->asgarosforum->options_editor);
                    echo '</div>';

                    $this->asgarosforum->uploads->show_editor_upload_form($post);
                    $this->asgarosforum->notifications->show_editor_subscription_option();
                    do_action('asgarosforum_editor_custom_content_bottom');

                    echo '<div class="editor-row">';
                        if ($editorView === 'addtopic') {
                            echo '<input type="hidden" name="submit_action" value="add_topic">';
                        } else if ($editorView === 'addpost') {
                            echo '<input type="hidden" name="submit_action" value="add_post">';
                        } else if ($editorView === 'editpost') {
                            echo '<input type="hidden" name="submit_action" value="edit_post">';
                            echo '<input type="hidden" name="part_id" value="'.($this->asgarosforum->current_page + 1).'">';
                        }

						echo '<div class="left">';
						if ($inOtherView) {
							echo '<a href="'.$actionURL.'" class="cancel">'.__('Cancel', 'asgaros-forum').'</a>';
						} else {
							if ($editorView === 'editpost') {
								$actionURL = $this->asgarosforum->get_link('topic', $this->asgarosforum->current_topic);
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
