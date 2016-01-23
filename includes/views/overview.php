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
                $thread_id = $this->get_lastpost_data($forum->id, 'parent_id', 't');
                ?>
                <div class="forum">
                    <div class="forum-status"><?php $this->get_thread_image($thread_id, 'overview'); ?></div>
                    <div class="forum-name">
                        <strong><a href="<?php echo $this->get_link($forum->id, $this->url_forum); ?>"><?php echo $forum->name; ?></a></strong>
                        <small><?php echo $forum->description; ?></small>
                    </div>
                    <div class="forum-stats">
                        <small><?php _e('Threads:', 'asgaros-forum'); ?> <?php echo $this->count_elements($forum->id, $this->table_threads); ?></small>
                        <small><?php _e('Posts:', 'asgaros-forum'); ?> <?php echo $this->count_posts_in_forum($forum->id); ?></small>
                    </div>
                    <div class="forum-poster"><?php echo $this->get_lastpost_in_forum($forum->id); ?></div>
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
    <span class="icon-files-empty-small-yes"></span><?php _e('New posts', 'asgaros-forum'); ?> &middot;
    <span class="icon-files-empty-small-no"></span><?php _e('No new posts', 'asgaros-forum'); ?> &middot;
    <span class="icon-checkmark"></span><a href="<?php echo $this->url_base; ?>markallread"><?php _e('Mark All Read', 'asgaros-forum'); ?></a>
</div>
<?php } ?>
