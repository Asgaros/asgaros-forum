<div class='title-element'><?php echo $g->name; ?></div>
<div class='category-content'>
    <table>
        <?php
        $frs = $this->get_forums($g->id);
        if (count($frs) > 0) {
            foreach ($frs as $f) {
                ?>
                <tr>
                <?php
                $image = "no";

                if ($user_ID) {
                    $lpif = $this->last_poster_in_forum($f->id, true);
                    $last_posterid = $this->last_posterid($f->id);

                    if ($last_posterid != $user_ID) {
                        $lp = strtotime($lpif); // date
                        $lv = strtotime($this->last_visit());

                        if ($lv < $lp) {
                            $image = "yes";
                        }
                    }
                }
                ?>
                    <td class="status-icon"><span class="icon-files-empty-big-<?php echo $image; ?>"></span></td>
                    <td><strong><a href="<?php echo $this->get_forumlink($f->id); ?>"><?php echo $f->name; ?></a></strong><br /><?php echo $f->description; ?></td>
                    <td class="forumstats"><?php _e("Topics: ", "asgarosforum"); ?>&nbsp;<?php echo $this->num_threads($f->id); ?><br /><?php _e("Posts: ", "asgarosforum"); ?>&nbsp;<?php echo $this->num_posts_forum($f->id); ?></td>
                    <td class="poster_in_forum"><?php echo $this->last_poster_in_forum($f->id); ?></td>
                </tr>
            <?php
            }
        } else { ?>
            <tr><td class="notice"><?php _e("There are no forums yet!", "asgarosforum"); ?></td></tr>
        <?php } ?>
    </table>
</div>
