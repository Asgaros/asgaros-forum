<?php

if (!defined('ABSPATH')) exit;

?>
<div class="wrap" id="af-options">
    <h2><?php _e('Options', 'asgaros-forum'); ?></h2>
    <?php if ($this->saved) { ?>
        <div class="updated">
            <p><?php _e('Your options have been saved.', 'asgaros-forum'); ?></p>
        </div>
    <?php } ?>
    <form method="post">
        <p>
            <label for="posts_per_page"><?php _e('Replies to show per page:', 'asgaros-forum'); ?></label>
            <input type="text" name="posts_per_page" id="posts_per_page" value="<?php echo stripslashes($asgarosforum->options['posts_per_page']); ?>" />
        </p>
        <p>
            <label for="threads_per_page"><?php _e('Threads to show per page:', 'asgaros-forum'); ?></label>
            <input type="text" name="threads_per_page" id="threads_per_page" value="<?php echo stripslashes($asgarosforum->options['threads_per_page']); ?>" />
        </p>
        <p>
            <input type="checkbox" name="allow_file_uploads" id="allow_file_uploads" <?php checked(!empty($asgarosforum->options['allow_file_uploads'])); ?> />
            <label for="allow_file_uploads"><?php _e('Allow file uploads', 'asgaros-forum'); ?></label>
        </p>
        <input type="submit" name="af_options_submit" class="button button-primary" value="<?php _e('Save Options', 'asgaros-forum'); ?>" />
    </form>
</div>
