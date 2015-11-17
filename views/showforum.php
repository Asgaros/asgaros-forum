<table cellpadding="0" cellspacing="0">
  <tr class="pop_menus">
    <td width="100%"><?php echo $this->thread_pageing($forum_id); ?></td>
    <td><?php echo $this->forum_menu($this->current_group); ?></td>
  </tr>
</table>

<div class="wpf">
  <table class="wpf-table" id="topicTable">
    <tr>
      <th width="7%" class="forumIcon"><?php _e("Status", "asgarosforum"); ?></th>
      <th><?php _e("Topic Title", "asgarosforum"); ?></th>
      <th width="16%" nowrap="nowrap"><?php _e("Started by", "asgarosforum"); ?></th>
      <th width="7%"><?php _e("Replies", "asgarosforum"); ?></th>
      <th width="7%"><?php _e("Views", "asgarosforum"); ?></th>
      <th width="24%"><?php _e("Last post", "asgarosforum"); ?></th>
    </tr>

    <?php if ($sticky_threads && !$this->curr_page): //Prevent stickies from showing up on page 2...n ?>
      <tr>
        <th class="wpf-bright" colspan="6">
          <?php _e("Sticky Topics", "asgarosforum"); ?>
        </th>
      </tr>

      <?php foreach ($sticky_threads as $thread): ?>
        <tr>
          <td class="forumIcon" align="center">
            <img src="<?php echo $this->skin_url; ?>/images/topic/normal_post_sticky.png" /> <!-- hard coded img needs to be moved to css -->
          </td>
          <td class="wpf-alt sticky wpf-topic-title">
            <span class="topicTitle">
              <a href="<?php echo $this->get_threadlink($thread->id); ?>">
                <?php echo $this->cut_string($this->output_filter($thread->subject)); ?>
              </a>
              <?php echo $this->maybe_get_unread_image($thread->id); ?>
            </span>
            <?php if ($this->is_moderator($user_ID, $this->current_forum)): ?>
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
          <td class="img-avatar-forumstats" align="center">
            <?php echo $this->profile_link($this->get_starter($thread->id)); ?>
          </td>
          <td class="wpf-alt forumstats" align="center">
            <span class="icon-replies"><?php echo (int) ($this->num_posts($thread->id) - 1); ?></span>
          </td>
          <td class="wpf-alt forumstats" align="center">
            <span class="icon-views">
              <?php echo (int) $thread->views; ?>
            </span>
          </td>
          <td><small><?php echo $this->get_lastpost($thread->id); ?></small></td>
        </tr>
      <?php endforeach; ?>

      <tr>
        <th class="wpf-bright forumTopics" colspan="6">
          <?php _e("Forum Topics", "asgarosforum"); ?>
        </th>
      </tr>
    <?php endif; //END STICKIES ?>

    <?php foreach ($threads as $thread): ?>
      <tr class="<?php
      $alt = 'alt even';
      echo ($alt == 'alt even') ? 'odd' : 'alt even';
      ?>">
        <td class="forumIcon" align="center"><?php echo $this->get_topic_image($thread->id); ?></td>
        <td class="wpf-alt">
          <span class="topicTitle">
            <a href="<?php echo $this->get_threadlink($thread->id); ?>">
              <?php echo $this->cut_string($this->output_filter($thread->subject), 50); ?>
            </a>
            <?php echo $this->maybe_get_unread_image($thread->id); ?>
          </span>
          <?php if ($this->is_moderator($user_ID, $this->current_forum)): ?>
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
        <td class="img-avatar-forumstats" align="center">
          <?php echo $this->profile_link($this->get_starter($thread->id)); ?>
        </td>
        <td class="wpf-alt forumstats" align="center">
          <span class="icon-replies">
            <?php echo (int) ($this->num_posts($thread->id) - 1); ?>
          </span>
        </td>
        <td class="wpf-alt forumstats" align="center">
          <span class="icon-views">
            <?php echo (int) $thread->views; ?>
          </span>
        </td>
        <td><small><?php echo $this->get_lastpost($thread->id); ?></small></td>
      </tr>
    <?php endforeach; //END NORMAL THREADS ?>
  </table>
</div>

<table cellpadding="0" cellspacing="0">
  <tr class="pop_menus">
    <td width="100%"><?php echo $this->thread_pageing($forum_id); ?></td>
    <td><?php echo $this->forum_menu($this->current_group, "bottom"); ?></td>
  </tr>
</table>
