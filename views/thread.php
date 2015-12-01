<table class="top_menus">
    <tr>
        <td class='pages'><?php echo $this->pageing($thread_id, 'post'); ?></td>
        <td><?php echo $this->topic_menu();?></td>
    </tr>
</table>

<div class='title-element'><?php echo $this->cut_string($this->get_subject($thread_id), 70) . $meClosed; ?></div>
<div class='content-element'>
    <?php
    $counter = 0;
    foreach ($posts as $post) {
        $counter++;
        ?>
        <table id='postid-<?php echo $post->id; ?>'>
            <tr>
                <td colspan='2' class='bright'>
                    <span class='post-data-format'><?php echo date_i18n($this->dateFormat, strtotime($post->date)); ?></span>
                    <div class='wpf-meta'><?php echo $this->get_postmeta($post->id, $post->author_id, $post->parent_id, $counter); ?></div>
                </td>
            </tr>
            <tr>
                <td class='autorpostbox'>
                    <?php if ($this->options["forum_use_gravatar"]) {
                        echo $this->get_avatar($post->author_id);
                    } ?>
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

<?php if ((!$this->is_closed() || $this->is_moderator($user_ID)) && ($user_ID || $this->allow_unreg())) { ?>
    <div id='thread-reply'>
        <form name='addform' method='post'>
            <strong><?php echo __("Quick Reply", "asgarosforum"); ?>:</strong><br />
            <?php wp_editor('', 'message', $this->editor_settings); ?>
            <br /><?php echo $this->get_captcha(); ?><br />
            <input type='submit' name='add_post_submit' value='<?php _e("Submit Quick Reply", "asgarosforum"); ?>' />
            <input type='hidden' name='add_post_forumid' value='<?php echo floor($quick_thread); ?>'/>
        </form>
    </div>
<?php } ?>

<table class="top_menus">
    <tr>
        <td class='pages'><?php echo $this->pageing($thread_id, 'post'); ?></td>
        <td><?php echo $this->topic_menu();?></td>
    </tr>
</table>
