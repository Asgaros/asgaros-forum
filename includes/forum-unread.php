<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumUnread {
    private $asgarosforum = null;
    private $userID;
    private $excludedItems = array();

    public function __construct($object) {
        $this->asgarosforum = $object;
    }

    public function prepareUnreadStatus() {
        // Determine with the user ID if the user is logged in.
        $this->userID = get_current_user_id();

        // Initialize data. For guests we use a cookie as source, otherwise use database.
        if ($this->userID) {
            // Create database entry when it does not exist.
            if (!get_user_meta($this->userID, 'asgarosforum_unread_cleared', true)) {
                add_user_meta($this->userID, 'asgarosforum_unread_cleared', '1000-01-01 00:00:00');
            }

            // Get IDs of excluded topics.
            $items = get_user_meta($this->userID, 'asgarosforum_unread_exclude', true);

            // Only add it to the exclude-list when the result is not empty because otherwise the array is converted to a string.
            if (!empty($items)) {
                $this->excludedItems = $items;
            }
        } else {
            // Create a cookie when it does not exist.
            if (!isset($_COOKIE['asgarosforum_unread_cleared'])) {
                // There is no cookie set so basically the forum has never been visited.
                setcookie('asgarosforum_unread_cleared', '1000-01-01 00:00:00', 2147483647, COOKIEPATH, COOKIE_DOMAIN);
            }

            // Get IDs of excluded topics.
            if (isset($_COOKIE['asgarosforum_unread_exclude'])) {
                $this->excludedItems = maybe_unserialize($_COOKIE['asgarosforum_unread_exclude']);
            }
        }
    }

    public function markAllRead() {
        $currentTime = $this->asgarosforum->current_time();

        if ($this->userID) {
            update_user_meta($this->userID, 'asgarosforum_unread_cleared', $currentTime);
            delete_user_meta($this->userID, 'asgarosforum_unread_exclude');
        } else {
            setcookie('asgarosforum_unread_cleared', $currentTime, 2147483647, COOKIEPATH, COOKIE_DOMAIN);
            unset($_COOKIE['asgarosforum_unread_exclude']);
            setcookie('asgarosforum_unread_exclude', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }

        // Redirect to the forum overview.
        wp_redirect(html_entity_decode($this->asgarosforum->get_link('home')));
        exit;
    }

    // Marks a topic as read when an user opens it.
    public function markTopicRead() {
        $this->excludedItems[$this->asgarosforum->current_topic] = intval($this->asgarosforum->get_lastpost_in_topic($this->asgarosforum->current_topic)->id);

        if ($this->userID) {
            update_user_meta($this->userID, 'asgarosforum_unread_exclude', $this->excludedItems);
        } else {
            setcookie('asgarosforum_unread_exclude', maybe_serialize($this->excludedItems), 2147483647, COOKIEPATH, COOKIE_DOMAIN);
        }
    }

    public function getLastVisit() {
        if ($this->userID) {
            return get_user_meta($this->userID, 'asgarosforum_unread_cleared', true);
        } else if (isset($_COOKIE['asgarosforum_unread_cleared'])) {
            return $_COOKIE['asgarosforum_unread_cleared'];
        } else {
            return "1000-01-01 00:00:00";
        }
    }

    public function getStatusForum($id, $topicsAvailable) {
        // Only do the checks when there are topics available.
        if ($topicsAvailable) {
            // Prepare list with IDs of already visited topics.
            $visited_topics = "0";

            if (!empty($this->excludedItems)) {
                $visited_topics = implode(',', array_keys($this->excludedItems));
            }

            // Try to find a post in a topic which has not been visited yet since last marking.
            $sql = "";

            // We need to use slightly different queries here because we cant determine if a post was created by the visiting guest.
            if ($this->userID) {
                $sql = "SELECT p.id FROM {$this->asgarosforum->tables->forums} AS f, {$this->asgarosforum->tables->topics} AS t, {$this->asgarosforum->tables->posts} AS p WHERE (f.id = {$id} OR f.parent_forum = {$id}) AND t.parent_id = f.id AND p.parent_id = t.id AND p.parent_id NOT IN({$visited_topics}) AND p.author_id <> {$this->userID} AND p.date > '{$this->getLastVisit()}' LIMIT 1;";
            } else {
                $sql = "SELECT p.id FROM {$this->asgarosforum->tables->forums} AS f, {$this->asgarosforum->tables->topics} AS t, {$this->asgarosforum->tables->posts} AS p WHERE (f.id = {$id} OR f.parent_forum = {$id}) AND t.parent_id = f.id AND p.parent_id = t.id AND p.parent_id NOT IN({$visited_topics}) AND p.date > '{$this->getLastVisit()}' LIMIT 1;";
            }

            $unread_check = $this->asgarosforum->db->get_results($sql);

            if (!empty($unread_check)) {
                return 'unread';
            }

            // Get last post of all topics which have been visited since last marking.
            $sql = "";

            // Again we need to use slightly different queries here because we cant determine if a post was created by the visiting guest.
            if ($this->userID) {
                $sql = "SELECT MAX(p.id) AS max_id, p.parent_id FROM {$this->asgarosforum->tables->forums} AS f, {$this->asgarosforum->tables->topics} AS t, {$this->asgarosforum->tables->posts} AS p WHERE (f.id = {$id} OR f.parent_forum = {$id}) AND t.parent_id = f.id AND p.parent_id = t.id AND p.parent_id IN({$visited_topics}) AND p.author_id <> {$this->userID} GROUP BY p.parent_id;";
            } else {
                $sql = "SELECT MAX(p.id) AS max_id, p.parent_id FROM {$this->asgarosforum->tables->forums} AS f, {$this->asgarosforum->tables->topics} AS t, {$this->asgarosforum->tables->posts} AS p WHERE (f.id = {$id} OR f.parent_forum = {$id}) AND t.parent_id = f.id AND p.parent_id = t.id AND p.parent_id IN({$visited_topics}) GROUP BY p.parent_id;";
            }

            $unread_check = $this->asgarosforum->db->get_results($sql);

            if (!empty($unread_check)) {
                // Check for every visited topic if it contains a newer post.
                foreach ($unread_check as $key => $last_post) {
                    if (isset($this->excludedItems[$last_post->parent_id]) && $last_post->max_id > $this->excludedItems[$last_post->parent_id]) {
                        return 'unread';
                    }
                }
            }
        }

        return 'read';
    }

    public function getStatusTopic($topic_id) {
        $lastpost = $this->asgarosforum->get_lastpost_in_topic($topic_id);

        // Set empty lastpostData for loggedin user when he is the author of the last post or when topic already read.
        if ($lastpost) {
            return $this->get_post_status($lastpost->id, $lastpost->author_id, $lastpost->date, $topic_id);
        }

        return 'unread';
    }

    public function get_post_status($post_id, $post_author, $post_date, $topic_id) {
        // If post has been written before last read-marker: read
        $date_post = strtotime($post_date);
        $date_visit = strtotime($this->getLastVisit());

        if ($date_post < $date_visit) {
            return 'read';
        }

        // If post has been written from visitor: read
        if ($this->userID && $post_author == $this->userID) {
            return 'read';
        }

        // If the same or a newer post in this topic has already been read: read
        if (isset($this->excludedItems[$topic_id]) && $this->excludedItems[$topic_id] >= $post_id) {
            return 'read';
        }

        // In all other cases the post has not been read yet.
        return 'unread';
    }

    public function showUnreadControls() {
        echo '<div id="read-unread">';
            echo '<span class="indicator unread"></span>';
            echo '<span class="indicator-label">'.__('New posts', 'asgaros-forum').'</span>';
            echo '<span class="indicator read"></span>';
            echo '<span class="indicator-label">'.__('Nothing new', 'asgaros-forum').'</span>';
            echo '<span class="dashicons-before dashicons-yes"></span>';
            echo '<span class="indicator-label"><a href="'.$this->asgarosforum->get_link('markallread').'">'.__('Mark All Read', 'asgaros-forum').'</a></span>';

            echo '<div class="clear"></div>';
        echo '</div>';
    }
}
