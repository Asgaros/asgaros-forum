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
        // Load reports of the current user.
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();

            $this->reports[$user_id] = $this->get_reports_of_user($user_id);
        }
    }

    public function render_report_button($post_id, $topic_id) {
        // Only show a report button when the user is logged-in.
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();

            if (!$this->report_exists($post_id, $user_id)) {
                $report_message = __('Are you sure that you want to report this post?', 'asgaros-forum');
                $report_href = AsgarosForumRewrite::getLink('topic', $topic_id, array('post' => $post_id, 'report_add' => 1, 'part' => ($this->asgarosforum->current_page + 1)), '#postid-'.$post_id);

                echo '<a href="'.$report_href.'" title="'.__('Report Post', 'asgaros-forum').'" onclick="return confirm(\''.$report_message.'\');">';
                    echo '<span class="report-link dashicons-before dashicons-warning"></span>';
                echo '</a>';
            } else {
                echo '<span class="report-exists dashicons-before dashicons-warning" title="'.__('You reported this post.', 'asgaros-forum').'"></span>';
            }

            echo '&nbsp;&middot;&nbsp;';
        }
    }

    public function add_report($post_id, $user_id) {
        // Only add a report when the post exists ...
        if (AsgarosForumContent::postExists($post_id)) {
            // ... and when there is not already a report from the user.
            if (!$this->report_exists($post_id, $user_id)) {
                $this->asgarosforum->db->insert($this->asgarosforum->tables->reports, array('post_id' => $post_id, 'user_id' => $user_id), array('%d', '%d'));

                // Add the value also to the reports-array.
                $this->reports[$user_id][] = $post_id;
            }
        }
    }

    public function remove_report($post_id) {
        $this->asgarosforum->db->delete($this->asgarosforum->tables->reports, array('post_id' => $post_id), array('%d'));
    }

    public function report_exists($post_id, $user_id) {
        // Load records of user first when they are not loaded yet.
        if (!isset($this->reports[$user_id])) {
            $this->reports[$user_id] = $this->get_reports_of_user($user_id);
        }

        if (in_array($post_id, $this->reports[$user_id])) {
            return true;
        }

        return false;
    }

    public function get_reports_of_user($user_id) {
        return $this->asgarosforum->db->get_col($this->asgarosforum->db->prepare('SELECT post_id FROM '.$this->asgarosforum->tables->reports.' WHERE user_id = %d', $user_id));
    }

    // Returns all reported posts with a array of reporting users.
    public function get_reports() {
        $result = $this->asgarosforum->db->get_results('SELECT post_id, user_id FROM '.$this->asgarosforum->tables->reports.' ORDER BY post_id DESC;');

        $reports = array();

        foreach ($result as $report) {
            $reports[$report->post_id][] = $report->user_id;
        }

        return $reports;
    }
}