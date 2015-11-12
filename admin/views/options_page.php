<div class="wrap">
    <h2>Mingle Forum - <?php _e('Options', 'mingle-forum'); ?></h2>
    <?php if($saved): ?>
        <div class="updated">
            <p><?php _e('Your options have been saved.', 'mingle-forum'); ?></p>
        </div>
    <?php endif; ?>
    <form action="" method="post" id="mf_options_form">
        <p>
            <label for="forum_posts_per_page" class="mf_tb_label"><?php _e('Replies to show per page:', 'mingle-forum'); ?></label>
            <input type="text" name="forum_posts_per_page" id="forum_posts_per_page" value="<?php echo stripslashes($mingleforum->options['forum_posts_per_page']); ?>" class="mf_tb" />
        </p>
        <p>
            <label for="forum_threads_per_page" class="mf_tb_label"><?php _e('Topics to show per page:', 'mingle-forum'); ?></label>
            <input type="text" name="forum_threads_per_page" id="forum_threads_per_page" value="<?php echo stripslashes($mingleforum->options['forum_threads_per_page']); ?>" class="mf_tb" />
        </p>
        <p>
            <label for="forum_display_name" class="mf_tb_label"><?php _e('Display names publically as:', 'mingle-forum'); ?></label>
            <select name="forum_display_name">
                <option value="user_login" <?php selected($mingleforum->options['forum_display_name'], 'user_login'); ?>><?php _e('user_login', 'mingle-forum'); ?></option>
                <option value="nickname" <?php selected($mingleforum->options['forum_display_name'], 'nickname'); ?>><?php _e('nickname', 'mingle-forum'); ?></option>
                <option value="display_name" <?php selected($mingleforum->options['forum_display_name'], 'display_name'); ?>><?php _e('display_name', 'mingle-forum'); ?></option>
                <option value="first_name" <?php selected($mingleforum->options['forum_display_name'], 'first_name'); ?>><?php _e('first_name', 'mingle-forum'); ?></option>
                <option value="last_name" <?php selected($mingleforum->options['forum_display_name'], 'last_name'); ?>><?php _e('last_name', 'mingle-forum'); ?></option>
            </select>
        </p>
        <p>
            <input type="checkbox" name="forum_require_registration" id="forum_require_registration" <?php checked(!empty($mingleforum->options['forum_require_registration'])); ?> />
            <label for="forum_require_registration" class="mf_cb_label"><?php _e('Only Logged in Users can post Replies', 'mingle-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="forum_use_gravatar" id="forum_use_gravatar" <?php checked(!empty($mingleforum->options['forum_use_gravatar'])); ?> />
            <label for="forum_use_gravatar" class="mf_cb_label"><?php _e('Show Avatars in the Forum', 'mingle-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="forum_use_seo_friendly_urls" id="forum_use_seo_friendly_urls" <?php checked(!empty($mingleforum->options['forum_use_seo_friendly_urls'])); ?> />
            <label for="forum_use_seo_friendly_urls" class="mf_cb_label"><?php _e('Use SEO-friendly Permalinks', 'mingle-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="forum_allow_image_uploads" id="forum_allow_image_uploads" <?php checked(!empty($mingleforum->options['forum_allow_image_uploads'])); ?> />
            <label for="forum_allow_image_uploads" class="mf_cb_label"><?php _e('Allow image uploads', 'mingle-forum'); ?></label>
        </p>
        <input type="submit" name="mf_options_submit" class="mf_admin_submit button" value="<?php _e('Save Options', 'mingle-forum'); ?>" />
    </form>
</div>
