<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumProfile {
    private $asgarosforum = null;

    public function __construct($object) {
        $this->asgarosforum = $object;
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

    // Sets the current view when the functionality is enabled.
    public function setCurrentView() {
        if ($this->functionalityEnabled()) {
            $this->asgarosforum->current_view = 'profile';
        } else {
            $this->asgarosforum->current_view = 'overview';
        }
    }

    public function getUserData($user_id = false) {
        if (!$user_id) {
            $user_id = $this->asgarosforum->current_element;
        }

        return get_user_by('id', $user_id);
    }

    // Gets the current title.
    public function getCurrentTitle() {
        $currentTitle = __('Profile', 'asgaros-forum');

        $userData = $this->getUserData();

        if ($userData) {
            $currentTitle .= ': '.$userData->display_name;
        }

        return $currentTitle;
    }

    // Sets the breadcrumbs.
    public function setBreadCrumbs() {
        $elementLink = $this->asgarosforum->get_link('current');
        $elementTitle = __('Profile', 'asgaros-forum');

        $userData = $this->getUserData();

        if ($userData) {
            $elementTitle = __('Profile', 'asgaros-forum').': '.$userData->display_name;
        }

        $this->asgarosforum->breadcrumbs->add_breadcrumb($elementLink, $elementTitle);
    }

    // Shows the profile of a user.
    public function showProfile() {
        $userData = $this->getUserData();

        if ($userData) {
            if ($this->hideProfileLink()) {
                echo __('You need to login to have access to profiles.', 'asgaros-forum');
            } else {
                $showAvatars = get_option('show_avatars');
                $background_style = '';
                $userOnline = ($this->asgarosforum->online->is_user_online($userData->ID)) ? ' class="user-online"' : '';

                echo '<div id="forum-profile"'.$userOnline.'>';
                    echo '<div id="profile-header">';
                        if ($showAvatars) {
                            $url = get_avatar_url($userData->ID, 480);
                            $background_style = 'style="background-image: url(\''.$url.'\');"';
                        }

                        echo '<div class="background-avatar" '.$background_style.'></div>';
                        echo '<div class="background-contrast"></div>';

                        // Show avatar.
                        if ($showAvatars) {
                            echo get_avatar($userData->ID, 160);
                        }

                        echo '<div class="user-info">';
                            echo '<div class="profile-display-name">'.$userData->display_name.'</div>';
                            echo '<div class="profile-forum-role">'.AsgarosForumPermissions::getForumRole($userData->ID).'</div>';
                        echo '</div>';
                    echo '</div>';

                    echo '<div id="profile-content">';
                        // Show first name.
                        if (!empty($userData->first_name)) {
                            $cellTitle = __('First Name:', 'asgaros-forum');
                            $cellValue = $userData->first_name;

                            $this->renderProfileRow($cellTitle, $cellValue);
                        }

                        // Show user groups.
                        $userGroups = AsgarosForumUserGroups::getUserGroupsOfUser($userData->ID, 'all', true);

                        if (!empty($userGroups)) {
                            $cellTitle = __('User Groups:', 'asgaros-forum');
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

                        // Show topics started.
                        $createdTopics = $this->asgarosforum->getTopicsByUser($userData->ID);
                        $counterTopics = count($createdTopics);
                        $cellTitle = __('Topics Started:', 'asgaros-forum');
                        $cellValue = number_format_i18n($counterTopics);

                        $this->renderProfileRow($cellTitle, $cellValue);

                        // Show replies created.
                        $createdPosts = $this->asgarosforum->countPostsByUser($userData->ID);
                        $counterPosts = $createdPosts - $counterTopics;
                        $cellTitle = __('Replies Created:', 'asgaros-forum');
                        $cellValue = number_format_i18n($counterPosts);

                        $this->renderProfileRow($cellTitle, $cellValue);

                        // Show biographical info.
                        if (!empty($userData->description)) {
                            $cellTitle = __('Biographical Info:', 'asgaros-forum');
                            $cellValue = trim(esc_html($userData->description));

                            $this->renderProfileRow($cellTitle, $cellValue);
                        }

                        // Show signature.
                        if ($this->asgarosforum->options['allow_signatures']) {
                            $signature = trim(esc_html(get_user_meta($userData->ID, 'asgarosforum_signature', true)));

                            if (!empty($signature)) {
                                $cellTitle = __('Signature:', 'asgaros-forum');
                                $cellValue = $signature;

                                $this->renderProfileRow($cellTitle, $cellValue);
                            }
                        }

                        do_action('asgarosforum_custom_profile_content', $userData);

                        if ($userData->ID == get_current_user_id()) {
                            echo '<a href="'.get_edit_profile_url().'" class="edit-profile-link"><span class="dashicons-before dashicons-edit">'.__('Edit Profile', 'asgaros-forum').'</span></a>';
                        }
                    echo '</div>';
                echo '</div>';
            }
        } else {
            _e('This user does not exist.', 'asgaros-forum');
        }
    }

    public function renderProfileRow($cellTitle, $cellValue, $type = '') {
        echo '<div>';
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

    public function getProfileLink($userObject) {
        if ($this->hideProfileLink() || !$this->functionalityEnabled()) {
            return false;
        } else {
            $profileLink = $this->asgarosforum->get_link('profile', $userObject->ID);
            $profileLink = apply_filters('asgarosforum_filter_profile_link', $profileLink, $userObject);

            return $profileLink;
        }
    }

    public function renderCurrentUsersProfileLink() {
        // First check if the user is logged in.
        if ($this->functionalityEnabled()) {
            // Only continue if the current user is logged in.
            if (is_user_logged_in()) {
                // Get current user.
                $currentUserObject = wp_get_current_user();

                // Get and build profile link.
                $profileLink = $this->getProfileLink($currentUserObject);

                echo '<a class="profile-link" href="'.$profileLink.'">'.__('My Profile', 'asgaros-forum').'</a>';
            }
        }
    }
}
