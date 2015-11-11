<div class="wrap">
  <h2>Mingle Forum - <?php _e('Options', 'mingle-forum'); ?></h2>

  <?php if($saved): ?>
    <div id="message" class="updated below-h2">
      <p><?php _e('Your options have been saved.', 'mingle-forum'); ?></p>
    </div>
  <?php endif; ?>

  <form action="" method="post" id="mf_options_form">
    <div id="mf-options-accordion">
      <h3><?php _e('General', 'mingle-forum'); ?></h3>
      <div>
        <p>
          <input type="checkbox" name="notify_admin_on_new_posts" id="notify_admin_on_new_posts" <?php checked(!empty($mingleforum->options['notify_admin_on_new_posts'])); ?> />
          <label for="notify_admin_on_new_posts" class="mf_cb_label">
            <?php _e('Email Site Administrator on all new Replies', 'mingle-forum'); ?>
          </label>
        </p>
        <p>
          <input type="checkbox" name="allow_user_replies_locked_cats" id="allow_user_replies_locked_cats" <?php checked(!empty($mingleforum->options['allow_user_replies_locked_cats'])); ?> />
          <label for="allow_user_replies_locked_cats" class="mf_cb_label">
            <?php _e('Users can reply in Locked Categories', 'mingle-forum'); ?>
          </label>
        </p>
        <p>
          <label for="forum_disabled_cats" class="mf_tb_label">
            <?php _e('Locked Categories (Admins only)', 'mingle-forum'); ?>
          </label>
          <!-- NEED to change this into a multi-select sometime soon -->
          <input type="text" name="forum_disabled_cats" id="forum_disabled_cats" value="<?php echo implode(',', $mingleforum->options['forum_disabled_cats']); ?>" class="mf_tb" />
        </p>
      </div>

      <h3><?php _e('Users', 'mingle-forum'); ?></h3>
      <div>
        <p>
          <input type="checkbox" name="forum_require_registration" id="forum_require_registration" <?php checked(!empty($mingleforum->options['forum_require_registration'])); ?> />
          <label for="forum_require_registration" class="mf_cb_label">
            <?php _e('Only Logged in Users can post Replies', 'mingle-forum'); ?>
          </label>
        </p>
        <p>
          <label for="forum_display_name" class="mf_tb_label">
            <?php _e('Display names publically as:', 'mingle-forum'); ?>
          </label>
          <select name="forum_display_name">
            <option value="user_login" <?php selected($mingleforum->options['forum_display_name'], 'user_login'); ?>><?php _e('user_login', 'mingle-forum'); ?></option>
            <option value="nickname" <?php selected($mingleforum->options['forum_display_name'], 'nickname'); ?>><?php _e('nickname', 'mingle-forum'); ?></option>
            <option value="display_name" <?php selected($mingleforum->options['forum_display_name'], 'display_name'); ?>><?php _e('display_name', 'mingle-forum'); ?></option>
            <option value="first_name" <?php selected($mingleforum->options['forum_display_name'], 'first_name'); ?>><?php _e('first_name', 'mingle-forum'); ?></option>
            <option value="last_name" <?php selected($mingleforum->options['forum_display_name'], 'last_name'); ?>><?php _e('last_name', 'mingle-forum'); ?></option>
          </select>
        </p>
      </div>

      <h3><?php _e('Formatting', 'mingle-forum'); ?></h3>
      <div>
        <p>
          <input type="checkbox" name="forum_use_gravatar" id="forum_use_gravatar" <?php checked(!empty($mingleforum->options['forum_use_gravatar'])); ?> />
          <label for="forum_use_gravatar" class="mf_cb_label">
            <?php _e('Show Avatars in the Forum', 'mingle-forum'); ?>
          </label>
        </p>
        <p>
          <label for="forum_posts_per_page" class="mf_tb_label">
            <?php _e('Replies to show per page', 'mingle-forum'); ?>
          </label>
          <input type="text" name="forum_posts_per_page" id="forum_posts_per_page" value="<?php echo stripslashes($mingleforum->options['forum_posts_per_page']); ?>" class="mf_tb" />
        </p>
        <p>
          <label for="forum_threads_per_page" class="mf_tb_label">
            <?php _e('Topics to show per page', 'mingle-forum'); ?>
          </label>
          <input type="text" name="forum_threads_per_page" id="forum_threads_per_page" value="<?php echo stripslashes($mingleforum->options['forum_threads_per_page']); ?>" class="mf_tb" />
        </p>
        <p>
          <label for="forum_date_format" class="mf_tb_label">
            <?php _e('Date format, see', 'mingle-forum'); ?> <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net</a>
          </label>
          <input type="text" name="forum_date_format" id="forum_date_format" value="<?php echo stripslashes($mingleforum->options['forum_date_format']); ?>" class="mf_tb" />
        </p>
      </div>

      <h3><?php _e('SEO', 'mingle-forum'); ?></h3>
      <div>
        <p>
          <input type="checkbox" name="forum_use_seo_friendly_urls" id="forum_use_seo_friendly_urls" <?php checked(!empty($mingleforum->options['forum_use_seo_friendly_urls'])); ?> />
          <label for="forum_use_seo_friendly_urls" class="mf_cb_label">
            <?php _e('Use SEO-friendly Permalinks', 'mingle-forum'); ?>
          </label>
        </p>
      </div>

      <h3><?php _e('SPAM and Security', 'mingle-forum'); ?></h3>
      <div>
        <p>
          <input type="checkbox" name="forum_captcha" id="forum_captcha" <?php checked(!empty($mingleforum->options['forum_captcha'])); ?> />
          <label for="forum_captcha" class="mf_cb_label">
            <?php _e('Guets must fill out Captcha when posting Replies', 'mingle-forum'); ?>
          </label>
        </p>
        <p>
          <input type="checkbox" name="forum_allow_image_uploads" id="forum_allow_image_uploads" <?php checked(!empty($mingleforum->options['forum_allow_image_uploads'])); ?> />
          <label for="forum_allow_image_uploads" class="mf_cb_label">
            <?php _e('Allow image uploads', 'mingle-forum'); ?>
          </label>
        </p>
        <p>
          <label for="forum_posting_time_limit" class="mf_tb_label">
            <?php _e('Seconds users must wait between Replies', 'mingle-forum'); ?>
          </label>
          <input type="text" name="forum_posting_time_limit" id="forum_posting_time_limit" value="<?php echo stripslashes($mingleforum->options['forum_posting_time_limit']); ?>" class="mf_tb" />
        </p>
      </div>
    </div>

    <input type="submit" name="mf_options_submit" class="mf_admin_submit button" value="<?php _e('Save Options', 'mingle-forum'); ?>" />
  </form>

</div> <!-- End wrap -->
