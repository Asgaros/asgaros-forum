<?php

if (!defined('ABSPATH')) exit;

?>
<div class="pages-and-menu">
    <?php
    $pagination = new AsgarosForumPagination($this);
    $paginationRendering = $pagination->renderPagination($this->tables->posts);
    echo $paginationRendering;
    echo $this->forum_menu('topic');
    ?>
    <div class="clear"></div>
</div>

<div class="title-element"><?php echo $meClosed; ?></div>
<div class="content-element">
    <?php
    $counter = 0;
    $avatars_available = get_option('show_avatars');
    $topicStarter = $this->get_topic_starter($this->current_topic);
    foreach ($posts as $post) {
        require('post-element.php');
    } ?>
</div>
<?php AsgarosForumEditor::showEditor('addpost', true); ?>
<div class="pages-and-menu">
    <?php
    echo $paginationRendering;
    echo $this->forum_menu('topic', false);
    ?>
    <div class="clear"></div>
</div>

<?php
AsgarosForumNotifications::showTopicSubscriptionLink();
?>
