<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumProfile {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_action('asgarosforum_breadcrumbs_profile', array($this, 'add_breadcrumbs_profile'));
        add_action('asgarosforum_breadcrumbs_history', array($this, 'add_breadcrumbs_history'));
    }

    // Checks if the profile functionality is enabled.
    public function functionalityEnabled() {
        return $this->asgarosforum->options['enable_profiles'];
    }

    // Checks if profile links should be hidden for the current user.
    public function hideProfileLink() {
        if (!is_user_logged_in() && $this->asgarosforum->options['hide_profiles_from_guests']) {
            return true;
        } else {
            return false;
        }
    }

    public function get_user_data($user_id) {
        return get_user_by('id', $user_id);
    }

    // Gets the current title.
    public function get_profile_title() {
        $currentTitle = __('Profile', 'asgaros-forum').$this->get_title_suffix();

        return $currentTitle;
    }

    public function get_history_title() {
        $currentTitle = __('Post History', 'asgaros-forum').$this->get_title_suffix();

        return $currentTitle;
    }

    private function get_title_suffix() {
        $suffix = '';
        $userData = $this->get_user_data($this->asgarosforum->current_element);

        if ($userData) {
            $suffix = ': '.$userData->display_name;
        }

        return $suffix;
    }

    // Sets the breadcrumbs.
    public function add_breadcrumbs_profile() {
        $elementLink = $this->asgarosforum->get_link('current');
        $elementTitle = __('Profile', 'asgaros-forum').$this->get_title_suffix();
        $this->asgarosforum->breadcrumbs->add_breadcrumb($elementLink, $elementTitle);
    }

    public function add_breadcrumbs_history() {
        $elementLink = $this->asgarosforum->get_link('current');
        $elementTitle = __('Post History', 'asgaros-forum').$this->get_title_suffix();
        $this->asgarosforum->breadcrumbs->add_breadcrumb($elementLink, $elementTitle);
    }

    public function show_profile_header($user_data) {
        $userOnline = ($this->asgarosforum->online->is_user_online($user_data->ID)) ? ' class="user-online"' : '';
        $background_style = '';

        echo '<div id="profile-header"'.$userOnline.'>';
            if ($this->asgarosforum->options['enable_avatars']) {
                $url = get_avatar_url($user_data->ID, 480);
                $background_style = 'style="background-image: url(\''.$url.'\');"';
            }

            echo '<div class="background-avatar" '.$background_style.'></div>';
            echo '<div class="background-contrast"></div>';

            // Show avatar.
            if ($this->asgarosforum->options['enable_avatars']) {
                echo get_avatar($user_data->ID, 160, '', '', array('force_display' => true));
            }

            echo '<div class="user-info">';
                echo '<div class="profile-display-name">'.$user_data->display_name.'</div>';

                $role = $this->asgarosforum->permissions->getForumRole($user_data->ID);

                // Special styling for banned users.
                if ($this->asgarosforum->permissions->get_forum_role($user_data->ID) === 'banned') {
                    $role = '<span class="banned">'.$role.'</span>';
                }

                echo '<div class="profile-forum-role">';
                $count_posts = $this->asgarosforum->countPostsByUser($user_data->ID);
                $this->asgarosforum->render_reputation_badges($count_posts);
                echo $role;
                echo '</div>';
            echo '</div>';
        echo '</div>';
    }

    public function show_profile_navigation($user_data) {
        echo '<div id="profile-navigation">';
            $profile_link = $this->getProfileLink($user_data);
            $history_link = $this->get_history_link($user_data);

            // Profile link.
            if ($this->asgarosforum->current_view === 'profile') {
                echo '<a class="active" href="'.$profile_link.'">'.__('Profile', 'asgaros-forum').'</a>';
            } else {
                echo '<a href="'.$profile_link.'">'.__('Profile', 'asgaros-forum').'</a>';
            }

            // Subscriptions link.
            if ($this->asgarosforum->current_view === 'history') {
                echo '<a class="active" href="'.$history_link.'">'.__('Post History', 'asgaros-forum').'</a>';
            } else {
                echo '<a href="'.$history_link.'">'.__('Post History', 'asgaros-forum').'</a>';
            }

            do_action('asgarosforum_custom_profile_menu');
        echo '</div>';
    }

    public function count_post_history_by_user($user_id) {
        return count($this->get_post_history_by_user($user_id));
    }

    public function get_post_history_by_user($user_id, $limit = false) {
        // Get accessible categories for the current user first.
        $accessible_categories = $this->asgarosforum->content->get_categories_ids();

        if (empty($accessible_categories)) {
            // Cancel if the user cant access any categories.
            return false;
        } else {
            // Now load history-data based for an user based on the categories which are accessible for the current user.
            $accessible_categories = implode(',', $accessible_categories);

            $query_limit = "";

            if ($limit) {
                $elements_maximum = 50;
                $elements_start = $this->asgarosforum->current_page * $elements_maximum;

                $query_limit = "LIMIT {$elements_start}, {$elements_maximum}";
            }

            $query = "SELECT p.id, p.text, p.date, p.parent_id, t.name FROM {$this->asgarosforum->tables->posts} AS p, {$this->asgarosforum->tables->topics} AS t WHERE p.author_id = %d AND p.parent_id = t.id AND EXISTS (SELECT f.id FROM {$this->asgarosforum->tables->forums} AS f WHERE f.id = t.parent_id AND f.parent_id IN ({$accessible_categories})) AND t.approved = 1 ORDER BY p.id DESC {$query_limit};";

            return $this->asgarosforum->db->get_results($this->asgarosforum->db->prepare($query, $user_id));
        }
    }

    public function show_history() {
        $user_id = $this->asgarosforum->current_element;
        $userData = $this->get_user_data($user_id);

        if ($userData) {
            if ($this->hideProfileLink()) {
                _e('You need to login to have access to profiles.', 'asgaros-forum');
            } else {
                $this->show_profile_header($userData);
                $this->show_profile_navigation($userData);

                echo '<div id="profile-layer">';
                    $posts = $this->get_post_history_by_user($user_id, true);

                    if (empty($posts)) {
                        _e('No posts made by this user.', 'asgaros-forum');
                    } else {
                        $pagination = $this->asgarosforum->pagination->renderPagination('history', $user_id);

                        if ($pagination) {
                            echo '<div class="pages-and-menu">'.$pagination.'</div>';
                        }

                        foreach ($posts as $post) {
                            echo '<div class="history-element">';
                                echo '<div class="history-name">';
                                    $link = $this->asgarosforum->rewrite->get_post_link($post->id, $post->parent_id);
                                    $text = esc_html(stripslashes(strip_tags($post->text)));
                                    $text = $this->asgarosforum->cut_string($text, 100);

                                    echo '<a class="history-title" href="'.$link.'">'.$text.'</a>';

                                    $topic_link = $this->asgarosforum->rewrite->get_link('topic', $post->parent_id);
                                    $topic_name = esc_html(stripslashes($post->name));
                                    $topic_time = sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($post->date), current_time('timestamp')));

                                    echo '<span class="history-topic">'.__('In:', 'asgaros-forum').' <a href="'.$topic_link.'">'.$topic_name.'</a></span>';
                                echo '</div>';

                                echo '<div class="history-time">'.$topic_time.'</div>';
                            echo '</div>';
                        }

                        if ($pagination) {
                            echo '<div class="pages-and-menu">'.$pagination.'</div>';
                        }
                    }
                echo '</div>';
            }
        } else {
            _e('This user does not exist.', 'asgaros-forum');
        }
    }

    // Shows the profile of a user.
    public function showProfile() {
        $user_id = $this->asgarosforum->current_element;
        $userData = $this->get_user_data($user_id);

        if ($userData) {
            if ($this->hideProfileLink()) {
                _e('You need to login to have access to profiles.', 'asgaros-forum');
            } else {
                $this->show_profile_header($userData);
                $this->show_profile_navigation($userData);

                echo '<div id="profile-content">';
                    // Show first name.
                    if (!empty($userData->first_name)) {
                        $cellTitle = __('First Name:', 'asgaros-forum');
                        $cellValue = $userData->first_name;

                        $this->renderProfileRow($cellTitle, $cellValue);
                    }

                    // Show usergroups.
                    $userGroups = AsgarosForumUserGroups::getUserGroupsOfUser($userData->ID, 'all', true);

                    if (!empty($userGroups)) {
                        $cellTitle = __('Usergroups:', 'asgaros-forum');
                        $cellValue = $userGroups;

                        $this->renderProfileRow($cellTitle, $cellValue, 'usergroups');
                    }

                    // Show website.
                    if (!empty($userData->user_url)) {
                        $cellTitle = __('Website:', 'asgaros-forum');
                        $cellValue = '<a href="'.$userData->user_url.'" rel="nofollow" target="_blank">'.$userData->user_url.'</a>';

                        $this->renderProfileRow($cellTitle, $cellValue);
                    }

                    // Show last seen.
                    if ($this->asgarosforum->online->functionality_enabled) {
                        $cellTitle = __('Last seen:', 'asgaros-forum');
                        $cellValue = $this->asgarosforum->online->last_seen($userData->ID);

                        $this->renderProfileRow($cellTitle, $cellValue);
                    }

                    // Show member since.
                    $cellTitle = __('Member Since:', 'asgaros-forum');
                    $cellValue = $this->asgarosforum->format_date($userData->user_registered, false);

                    $this->renderProfileRow($cellTitle, $cellValue);

                    // Show biographical info.
                    if (!empty($userData->description)) {
                        $cellTitle = __('Biographical Info:', 'asgaros-forum');
                        $cellValue = trim(wpautop(esc_html($userData->description)));

                        $this->renderProfileRow($cellTitle, $cellValue);
                    }

                    // Show signature.
                    $signature = $this->asgarosforum->get_signature($userData->ID);

                    if ($signature !== false) {
                        $cellTitle = __('Signature:', 'asgaros-forum');
                        $cellValue = $signature;

                        $this->renderProfileRow($cellTitle, $cellValue);
                    }

                    do_action('asgarosforum_profile_row', $userData);

                    echo '<div class="profile-section-header">';
                        echo '<span class="profile-section-header-icon fas fa-address-card"></span>';
                        echo __('Member Activity', 'asgaros-forum');
                    echo '</div>';

                    echo '<div class="profile-section-content">';
                        // Topics started.
                        $count_topics = $this->asgarosforum->countTopicsByUser($userData->ID);
                        AsgarosForumStatistics::renderStatisticsElement(__('Topics Started', 'asgaros-forum'), $count_topics, 'far fa-comments');

                        // Replies created.
                        $count_posts = $this->asgarosforum->countPostsByUser($userData->ID);
                        $count_posts = $count_posts - $count_topics;
                        AsgarosForumStatistics::renderStatisticsElement(__('Replies Created', 'asgaros-forum'), $count_posts, 'far fa-comment');

                        // Likes Received.
                        if ($this->asgarosforum->options['enable_reactions']) {
                            $count_likes = $this->asgarosforum->reactions->get_reactions_received($userData->ID, 'up');
                            AsgarosForumStatistics::renderStatisticsElement(__('Likes Received', 'asgaros-forum'), $count_likes, 'fas fa-thumbs-up');
                        }
                    echo '</div>';

                    do_action('asgarosforum_custom_profile_content', $userData);

                    $current_user_id = get_current_user_id();

                    if ($userData->ID == $current_user_id) {
                        echo '<a href="'.get_edit_profile_url().'" class="edit-profile-link">';
                            echo '<span class="fas fa-pencil-alt"></span>';
                            echo __('Edit Profile', 'asgaros-forum');
                        echo '</a>';
                    }

                    // Check if the current user can ban this user.
                    if ($this->asgarosforum->permissions->can_ban_user($current_user_id, $userData->ID)) {
                        if ($this->asgarosforum->permissions->isBanned($userData->ID)) {
                            $url = $this->getProfileLink($userData, array('unban_user' => $userData->ID));
                            $nonce_url = wp_nonce_url($url, 'unban_user_'.$userData->ID);
                            echo '<a class="banned" href="'.$nonce_url.'">'.__('Unban User', 'asgaros-forum').'</a>';
                        } else {
                            $url = $this->getProfileLink($userData, array('ban_user' => $userData->ID));
                            $nonce_url = wp_nonce_url($url, 'ban_user_'.$userData->ID);
                            echo '<a class="banned" href="'.$nonce_url.'">'.__('Ban User', 'asgaros-forum').'</a>';
                        }
                    }
                echo '</div>';
            }
        } else {
            _e('This user does not exist.', 'asgaros-forum');
        }
    }

    public function renderProfileRow($cellTitle, $cellValue, $type = '') {
        echo '<div class="profile-row">';
            echo '<div>'.$cellTitle.'</div>';
            echo '<div>';

            if (is_array($cellValue)) {
                foreach ($cellValue as $value) {
                    if ($type == 'usergroups') {
                        echo AsgarosForumUserGroups::render_usergroup_tag($value);
                    } else {
                        echo $value.'<br>';
                    }
                }
            } else {
                echo $cellValue;
            }

            echo '</div>';
        echo '</div>';
    }

    public function getProfileLink($userObject, $additional_parameters = false) {
        if ($this->hideProfileLink() || !$this->functionalityEnabled()) {
            return false;
        } else {
            $profileLink = $this->asgarosforum->get_link('profile', $userObject->ID, $additional_parameters, '', false);
            $profileLink = apply_filters('asgarosforum_filter_profile_link', $profileLink, $userObject);

            return $profileLink;
        }
    }

    public function get_history_link($userObject) {
        if ($this->hideProfileLink() || !$this->functionalityEnabled()) {
            return false;
        } else {
            $profileLink = $this->asgarosforum->get_link('history', $userObject->ID);
            $profileLink = apply_filters('asgarosforum_filter_history_link', $profileLink, $userObject);

            return $profileLink;
        }
    }

    // Renders a link to the own profile. The own profile is always available, even when the profile functionality is disabled.
    public function myProfileLink() {
        // First check if the user is logged in.
        if ($this->functionalityEnabled()) {
            // Only continue if the current user is logged in.
            if (is_user_logged_in()) {
                // Get current user.
                $currentUserObject = wp_get_current_user();

                // Get and build profile link.
                $profileLink = $this->getProfileLink($currentUserObject);

                echo '<a class="profile-link" href="'.$profileLink.'">'.__('Profile', 'asgaros-forum').'</a>';
            }
        }
    }
}
