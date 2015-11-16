<div class="wrap">
    <h2>Mingle Forum - <?php _e('Structure', 'mingle-forum'); ?></h2>
    <?php if ($saved): ?>
        <div class="updated">
            <p><?php _e('Your Forums have been saved.', 'mingle-forum'); ?></p>
        </div>
    <?php endif; ?>
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=mingle-forum-structure'); ?>" class="nav-tab main-nav"><?php _e('Categories', 'mingle-forum'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=mingle-forum-structure&action=forums'); ?>" class="nav-tab main-nav nav-tab-active"><?php _e('Forums', 'mingle-forum'); ?></a>
    </h2>
    <form action="" method="post">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <?php $forums = $mingleforum->get_forums($cat->id); ?>
                <fieldset class="mf_fset">
                    <legend><?php echo stripslashes($cat->name); ?></legend>
                    <ol class="sortable_forums mf_ordered_list" id="sortable-forums-<?php echo $cat->id; ?>">
                        <?php if (!empty($forums)): ?>
                            <?php foreach ($forums as $forum): ?>
                                <li class="ui-state-default">
                                    <input type="hidden" name="mf_forum_id[<?php echo $cat->id; ?>][]" value="<?php echo $forum->id; ?>" />
                                    <label><?php _e('Forum Name:', 'mingle-forum'); ?>&nbsp;<input type="text" name="forum_name[<?php echo $cat->id; ?>][]" value="<?php echo esc_html(stripslashes($forum->name)); ?>" /></label>&nbsp;&nbsp;
                                    <label><?php _e('Description:', 'mingle-forum'); ?>&nbsp;<input type="text" name="forum_description[<?php echo $cat->id; ?>][]" value="<?php echo esc_html(stripslashes($forum->description)); ?>" size="50" /></label>
                                    <a href="#" class="mf_remove_forum" title="<?php _e('Remove this Forum', 'mingle-forum'); ?>">
                                        <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="ui-state-default">
                                <input type="hidden" name="mf_forum_id[<?php echo $cat->id; ?>][]" value="new" />
                                <label><?php _e('Forum Name:', 'mingle-forum'); ?>&nbsp;<input type="text" name="forum_name[<?php echo $cat->id; ?>][]" value="" /></label>&nbsp;&nbsp;
                                <label><?php _e('Description:', 'mingle-forum'); ?>&nbsp;<input type="text" name="forum_description[<?php echo $cat->id; ?>][]" value="" size="50" /></label>
                                <a href="#" class="mf_remove_forum" title="<?php _e('Remove this Forum', 'mingle-forum'); ?>">
                                    <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
                                </a>
                            </li>
                        <?php endif; ?>
                    </ol>
                    <a href="#" class="mf_add_new_forum" title="<?php _e('Add new Forum', 'mingle-forum'); ?>" data-value="<?php echo $cat->id; ?>">
                        <img src="<?php echo WPFURL.'images/add.png'; ?>" width="32" />
                    </a>
                </fieldset>
            <?php endforeach; ?>
            <input type="submit" name="mf_forums_save" value="<?php _e('Save Changes', 'mingle-forum'); ?>" class="mf_admin_submit button" />
        <?php else: ?>
            <p><?php _e('You must add some categories first.', 'mingle-forum'); ?></p>
        <?php endif; ?>
    </form>
</div>
<div id="hidden-element-container">
    <li class="ui-state-default">
        <input type="hidden" name="mf_forum_id[<?php echo $cat->id; ?>][]" value="new" />
        <label><?php _e('Forum Name:', 'mingle-forum'); ?>&nbsp;<input type="text" name="forum_name[<?php echo $cat->id; ?>][]" value="" /></label>&nbsp;&nbsp;
        <label><?php _e('Description:', 'mingle-forum'); ?>&nbsp;<input type="text" name="forum_description[<?php echo $cat->id; ?>][]" value="" size="50" /></label>
        <a href="#" class="mf_remove_forum" title="<?php _e('Remove this Forum', 'mingle-forum'); ?>">
            <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
        </a>
    </li>
</div>
