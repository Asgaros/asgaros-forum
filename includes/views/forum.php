<?php

if (!defined('ABSPATH')) exit;

AsgarosForumEditor::showEditor('addtopic', true);

?>

<div class="pages-and-menu">
    <?php
    $pagination = new AsgarosForumPagination($this);
    $paginationRendering = ($counter_normal > 0) ? $pagination->renderPagination($this->tables->topics) : '';
    echo $paginationRendering;
    echo $this->showForumMenu();
    ?>
    <div class="clear"></div>
</div>

<?php
// Subforums
$subforums = $this->get_forums($this->current_category, $this->current_forum);
if (count($subforums) > 0) {
    echo '<div class="title-element">';
        echo __('Subforums', 'asgaros-forum');
        echo '<span class="last-post-headline">'.__('Last post', 'asgaros-forum').'</span>';
    echo '</div>';
    echo '<div class="content-element">';
    foreach ($subforums as $forum) {
        require('forum-element.php');
    }
    echo '</div>';
}

if ($counter_total > 0) {
    echo '<div class="title-element">';
        echo __('Topics', 'asgaros-forum');
        echo '<span class="last-post-headline">'.__('Last post', 'asgaros-forum').'</span>';
    echo '</div>';
    echo '<div class="content-element">';
        // Sticky topics
        if ($sticky_topics && !$this->current_page) {
            foreach ($sticky_topics as $topic) {
                require('topic-element.php');
            }
        }

        if ($counter_normal > 0 && (($sticky_topics && !$this->current_page))) {
            echo '<div class="sticky-bottom"></div>';
        }

        foreach ($topics as $topic) {
            require('topic-element.php');
        } ?>
    </div>

    <div class="pages-and-menu">
        <?php
        echo $paginationRendering;
        echo $this->showForumMenu();
        ?>
        <div class="clear"></div>
    </div>
<?php } else {
    echo '<div class="title-element">'.esc_html(stripslashes($this->current_forum_name)).'</div>';
    echo '<div class="content-element">';
    echo '<div class="notice">'.__('There are no topics yet!', 'asgaros-forum').'</div>';
    echo '</div>';
}

AsgarosForumNotifications::showForumSubscriptionLink();

?>
