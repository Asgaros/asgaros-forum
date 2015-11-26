
<table>
  <tr class="pop_menus">
          <td width="100%">
<?php if ($thread_counter > 0): ?>
              <?php echo $this->pageing($forum_id, 'thread'); ?>
      <?php endif; ?>
      </td>
    <td><?php echo $this->forum_menu(); ?></td>
  </tr>
</table>

<?php if ($thread_counter > 0): ?>
<div class="wpf">
  <table class="wpf-table full" id="topicTable">
    <tr>
      <th width="7%"><?php _e("Status", "asgarosforum"); ?></th>
      <th><?php _e("Topic Title", "asgarosforum"); ?></th>
      <th width="16%" nowrap="nowrap"><?php _e("Started by", "asgarosforum"); ?></th>
      <th width="7%"><?php _e("Replies", "asgarosforum"); ?></th>
      <th width="7%"><?php _e("Views", "asgarosforum"); ?></th>
      <th width="24%"><?php _e("Last post", "asgarosforum"); ?></th>
    </tr>

    <?php if ($sticky_threads && !$this->curr_page): //Prevent stickies from showing up on page 2...n ?>
      <tr>
        <td class="wpf-bright" colspan="6">
          <?php _e("Sticky Topics", "asgarosforum"); ?>
      </td>
      </tr>

      <?php foreach ($sticky_threads as $thread): ?>
        <tr>
          <td align="center">
             <?php echo $this->get_topic_image($thread->id); ?></td>
          </td>
          <td class="sticky wpf-topic-title">
            <span class="topicTitle">
              <a href="<?php echo $this->get_threadlink($thread->id); ?>">
                <?php echo $this->cut_string($thread->subject); ?>
              </a>
            </span>
            <?php if ($this->is_moderator($user_ID)): ?>
              <div class="mf_sticky_post_actions">
                <small>
                  <a href="<?php echo $this->forum_link . $this->current_forum . "&getNewForumID&topic={$thread->id}"; ?>">
                    <?php _e("Move Topic", "asgarosforum"); ?>
                  </a> |
                  <a href="<?php echo $this->forum_link . $this->current_forum . "&delete_topic&topic={$thread->id}"; ?>" onclick="return wpf_confirm();">
                    <?php _e("Delete Topic", "asgarosforum"); ?>
                  </a>
                </small>
              </div>
            <?php endif; ?>
          </td>
          <td align="center">
            <?php echo $this->profile_link($this->get_starter($thread->id)); ?>
          </td>
          <td class="forumstats" align="center">
            <span class="icon-bubbles4"></span><span><?php echo (int) ($this->num_posts($thread->id) - 1); ?></span>
          </td>
          <td class="forumstats" align="center">
            <span class="icon-eye"></span><span>
              <?php echo (int) $thread->views; ?>
            </span>
          </td>
          <td><small><?php echo $this->get_lastpost($thread->id); ?></small></td>
        </tr>
      <?php endforeach; ?>

      <tr>
        <td class="wpf-bright forumTopics" colspan="6">
          <?php _e("Forum Topics", "asgarosforum"); ?>
      </td>
      </tr>
    <?php endif; //END STICKIES ?>

    <?php foreach ($threads as $thread): ?>
      <tr>
        <td align="center">
            <?php echo $this->get_topic_image($thread->id); ?></td>
        <td>
          <span class="topicTitle">
            <a href="<?php echo $this->get_threadlink($thread->id); ?>">
              <?php echo $this->cut_string($thread->subject, 50); ?>
            </a>
          </span>
          <?php if ($this->is_moderator($user_ID)): ?>
            <div class="mf_post_actions">
              <small>
                <a href="<?php echo $this->forum_link . $this->current_forum . "&getNewForumID&topic={$thread->id}"; ?>">
                  <?php _e("Move Topic", "asgarosforum"); ?>
                </a> |
                <a href="<?php echo $this->forum_link . $this->current_forum . "&delete_topic&topic={$thread->id}"; ?>" onclick="return wpf_confirm();">
                  <?php _e("Delete Topic", "asgarosforum"); ?>
                </a>
              </small>
            </div>
          <?php endif; ?>
        </td>
        <td align="center">
          <?php echo $this->profile_link($this->get_starter($thread->id)); ?>
        </td>
        <td class="forumstats" align="center">
          <span class="icon-bubbles4"></span><span>
            <?php echo (int) ($this->num_posts($thread->id) - 1); ?>
          </span>
        </td>
        <td class="forumstats" align="center">
          <span class="icon-eye"></span><span>
            <?php echo (int) $thread->views; ?>
          </span>
        </td>
        <td><small><?php echo $this->get_lastpost($thread->id); ?></small></td>
      </tr>
    <?php endforeach; //END NORMAL THREADS ?>
  </table>
</div>

<table>
  <tr class="pop_menus">
    <td width="100%"><?php echo $this->pageing($forum_id, 'thread'); ?></td>
    <td><?php echo $this->forum_menu(); ?></td>
  </tr>
</table>
<?php else: ?>

<div class='wpf_notice'><?php _e("There are no threads yet!", "asgarosforum"); ?></div>
<?php endif; ?>
