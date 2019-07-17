<?php

if (!defined('ABSPATH')) exit;

if ($categories) {
    $forumsAvailable = false;

    foreach ($categories as $category) {
        // Reset forums-counter for ads when we enter a new category.
        $this->ads->counter_forums = 0;

        echo '<div class="title-element" id="forum-category-'.$category->term_id.'">';
            echo $category->name;
            echo '<span class="last-post-headline">'.__('Last post', 'asgaros-forum').'</span>';
        echo '</div>';
        echo '<div class="content-container">';
            $forums = $this->get_forums($category->term_id);
            if (empty($forums)) {
                $this->render_notice(__('In this category are no forums yet!', 'asgaros-forum'));
            } else {
                foreach ($forums as $forum) {
                    $forumsAvailable = true;

                    $this->render_forum_element($forum);
                }
            }
        echo '</div>';

        do_action('asgarosforum_after_category');
    }

    if ($forumsAvailable) {
        $this->unread->show_unread_controls();
    }

    AsgarosForumStatistics::showStatistics();
} else {
    $this->render_notice(__('There are no categories yet!', 'asgaros-forum'));
}
