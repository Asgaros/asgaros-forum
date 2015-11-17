<div class="wpf-item-poster">
  <div class="wpf-item-poster-li">
    <?php echo __("by", "asgarosforum") . ' ' . $this->profile_link($post->author_id); ?>
  </div>

  <div class="wpf-item-poster-li">
    <?php echo date_i18n($this->dateFormat, strtotime($post->date)); ?>
    <a href="<?php echo $link; ?>">
      <img title="<?php echo __("View last post", "asgarosforum"); ?>" style="vertical-align:middle;padding-left:5px;margin:-3px 0 0px 0;" src="<?php echo $this->skin_url; ?>/images/post/lastpost.png" />
    </a>
  </div>
</div>
