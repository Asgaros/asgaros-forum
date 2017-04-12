<?php

if (!defined('ABSPATH')) exit;

$counter++;

echo '<div class="post" id="postid-'.$post->id.'">';
    echo '<div class="post-header">';
        echo '<div class="post-date">'.$this->format_date($post->date).'</div>';

        if ($this->current_view != 'post') {
            echo $this->post_menu($post->id, $post->author_id, $counter);
        }
        ?>
        <div class="clear"></div>
    </div>
    <div class="post-content">
        <div class="post-author<?php if (AsgarosForumOnline::isUserOnline($post->author_id)) { echo ' user-online'; } ?>">
            <?php
            if ($this->current_view != 'post' && $this->options['highlight_authors'] && ($counter > 1 || $this->current_page > 0) && $topicStarter != 0 && $topicStarter == $post->author_id) {
                echo '<small class="post-author-marker">'.__('Topic Author', 'asgaros-forum').'</small>';
            }

            if ($avatars_available) {
                echo get_avatar($post->author_id, 80);
                echo '<br />';
            }
            ?>
            <strong><?php echo apply_filters('asgarosforum_filter_post_username', $this->getUsername($post->author_id), $post->author_id); ?></strong><br />
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
            <div class="clear"></div>
        </div>
        <div class="post-message">
            <?php
            echo '<div id="post-quote-container-'.$post->id.'" style="display: none;"><blockquote><div class="quotetitle">'.__('Quote from', 'asgaros-forum').' '.$this->getUsername($post->author_id).' '.sprintf(__('on %s', 'asgaros-forum'), $this->format_date($post->date)).'</div>'.stripslashes($post->text).'</blockquote><br /></div>';
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
            AsgarosForumUploads::showUploadedFiles($post);
            echo '<div class="post-footer">';
            if ($this->options['show_edit_date'] && (strtotime($post->date_edit) > strtotime($post->date))) {
                echo sprintf(__('Last edited on %s', 'asgaros-forum'), $this->format_date($post->date_edit)).'&nbsp;&middot;&nbsp;';
            }

            if ($this->current_view != 'post') {
                echo '<a href="'.$this->get_postlink($this->current_topic, $post->id, ($this->current_page + 1)).'">#'.(($this->options['posts_per_page'] * $this->current_page) + $counter).'</a>';
            }

            // Show signature.
            if ($this->current_view != 'post' && $this->options['allow_signatures']) {
                $signature = trim(esc_html(get_user_meta($post->author_id, 'asgarosforum_signature', true)));

                if (!empty($signature)) {
                    echo '<div class="signature">'.$signature.'</div>';
                }
            }

            echo '</div>';

            do_action('asgarosforum_after_post_message', $post->author_id, $post->id);
            ?>
        </div>
    </div>
</div>
