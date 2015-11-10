<li style="list-style: none outside none;">
  <div style="float:left;margin:10px 13px 0px 0;">
    <?php echo $this->get_avatar($post->author_id, 35); ?>
  </div>

  <div style="margin:0px 0px 12px 0;line-height:17px;">
    <div style="margin: 0px 0px 0px 50px;">
      <a href="<?php echo $this->get_paged_threadlink($post->parent_id, "#postid-" . $post->id); ?>"><?php echo $this->output_filter($post->subject); ?></a>
    </div>

    <div style="margin:0px 0px 0px 50px;">
      <?php echo __("by:", "mingleforum") . ' ' . $this->profile_link($post->author_id); ?>
    </div>

    <div style="margin:0px 0px 0px 50px;">
      <small><?php echo $this->format_date($post->date); ?></small>
      <a href="<?php echo $this->get_paged_threadlink($post->parent_id, "#postid-" . $post->id); ?>">
        <img title="<?php _e("Last post", "mingleforum"); ?>" style="vertical-align:middle;padding-left:10px;margin:0px 0 0px 0;border-radius:0px;box-shadow:none;" src="<?php echo $this->skin_url; ?>/images/post/lastpost.png" />
      </a>
    </div>
  </div>
</li>
