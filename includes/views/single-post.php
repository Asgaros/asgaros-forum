<?php if (!defined('ABSPATH')) exit; ?>
<div class="title-element"></div>
<div class="content-element">
<div class="post" id="postid-<?php echo $post->id; ?>">
    <div class="post-header">
        <div class="post-date"><?php echo $this->format_date($post->date); ?></div>
        <div class="clear"></div>
    </div>
    <div class="post-content">
        <div class="post-author">
            <?php
            if (get_option('show_avatars')) {
                echo get_avatar($post->author_id, 80);
                echo '<br />';
            }
            ?>
            <strong><?php echo apply_filters('asgarosforum_filter_post_username', $this->get_username($post->author_id), $post->author_id); ?></strong><br />
            <?php
            // Only show post-counter for existent users.
            if (get_userdata($post->author_id) != false) {
                $author_posts_i18n = number_format_i18n($post->author_posts);
                echo '<small>'.sprintf(_n('%s Post', '%s Posts', $post->author_posts, 'asgaros-forum'), $author_posts_i18n).'</small>';
            }

            if (AsgarosForumPermissions::isBanned($post->author_id)) {
                echo '<br /><small class="banned">'.__('Banned', 'asgaros-forum').'</small>';
            }

            do_action('asgarosforum_after_post_author', $post->author_id, $post->author_posts);
            ?>
        </div>
        <div class="post-message">
            <?php
            $post_content = make_clickable(wpautop($wp_embed->autoembed(stripslashes($post->text))));

            if ($this->options['allow_shortcodes']) {
                add_filter('strip_shortcodes_tagnames', array('AsgarosForumShortcodes', 'filterShortcodes'), 10, 2);
                $post_content = strip_shortcodes($post_content);
                remove_filter('strip_shortcodes_tagnames', array('AsgarosForumShortcodes', 'filterShortcodes'), 10, 2);

                // Run shortcodes.
                $post_content = do_shortcode($post_content);
            }

            $post_content = apply_filters('asgarosforum_filter_post_content', $post_content);
            echo $post_content;
            AsgarosForumUploads::getFileList($post);
            echo '<div class="post-footer">';
            if ($this->options['show_edit_date'] && (strtotime($post->date_edit) > strtotime($post->date))) {
                echo sprintf(__('Last edited on %s', 'asgaros-forum'), $this->format_date($post->date_edit)).'&nbsp;&middot;&nbsp;';
            }

            echo '</div>';

            do_action('asgarosforum_after_post_message', $post->author_id, $post->id);
            ?>
        </div>
    </div>
</div>
</div>
