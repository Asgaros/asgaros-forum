<div class="wpf-item-poster">
  <div class="wpf-item-poster-li">
    <?php echo __("by", "asgarosforum") . ' ' . $this->profile_link($post->author_id); ?>
  </div>

  <div class="wpf-item-poster-li">
      <a href="<?php echo $link; ?>">
    <?php echo date_i18n($this->dateFormat, strtotime($post->date)); ?>&nbsp;Uhr
    </a>
  </div>
</div>
