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
                add_user_meta($this->userID, 'asgarosforum_unread_cleared', '0000-00-00 00:00:00');
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
                setcookie('asgarosforum_unread_cleared', '0000-00-00 00:00:00', 2147483647);
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
            setcookie('asgarosforum_unread_cleared', $currentTime, 2147483647);
            unset($_COOKIE['asgarosforum_unread_exclude']);
            setcookie('asgarosforum_unread_exclude', '', time() - 3600);
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
            setcookie('asgarosforum_unread_exclude', maybe_serialize($this->excludedItems), 2147483647);
        }
    }

    public function getLastVisit() {
        if ($this->userID) {
            return get_user_meta($this->userID, 'asgarosforum_unread_cleared', true);
        } else if (isset($_COOKIE['asgarosforum_unread_cleared'])) {
            return $_COOKIE['asgarosforum_unread_cleared'];
        } else {
            return "0000-00-00 00:00:00";
        }
    }

    public function getStatus($lastpostData) {
        $status = 'read';

        if ($lastpostData) {
            $lastpostTime = $lastpostData->date;

            if ($lastpostTime) {
                $lp = strtotime($lastpostTime);
                $lv = strtotime($this->getLastVisit());

                if ($lp > $lv) {
                    $status = 'unread';
                }
            }
        }

        return $status;
    }

    public function getStatusForum($id, $topicsAvailable) {
        $lastpostData = null;
        $lastpostList = null;

        // Only do the checks when there are topics available.
        if ($topicsAvailable) {
            // Only ignore posts from the loggedin user because we cant determine if a post from a guest was created by the visiting guest.
            if ($this->userID) {
                $sql = $this->asgarosforum->db->prepare("SELECT p.id, p.date, p.parent_id FROM ".$this->asgarosforum->tables->posts." AS p INNER JOIN ".$this->asgarosforum->tables->topics." AS t ON p.parent_id = t.id INNER JOIN ".$this->asgarosforum->tables->forums." AS f ON t.parent_id = f.id WHERE p.id IN (SELECT MAX(p_inner.id) FROM ".$this->asgarosforum->tables->posts." AS p_inner GROUP BY p_inner.parent_id) AND p.author_id <> %d AND (f.id = %d OR f.parent_forum = %d) AND p.date > '%s' ORDER BY p.id DESC;", $this->userID, $id, $id, $this->getLastVisit());
                $lastpostList = $this->asgarosforum->db->get_results($sql);
            } else {
                $sql = $this->asgarosforum->db->prepare("SELECT p.id, p.date, p.parent_id FROM ".$this->asgarosforum->tables->posts." AS p INNER JOIN ".$this->asgarosforum->tables->topics." AS t ON p.parent_id = t.id INNER JOIN ".$this->asgarosforum->tables->forums." AS f ON t.parent_id = f.id WHERE p.id IN (SELECT MAX(p_inner.id) FROM ".$this->asgarosforum->tables->posts." AS p_inner GROUP BY p_inner.parent_id) AND (f.id = %d OR f.parent_forum = %d) AND p.date > '%s' ORDER BY p.id DESC;", $id, $id, $this->getLastVisit());
                $lastpostList = $this->asgarosforum->db->get_results($sql);
            }

            foreach ($lastpostList as $key => $lastpostListItem) {
                // This topic has not been opened yet, so it is actually the last post.
                if (!isset($this->excludedItems[$lastpostListItem->parent_id])) {
                    $lastpostData = $lastpostListItem;
                    break;
                }

                // This topic has been opened, but there is already a newer post, so it is actually the last post.
                if (isset($this->excludedItems[$lastpostListItem->parent_id]) && $lastpostListItem->id > $this->excludedItems[$lastpostListItem->parent_id]) {
                    $lastpostData = $lastpostListItem;
                    break;
                }
            }
        }

        return $this->getStatus($lastpostData);
    }

    public function getStatusTopic($id) {
        $lastpostData = $this->asgarosforum->get_lastpost_in_topic($id);

        // Set empty lastpostData for loggedin user when he is the author of the last post or when topic already read.
        if ($lastpostData) {
            if (($this->userID && $lastpostData->author_id == $this->userID) || (isset($this->excludedItems[$id]) && $this->excludedItems[$id] == $lastpostData->id)) {
                $lastpostData = null;
            }
        }

        return $this->getStatus($lastpostData);
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
