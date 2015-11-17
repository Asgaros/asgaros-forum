<?php

if (!defined('WP_CONTENT_DIR'))
  define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

define('WPFPLUGIN', "asgarosforum");

define('WPFDIR', dirname(plugin_basename(__FILE__)));
define('WPFPATH', plugin_dir_path(__FILE__));
define('WPFURL', plugin_dir_url(__FILE__));

define("ADMIN_PROFILE_URL", get_bloginfo("url") . "/wp-admin/user-edit.php?user_id=");
define("PROFILE_URL", get_bloginfo("url") . "/wp-admin/profile.php");

define("ADMIN_ROW_COL", "rows='8' cols='35'");
define("ROW_COL", "rows='20' cols='80'");

define("PHP_SELF", "{$_SERVER['PHP_SELF']}");

define('MAIN', "main");
define('THREAD', "thread");
define('SEARCH', "search");
define('PROFILE', "profile");
define('POSTREPLY', "postreply");
define('EDITPOST', "editpost");
define("NEWTOPICS", "newtopics");
define("NEWTOPIC", "newtopic");

define("CAT", __("Category", "asgarosforum"));
define("FORUM", __("Forum", "asgarosforum"));
define("TOPIC", __("Topic", "asgarosforum"));
define("POST", __("Post", "asgarosforum"));

// Maybe change
define("SORT_ORDER", "DESC");
?>
