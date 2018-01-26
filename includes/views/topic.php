<?php

if (!defined('ABSPATH')) exit;

?>
<div class="pages-and-menu">
    <?php
    $pagination = new AsgarosForumPagination($this);
    $paginationRendering = $pagination->renderPagination($this->tables->posts, $this->current_topic);
    echo $paginationRendering;
    echo $this->showTopicMenu();
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
<?php $this->editor->showEditor('addpost', true); ?>
<div class="pages-and-menu">
    <?php
    echo $paginationRendering;
    echo $this->showTopicMenu(false);
    ?>
    <div class="clear"></div>
</div>

<?php
$this->notifications->show_topic_subscription_link($this->current_topic);
?>
