<div class="wrap">
  <h2>Mingle Forum - <?php _e('Structure', 'mingle-forum'); ?></h2>

  <?php if (isset($_GET['saved']) && $_GET['saved'] == 'true'): ?>
    <div id="message" class="updated below-h2">
      <p><?php _e('Your Forums have been saved.', 'mingle-forum'); ?></p>
    </div>
  <?php endif; ?>

  <p><i>* <?php _e('Categories can be thought of as empty boxes. Great for organizing stuff, but no good without something in them. Use categories to organize your various Forums. Say you want a discussion board dedicated to classic sports cars. Then you would create a Category called "Chevrolet" and put Forums inside of it called "Corvette Sting Ray", "Aston Martin DB5", "1969 Camaro", etc.', 'mingle-forum'); ?></i></p>

  <h2 class="nav-tab-wrapper">
    <a href="<?php echo admin_url('admin.php?page=mingle-forum-structure'); ?>" class="nav-tab main-nav"><?php _e('Categories', 'mingle-forum'); ?></a>
    <a href="<?php echo admin_url('admin.php?page=mingle-forum-structure&action=forums'); ?>" class="nav-tab main-nav nav-tab-active"><?php _e('Forums', 'mingle-forum'); ?></a>
  </h2>

  <form action="" method="post">
    <?php if(!empty($categories)): ?>
      <?php foreach($categories as $cat): ?>
        <?php $forums = $mingleforum->get_forums($cat->id); ?>
        <fieldset class="mf_fset">
          <legend><?php echo stripslashes($cat->name); ?></legend>
          <ol class="sortable_forums mf_ordered_list" id="sortable-forums-<?php echo $cat->id; ?>">
            <?php if(!empty($forums)): ?>
              <?php foreach($forums as $forum): ?>
                <li class="ui-state-active">
                  <input type="hidden" name="mf_forum_id[<?php echo $cat->id; ?>][]" value="<?php echo $forum->id; ?>" />
                  &nbsp;&nbsp;
                  <label for="forum-name-<?php echo $forum->id; ?>"><?php _e('Forum Name:', 'mingle-forum'); ?></label>
                  <input type="text" name="forum_name[<?php echo $cat->id; ?>][]" id="forum-name-<?php echo $forum->id; ?>" value="<?php echo htmlentities(stripslashes($forum->name), ENT_QUOTES); ?>" />
                  &nbsp;&nbsp;
                  <label for="forum-description-<?php echo $forum->id; ?>"><?php _e('Description:', 'mingle-forum'); ?></label>
                  <input type="text" name="forum_description[<?php echo $cat->id; ?>][]" id="forum-description-<?php echo $forum->id; ?>" value="<?php echo htmlentities(stripslashes($forum->description), ENT_QUOTES); ?>" size="50" />

                  <a href="#" class="mf_remove_forum" title="<?php _e('Remove this Forum', 'mingle-forum'); ?>">
                    <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
                  </a>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li class="ui-state-active">
                <?php $random_id = rand(1000001, 2000001); ?>
                <input type="hidden" name="mf_forum_id[<?php echo $cat->id; ?>][]" value="new" />
                &nbsp;&nbsp;
                <label for="forum-name-<?php echo $random_id; ?>"><?php _e('Forum Name:', 'mingle-forum'); ?></label>
                <input type="text" name="forum_name[<?php echo $cat->id; ?>][]" id="forum-name-<?php echo $random_id; ?>" value="" />
                &nbsp;&nbsp;
                <label for="forum-description-<?php echo $random_id; ?>"><?php _e('Description:', 'mingle-forum'); ?></label>
                <input type="text" name="forum_description[<?php echo $cat->id; ?>][]" id="forum-description-<?php echo $random_id; ?>" value="" size="50" />

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
      <?php endforeach; //End foreach($categories as $cat) ?>

      <div style="margin-top:15px;">
        <input type="submit" name="mf_forums_save" value="<?php _e('Save Changes', 'mingle-forum'); ?>" class="button" />
      </div>
    
    <?php else: //else !empty($categories) if ?>
      <h3><?php _e('You must add some Categories first.', 'mingle-forum'); ?></h3>
    <?php endif; //end !empty($categories) if ?>
  </form>

</div>
