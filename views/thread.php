<table class="top_menus">
    <tr>
        <td class='pages'><?php echo $this->pageing($this->table_posts); ?></td>
        <td><?php echo $this->thread_menu();?></td>
    </tr>
</table>

<div class='title-element'><?php echo $this->cut_string($this->get_name($this->current_thread, $this->table_threads), 70) . $meClosed; ?></div>
<div class='content-element thread'>
    <?php
    $counter = 0;
    foreach ($posts as $post) {
        $counter++;
        ?>
        <table id='postid-<?php echo $post->id; ?>'>
            <tr>
                <td colspan='2' class='bright'>
                    <span class='post-data-format'><?php echo $this->format_date($post->date); ?></span>
                    <div class='wpf-meta'><?php echo $this->post_menu($post->id, $post->author_id, $counter); ?></div>
                </td>
            </tr>
            <tr>
                <td class='autorpostbox'>
                    <?php echo get_avatar($post->author_id, 60); ?>
                    <br /><strong><?php echo $this->get_username($post->author_id, true); ?></strong><br />
                    <?php echo __("Posts:", "asgarosforum") . "&nbsp;" . $this->count_userposts($post->author_id); ?>
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
        <td class='pages'><?php echo $this->pageing($this->table_posts); ?></td>
        <td><?php echo $this->thread_menu();?></td>
    </tr>
</table>
