<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

$option_name = 'plugin_option_name';

delete_option('asgarosforum_options');
delete_option('asgarosforum_db_version');

// For site options in Multisite
delete_site_option('asgarosforum_options');
delete_site_option('asgarosforum_db_version');

// Drop a custom db table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}forum_categories;");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}forum_forums;");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}forum_threads;");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}forum_posts;");
?>
