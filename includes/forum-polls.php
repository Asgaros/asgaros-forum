<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPolls {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_action('asgarosforum_editor_custom_content_bottom', array($this, 'render_editor_form'));
    }

    public function render_editor_form() {
        // Cancel if poll-functionality is disabled.
        if (!$this->asgarosforum->options['enable_polls']) {
            return;
        }

        echo '<div class="editor-row">';
            echo '<span id="poll-toggle" class="row-title dashicons-before dashicons-chart-pie">'.__('Add Poll', 'asgaros-forum').'</span>';

            echo '<div id="poll-form">';
                echo '<div id="poll-question">';
                    echo '<input class="editor-subject-input" type="text" maxlength="255" name="poll-question-input" placeholder="'.__('Will you enter a question here?', 'asgaros-forum').'" value="">';
                echo '</div>';

                echo '<div id="poll-options">';
                    echo '<input class="editor-subject-input" type="text" maxlength="255" name="poll-question-option[]" placeholder="'.__('Yes', 'asgaros-forum').'" value="">';
                    echo '<input class="editor-subject-input" type="text" maxlength="255" name="poll-question-option[]" placeholder="'.__('No', 'asgaros-forum').'" value="">';
                echo '</div>';

                echo '<label class="editor-label">';
                    echo '<input type="checkbox" name="poll-multiple-answers"><span>'.__('Allow multiple answers', 'asgaros-forum').'</span>';
                echo '</label>';
            echo '</div>';
        echo '</div>';
    }
}
