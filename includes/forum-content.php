<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumContent {
    private static $asgarosforum = null;
    private static $taxonomyName = 'asgarosforum-category';
    private static $action = false;
    private static $dataSubject;
    private static $dataContent;

    public function __construct($object) {
		self::$asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
    }

    public function initialize() {
        self::initializeTaxonomy();
    }

    public static function initializeTaxonomy() {
        // Register the taxonomies.
        register_taxonomy(
			self::$taxonomyName,
			null,
			array(
				'public'        => false,
				'rewrite'       => false
			)
		);
    }

    public static function doInsertion() {
        if (self::prepareExecution()) {
            self::insertData();
        }
    }

    private static function getAction() {
        // If no action is set, try to determine one.
        if (!self::$action && ($_POST['submit_action'] === 'add_topic' || $_POST['submit_action'] === 'add_post' || $_POST['submit_action'] === 'edit_post')) {
            self::$action = $_POST['submit_action'];
        }

        return self::$action;
    }

    private static function setData() {
        if (isset($_POST['subject'])) {
            self::$dataSubject = apply_filters('asgarosforum_filter_subject_before_insert', trim($_POST['subject']));
        }

        if (isset($_POST['message'])) {
            self::$dataContent = apply_filters('asgarosforum_filter_content_before_insert', trim($_POST['message']));
        }
    }

    private static function prepareExecution() {
        global $asgarosforum;

        // Cancel if there is already an error.
        if (!empty($asgarosforum->error)) {
            return false;
        }

        // Cancel if the current user is not logged-in and guest postings are disabled.
        if (!is_user_logged_in() && !$asgarosforum->options['allow_guest_postings']) {
            $asgarosforum->error = __('You are not allowed to do this.', 'asgaros-forum');
            return false;
        }

        // Cancel if the current user is banned.
        if (AsgarosForumPermissions::isBanned('current')) {
            $asgarosforum->error = __('You are banned!', 'asgaros-forum');
            return false;
        }

        // Cancel if parents are not set. Prevents the creation of hidden content caused by spammers.
        if (!$asgarosforum->parents_set) {
            $asgarosforum->error = __('You are not allowed to do this.', 'asgaros-forum');
            return false;
        }

        // Cancel when no action could be determined.
        if (!self::getAction()) {
            $asgarosforum->error = __('You are not allowed to do this.', 'asgaros-forum');
            return false;
        }

        // Set the data.
        self::setData();

        // Cancel if the current user is not allowed to edit that post.
        if (self::getAction() === 'edit_post' && !AsgarosForumPermissions::isModerator('current') && AsgarosForumPermissions::$currentUserID != $asgarosforum->get_post_author($asgarosforum->current_post)) {
            $asgarosforum->error = __('You are not allowed to do this.', 'asgaros-forum');
            return false;
        }

        // Cancel if subject is empty.
        if ((self::getAction() === 'add_topic' || (self::getAction() === 'edit_post' && $asgarosforum->is_first_post($asgarosforum->current_post))) && empty(self::$dataSubject)) {
            $asgarosforum->info = __('You must enter a subject.', 'asgaros-forum');
            return false;
        }

        // Cancel if content is empty.
        if (empty(self::$dataContent)) {
            $asgarosforum->info = __('You must enter a message.', 'asgaros-forum');
            return false;
        }

        // Cancel when the file extension of uploads are not allowed.
        if (!AsgarosForumUploads::checkUploadsExtension()) {
            $asgarosforum->info = __('You are not allowed to upload files with that file extension.', 'asgaros-forum');
            return false;
        }

        // Cancel when the file size of uploads is too big.
        if (!AsgarosForumUploads::checkUploadsSize()) {
            $asgarosforum->info = __('You are not allowed to upload files with that file size.', 'asgaros-forum');
            return false;
        }

        // Do custom insert validation checks.
        $custom_check = apply_filters('asgarosforum_filter_insert_custom_validation', true);
        if (!$custom_check) {
            return false;
        }

        return true;
    }

    private static function insertData() {
        global $asgarosforum;

        $redirect = '';
        $uploadList = AsgarosForumUploads::getUploadList();
        $authorID = AsgarosForumPermissions::$currentUserID;

        if (self::getAction() === 'add_topic') {
            // Create the topic.
            $insertedIDs = self::insertTopic($asgarosforum->current_forum, self::$dataSubject, self::$dataContent, $authorID, $uploadList);

            // Assign the inserted IDs.
            $asgarosforum->current_topic = $insertedIDs->topic_id;
            $asgarosforum->current_post = $insertedIDs->post_id;

            // Upload files.
            AsgarosForumUploads::uploadFiles($asgarosforum->current_post, $uploadList);

            // Create redirect link.
            $redirect = html_entity_decode($asgarosforum->getLink('topic', $asgarosforum->current_topic, false, '#postid-'.$asgarosforum->current_post));

            // Send notification about new topic to global subscribers.
            AsgarosForumNotifications::notifyGlobalTopicSubscribers(self::$dataSubject, self::$dataContent, $redirect, AsgarosForumPermissions::$currentUserID);
        } else if (self::getAction() === 'add_post') {
            // Create the post.
            $asgarosforum->current_post = self::insertPost($asgarosforum->current_topic, self::$dataContent, $authorID, $uploadList);

            AsgarosForumUploads::uploadFiles($asgarosforum->current_post, $uploadList);

            $redirect = html_entity_decode($asgarosforum->get_postlink($asgarosforum->current_topic, $asgarosforum->current_post));

            // Send notification about new post to subscribers
            AsgarosForumNotifications::notifyTopicSubscribers(self::$dataContent, $redirect, AsgarosForumPermissions::$currentUserID);
        } else if (self::getAction() === 'edit_post') {
            $date = $asgarosforum->current_time();
            $uploadList = AsgarosForumUploads::uploadFiles($asgarosforum->current_post, $uploadList);
            $asgarosforum->db->update($asgarosforum->tables->posts, array('text' => self::$dataContent, 'uploads' => maybe_serialize($uploadList), 'date_edit' => $date, 'author_edit' => AsgarosForumPermissions::$currentUserID), array('id' => $asgarosforum->current_post), array('%s', '%s', '%s', '%d'), array('%d'));

            if ($asgarosforum->is_first_post($asgarosforum->current_post) && !empty(self::$dataSubject)) {
                $asgarosforum->db->update($asgarosforum->tables->topics, array('name' => self::$dataSubject), array('id' => $asgarosforum->current_topic), array('%s'), array('%d'));
            }

            $redirect = html_entity_decode($asgarosforum->get_postlink($asgarosforum->current_topic, $asgarosforum->current_post, $_POST['part_id']));
        }

        AsgarosForumNotifications::updateSubscriptionStatus();

        do_action('asgarosforum_after_'.self::getAction().'_submit', $asgarosforum->current_post, $asgarosforum->current_topic);

        wp_redirect($redirect);
        exit;
    }

    //======================================================================
    // FUNCTIONS FOR INSERTING CONTENT.
    //======================================================================

    // Inserts a new forum.
    public static function insertForum($categoryID, $name, $description, $parentForum, $icon, $order, $closed) {
        global $asgarosforum;

        // Get a slug for the new forum.
        $forum_slug = AsgarosForumRewrite::createUniqueSlug($name, $asgarosforum->tables->forums, 'forum');

        // Insert the forum.
        $asgarosforum->db->insert(
            $asgarosforum->tables->forums,
            array('name' => $name, 'parent_id' => $categoryID, 'parent_forum' => $parentForum, 'description' => $description, 'icon' => $icon, 'sort' => $order, 'closed' => $closed, 'slug' => $forum_slug),
            array('%s', '%d', '%d', '%s', '%s', '%d', '%d', '%s')
        );

        // Return the ID of the inserted forum.
        return $asgarosforum->db->insert_id;
    }

    // Inserts a new topic.
    public static function insertTopic($forumID, $name, $text, $authorID = false, $uploads = array()) {
        global $asgarosforum;

        // Set the author ID.
        if (!$authorID) {
            $authorID = AsgarosForumPermissions::$currentUserID;
        }

        // Get a slug for the new topic.
        $topic_slug = AsgarosForumRewrite::createUniqueSlug($name, $asgarosforum->tables->topics, 'topic');

        // Insert the topic.
        $asgarosforum->db->insert($asgarosforum->tables->topics, array('name' => $name, 'parent_id' => $forumID, 'slug' => $topic_slug), array('%s', '%d', '%s'));

        // Save the ID of the new topic.
        $insertedIDs = new stdClass;
        $insertedIDs->topic_id = $asgarosforum->db->insert_id;

        // Now create a post inside this topic and save its ID as well.
        $insertedIDs->post_id = self::insertPost($insertedIDs->topic_id, $text, $authorID, $uploads);

        // Return the IDs of the inserted content.
        return $insertedIDs;
    }

    // Inserts a new post.
    public static function insertPost($topicID, $text, $authorID = false, $uploads = array()) {
        global $asgarosforum;

        // Set the author ID.
        if (!$authorID) {
            $authorID = AsgarosForumPermissions::$currentUserID;
        }

        // Get the current time.
        $date = $asgarosforum->current_time();

        // Insert the post.
        $asgarosforum->db->insert($asgarosforum->tables->posts, array('text' => $text, 'parent_id' => $topicID, 'date' => $date, 'author_id' => $authorID, 'uploads' => maybe_serialize($uploads)), array('%s', '%d', '%s', '%d', '%s'));

        // Return the ID of the inserted post.
        return $asgarosforum->db->insert_id;
    }

    //======================================================================
    // FUNCTIONS TO CHECK IF SPECIFIC CONTENT EXISTS.
    //======================================================================

    // Checks if a category exists.
    public static function categoryExists($categoryID) {
        if ($categoryID) {
            $check = get_term($categoryID, 'asgarosforum-category');

            if ($check) {
                return true;
            }
        }

        return false;
    }

    // Checks if a forum exists.
    public static function forumExists($forumID) {
        global $asgarosforum;

        if ($forumID) {
            $check = $asgarosforum->db->get_var($asgarosforum->db->prepare("SELECT id FROM {$asgarosforum->tables->forums} WHERE id = %d", $forumID));

            if ($check) {
                return true;
            }
        }

        return false;
    }

    // Checks if a topic exists.
    public static function topicExists($topicID) {
        global $asgarosforum;

        if ($topicID) {
            $check = $asgarosforum->db->get_var($asgarosforum->db->prepare("SELECT id FROM {$asgarosforum->tables->topics} WHERE id = %d", $topicID));

            if ($check) {
                return true;
            }
        }

        return false;
    }

    // Checks if a post exists.
    public static function postExists($postID) {
        global $asgarosforum;

        if ($postID) {
            $check = $asgarosforum->db->get_var($asgarosforum->db->prepare("SELECT id FROM {$asgarosforum->tables->posts} WHERE id = %d", $postID));

            if ($check) {
                return true;
            }
        }

        return false;
    }

    //======================================================================
    // FUNCTIONS FOR GETTING CONTENT.
    //======================================================================

    public static function get_sticky_topics($forum_id) {
        global $asgarosforum;

        $order = apply_filters('asgarosforum_filter_get_sticky_topics_order', "(SELECT MAX(id) FROM {$asgarosforum->tables->posts} AS p WHERE p.parent_id = t.id) DESC");
        $results = $asgarosforum->db->get_results($asgarosforum->db->prepare("SELECT t.*, (SELECT author_id FROM {$asgarosforum->tables->posts} WHERE parent_id = t.id ORDER BY id ASC LIMIT 1) AS author_id, (SELECT (COUNT(*) - 1) FROM {$asgarosforum->tables->posts} WHERE parent_id = t.id) AS answers FROM {$asgarosforum->tables->topics} AS t WHERE t.parent_id = %d AND t.status LIKE 'sticky%' ORDER BY {$order};", $forum_id));
        $results = apply_filters('asgarosforum_filter_get_threads', $results);
        return $results;
    }

    public static function get_categories($enable_filtering = true) {
        $filter = array();
        $include = array();
        $metaQueryFilter = array();

        if ($enable_filtering) {
            $filter = apply_filters('asgarosforum_filter_get_categories', $filter);
            $metaQueryFilter = self::get_categories_filter();

            // Set include filter when extended shortcode is used.
            if (AsgarosForumShortcodes::$includeCategories) {
                $include = AsgarosForumShortcodes::$includeCategories;
            }
        }

        $categories = get_terms('asgarosforum-category', array('hide_empty' => false, 'exclude' => $filter, 'include' => $include, 'meta_query' => $metaQueryFilter));

        // Filter categories by usergroups.
        if ($enable_filtering) {
            $categories = AsgarosForumUserGroups::filterCategories($categories);
        }

        // Get information about ordering.
        foreach ($categories as $category) {
            $category->order = get_term_meta($category->term_id, 'order', true);
        }

        // Sort the categories based on ordering information.
        usort($categories, array('AsgarosForumContent', 'get_categories_compare'));

        return $categories;
    }

    public static function get_categories_filter() {
        $metaQueryFilter = array('relation' => 'AND');

        if (!AsgarosForumPermissions::isModerator('current')) {
            $metaQueryFilter[] = array(
                'key'       => 'category_access',
                'value'     => 'moderator',
                'compare'   => 'NOT LIKE'
            );
        }

        if (!is_user_logged_in()) {
            $metaQueryFilter[] = array(
                'key'       => 'category_access',
                'value'     => 'loggedin',
                'compare'   => 'NOT LIKE'
            );
        }

        if (sizeof($metaQueryFilter) > 1) {
            return $metaQueryFilter;
        } else {
            return array();
        }
    }

    public static function get_categories_compare($a, $b) {
        return ($a->order < $b->order) ? -1 : (($a->order > $b->order) ? 1 : 0);
    }

    // TODO: Remove redundant function in forum.php
    public static function get_topic($topic_id) {
        global $asgarosforum;

        return $asgarosforum->db->get_row("SELECT * FROM {$asgarosforum->tables->topics} WHERE id = {$topic_id};");
    }

    // TODO: Remove redundant function in forum.php
    public static function get_post($post_id) {
        global $asgarosforum;

        return $asgarosforum->db->get_row("SELECT p1.*, (SELECT COUNT(*) FROM {$asgarosforum->tables->posts} AS p2 WHERE p2.author_id = p1.author_id) AS author_posts FROM {$asgarosforum->tables->posts} AS p1 WHERE p1.id = {$post_id};");
    }
}

?>
