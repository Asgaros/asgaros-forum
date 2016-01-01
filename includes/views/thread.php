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
                <div class="post-date"><?php echo $this->format_date($post->date); ?></div>
                <div class="post-menu"><?php echo $this->post_menu($post->id, $post->author_id, $counter); ?></div>
            </div>
            <div class="post-content">
                <div class="post-author">
                    <?php echo get_avatar($post->author_id, 60); ?>
                    <br /><strong><?php echo $this->get_username($post->author_id, true); ?></strong><br />
                    <small><?php echo __('Posts:', 'asgaros-forum').'&nbsp;'.$this->count_userposts($post->author_id); ?></small>
                </div>
                <div class="post-message">
                    <?php echo stripslashes(make_clickable(wpautop($wp_embed->autoembed($post->text)))); ?>
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
