<div class="wrap">
    <h2>Mingle Forum - <?php _e('User Groups', 'mingle-forum'); ?></h2>
    <?php if (isset($_GET['saved']) && $_GET['saved'] == 'true'): ?>
        <div class="updated">
            <p><?php _e('Your User Groups have been saved.', 'mingle-forum'); ?></p>
        </div>
    <?php endif; ?>
    <p><i>* <?php _e('By default all users have full access to create Topics and Replies in all Categories. User Groups can help you limit access to certain Categories to only those users who belong to the Group associated with that Category.', 'mingle-forum'); ?></i></p>
    <form action="" method="post">
        <fieldset class="mf_fset">
            <legend><?php _e('Manage User Groups', 'mingle-forum'); ?></legend>
            <ol id="user-groups" class="mf_ordered_list">
                <?php if(!empty($user_groups)): ?>
                    <?php foreach($user_groups as $group): ?>
                        <li class="ui-state-default mf_user_group_li_item">
                            <input type="hidden" name="mf_user_group_id[]" value="<?php echo $group->id; ?>" />
                            <label><?php _e('Name:', 'mingle-forum'); ?>&nbsp;<input type="text" name="user_group_name[]" value="<?php echo htmlentities(stripslashes($group->name), ENT_QUOTES); ?>" /></label>&nbsp;&nbsp;
                            <label><?php _e('Description:', 'mingle-forum'); ?>&nbsp;<input type="text" name="user_group_description[]" value="<?php echo htmlentities(stripslashes($group->description), ENT_QUOTES); ?>" size="40" /></label>&nbsp;&nbsp;
                            <a href="<?php echo admin_url('admin.php?page=mingle-forum-user-groups&action=users&id='.$group->id); ?>" class="button"><?php _e('Manage Users', 'mingle-forum'); ?></a>
                            <a href="#" class="mf_remove_user_group" title="<?php _e('Remove this User Group', 'mingle-forum'); ?>">
                                <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="ui-state-default mf_user_group_li_item">
                        <input type="hidden" name="mf_user_group_id[]" value="new" />
                        <label><?php _e('Name:', 'mingle-forum'); ?>&nbsp;<input type="text" name="user_group_name[]" value="" /></label>&nbsp;&nbsp;
                        <label><?php _e('Description:', 'mingle-forum'); ?>&nbsp;<input type="text" name="user_group_description[]" value="" size="40" /></label>&nbsp;&nbsp;
                        <a href="#" class="mf_remove_user_group" title="<?php _e('Remove this User Group', 'mingle-forum'); ?>">
                            <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
                        </a>
                    </li>
                <?php endif; ?>
            </ol>
            <a href="#" id="mf_add_new_user_group" title="<?php _e('Add new User Group', 'mingle-forum'); ?>">
                <img src="<?php echo WPFURL.'images/add.png'; ?>" width="32" />
            </a>
        </fieldset>
        <input type="submit" name="mf_user_groups_save" value="<?php _e('Save Changes', 'mingle-forum'); ?>" class="mf_admin_submit button" />
    </form>
</div>
<div id="hidden-element-container">
    <li class="ui-state-default mf_user_group_li_item">
        <input type="hidden" name="mf_user_group_id[]" value="new" />
        <label><?php _e('Name:', 'mingle-forum'); ?>&nbsp;<input type="text" name="user_group_name[]" value="" /></label>&nbsp;&nbsp;
        <label><?php _e('Description:', 'mingle-forum'); ?>&nbsp;<input type="text" name="user_group_description[]" value="" size="40" /></label>&nbsp;&nbsp;
        <a href="#" class="mf_remove_user_group" title="<?php _e('Remove this User Group', 'mingle-forum'); ?>">
            <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
        </a>
    </li>
</div>
