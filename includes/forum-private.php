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

		// Overwrite topic-counters for private forums.
		foreach ($topic_counters as $key => $topic_counter) {
			if ($this->is_private_forum($topic_counter->forum_id)) {
				$query = "SELECT COUNT(*) FROM {$this->asgarosforum->tables->topics} WHERE `parent_id` = %d AND `author_id` = %d AND `approved` = 1;";
				$query = $this->asgarosforum->db->prepare($query, $topic_counter->forum_id, get_current_user_id());
				
				$topic_counters[$key]->topic_counter = $this->asgarosforum->db->get_var($query);
			}
		}

		return $topic_counters;
	}

	private $cache_is_private_forum = array();

	public function is_private_forum($forum_id) {
		if (!isset($this->cache_is_private_forum[$forum_id])) {
			$this->cache_is_private_forum[$forum_id] = false;

			$forum_status = $this->asgarosforum->db->get_var("SELECT forum_status FROM {$this->asgarosforum->tables->forums} WHERE id = {$forum_id};");
		
			if ($forum_status === 'private') {
				$this->cache_is_private_forum[$forum_id] = true;
			}
		}

		return $this->cache_is_private_forum[$forum_id];
	}
}
