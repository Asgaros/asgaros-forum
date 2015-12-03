<div class="wrap">
    <h2>Forum - <?php _e('Options', 'asgarosforum'); ?></h2>
    <?php if ($saved): ?>
        <div class="updated">
            <p><?php _e('Your options have been saved.', 'asgarosforum'); ?></p>
        </div>
    <?php endif; ?>
    <form action="" method="post" id="mf_options_form">
        <p>
            <label for="forum_posts_per_page" class="mf_tb_label"><?php _e('Replies to show per page:', 'asgarosforum'); ?></label>
            <input type="text" name="forum_posts_per_page" id="forum_posts_per_page" value="<?php echo stripslashes($asgarosforum->options['forum_posts_per_page']); ?>" class="mf_tb" />
        </p>
        <p>
            <label for="forum_threads_per_page" class="mf_tb_label"><?php _e('Topics to show per page:', 'asgarosforum'); ?></label>
            <input type="text" name="forum_threads_per_page" id="forum_threads_per_page" value="<?php echo stripslashes($asgarosforum->options['forum_threads_per_page']); ?>" class="mf_tb" />
        </p>
        <p>
            <label for="forum_display_name" class="mf_tb_label"><?php _e('Display names publically as:', 'asgarosforum'); ?></label>
            <select name="forum_display_name">
                <option value="user_login" <?php selected($asgarosforum->options['forum_display_name'], 'user_login'); ?>><?php _e('user_login', 'asgarosforum'); ?></option>
                <option value="nickname" <?php selected($asgarosforum->options['forum_display_name'], 'nickname'); ?>><?php _e('nickname', 'asgarosforum'); ?></option>
                <option value="display_name" <?php selected($asgarosforum->options['forum_display_name'], 'display_name'); ?>><?php _e('display_name', 'asgarosforum'); ?></option>
                <option value="first_name" <?php selected($asgarosforum->options['forum_display_name'], 'first_name'); ?>><?php _e('first_name', 'asgarosforum'); ?></option>
                <option value="last_name" <?php selected($asgarosforum->options['forum_display_name'], 'last_name'); ?>><?php _e('last_name', 'asgarosforum'); ?></option>
            </select>
        </p>
        <p>
            <input type="checkbox" name="forum_allow_image_uploads" id="forum_allow_image_uploads" <?php checked(!empty($asgarosforum->options['forum_allow_image_uploads'])); ?> />
            <label for="forum_allow_image_uploads" class="mf_cb_label"><?php _e('Allow image uploads', 'asgarosforum'); ?></label>
        </p>
        <input type="submit" name="mf_options_submit" class="mf_admin_submit button" value="<?php _e('Save Options', 'asgarosforum'); ?>" />
    </form>
</div>
