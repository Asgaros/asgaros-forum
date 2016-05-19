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
        <h3><?php _e('General', 'asgaros-forum'); ?></h3>
        <p>
            <label for="posts_per_page"><?php _e('Replies to show per page:', 'asgaros-forum'); ?></label>
            <input type="text" name="posts_per_page" id="posts_per_page" value="<?php echo stripslashes($asgarosforum->options['posts_per_page']); ?>" size="3">
        </p>
        <p>
            <label for="threads_per_page"><?php _e('Threads to show per page:', 'asgaros-forum'); ?></label>
            <input type="text" name="threads_per_page" id="threads_per_page" value="<?php echo stripslashes($asgarosforum->options['threads_per_page']); ?>" size="3">
        </p>
        <p>
            <input type="checkbox" name="minimalistic_editor" id="minimalistic_editor" <?php checked(!empty($asgarosforum->options['minimalistic_editor'])); ?>>
            <label for="minimalistic_editor"><?php _e('Use minimalistic editor', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="highlight_admin" id="highlight_admin" <?php checked(!empty($asgarosforum->options['highlight_admin'])); ?>>
            <label for="highlight_admin"><?php _e('Highlight administrator/moderator names', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="show_edit_date" id="show_edit_date" <?php checked(!empty($asgarosforum->options['show_edit_date'])); ?>>
            <label for="show_edit_date"><?php _e('Show edit date', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="require_login" id="require_login" <?php checked(!empty($asgarosforum->options['require_login'])); ?>>
            <label for="require_login"><?php _e('Forum visible to logged in users only', 'asgaros-forum'); ?></label>
        </p>
        <h3><?php _e('Notifications', 'asgaros-forum'); ?></h3>
        <p>
            <input type="checkbox" name="admin_subscriptions" id="admin_subscriptions" <?php checked(!empty($asgarosforum->options['admin_subscriptions'])); ?>>
            <label for="admin_subscriptions"><?php _e('Notify administrator about new topics', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="allow_subscriptions" id="allow_subscriptions" <?php checked(!empty($asgarosforum->options['allow_subscriptions'])); ?>>
            <label for="allow_subscriptions"><?php _e('Allow thread-subscriptions (for logged-in users only)', 'asgaros-forum'); ?></label>
        </p>
        <h3><?php _e('Uploads', 'asgaros-forum'); ?></h3>
        <p>
            <input type="checkbox" name="allow_file_uploads" id="allow_file_uploads" <?php checked(!empty($asgarosforum->options['allow_file_uploads'])); ?>>
            <label for="allow_file_uploads"><?php _e('Allow file uploads', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <label for="allowed_filetypes"><?php _e('Allowed filetypes:', 'asgaros-forum'); ?></label>
            <input type="text" name="allowed_filetypes" id="allowed_filetypes" value="<?php echo stripslashes($asgarosforum->options['allowed_filetypes']); ?>" size="3">
        </p>
        <h3><?php _e('Appearance', 'asgaros-forum'); ?></h3>
        <?php
        $themes = AsgarosForumThemeManager::get_themes();
        if (count($themes) > 1) { ?>
        <p>
            <label for="theme"><?php _e('Theme', 'asgaros-forum'); ?>:</label>
            <select name="theme">
                <?php foreach ($themes as $k => $v) {
                    echo '<option value="'.$k.'" '.selected($k, AsgarosForumThemeManager::get_current_theme(), false).'>'.$v['name'].'</option>';
                } ?>
            </select>
        </p>
        <?php } ?>
        <p class="custom-color-selector" <?php if (!AsgarosForumThemeManager::is_default_theme()) { echo 'style="display: none;"'; } ?>>
            <label for="custom_color"><?php _e('Forum color:', 'asgaros-forum'); ?></label>
            <input type="text" value="<?php echo stripslashes($asgarosforum->options['custom_color']); ?>" class="color-picker" name="custom_color" id="custom_color" data-default-color="#2d89cc">
        </p>
        <p class="custom-color-selector" <?php if (!AsgarosForumThemeManager::is_default_theme()) { echo 'style="display: none;"'; } ?>>
            <label for="custom_text_color"><?php _e('Text color:', 'asgaros-forum'); ?></label>
            <input type="text" value="<?php echo stripslashes($asgarosforum->options['custom_text_color']); ?>" class="color-picker" name="custom_text_color" id="custom_text_color" data-default-color="#444444">
        </p>
        <p class="custom-color-selector" <?php if (!AsgarosForumThemeManager::is_default_theme()) { echo 'style="display: none;"'; } ?>>
            <label for="custom_background_color"><?php _e('Background color:', 'asgaros-forum'); ?></label>
            <input type="text" value="<?php echo stripslashes($asgarosforum->options['custom_background_color']); ?>" class="color-picker" name="custom_background_color" id="custom_background_color" data-default-color="#ffffff">
        </p>
        <input type="submit" name="af_options_submit" class="button button-primary" value="<?php _e('Save Options', 'asgaros-forum'); ?>">
    </form>
</div>
