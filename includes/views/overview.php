<?php

if (!defined('ABSPATH')) exit;

$forum_counter = 0;

?>
<?php foreach ($categories as $category) { ?>
    <div class="title-element"><?php echo $category->name; ?></div>
    <div class="content-element space">
        <?php
        $frs = $this->get_forums($category->term_id);
        if (count($frs) > 0) {
            foreach ($frs as $forum) {
                $forum_counter++;
                $lastpost_data = $this->get_lastpost_data($forum->id, 'p.id, p.date, p.parent_id, p.author_id, t.name', 't');
                ?>
                <div class="forum">
                    <div class="forum-status"><?php $this->get_thread_image($lastpost_data, 'overview'); ?></div>
                    <div class="forum-name">
                        <strong><a href="<?php echo $this->get_link($forum->id, $this->url_forum); ?>"><?php echo esc_html($forum->name); ?></a></strong>
                        <small><?php echo esc_html($forum->description); ?></small>
                    </div>
                    <div class="forum-stats">
                        <small><?php echo sprintf(_n('%s Thread', '%s Threads', $forum->count_threads, 'asgaros-forum'), $forum->count_threads); ?></small>
                        <small><?php echo sprintf(_n('%s Post', '%s Posts', $forum->count_posts, 'asgaros-forum'), $forum->count_posts); ?></small>
                    </div>
                    <div class="forum-poster"><?php echo $this->get_lastpost($lastpost_data); ?></div>
                </div>
            <?php
            }
        } else { ?>
            <div class="notice"><?php _e('There are no forums yet!', 'asgaros-forum'); ?></div>
        <?php } ?>
    </div>
<?php } ?>
<?php if ($forum_counter > 0) { ?>
<div class="footer">
    <span class="icon-files-empty-small unread"></span><?php _e('New posts', 'asgaros-forum'); ?> &middot;
    <span class="icon-files-empty-small"></span><?php _e('No new posts', 'asgaros-forum'); ?> &middot;
    <span class="icon-checkmark"></span><a href="<?php echo $this->url_markallread; ?>"><?php _e('Mark All Read', 'asgaros-forum'); ?></a>
</div>
<?php } ?>
