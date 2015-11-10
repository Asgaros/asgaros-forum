<h3><?php _e('Mingle Forum Moderator Settings', 'mingle-forum'); ?></h3>

<div id="mf_moderator_wrapper">
  <?php if(!empty($categories)): ?>
    <input type="checkbox" name="mf_global_moderator" id="mf_global_moderator" <?php checked(!is_array($mod)); ?> />
    <label for="mf_global_moderator"><?php _e('Make this User a Global Moderator', 'mingle-forum'); ?></label>
    <div id="mf_moderator_not_global">
      <?php foreach($categories as $cat): ?>
        <?php $forums = $mingleforum->get_forums($cat->id); ?>
        <h4><?php echo stripslashes($cat->name); ?></h4>
        <?php if(!empty($forums)): ?>
          <?php foreach($forums as $forum): ?>
            <div class="mf_moderator_forums">
              <input type="checkbox" name="mf_moderator_forum_ids[]" id="mf_moderator_forum_id-<?php echo $forum->id; ?>" value="<?php echo $forum->id; ?>" <?php checked((is_array($mod) && in_array($forum->id, $mod))); ?> />
              <label for="mf_moderator_forum_id-<?php echo $forum->id; ?>"><?php echo stripslashes($forum->name); ?></label>
            </div>
          <?php endforeach; //foreach forums ?>
        <?php endif; //not empty forums ?>
      <?php endforeach; //foreach groups ?>
    </div>
  <?php endif; //not empty groups ?>
</div>
