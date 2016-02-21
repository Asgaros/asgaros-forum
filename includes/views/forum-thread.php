<div class="thread">
    <?php $lastpost_data = $this->get_lastpost_data($thread->id, 'p.date, p.author_id', 'p'); ?>
    <div class="thread-status"><?php $this->get_thread_image($lastpost_data, $thread->status); ?></div>
    <div class="thread-name">
        <strong><a href="<?php echo $this->get_link($thread->id, $this->url_thread); ?>"><?php echo $this->cut_string($thread->name); ?></a></strong>
        <small><?php _e('Created by:', 'asgaros-forum'); ?> <i><?php echo $this->get_username($this->get_thread_starter($thread->id)); ?></i></small>
    </div>
    <div class="thread-stats">
        <small><?php _e('Answers', 'asgaros-forum'); ?>: <?php echo (int) ($this->count_elements($thread->id, $this->table_posts) - 1); ?></small>
        <small><?php _e('Views', 'asgaros-forum'); ?>: <?php echo (int) $thread->views; ?></small>
    </div>
    <div class="thread-poster"><?php echo $this->get_lastpost_in_thread($thread->id); ?></div>
</div>
