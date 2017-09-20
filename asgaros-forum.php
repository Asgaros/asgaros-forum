<?php

/*
  Plugin Name: Asgaros Forum
  Plugin URI: https://asgaros.de
  Description: Asgaros Forum is the best forum solution for WordPress! It comes with dozens of features in a beautiful design and stays slight, simple and fast.
  Version: 1.6.1
  Author: Thomas Belser
  Author URI: https://asgaros.de
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

// Include Asgaros Forum Core Files.
require('includes/forum.php');
require('includes/forum-database.php');
require('includes/forum-taxonomies.php');
require('includes/forum-rewrite.php');
require('includes/forum-permissions.php');
require('includes/forum-insert.php');
require('includes/forum-notifications.php');
require('includes/forum-widgets.php');
require('includes/forum-thememanager.php');
require('includes/forum-unread.php');
require('includes/forum-uploads.php');
require('includes/forum-search.php');
require('includes/forum-statistics.php');
require('includes/forum-breadcrumbs.php');
require('includes/forum-editor.php');
require('includes/forum-shortcodes.php');
require('includes/forum-pagination.php');
require('includes/forum-online.php');
require('includes/forum-usergroups.php');
require('includes/forum-profile.php');
require('admin/admin.php');
require('admin/tables/admin-structure-table.php');
require('admin/tables/admin-usergroups-table.php');

AsgarosForumDatabase::createInstance();
$asgarosforum = new AsgarosForum();

if (is_admin()) {
    $asgarosforum_admin = new AsgarosForumAdmin();
}
