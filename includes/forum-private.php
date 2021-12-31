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
		add_filter('asgarosforum_overwrite_topic_counter_cache', array($this, 'overwrite_topic_counter_cache'), 10, 1);
    }

	public function add_forum_status_option($forum_status_options) {
		$forum_status_options['private'] = __('Private', 'asgaros-forum');

		return $forum_status_options;
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
