<?php

if (!defined('ABSPATH')) exit;

$lastpost_data = false;

// Only fetch lastpost data when there are topics in the forum.
if ($forum->count_topics) {
    $lastpost_data = $this->get_lastpost_in_forum($forum->id);
}

// Get the read/unread status of a forum.
$unreadStatus = AsgarosForumUnread::getStatusForum($forum->id, $forum->count_topics);

// Format the element counters.
$count_topics_i18n = number_format_i18n($forum->count_topics);
$count_posts_i18n = number_format_i18n($forum->count_posts);

echo '<div class="forum" id="forum-'.$forum->id.'">';
    echo '<div class="forum-status">';
        echo '<span class="dashicons-before dashicons-overview '.$unreadStatus.'"></span>';
    echo '</div>';
    echo '<div class="forum-name">';
        echo '<strong><a href="'.$this->getLink('forum', $forum->id).'">'.esc_html(stripslashes($forum->name)).'</a></strong>';
        echo '<small>'.esc_html(stripslashes($forum->description)).'</small>';

        // Show subforums.
        if ($forum->count_subforums > 0) {
            echo '<small class="forum-subforums">';
            echo '<b>'.__('Subforums', 'asgaros-forum').':</b>&nbsp;';

            $subforums = $this->get_forums($category->term_id, $forum->id);
            $subforumsFirstDone = false;

            foreach ($subforums as $subforum) {
                echo ($subforumsFirstDone) ? '&nbsp;&middot;&nbsp;' : '';
                echo '<a href="'.$this->getLink('forum', $subforum->id).'">'.esc_html(stripslashes($subforum->name)).'</a>';
                $subforumsFirstDone = true;
            }

            echo '</small>';
        }
    echo '</div>';
    echo '<div class="forum-stats">';
        echo '<small>'.sprintf(_n('%s Topic', '%s Topics', $forum->count_topics, 'asgaros-forum'), $count_topics_i18n).'</small>';
        echo '<small>'.sprintf(_n('%s Post', '%s Posts', $forum->count_posts, 'asgaros-forum'), $count_posts_i18n).'</small>';
    echo '</div>';
    echo '<div class="forum-poster">'.$this->get_lastpost($lastpost_data).'</div>';
echo '</div>';
