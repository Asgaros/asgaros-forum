<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumMembersList {
    private $asgarosforum = null;
    public $filter_type = 'role';
    public $filter_name = 'all';

    public function __construct($object) {
        $this->asgarosforum = $object;

        // Set filter based on URL parameters.
        add_action('asgarosforum_prepare_members', array($this, 'set_filter'));

        add_action('asgarosforum_breadcrumbs_members', array($this, 'add_breadcrumbs'));
    }

    public function functionality_enabled() {
        if (!$this->asgarosforum->options['enable_memberslist'] || ($this->asgarosforum->options['memberslist_loggedin_only'] && !is_user_logged_in())) {
            return false;
        } else {
            return true;
        }
    }

    public function add_breadcrumbs() {
        $element_link = $this->asgarosforum->get_link('members');
        $element_title = __('Members', 'asgaros-forum');
        $this->asgarosforum->breadcrumbs->add_breadcrumb($element_link, $element_title);
    }

    public function set_filter() {
        if ($this->functionality_enabled()) {
            if (!empty($_GET['filter_type']) && !empty($_GET['filter_name'])) {
                if ($_GET['filter_type'] === 'role') {
                    switch ($_GET['filter_name']) {
                        case 'all':
                        case 'normal':
                        case 'moderator':
                        case 'administrator':
                        case 'banned':
                            $this->filter_type = 'role';
                            $this->filter_name = 'all';

                            // Ensure that the filter is available.
                            if ($this->is_filter_available($_GET['filter_name'])) {
                                $this->filter_name = $_GET['filter_name'];
                            }
                        break;
                    }
                } else if ($_GET['filter_type'] === 'group') {
                    $this->filter_type = 'group';
                    $this->filter_name = $_GET['filter_name'];
                }
            }
        }
    }

    public function show_memberslist_link() {
        if ($this->functionality_enabled()) {
            $membersLink = $this->asgarosforum->get_link('members');
            $membersLink = apply_filters('asgarosforum_filter_members_link', $membersLink);

            echo '<a class="members-link" href="'.$membersLink.'">'.__('Members', 'asgaros-forum').'</a>';
        }
    }

    public function show_filters() {
        // Load usergroups.
        $usergroups = AsgarosForumUserGroups::getUserGroups(array(), true);

        // Dont show filters when there are no usergroups and no active filters.
        if (empty($usergroups) && !$this->is_filter_available('normal') && !$this->is_filter_available('moderator') && !$this->is_filter_available('administrator') && !$this->is_filter_available('banned')) {
            echo '<div class="title-element"></div>';
            return;
        }

        $filter_toggle_text = __('Show Filters', 'asgaros-forum');
        $filter_toggle_icon = 'fas fa-chevron-down';
        $filter_toggle_hide = 'style="display: none;"';

        if (!empty($_GET['filter_type']) && !empty($_GET['filter_name'])) {
            $filter_toggle_text = __('Hide Filters', 'asgaros-forum');
            $filter_toggle_icon = 'fas fa-chevron-up';
            $filter_toggle_hide = '';
        }

        echo '<div class="title-element" id="memberslist-filter-toggle">';
            echo '<span class="title-element-icon '.$filter_toggle_icon.'"></span>';
            echo '<span class="title-element-text">'.$filter_toggle_text.'</span>';
        echo '</div>';

        echo '<div id="memberslist-filter" data-value-show-filters="'.__('Show Filters', 'asgaros-forum').'" data-value-hide-filters="'.__('Hide Filters', 'asgaros-forum').'" '.$filter_toggle_hide.'>';
            echo '<div id="roles-filter">';
                echo '<div class="filter-name">'.__('Roles:', 'asgaros-forum').'</div>';
                echo '<div class="filter-options">';
                    echo $this->render_filter_option('role', 'all', __('All Users', 'asgaros-forum'));

                    if ($this->is_filter_available('normal')) {
                        $users = $this->asgarosforum->permissions->get_users_by_role('normal');

                        if (count($users) > 0) {
                            echo '&nbsp;&middot;&nbsp;';
                            echo $this->render_filter_option('role', 'normal', __('Normal', 'asgaros-forum'));
                        }
                    }

                    if ($this->is_filter_available('moderator')) {
                        $users = $this->asgarosforum->permissions->get_users_by_role('moderator');

                        if (count($users) > 0) {
                            echo '&nbsp;&middot;&nbsp;';
                            echo $this->render_filter_option('role', 'moderator', __('Moderators', 'asgaros-forum'));
                        }
                    }

                    if ($this->is_filter_available('administrator')) {
                        $users = $this->asgarosforum->permissions->get_users_by_role('administrator');

                        if (count($users) > 0) {
                            echo '&nbsp;&middot;&nbsp;';
                            echo $this->render_filter_option('role', 'administrator', __('Administrators', 'asgaros-forum'));
                        }
                    }

                    if ($this->is_filter_available('banned')) {
                        $users = $this->asgarosforum->permissions->get_users_by_role('banned');

                        if (count($users) > 0) {
                            echo '&nbsp;&middot;&nbsp;';
                            echo $this->render_filter_option('role', 'banned', __('Banned', 'asgaros-forum'));
                        }
                    }
                echo '</div>';
            echo '</div>';

            if (!empty($usergroups)) {
                $usergroups_filter_output = '';

                foreach ($usergroups as $usergroup) {
                    $users_counter = AsgarosForumUserGroups::countUsersOfUserGroup($usergroup->term_id);

                    // Only list usergroups with users in it.
                    if ($users_counter > 0) {
                        $font_weight = 'normal';

                        if ($this->filter_type == 'group' && $this->filter_name == $usergroup->term_id) {
                            $font_weight = 'bold';
                        }
                        $usergroups_filter_output .= AsgarosForumUserGroups::render_usergroup_tag($usergroup, $font_weight);
                    }
                }

                if (!empty($usergroups_filter_output)) {
                    echo '<div id="usergroups-filter">';
                        echo '<div class="filter-name">'.__('Usergroups:', 'asgaros-forum').'</div>';
                        echo '<div class="filter-options">'.$usergroups_filter_output.'</div>';
                    echo '</div>';
                }
            }

        echo '</div>';
    }

    public function render_filter_option($filter_type, $filter_name, $title) {
        $output = '<a href="'.$this->asgarosforum->rewrite->get_link('members', false, array('filter_type' => $filter_type, 'filter_name' => $filter_name)).'">'.$title.'</a>';

        if ($filter_type === $this->filter_type && $filter_name == $this->filter_name) {
            return '<b>'.$output.'</b>';
        }

        return $output;
    }

    public function show_memberslist() {
        $pagination_rendering = $this->asgarosforum->pagination->renderPagination('members');
        $paginationRendering = ($pagination_rendering) ? '<div class="pages-and-menu">'.$pagination_rendering.'<div class="clear"></div></div>' : '';
        echo $paginationRendering;

        $this->show_filters();

        echo '<div class="content-container">';

        $data = $this->get_members();

        if (empty($data)) {
            $this->asgarosforum->render_notice(__('No users found!', 'asgaros-forum'));
        } else {
            $start = $this->asgarosforum->current_page * $this->asgarosforum->options['members_per_page'];
            $end = $this->asgarosforum->options['members_per_page'];

            $dataSliced = array_slice($data, $start, $end);

            foreach ($dataSliced as $element) {
                $userOnline = ($this->asgarosforum->online->is_user_online($element->ID)) ? ' user-online' : '';

                echo '<div class="content-element member'.$userOnline.'">';
                    if ($this->asgarosforum->options['enable_avatars']) {
                        echo '<div class="member-avatar">';
                        echo get_avatar($element->ID, 60, '', '', array('force_display' => true));
                        echo '</div>';
                    }

                    echo '<div class="member-name">';
                        echo $this->asgarosforum->getUsername($element->ID);
                        echo '<small>'.$this->asgarosforum->permissions->getForumRole($element->ID).'</small>';

                        $usergroups = AsgarosForumUserGroups::getUserGroupsOfUser($element->ID, 'all', true);

                        if (!empty($usergroups)) {
                            echo '<small>';

                            foreach ($usergroups as $usergroup) {
                                echo AsgarosForumUserGroups::render_usergroup_tag($usergroup);
                            }

                            echo '</small>';
                        }
                    echo '</div>';

                    echo '<div class="member-posts">';
                        $member_posts_i18n = number_format_i18n($element->forum_posts);
                        echo sprintf(_n('%s Post', '%s Posts', $element->forum_posts, 'asgaros-forum'), $member_posts_i18n);
                    echo '</div>';

                    if ($this->asgarosforum->online->functionality_enabled) {
                        echo '<div class="member-last-seen">';
                            echo '<i>'.$this->asgarosforum->online->last_seen($element->ID).'</i>';
                        echo '</div>';
                    }
                echo '</div>';
            }
        }

        echo '</div>';

        echo $paginationRendering;
    }

    // Checks if a given filter is available.
    public function is_filter_available($filter) {
        // Filter for all users always available.
        if ($filter === 'all') {
            return true;
        }

        // Check if other filters are available.
        if (!empty($this->asgarosforum->options['memberslist_filter_'.$filter])) {
            return true;
        }

        // Otherwise the filter is not available.
        return false;
    }

    public function get_members() {
        $allUsers = false;

        if ($this->filter_type === 'role') {
            $allUsers = $this->asgarosforum->permissions->get_users_by_role($this->filter_name);
        } else if ($this->filter_type === 'group') {
            $allUsers = AsgarosForumUserGroups::get_users_in_usergroup($this->filter_name);
        }

        if ($allUsers) {
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
        }

        return $allUsers;
    }
}
