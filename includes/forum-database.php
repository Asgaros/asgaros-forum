<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumDatabase {
    private $db;
    private $db_version = 20;
    private $tables;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->setTables();
        register_activation_hook(__FILE__, array($this, 'activatePlugin'));
        add_action('wpmu_new_blog', array($this, 'buildSubsite'), 10, 6);
        add_filter('wpmu_drop_tables', array($this, 'deleteSubsite'));
        add_action('plugins_loaded', array($this, 'buildDatabase'));
	}

    private function setTables() {
        $this->tables = new stdClass();
        $this->tables->forums       = $this->db->prefix.'forum_forums';
        $this->tables->topics       = $this->db->prefix.'forum_topics';
        $this->tables->posts        = $this->db->prefix.'forum_posts';
        $this->tables->reports      = $this->db->prefix.'forum_reports';
        $this->tables->reactions    = $this->db->prefix.'forum_reactions';
    }

    public function getTables() {
        return $this->tables;
    }

    public function activatePlugin($networkwide) {
        if (function_exists('is_multisite') && is_multisite()) {
            // Check if it is a network activation. If so, run the database-creation for each id.
            if ($networkwide) {
                $old_blog =  $this->db->blogid;

                // Get all blog ids
                $blogids = $this->db->get_col('SELECT blog_id FROM '.$this->db->blogs);

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
    public function buildSubsite($blog_id, $user_id, $domain, $path, $site_id, $meta) {
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
        $tables[] = $this->db->prefix.'forum_forums';
        $tables[] = $this->db->prefix.'forum_topics';
        $tables[] = $this->db->prefix.'forum_posts';
        $tables[] = $this->db->prefix.'forum_reports';
        $tables[] = $this->db->prefix.'forum_reactions';

        // Delete data which has been used in old versions of the plugin.
        $tables[] = $this->db->prefix.'forum_threads';
        return $tables;
    }

    public function buildDatabase() {
        global $asgarosforum;

        $database_version_installed = get_option('asgarosforum_db_version');

        if ($database_version_installed != $this->db_version) {
            // Rename old table.
            $renameTable = $this->db->get_results('SHOW TABLES LIKE "'.$this->db->prefix.'forum_threads";');
            if (!empty($renameTable)) {
                $this->db->query('RENAME TABLE '.$this->db->prefix.'forum_threads TO '.$this->tables->topics.';');
            }

            $charset_collate = $this->db->get_charset_collate();

            $sql = array();

            $sql[] = "CREATE TABLE ".$this->tables->forums." (
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

            $sql[] = "CREATE TABLE ".$this->tables->topics." (
            id int(11) NOT NULL auto_increment,
            parent_id int(11) NOT NULL default '0',
            views int(11) NOT NULL default '0',
            name varchar(255) NOT NULL default '',
            status varchar(20) NOT NULL default 'normal_open',
            slug varchar(255) NOT NULL default '',
            PRIMARY KEY  (id)
            ) $charset_collate;";

            $sql[] = "CREATE TABLE ".$this->tables->posts." (
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

            $sql[] = "CREATE TABLE ".$this->tables->reports." (
            post_id int(11) NOT NULL default '0',
            reporter_id int(11) NOT NULL default '0',
            PRIMARY KEY  (post_id, reporter_id)
            ) $charset_collate;";

            $sql[] = "CREATE TABLE ".$this->tables->reactions." (
            post_id int(11) NOT NULL default '0',
            user_id int(11) NOT NULL default '0',
            reaction varchar(20) NOT NULL default '',
            PRIMARY KEY  (post_id, user_id)
            ) $charset_collate;";

            require_once(ABSPATH.'wp-admin/includes/upgrade.php');

            dbDelta($sql);

            if ($database_version_installed < 5) {
                // Because most of the WordPress users are using a MySQL version below 5.6,
                // we have to set the ENGINE for the post-table to MyISAM because InnoDB doesnt
                // support FULLTEXT before MySQL version 5.6.
                $this->db->query('ALTER TABLE '.$this->tables->posts.' ENGINE = MyISAM;');
                $this->db->query('ALTER TABLE '.$this->tables->posts.' ADD FULLTEXT (text);');
            }

            // Create forum slugs.
            if ($database_version_installed < 6) {
                $forums = $this->db->get_results("SELECT id, name FROM ".$this->tables->forums." WHERE slug = '' ORDER BY id ASC;");

                foreach ($forums as $forum) {
                    $slug = $asgarosforum->rewrite->create_unique_slug($forum->name, $this->tables->forums, 'forum');
                    $this->db->update($this->tables->forums, array('slug' => $slug), array('id' => $forum->id), array('%s'), array('%d'));
                }
            }

            // Add index to posts.author_id to make countings faster.
            if ($database_version_installed < 11) {
                $this->db->query('ALTER TABLE '.$this->tables->posts.' ADD INDEX(author_id);');
            }

            // Add index to posts.parent_id for faster queries.
            if ($database_version_installed < 12) {
                $this->db->query('ALTER TABLE '.$this->tables->posts.' ADD INDEX(parent_id);');
            }

            // Add existing user groups to a default user groups category and/or create an example user group.
            if ($database_version_installed < 13) {
                // Initialize taxonomy first.
                AsgarosForumUserGroups::initializeTaxonomy();

                // Create a new example category first.
                $defaultCategoryName = __('Custom User Groups', 'asgaros-forum');
                $defaultCategory = AsgarosForumUserGroups::insertUserGroupCategory($defaultCategoryName);

                // Ensure that no error happened.
                if (!is_wp_error($defaultCategory)) {
                    // Now get all existing elements.
                    $existingCategories = AsgarosForumUserGroups::getUserGroupCategories();

                    // When there is only one element, then it is the newly created category.
                    if (count($existingCategories) > 1) {
                        // Move every existing user group into the new default category.
                        foreach ($existingCategories as $category) {
                            // But ensure to not move the new default category into it.
                            if ($category->term_id != $defaultCategory['term_id']) {
                                $color = AsgarosForumUserGroups::getUserGroupColor($category->term_id);
                                AsgarosForumUserGroups::updateUserGroup($category->term_id, $defaultCategory['term_id'], $category->name, $color);
                            }
                        }
                    } else {
                        // Add an example user group.
                        $defaultUserGroupName = __('Example User Group', 'asgaros-forum');
                        $defaultUserGroup = AsgarosForumUserGroups::insertUserGroup($defaultCategory['term_id'], $defaultUserGroupName, '#2d89cc');
                    }
                }
            }

            // Move appearance settings into its own options-array.
            if ($database_version_installed < 14) {
                // Ensure that all options are loaded first.
                $asgarosforum->loadOptions();
                $asgarosforum->appearance->load_options();

                // Build the intersect.
                $appearance_intersect = array_intersect_key($asgarosforum->options, $asgarosforum->appearance->options) + $asgarosforum->appearance->options;

                // Remove keys from old settings.
                $options_cleaned = array_diff_key($asgarosforum->options, $appearance_intersect);

                // Save all options.
                $asgarosforum->appearance->save_options($appearance_intersect);
                $asgarosforum->saveOptions($options_cleaned);
            }

            if ($database_version_installed < 19) {
                // Because most of the WordPress users are using a MySQL version below 5.6,
                // we have to set the ENGINE for the post-table to MyISAM because InnoDB doesnt
                // support FULLTEXT before MySQL version 5.6.
                $this->db->query('ALTER TABLE '.$this->tables->topics.' ENGINE = MyISAM;');
                $this->db->query('ALTER TABLE '.$this->tables->topics.' ADD FULLTEXT (name);');
            }

            // Create some default content.
            if ($database_version_installed < 20) {
                // Initialize taxonomy first.
                AsgarosForumContent::initialize_taxonomy();

                // Get all categories first.
                $categories = $asgarosforum->content->get_categories(false);

                // Only continue when there are no categories yet.
                if (count($categories) == 0) {
                    // Add an example category.
                    $default_category_name = __('Example Category', 'asgaros-forum');

                    $new_category = wp_insert_term($default_category_name, 'asgarosforum-category');

                    if (!is_wp_error($new_category)) {
                        update_term_meta($new_category['term_id'], 'category_access', 'everyone');
                        update_term_meta($new_category['term_id'], 'order', 1);

                        $default_forum_name = __('First Forum', 'asgaros-forum');
                        $default_forum_description = __('My first forum.', 'asgaros-forum');

                        $asgarosforum->content->insert_forum($new_category['term_id'], $default_forum_name, $default_forum_description, 0, 'dashicons-editor-justify', 1, 0);
                    }
                }
            }

            update_option('asgarosforum_db_version', $this->db_version);
        }
    }
}

?>
