<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

global $wpdb;

delete_option('asgarosforum_options');
delete_option('asgarosforum_db_version');

// For site options in multisite
delete_site_option('asgarosforum_options');
delete_site_option('asgarosforum_db_version');

// Delete user meta data
delete_metadata('user', 0, 'asgarosforum_lastvisit', '', true);
delete_metadata('user', 0, 'asgarosforum_moderator', '', true);
delete_metadata('user', 0, 'asgarosforum_banned', '', true);
delete_metadata('user', 0, 'asgarosforum_subscription_topic', '', true);

// Delete terms
$terms = $wpdb->get_col('SELECT t.term_id FROM '.$wpdb->terms.' AS t INNER JOIN '.$wpdb->term_taxonomy.' AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = "asgarosforum-category";');

foreach ($terms as $term) {
    wp_delete_term($term, 'asgarosforum-category');
}

// Drop custom tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}forum_forums;");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}forum_threads;");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}forum_posts;");

// Delete uploaded files
function recursiveDelete($str) {
    if (is_file($str)) {
        return @unlink($str);
    } else if (is_dir($str)) {
        $scan = glob(rtrim($str, '/').'/*');

        foreach($scan as $path) {
            recursiveDelete($path);
        }

        return @rmdir($str);
    }
}

$upload_dir = wp_upload_dir();
$upload_path = $upload_dir['basedir'].'/asgarosforum/';
recursiveDelete($upload_path);

// Delete themes
$theme_path = WP_CONTENT_DIR.'/themes-asgarosforum';
recursiveDelete($theme_path);

?>
