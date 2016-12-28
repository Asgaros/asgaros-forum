<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumStatistics {
    public static function showStatistics() {
        global $asgarosforum;

        // Check if this functionality is enabled.
        if ($asgarosforum->options['show_statistics']) {
            $data = self::getData();
            echo '<div id="statistics">';
                echo '<div id="statistics-header">';
                    echo '<strong class="dashicons-before dashicons-chart-line">'.__('Statistics', 'asgaros-forum').'</strong>';
                echo '</div>';
                echo '<div id="statistics-body">';
                    echo '<div class="statistics-element">';
                        echo '<div class="element-number dashicons-before dashicons-editor-alignleft">'.$data->topics.'</div>';
                        echo '<div class="element-name">'.__('Topics', 'asgaros-forum').'</div>';
                    echo '</div>';
                    echo '<div class="statistics-element">';
                        echo '<div class="element-number dashicons-before dashicons-format-quote">'.$data->posts.'</div>';
                        echo '<div class="element-name">'.__('Posts', 'asgaros-forum').'</div>';
                    echo '</div>';
                    echo '<div class="statistics-element">';
                        echo '<div class="element-number dashicons-before dashicons-visibility">'.$data->views.'</div>';
                        echo '<div class="element-name">'.__('Views', 'asgaros-forum').'</div>';
                    echo '</div>';
                    echo '<div class="statistics-element">';
                        echo '<div class="element-number dashicons-before dashicons-groups">'.$data->users.'</div>';
                        echo '<div class="element-name">'.__('Users', 'asgaros-forum').'</div>';
                    echo '</div>';
                    do_action('asgarosforum_statistics_custom_element');
                echo '</div>';
                do_action('asgarosforum_statistics_custom_content_bottom');
                echo '<div class="clear"></div>';
            echo '</div>';
        }
    }

    public static function getData() {
        global $asgarosforum;
        $queryTopics = "SELECT COUNT(id) FROM {$asgarosforum->tables->topics}";
        $queryPosts = "SELECT COUNT(id) FROM {$asgarosforum->tables->posts}";
        $queryViews = "SELECT SUM(views) FROM {$asgarosforum->tables->topics}";
        $data = $asgarosforum->db->get_row("SELECT ({$queryTopics}) AS topics, ({$queryPosts}) AS posts, ({$queryViews}) AS views");
        $data->users = count_users()['total_users'];
        return $data;
    }
}
