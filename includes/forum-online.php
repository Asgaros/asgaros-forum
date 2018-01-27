<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumOnline {
    private $asgarosforum = null;
    private $current_user_id = null;
    private $current_time_stamp = null;
    private $functionality_enabled = false;
    private $interval_update = 1;
    private $interval_online = 10;
    private $online_users = array();

    public function __construct($object) {
		$this->asgarosforum = $object;

        add_action('init', array($this, 'initialize'));
    }

    public function initialize() {
        $this->functionality_enabled = $this->asgarosforum->options['show_who_is_online'];
    }

    public function update_online_status() {
        if ($this->functionality_enabled) {
            $this->current_user_id = get_current_user_id();
            $this->current_time_stamp = $this->asgarosforum->current_time();

            // Only run timestamp logic for loggedin users.
            if ($this->current_user_id) {
                // Get the timestamp of the current user.
                $user_time_stamp = get_user_meta($this->current_user_id, 'asgarosforum_online_timestamp', true);

                // If there is no timestamp for that user of the update interval passed, create or update it.
                if (!$user_time_stamp || ((strtotime($this->current_time_stamp) - strtotime($user_time_stamp)) > ($this->interval_update * 60))) {
                    update_user_meta($this->current_user_id, 'asgarosforum_online_timestamp', $this->current_time_stamp);
                    $user_time_stamp = $this->current_time_stamp;
                }
            }

            // Load list of online users.
            $this->load_online_users();
        }
    }

    public function load_online_users() {
        $minimum_check_time = date_i18n('Y-m-d H:i:s', (strtotime($this->current_time_stamp) - ($this->interval_online * 60)));

        // Get list of online users.
        $this->online_users = get_users(
            array(
                'fields'        => 'id',
                'meta_query'    => array(
                    'relation'  => 'AND',
                    array(
                        'key'       => 'asgarosforum_online_timestamp',
                        'compare'   => 'EXISTS'
                    ),
                    array(
                        'key'       => 'asgarosforum_online_timestamp',
                        'value'     => $minimum_check_time,
                        'compare'   => '>='
                    )
                )
            )
        );
    }

    public function render_statistics_element() {
        if ($this->functionality_enabled) {
            AsgarosForumStatistics::renderStatisticsElement(__('Online', 'asgaros-forum'), count($this->online_users), 'dashicons-before dashicons-lightbulb');
        }
    }

    public function render_online_information() {
        if ($this->functionality_enabled) {
            $newest_member = get_users(array('orderby' => 'ID', 'order' => 'DESC', 'number' => 1));
            $currently_online = (!empty($this->online_users)) ? get_users(array('include' => $this->online_users)) : false;

            echo '<div id="statistics-online-users">';
            echo '<span class="dashicons-before dashicons-businessman">'.__('Newest Member:', 'asgaros-forum').'&nbsp;<i>'.$this->asgarosforum->renderUsername($newest_member[0]).'</i></span>';
            echo '&nbsp;&middot;&nbsp;';
            echo '<span class="dashicons-before dashicons-groups">';

            if ($currently_online) {
                echo __('Currently Online:', 'asgaros-forum').'&nbsp;<i>';

                $loop_counter = 0;

                foreach ($currently_online as $online_user) {
                    $loop_counter++;

                    if ($loop_counter > 1) {
                        echo ', ';
                    }

                    echo $this->asgarosforum->renderUsername($online_user);
                }

                echo '</i>';
            } else {
                echo '<i>'.__('Currently nobody is online.', 'asgaros-forum').'</i>';
            }

            echo '</span>';
            echo '</div>';
        }
    }

    public function is_user_online($user_id) {
        if ($this->functionality_enabled && in_array($user_id, $this->online_users)) {
            return true;
        } else {
            return false;
        }
    }

    public function delete_user_time_stamp() {
        delete_user_meta(get_current_user_id(), 'asgarosforum_online_timestamp');
    }
}
