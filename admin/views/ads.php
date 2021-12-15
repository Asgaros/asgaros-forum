<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap" id="af-structure">
    <?php
    $title = __('Ads', 'asgaros-forum');
    $titleUpdated = __('Ads updated.', 'asgaros-forum');
    $this->render_admin_header($title, $titleUpdated);
    ?>

    <div id="editor-container" class="settings-box" style="display: none;">
        <div class="settings-header"></div>
        <div class="editor-instance" id="ad-editor" style="display: none;">
            <form method="post">
                <?php wp_nonce_field('asgaros_forum_save_ad'); ?>
                <input type="hidden" name="ad_id" value="new">

                <table class="form-table">
                    <tr>
                        <th><label for="ad_name"><?php esc_html_e('Name:', 'asgaros-forum'); ?></label></th>
                        <td><input class="element-name" type="text" size="100" maxlength="200" name="ad_name" id="ad_name" value="" required></td>
                    </tr>
                    <tr>
                        <th><label for="ad_code"><?php esc_html_e('Code:', 'asgaros-forum'); ?></label></th>
                        <td><textarea class="large-text" data-code-editor-mode="htmlmixed" rows="8" cols="80" type="text" name="ad_code" id="ad_code"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="ad_active"><?php esc_html_e('Active:', 'asgaros-forum'); ?></label></th>
                        <td><input type="checkbox" id="ad_active" name="ad_active"></td>
                    </tr>
                    <tr id="locations-editor">
                        <th><label><?php esc_html_e('Location:', 'asgaros-forum'); ?></label></th>
                        <td>
                            <?php
                            foreach ($this->asgarosforum->ads->locations as $key => $value) {
                                echo '<label><input type="checkbox" name="ad_locations[]" value="'.esc_attr($key).'">'.esc_html($value).'</label>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="af-create-edit-ad-submit" value="<?php esc_attr_e('Save', 'asgaros-forum'); ?>" class="button button-primary">
                    <a class="button-cancel button button-secondary"><?php esc_html_e('Cancel', 'asgaros-forum'); ?></a>
                </p>
            </form>
        </div>

        <div class="editor-instance delete-layer" id="ad-delete" style="display: none;">
            <form method="post">
                <?php wp_nonce_field('asgaros_forum_delete_ad'); ?>
                <input type="hidden" name="ad_id" value="0">
                <p><?php esc_html_e('Are you sure you want to delete this ad?', 'asgaros-forum'); ?></p>

                <p class="submit">
                    <input type="submit" name="asgaros-forum-delete-ad" value="<?php esc_attr_e('Delete', 'asgaros-forum'); ?>" class="button button-primary">
                    <a class="button-cancel button button-secondary"><?php esc_html_e('Cancel', 'asgaros-forum'); ?></a>
                </p>
            </form>
        </div>
    </div>

    <div class="settings-box">
        <div class="settings-header">
            <span class="fas fa-ad"></span>
            <?php esc_html_e('Ads', 'asgaros-forum'); ?>
        </div>
        <?php
        $ads = $this->asgarosforum->ads->get_ads();

        if (!empty($ads)) {
            $ads_table = new AsgarosForumAdminTableAds($ads);
            $ads_table->prepare_items();
            $ads_table->display();
        }

        echo '<a href="#" class="ad-editor-link add-element" data-value-id="new" data-value-editor-title="'.esc_attr__('New Ad', 'asgaros-forum').'">';
            echo '<span class="fas fa-plus"></span>';
            echo esc_html__('New Ad', 'asgaros-forum');
        echo '</a>';

        ?>
    </div>
</div>
