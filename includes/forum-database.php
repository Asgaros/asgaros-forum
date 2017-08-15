<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumDatabase {
    const DATABASE_VERSION = 12;

    private static $instance = null;
    private static $db;
    private static $table_forums;
    private static $table_topics;
    private static $table_posts;

    // AsgarosForumDatabase instance creator
	public static function createInstance() {
		if (self::$instance === null) {
			self::$instance = new self;
		}

        return self::$instance;
	}

    // AsgarosForumDatabase constructor
	private function __construct() {
        global $wpdb;
        self::$db = $wpdb;
        $this->setTables();
        register_activation_hook(__FILE__, array($this, 'activatePlugin'));
        add_action('wpmu_new_blog', array($this, 'buildSubsite'), 10, 6);
        add_filter('wpmu_drop_tables', array($this, 'deleteSubsite'));
        add_action('plugins_loaded', array($this, 'buildDatabase'));
	}

    private function setTables() {
        self::$table_forums = self::$db->prefix.'forum_forums';
        self::$table_topics = self::$db->prefix.'forum_topics';
        self::$table_posts  = self::$db->prefix.'forum_posts';
    }

    public static function getTables() {
        $tables = new stdClass();
        $tables->forums = self::$table_forums;
        $tables->topics = self::$table_topics;
        $tables->posts = self::$table_posts;
        return $tables;
    }

    public static function activatePlugin($networkwide) {
        if (function_exists('is_multisite') && is_multisite()) {
            // Check if it is a network activation. If so, run the database-creation for each id.
            if ($networkwide) {
                $old_blog =  self::$db->blogid;

                // Get all blog ids
                $blogids = self::$db->get_col('SELECT blog_id FROM '.self::$db->blogs);

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
        $tables[] = self::$db->prefix.'forum_forums';
        $tables[] = self::$db->prefix.'forum_topics';
        $tables[] = self::$db->prefix.'forum_posts';

        // Delete data which has been used in old versions of the plugin.
        $tables[] = self::$db->prefix.'forum_threads';
        return $tables;
    }

    public static function buildDatabase() {
        global $asgarosforum;

        $database_version_installed = get_option('asgarosforum_db_version');

        if ($database_version_installed != self::DATABASE_VERSION) {
            // Rename old table.
            $renameTable = self::$db->get_results('SHOW TABLES LIKE "'.self::$db->prefix.'forum_threads";');
            if (!empty($renameTable)) {
                self::$db->query('RENAME TABLE '.self::$db->prefix.'forum_threads TO '.self::$table_topics.';');
            }

            $charset_collate = self::$db->get_charset_collate();

            $sql1 = "
            CREATE TABLE ".self::$table_forums." (
            id int(11) NOT NULL auto_increment,
            name varchar(255) NOT NULL default '',
            parent_id int(11) NOT NULL default '0',
            parent_forum int(11) NOT NULL default '0',
            description varchar(255) NOT NULL default '',
            icon varchar(255) NOT NULL default '',
            sort int(11) NOT NULL default '0',
            closed int(11) NOT NULL default '0',
            slug varchar(255) NOT NULL default '',
            PRIMARY KEY  (id)
            ) $charset_collate;";

            $sql2 = "
            CREATE TABLE ".self::$table_topics." (
            id int(11) NOT NULL auto_increment,
            parent_id int(11) NOT NULL default '0',
            views int(11) NOT NULL default '0',
            name varchar(255) NOT NULL default '',
            status varchar(20) NOT NULL default 'normal_open',
            slug varchar(255) NOT NULL default '',
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
            author_edit int(11) NOT NULL default '0',
            uploads longtext,
            PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH.'wp-admin/includes/upgrade.php');

            dbDelta($sql1);
            dbDelta($sql2);
            dbDelta($sql3);

            if ($database_version_installed < 5) {
                // Because most of the WordPress users are using a MySQL version below 5.6,
                // we have to set the ENGINE for the post-table to MyISAM because InnoDB doesnt
                // support FULLTEXT before MySQL version 5.6.
                self::$db->query('ALTER TABLE '.self::$table_posts.' ENGINE = MyISAM;');
                self::$db->query('ALTER TABLE '.self::$table_posts.' ADD FULLTEXT (text);');
            }

            // Create forum slugs.
            if ($database_version_installed < 6) {
                $forums = self::$db->get_results("SELECT id, name FROM ".self::$table_forums." WHERE slug = '' ORDER BY id ASC;");

                foreach ($forums as $forum) {
                    $slug = AsgarosForumRewrite::createUniqueSlug($forum->name, self::$table_forums, 'forum');
                    self::$db->update(self::$table_forums, array('slug' => $slug), array('id' => $forum->id), array('%s'), array('%d'));
                }
            }

            // Add index to posts.author_id to make countings faster.
            if ($database_version_installed < 11) {
                self::$db->query('ALTER TABLE '.self::$table_posts.' ADD INDEX(author_id);');
            }

            // Add index to posts.parent_id for faster queries.
            if ($database_version_installed < 12) {
                self::$db->query('ALTER TABLE '.self::$table_posts.' ADD INDEX(parent_id);');
            }

            update_option('asgarosforum_db_version', self::DATABASE_VERSION);
        }
    }
}

?>
