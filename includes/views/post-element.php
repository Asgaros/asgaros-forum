<?php

if (!defined('ABSPATH')) exit;

$counter++;


$highlightClass = '';
if (!empty($_GET['highlight_post']) && $_GET['highlight_post'] == $post->id) {
    $highlightClass = 'highlight-post';
}

$user_data = get_userdata($post->author_id);

?>
<div class="post-element <?php echo $highlightClass; ?>" id="postid-<?php echo $post->id; ?>">
    <div class="post-author<?php if ($this->online->is_user_online($post->author_id)) { echo ' user-online'; } ?>">
        <?php
        if ($this->current_view != 'post' && $this->options['highlight_authors'] && ($counter > 1 || $this->current_page > 0) && $topicStarter != 0 && $topicStarter == $post->author_id) {
            echo '<small class="post-author-marker">'.__('Topic Author', 'asgaros-forum').'</small>';
        }

        if ($avatars_available) {
            $avatar_size = apply_filters('asgarosforum_filter_avatar_size', 100);
            echo get_avatar($post->author_id, $avatar_size);
        }
        ?>
        <strong><?php echo apply_filters('asgarosforum_filter_post_username', $this->getUsername($post->author_id), $post->author_id); ?></strong>
        <?php

        // Condition for content which is only available for existing users.
        if ($user_data != false) {
            $this->mentioning->render_nice_name($post->author_id);

            // Show author posts counter if activated.
            if ($this->options['show_author_posts_counter']) {
                $author_posts_i18n = number_format_i18n($post->author_posts);
                echo '<small class="post-counter">'.sprintf(_n('%s Post', '%s Posts', $post->author_posts, 'asgaros-forum'), $author_posts_i18n).'</small>';
            }
        }

        if (AsgarosForumPermissions::isBanned($post->author_id)) {
            echo '<small class="banned">'.__('Banned', 'asgaros-forum').'</small>';
        }

        // Show usergroups of user.
        $usergroups = AsgarosForumUserGroups::getUserGroupsOfUser($post->author_id, 'all', true);

        if (!empty($usergroups)) {
            foreach ($usergroups as $usergroup) {
                echo AsgarosForumUserGroups::render_usergroup_tag($usergroup);
            }
        }

        do_action('asgarosforum_after_post_author', $post->author_id, $post->author_posts);
        ?>
        <div class="clear"></div>
    </div>
    <div class="post-wrapper">
        <div class="post-message">
            <?php

            echo '<div class="forum-post-header">';
            echo '<div class="forum-post-date">'.$this->format_date($post->date).'</div>';

            if ($this->current_view != 'post') {
                echo $this->show_post_menu($post->id, $post->author_id, $counter, $post->date);
            }

            echo '<div class="clear"></div>';
            echo '</div>';

            echo '<div id="post-quote-container-'.$post->id.'" style="display: none;"><blockquote><div class="quotetitle">'.__('Quote from', 'asgaros-forum').' '.$this->getUsername($post->author_id).' '.sprintf(__('on %s', 'asgaros-forum'), $this->format_date($post->date)).'</div>'.wpautop(stripslashes($post->text)).'</blockquote><br /></div>';
            global $wp_embed;
            $post_content = wpautop($wp_embed->autoembed(stripslashes($post->text)));

            if ($this->options['allow_shortcodes']) {
                add_filter('strip_shortcodes_tagnames', array($this->shortcode, 'filterShortcodes'), 10, 2);
                $post_content = strip_shortcodes($post_content);
                remove_filter('strip_shortcodes_tagnames', array($this->shortcode, 'filterShortcodes'), 10, 2);

                // Run shortcodes.
                $post_content = do_shortcode($post_content);
            }

            $post_content = $this->mentioning->nice_name_to_link($post_content);

            // This function has to be called at last to ensure that we dont break links to mentioned users.
            $post_content = make_clickable($post_content);

            $post_content = apply_filters('asgarosforum_filter_post_content', $post_content, $post->id);

            echo $post_content;
            $this->uploads->show_uploaded_files($post);

            do_action('asgarosforum_after_post_message', $post->author_id, $post->id);

            // Show post footer.
            echo '<div class="post-footer">';
                $this->reactions->render_reactions_area($post->id, $this->current_topic);

                echo '<div class="post-meta">';
                    if ($this->options['show_edit_date'] && (strtotime($post->date_edit) > strtotime($post->date))) {
                        // Show who edited a post (when the information exist in the database).
                        if ($post->author_edit) {
                            echo sprintf(__('Last edited on %s by %s', 'asgaros-forum'), $this->format_date($post->date_edit), $this->getUsername($post->author_edit));
                        } else {
                            echo sprintf(__('Last edited on %s', 'asgaros-forum'), $this->format_date($post->date_edit));
                        }

                        if ($this->current_view != 'post') {
                            echo '&nbsp;&middot;&nbsp;';
                        }
                    }

                    if ($this->current_view != 'post') {
                        // Show report button.
                        $this->reports->render_report_button($post->id, $this->current_topic);

                        echo '<a href="'.$this->get_postlink($this->current_topic, $post->id, ($this->current_page + 1)).'">#'.(($this->options['posts_per_page'] * $this->current_page) + $counter).'</a>';
                    }
                echo '</div>';
            echo '</div>';
            ?>
        </div>

        <?php
        // Show signature.
        if ($this->current_view != 'post' && $this->options['allow_signatures']) {
            $signature = trim(esc_html(get_user_meta($post->author_id, 'asgarosforum_signature', true)));

            if (!empty($signature)) {
                echo '<div class="signature">'.$signature.'</div>';
            }
        }
        ?>
    </div>
</div>

<?php

// Hook for custom-stuff after first post.
if ($counter == 1) {
    do_action('asgarosforum_after_first_post');
}

?>
