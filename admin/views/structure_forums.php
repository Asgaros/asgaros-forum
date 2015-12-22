<div class="wrap">
    <h2><?php _e('Forums', 'asgarosforum'); ?></h2>
    <?php if ($this->saved): ?>
        <div class="updated">
            <p><?php _e('Your Forums have been saved.', 'asgarosforum'); ?></p>
        </div>
    <?php endif; ?>
    <form action="" method="post">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $category): ?>
                <?php $forums = $asgarosforum->get_forums($category->term_id); ?>
                <fieldset class="af_fset">
                    <legend><?php echo stripslashes($category->name); ?></legend>
                    <ol class="sortable_elements af_ordered_list" id="sortable-forums-<?php echo $category->term_id; ?>">
                        <?php if (!empty($forums)): ?>
                            <?php foreach ($forums as $forum): ?>
                                <li class="ui-state-default">
                                    <input type="hidden" name="af_forum_id[<?php echo $category->term_id; ?>][]" value="<?php echo $forum->id; ?>" />
                                    <label><?php _e('Forum Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_name[<?php echo $category->term_id; ?>][]" value="<?php echo esc_html(stripslashes($forum->name)); ?>" /></label>&nbsp;&nbsp;
                                    <label><?php _e('Description:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_description[<?php echo $category->term_id; ?>][]" value="<?php echo esc_html(stripslashes($forum->description)); ?>" /></label>
                                    <a href="#" class="af_remove_forum dashicons-before dashicons-trash" title="<?php _e('Remove this Forum', 'asgarosforum'); ?>"></a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="ui-state-default">
                                <input type="hidden" name="af_forum_id[<?php echo $category->term_id; ?>][]" value="new" />
                                <label><?php _e('Forum Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_name[<?php echo $category->term_id; ?>][]" value="" /></label>&nbsp;&nbsp;
                                <label><?php _e('Description:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_description[<?php echo $category->term_id; ?>][]" value="" /></label>
                                <a href="#" class="af_remove_forum dashicons-before dashicons-trash" title="<?php _e('Remove this Forum', 'asgarosforum'); ?>"></a>
                            </li>
                        <?php endif; ?>
                    </ol>
                    <a href="#" class="af_add_new_forum dashicons-before dashicons-plus" title="<?php _e('Add new Forum', 'asgarosforum'); ?>" data-value="<?php echo $category->term_id; ?>"><?php _e('Add new Forum', 'asgarosforum'); ?></a>
                </fieldset>
            <?php endforeach; ?>
            <input type="submit" name="af_forums_save" value="<?php _e('Save Changes', 'asgarosforum'); ?>" class="af_admin_submit button button-primary" />
        <?php else: ?>
            <p><?php _e('You must add some categories first.', 'asgarosforum'); ?></p>
        <?php endif; ?>
    </form>
</div>
<div id="hidden-element-container">
    <li class="ui-state-default">
        <input type="hidden" name="af_forum_id[][]" value="new" />
        <label><?php _e('Forum Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_name[][]" value="" /></label>&nbsp;&nbsp;
        <label><?php _e('Description:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_description[][]" value="" /></label>
        <a href="#" class="af_remove_forum dashicons-before dashicons-trash" title="<?php _e('Remove this Forum', 'asgarosforum'); ?>"></a>
    </li>
</div>
