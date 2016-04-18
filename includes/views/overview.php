<?php

if (!defined('ABSPATH')) exit;

$forum_counter = 0;

?>
<?php foreach ($categories as $category) { ?>
    <div class="title-element" id="forum-category-<?php echo $category->term_id; ?>"><?php echo $category->name; ?></div>
    <div class="content-element space">
        <?php
        $frs = $this->get_forums($category->term_id);
        if (count($frs) > 0) {
            foreach ($frs as $forum) {
                $forum_counter++;
                require('forum-element.php');
            }
        } else { ?>
            <div class="notice"><?php _e('There are no forums yet!', 'asgaros-forum'); ?></div>
        <?php } ?>
    </div>
<?php } ?>
<?php if ($forum_counter > 0) { ?>
<div class="footer">
    <span class="dashicons-before dashicons-admin-page-small unread"></span><?php _e('New posts', 'asgaros-forum'); ?> &middot;
    <span class="dashicons-before dashicons-admin-page-small"></span><?php _e('No new posts', 'asgaros-forum'); ?> &middot;
    <span class="dashicons-before dashicons-yes"></span><a href="<?php echo $this->url_markallread; ?>"><?php _e('Mark All Read', 'asgaros-forum'); ?></a>
</div>
<?php } ?>
