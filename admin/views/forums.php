<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap" id="af-structure">
    <h2><?php _e('Forums', 'asgaros-forum'); ?></h2>
    <?php if ($this->saved) { ?>
        <div class="updated">
            <p><?php _e('Forum structure updated.', 'asgaros-forum'); ?></p>
        </div>
    <?php
    }
    ?>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div class="postbox-container">

                <div id="structure-editor" class="postbox" style="display: none;">
                    <h2 class="hndle"><span><?php _e('Add new forum', 'asgaros-forum'); ?></span></h2>
                    <div class="inside">

                        <div id="forum-editor" style="display: none;">
                            <form method="post">
                                <input type="hidden" name="forum_id" value="new">
                                <input type="hidden" name="forum_category" value="0">
                                <input type="hidden" name="forum_parent_forum" value="0">
                                <input type="hidden" id="forum_new_name" value="">
                                <input type="hidden" id="forum_new_description" value="">
                                <input type="hidden" id="forum_new_closed" value="">
                                <input type="hidden" id="forum_new_order" value="1">

                                <table class="form-table">
                                    <tr>
                                        <th><label class="post-attributes-label-wrapper" for="forum_name"><?php _e('Name:', 'asgaros-forum'); ?></label></th>
                                        <td><input type="text" size="100" name="forum_name" id="forum_name" value="" required></td>
                                    </tr>
                                    <tr>
                                        <th><label for="forum_description"><?php _e('Description:', 'asgaros-forum'); ?></label></th>
                                        <td><input type="text" size="100" id="forum_description" name="forum_description" value=""></td>
                                    </tr>
                                    <tr>
                                        <th><label for="forum_closed"><?php _e('Closed:', 'asgaros-forum'); ?></label></th>
                                        <td><input type="checkbox" id="forum_closed" name="forum_closed"></td>
                                    </tr>
                                    <tr>
                                        <th><label for="forum_order"><?php _e('Order:', 'asgaros-forum'); ?></label></th>
                                        <td><input type="number" size="4" id="forum_order" name="forum_order" value="" min="0"></td>
                                    </tr>
                                </table>

                                <p class="submit">
                                    <input type="submit" name="af-create-edit-forum-submit" value="<?php _e('Save', 'asgaros-forum'); ?>" class="button button-primary">
                                    <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
                                </p>
                            </form>
                        </div>

                        <div id="forum-delete" style="display: none;">
                            <form method="post">
                                <input type="hidden" name="forum-id" value="0">
                                <input type="hidden" name="forum-category" value="0">
                                <p><?php _e('Deleting this forum will also permanently delete all sub-forums, topics and posts inside it. Are you sure you want to delete this forum?', 'asgaros-forum'); ?></p>

                                <p class="submit">
                                    <input type="submit" name="asgaros-forum-delete-forum" value="<?php _e('Delete', 'asgaros-forum'); ?>" class="button button-primary">
                                    <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>

                <?php
                if (!empty($categories)) {
                    foreach ($categories as $category) {
                        $forums = $asgarosforum->get_forums($category->term_id, 0, ARRAY_A);
                        ?>
                        <div class="postbox">
                            <h2 class="hndle"><span><?php echo stripslashes($category->name); ?> <span class="element-id">(<?php _e('ID', 'asgaros-forum'); ?>: <?php echo $category->term_id; ?>)</span></span></h2>
                            <div class="inside">
                                <?php
                                if (!empty($forums)) {
                                    $structureTable = new Asgaros_Forum_Admin_Structure_Table($forums);
                                    $structureTable->prepare_items();
                                    $structureTable->display();
                                }
                                ?>
                                <a href="#" class="forum-editor-link dashicons-before dashicons-plus padding-top" data-value-id="new" data-value-category="<?php echo $category->term_id; ?>" data-value-parent-forum="0" data-value-editor-title="<?php _e('Add Forum', 'asgaros-forum'); ?>">
                                    <?php _e('Add Forum', 'asgaros-forum'); ?>
                                </a>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>
