<?php

if (!defined('ABSPATH')) exit;

echo '<h1 class="main-title">'.esc_html(stripslashes($this->get_name($this->current_topic, $this->tables->topics))).'</h1>';

?>
<div class="pages-and-menu">
    <?php
    $pageing = $this->pageing($this->tables->posts);
    echo $pageing;
    ?>
    <div class="forum-menu"><?php echo $this->forum_menu('thread');?></div>
    <div class="clear"></div>
</div>

<div class="title-element"><?php echo $meClosed; ?></div>
<div class="content-element">
    <?php
    $counter = 0;
    $avatars_available = get_option('show_avatars');
    $threadStarter = $this->get_topic_starter($this->current_topic);
    foreach ($posts as $post) {
        require('post-element.php');
    } ?>
</div>
<?php AsgarosForumEditor::showEditor('addpost', true); ?>
<div class="pages-and-menu">
    <?php echo $pageing; ?>
    <div class="forum-menu"><?php echo $this->forum_menu('thread', false); ?></div>
    <div class="clear"></div>
</div>

<?php
AsgarosForumNotifications::showTopicSubscriptionLink();
?>
