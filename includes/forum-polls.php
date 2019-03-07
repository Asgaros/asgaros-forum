<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPolls {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_action('asgarosforum_editor_custom_content_bottom', array($this, 'render_poll_form'), 10, 1);
        add_action('asgarosforum_after_add_topic_submit', array($this, 'save_poll_form'), 10, 6);
    }

    public function render_poll_form($editor_view) {
        // Cancel if poll-functionality is disabled.
        if (!$this->asgarosforum->options['enable_polls']) {
            return;
        }

        // Cancel if we are not in the addtopic editor-view.
        if ($editor_view !== 'addtopic') {
            return;
        }

        echo '<div class="editor-row">';
            echo '<span id="poll-add" class="row-title poll-toggle dashicons-before dashicons-plus-alt">'.__('Add Poll', 'asgaros-forum').'</span>';
            echo '<span id="poll-remove" class="row-title poll-toggle dashicons-before dashicons-trash">'.__('Remove Poll', 'asgaros-forum').'</span>';

            echo '<div id="poll-form">';
                echo '<div id="poll-question">';
                    echo '<input class="editor-subject-input" type="text" maxlength="255" name="poll-title" placeholder="'.__('Will you enter a question here?', 'asgaros-forum').'" value="">';
                echo '</div>';

                echo '<div id="poll-options">';
                    echo '<input class="editor-subject-input" type="text" maxlength="255" name="poll-option[]" placeholder="'.__('Yes', 'asgaros-forum').'" value="">';
                    echo '<input class="editor-subject-input" type="text" maxlength="255" name="poll-option[]" placeholder="'.__('No', 'asgaros-forum').'" value="">';
                echo '</div>';

                echo '<label class="editor-label">';
                    echo '<input type="checkbox" name="poll-multiple"><span>'.__('Allow multiple answers', 'asgaros-forum').'</span>';
                echo '</label>';
            echo '</div>';
        echo '</div>';
    }

    public function save_poll_form($post_id, $topic_id, $topic_subject, $topic_content, $topic_link, $author_id) {
        // Cancel if poll-functionality is disabled.
        if (!$this->asgarosforum->options['enable_polls']) {
            return;
        }

        // Prepare variables.
        $poll_title = '';
        $poll_options = array();
        $poll_multiple = 0;

        // Cancel if no poll-title is set.
        if (empty($_POST['poll-title'])) {
            return;
        }

        // Trim poll-title and remove tags.
        $poll_title = trim(strip_tags($_POST['poll-title']));

        // Cancel if poll-title is empty.
        if (empty($poll_title)) {
            return;
        }

        // Cancel if no poll-options are set.
        if (empty($_POST['poll-option'])) {
            return;
        }

        // Assign not-empty poll-options to array.
        foreach ($_POST['poll-option'] as $option) {
            $poll_option = trim(strip_tags($option));

            if (!empty($poll_option)) {
                $poll_options[] = $poll_option;
            }
        }

        // Cancel if poll-options are empty.
        if (empty($poll_options)) {
            return;
        }

        // Set multiple-option.
        if (isset($_POST['poll-multiple'])) {
            $poll_multiple = 1;
        }

        // Insert poll.
        $this->asgarosforum->db->insert(
            $this->asgarosforum->tables->polls,
            array('topic_id' => $topic_id, 'title' => $poll_title, 'multiple' => $poll_multiple),
            array('%d', '%s', '%d')
        );

        // Get poll-id.
        $poll_id = $this->asgarosforum->db->insert_id;

        // Insert poll options.
        foreach ($poll_options as $option) {
            $this->asgarosforum->db->insert(
                $this->asgarosforum->tables->polls_options,
                array('poll_id' => $poll_id, 'option' => $option),
                array('%d', '%s')
            );
        }
    }
}
