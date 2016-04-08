<?php

if (!defined('ABSPATH')) exit;

$lastpost_data = $this->get_lastpost_in_forum($forum->id);

?>
<div class="forum" id="forum-<?php echo $forum->id; ?>">
    <div class="forum-status"><?php $this->get_thread_image($lastpost_data, 'overview'); ?></div>
    <div class="forum-name">
        <strong><a href="<?php echo $this->get_link($forum->id, $this->url_forum); ?>"><?php echo esc_html(stripslashes($forum->name)); ?></a></strong>
        <small><?php echo $forum->description; ?></small>
        <?php
        if ($forum->count_subforums > 0) {
            echo '<small class="forum-subforums">';
            echo '<b>'.__('Subforums', 'asgaros-forum').':</b>&nbsp;';

            $subforums = $this->get_forums($category->term_id, $forum->id);

            $subforums_counter = 0;
            foreach ($subforums as $subforum) {
                echo ($subforums_counter > 0) ? '&nbsp;&middot;&nbsp;' : '';
                echo '<a href="'.$this->get_link($subforum->id, $this->url_forum).'">'.esc_html(stripslashes($subforum->name)).'</a>';
                $subforums_counter++;
            }

            echo '</small>';
        }
        ?>
    </div>
    <div class="forum-stats">
        <small><?php echo sprintf(_n('%s Thread', '%s Threads', $forum->count_threads, 'asgaros-forum'), $forum->count_threads); ?></small>
        <small><?php echo sprintf(_n('%s Post', '%s Posts', $forum->count_posts, 'asgaros-forum'), $forum->count_posts); ?></small>
    </div>
    <div class="forum-poster"><?php echo $this->get_lastpost($lastpost_data); ?></div>
</div>
