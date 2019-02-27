<?php

if (!defined('ABSPATH')) exit;

// Get counters and format them.
$count_topics = $this->get_forum_topic_counter($forum->id);
$count_topics_i18n = number_format_i18n($count_topics);
$count_posts = $this->get_forum_post_counter($forum->id);
$count_posts_i18n = number_format_i18n($count_posts);

// Get the read/unread status of a forum.
$unread_status = $this->unread->get_status_forum($forum->id, $count_topics);

echo '<div class="forum" id="forum-'.$forum->id.'">';
    $forum_icon = trim(esc_html(stripslashes($forum->icon)));
    $forum_icon = (empty($forum_icon)) ? 'dashicons-format-chat' : $forum_icon;

    echo '<div class="forum-status forum-dashicon dashicons-before '.$forum_icon.' '.$unread_status.'"></div>';
    echo '<div class="forum-name">';
        echo '<a class="forum-title" href="'.$this->get_link('forum', $forum->id).'">'.esc_html(stripslashes($forum->name)).'</a>';

        // Show the description of the forum when it is not empty.
        $forum_description = stripslashes($forum->description);
        if (!empty($forum_description)) {
            echo '<small class="forum-description">'.$forum_description.'</small>';
        }

        // Show forum stats.
        echo '<small class="forum-stats">';
            echo sprintf(_n('%s Topic', '%s Topics', $count_topics, 'asgaros-forum'), $count_topics_i18n);
            echo '&nbsp;&middot;&nbsp;';
            echo sprintf(_n('%s Post', '%s Posts', $count_posts, 'asgaros-forum'), $count_posts_i18n);
        echo '</small>';

        echo '<small class="forum-lastpost-small">';
            echo $this->render_lastpost_in_forum($forum->id, true);
        echo '</small>';

        // Show subforums.
        if ($forum->count_subforums > 0) {
            echo '<small class="forum-subforums">';
            echo '<b>'.__('Subforums', 'asgaros-forum').':</b>&nbsp;';

            $subforums = $this->get_forums($category->term_id, $forum->id);
            $subforumsFirstDone = false;

            foreach ($subforums as $subforum) {
                echo ($subforumsFirstDone) ? '&nbsp;&middot;&nbsp;' : '';
                echo '<a href="'.$this->get_link('forum', $subforum->id).'">'.esc_html(stripslashes($subforum->name)).'</a>';
                $subforumsFirstDone = true;
            }

            echo '</small>';
        }
    echo '</div>';
    do_action('asgarosforum_custom_forum_column', $forum->id);
    echo '<div class="forum-poster">'.$this->render_lastpost_in_forum($forum->id).'</div>';
echo '</div>';

do_action('asgarosforum_after_forum');
