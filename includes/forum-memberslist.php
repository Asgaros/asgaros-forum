<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumMembersList {
    public static function renderMembersListLink() {
        global $asgarosforum;

        if ($asgarosforum->options['enable_memberslist']) {
            $membersLink = $asgarosforum->getLink('members');
            $membersLink = apply_filters('asgarosforum_filter_members_link', $membersLink);

            echo '<a class="members-link" href="'.$membersLink.'">'.__('Members', 'asgaros-forum').'</a>';
        }
    }

    public static function showMembersList() {
        global $asgarosforum;

        $pagination = new AsgarosForumPagination($asgarosforum);
        $paginationRendering = '<div class="pages-and-menu">'.$pagination->renderPagination('members').'<div class="clear"></div></div>';

        echo $paginationRendering;

        echo '<div class="title-element"></div>';
        echo '<div class="content-element">';

        $showAvatars = get_option('show_avatars');

        $data = self::getMembers();

        $start = $asgarosforum->current_page * 25;
        $end = 25;

        $dataSliced = array_slice($data, $start, $end);

        foreach ($dataSliced as $element) {
            $userOnline = (AsgarosForumOnline::isUserOnline($element->ID)) ? ' user-online' : '';

            echo '<div class="member'.$userOnline.'">';
                if ($showAvatars) {
                    echo '<div class="member-avatar">';
                    echo get_avatar($element->ID, 50);
                    echo '</div>';
                }

                echo '<div class="member-name">';
                echo $asgarosforum->getUsername($element->ID);
                echo '</div>';
                echo '<div class="member-posts">';
                $member_posts_i18n = number_format_i18n($element->forum_posts);
                echo sprintf(_n('%s Post', '%s Posts', $element->forum_posts, 'asgaros-forum'), $member_posts_i18n);
                echo '</div>';
            echo '</div>';
        }

        echo '</div>';

        echo $paginationRendering;
    }

    public static function getMembers() {
        global $asgarosforum, $wpdb;

        // Some default variables.
        $allUsersSearchable = array();
        $postsCounterSearchable = array();

        // Get all existing users.
        $allUsers = get_users();

        // Change the structure of the results for better searchability.
        foreach ($allUsers as $user) {
            $allUsersSearchable[$user->ID] = $user;
        }

        // Now get the amount of forum posts for all users.
        $postsCounter = $wpdb->get_results("SELECT author_id, COUNT(id) AS counter FROM {$asgarosforum->tables->posts} GROUP BY author_id ORDER BY COUNT(id) DESC;");

        // Change the structure of the results for better searchability.
        foreach ($postsCounter as $postCounter) {
            $postsCounterSearchable[$postCounter->author_id] = $postCounter->counter;
        }

        // Now add the numbers of posts to the users array when they are listed in the post counter.
        foreach ($allUsersSearchable as $key => $user) {
            if (isset($postsCounterSearchable[$key])) {
                $allUsersSearchable[$key]->forum_posts = $postsCounterSearchable[$key];
            } else {
                $allUsersSearchable[$key]->forum_posts = 0;
            }
        }

        // Obtain a list of columns for array_multisort().
        $columnForumPosts = array();
        $columnDisplayName = array();

        foreach ($allUsersSearchable as $key => $user) {
            $columnForumPosts[$key] = $user->forum_posts;
            $columnDisplayName[$key] = $user->display_name;
        }

        // Ensure case insensitive sorting.
        $columnDisplayName = array_map('strtolower', $columnDisplayName);

        // Now sort the array based on the columns.
        array_multisort($columnForumPosts, SORT_NUMERIC, SORT_DESC, $columnDisplayName, SORT_STRING, SORT_ASC, $allUsersSearchable);

        return $allUsersSearchable;
    }
}