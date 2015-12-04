<table class="top_menus">
    <tr>
        <td class='pages'><?php echo $this->pageing($thread_id, 'post'); ?></td>
        <td><?php echo $this->topic_menu();?></td>
    </tr>
</table>

<div class='title-element'><?php echo $this->cut_string($this->get_name($thread_id, $this->table_threads), 70) . $meClosed; ?></div>
<div class='content-element thread'>
    <?php
    $counter = 0;
    foreach ($posts as $post) {
        $counter++;
        ?>
        <table id='postid-<?php echo $post->id; ?>'>
            <tr>
                <td colspan='2' class='bright'>
                    <span class='post-data-format'><?php echo date_i18n($this->date_format, strtotime($post->date)); ?></span>
                    <div class='wpf-meta'><?php echo $this->get_postmeta($post->id, $post->author_id, $post->parent_id, $counter); ?></div>
                </td>
            </tr>
            <tr>
                <td class='autorpostbox'>
                    <?php echo get_avatar($post->author_id, 60); ?>
                    <br /><strong><?php echo $this->profile_link($post->author_id, true); ?></strong><br />
                    <?php echo __("Posts:", "asgarosforum") . "&nbsp;" . $this->get_userposts_num($post->author_id); ?>
                </td>
                <td>
                    <?php echo stripslashes(make_clickable(wpautop($this->autoembed($post->text)))); ?>
                </td>
            </tr>
        </table>
    <?php } ?>
</div>

<table class="top_menus">
    <tr>
        <td class='pages'><?php echo $this->pageing($thread_id, 'post'); ?></td>
        <td><?php echo $this->topic_menu();?></td>
    </tr>
</table>
