<?php echo __("by", "asgarosforum") . ' ' . $this->profile_link($post->author_id); ?><br />
<a href="<?php echo $link; ?>"><?php echo date_i18n($this->dateFormat, strtotime($post->date)); ?>&nbsp;Uhr</a>
