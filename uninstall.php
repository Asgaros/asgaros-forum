<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

global $wpdb;

delete_option('asgarosforum_options');
delete_option('asgarosforum_db_version');

// For site options in Multisite
delete_site_option('asgarosforum_options');
delete_site_option('asgarosforum_db_version');

// Delete user meta data
delete_metadata('user', 0, 'asgarosforum_lastvisit', '', true);

// Delete terms ...
$terms = $wpdb->get_results('SELECT t.name, t.term_id FROM '.$wpdb->terms.' AS t INNER JOIN '.$wpdb->term_taxonomy.' AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = "asgarosforum-category";');

foreach ($terms as $term) {
    wp_delete_term($term->term_id, 'asgarosforum-category');
}

// Drop a custom db table
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}forum_forums;");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}forum_threads;");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}forum_posts;");
?>
