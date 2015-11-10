<div class="wrap">
  <h2>Mingle Forum - <?php _e('Monetize', 'mingle-forum'); ?></h2>

  <?php if ($saved): ?>
    <div id="message" class="updated below-h2">
      <p><?php _e('Your options have been saved.', 'mingle-forum'); ?></p>
    </div>
  <?php endif; ?>

  <h3><?php _e('Monetize your Forum by placing Ads in strategic locations!', 'mingle-forum'); ?></h3>
  <i>* <?php _e('HTML is allowed in all ad areas below:', 'mingle-forum'); ?></i>

  <form action="" method="post">
    <div class="parent_ad_box">
      <input type="checkbox" name="mf_ad_above_forum_on" id="mf_ad_above_forum_on" class="mf_ad_enable" data-value="above_forum" value="true" <?php checked(!empty($mingleforum->ads_options['mf_ad_above_forum_on'])); ?> />
      <label for="mf_ad_above_forum_on"><?php echo __('Enable Area Above Forum', 'mingleforum'); ?></label>
      <div id="above_forum" class="mf_ad_hidden_area">
        <textarea name="mf_ad_above_forum_text" class="ad_area_text"><?php echo stripslashes($mingleforum->ads_options['mf_ad_above_forum']); ?></textarea><br/>
        <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-above-forum</small>
      </div>
    </div>

    <div class="parent_ad_box">
      <input type="checkbox" name="mf_ad_below_forum_on" id="mf_ad_below_forum_on" class="mf_ad_enable" data-value="below_forum" value="true" <?php checked(!empty($mingleforum->ads_options['mf_ad_below_forum_on'])); ?> />
      <label for="mf_ad_below_forum_on"><?php echo __('Enable Area Below Forum', 'mingleforum'); ?></label>
      <div id="below_forum" class="mf_ad_hidden_area">
        <textarea name="mf_ad_below_forum_text" class="ad_area_text"><?php echo stripslashes($mingleforum->ads_options['mf_ad_below_forum']); ?></textarea><br/>
        <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-below-forum</small>
      </div>
    </div>

    <div class="parent_ad_box">
      <input type="checkbox" name="mf_ad_above_branding_on" id="mf_ad_above_branding_on" class="mf_ad_enable" data-value="above_branding" value="true" <?php checked(!empty($mingleforum->ads_options['mf_ad_above_branding_on'])); ?> />
      <label for="mf_ad_above_branding_on"><?php echo __('Enable Area Above Branding', 'mingleforum'); ?></label>
      <div id="above_branding" class="mf_ad_hidden_area">
        <textarea name="mf_ad_above_branding_text" class="ad_area_text"><?php echo stripslashes($mingleforum->ads_options['mf_ad_above_branding']); ?></textarea><br/>
        <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-above-branding</small>
      </div>
    </div>

    <div class="parent_ad_box">
      <input type="checkbox" name="mf_ad_above_info_center_on" id="mf_ad_above_info_center_on" class="mf_ad_enable" data-value="above_info_center" value="true" <?php checked(!empty($mingleforum->ads_options['mf_ad_above_info_center_on'])); ?> />
      <label for="mf_ad_above_info_center_on"><?php echo __('Enable Area Above Info Center', 'mingleforum'); ?></label>
      <div id="above_info_center" class="mf_ad_hidden_area">
        <textarea name="mf_ad_above_info_center_text" class="ad_area_text"><?php echo stripslashes($mingleforum->ads_options['mf_ad_above_info_center']); ?></textarea><br/>
        <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-above-info-center</small>
      </div>
    </div>

    <div class="parent_ad_box">
      <input type="checkbox" name="mf_ad_below_menu_on" id="mf_ad_below_menu_on" class="mf_ad_enable" data-value="below_menu" value="true" <?php checked(!empty($mingleforum->ads_options['mf_ad_below_menu_on'])); ?> />
      <label for="mf_ad_below_menu_on"><?php echo __('Enable Area Below Menu', 'mingleforum'); ?></label>
      <div id="below_menu" class="mf_ad_hidden_area">
        <textarea name="mf_ad_below_menu_text" class="ad_area_text"><?php echo stripslashes($mingleforum->ads_options['mf_ad_below_menu']); ?></textarea><br/>
        <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-below-menu</small>
      </div>
    </div>

    <div class="parent_ad_box">
      <input type="checkbox" name="mf_ad_above_quick_reply_on" id="mf_ad_above_quick_reply_on" class="mf_ad_enable" data-value="above_quick_reply" value="true" <?php checked(!empty($mingleforum->ads_options['mf_ad_above_quick_reply_on'])); ?> />
      <label for="mf_ad_above_quick_reply_on"><?php echo __('Enable Area Above Quick Reply', 'mingleforum'); ?></label>
      <div id="above_quick_reply" class="mf_ad_hidden_area">
        <textarea name="mf_ad_above_quick_reply_text" class="ad_area_text"><?php echo stripslashes($mingleforum->ads_options['mf_ad_above_quick_reply']); ?></textarea><br/>
        <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-above-quick-reply</small>
      </div>
    </div>

    <div class="parent_ad_box">
      <input type="checkbox" name="mf_ad_below_first_post_on" id="mf_ad_below_first_post_on" class="mf_ad_enable" data-value="below_first_post" value="true" <?php checked(!empty($mingleforum->ads_options['mf_ad_below_first_post_on'])); ?> />
      <label for="mf_ad_below_first_post_on"><?php echo __('Enable Area Below First Post', 'mingleforum'); ?></label>
      <div id="below_first_post" class="mf_ad_hidden_area">
        <textarea name="mf_ad_below_first_post_text" class="ad_area_text"><?php echo stripslashes($mingleforum->ads_options['mf_ad_below_first_post']); ?></textarea><br/>
        <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-below-first-post</small>
      </div>
    </div>

    <div class="parent_ad_box">
      <label><?php echo __('Advanced: Specify Custom CSS', 'mingleforum'); ?></label>
      <div class="mf_ad_hidden_area">
        <textarea name="mf_ad_custom_css" class="ad_area_text"><?php echo stripslashes($mingleforum->ads_options['mf_ad_custom_css']); ?></textarea>
      </div>
    </div>

    <p>
      <input type="submit" name="mf_ads_options_save" value="<?php _e('Save Ads', 'mingle-forum'); ?>" class="button" />
    </p>
  </form>
</div>
