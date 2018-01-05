<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap" id="af-structure">
    <?php
    $title = __('Reports', 'asgaros-forum');
    $titleUpdated = __('Reports updated.', 'asgaros-forum');
    $this->render_admin_header($title, $titleUpdated);
    ?>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div class="postbox-container">

                <div id="editor-container" class="postbox" style="display: none;">
                    <h2 class="hndle"></h2>
                    <div class="inside">
                        <div class="editor-instance delete-layer" id="report-delete" style="display: none;">
                            <form method="post">
                                <?php wp_nonce_field('asgaros_forum_delete_report'); ?>
                                <input type="hidden" name="report-id" value="0">
                                <p><?php _e('Are you sure you want to delete this report?', 'asgaros-forum'); ?></p>

                                <p class="submit">
                                    <input type="submit" name="asgaros-forum-delete-report" value="<?php _e('Delete', 'asgaros-forum'); ?>" class="button button-primary">
                                    <a class="button-cancel button button-secondary"><?php _e('Cancel', 'asgaros-forum'); ?></a>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="postbox">
                    <h2 class="hndle">
                        <?php _e('Reports', 'asgaros-forum'); ?>
                    </h2>
                    <div class="inside">
                        <?php
                        $reports = $this->asgarosforum->reports->get_reports();

                        if (empty($reports)) {
                            _e('There are no reports yet!', 'asgaros-forum');
                        } else {
                            $reportsTable = new Asgaros_Forum_Admin_Reports_Table($reports);
                            $reportsTable->prepare_items();
                            $reportsTable->display();
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
