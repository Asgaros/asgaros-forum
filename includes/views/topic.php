<?php

if (!defined('ABSPATH')) exit;

$this->polls->render_poll($this->current_topic);
$this->render_sticky_panel();

echo '<div class="pages-and-menu">';
    $paginationRendering = $this->pagination->renderPagination($this->tables->posts, $this->current_topic);
    echo $paginationRendering;
    echo $this->show_topic_menu();
    echo '<div class="clear"></div>';
echo '</div>';

echo '<div class="title-element"></div>';

$counter = 0;
$topicStarter = $this->get_topic_starter($this->current_topic);
foreach ($posts as $post) {
    require('post-element.php');
} ?>
<?php $this->editor->showEditor('addpost', true); ?>
<div class="pages-and-menu">
    <?php
    echo $paginationRendering;
    echo $this->show_topic_menu(false);
    ?>
    <div class="clear"></div>
</div>
