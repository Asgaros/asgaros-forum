<?php

if (!defined('ABSPATH')) exit;

?>
<div>
    <?php
    $pageing = $this->pageing($this->table_posts);
    echo $pageing;
    ?>
    <div class="forum-menu"><?php echo $this->forum_menu('thread');?></div>
    <div class="clear"></div>
</div>

<div class="title-element"><?php echo esc_html($this->cut_string(stripslashes($this->get_name($this->current_thread, $this->table_threads)), 70)).$meClosed; ?></div>
<div class="content-element">
    <?php
    $counter = 0;
    $avatars_available = get_option('show_avatars');
    foreach ($posts as $post) {
        $counter++;
        ?>
        <div class="post" id="postid-<?php echo $post->id; ?>">
            <div class="post-header">
                <div class="post-date"><?php echo $this->format_date($post->date); ?></div>
                <?php echo $this->post_menu($post->id, $post->author_id, $counter); ?>
                <div class="clear"></div>
            </div>
            <div class="post-content">
                <div class="post-author">
                    <?php
                    if ($avatars_available) {
                        echo get_avatar($post->author_id, 60);
                        echo '<br />';
                    }
                    ?>
                    <strong><?php echo apply_filters('asgarosforum_filter_post_username', $this->get_username($post->author_id), $post->author_id); ?></strong><br />
                    <?php
                    // Only show post-counter for existent users.
                    if (get_userdata($post->author_id) != false) {
                        echo '<small>'.sprintf(_n('%s Post', '%s Posts', $post->author_posts, 'asgaros-forum'), $post->author_posts).'</small>';
                    }

                    if (AsgarosForumPermissions::isBanned($post->author_id)) {
                        echo '<br /><small class="banned">'.__('Banned', 'asgaros-forum').'</small>';
                    }

                    do_action('asgarosforum_after_post_author', $post->author_id);
                    ?>
                </div>
                <div class="post-message">
                    <?php
                    $post_content = make_clickable(wpautop($wp_embed->autoembed(stripslashes($post->text))));
                    $post_content = apply_filters('asgarosforum_filter_post_content', $post_content);
                    echo $post_content;
                    AsgarosForumUploads::getFileList($post->id, $post->uploads, true);
                    echo '<div class="post-footer">';
                    if ($this->options['show_edit_date'] && (strtotime($post->date_edit) > strtotime($post->date))) {
                        echo sprintf(__('Last edited on %s', 'asgaros-forum'), $this->format_date($post->date_edit)).'&nbsp;&middot;&nbsp;';
                    }
                    echo '<a href="'.$this->get_postlink($this->current_thread, $post->id, ($this->current_page + 1)).'">#'.(($this->options['posts_per_page'] * $this->current_page) + $counter).'</a>';
                    echo '</div>';
                    do_action('asgarosforum_after_post_message', $post->author_id, $post->id);
                    ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<div>
    <?php echo $pageing; ?>
    <div class="forum-menu"><?php echo $this->forum_menu('thread', false);?></div>
    <div class="clear"></div>
</div>

<?php AsgarosForumNotifications::showSubscriptionLink(); ?>
