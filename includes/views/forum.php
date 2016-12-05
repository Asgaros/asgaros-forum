<?php

if (!defined('ABSPATH')) exit;

?>

<div>
    <?php
    $pageing = ($counter_normal > 0) ? $this->pageing($this->tables->topics) : '';
    echo $pageing;
    ?>
    <div class="forum-menu"><?php echo $this->forum_menu('forum'); ?></div>
    <div class="clear"></div>
</div>

<?php
// Subforums
$subforums = $this->get_forums($this->current_category, $this->current_forum);
if (count($subforums) > 0) {
    echo '<div class="title-element">'.__('Subforums', 'asgaros-forum').'</div>';
    echo '<div class="content-element">';
    $elementMarker = '';
    $forumsCounter = 0;
    foreach ($subforums as $forum) {
        $forumsCounter++;
        $elementMarker = ($forumsCounter & 1) ? 'odd' : 'even';
        require('forum-element.php');
    }
    echo '</div>';
}

if ($counter_total > 0) {
    echo '<div class="title-element">'.esc_html(stripslashes($this->get_name($this->current_forum, $this->tables->forums))).'</div>';
    echo '<div class="content-element">';
        // Sticky threads
        if ($sticky_threads && !$this->current_page) { ?>
            <div class="sticky-top"><?php _e('Sticky Threads', 'asgaros-forum'); ?></div>
            <?php
            $elementMarker = '';
            $elementsCounter = 0;
            foreach ($sticky_threads as $thread) {
                $elementsCounter++;
                $elementMarker = ($elementsCounter & 1) ? 'odd' : 'even';
                require('thread-element.php');
            }
        }

        if ($counter_normal > 0 && (($sticky_threads && !$this->current_page))) {
            echo '<div class="sticky-bottom"></div>';
        }

        $elementMarker = '';
        $elementsCounter = 0;
        foreach ($threads as $thread) {
            $elementsCounter++;
            $elementMarker = ($elementsCounter & 1) ? 'odd' : 'even';
            require('thread-element.php');
        } ?>
    </div>

    <div>
        <?php echo $pageing; ?>
        <div class="forum-menu"><?php echo $this->forum_menu('forum'); ?></div>
        <div class="clear"></div>
    </div>
<?php } else {
    echo '<div class="title-element">'.esc_html(stripslashes($this->get_name($this->current_forum, $this->tables->forums))).'</div>';
    echo '<div class="content-element">';
    echo '<div class="notice">'.__('There are no threads yet!', 'asgaros-forum').'</div>';
    echo '</div>';
} ?>
