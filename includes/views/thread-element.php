<?php

if (!defined('ABSPATH')) exit;

?>

<div class="thread <?php echo $elementMarker; ?>">
    <?php $lastpost_data = $this->get_lastpost_in_thread($thread->id); ?>
    <div class="thread-status">
        <?php
        $unreadStatus = AsgarosForumUnread::getStatusThread($thread->id);
        echo '<span class="dashicons-before dashicons-'.$thread->status.$unreadStatus.'"></span>';
        ?>
    </div>
    <div class="thread-name">
        <strong><a href="<?php echo $this->getLink('topic', $thread->id); ?>" title="<?php echo esc_html(stripslashes($thread->name)); ?>"><?php echo esc_html($this->cut_string(stripslashes($thread->name))); ?></a></strong>
        <small><?php _e('Created by:', 'asgaros-forum'); ?> <i><?php echo $this->get_username($this->get_thread_starter($thread->id)); ?></i></small>
    </div>
    <div class="thread-stats">
        <?php $count_answers = (int)($this->db->get_var($this->db->prepare("SELECT COUNT(id) FROM {$this->tables->posts} WHERE parent_id = %d;", $thread->id)) - 1); ?>
        <small><?php echo sprintf(_n('%s Answer', '%s Answers', $count_answers, 'asgaros-forum'), $count_answers); ?></small>
        <small><?php echo sprintf(_n('%s View', '%s Views', (int)$thread->views, 'asgaros-forum'), (int)$thread->views); ?></small>
    </div>
    <div class="thread-poster"><?php echo $this->get_lastpost($lastpost_data, 'thread'); ?></div>
</div>
