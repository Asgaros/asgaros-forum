<table class="top_menus">
    <tr>
        <td class="pages">
            <?php if ($counter_normal > 0): ?>
                <?php echo $this->pageing($this->table_threads); ?>
            <?php endif; ?>
        </td>
        <td><?php $this->forum_menu(); ?></td>
    </tr>
</table>

<?php if ($counter_total > 0): ?>
    <div class="title-element"><?php echo $this->get_name($this->current_forum, $this->table_forums); ?></div>
    <div class="content-element">
        <table>
            <?php if ($sticky_threads && !$this->current_page): // Prevent stickies from showing up on page 2...n ?>
                <tr>
                    <td class="bright" colspan="4"><?php _e("Sticky Threads", "asgarosforum"); ?></td>
                </tr>

                <?php foreach ($sticky_threads as $thread): ?>
                    <tr>
                        <td class="status-icon"><?php $this->get_thread_image($thread->id, $thread->status); ?></td>
                        <td>
                            <strong><a href="<?php echo $this->get_link($thread->id, $this->url_thread); ?>"><?php echo $this->cut_string($thread->name); ?></a></strong><br />
                            <small><?php _e('Created by:'); ?> <i><?php echo $this->get_username($this->get_thread_starter($thread->id)); ?></i></small>
                        </td>
                        <td class="forumstats">
                            <span class="icon-bubbles4"></span><span><?php echo (int) ($this->count_elements($thread->id, $this->table_posts) - 1); ?></span><br />
                            <span class="icon-eye"></span><span><?php echo (int) $thread->views; ?></span>
                        </td>
                        <td class="poster_in_forum"><?php echo $this->get_lastpost_in_thread($thread->id); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($counter_normal > 0): ?>
                <tr>
                    <td class="bright" colspan="4"></td>
                </tr>
            <?php endif; ?>
            <?php endif; // END STICKIES ?>

            <?php foreach ($threads as $thread): ?>
                <tr>
                    <td class="status-icon"><?php $this->get_thread_image($thread->id, $thread->status); ?></td>
                    <td>
                        <strong><a href="<?php echo $this->get_link($thread->id, $this->url_thread); ?>"><?php echo $this->cut_string($thread->name); ?></a></strong><br />
                        <small><?php _e('Created by:'); ?> <i><?php echo $this->get_username($this->get_thread_starter($thread->id)); ?></i></small>
                    </td>
                    <td class="forumstats">
                        <span class="icon-bubbles4"></span><span><?php echo (int) ($this->count_elements($thread->id, $this->table_posts) - 1); ?></span><br />
                        <span class="icon-eye"></span><span><?php echo (int) $thread->views; ?></span>
                    </td>
                    <td class="poster_in_forum"><?php echo $this->get_lastpost_in_thread($thread->id); ?></td>
                </tr>
            <?php endforeach; // END NORMAL THREADS ?>
        </table>
    </div>

    <table class="top_menus">
        <tr>
            <td class="pages">
                <?php if ($counter_normal > 0): ?>
                    <?php echo $this->pageing($this->table_threads); ?>
                <?php endif; ?>
            </td>
            <td><?php $this->forum_menu(); ?></td>
        </tr>
    </table>
<?php else: ?>
    <div class='notice'><?php _e("There are no threads yet!", "asgarosforum"); ?></div>
<?php endif; ?>
