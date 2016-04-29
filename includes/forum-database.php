<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumDatabase {
    const DATABASE_VERSION = 4;

    private static $instance = null;
    private static $table_forums;
    private static $table_threads;
    private static $table_posts;

    // AsgarosForumDatabase instance creator
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self;
		} else {
			return self::$instance;
		}
	}

    // AsgarosForumDatabase constructor
	private function __construct() {
        global $wpdb;

        self::$table_forums     = $wpdb->prefix.'forum_forums';
        self::$table_threads    = $wpdb->prefix.'forum_threads';
        self::$table_posts      = $wpdb->prefix.'forum_posts';

        register_activation_hook(__FILE__, array($this, 'buildDatabase'));
        add_action('plugins_loaded', array($this, 'buildDatabase'));
	}

    public static function getTable($name) {
        if ($name === 'forums') {
            return self::$table_forums;
        } else if ($name === 'threads') {
            return self::$table_threads;
        } else if ($name === 'posts') {
            return self::$table_posts;
        }
    }

    public static function buildDatabase() {
        global $wpdb;
        $database_version_installed = get_option('asgarosforum_db_version');

        if ($database_version_installed != self::DATABASE_VERSION) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql1 = "
            CREATE TABLE ".self::$table_forums." (
            id int(11) NOT NULL auto_increment,
            name varchar(255) NOT NULL default '',
            parent_id int(11) NOT NULL default '0',
            parent_forum int(11) NOT NULL default '0',
            description varchar(255) NOT NULL default '',
            sort int(11) NOT NULL default '0',
            closed int(11) NOT NULL default '0',
            PRIMARY KEY  (id)
            ) $charset_collate;";

            $sql2 = "
            CREATE TABLE ".self::$table_threads." (
            id int(11) NOT NULL auto_increment,
            parent_id int(11) NOT NULL default '0',
            views int(11) NOT NULL default '0',
            name varchar(255) NOT NULL default '',
            status varchar(20) NOT NULL default 'normal_open',
            PRIMARY KEY  (id)
            ) $charset_collate;";

            $sql3 = "
            CREATE TABLE ".self::$table_posts." (
            id int(11) NOT NULL auto_increment,
            text longtext,
            parent_id int(11) NOT NULL default '0',
            date datetime NOT NULL default '0000-00-00 00:00:00',
            date_edit datetime NOT NULL default '0000-00-00 00:00:00',
            author_id int(11) NOT NULL default '0',
            uploads longtext,
            PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH.'wp-admin/includes/upgrade.php');

            dbDelta($sql1);
            dbDelta($sql2);
            dbDelta($sql3);

            update_option('asgarosforum_db_version', self::DATABASE_VERSION);
        }
    }
}

?>
