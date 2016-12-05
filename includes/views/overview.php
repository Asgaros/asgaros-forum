<?php

if (!defined('ABSPATH')) exit;

$forumsAvailable = false;

foreach ($categories as $category) {
    echo '<div class="title-element" id="forum-category-'.$category->term_id.'">'.$category->name.'</div>';
    echo '<div class="content-element">';
        $forums = $this->get_forums($category->term_id);
        if (empty($forums)) {
            echo '<div class="notice">'.__('In this category are no forums yet!', 'asgaros-forum').'</div>';
        } else {
            $elementMarker = '';
            $forumsCounter = 0;
            foreach ($forums as $forum) {
                $forumsAvailable = true;
                $forumsCounter++;
                $elementMarker = ($forumsCounter & 1) ? 'odd' : 'even';
                require('forum-element.php');
            }
        }
    echo '</div>';
}

if ($forumsAvailable) {
    AsgarosForumUnread::showUnreadControls();
}
