<table class="top_menus">
    <tr>
        <td class="pages">
            <?php if (count($threads) > 0): ?>
                <?php echo $this->pageing('thread'); ?>
            <?php endif; ?>
        </td>
        <td><?php echo $this->forum_menu(); ?></td>
    </tr>
</table>

<?php if ($thread_counter > 0): ?>
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
                            <small><?php _e('Created by:'); ?> <i><?php echo $this->profile_link($this->get_starter($thread->id)); ?></i></small>
                        </td>
                        <td class="forumstats">
                            <span class="icon-bubbles4"></span><span><?php echo (int) ($this->num_posts($thread->id) - 1); ?></span><br />
                            <span class="icon-eye"></span><span><?php echo (int) $thread->views; ?></span>
                        </td>
                        <td class="poster_in_forum"><?php echo $this->get_lastpost($thread->id); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($threads) > 0): ?>
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
                        <small><?php _e('Created by:'); ?> <i><?php echo $this->profile_link($this->get_starter($thread->id)); ?></i></small>
                    </td>
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
            <td class="pages">
                <?php if (count($threads) > 0): ?>
                    <?php echo $this->pageing('thread'); ?>
                <?php endif; ?>
            </td>
            <td><?php echo $this->forum_menu(); ?></td>
        </tr>
    </table>
<?php else: ?>
    <div class='notice'><?php _e("There are no threads yet!", "asgarosforum"); ?></div>
<?php endif; ?>
