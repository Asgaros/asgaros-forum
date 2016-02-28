<?php

if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    echo '<div class="info">'.__('You need to login in order to create posts and topics.', 'asgaros-forum').'&nbsp;<a href="'.wp_login_url(get_permalink()).'">&raquo; '.__('Login', 'asgaros-forum').'</a></div>';
}

?>

<div>
    <div class="pages">
        <?php if ($counter_normal > 0) {
            echo $this->pageing($this->table_threads);
        } ?>
    </div>
    <div class="forum-menu"><?php echo $this->forum_menu('forum'); ?></div>
    <div class="clear"></div>
</div>

<?php if ($counter_total > 0) { ?>
    <div class="title-element"><?php echo esc_html(stripslashes($this->get_name($this->current_forum, $this->table_forums))); ?></div>
    <div class="content-element">
        <?php if ($sticky_threads && !$this->current_page) { ?>
            <div class="bright"><?php _e('Sticky Threads', 'asgaros-forum'); ?></div>
            <?php foreach ($sticky_threads as $thread) {
                require('forum-thread.php');
            }

            if ($counter_normal > 0) { ?>
                <div class="bright"></div>
            <?php }
        }

        foreach ($threads as $thread) {
            require('forum-thread.php');
        } ?>
    </div>

    <div>
        <div class="pages">
            <?php if ($counter_normal > 0) {
                echo $this->pageing($this->table_threads);
            } ?>
        </div>
        <div class="forum-menu"><?php echo $this->forum_menu('forum'); ?></div>
        <div class="clear"></div>
    </div>
<?php } else { ?>
    <div class="notice"><?php _e('There are no threads yet!', 'asgaros-forum'); ?></div>
<?php } ?>
