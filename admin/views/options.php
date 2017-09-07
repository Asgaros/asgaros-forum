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
        <?php wp_nonce_field('asgaros_forum_save_options'); ?>
        <h3><?php _e('General', 'asgaros-forum'); ?></h3>
        <p>
            <label for="location"><?php _e('Forum location:', 'asgaros-forum'); ?></label>
            <?php
            // Set a post_status argument because of a core bug.
            // See: https://core.trac.wordpress.org/ticket/8592
            wp_dropdown_pages(array('selected' => esc_attr($asgarosforum->options['location']), 'name' => 'location', 'id' => 'location', 'post_status' => array('publish', 'pending', 'draft', 'private')));
            echo '<span class="description">'.__('Page which contains the [forum]-shortcode.', 'asgaros-forum').'</span>';
            ?>
        </p>
        <p>
            <label for="posts_per_page"><?php _e('Replies to show per page:', 'asgaros-forum'); ?></label>
            <input type="number" name="posts_per_page" id="posts_per_page" value="<?php echo stripslashes($asgarosforum->options['posts_per_page']); ?>" size="3" min="1">
        </p>
        <p>
            <label for="topics_per_page"><?php _e('Topics to show per page:', 'asgaros-forum'); ?></label>
            <input type="number" name="topics_per_page" id="topics_per_page" value="<?php echo stripslashes($asgarosforum->options['topics_per_page']); ?>" size="3" min="1">
        </p>
        <p>
            <input type="checkbox" name="minimalistic_editor" id="minimalistic_editor" <?php checked(!empty($asgarosforum->options['minimalistic_editor'])); ?>>
            <label for="minimalistic_editor"><?php _e('Use minimalistic editor', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="allow_shortcodes" id="allow_shortcodes" <?php checked(!empty($asgarosforum->options['allow_shortcodes'])); ?>>
            <label for="allow_shortcodes"><?php _e('Allow shortcodes in posts', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="highlight_admin" id="highlight_admin" <?php checked(!empty($asgarosforum->options['highlight_admin'])); ?>>
            <label for="highlight_admin"><?php _e('Highlight administrator/moderator names', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="highlight_authors" id="highlight_authors" <?php checked(!empty($asgarosforum->options['highlight_authors'])); ?>>
            <label for="highlight_authors"><?php _e('Highlight topic authors', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="show_author_posts_counter" id="show_author_posts_counter" <?php checked(!empty($asgarosforum->options['show_author_posts_counter'])); ?>>
            <label for="show_author_posts_counter"><?php _e('Show author posts counter', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="show_edit_date" id="show_edit_date" <?php checked(!empty($asgarosforum->options['show_edit_date'])); ?>>
            <label for="show_edit_date"><?php _e('Show edit date', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="show_description_in_forum" id="show_description_in_forum" <?php checked(!empty($asgarosforum->options['show_description_in_forum'])); ?>>
            <label for="show_description_in_forum"><?php _e('Show description in forum', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="require_login" id="require_login" <?php checked(!empty($asgarosforum->options['require_login'])); ?>>
            <label for="require_login"><?php _e('Forum visible to logged in users only', 'asgaros-forum'); ?></label>
        </p>
        <h3><?php _e('Features', 'asgaros-forum'); ?></h3>
        <p>
            <input type="checkbox" name="enable_search" id="enable_search" <?php checked(!empty($asgarosforum->options['enable_search'])); ?>>
            <label for="enable_search"><?php _e('Enable search functionality', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="enable_breadcrumbs" id="enable_breadcrumbs" <?php checked(!empty($asgarosforum->options['enable_breadcrumbs'])); ?>>
            <label for="enable_breadcrumbs"><?php _e('Enable breadcrumbs', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="show_who_is_online" id="show_who_is_online" <?php checked(!empty($asgarosforum->options['show_who_is_online'])); ?>>
            <label for="show_who_is_online"><?php _e('Show who is online', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="show_statistics" id="show_statistics" <?php checked(!empty($asgarosforum->options['show_statistics'])); ?>>
            <label for="show_statistics"><?php _e('Show statistics', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="allow_signatures" id="allow_signatures" <?php checked(!empty($asgarosforum->options['allow_signatures'])); ?>>
            <label for="allow_signatures"><?php _e('Allow signatures', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="allow_guest_postings" id="allow_guest_postings" <?php checked(!empty($asgarosforum->options['allow_guest_postings'])); ?>>
            <label for="allow_guest_postings"><?php _e('Allow guest postings', 'asgaros-forum'); ?></label>
        </p>
        <h3><?php _e('Subscriptions', 'asgaros-forum'); ?></h3>
        <p>
            <input type="checkbox" name="admin_subscriptions" id="admin_subscriptions" <?php checked(!empty($asgarosforum->options['admin_subscriptions'])); ?>>
            <label for="admin_subscriptions"><?php _e('Notify site owner about new topics', 'asgaros-forum'); ?></label>
        </p>
        <p>
            <input type="checkbox" name="allow_subscriptions" id="allow_subscriptions" <?php checked(!empty($asgarosforum->options['allow_subscriptions'])); ?>>
            <label for="allow_subscriptions"><?php _e('Allow subscriptions (for logged-in users only)', 'asgaros-forum'); ?></label>
        </p>
        <?php
        // Set some default sender information.
        if (empty($asgarosforum->options['notification_sender_name'])) {
            $asgarosforum->options['notification_sender_name'] = get_bloginfo('name');
        }

        if (empty($asgarosforum->options['notification_sender_mail'])) {
            $asgarosforum->options['notification_sender_mail'] = get_bloginfo('admin_email');
        }
        ?>
        <p>
            <label for="notification_sender_name"><?php _e('Sender name:', 'asgaros-forum'); ?></label>
            <input class="regular-text" type="text" name="notification_sender_name" id="notification_sender_name" value="<?php echo esc_html(stripslashes($asgarosforum->options['notification_sender_name'])); ?>">
        </p>
        <p>
            <label for="notification_sender_mail"><?php _e('Sender mail:', 'asgaros-forum'); ?></label>
            <input class="regular-text" type="text" name="notification_sender_mail" id="notification_sender_mail" value="<?php echo esc_html(stripslashes($asgarosforum->options['notification_sender_mail'])); ?>">
        </p>
        <h3><?php _e('Profiles', 'asgaros-forum'); ?></h3>
        <?php
        $profileOption = checked(!empty($asgarosforum->options['enable_profiles']), true, false);
        ?>
        <p>
            <input type="checkbox" name="enable_profiles" id="enable_profiles" <?php checked(!empty($asgarosforum->options['enable_profiles'])); ?>>
            <label for="enable_profiles"><?php _e('Enable profiles', 'asgaros-forum'); ?></label>
        </p>
        <p class="profile-option" <?php if (!$profileOption) { echo 'style="display: none;"'; } ?>>
            <input type="checkbox" name="hide_profiles_from_guests" id="hide_profiles_from_guests" <?php checked(!empty($asgarosforum->options['hide_profiles_from_guests'])); ?>>
            <label for="hide_profiles_from_guests"><?php _e('Show profiles to logged-in users only', 'asgaros-forum'); ?></label>
        </p>
        <h3><?php _e('Uploads', 'asgaros-forum'); ?></h3>
        <?php
        $uploadsOption = checked(!empty($asgarosforum->options['allow_file_uploads']), true, false);
        ?>
        <p>
            <input type="checkbox" name="allow_file_uploads" id="allow_file_uploads" <?php echo $uploadsOption; ?>>
            <label for="allow_file_uploads"><?php _e('Allow file uploads', 'asgaros-forum'); ?></label>
        </p>
        <p class="uploads-option" <?php if (!$uploadsOption) { echo 'style="display: none;"'; } ?>>
            <input type="checkbox" name="allow_file_uploads_guests" id="allow_file_uploads_guests" <?php checked(!empty($asgarosforum->options['allow_file_uploads_guests'])); ?>>
            <label for="allow_file_uploads_guests"><?php _e('Guests can upload files', 'asgaros-forum'); ?></label>
        </p>
        <p class="uploads-option" <?php if (!$uploadsOption) { echo 'style="display: none;"'; } ?>>
            <input type="checkbox" name="uploads_show_thumbnails" id="uploads_show_thumbnails" <?php checked(!empty($asgarosforum->options['uploads_show_thumbnails'])); ?>>
            <label for="uploads_show_thumbnails"><?php _e('Show thumbnails', 'asgaros-forum'); ?></label>
        </p>
        <p class="uploads-option" <?php if (!$uploadsOption) { echo 'style="display: none;"'; } ?>>
            <input type="checkbox" name="hide_uploads_from_guests" id="hide_uploads_from_guests" <?php checked(!empty($asgarosforum->options['hide_uploads_from_guests'])); ?>>
            <label for="hide_uploads_from_guests"><?php _e('Show uploads to logged-in users only', 'asgaros-forum'); ?></label>
        </p>
        <p class="uploads-option" <?php if (!$uploadsOption) { echo 'style="display: none;"'; } ?>>
            <label for="allowed_filetypes"><?php _e('Allowed filetypes:', 'asgaros-forum'); ?></label>
            <input class="regular-text" type="text" name="allowed_filetypes" id="allowed_filetypes" value="<?php echo esc_html(stripslashes($asgarosforum->options['allowed_filetypes'])); ?>">
        </p>
        <p class="uploads-option" <?php if (!$uploadsOption) { echo 'style="display: none;"'; } ?>>
            <label for="uploads_maximum_number"><?php _e('Maximum files per post:', 'asgaros-forum'); ?></label>
            <input type="number" name="uploads_maximum_number" id="uploads_maximum_number" value="<?php echo stripslashes($asgarosforum->options['uploads_maximum_number']); ?>" size="3" min="0">
            <span class="description"><?php _e('(0 = No limitation)', 'asgaros-forum'); ?></span>
        </p>
        <p class="uploads-option" <?php if (!$uploadsOption) { echo 'style="display: none;"'; } ?>>
            <label for="uploads_maximum_size"><?php _e('Maximum file size (in megabyte):', 'asgaros-forum'); ?></label>
            <input type="number" name="uploads_maximum_size" id="uploads_maximum_size" value="<?php echo stripslashes($asgarosforum->options['uploads_maximum_size']); ?>" size="3" min="0">
            <span class="description"><?php _e('(0 = No limitation)', 'asgaros-forum'); ?></span>
        </p>
        <h3><?php _e('Appearance', 'asgaros-forum'); ?></h3>
        <?php
        $themes = AsgarosForumThemeManager::get_themes();
        if (count($themes) > 1) { ?>
        <p>
            <label for="theme"><?php _e('Theme', 'asgaros-forum'); ?>:</label>
            <select name="theme" id="theme">
                <?php foreach ($themes as $k => $v) {
                    echo '<option value="'.$k.'" '.selected($k, AsgarosForumThemeManager::get_current_theme(), false).'>'.$v['name'].'</option>';
                } ?>
            </select>
        </p>
        <?php
        }
        $themesOption = AsgarosForumThemeManager::is_default_theme();
        ?>
        <p class="custom-color-selector" <?php if (!$themesOption) { echo 'style="display: none;"'; } ?>>
            <label for="custom_color"><?php _e('Forum color:', 'asgaros-forum'); ?></label>
            <input type="text" value="<?php echo stripslashes($asgarosforum->options['custom_color']); ?>" class="color-picker" name="custom_color" id="custom_color" data-default-color="#2d89cc">
        </p>
        <p class="custom-color-selector" <?php if (!$themesOption) { echo 'style="display: none;"'; } ?>>
            <label for="custom_text_color"><?php _e('Text color:', 'asgaros-forum'); ?></label>
            <input type="text" value="<?php echo stripslashes($asgarosforum->options['custom_text_color']); ?>" class="color-picker" name="custom_text_color" id="custom_text_color" data-default-color="#444444">
        </p>
        <p class="custom-color-selector" <?php if (!$themesOption) { echo 'style="display: none;"'; } ?>>
            <label for="custom_background_color"><?php _e('Background color:', 'asgaros-forum'); ?></label>
            <input type="text" value="<?php echo stripslashes($asgarosforum->options['custom_background_color']); ?>" class="color-picker" name="custom_background_color" id="custom_background_color" data-default-color="#ffffff">
        </p>
        <input type="submit" name="af_options_submit" class="button button-primary" value="<?php _e('Save Options', 'asgaros-forum'); ?>">
    </form>
</div>
