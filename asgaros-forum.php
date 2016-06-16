<?php

/*
  Plugin Name: Asgaros Forum
  Plugin URI: https://github.com/Asgaros/asgaros-forum
  Description: A lightweight and simple forum plugin for WordPress.
  Version: 1.1.6
  Author: Thomas Belser
  Author URI: http://thomasbelser.net
  License: GPL2
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  Text Domain: asgaros-forum
  Domain Path: /languages

  Asgaros Forum is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 2 of the License, or
  any later version.

  Asgaros Forum is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Asgaros Forum. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

if (!defined('ABSPATH')) exit;

//Textdomain Hook
function asgarosforum_load_plugin_textdomain() {
    load_plugin_textdomain('asgaros-forum', FALSE, basename(dirname(__FILE__)).'/languages/');
}
add_action('plugins_loaded', 'asgarosforum_load_plugin_textdomain');

require('includes/forum-database.php');
require('includes/forum-permissions.php');
require('includes/forum-insert.php');
require('includes/forum-notifications.php');
require('includes/forum.php');
require('includes/forum-widgets.php');
require('includes/forum-thememanager.php');
require('includes/forum-uploads.php');
require('admin/admin.php');

AsgarosForumDatabase::getInstance();
AsgarosForumPermissions::getInstance();
AsgarosForumNotifications::getInstance();
AsgarosForumUploads::getInstance();

global $asgarosforum;
global $asgarosforum_admin;
$plugin_root_url = plugin_dir_url(__FILE__);
$asgarosforum = new AsgarosForum($plugin_root_url);
AsgarosForumThemeManager::getInstance($plugin_root_url);

if (is_admin()) {
    $asgarosforum_admin = new asgarosforum_admin();
}

?>
