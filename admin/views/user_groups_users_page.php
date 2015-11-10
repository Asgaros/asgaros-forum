<div class="wrap">
  <h2>Mingle Forum - <?php _e('Manage User Group Users', 'mingle-forum'); ?></h2>

  <form action="" method="post">
    <br/>
    <input type="checkbox" name="usergroup_users_auto_add" id="auto_add" <?php checked($usergroup->auto_add); ?> />
    <label for="auto_add"><?php _e('Add new Users to this Group automatically'); ?></label>
    <br/><br/>
    <label><?php _e('Add Users to this Group', 'mingle-forum'); ?></label>:<br/>
    <input type="text" name="usergroup_users_add_new" id="usergroup_users_add_new" />
    <br/><br/>
    <input type="submit" name="usergroup_users_save" value="<?php _e('Update', 'mingle-forum'); ?>" class="button" />
  </form>
  
  <h4><?php _e('Users in this Group', 'mingle-forum'); ?>:</h4>
  <?php if(!empty($usergroup_users)): ?>
    <?php foreach($usergroup_users as $u): ?>
      <?php $alt = (isset($alt) && empty($alt))?'usergroup_users_row_alt':''; ?>
      <div class="usergroup_users_row <?php echo $alt; ?>">
        <a href="<?php echo admin_url('admin.php?page=mingle-forum-user-groups&action=deluser&group_id='.$usergroup->id.'&user_id='.$u->user_id); ?>" title="<?php _e('Remove User from this Group', 'mingle-forum'); ?>" onclick="return confirm('<?php _e('Are you sure you want to remove the User from this Group?', 'mingle-forum'); ?>');"><?php _e('X', 'mingle-forum'); ?></a>
        &nbsp;&nbsp;
        <a href="<?php echo admin_url('user-edit.php?user_id='.$u->user_id); ?>" title="<?php _e('View/Edit Profile', 'mingle-forum'); ?>" target="_blank"><?php echo $u->user_login; ?></a>
      </div>
    <?php endforeach; ?>
  <?php else: //!empty $usergroup_users?>
    <h4><?php _e('There are no users in this Group', 'mingle-forum'); ?></h4>
  <?php endif; //!empty $usergroup_users ?>
</div>
