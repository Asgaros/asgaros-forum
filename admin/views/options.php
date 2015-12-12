<div class="wrap">
    <h2>Forum - <?php _e('Options', 'asgarosforum'); ?></h2>
    <?php if ($this->saved): ?>
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
            <label for="forum_threads_per_page" class="mf_tb_label"><?php _e('Threads to show per page:', 'asgarosforum'); ?></label>
            <input type="text" name="forum_threads_per_page" id="forum_threads_per_page" value="<?php echo stripslashes($asgarosforum->options['forum_threads_per_page']); ?>" class="mf_tb" />
        </p>
        <p>
            <input type="checkbox" name="forum_allow_file_uploads" id="forum_allow_file_uploads" <?php checked(!empty($asgarosforum->options['forum_allow_file_uploads'])); ?> />
            <label for="forum_allow_file_uploads" class="mf_cb_label"><?php _e('Allow file uploads', 'asgarosforum'); ?></label>
        </p>
        <input type="submit" name="mf_options_submit" class="mf_admin_submit button" value="<?php _e('Save Options', 'asgarosforum'); ?>" />
    </form>
</div>
