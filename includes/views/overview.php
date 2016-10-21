<?php

if (!defined('ABSPATH')) exit;

$forum_counter = 0;

foreach ($categories as $category) { ?>
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
            <div class="notice"><?php _e('In this category are no forums yet!', 'asgaros-forum'); ?></div>
        <?php } ?>
    </div>
<?php
}

if ($forum_counter > 0) {
    AsgarosForumUnread::showUnreadControls();
}

?>
