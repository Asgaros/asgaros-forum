<div class="wrap">
    <h2>Forum - <?php _e('Manage User Group Users', 'asgarosforum'); ?></h2>
    <?php if ($saved): ?>
        <div class="updated">
            <p><?php _e('Users have been saved.', 'asgarosforum'); ?></p>
        </div>
    <?php endif; ?>
    <form action="" method="post">
        <fieldset class="mf_fset">
            <legend><?php echo esc_html(stripslashes($usergroup->name)); ?></legend>
            <ol id="user-groups" class="mf_ordered_list">
                <?php if (!empty($usergroup_users)): ?>
                    <?php foreach ($usergroup_users as $u): ?>
                        <li class="ui-state-default mf_user_group_li_item">
                            <label><?php echo $u->user_login; ?></label>
                            <a href="<?php echo admin_url('admin.php?page=asgarosforum-user-groups&action=deluser&groupid='.$usergroup->id.'&user_id='.$u->user_id); ?>" class="mf_remove_user_group_user" title="<?php _e('Remove this User', 'asgarosforum'); ?>">
                                <img src="<?php echo WPFURL.'admin/images/remove.png'; ?>" width="24" />
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php _e('There are no users in this Group.', 'asgarosforum'); ?></p>
                <?php endif; ?>
            </ol>
            <p>
                <label><?php _e('Add User to this Group', 'asgarosforum'); ?>:&nbsp;<input type="text" name="usergroup_user_add_new" /></label>&nbsp;
                <input type="submit" name="usergroup_users_save" value="<?php _e('Add', 'asgarosforum'); ?>" class="button" />
            </p>
        </fieldset>
    </form>
</div>
