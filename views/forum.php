<table class="top_menus">
    <tr>
        <td class='pages'>
            <?php if ($thread_counter > 0): ?>
                <?php echo $this->pageing($forum_id, 'thread'); ?>
            <?php endif; ?>
        </td>
        <td><?php echo $this->forum_menu(); ?></td>
    </tr>
</table>

<?php if ($thread_counter > 0): ?>
    <div class="content-element">
        <table>
            <tr>
                <th><?php _e("Status", "asgarosforum"); ?></th>
                <th><?php _e("Topic Title", "asgarosforum"); ?></th>
                <th><?php _e("Started by", "asgarosforum"); ?></th>
                <th><?php _e("Statistics", "asgarosforum"); ?></th>
                <th><?php _e("Last post", "asgarosforum"); ?></th>
            </tr>

            <?php if ($sticky_threads && !$this->current_page): // Prevent stickies from showing up on page 2...n ?>
                <tr>
                    <td class="bright" colspan="5"><?php _e("Sticky Topics", "asgarosforum"); ?></td>
                </tr>

                <?php foreach ($sticky_threads as $thread): ?>
                    <tr>
                        <td class="status-icon"><?php echo $this->get_topic_image($thread->id); ?></td>
                        <td><a href="<?php echo $this->get_threadlink($thread->id); ?>"><?php echo $this->cut_string($thread->name); ?></a></td>
                        <td><?php echo $this->profile_link($this->get_starter($thread->id)); ?></td>
                        <td class="forumstats">
                            <span class="icon-bubbles4"></span><span><?php echo (int) ($this->num_posts($thread->id) - 1); ?></span><br />
                            <span class="icon-eye"></span><span><?php echo (int) $thread->views; ?></span>
                        </td>
                        <td class="poster_in_forum"><?php echo $this->get_lastpost($thread->id); ?></td>
                    </tr>
                <?php endforeach; ?>

                <tr>
                    <td class="bright" colspan="5"><?php _e("Forum Topics", "asgarosforum"); ?></td>
                </tr>
            <?php endif; // END STICKIES ?>

            <?php foreach ($threads as $thread): ?>
                <tr>
                    <td class="status-icon"><?php echo $this->get_topic_image($thread->id); ?></td>
                    <td><a href="<?php echo $this->get_threadlink($thread->id); ?>"><?php echo $this->cut_string($thread->name, 50); ?></a></td>
                    <td><?php echo $this->profile_link($this->get_starter($thread->id)); ?></td>
                    <td class="forumstats">
                        <span class="icon-bubbles4"></span><span><?php echo (int) ($this->num_posts($thread->id) - 1); ?></span><br />
                        <span class="icon-eye"></span><span><?php echo (int) $thread->views; ?></span>
                    </td>
                    <td class="poster_in_forum"><?php echo $this->get_lastpost($thread->id); ?></td>
                </tr>
            <?php endforeach; // END NORMAL THREADS ?>
        </table>
    </div>

    <table class="top_menus">
        <tr>
            <td class='pages'><?php echo $this->pageing($forum_id, 'thread'); ?></td>
            <td><?php echo $this->forum_menu(); ?></td>
        </tr>
    </table>
<?php else: ?>
    <div class='notice'><?php _e("There are no threads yet!", "asgarosforum"); ?></div>
<?php endif; ?>
