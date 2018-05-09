<?php

if (!defined('ABSPATH')) exit;

?>
<div class="wrap" id="af-options">
    <?php
    $title = __('Settings', 'asgaros-forum');
    $titleUpdated = __('Settings updated.', 'asgaros-forum');
    $this->render_admin_header($title, $titleUpdated);
    ?>
    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div class="postbox-container">

                <form method="post">
                    <?php wp_nonce_field('asgaros_forum_save_options'); ?>

                    <div class="postbox">
                        <h2 class="hndle dashicons-before dashicons-admin-settings"><?php _e('General', 'asgaros-forum'); ?></h2>
                        <div class="inside">
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
                                <input type="checkbox" name="create_blog_topics" id="create_blog_topics" <?php checked(!empty($asgarosforum->options['create_blog_topics'])); ?>>
                                <label for="create_blog_topics"><?php _e('Create topics for new blog posts in the following forum:', 'asgaros-forum'); ?></label>

                                <?php
                                echo '<select name="create_blog_topics_id">';

                                echo '<option value="0"'.(0 == $asgarosforum->options['create_blog_topics_id'] ? ' selected="selected"' : '').'>'.__('Select Forum', 'asgaros-forum').'</option>';

                                $categories = $asgarosforum->content->get_categories();

                                if ($categories) {
                                    foreach ($categories as $category) {
                                        $forums = $asgarosforum->get_forums($category->term_id, 0, true);

                                        if ($forums) {
                                            foreach ($forums as $forum) {
                                                echo '<option value="'.$forum->id.'"'.($forum->id == $asgarosforum->options['create_blog_topics_id'] ? ' selected="selected"' : '').'>'.esc_html($forum->name).'</option>';

                                                if ($forum->count_subforums > 0) {
                                                    $subforums = $asgarosforum->get_forums($category->term_id, $forum->id, true);

                                                    foreach ($subforums as $subforum) {
                                                        echo '<option value="'.$subforum->id.'"'.($subforum->id == $asgarosforum->options['create_blog_topics_id'] ? ' selected="selected"' : '').'>--- '.esc_html($subforum->name).'</option>';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                echo '</select>';
                                ?>
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
                                <input type="checkbox" name="show_description_in_forum" id="show_description_in_forum" <?php checked(!empty($asgarosforum->options['show_description_in_forum'])); ?>>
                                <label for="show_description_in_forum"><?php _e('Show description in forum', 'asgaros-forum'); ?></label>
                            </p>
                            <p>
                                <input type="checkbox" name="require_login" id="require_login" <?php checked(!empty($asgarosforum->options['require_login'])); ?>>
                                <label for="require_login"><?php _e('Forum visible to logged in users only', 'asgaros-forum'); ?></label>
                            </p>
                            <p>
                                <input type="checkbox" name="show_login_button" id="show_login_button" <?php checked(!empty($asgarosforum->options['show_login_button'])); ?>>
                                <label for="show_login_button"><?php _e('Show login button', 'asgaros-forum'); ?></label>
                            </p>
                            <p>
                                <input type="checkbox" name="show_logout_button" id="show_logout_button" <?php checked(!empty($asgarosforum->options['show_logout_button'])); ?>>
                                <label for="show_logout_button"><?php _e('Show logout button', 'asgaros-forum'); ?></label>
                            </p>
                            <p>
                                <input type="checkbox" name="show_register_button" id="show_register_button" <?php checked(!empty($asgarosforum->options['show_register_button'])); ?>>
                                <label for="show_register_button"><?php _e('Show register button', 'asgaros-forum'); ?></label>
                            </p>
                            <p>
                                <input type="checkbox" name="show_edit_date" id="show_edit_date" <?php checked(!empty($asgarosforum->options['show_edit_date'])); ?>>
                                <label for="show_edit_date"><?php _e('Show edit date', 'asgaros-forum'); ?></label>
                            </p>
                            <p>
                                <label for="time_limit_edit_posts"><?php _e('Time limitation for editing posts (in minutes):', 'asgaros-forum'); ?></label>
                                <input type="number" name="time_limit_edit_posts" id="time_limit_edit_posts" value="<?php echo stripslashes($asgarosforum->options['time_limit_edit_posts']); ?>" size="3" min="0">
                                <span class="description"><?php _e('(0 = No limitation)', 'asgaros-forum'); ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle dashicons-before dashicons-admin-plugins"><?php _e('Features', 'asgaros-forum'); ?></h2>
                        <div class="inside">
                            <p>
                                <input type="checkbox" name="enable_seo_urls" id="enable_seo_urls" <?php checked(!empty($asgarosforum->options['enable_seo_urls'])); ?>>
                                <label for="enable_seo_urls"><?php _e('Enable SEO-friendly URLs', 'asgaros-forum'); ?></label>
                            </p>
                            <p>
                                <input type="checkbox" name="enable_activity" id="enable_activity" <?php checked(!empty($asgarosforum->options['enable_activity'])); ?>>
                                <label for="enable_activity"><?php _e('Enable Activity Feed', 'asgaros-forum'); ?></label>
                            </p>
                            <p>
                                <input type="checkbox" name="enable_mentioning" id="enable_mentioning" <?php checked(!empty($asgarosforum->options['enable_mentioning'])); ?>>
                                <label for="enable_mentioning"><?php _e('Enable Mentioning', 'asgaros-forum'); ?></label>
                            </p>
                            <p>
                                <input type="checkbox" name="enable_reactions" id="enable_reactions" <?php checked(!empty($asgarosforum->options['enable_reactions'])); ?>>
                                <label for="enable_reactions"><?php _e('Enable reactions', 'asgaros-forum'); ?></label>
                            </p>
                            <p>
                                <input type="checkbox" name="enable_search" id="enable_search" <?php checked(!empty($asgarosforum->options['enable_search'])); ?>>
                                <label for="enable_search"><?php _e('Enable search functionality', 'asgaros-forum'); ?></label>
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
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle dashicons-before dashicons-location-alt"><?php _e('Breadcrumbs', 'asgaros-forum'); ?></h2>
                        <div class="inside">
                            <?php
                            $breadcrumbs_option = checked(!empty($asgarosforum->options['enable_breadcrumbs']), true, false);
                            ?>
                            <p>
                                <input type="checkbox" name="enable_breadcrumbs" id="enable_breadcrumbs" class="show_hide_initiator" data-hide-class="breadcrumbs-option" <?php checked(!empty($asgarosforum->options['enable_breadcrumbs'])); ?>>
                                <label for="enable_breadcrumbs"><?php _e('Enable breadcrumbs', 'asgaros-forum'); ?></label>
                            </p>
                            <p class="breadcrumbs-option" <?php if (!$breadcrumbs_option) { echo 'style="display: none;"'; } ?>>
                                <input type="checkbox" name="breadcrumbs_show_category" id="breadcrumbs_show_category" <?php checked(!empty($asgarosforum->options['breadcrumbs_show_category'])); ?>>
                                <label for="breadcrumbs_show_category"><?php _e('Show category name in breadcrumbs', 'asgaros-forum'); ?></label>
                            </p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle dashicons-before dashicons-email-alt"><?php _e('Subscriptions', 'asgaros-forum'); ?></h2>
                        <div class="inside">
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
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle dashicons-before dashicons-groups"><?php _e('Members List', 'asgaros-forum'); ?></h2>
                        <div class="inside">
                            <?php
                            $membersListOption = checked(!empty($asgarosforum->options['enable_memberslist']), true, false);
                            ?>
                            <p>
                                <input type="checkbox" name="enable_memberslist" id="enable_memberslist" class="show_hide_initiator" data-hide-class="memberslist-option" <?php checked(!empty($asgarosforum->options['enable_memberslist'])); ?>>
                                <label for="enable_memberslist"><?php _e('Enable members list', 'asgaros-forum'); ?></label>
                            </p>
                            <p class="memberslist-option" <?php if (!$membersListOption) { echo 'style="display: none;"'; } ?>>
                                <input type="checkbox" name="memberslist_loggedin_only" id="memberslist_loggedin_only" <?php checked(!empty($asgarosforum->options['memberslist_loggedin_only'])); ?>>
                                <label for="memberslist_loggedin_only"><?php _e('Show members list to logged-in users only', 'asgaros-forum'); ?></label>
                            </p>
                            <p class="memberslist-option" <?php if (!$membersListOption) { echo 'style="display: none;"'; } ?>>
                                <label for="members_per_page"><?php _e('Members per page:', 'asgaros-forum'); ?></label>
                                <input type="number" name="members_per_page" id="members_per_page" value="<?php echo stripslashes($asgarosforum->options['members_per_page']); ?>" size="3" min="1">
                            </p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle dashicons-before dashicons-admin-users"><?php _e('Profiles', 'asgaros-forum'); ?></h2>
                        <div class="inside">
                            <?php
                            $profileOption = checked(!empty($asgarosforum->options['enable_profiles']), true, false);
                            ?>
                            <p>
                                <input type="checkbox" name="enable_profiles" id="enable_profiles" class="show_hide_initiator" data-hide-class="profile-option" <?php checked(!empty($asgarosforum->options['enable_profiles'])); ?>>
                                <label for="enable_profiles"><?php _e('Enable profiles', 'asgaros-forum'); ?></label>
                            </p>
                            <p class="profile-option" <?php if (!$profileOption) { echo 'style="display: none;"'; } ?>>
                                <input type="checkbox" name="hide_profiles_from_guests" id="hide_profiles_from_guests" <?php checked(!empty($asgarosforum->options['hide_profiles_from_guests'])); ?>>
                                <label for="hide_profiles_from_guests"><?php _e('Show profiles to logged-in users only', 'asgaros-forum'); ?></label>
                            </p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle dashicons-before dashicons-media-archive"><?php _e('Uploads', 'asgaros-forum'); ?></h2>
                        <div class="inside">
                            <?php
                            $uploadsOption = checked(!empty($asgarosforum->options['allow_file_uploads']), true, false);
                            ?>
                            <p>
                                <input type="checkbox" name="allow_file_uploads" id="allow_file_uploads" class="show_hide_initiator" data-hide-class="uploads-option" <?php echo $uploadsOption; ?>>
                                <label for="allow_file_uploads"><?php _e('Allow uploads', 'asgaros-forum'); ?></label>
                            </p>
                            <p class="uploads-option" <?php if (!$uploadsOption) { echo 'style="display: none;"'; } ?>>
                                <input type="checkbox" name="uploads_show_thumbnails" id="uploads_show_thumbnails" <?php checked(!empty($asgarosforum->options['uploads_show_thumbnails'])); ?>>
                                <label for="uploads_show_thumbnails"><?php _e('Show thumbnails', 'asgaros-forum'); ?></label>
                            </p>
                            <p class="uploads-option" <?php if (!$uploadsOption) { echo 'style="display: none;"'; } ?>>
                                <input type="checkbox" name="hide_uploads_from_guests" id="hide_uploads_from_guests" <?php checked(!empty($asgarosforum->options['hide_uploads_from_guests'])); ?>>
                                <label for="hide_uploads_from_guests"><?php _e('Show uploaded files to logged-in users only', 'asgaros-forum'); ?></label>
                            </p>
                            <p class="uploads-option" <?php if (!$uploadsOption) { echo 'style="display: none;"'; } ?>>
                                <label for="upload_permission"><?php _e('Who can upload files:', 'asgaros-forum'); ?></label>

                                <select name="upload_permission" id="upload_permission">';
                                    <option value="everyone" <?php if ($asgarosforum->options['upload_permission'] == 'everyone') { echo 'selected="selected"'; } ?>><?php _e('Everyone', 'asgaros-forum'); ?></option>
                                    <option value="loggedin" <?php if ($asgarosforum->options['upload_permission'] == 'loggedin') { echo 'selected="selected"'; } ?>><?php _e('Logged in users only', 'asgaros-forum'); ?></option>
                                    <option value="moderator" <?php if ($asgarosforum->options['upload_permission'] == 'moderator') { echo 'selected="selected"'; } ?>><?php _e('Moderators only', 'asgaros-forum'); ?></option>
                                </select>
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
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle dashicons-before dashicons-warning"><?php _e('Reports', 'asgaros-forum'); ?></h2>
                        <div class="inside">
                            <?php
                            $reportsOption = checked(!empty($asgarosforum->options['reports_enabled']), true, false);
                            ?>
                            <p>
                                <input type="checkbox" name="reports_enabled" id="reports_enabled" class="show_hide_initiator" data-hide-class="reports-option" <?php checked(!empty($asgarosforum->options['reports_enabled'])); ?>>
                                <label for="reports_enabled"><?php _e('Enable reports', 'asgaros-forum'); ?></label>
                            </p>
                            <p class="reports-option" <?php if (!$reportsOption) { echo 'style="display: none;"'; } ?>>
                                <input type="checkbox" name="reports_notifications" id="reports_notifications" <?php checked(!empty($asgarosforum->options['reports_notifications'])); ?>>
                                <label for="reports_notifications"><?php _e('Notify site owner about new reports', 'asgaros-forum'); ?></label>
                            </p>
                        </div>
                    </div>

                    <input type="submit" name="af_options_submit" class="button button-primary" value="<?php _e('Save Settings', 'asgaros-forum'); ?>">
                </form>

            </div>
        </div>
    </div>
</div>
