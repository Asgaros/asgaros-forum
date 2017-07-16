<?php

if (!defined('ABSPATH')) exit;

?>

<div class="thread">
    <?php $lastpost_data = $this->get_lastpost_in_topic($thread->id); ?>
    <div class="topic-status">
        <?php
        $unreadStatus = AsgarosForumUnread::getStatusTopic($thread->id);
        echo '<span class="dashicons-before dashicons-'.$thread->status.' '.$unreadStatus.'"></span>';
        ?>
    </div>
    <div class="topic-name">
        <strong><a href="<?php echo $this->getLink('topic', $thread->id); ?>" title="<?php echo esc_html(stripslashes($thread->name)); ?>"><?php echo esc_html(stripslashes($thread->name)); ?></a></strong>
        <small><?php echo __('By', 'asgaros-forum').'&nbsp;<b>'.$this->getUsername($thread->author_id); ?></b></small>
    </div>
    <?php do_action('asgarosforum_custom_topic_column', $thread->id); ?>
    <div class="topic-stats">
        <?php
        $count_answers_i18n = number_format_i18n($thread->answers);
        $count_views_i18n = number_format_i18n($thread->views);
        echo sprintf(_n('%s Answer', '%s Answers', $thread->answers, 'asgaros-forum'), $count_answers_i18n).'<br>';
        echo sprintf(_n('%s View', '%s Views', $thread->views, 'asgaros-forum'), $count_views_i18n);
        ?>
    </div>
    <div class="topic-poster"><?php echo $this->get_lastpost($lastpost_data, 'thread'); ?></div>
</div>
