<?php

if (!defined('ABSPATH')) exit;

?>
<div class="wrap" id="af-forums">
    <h2><?php _e('Forums', 'asgaros-forum'); ?></h2>
    <?php if ($this->saved) { ?>
        <div class="updated">
            <p><?php _e('Your Forums have been saved.', 'asgaros-forum'); ?></p>
        </div>
    <?php } ?>
    <form method="post">
        <?php if (!empty($categories)) { ?>
            <?php foreach ($categories as $category) { ?>
                <h3><?php echo stripslashes($category->name); ?></h3>
                <div id="category-<?php echo $category->term_id; ?>">
                    <?php $forums = $asgarosforum->get_forums($category->term_id); ?>
                    <?php if (!empty($forums)) { ?>
                        <?php foreach ($forums as $forum) { ?>
                            <div class="forum">
                                <input type="hidden" name="forum_id[<?php echo $category->term_id; ?>][]" value="<?php echo $forum->id; ?>" />
                                <label><?php _e('Name:', 'asgaros-forum'); ?><input type="text" name="forum_name[<?php echo $category->term_id; ?>][]" value="<?php echo esc_html(stripslashes($forum->name)); ?>" /></label>
                                <label><?php _e('Description:', 'asgaros-forum'); ?><input type="text" name="forum_description[<?php echo $category->term_id; ?>][]" value="<?php echo esc_html(stripslashes($forum->description)); ?>" /></label>
                                <label>
                                    <a href="#" class="af-sort-up dashicons-before dashicons-arrow-up"></a>
                                    <a href="#" class="af-sort-down dashicons-before dashicons-arrow-down"></a>
                                </label>
                                <a href="#" class="af-remove-forum dashicons-before dashicons-trash" title="<?php _e('Remove this Forum', 'asgaros-forum'); ?>"></a>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
                <a href="#" class="af-add-new-forum dashicons-before dashicons-plus" title="<?php _e('Add new Forum', 'asgaros-forum'); ?>" data-value="<?php echo $category->term_id; ?>"><?php _e('Add new Forum', 'asgaros-forum'); ?></a><br />
            <?php } ?>
            <input type="submit" name="af_forums_submit" value="<?php _e('Save Changes', 'asgaros-forum'); ?>" class="button button-primary" />
        <?php } else { ?>
            <p><?php _e('You must add some categories first.', 'asgaros-forum'); ?></p>
        <?php } ?>
    </form>
    <div id="new-element">
        <div class="forum">
            <input type="hidden" name="forum_id[][]" value="new" />
            <label><?php _e('Name:', 'asgaros-forum'); ?><input type="text" name="forum_name[][]" value="" /></label>
            <label><?php _e('Description:', 'asgaros-forum'); ?><input type="text" name="forum_description[][]" value="" /></label>
            <label>
                <a href="#" class="af-sort-up dashicons-before dashicons-arrow-up"></a>
                <a href="#" class="af-sort-down dashicons-before dashicons-arrow-down"></a>
            </label>
            <a href="#" class="af-remove-forum dashicons-before dashicons-trash" title="<?php _e('Remove this Forum', 'asgaros-forum'); ?>"></a>
        </div>
    </div>
</div>
