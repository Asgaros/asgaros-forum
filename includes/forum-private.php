<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPrivate {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;

		add_action('init', array($this, 'initialize'));
    }

	public function initialize() {
		add_filter('asgarosforum_filter_forum_status_options', array($this, 'add_forum_status_option'), 10, 1);
		add_filter('asgarosforum_overwrite_post_counter_cache', array($this, 'overwrite_post_counter_cache'), 10, 1);
		add_filter('asgarosforum_overwrite_topic_counter_cache', array($this, 'overwrite_topic_counter_cache'), 10, 1);
		add_filter('asgarosforum_overwrite_forum_status', array($this, 'overwrite_forum_status'), 10, 2);
    }

	public function add_forum_status_option($forum_status_options) {
		$forum_status_options['private'] = __('Private', 'asgaros-forum');

		return $forum_status_options;
	}

	public function overwrite_post_counter_cache($post_counters) {
		// Skip the overwriting-process if the current user is at least a moderator.
		if ($this->asgarosforum->permissions->isModerator('current')) {
			return $post_counters;
		}

		// Get counts for posts in own topics in all forums.
		$query = "SELECT t.`parent_id` AS `forum_id`, COUNT(*) AS `post_counter` FROM {$this->asgarosforum->tables->posts} AS p, {$this->asgarosforum->tables->topics} AS t WHERE p.`parent_id` = t.`id` AND t.`author_id` = %d AND t.`approved` = 1 GROUP BY t.`parent_id`;";
		$query = $this->asgarosforum->db->prepare($query, get_current_user_id());
		$results = $this->asgarosforum->db->get_results($query);

		// Prepare array for further processing.
		$own_topics_posts = array();

		foreach ($results as $result) {
			$own_topics_posts[$result->forum_id] = $result->post_counter;
		}

		// Overwrite post-counters for private forums.
		foreach ($post_counters as $key => $post_counter) {
			if ($this->is_private_forum($post_counter->forum_id)) {
				$post_counters[$key]->post_counter = isset($own_topics_posts[$post_counter->forum_id]) ? $own_topics_posts[$post_counter->forum_id] : 0;
			}
		}

		return $post_counters;
	}

	public function overwrite_topic_counter_cache($topic_counters) {
		// Skip the overwriting-process if the current user is at least a moderator.
		if ($this->asgarosforum->permissions->isModerator('current')) {
			return $topic_counters;
		}

		// Get counts for own topics in all forums.
		$query = "SELECT `parent_id` AS `forum_id`, COUNT(*) AS `topic_counter` FROM {$this->asgarosforum->tables->topics} WHERE `author_id` = %d AND `approved` = 1 GROUP BY `parent_id`;";
		$query = $this->asgarosforum->db->prepare($query, get_current_user_id());
		$results = $this->asgarosforum->db->get_results($query);

		// Prepare array for further processing.
		$own_topics = array();

		foreach ($results as $result) {
			$own_topics[$result->forum_id] = $result->topic_counter;
		}

		// Overwrite topic-counters for private forums.
		foreach ($topic_counters as $key => $topic_counter) {
			if ($this->is_private_forum($topic_counter->forum_id)) {
				$topic_counters[$key]->topic_counter = isset($own_topics[$topic_counter->forum_id]) ? $own_topics[$topic_counter->forum_id] : 0;
			}
		}

		return $topic_counters;
	}

	public function overwrite_forum_status($forum_status, $forum_id) {
		// Skip the overwriting-process if the current user is at least a moderator.
		if ($this->asgarosforum->permissions->isModerator('current')) {
			return $forum_status;
		}

		// Skip the overwriting-process if the current forum is not a private forum.
		if (!$this->is_private_forum($forum_id)) {
			return $forum_status;
		}

		// Guests cannot access private-topics, so the forum is marked as read.
		$user_id = get_current_user_id();

		if ($user_id === 0) {
			return 'read';
		}

		return $forum_status;





		// Try to find a post of an own topic inside a private (sub-)forum which has not been visited yet since last marking.

		// normal logic for nornal topics for guests
		


		// Prepare list with IDs of already visited topics.
		$visited_topics = "0";

		if (!empty($this->asgarosforum->unread->excluded_items) && !is_string($this->asgarosforum->unread->excluded_items)) {
			$visited_topics = implode(',', array_keys($this->asgarosforum->unread->excluded_items));
		}

		// Try to find a post in an own topic which has not been visited yet since last marking.
		$sql = "SELECT p.id FROM {$this->asgarosforum->tables->forums} AS f, {$this->asgarosforum->tables->topics} AS t, {$this->asgarosforum->tables->posts} AS p WHERE f.id = {$forum_id} AND t.parent_id = f.id AND p.parent_id = t.id AND p.parent_id NOT IN({$visited_topics}) AND p.date > '{$this->asgarosforum->unread->get_last_visit()}' AND t.author_id = {$user_id} AND p.author_id <> {$user_id} LIMIT 1;";

		$unread_check = $this->asgarosforum->db->get_results($sql);

		if (!empty($unread_check)) {
			return 'unread';
		}

		// Get last post of all topics which have been visited since last marking.
		$sql = "SELECT MAX(p.id) AS max_id, p.parent_id FROM {$this->asgarosforum->tables->forums} AS f, {$this->asgarosforum->tables->topics} AS t, {$this->asgarosforum->tables->posts} AS p WHERE f.id = {$forum_id} AND t.parent_id = f.id AND p.parent_id = t.id AND p.parent_id IN({$visited_topics}) AND t.author_id = {$user_id} AND p.author_id <> {$user_id} GROUP BY p.parent_id;";

		$unread_check = $this->asgarosforum->db->get_results($sql);

		if (!empty($unread_check)) {
			// Check for every visited topic if it contains a newer post.
			foreach ($unread_check as $key => $last_post) {
				if (isset($this->asgarosforum->unread->excluded_items[$last_post->parent_id]) && $last_post->max_id > $this->asgarosforum->unread->excluded_items[$last_post->parent_id]) {
					return 'unread';
				}
			}
		}

        return 'read';
	}

	private $cache_is_private_forum = array();

	public function is_private_forum($forum_id) {
		if (!isset($this->cache_is_private_forum[$forum_id])) {
			// Get private-status of all forums.
			$query = "SELECT `id`, `forum_status` FROM {$this->asgarosforum->tables->forums};";
			$results = $this->asgarosforum->db->get_results($query);

			// Set private-status for all forums.
			foreach ($results as $result) {
				if ($result->forum_status === 'private') {
					$this->cache_is_private_forum[$result->id] = true;
				} else {
					$this->cache_is_private_forum[$result->id] = false;
				}
			}
		}

		return $this->cache_is_private_forum[$forum_id];
	}
}
