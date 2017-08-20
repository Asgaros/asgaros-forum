<?php

if (!defined('ABSPATH')) exit;

?>

<div class="topic">
    <?php $lastpost_data = $this->get_lastpost_in_topic($topic->id); ?>
    <div class="topic-status">
        <?php
        $unreadStatus = AsgarosForumUnread::getStatusTopic($topic->id);
        echo '<span class="dashicons-before dashicons-'.$topic->status.' '.$unreadStatus.'"></span>';
        ?>
    </div>
    <div class="topic-name">
        <a href="<?php echo $this->getLink('topic', $topic->id); ?>" title="<?php echo esc_html(stripslashes($topic->name)); ?>"><?php echo esc_html(stripslashes($topic->name)); ?></a>
        <small><?php echo __('By', 'asgaros-forum').'&nbsp;'.$this->getUsername($topic->author_id); ?></small>
    </div>
    <?php do_action('asgarosforum_custom_topic_column', $topic->id); ?>
    <div class="topic-stats">
        <?php
        $count_answers_i18n = number_format_i18n($topic->answers);
        $count_views_i18n = number_format_i18n($topic->views);
        echo sprintf(_n('%s Answer', '%s Answers', $topic->answers, 'asgaros-forum'), $count_answers_i18n).'<br>';
        echo sprintf(_n('%s View', '%s Views', $topic->views, 'asgaros-forum'), $count_views_i18n);
        ?>
    </div>
    <div class="topic-poster"><?php echo $this->get_lastpost($lastpost_data, 'thread'); ?></div>
</div>
