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
            <input type="text" name="posts_per_page" id="posts_per_page" value="<?php echo stripslashes($asgarosforum->options['posts_per_page']); ?>" size="3" />
        </p>
        <p>
            <label for="threads_per_page"><?php _e('Threads to show per page:', 'asgaros-forum'); ?></label>
            <input type="text" name="threads_per_page" id="threads_per_page" value="<?php echo stripslashes($asgarosforum->options['threads_per_page']); ?>" size="3" />
        </p>
        <p>
            <input type="checkbox" name="highlight_admin" id="highlight_admin" <?php checked(!empty($asgarosforum->options['highlight_admin'])); ?> />
            <label for="highlight_admin"><?php _e('Highlight administrator/moderator names', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="show_edit_date" id="show_edit_date" <?php checked(!empty($asgarosforum->options['show_edit_date'])); ?> />
            <label for="show_edit_date"><?php _e('Show edit date', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="require_login" id="require_login" <?php checked(!empty($asgarosforum->options['require_login'])); ?> />
            <label for="require_login"><?php _e('Forum visible to logged in users only', 'asgaros-forum'); ?></label>
        </p>
        <h3>Uploads</h3>
        <p>
            <input type="checkbox" name="allow_file_uploads" id="allow_file_uploads" <?php checked(!empty($asgarosforum->options['allow_file_uploads'])); ?> />
            <label for="allow_file_uploads"><?php _e('Allow file uploads', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <label for="allowed_filetypes"><?php _e('Allowed filetypes:', 'asgaros-forum'); ?></label>
            <input type="text" name="allowed_filetypes" id="allowed_filetypes" value="<?php echo stripslashes($asgarosforum->options['allowed_filetypes']); ?>" size="3" />
        </p>
        <h3>Themes</h3>
        <p>
            <label for="theme"><?php _e('Forum theme', 'asgaros-forum'); ?></label>
            <select name="theme" class="options">
                <?php
                $themes = ThemeManager::get_themes();
                foreach ($themes as $k => $v) {
                ?><option value = "<?=$k?>" <?php if ( $k == ThemeManager::get_current_theme() ) { echo "selected"; }?>><?=$v['name']?></option >
                <?php } ?>
            </select>
        </p>
        <?php if (ThemeManager::get_current_theme() === ThemeManager::AF_DEFAULT_THEME) { ?>
        <p>
            <input type="text" value="<?php echo stripslashes($asgarosforum->options['custom_color']); ?>" class="custom-color" name="custom_color" data-default-color="#2d89cc" />
        </p>
        <?php } ?>
        <input type="submit" name="af_options_submit" class="button button-primary" value="<?php _e('Save Options', 'asgaros-forum'); ?>" />
    </form>
</div>
