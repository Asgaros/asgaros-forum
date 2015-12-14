<div class="wrap">
    <h2>Forum - <?php _e('Structure', 'asgarosforum'); ?></h2>
    <?php if ($this->saved): ?>
        <div class="updated">
            <p><?php _e('Your Forums have been saved.', 'asgarosforum'); ?></p>
        </div>
    <?php endif; ?>
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=asgarosforum-structure'); ?>" class="nav-tab main-nav"><?php _e('Categories', 'asgarosforum'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=asgarosforum-structure&action=forums'); ?>" class="nav-tab main-nav nav-tab-active"><?php _e('Forums', 'asgarosforum'); ?></a>
    </h2>
    <form action="" method="post">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <?php $forums = $asgarosforum->get_forums($cat->id); ?>
                <fieldset class="mf_fset">
                    <legend><?php echo stripslashes($cat->name); ?></legend>
                    <ol class="sortable_elements mf_ordered_list" id="sortable-forums-<?php echo $cat->id; ?>">
                        <?php if (!empty($forums)): ?>
                            <?php foreach ($forums as $forum): ?>
                                <li class="ui-state-default">
                                    <input type="hidden" name="mf_forum_id[<?php echo $cat->id; ?>][]" value="<?php echo $forum->id; ?>" />
                                    <label><?php _e('Forum Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_name[<?php echo $cat->id; ?>][]" value="<?php echo esc_html(stripslashes($forum->name)); ?>" /></label>&nbsp;&nbsp;
                                    <label><?php _e('Description:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_description[<?php echo $cat->id; ?>][]" value="<?php echo esc_html(stripslashes($forum->description)); ?>" /></label>
                                    <a href="#" class="mf_remove_forum" title="<?php _e('Remove this Forum', 'asgarosforum'); ?>">
                                        <img src="<?php echo WPAFURL.'admin/images/remove.png'; ?>" width="24" />
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="ui-state-default">
                                <input type="hidden" name="mf_forum_id[<?php echo $cat->id; ?>][]" value="new" />
                                <label><?php _e('Forum Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_name[<?php echo $cat->id; ?>][]" value="" /></label>&nbsp;&nbsp;
                                <label><?php _e('Description:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_description[<?php echo $cat->id; ?>][]" value="" /></label>
                                <a href="#" class="mf_remove_forum" title="<?php _e('Remove this Forum', 'asgarosforum'); ?>">
                                    <img src="<?php echo WPAFURL.'admin/images/remove.png'; ?>" width="24" />
                                </a>
                            </li>
                        <?php endif; ?>
                    </ol>
                    <a href="#" class="mf_add_new_forum" title="<?php _e('Add new Forum', 'asgarosforum'); ?>" data-value="<?php echo $cat->id; ?>">
                        <img src="<?php echo WPAFURL.'admin/images/add.png'; ?>" width="32" />
                    </a>
                </fieldset>
            <?php endforeach; ?>
            <input type="submit" name="mf_forums_save" value="<?php _e('Save Changes', 'asgarosforum'); ?>" class="mf_admin_submit button button-primary" />
        <?php else: ?>
            <p><?php _e('You must add some categories first.', 'asgarosforum'); ?></p>
        <?php endif; ?>
    </form>
</div>
<div id="hidden-element-container">
    <li class="ui-state-default">
        <input type="hidden" name="mf_forum_id[][]" value="new" />
        <label><?php _e('Forum Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_name[][]" value="" /></label>&nbsp;&nbsp;
        <label><?php _e('Description:', 'asgarosforum'); ?>&nbsp;<input type="text" name="forum_description[][]" value="" /></label>
        <a href="#" class="mf_remove_forum" title="<?php _e('Remove this Forum', 'asgarosforum'); ?>">
            <img src="<?php echo WPAFURL.'admin/images/remove.png'; ?>" width="24" />
        </a>
    </li>
</div>
