<?php

if (!defined('ABSPATH')) exit;

// Get topics.
$topics = $this->content->get_topics($this->current_forum);
$topics_sticky = $this->content->get_sticky_topics($this->current_forum);

// Set counter.
$counter_normal = count($topics);
$counter_total = count($topics_sticky) + $counter_normal;

// Load editor.
$this->editor->showEditor('addtopic', true);

// Show pagination and menu.
echo '<div class="pages-and-menu">';
    $paginationRendering = ($counter_normal > 0) ? $this->pagination->renderPagination($this->tables->topics, $this->current_forum) : '';

    echo $paginationRendering;
    echo $this->showForumMenu();
    echo '<div class="clear"></div>';
echo '</div>';

// Show sub-forums.
$subforums = $this->get_forums($this->current_category, $this->current_forum);

if (!empty($subforums)) {
    echo '<div class="title-element">';
        echo __('Subforums', 'asgaros-forum');
        echo '<span class="last-post-headline">'.__('Last post', 'asgaros-forum').'</span>';
    echo '</div>';

    echo '<div class="content-element">';
        foreach ($subforums as $forum) {
            require('forum-element.php');
        }
    echo '</div>';
}

if ($counter_total > 0) {
    echo '<div class="title-element">';
        echo __('Topics', 'asgaros-forum');
        echo '<span class="last-post-headline">'.__('Last post', 'asgaros-forum').'</span>';
    echo '</div>';

    echo '<div class="content-element">';
        // Show sticky topics.
        if ($topics_sticky && !$this->current_page) {
            foreach ($topics_sticky as $topic) {
                $this->render_topic_element($topic, 'topic-sticky');
            }
        }

        foreach ($topics as $topic) {
            $this->render_topic_element($topic);
        }
    echo '</div>';

    echo '<div class="pages-and-menu">';
        echo $paginationRendering;
        echo $this->showForumMenu();
        echo '<div class="clear"></div>';
    echo '</div>';
} else {
    echo '<div class="title-element"></div>';

    echo '<div class="content-element">';
        echo '<div class="notice">'.__('There are no topics yet!', 'asgaros-forum').'</div>';
    echo '</div>';
}
