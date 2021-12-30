<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumPrivate {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;

		add_action('init', array($this, 'initialize'));
    }

	public function initialize() {
        add_filter('asgarosforum_filter_forum_status_options', array($this, 'add_forum_status_option'));
    }

	public function add_forum_status_option($forum_status_options) {
		$forum_status_options['private'] = __('Private', 'asgaros-forum');

		return $forum_status_options;
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
