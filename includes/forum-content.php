<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumContent {
    private $asgarosforum = null;
    private static $taxonomy_name = 'asgarosforum-category';
    private $action = false;
    private $data_subject;
    private $data_content;

    public function __construct($object) {
		$this->asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
    }

    public function initialize() {
        self::initialize_taxonomy();
    }

    public static function initialize_taxonomy() {
        // Register the taxonomies.
        register_taxonomy(
			self::$taxonomy_name,
			null,
			array(
				'public'        => false,
				'rewrite'       => false
			)
		);
    }

    public function do_insertion() {
        if ($this->prepare_execution()) {
            $this->insert_data();
        }
    }

    private function get_action() {
        // If no action is set, try to determine one.
        if (!$this->action && ($_POST['submit_action'] === 'add_topic' || $_POST['submit_action'] === 'add_post' || $_POST['submit_action'] === 'edit_post')) {
            $this->action = $_POST['submit_action'];
        }

        return $this->action;
    }

    private function set_data() {
        if (isset($_POST['subject'])) {
            $this->data_subject = apply_filters('asgarosforum_filter_subject_before_insert', trim($_POST['subject']));
        }

        if (isset($_POST['message'])) {
            $this->data_content = apply_filters('asgarosforum_filter_content_before_insert', trim($_POST['message']));
        }
    }

    private function prepare_execution() {
        // Cancel if there is already an error.
        if (!empty($this->asgarosforum->error)) {
            return false;
        }

        // Cancel if the current user is not logged-in and guest postings are disabled.
        if (!is_user_logged_in() && !$this->asgarosforum->options['allow_guest_postings']) {
            $this->asgarosforum->error = __('You are not allowed to do this.', 'asgaros-forum');
            return false;
        }

        // Cancel if the current user is banned.
        if (AsgarosForumPermissions::isBanned('current')) {
            $this->asgarosforum->error = __('You are banned!', 'asgaros-forum');
            return false;
        }

        // Cancel if parents are not set. Prevents the creation of hidden content caused by spammers.
        if (!$this->asgarosforum->parents_set) {
            $this->asgarosforum->error = __('You are not allowed to do this.', 'asgaros-forum');
            return false;
        }

        // Cancel when no action could be determined.
        if (!$this->get_action()) {
            $this->asgarosforum->error = __('You are not allowed to do this.', 'asgaros-forum');
            return false;
        }

        // Set the data.
        $this->set_data();

        // Cancel if the current user is not allowed to edit that post.
        if ($this->get_action() === 'edit_post') {
            $user_id = AsgarosForumPermissions::$currentUserID;

            if (!AsgarosForumPermissions::can_edit_post($user_id, $this->asgarosforum->current_post)) {
                $this->asgarosforum->error = __('You are not allowed to do this.', 'asgaros-forum');
                return false;
            }
        }

        // Cancel if the forum is closed for the current user.
        if ($this->get_action() === 'add_topic') {
            if (!$this->asgarosforum->forumIsOpen()) {
                $this->asgarosforum->error = __('You are not allowed to do this.', 'asgaros-forum');
                return false;
            }
        }

        // Cancel if the topic is closed and the user is not a moderator.
        if ($this->get_action() === 'add_post') {
            if ($this->asgarosforum->get_status('closed') && !AsgarosForumPermissions::isModerator('current')) {
                $this->asgarosforum->error = __('You are not allowed to do this.', 'asgaros-forum');
                return false;
            }
        }

        // Cancel if subject is empty.
        if (($this->get_action() === 'add_topic' || ($this->get_action() === 'edit_post' && $this->asgarosforum->is_first_post($this->asgarosforum->current_post))) && empty($this->data_subject)) {
            $this->asgarosforum->info = __('You must enter a subject.', 'asgaros-forum');
            return false;
        }

        // Cancel if content is empty.
        if (empty($this->data_content)) {
            $this->asgarosforum->info = __('You must enter a message.', 'asgaros-forum');
            return false;
        }

        // Cancel when the file extension of uploads are not allowed.
        if (!$this->asgarosforum->uploads->check_uploads_extension()) {
            $this->asgarosforum->info = __('You are not allowed to upload files with that file extension.', 'asgaros-forum');
            return false;
        }

        // Cancel when the file size of uploads is too big.
        if (!$this->asgarosforum->uploads->check_uploads_size()) {
            $this->asgarosforum->info = __('You are not allowed to upload files with that file size.', 'asgaros-forum');
            return false;
        }

        // Do custom insert validation checks.
        $custom_check = apply_filters('asgarosforum_filter_insert_custom_validation', true);
        if (!$custom_check) {
            return false;
        }

        return true;
    }

    private function insert_data() {
        $redirect = '';
        $upload_list = $this->asgarosforum->uploads->get_upload_list();
        $author_id = AsgarosForumPermissions::$currentUserID;

        if ($this->get_action() === 'add_topic') {
            // Create the topic.
            $inserted_ids = $this->insert_topic($this->asgarosforum->current_forum, $this->data_subject, $this->data_content, $author_id, $upload_list);

            // Assign the inserted IDs.
            $this->asgarosforum->current_topic = $inserted_ids->topic_id;
            $this->asgarosforum->current_post = $inserted_ids->post_id;

            // Upload files.
            $this->asgarosforum->uploads->upload_files($this->asgarosforum->current_post, $upload_list);

            // Create redirect link.
            $redirect = html_entity_decode($this->asgarosforum->get_link('topic', $this->asgarosforum->current_topic, false, '#postid-'.$this->asgarosforum->current_post));

            // Send notification about new topic.
            $this->asgarosforum->notifications->notify_about_new_topic($this->data_subject, $this->data_content, $redirect, AsgarosForumPermissions::$currentUserID);
        } else if ($this->get_action() === 'add_post') {
            // Create the post.
            $this->asgarosforum->current_post = $this->insert_post($this->asgarosforum->current_topic, $this->data_content, $author_id, $upload_list);

            $this->asgarosforum->uploads->upload_files($this->asgarosforum->current_post, $upload_list);

            $redirect = html_entity_decode($this->asgarosforum->get_postlink($this->asgarosforum->current_topic, $this->asgarosforum->current_post));

            // Send notification about new post.
            $this->asgarosforum->notifications->notify_about_new_post($this->data_content, $redirect, AsgarosForumPermissions::$currentUserID);
        } else if ($this->get_action() === 'edit_post') {
            $date = $this->asgarosforum->current_time();
            $upload_list = $this->asgarosforum->uploads->upload_files($this->asgarosforum->current_post, $upload_list);
            $this->asgarosforum->db->update($this->asgarosforum->tables->posts, array('text' => $this->data_content, 'uploads' => maybe_serialize($upload_list), 'date_edit' => $date, 'author_edit' => AsgarosForumPermissions::$currentUserID), array('id' => $this->asgarosforum->current_post), array('%s', '%s', '%s', '%d'), array('%d'));

            if ($this->asgarosforum->is_first_post($this->asgarosforum->current_post) && !empty($this->data_subject)) {
                $this->asgarosforum->db->update($this->asgarosforum->tables->topics, array('name' => $this->data_subject), array('id' => $this->asgarosforum->current_topic), array('%s'), array('%d'));
            }

            $redirect = html_entity_decode($this->asgarosforum->get_postlink($this->asgarosforum->current_topic, $this->asgarosforum->current_post, $_POST['part_id']));
        }

        $this->asgarosforum->notifications->update_topic_subscription_status($this->asgarosforum->current_topic);

        do_action('asgarosforum_after_'.$this->get_action().'_submit', $this->asgarosforum->current_post, $this->asgarosforum->current_topic, $this->data_subject, $this->data_content, $redirect);

        wp_redirect($redirect);
        exit;
    }

    //======================================================================
    // FUNCTIONS FOR INSERTING CONTENT.
    //======================================================================

    // Inserts a new forum.
    public function insert_forum($category_id, $name, $description, $parent_forum, $icon, $order, $closed) {
        // Get a slug for the new forum.
        $forum_slug = $this->asgarosforum->rewrite->create_unique_slug($name, $this->asgarosforum->tables->forums, 'forum');

        // Insert the forum.
        $this->asgarosforum->db->insert(
            $this->asgarosforum->tables->forums,
            array('name' => $name, 'parent_id' => $category_id, 'parent_forum' => $parent_forum, 'description' => $description, 'icon' => $icon, 'sort' => $order, 'closed' => $closed, 'slug' => $forum_slug),
            array('%s', '%d', '%d', '%s', '%s', '%d', '%d', '%s')
        );

        // Return the ID of the inserted forum.
        return $this->asgarosforum->db->insert_id;
    }

    // Inserts a new topic.
    public function insert_topic($forum_id, $name, $text, $author_id = false, $uploads = array()) {
        // Set the author ID.
        if (!$author_id) {
            $author_id = AsgarosForumPermissions::$currentUserID;
        }

        // Get a slug for the new topic.
        $topic_slug = $this->asgarosforum->rewrite->create_unique_slug($name, $this->asgarosforum->tables->topics, 'topic');

        // Insert the topic.
        $this->asgarosforum->db->insert($this->asgarosforum->tables->topics, array('name' => $name, 'parent_id' => $forum_id, 'slug' => $topic_slug), array('%s', '%d', '%s'));

        // Save the ID of the new topic.
        $inserted_ids = new stdClass;
        $inserted_ids->topic_id = $this->asgarosforum->db->insert_id;

        // Now create a post inside this topic and save its ID as well.
        $inserted_ids->post_id = $this->insert_post($inserted_ids->topic_id, $text, $author_id, $uploads);

        // Return the IDs of the inserted content.
        return $inserted_ids;
    }

    // Inserts a new post.
    public function insert_post($topic_id, $text, $author_id = false, $uploads = array()) {
        // Set the author ID.
        if (!$author_id) {
            $author_id = AsgarosForumPermissions::$currentUserID;
        }

        // Get the current time.
        $date = $this->asgarosforum->current_time();

        // Insert the post.
        $this->asgarosforum->db->insert($this->asgarosforum->tables->posts, array('text' => $text, 'parent_id' => $topic_id, 'date' => $date, 'author_id' => $author_id, 'uploads' => maybe_serialize($uploads)), array('%s', '%d', '%s', '%d', '%s'));

        // Return the ID of the inserted post.
        return $this->asgarosforum->db->insert_id;
    }

    //======================================================================
    // FUNCTIONS TO CHECK IF SPECIFIC CONTENT EXISTS.
    //======================================================================

    // Checks if a category exists.
    public function category_exists($category_id) {
        if ($category_id) {
            $check = get_term($category_id, 'asgarosforum-category');

            if ($check) {
                return true;
            }
        }

        return false;
    }

    // Checks if a forum exists.
    public function forum_exists($forum_id) {
        if ($forum_id) {
            $check = $this->asgarosforum->db->get_var($this->asgarosforum->db->prepare("SELECT id FROM {$this->asgarosforum->tables->forums} WHERE id = %d", $forum_id));

            if ($check) {
                return true;
            }
        }

        return false;
    }

    // Checks if a topic exists.
    public function topic_exists($topic_id) {
        if ($topic_id) {
            $check = $this->asgarosforum->db->get_var($this->asgarosforum->db->prepare("SELECT id FROM {$this->asgarosforum->tables->topics} WHERE id = %d", $topic_id));

            if ($check) {
                return true;
            }
        }

        return false;
    }

    // Checks if a post exists.
    public function post_exists($post_id) {
        if ($post_id) {
            $check = $this->asgarosforum->db->get_var($this->asgarosforum->db->prepare("SELECT id FROM {$this->asgarosforum->tables->posts} WHERE id = %d", $post_id));

            if ($check) {
                return true;
            }
        }

        return false;
    }

    //======================================================================
    // FUNCTIONS FOR GETTING CONTENT.
    //======================================================================

    public function get_sticky_topics($forum_id) {
        $order = apply_filters('asgarosforum_filter_get_sticky_topics_order', "(SELECT MAX(id) FROM {$this->asgarosforum->tables->posts} AS p WHERE p.parent_id = t.id) DESC");
        $results = $this->asgarosforum->db->get_results($this->asgarosforum->db->prepare("SELECT t.*, (SELECT author_id FROM {$this->asgarosforum->tables->posts} WHERE parent_id = t.id ORDER BY id ASC LIMIT 1) AS author_id, (SELECT (COUNT(*) - 1) FROM {$this->asgarosforum->tables->posts} WHERE parent_id = t.id) AS answers FROM {$this->asgarosforum->tables->topics} AS t WHERE t.parent_id = %d AND t.status LIKE 'sticky%' ORDER BY {$order};", $forum_id));
        $results = apply_filters('asgarosforum_filter_get_threads', $results);
        return $results;
    }

    public function get_categories($enable_filtering = true) {
        $filter = array();
        $include = array();
        $meta_query_filter = array();

        if ($enable_filtering) {
            $filter = apply_filters('asgarosforum_filter_get_categories', $filter);
            $meta_query_filter = $this->get_categories_filter();

            // Set include filter when extended shortcode is used.
            if ($this->asgarosforum->shortcode->includeCategories) {
                $include = $this->asgarosforum->shortcode->includeCategories;
            }
        }

        $categories = get_terms('asgarosforum-category', array('hide_empty' => false, 'exclude' => $filter, 'include' => $include, 'meta_query' => $meta_query_filter));

        // Filter categories by usergroups.
        if ($enable_filtering) {
            $categories = AsgarosForumUserGroups::filterCategories($categories);
        }

        // Get information about ordering.
        foreach ($categories as $category) {
            $category->order = get_term_meta($category->term_id, 'order', true);
        }

        // Sort the categories based on ordering information.
        usort($categories, array($this, 'get_categories_compare'));

        return $categories;
    }

    public function get_categories_filter() {
        $meta_query_filter = array('relation' => 'AND');

        if (!AsgarosForumPermissions::isModerator('current')) {
            $meta_query_filter[] = array(
                'key'       => 'category_access',
                'value'     => 'moderator',
                'compare'   => 'NOT LIKE'
            );
        }

        if (!is_user_logged_in()) {
            $meta_query_filter[] = array(
                'key'       => 'category_access',
                'value'     => 'loggedin',
                'compare'   => 'NOT LIKE'
            );
        }

        if (sizeof($meta_query_filter) > 1) {
            return $meta_query_filter;
        } else {
            return array();
        }
    }

    public function get_categories_compare($a, $b) {
        return ($a->order < $b->order) ? -1 : (($a->order > $b->order) ? 1 : 0);
    }

    // TODO: Remove redundant function in forum.php
    public function get_topic($topic_id) {
        return $this->asgarosforum->db->get_row("SELECT * FROM {$this->asgarosforum->tables->topics} WHERE id = {$topic_id};");
    }

    public function get_post($post_id) {
        return $this->asgarosforum->db->get_row("SELECT p1.*, (SELECT COUNT(*) FROM {$this->asgarosforum->tables->posts} AS p2 WHERE p2.author_id = p1.author_id) AS author_posts FROM {$this->asgarosforum->tables->posts} AS p1 WHERE p1.id = {$post_id};");
    }

    public function get_forum($forum_id) {
        return $this->asgarosforum->db->get_row("SELECT * FROM {$this->asgarosforum->tables->forums} WHERE id = {$forum_id};");
    }
}

?>
