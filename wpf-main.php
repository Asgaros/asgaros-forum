<?php

/*
  Plugin Name: Asgaros Forum
  Plugin URI: https://github.com/Asgaros/asgaros-forum
  Description: A lightweight and simple forum plugin for WordPress which is based on the Mingle Forum plugin (v1.1.0-dev) from Cartpauj (https://github.com/cartpauj/mingle-forum).
  Version: 1.0.0 Development-Version
  Author: Thomas Belser
  Author URI: https://github.com/Asgaros/asgaros-forum
  Text Domain: asgarosforum

  GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

//Textdomain Hook
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain('asgarosforum', false, $plugin_dir . '/translations/');

//Setup defines
define('WPFURL', plugin_dir_url(__FILE__));
define('POST', "post");
define('THREAD', "thread");
define("FORUM", __("Forum", "asgarosforum"));

//Load class files
require('admin/mfadmin.php');
require("wpf.class.php");

global $asgarosforum;
$asgarosforum = new asgarosforum();

?>
