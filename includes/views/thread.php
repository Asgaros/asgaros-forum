<?php

if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    echo '<div class="info">'.__('You need to login in order to create posts and topics.', 'asgaros-forum').'&nbsp;<a href="'.wp_login_url(get_permalink()).'">&raquo; '.__('Login', 'asgaros-forum').'</a></div>';
}

?>
<div>
    <div class="pages"><?php echo $this->pageing($this->table_posts); ?></div>
    <div class="forum-menu"><?php echo $this->forum_menu('thread');?></div>
    <div class="clear"></div>
</div>

<div class="title-element"><?php echo $this->cut_string($this->get_name($this->current_thread, $this->table_threads), 70) . $meClosed; ?></div>
<div class="content-element">
    <?php
    $counter = 0;
    foreach ($posts as $post) {
        $counter++;
        ?>
        <div class="post" id="postid-<?php echo $post->id; ?>">
            <div class="post-header">
                <div class="post-date"><a href="<?php echo $this->get_postlink($this->current_thread, $post->id, ($this->current_page + 1)); ?>"><?php echo $this->format_date($post->date); ?></a></div>
                <?php echo $this->post_menu($post->id, $post->author_id, $counter); ?>
                <div class="clear"></div>
            </div>
            <div class="post-content">
                <div class="post-author">
                    <?php echo get_avatar($post->author_id, 60); ?>
                    <br /><strong><?php echo $this->get_username($post->author_id, true); ?></strong><br />
                    <small><?php echo __('Posts:', 'asgaros-forum').'&nbsp;'.$this->count_userposts($post->author_id); ?></small>
                    <?php do_action('asgarosforum_action_after_post_author', $post->author_id); ?>
                </div>
                <div class="post-message">
                    <?php echo make_clickable(wpautop($wp_embed->autoembed(stripslashes($post->text)))); ?>
                    <?php $this->file_list($post->id, $post->uploads, true); ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<div>
    <div class="pages"><?php echo $this->pageing($this->table_posts); ?></div>
    <div class="forum-menu"><?php echo $this->forum_menu('thread');?></div>
    <div class="clear"></div>
</div>
