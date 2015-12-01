<div class="content-element">
    <table>
        <tr>
            <th>Status</th>
            <th><?php _e("Subject", "asgarosforum"); ?></th>
            <th><?php _e("Started by", "asgarosforum"); ?></th>
            <th><?php _e("Posted", "asgarosforum"); ?></th>
        </tr>
        <?php
        foreach ($results as $result) {
            if ($this->have_access($this->forum_get_group_from_post($result->parent_id))) { ?>
                <tr>
                    <td class="status-icon"><?php echo $this->get_topic_image($result->parent_id); ?></td>
                    <td><a href="<?php echo $this->get_threadlink($result->parent_id); ?>"><?php echo stripslashes($result->subject); ?></a></td>
                    <td class="forumstats"><?php echo $this->profile_link($result->author_id); ?></td>
                    <td class="poster_in_forum"><?php echo $this->format_date($result->date); ?></td>
                </tr>
            <?php }
        } ?>
    </table>
</div>
