<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumMembersList {
    private $asgarosforum = null;
    public $filter_role = 'all';

    public function __construct($object) {
        $this->asgarosforum = $object;

        // Set filter based on URL parameter.
        $this->filter_role = $this->get_filter();
    }

    public function functionalityEnabled() {
        if (!$this->asgarosforum->options['enable_memberslist'] || ($this->asgarosforum->options['memberslist_loggedin_only'] && !is_user_logged_in())) {
            return false;
        } else {
            return true;
        }
    }

    public function get_filter() {
        if ($this->functionalityEnabled()) {
            if (!empty($_GET['filter_role'])) {
                switch ($_GET['filter_role']) {
                    case 'all':
                    case 'normal':
                    case 'moderator':
                    case 'administrator':
                    case 'banned':
                        return $_GET['filter_role'];
                    break;
                }
            }
        }

        return 'all';
    }

    public function renderMembersListLink() {
        if ($this->functionalityEnabled()) {
            $membersLink = $this->asgarosforum->get_link('members');
            $membersLink = apply_filters('asgarosforum_filter_members_link', $membersLink);

            echo '<a class="members-link" href="'.$membersLink.'">'.__('Members', 'asgaros-forum').'</a>';
        }
    }

    public function show_filters() {
        $filter_toggle_text = __('Show Filters', 'asgaros-forum');
        $filter_toggle_class = 'dashicons-arrow-down-alt2';
        $filter_toggle_hidden = 'style="display: none;"';

        if (isset($_GET['filter_role'])) {
            $filter_toggle_text = __('Hide Filters', 'asgaros-forum');
            $filter_toggle_class = 'dashicons-arrow-up-alt2';
            $filter_toggle_hidden = '';
        }

        echo '<div class="title-element dashicons-before '.$filter_toggle_class.'" id="memberslist-filter-toggle">'.$filter_toggle_text.'</div>';
        echo '<div id="memberslist-filter" data-value-show-filters="'.__('Show Filters', 'asgaros-forum').'" data-value-hide-filters="'.__('Hide Filters', 'asgaros-forum').'" '.$filter_toggle_hidden.'>';
            echo '<div id="roles-filter">';
                echo 'Roles:';
                echo '&nbsp;';
                echo $this->render_role_option('all', 'All Users');
                echo '&nbsp;&middot;&nbsp;';
                echo $this->render_role_option('normal', 'Normal');
                echo '&nbsp;&middot;&nbsp;';
                echo $this->render_role_option('moderator', 'Moderators');
                echo '&nbsp;&middot;&nbsp;';
                echo $this->render_role_option('administrator', 'Administrators');
                echo '&nbsp;&middot;&nbsp;';
                echo $this->render_role_option('banned', 'Banned');

                //print_r(AsgarosForumUserGroups::getUserGroups(array(), true));
            echo '</div>';
        echo '</div>';
    }

    public function render_role_option($role, $name) {
        $output = '<a href="'.$this->asgarosforum->rewrite->get_link('members', false, array('filter_role' => $role)).'">'.$name.'</a>';

        if ($role === $this->filter_role) {
            echo '<b>'.$output.'</b>';
        } else {
            echo $output;
        }
    }

    public function showMembersList() {
        $pagination_rendering = $this->asgarosforum->pagination->renderPagination('members');
        $paginationRendering = ($pagination_rendering) ? '<div class="pages-and-menu">'.$pagination_rendering.'<div class="clear"></div></div>' : '';
        echo $paginationRendering;

        $this->show_filters();

        echo '<div class="content-element">';

        $showAvatars = get_option('show_avatars');

        $data = $this->getMembers();

        if (empty($data)) {
            echo '<div class="notice">'.__('No users found!', 'asgaros-forum').'</div>';
        } else {
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
                            echo $this->asgarosforum->permissions->getForumRole($element->ID);
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
        }

        echo '</div>';

        echo $paginationRendering;
    }

    public function getMembers() {
        $allUsers = $this->asgarosforum->permissions->get_users_by_role($this->filter_role);

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
