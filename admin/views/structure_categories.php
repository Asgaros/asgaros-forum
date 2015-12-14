<div class="wrap">
    <h2>Forum - <?php _e('Structure', 'asgarosforum'); ?></h2>
    <?php if ($this->saved): ?>
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
        <fieldset class="af_fset">
            <ol id="sortable-categories" class="sortable_elements af_ordered_list">
                <?php if (!empty($categories)): ?>
                    <?php do_action('asgarosforum_admin_before_categories_options'); ?>
                    <?php foreach ($categories as $category): ?>
                        <li class="ui-state-default">
                            <input type="hidden" name="af_category_id[]" value="<?php echo $category->id; ?>" />
                            <label><?php _e('Category Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="category_name[]" value="<?php echo esc_html(stripslashes($category->name)); ?>" /></label>
                            <a href="#" class="af_remove_category dashicons-before dashicons-trash" title="<?php _e('Remove this Category', 'asgarosforum'); ?>"></a>
                            <?php do_action('asgarosforum_admin_after_category_options', $category); ?>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="ui-state-default">
                        <input type="hidden" name="af_category_id[]" value="new" />
                        <label><?php _e('Category Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="category_name[]" value="" /></label>
                        <a href="#" class="af_remove_category dashicons-before dashicons-trash" title="<?php _e('Remove this Category', 'asgarosforum'); ?>"></a>
                    </li>
                <?php endif; ?>
            </ol>
            <a href="#" id="af_add_new_category" class="dashicons-before dashicons-plus" title="<?php _e('Add new Category', 'asgarosforum'); ?>"><?php _e('Add new Category', 'asgarosforum'); ?></a>
        </fieldset>
        <input type="submit" name="af_categories_save" value="<?php _e('Save Changes', 'asgarosforum'); ?>" class="af_admin_submit button button-primary" />
    </form>
</div>
<div id="hidden-element-container">
    <li class="ui-state-default">
        <input type="hidden" name="af_category_id[]" value="new" />
        <label><?php _e('Category Name:', 'asgarosforum'); ?>&nbsp;<input type="text" name="category_name[]" value="" /></label>
        <a href="#" class="af_remove_category dashicons-before dashicons-trash" title="<?php _e('Remove this Category', 'asgarosforum'); ?>"></a>
    </li>
</div>
