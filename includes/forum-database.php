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
        $this->setTables();
        register_activation_hook(__FILE__, array($this, 'activatePlugin'));
        add_action('wpmu_new_blog', array($this, 'buildSubsite'));
        add_filter('wpmu_drop_tables', array($this, 'deleteSubsite'));
        add_action('plugins_loaded', array($this, 'buildDatabase'));
	}

    private function setTables() {
        global $wpdb;

        self::$table_forums     = $wpdb->prefix.'forum_forums';
        self::$table_threads    = $wpdb->prefix.'forum_threads';
        self::$table_posts      = $wpdb->prefix.'forum_posts';
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

    public static function activatePlugin($networkwide) {
        global $wpdb;

        if (function_exists('is_multisite') && is_multisite()) {
            // Check if it is a network activation. If so, run the database-creation for each id.
            if ($networkwide) {
                $old_blog =  $wpdb->blogid;

                // Get all blog ids
                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

                foreach ($blogids as $blog_id) {
                    switch_to_blog($blog_id);
                    $this->setTables();
                    $this->buildDatabase();
                }

                switch_to_blog($old_blog);
                $this->setTables();
            }
        } else {
            $this->buildDatabase();
        }
    }

    // Create tables for a new subsite in a multisite installation.
    public static function buildSubsite($blog_id, $user_id, $domain, $path, $site_id, $meta) {
        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH.'/wp-admin/includes/plugin.php');
        }

        if (is_plugin_active_for_network('asgaros-forum/asgaros-forum.php')) {
            switch_to_blog($blog_id);
            $this->setTables();
            $this->buildDatabase();
            restore_current_blog();
            $this->setTables();
        }
    }

    // Delete tables during a subsite uninstall.
    public function deleteSubsite($tables) {
        global $wpdb;
        $tables[] = $wpdb->prefix.'forum_forums';
        $tables[] = $wpdb->prefix.'forum_threads';
        $tables[] = $wpdb->prefix.'forum_posts';
        return $tables;
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
