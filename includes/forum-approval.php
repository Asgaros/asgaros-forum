<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumApproval {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;
    }

    // Checks if a topic is approved.
    public function is_approved($topic_id) {
        $approved = $this->asgarosforum->db->get_var("SELECT approved FROM {$this->asgarosforum->tables->topics} WHERE id = {$topic_id};");

        if ($approved === '1') {
            return true;
        } else {
            return false;
        }
    }

    // Approves a topic.
    public function approve_topic($topic_id) {
        $this->asgarosforum->db->update($this->asgarosforum->tables->topics, array('approved' => 1), array('id' => $topic_id), array('%d'), array('%d'));
    }

    // Sends a notification about a new unapproved topic.
    public function notify_about_new_unapproved_topic($topic_name, $topic_text, $topic_link, $topic_author) {
        $topic_name = esc_html(stripslashes($topic_name));
        $author_name = $this->asgarosforum->getUsername($topic_author);
        $notification_subject = __('New unapproved topic', 'asgaros-forum');

        // Prepare message-template.
        $replacements = array(
            '###AUTHOR###'  => $author_name,
            '###LINK###'    => '<a href="'.$topic_link.'">'.$topic_link.'</a>',
            '###TITLE###'   => $topic_name,
            '###CONTENT###' => wpautop(stripslashes($topic_text))
        );

        $notification_message = __('Hello ###USERNAME###,<br><br>You received this message because there is a new unapproved forum-topic.<br><br>Topic:<br>###TITLE###<br><br>Author:<br>###AUTHOR###<br><br>Text:<br>###CONTENT###<br><br>Link:<br>###LINK###', 'asgaros-forum');

        $admin_mail = get_bloginfo('admin_email');

        $this->asgarosforum->notifications->send_notifications($admin_mail, $notification_subject, $notification_message, $replacements);
    }

    // Checks if a topic requires approval for a specific forum and user.
    public function topic_requires_approval($forum_id, $user_id) {
        // If the current user is at least a moderator, no approval is needed.
        if ($this->asgarosforum->permissions->isModerator($user_id)) {
            return false;
        }

        // Check if the forum requires approval for new topics.
        $approval = $this->asgarosforum->db->get_var("SELECT approval FROM {$this->asgarosforum->tables->forums} WHERE id = {$forum_id};");

        // Additional checks if forum requires approval.
        if ($approval === '1') {
            // If the current user is a guest, approval is needed for sure.
            if (!is_user_logged_in()) {
                return true;
            }

            // If approval is needed for normal users as well, approval is needed because we already know the current user is not even a moderator.
            if ($this->asgarosforum->options['approval_for'] == 'normal') {
                return true;
            }
        }

        // Otherwise no approval is needed.
        return false;
    }
}
