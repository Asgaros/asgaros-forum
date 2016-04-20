<?php if (!defined('ABSPATH')) exit; ?>
<?php add_thickbox(); ?>

<div class="wrap" id="af-forums">
    <h2><?php _e('Forums', 'asgaros-forum'); ?></h2>
    <?php if ($this->saved) { ?>
        <div class="updated">
            <p><?php _e('Forum structure updated.', 'asgaros-forum'); ?></p>
        </div>
    <?php
    }

    if (!empty($categories)) {
        foreach ($categories as $category) { ?>
            <h3><?php echo stripslashes($category->name); ?></h3>
            <?php
            $forums = $asgarosforum->get_forums($category->term_id);
            foreach ($forums as $forum) { ?>
                <div class="forum">
                    <input type="hidden" id="forum_<?php echo $forum->id; ?>_name" value="<?php echo esc_html(stripslashes($forum->name)); ?>">
                    <input type="hidden" id="forum_<?php echo $forum->id; ?>_description" value="<?php echo esc_html(stripslashes($forum->description)); ?>">
                    <input type="hidden" id="forum_<?php echo $forum->id; ?>_closed" value="<?php echo esc_html(stripslashes($forum->closed)); ?>">
                    <input type="hidden" id="forum_<?php echo $forum->id; ?>_order" value="<?php echo esc_html(stripslashes($forum->sort)); ?>">
                    <span class="forum-name">
                        <?php
                        if ($forum->closed == 1) {
                            echo '<span class="dashicons-before dashicons-lock"></span>&nbsp;';
                        }
                        echo '<b>'.esc_html(stripslashes($forum->name)).'</b>';
                        ?>
                    </span>
                    <span class="forum-order"><?php echo __('Order:', 'asgaros-forum').'&nbsp;'.esc_html(stripslashes($forum->sort)); ?></span>
                    <span class="forum-actions">
                        <a data-value-id="<?php echo $forum->id; ?>" data-value-category="<?php echo $category->term_id; ?>" href="#TB_inline&amp;width=500&amp;height=130&amp;inlineId=forum-delete" class="forum-delete-link delete thickbox" title="<?php _e('Delete this forum', 'asgaros-forum'); ?>"><?php _e('Delete', 'asgaros-forum'); ?></a>&nbsp;&middot;&nbsp;
                        <a data-value-id="<?php echo $forum->id; ?>" data-value-category="<?php echo $category->term_id; ?>" href="#TB_inline&amp;width=500&amp;height=235&amp;inlineId=forum-editor" class="forum-editor-link thickbox" title="<?php _e('Edit forum', 'asgaros-forum'); ?>" data-value-parent-forum="<?php echo $forum->parent_forum; ?>"><?php _e('Edit forum', 'asgaros-forum'); ?></a>&nbsp;&middot;&nbsp;
                        <a data-value-id="new" data-value-category="<?php echo $category->term_id; ?>" href="#TB_inline&amp;width=500&amp;height=235&amp;inlineId=forum-editor" class="forum-editor-link thickbox" title="<?php _e('Add new sub-forum', 'asgaros-forum'); ?>" data-value-parent-forum="<?php echo $forum->id; ?>"><?php _e('Add new sub-forum', 'asgaros-forum'); ?></a>
                    </span>
                </div>
                <?php
                if ($forum->count_subforums > 0) {
                    $subforums = $asgarosforum->get_forums($category->term_id, $forum->id);

                    foreach ($subforums as $subforum) { ?>
                        <div class="forum">
                            <input type="hidden" id="forum_<?php echo $subforum->id; ?>_name" value="<?php echo esc_html(stripslashes($subforum->name)); ?>">
                            <input type="hidden" id="forum_<?php echo $subforum->id; ?>_description" value="<?php echo esc_html(stripslashes($subforum->description)); ?>">
                            <input type="hidden" id="forum_<?php echo $subforum->id; ?>_closed" value="<?php echo esc_html(stripslashes($subforum->closed)); ?>">
                            <input type="hidden" id="forum_<?php echo $subforum->id; ?>_order" value="<?php echo esc_html(stripslashes($subforum->sort)); ?>">
                            <span class="forum-name">
                                <span class="subforum"></span>
                                <?php
                                if ($subforum->closed == 1) {
                                    echo '<span class="dashicons-before dashicons-lock"></span>&nbsp;';
                                }
                                echo '<b>'.esc_html(stripslashes($subforum->name)).'</b>';
                                ?>
                            </span>
                            <span class="forum-order"><?php echo __('Order:', 'asgaros-forum').'&nbsp;'.esc_html(stripslashes($subforum->sort)); ?></span>
                            <span class="forum-actions">
                                <a data-value-id="<?php echo $subforum->id; ?>" data-value-category="<?php echo $category->term_id; ?>" href="#TB_inline&amp;width=500&amp;height=130&amp;inlineId=forum-delete" class="forum-delete-link delete thickbox" title="<?php _e('Delete this forum', 'asgaros-forum'); ?>"><?php _e('Delete', 'asgaros-forum'); ?></a>&nbsp;&middot;&nbsp;
                                <a data-value-id="<?php echo $subforum->id; ?>" data-value-category="<?php echo $category->term_id; ?>" href="#TB_inline&amp;width=500&amp;height=235&amp;inlineId=forum-editor" class="forum-editor-link thickbox" title="<?php _e('Edit forum', 'asgaros-forum'); ?>" data-value-parent-forum="<?php echo $subforum->parent_forum; ?>"><?php _e('Edit forum', 'asgaros-forum'); ?></a>
                            </span>
                        </div>
                    <?php
                    }
                }
            } ?>
            <a href="#TB_inline&amp;width=500&amp;height=235&amp;inlineId=forum-editor" class="forum-editor-link dashicons-before dashicons-plus thickbox" title="<?php _e('Add new forum', 'asgaros-forum'); ?>" data-value-id="new" data-value-category="<?php echo $category->term_id; ?>" data-value-parent-forum="0"><?php _e('Add new forum', 'asgaros-forum'); ?></a><br />
        <?php
        }
    } else {
        echo '<p>'.__('You must add some categories first.', 'asgaros-forum').'</p>';
    } ?>
    <div id="forum-editor" style="display: none;">
        <form id="add-edit-forum-form" method="post">
            <input type="hidden" name="forum_id" value="new">
            <input type="hidden" name="forum_category" value="0">
            <input type="hidden" name="forum_parent_forum" value="0">
            <input type="hidden" id="forum_new_name" value="">
            <input type="hidden" id="forum_new_description" value="">
            <input type="hidden" id="forum_new_closed" value="">
            <input type="hidden" id="forum_new_order" value="1">

            <table class="form-table">
                <tr>
                    <th><label for="forum_name"><?php _e('Name:', 'asgaros-forum'); ?></label></th>
                    <td><input type="text" id="forum_name" name="forum_name" value="" required></td>
                </tr>
                <tr>
                    <th><label for="forum_description"><?php _e('Description:', 'asgaros-forum'); ?></label></th>
                    <td><input type="text" id="forum_description" name="forum_description" value=""></td>
                </tr>
                <tr>
                    <th><label for="forum_closed"><?php _e('Closed:', 'asgaros-forum'); ?></label></th>
                    <td><input type="checkbox" id="forum_closed" name="forum_closed"></td>
                </tr>
                <tr>
                    <th><label for="forum_order"><?php _e('Order:', 'asgaros-forum'); ?></label></th>
                    <td><input type="number" id="forum_order" name="forum_order" value=""></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="af-create-edit-forum-submit" value="<?php _e('Save', 'asgaros-forum'); ?>" class="button button-primary">
                <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
            </p>
        </form>
    </div>

    <div id="forum-delete" style="display: none;">
        <form id="delete-forum-form" method="post">
            <input type="hidden" name="forum-id" value="0">
            <input type="hidden" name="forum-category" value="0">
            <p><?php _e('Deleting this forum will also permanently delete all sub-forums, threads and replies associated with it. Are you sure you want to delete this forum?', 'asgaros-forum'); ?></p>

            <p class="submit">
                <input type="submit" name="asgaros-forum-delete-forum" value="<?php _e('Delete', 'asgaros-forum'); ?>" class="button button-primary">
                <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
            </p>
        </form>
    </div>
</div>
