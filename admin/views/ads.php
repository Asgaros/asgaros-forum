<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap" id="af-structure">
    <?php
    $title = __('Ads', 'asgaros-forum');
    $titleUpdated = __('Ads updated.', 'asgaros-forum');
    $this->render_admin_header($title, $titleUpdated);
    ?>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div class="postbox-container">

                <div id="editor-container" class="postbox" style="display: none;">
                    <h2 class="hndle"></h2>
                    <div class="inside">
                        <div class="editor-instance" id="ad-editor" style="display: none;">
                            <form method="post">
                                <?php wp_nonce_field('asgaros_forum_save_ad'); ?>
                                <input type="hidden" name="ad_id" value="new">

                                <table class="form-table">
                                    <tr>
                                        <th><label for="ad_name"><?php _e('Name:', 'asgaros-forum'); ?></label></th>
                                        <td><input class="element-name" type="text" size="100" maxlength="200" name="ad_name" id="ad_name" value="" required></td>
                                    </tr>
                                    <tr>
                                        <th><label for="ad_code"><?php _e('Code:', 'asgaros-forum'); ?></label></th>
                                        <td><textarea class="large-text" rows="8" cols="80" type="text" name="ad_code" id="ad_code" required></textarea></td>
                                    </tr>
                                    <tr>
                                        <th><label for="ad_active"><?php _e('Active:', 'asgaros-forum'); ?></label></th>
                                        <td><input type="checkbox" id="ad_active" name="ad_active"></td>
                                    </tr>
                                    <tr id="locations-editor">
                                        <th><label><?php _e('Location:', 'asgaros-forum'); ?></label></th>
                                        <td>
                                            <?php
                                            foreach ($this->asgarosforum->ads->locations as $key => $value) {
                                                echo '<label><input type="checkbox" name="ad_locations[]" value="'.$key.'">'.$value.'</label>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </table>

                                <p class="submit">
                                    <input type="submit" name="af-create-edit-ad-submit" value="<?php _e('Save', 'asgaros-forum'); ?>" class="button button-primary">
                                    <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
                                </p>
                            </form>
                        </div>

                        <div class="editor-instance delete-layer" id="ad-delete" style="display: none;">
                            <form method="post">
                                <?php wp_nonce_field('asgaros_forum_delete_ad'); ?>
                                <input type="hidden" name="ad_id" value="0">
                                <p><?php _e('Are you sure you want to delete this ad?', 'asgaros-forum'); ?></p>

                                <p class="submit">
                                    <input type="submit" name="asgaros-forum-delete-ad" value="<?php _e('Delete', 'asgaros-forum'); ?>" class="button button-primary">
                                    <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="postbox">
                    <h2 class="hndle dashicons-before dashicons-slides">
                        <?php _e('Ads', 'asgaros-forum'); ?>
                    </h2>
                    <div class="inside">
                        <?php
                        $ads = $this->asgarosforum->ads->get_ads();

                        if (!empty($ads)) {
                            $ads_table = new AsgarosForumAdminTableAds($ads);
                            $ads_table->prepare_items();
                            $ads_table->display();
                        }
                        ?>

                        <a href="#" class="ad-editor-link dashicons-before dashicons-plus padding-top" data-value-id="new" data-value-editor-title="<?php _e('New Ad', 'asgaros-forum'); ?>">
                            <?php _e('New Ad', 'asgaros-forum'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
