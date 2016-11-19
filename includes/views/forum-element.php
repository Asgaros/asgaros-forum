<?php

if (!defined('ABSPATH')) exit;

$lastpost_data = $this->get_lastpost_in_forum($forum->id);

echo '<div class="forum" id="forum-'.$forum->id.'">';
    echo '<div class="forum-status">';
        $unreadStatus = AsgarosForumUnread::getStatusForum($forum->id);
        echo '<span class="dashicons-before dashicons-overview'.$unreadStatus.'"></span>';
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
        echo '<small>'.sprintf(_n('%s Thread', '%s Threads', $forum->count_threads, 'asgaros-forum'), $forum->count_threads).'</small>';
        echo '<small>'.sprintf(_n('%s Post', '%s Posts', $forum->count_posts, 'asgaros-forum'), $forum->count_posts).'</small>';
    echo '</div>';
    echo '<div class="forum-poster">'.$this->get_lastpost($lastpost_data).'</div>';
echo '</div>';
