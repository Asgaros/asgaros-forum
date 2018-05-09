<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumReports {
    private $asgarosforum = null;
    private $reports = array();

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
    }

    public function initialize() {
        if ($this->asgarosforum->options['reports_enabled']) {
            // Load reports of the current user.
            if (is_user_logged_in()) {
                $reporter_id = get_current_user_id();

                $this->reports[$reporter_id] = $this->get_reports_of_user($reporter_id);
            }
        }
    }

    public function render_report_button($post_id, $topic_id) {
        if ($this->asgarosforum->options['reports_enabled']) {
            // Only show a report button when the user is logged-in.
            if (is_user_logged_in()) {
                $reporter_id = get_current_user_id();

                if (!$this->report_exists($post_id, $reporter_id)) {
                    $report_message = __('Are you sure that you want to report this post?', 'asgaros-forum');
                    $report_href = $this->asgarosforum->rewrite->get_link('topic', $topic_id, array('post' => $post_id, 'report_add' => 1, 'part' => ($this->asgarosforum->current_page + 1)), '#postid-'.$post_id);

                    echo '<a href="'.$report_href.'" title="'.__('Report Post', 'asgaros-forum').'" onclick="return confirm(\''.$report_message.'\');">';
                        echo '<span class="report-link dashicons-before dashicons-warning"></span>';
                    echo '</a>';
                } else {
                    echo '<span class="report-exists dashicons-before dashicons-warning" title="'.__('You reported this post.', 'asgaros-forum').'"></span>';
                }

                echo '&nbsp;&middot;&nbsp;';
            }
        }
    }

    public function add_report($post_id, $reporter_id) {
        // Only add a report when the post exists ...
        if ($this->asgarosforum->content->post_exists($post_id)) {
            // ... and the user is logged in ...
            if (is_user_logged_in()) {
                // ... and when there is not already a report from the user.
                if (!$this->report_exists($post_id, $reporter_id)) {
                    $this->asgarosforum->db->insert($this->asgarosforum->tables->reports, array('post_id' => $post_id, 'reporter_id' => $reporter_id), array('%d', '%d'));

                    // Send notification to site owner about new report.
                    $this->send_notification($post_id, $reporter_id);

                    // Add the value also to the reports-array.
                    $this->reports[$reporter_id][] = $post_id;
                }
            }
        }
    }

    public function send_notification($post_id, $reporter_id) {
        if ($this->asgarosforum->options['reports_notifications']) {
            $report = $this->get_report($post_id, $reporter_id);

            $author_name = $this->asgarosforum->getUsername($report['author_id']);
            $reporter = get_userdata($report['reporters']);

            $notification_subject = __('New report', 'asgaros-forum');
            $notification_message = sprintf(__('Hello,<br><br>There is a new report.<br><br>Topic:<br>%s<br><br>Post:<br>%s<br><br>Post Author:<br>%s<br><br>Reporter:<br>%s<br><br>Link to the post:<br><a href="%s">%s</a>', 'asgaros-forum'), $report['topic_name'], $report['post_text_raw'], $author_name, $reporter->display_name, $report['post_link'], $report['post_link']);

            $admin_mail = get_bloginfo('admin_email');

            $this->asgarosforum->notifications->send_notifications($admin_mail, $notification_subject, $notification_message);
        }
    }

    public function remove_report($post_id) {
        $this->asgarosforum->db->delete($this->asgarosforum->tables->reports, array('post_id' => $post_id), array('%d'));
    }

    public function report_exists($post_id, $reporter_id) {
        // Load records of user first when they are not loaded yet.
        if (!isset($this->reports[$reporter_id])) {
            $this->reports[$reporter_id] = $this->get_reports_of_user($reporter_id);
        }

        if (in_array($post_id, $this->reports[$reporter_id])) {
            return true;
        }

        return false;
    }

    public function get_reports_of_user($reporter_id) {
        return $this->asgarosforum->db->get_col($this->asgarosforum->db->prepare('SELECT post_id FROM '.$this->asgarosforum->tables->reports.' WHERE reporter_id = %d', $reporter_id));
    }

    // Returns all reported posts with an array of reporting users.
    public function get_reports() {
        $result = $this->asgarosforum->db->get_results('SELECT post_id, reporter_id FROM '.$this->asgarosforum->tables->reports.' ORDER BY post_id DESC;');

        $reports = array();

        foreach ($result as $report) {
            $reports[$report->post_id][] = $report->reporter_id;
        }

        return $reports;
    }

    // Returns data of a specific report.
    public function get_report($post_id, $reporter_ids) {
        $post_object    = $this->asgarosforum->content->get_post($post_id);
        $topic_object   = $this->asgarosforum->content->get_topic($post_object->parent_id);
        $post_link      = $this->asgarosforum->rewrite->get_post_link($post_id, $topic_object->id, false, array('highlight_post' => $post_id));

        $report = array(
            'post_id'       => $post_id,
            'post_text'     => esc_html(stripslashes($post_object->text)),
            'post_text_raw' => wpautop(stripslashes($post_object->text)),
            'post_link'     => $post_link,
            'topic_name'    => esc_html(stripslashes($topic_object->name)),
            'author_id'     => $post_object->author_id,
            'reporters'     => $reporter_ids
        );

        return $report;
    }

    public function count_reports() {
        $result = $this->asgarosforum->db->get_results('SELECT * FROM '.$this->asgarosforum->tables->reports.';');

        return count($result);
    }
}