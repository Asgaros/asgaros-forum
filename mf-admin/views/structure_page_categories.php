<div class="wrap">
  <h2>Mingle Forum - <?php _e('Structure', 'mingle-forum'); ?></h2>

  <?php if (isset($_GET['saved']) && $_GET['saved'] == 'true'): ?>
    <div id="message" class="updated below-h2">
      <p><?php _e('Your Categories have been saved.', 'mingle-forum'); ?></p>
    </div>
  <?php endif; ?>

  <p><i>* <?php _e('Categories can be thought of as empty boxes. Great for organizing stuff, but no good without something in them. Use categories to organize your various Forums. Say you want a discussion board dedicated to classic sports cars. Then you would create a Category called "Chevrolet" and put Forums inside of it called "Corvette Sting Ray", "Aston Martin DB5", "1969 Camaro", etc.', 'mingle-forum'); ?></i></p>

  <h2 class="nav-tab-wrapper">
    <a href="<?php echo admin_url('admin.php?page=mingle-forum-structure'); ?>" class="nav-tab main-nav nav-tab-active"><?php _e('Categories', 'mingle-forum'); ?></a>
    <a href="<?php echo admin_url('admin.php?page=mingle-forum-structure&action=forums'); ?>" class="nav-tab main-nav"><?php _e('Forums', 'mingle-forum'); ?></a>
  </h2>

  <form action="" method="post">
    <fieldset class="mf_fset">
      <legend><?php _e('Manage Categories', 'mingle-forum'); ?></legend>
      <ol id="sortable-categories" class="mf_ordered_list">
        <?php if(!empty($categories)): ?>
          <?php foreach($categories as $cat): ?>
            <li class="ui-state-default">
              <input type="hidden" name="mf_category_id[]" value="<?php echo $cat->id; ?>" />
              &nbsp;&nbsp;
              <label for="category-name-<?php echo $cat->id; ?>"><?php _e('Category Name:', 'mingle-forum'); ?></label>
              <input type="text" name="category_name[]" id="category-name-<?php echo $cat->id; ?>" value="<?php echo htmlentities(stripslashes($cat->name), ENT_QUOTES); ?>" />
              &nbsp;&nbsp;
              <label for="category-description-<?php echo $cat->id; ?>"><?php _e('Description:', 'mingle-forum'); ?></label>
              <input type="text" name="category_description[]" id="category-description-<?php echo $cat->id; ?>" value="<?php echo htmlentities(stripslashes($cat->description), ENT_QUOTES); ?>" size="50" />
              <a href="#" class="button access_control" data-value="<?php echo $cat->id; ?>" title="<?php echo __('Category ID', 'mingle-forum') . ' = ' . $cat->id; ?>"><?php _e('Limit Access', 'mingle-forum'); ?></a>

              <a href="#" class="mf_remove_category" title="<?php _e('Remove this Category', 'mingle-forum'); ?>">
                <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
              </a>
              <!-- USERGROUPS SHIZZLE -->
              <div id="user-groups-<?php echo $cat->id; ?>" class="user-groups-area">
                <?php $allusergroups = $mingleforum->get_usergroups(); ?>
                <?php $my_usergroups = (array)maybe_unserialize($cat->usergroups); ?>
                <?php if(!empty($allusergroups)): ?>
                  <label><?php _e('Usergroups with access', 'mingle-forum'); ?>:</label>
                  <?php foreach($allusergroups as $usergroup): ?>
                    <input type="checkbox" name="category_usergroups_<?php echo $cat->id; ?>[]" id="category_usergroups_<?php echo $cat->id.'_'.$usergroup->id; ?>" value="<?php echo $usergroup->id; ?>" <?php checked((in_array($usergroup->id, $my_usergroups))); ?> />
                    <label for="category_usergroups_<?php echo $cat->id.'_'.$usergroup->id; ?>"><?php echo stripslashes($usergroup->name); ?></label>&nbsp;&nbsp;
                  <?php endforeach; ?>
                <?php else: ?>
                <?php endif; ?>
              </div>
              <!-- /USERGROUPS SHIZZLE -->
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="ui-state-default">
            <input type="hidden" name="mf_category_id[]" value="new" />
            &nbsp;&nbsp;
            <label for="category-name-9999999"><?php _e('Category Name:', 'mingle-forum'); ?></label>
            <input type="text" name="category_name[]" id="category-name-9999999" value="" />
            &nbsp;&nbsp;
            <label for="category-description-9999999"><?php _e('Description:', 'mingle-forum'); ?></label>
            <input type="text" name="category_description[]" id="category-description-9999999" value="" size="50" />

            <a href="#" class="mf_remove_category" title="<?php _e('Remove this Category', 'mingle-forum'); ?>">
              <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
            </a>
          </li>
        <?php endif; ?>
      </ol>

      <a href="#" id="mf_add_new_category" title="<?php _e('Add new Category', 'mingle-forum'); ?>">
        <img src="<?php echo WPFURL.'images/add.png'; ?>" width="32" />
      </a>
    </fieldset>

    <div style="margin-top:15px;">
      <input type="submit" name="mf_categories_save" value="<?php _e('Save Changes', 'mingle-forum'); ?>" class="button" />
    </div>
  </form>

</div>
