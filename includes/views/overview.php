<?php

if (!defined('ABSPATH')) exit;

$forumsAvailable = false;

foreach ($categories as $category) {
    echo '<div class="title-element" id="forum-category-'.$category->term_id.'">'.$category->name.'</div>';
    echo '<div class="content-element space">';
        $forums = $this->get_forums($category->term_id);
        if (empty($forums)) {
            echo '<div class="notice">'.__('In this category are no forums yet!', 'asgaros-forum').'</div>';
        } else {
            foreach ($forums as $forum) {
                $forumsAvailable = true;
                require('forum-element.php');
            }
        }
    echo '</div>';
}

if ($forumsAvailable) {
    AsgarosForumUnread::showUnreadControls();
}
