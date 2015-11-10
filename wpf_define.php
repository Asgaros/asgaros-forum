<?php

if (!defined('WP_CONTENT_DIR'))
  define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

define('WPFPLUGIN', "mingle-forum");

define('WPFDIR', dirname(plugin_basename(__FILE__)));
define('WPFPATH', plugin_dir_path(__FILE__));
define('WPFURL', plugin_dir_url(__FILE__));
define('OLDSKINDIR', WPFPATH . 'default-skin/');
define('OLDSKINURL', WPFURL . 'default-skin/');
define('SKINDIR', WP_CONTENT_DIR . '/mingle-forum-skins/');
define('SKINURL', WP_CONTENT_URL . '/mingle-forum-skins/');
define('NO_SKIN_SCREENSHOT_URL', WPFURL . 'skins/default.png');

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

define("CAT", __("Category", "mingleforum"));
define("FORUM", __("Forum", "mingleforum"));
define("TOPIC", __("Topic", "mingleforum"));
define("POST", __("Post", "mingleforum"));

// Maybe change
define("SORT_ORDER", "DESC");
?>