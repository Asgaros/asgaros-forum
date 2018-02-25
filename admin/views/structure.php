<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap" id="af-structure">
    <?php
    $title = __('Structure', 'asgaros-forum');
    $titleUpdated = __('Structure updated.', 'asgaros-forum');
    $this->render_admin_header($title, $titleUpdated);

    $categories = $asgarosforum->content->get_categories();
    ?>

    <div id="hidden-data" style="display: none;">
        <select id="data-forum-parent-one-level">
            <?php
            if ($categories) {
                foreach ($categories as $category) {
                    echo '<option value="'.$category->term_id.'_0">'.$category->name.'</option>';
                }
            }
            ?>
        </select>

        <select id="data-forum-parent-two-level">
            <?php
            if ($categories) {
                foreach ($categories as $category) {
                    echo '<option value="'.$category->term_id.'_0">'.$category->name.'</option>';

                    $forums = $asgarosforum->get_forums($category->term_id, 0, true);

                    if ($forums) {
                        foreach ($forums as $forum) {
                            echo '<option value="'.$category->term_id.'_'.$forum->id.'">&mdash; '.esc_html($forum->name).'</option>';
                        }
                    }
                }
            }
            ?>
        </select>
    </div>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div class="postbox-container">

                <div id="editor-container" class="postbox" style="display: none;">
                    <h2 class="hndle"></h2>
                    <div class="inside">
                        <div class="editor-instance" id="category-editor" style="display: none;">
                            <form method="post">
                                <?php wp_nonce_field('asgaros_forum_save_category'); ?>
                                <input type="hidden" name="category_id" value="new">

                                <table class="form-table">
                                    <tr>
                                        <th><label for="category_name"><?php _e('Name:', 'asgaros-forum'); ?></label></th>
                                        <td><input class="element-name" type="text" size="100" maxlength="200" name="category_name" id="category_name" value="" required></td>
                                    </tr>
                                    <tr>
                                        <th><label for="category_access"><?php _e('Access:', 'asgaros-forum'); ?></label></th>
                                        <td>
                                            <select name="category_access" id="category_access">
                                                <option value="everyone"><?php _e('Everyone', 'asgaros-forum'); ?></option>
                                                <option value="loggedin"><?php _e('Logged in users only', 'asgaros-forum'); ?></option>
                                                <option value="moderator"><?php _e('Moderators only', 'asgaros-forum'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="category_order"><?php _e('Order:', 'asgaros-forum'); ?></label></th>
                                        <td><input type="number" size="4" id="category_order" name="category_order" value="" min="1"></td>
                                    </tr>
                                    <?php AsgarosForumUserGroups::renderCategoryEditorFields(); ?>
                                </table>

                                <p class="submit">
                                    <input type="submit" name="af-create-edit-category-submit" value="<?php _e('Save', 'asgaros-forum'); ?>" class="button button-primary">
                                    <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
                                </p>
                            </form>
                        </div>

                        <div class="editor-instance" id="forum-editor" style="display: none;">
                            <form method="post">
                                <?php wp_nonce_field('asgaros_forum_save_forum'); ?>
                                <input type="hidden" name="forum_id" value="new">
                                <input type="hidden" name="forum_category" value="0">
                                <input type="hidden" name="forum_parent_forum" value="0">

                                <table class="form-table">
                                    <tr>
                                        <th><label for="forum_name"><?php _e('Name:', 'asgaros-forum'); ?></label></th>
                                        <td><input class="element-name" type="text" size="100" maxlength="255" name="forum_name" id="forum_name" value="" required></td>
                                    </tr>
                                    <tr>
                                        <th><label for="forum_description"><?php _e('Description:', 'asgaros-forum'); ?></label></th>
                                        <td><input type="text" size="100" maxlength="255" id="forum_description" name="forum_description" value=""></td>
                                    </tr>
                                    <tr>
                                        <th><label for="forum_parent"><?php _e('Parent:', 'asgaros-forum'); ?></label></th>
                                        <td>
                                            <select name="forum_parent" id="forum_parent"></select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="forum_icon"><?php _e('Icon:', 'asgaros-forum'); ?></label></th>
                                        <td>
                                            <input type="text" size="50" id="forum_icon" name="forum_icon" value="">
                                            <a href="https://developer.wordpress.org/resource/dashicons/#editor-justify" target="_blank">
                                                <?php _e('List of available icons.', 'asgaros-forum'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="forum_closed"><?php _e('Closed:', 'asgaros-forum'); ?></label></th>
                                        <td><input type="checkbox" id="forum_closed" name="forum_closed"></td>
                                    </tr>
                                    <tr>
                                        <th><label for="forum_order"><?php _e('Order:', 'asgaros-forum'); ?></label></th>
                                        <td><input type="number" size="4" id="forum_order" name="forum_order" value="" min="1"></td>
                                    </tr>
                                </table>

                                <p class="submit">
                                    <input type="submit" name="af-create-edit-forum-submit" value="<?php _e('Save', 'asgaros-forum'); ?>" class="button button-primary">
                                    <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
                                </p>
                            </form>
                        </div>

                        <div class="editor-instance delete-layer" id="category-delete" style="display: none;">
                            <form method="post">
                                <?php wp_nonce_field('asgaros_forum_delete_category'); ?>
                                <input type="hidden" name="category-id" value="0">
                                <p><?php _e('Deleting this category will also permanently delete all forums, sub-forums, topics and posts inside it. Are you sure you want to delete this category?', 'asgaros-forum'); ?></p>

                                <p class="submit">
                                    <input type="submit" name="asgaros-forum-delete-category" value="<?php _e('Delete', 'asgaros-forum'); ?>" class="button button-primary">
                                    <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
                                </p>
                            </form>
                        </div>

                        <div class="editor-instance delete-layer" id="forum-delete" style="display: none;">
                            <form method="post">
                                <?php wp_nonce_field('asgaros_forum_delete_forum'); ?>
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

                <a href="#" class="category-editor-link dashicons-before dashicons-plus margin-bottom padding-top" data-value-id="new" data-value-editor-title="<?php _e('Add Category', 'asgaros-forum'); ?>">
                    <?php _e('Add Category', 'asgaros-forum'); ?>
                </a>

                <?php
                if (!empty($categories)) {
                    foreach ($categories as $category) {
                        $term_meta = get_term_meta($category->term_id);
                        $access = (!empty($term_meta['category_access'][0])) ? $term_meta['category_access'][0] : 'everyone';
                        $order = (!empty($term_meta['order'][0])) ? $term_meta['order'][0] : 1;
                        echo '<input type="hidden" id="category_'.$category->term_id.'_name" value="'.esc_html(stripslashes($category->name)).'">';
                        echo '<input type="hidden" id="category_'.$category->term_id.'_access" value="'.$access.'">';
                        echo '<input type="hidden" id="category_'.$category->term_id.'_order" value="'.$order.'">';
                        AsgarosForumUserGroups::renderHiddenFields($category->term_id);

                        $forums = $asgarosforum->get_forums($category->term_id, 0, true, ARRAY_A);
                        ?>
                        <div class="postbox">
                            <h2 class="hndle dashicons-before dashicons-archive">
                                <?php echo stripslashes($category->name); ?>&nbsp;
                                <span class="element-id">
                                    <?php
                                    echo '(';
                                    echo __('ID', 'asgaros-forum').': '.$category->term_id;
                                    echo ' &middot; ';
                                    echo __('Access:', 'asgaros-forum').' ';
                                    if ($access === 'everyone') {
                                        _e('Everyone', 'asgaros-forum');
                                    } else if ($access === 'loggedin') {
                                        _e('Logged in users only', 'asgaros-forum');
                                    } else if ($access === 'moderator') {
                                        _e('Moderators only', 'asgaros-forum');
                                    }
                                    echo ' &middot; ';
                                    echo __('Order:', 'asgaros-forum').' '.$order;
                                    AsgarosForumUserGroups::renderUserGroupsInCategory($category->term_id);
                                    do_action('asgarosforum_admin_show_custom_category_data', $category->term_id);
                                    echo ')';
                                    ?>
                                </span>
                                <span class="category-actions">
                                    <a href="#" class="category-delete-link action-delete" data-value-id="<?php echo $category->term_id; ?>" data-value-editor-title="<?php _e('Delete Category', 'asgaros-forum'); ?>"><?php _e('Delete Category', 'asgaros-forum'); ?></a>
                                    &middot;
                                    <a href="#" class="category-editor-link action-edit" data-value-id="<?php echo $category->term_id; ?>" data-value-editor-title="<?php _e('Edit Category', 'asgaros-forum'); ?>"><?php _e('Edit Category', 'asgaros-forum'); ?></a>
                                </span>
                            </h2>
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

                    echo '<a href="#" class="category-editor-link dashicons-before dashicons-plus margin-bottom" data-value-id="new" data-value-editor-title="'.__('Add Category', 'asgaros-forum').'">';
                        _e('Add Category', 'asgaros-forum');
                    echo '</a>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
