<?php $forum_counter = 0; ?>
<?php foreach ($categories as $category) { ?>
    <div class="title-element"><?php echo $category->name; ?></div>
    <div class="content-element space">
        <table>
            <?php
            $frs = $this->get_forums($category->id);
            if (count($frs) > 0) {
                foreach ($frs as $forum) {
                    $forum_counter++;
                    ?>
                    <tr>
                    <?php
                    $thread_id = $this->get_lastpost_data($forum->id, 'parent_id', $this->table_threads);
                    ?>
                        <td class="status-icon"><?php $this->get_thread_image($thread_id, 'overview'); ?></td>
                        <td><strong><a href="<?php echo $this->get_link($forum->id, $this->url_forum); ?>"><?php echo $forum->name; ?></a></strong><br /><?php echo $forum->description; ?></td>
                        <td class="forumstats"><?php _e("Threads: ", "asgarosforum"); ?>&nbsp;<?php echo $this->count_elements($forum->id, $this->table_threads); ?><br /><?php _e("Posts: ", "asgarosforum"); ?>&nbsp;<?php echo $this->count_posts_in_forum($forum->id); ?></td>
                        <td class="poster_in_forum"><?php echo $this->get_lastpost_in_forum($forum->id); ?></td>
                    </tr>
                <?php
                }
            } else { ?>
                <tr><td class="notice"><?php _e("There are no forums yet!", "asgarosforum"); ?></td></tr>
            <?php } ?>
        </table>
    </div>
<?php } ?>
<?php if ($forum_counter > 0) { ?>
<div class="footer">
    <span class="icon-files-empty-small-yes"></span><?php _e("New posts", "asgarosforum"); ?> &middot;
    <span class="icon-files-empty-small-no"></span><?php _e("No new posts", "asgarosforum"); ?> &middot;
    <span class="icon-checkmark"></span><a href="<?php echo $this->url_base; ?>markallread"><?php _e("Mark All Read", "asgarosforum"); ?></a>
</div>
<?php } ?>
