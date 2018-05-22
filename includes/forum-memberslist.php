<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumMembersList {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;
    }

    public function functionalityEnabled() {
        if (!$this->asgarosforum->options['enable_memberslist'] || ($this->asgarosforum->options['memberslist_loggedin_only'] && !is_user_logged_in())) {
            return false;
        } else {
            return true;
        }
    }

    public function renderMembersListLink() {
        if ($this->functionalityEnabled()) {
            $membersLink = $this->asgarosforum->get_link('members');
            $membersLink = apply_filters('asgarosforum_filter_members_link', $membersLink);

            echo '<a class="members-link" href="'.$membersLink.'">'.__('Members', 'asgaros-forum').'</a>';
        }
    }

    public function showMembersList() {
        $pagination_rendering = $this->asgarosforum->pagination->renderPagination('members');
        $paginationRendering = ($pagination_rendering) ? '<div class="pages-and-menu">'.$pagination_rendering.'<div class="clear"></div></div>' : '';
        echo $paginationRendering;

        echo '<div class="title-element"></div>';
        echo '<div class="content-element">';

        $showAvatars = get_option('show_avatars');

        $data = $this->getMembers();

        $start = $this->asgarosforum->current_page * $this->asgarosforum->options['members_per_page'];
        $end = $this->asgarosforum->options['members_per_page'];

        $dataSliced = array_slice($data, $start, $end);

        foreach ($dataSliced as $element) {
            $userOnline = ($this->asgarosforum->online->is_user_online($element->ID)) ? ' user-online' : '';

            echo '<div class="member'.$userOnline.'">';
                if ($showAvatars) {
                    echo '<div class="member-avatar">';
                    echo get_avatar($element->ID, 60);
                    echo '</div>';
                }

                echo '<div class="member-name">';
                    echo $this->asgarosforum->getUsername($element->ID);
                    echo '<small>';
                        echo AsgarosForumPermissions::getForumRole($element->ID);
                    echo '</small>';
                echo '</div>';

                echo '<div class="member-posts">';
                    $member_posts_i18n = number_format_i18n($element->forum_posts);
                    echo sprintf(_n('%s Post', '%s Posts', $element->forum_posts, 'asgaros-forum'), $member_posts_i18n);
                echo '</div>';

                if ($this->asgarosforum->online->functionality_enabled) {
                    echo '<div class="member-last-seen">';
                        echo __('Last seen:', 'asgaros-forum').' <i>'.$this->asgarosforum->online->last_seen($element->ID).'</i>';
                    echo '</div>';
                }
            echo '</div>';
        }

        echo '</div>';

        echo $paginationRendering;
    }

    public function getMembers() {
        // Get all existing users.
        $allUsers = get_users(array('fields' => array('ID', 'display_name')));

        // Now get the amount of forum posts for all users.
        $postsCounter = $this->asgarosforum->db->get_results("SELECT author_id, COUNT(id) AS counter FROM {$this->asgarosforum->tables->posts} GROUP BY author_id ORDER BY COUNT(id) DESC;");

        // Change the structure of the results for better searchability.
        $postsCounterSearchable = array();

        foreach ($postsCounter as $postCounter) {
            $postsCounterSearchable[$postCounter->author_id] = $postCounter->counter;
        }

        // Now add the numbers of posts to the users array when they are listed in the post counter.
        foreach ($allUsers as $key => $user) {
            if (isset($postsCounterSearchable[$user->ID])) {
                $allUsers[$key]->forum_posts = $postsCounterSearchable[$user->ID];
            } else {
                $allUsers[$key]->forum_posts = 0;
            }
        }

        // Obtain a list of columns for array_multisort().
        $columnForumPosts = array();
        $columnDisplayName = array();

        foreach ($allUsers as $key => $user) {
            $columnForumPosts[$key] = $user->forum_posts;
            $columnDisplayName[$key] = $user->display_name;
        }

        // Ensure case insensitive sorting.
        $columnDisplayName = array_map('strtolower', $columnDisplayName);

        // Now sort the array based on the columns.
        array_multisort($columnForumPosts, SORT_NUMERIC, SORT_DESC, $columnDisplayName, SORT_STRING, SORT_ASC, $allUsers);

        return $allUsers;
    }
}