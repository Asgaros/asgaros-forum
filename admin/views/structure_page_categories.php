<div class="wrap">
    <h2>Forum - <?php _e('Structure', 'asgarosforum'); ?></h2>
    <?php if ($saved): ?>
        <div class="updated">
            <p><?php _e('Your Categories have been saved.', 'asgarosforum'); ?></p>
        </div>
    <?php endif; ?>
    <p><i>* <?php _e('Categories can be thought of as empty boxes. Great for organizing stuff, but no good without something in them. Use categories to organize your various Forums.', 'asgarosforum'); ?></i></p>
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=asgarosforum-structure'); ?>" class="nav-tab main-nav nav-tab-active"><?php _e('Categories', 'asgarosforum'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=asgarosforum-structure&action=forums'); ?>" class="nav-tab main-nav"><?php _e('Forums', 'asgarosforum'); ?></a>
    </h2>
    <form action="" method="post">
        <fieldset class="mf_fset">
            <legend><?php _e('Manage Categories', 'asgarosforum'); ?></legend>
            <ol id="sortable-categories" class="mf_ordered_list">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <li class="ui-state-default">
                            <input type="hidden" name="mf_category_id[]" value="<?php echo $cat->id; ?>" />
                            <label><?php _e('Category Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="category_name[]" value="<?php echo esc_html(stripslashes($cat->name)); ?>" /></label>&nbsp;&nbsp;
                            <label><?php _e('Description:', 'asgarosforum'); ?>&nbsp;<input type="text" name="category_description[]" value="<?php echo esc_html(stripslashes($cat->description)); ?>" size="50" /></label>&nbsp;&nbsp;
                            <a href="#" class="button access_control" data-value="<?php echo $cat->id; ?>"><?php _e('Limit Access', 'asgarosforum'); ?></a>
                            <a href="#" class="mf_remove_category" title="<?php _e('Remove this Category', 'asgarosforum'); ?>">
                                <img src="<?php echo WPFURL.'admin/images/remove.png'; ?>" width="24" />
                            </a>
                            <!-- usergroups -->
                            <div id="user-groups-<?php echo $cat->id; ?>" class="user-groups-area">
                                <?php $allusergroups = self::getable_usergroups(); ?>
                                <?php $my_usergroups = (array)maybe_unserialize($cat->usergroups); ?>
                                <?php if (!empty($allusergroups)): ?>
                                    <label><?php _e('Limit access to the following usergroups', 'asgarosforum'); ?>:</label>&nbsp;
                                    <?php foreach ($allusergroups as $usergroup): ?>
                                        <label><input type="checkbox" name="category_usergroups_<?php echo $cat->id; ?>[]" value="<?php echo $usergroup->id; ?>" <?php checked((in_array($usergroup->id, $my_usergroups))); ?> />&nbsp;<?php echo stripslashes($usergroup->name); ?></label>&nbsp;&nbsp;
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="ui-state-default">
                        <input type="hidden" name="mf_category_id[]" value="new" />
                        <label><?php _e('Category Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="category_name[]" value="" /></label>&nbsp;&nbsp;
                        <label><?php _e('Description:', 'asgarosforum'); ?>&nbsp;<input type="text" name="category_description[]" value="" size="50" /></label>
                        <a href="#" class="mf_remove_category" title="<?php _e('Remove this Category', 'asgarosforum'); ?>">
                            <img src="<?php echo WPFURL.'admin/images/remove.png'; ?>" width="24" />
                        </a>
                    </li>
                <?php endif; ?>
            </ol>
            <a href="#" id="mf_add_new_category" title="<?php _e('Add new Category', 'asgarosforum'); ?>">
                <img src="<?php echo WPFURL.'admin/images/add.png'; ?>" width="32" />
            </a>
        </fieldset>
        <input type="submit" name="mf_categories_save" value="<?php _e('Save Changes', 'asgarosforum'); ?>" class="mf_admin_submit button" />
    </form>
</div>
<div id="hidden-element-container">
    <li class="ui-state-default">
        <input type="hidden" name="mf_category_id[]" value="new" />
        <label><?php _e('Category Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="category_name[]" value="" /></label>&nbsp;&nbsp;
        <label><?php _e('Description:', 'asgarosforum'); ?>&nbsp;<input type="text" name="category_description[]" value="" size="50" /></label>
        <a href="#" class="mf_remove_category" title="<?php _e('Remove this Category', 'asgarosforum'); ?>">
            <img src="<?php echo WPFURL.'admin/images/remove.png'; ?>" width="24" />
        </a>
    </li>
</div>
