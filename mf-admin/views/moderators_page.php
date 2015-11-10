<div class="wrap">
  <h2>
    Mingle Forum - <?php _e('Moderators', 'mingle-forum'); ?>
    <a href="#" id="mf_add_new_moderator" class="add-new-h2"><?php _e('Add New', 'mingle-forum'); ?></a>
  </h2>

  <div id="mf_hidden_moderator_instructions">
    <em>
      <?php _e('To add a moderator, browse to their WordPress', 'mingle-forum'); ?>
      <a href="<?php echo admin_url('users.php'); ?>" target="_blank"><strong><?php _e('user profile', 'mingle-forum'); ?></strong></a>
      <?php _e('and mangage which forums they have moderator priviledges over from there.', 'mingle-forum'); ?>
    </em>
  </div>

  <fieldset class="mf_fset">
    <legend><?php _e('Existing Moderators', 'mingle-forum'); ?></legend>
    <?php if(!empty($moderators)): ?>
      <?php foreach($moderators as $moderator): ?>
        <h3>
          <?php _e('Forums for', 'mingle-forum'); ?>
          <a href="<?php echo admin_url('user-edit.php?user_id='.$moderator->user_id); ?>" title="<?php _e('Edit User', 'mingle-forum'); ?>"><?php echo $moderator->user_login; ?></a>
        </h3>
        <span class="mf_moderators_forums">
          <?php $forum_ids = maybe_unserialize($moderator->meta_value); ?>
          <?php if(is_array($forum_ids) && !empty($forum_ids)): ?>
            <?php foreach($forum_ids as $forum_id): ?>
              <?php echo stripslashes($mingleforum->get_forumname($forum_id)); ?>, 
            <?php endforeach; //foreach forum_ids ?>
          <?php else: //is array forum_ids ?>
            <?php _e('Global Moderator', 'mingle-forum'); ?>
          <?php endif; //is array forum_ids ?>
        </span>
      <?php endforeach; ?>
    <?php else: //not empty mods ?>
      <h3><?php _e('No Moderators have been created', 'mingle-forum'); ?></h3>
    <?php endif; //not empty mods ?>
  </fieldset>

</div>
