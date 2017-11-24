<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap" id="af-usergroups">
    <?php
    $title = __('User Groups', 'asgaros-forum');
    $titleUpdated = __('User Groups updated.', 'asgaros-forum');
    $this->render_admin_header($title, $titleUpdated);
    ?>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div class="postbox-container">

                <div id="usergroup-editor-container" class="postbox" style="display: none;">
                    <h2 class="hndle"></h2>
                    <div class="inside">
                        <div id="usergroup-editor" style="display: none;">
                            <form method="post">
                                <?php wp_nonce_field('asgaros_forum_save_usergroup'); ?>
                                <input type="hidden" name="usergroup_id" value="new">

                                <table class="form-table">
                                    <tr>
                                        <th><label class="post-attributes-label-wrapper" for="usergroup_name"><?php _e('Name:', 'asgaros-forum'); ?></label></th>
                                        <td><input type="text" size="100" maxlength="200" name="usergroup_name" id="usergroup_name" value="" required></td>
                                    </tr>
                                    <tr id="usergroup-color-settings">
                                        <th><label for="usergroup_color"><?php _e('Color:', 'asgaros-forum'); ?></label></th>
                                        <td><input type="text" value="#444444" class="color-picker" name="usergroup_color" id="usergroup_color" data-default-color="#444444"></td>
                                    </tr>
                                </table>

                                <p class="submit">
                                    <input type="submit" name="af-create-edit-usergroup-submit" value="<?php _e('Save', 'asgaros-forum'); ?>" class="button button-primary">
                                    <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
                                </p>
                            </form>
                        </div>

                        <div id="usergroup-delete" style="display: none;">
                            <form method="post">
                                <?php wp_nonce_field('asgaros_forum_delete_usergroup'); ?>
                                <input type="hidden" name="usergroup-id" value="0">
                                <p><?php _e('Are you sure you want to delete this user group?', 'asgaros-forum'); ?></p>

                                <p class="submit">
                                    <input type="submit" name="asgaros-forum-delete-usergroup" value="<?php _e('Delete', 'asgaros-forum'); ?>" class="button button-primary">
                                    <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>

                <!--
                <div class="postbox">
                    <h2 class="hndle">
                        <?php _e('Default User Groups', 'asgaros-forum'); ?>
                    </h2>
                    <div class="inside"></div>
                </div>
                -->

                <div class="postbox">
                    <h2 class="hndle">
                        <?php _e('Custom User Groups', 'asgaros-forum'); ?>
                    </h2>
                    <div class="inside">
                        <?php
                        $usergroups = AsgarosForumUserGroups::getUserGroups();

                        if (!empty($usergroups)) {
                            $userGroupsTable = new Asgaros_Forum_Admin_UserGroups_Table($usergroups);
                            $userGroupsTable->prepare_items();
                            $userGroupsTable->display();
                        }
                        
                        echo '<a href="#" class="usergroup-editor-link dashicons-before dashicons-plus padding-top" data-value-id="new" data-value-editor-title="'.__('Add User Group', 'asgaros-forum').'">';
                            _e('Add User Group', 'asgaros-forum');
                        echo '</a>';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
