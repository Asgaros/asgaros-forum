<?php

if (!defined('ABSPATH')) exit;

$counter++;
?>
<div class="post-element" id="postid-<?php echo $post->id; ?>">
    <div class="post-author<?php if (AsgarosForumOnline::isUserOnline($post->author_id)) { echo ' user-online'; } ?>">
        <?php
        if ($this->current_view != 'post' && $this->options['highlight_authors'] && ($counter > 1 || $this->current_page > 0) && $topicStarter != 0 && $topicStarter == $post->author_id) {
            echo '<small class="post-author-marker">'.__('Topic Author', 'asgaros-forum').'</small>';
        }

        if ($avatars_available) {
            $avatar_size = apply_filters('asgarosforum_filter_avatar_size', 80);
            echo get_avatar($post->author_id, $avatar_size);
            echo '<br />';
        }
        ?>
        <strong><?php echo apply_filters('asgarosforum_filter_post_username', $this->getUsername($post->author_id), $post->author_id); ?></strong><br />
        <?php

        // Show author posts counter if activated.
        if ($this->options['show_author_posts_counter']) {
            // Only show post-counter for existing users.
            if (get_userdata($post->author_id) != false) {
                $author_posts_i18n = number_format_i18n($post->author_posts);
                echo '<small>'.sprintf(_n('%s Post', '%s Posts', $post->author_posts, 'asgaros-forum'), $author_posts_i18n).'</small>';
            }
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

        echo '<div class="forum-post-header">';
        echo '<div class="forum-post-date">'.$this->format_date($post->date).'</div>';

        if ($this->current_view != 'post') {
            echo $this->showPostMenu($post->id, $post->author_id, $counter);
        }

        echo '<div class="clear"></div>';
        echo '</div>';

        echo '<div id="post-quote-container-'.$post->id.'" style="display: none;"><blockquote><div class="quotetitle">'.__('Quote from', 'asgaros-forum').' '.$this->getUsername($post->author_id).' '.sprintf(__('on %s', 'asgaros-forum'), $this->format_date($post->date)).'</div>'.wpautop(stripslashes($post->text)).'</blockquote><br /></div>';
        global $wp_embed;
        $post_content = make_clickable(wpautop($wp_embed->autoembed(stripslashes($post->text))));

        if ($this->options['allow_shortcodes']) {
            add_filter('strip_shortcodes_tagnames', array('AsgarosForumShortcodes', 'filterShortcodes'), 10, 2);
            $post_content = strip_shortcodes($post_content);
            remove_filter('strip_shortcodes_tagnames', array('AsgarosForumShortcodes', 'filterShortcodes'), 10, 2);

            // Run shortcodes.
            $post_content = do_shortcode($post_content);
        }

        $post_content = apply_filters('asgarosforum_filter_post_content', $post_content, $post->id);
        echo $post_content;
        AsgarosForumUploads::showUploadedFiles($post);
        echo '<div class="post-footer">';
        if ($this->options['show_edit_date'] && (strtotime($post->date_edit) > strtotime($post->date))) {
            // Show who edited a post (when the information exist in the database).
            if ($post->author_edit) {
                echo sprintf(__('Last edited on %s by %s', 'asgaros-forum'), $this->format_date($post->date_edit), $this->getUsername($post->author_edit));
            } else {
                echo sprintf(__('Last edited on %s', 'asgaros-forum'), $this->format_date($post->date_edit));
            }

            echo '&nbsp;&middot;&nbsp;';
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

<?php

// Hook for custom-stuff after first post.
if ($counter == 1) {
    do_action('asgarosforum_after_first_post');
}

?>
