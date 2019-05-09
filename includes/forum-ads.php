<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumAds {
    private $asgarosforum = null;
    public $locations = array();
    public $ads = array();
    public $counter_categories = 0;
    public $counter_forums = 0;
    public $counter_topics = 0;
    public $counter_posts = 0;

    public function __construct($object) {
        $this->asgarosforum = $object;

        if ($this->asgarosforum->options['enable_ads']) {
            // Define available locations.
            $this->locations = array(
                'top'       => __('Top', 'asgaros-forum'),
                'header'    => __('Header', 'asgaros-forum'),
                'category'  => __('Category', 'asgaros-forum'),
                'forum'     => __('Forum', 'asgaros-forum'),
                'topic'     => __('Topic', 'asgaros-forum'),
                'post'      => __('Post', 'asgaros-forum'),
                'bottom'    => __('Bottom', 'asgaros-forum')
            );

            // Register hooks.
            add_action('asgarosforum_prepare', array($this, 'prepare_ads'));
            add_action('asgarosforum_content_top', array($this, 'include_ads_top'));
            add_action('asgarosforum_content_header', array($this, 'include_ads_header'));
            add_action('asgarosforum_after_category', array($this, 'include_ads_category'));
            add_action('asgarosforum_after_forum', array($this, 'include_ads_forum'));
            add_action('asgarosforum_after_topic', array($this, 'include_ads_topic'));
            add_action('asgarosforum_after_post', array($this, 'include_ads_post'));
            add_action('asgarosforum_content_bottom', array($this, 'include_ads_bottom'));
        }
    }

    // Preparation for frontend ads.
    public function prepare_ads() {
        // Prepare ads-container.
        foreach ($this->locations as $key => $value) {
            $this->ads[$key] = array();
        }

        // Load available ads.
        $ads = $this->asgarosforum->db->get_results('SELECT * FROM '.$this->asgarosforum->tables->ads.' WHERE active = 1 ORDER BY id ASC;');

        foreach ($ads as $ad) {
            if (!empty($ad->locations)) {
                $locations = explode(',', $ad->locations);

                foreach ($locations as $location) {
                    // Check if the location is available.
                    if (isset($this->ads[$location])) {
                        // Add the ad to the location.
                        $this->ads[$location][] = $ad->code;
                    }
                }
            }
        }
    }

    public function include_ads_top() {
        $this->render_ad('top');
    }

    public function include_ads_header() {
        $this->render_ad('header');
    }

    public function include_ads_category() {
        $this->counter_categories++;

        if (($this->counter_categories % $this->asgarosforum->options['ads_frequency_categories']) == 0) {
            $this->render_ad('category');
        }
    }

    public function include_ads_forum() {
        $this->counter_forums++;

        if (($this->counter_forums % $this->asgarosforum->options['ads_frequency_forums']) == 0) {
            $this->render_ad('forum');
        }
    }

    public function include_ads_topic() {
        $this->counter_topics++;

        if (($this->counter_topics % $this->asgarosforum->options['ads_frequency_topics']) == 0) {
            $this->render_ad('topic');
        }
    }

    public function include_ads_post() {
        $this->counter_posts++;

        if (($this->counter_posts % $this->asgarosforum->options['ads_frequency_posts']) == 0) {
            $this->render_ad('post');
        }
    }

    public function include_ads_bottom() {
        $this->render_ad('bottom');
    }

    public function render_ad($position) {
        if (!empty($this->ads[$position])) {
            // Set an initial ad.
            $ad = $this->ads[$position][0];

            // Count ads.
            $counter = count($this->ads[$position]);

            // Select a random ad if necessary.
            if ($counter > 1) {
                $ad = $this->ads[$position][rand(0, ($counter - 1))];
            }

            // Prepare ad.
            $ad = do_shortcode(stripslashes($ad));

            // Render ad.
            echo '<div class="ad ad-'.$position.'">'.$ad.'</div>';
        }
    }

    public function get_ads() {
        return $this->asgarosforum->db->get_results('SELECT * FROM '.$this->asgarosforum->tables->ads.' ORDER BY id ASC;');
    }

    public function save_ad($ad_id, $ad_name, $ad_code, $ad_active, $ad_locations) {
        if ($ad_id === 'new') {
            $this->asgarosforum->db->insert(
                $this->asgarosforum->tables->ads,
                array(
                    'name'      => $ad_name,
                    'code'      => $ad_code,
                    'active'    => $ad_active,
                    'locations' => $ad_locations
                ),
                array(
                    '%s',
                    '%s',
                    '%d',
                    '%s'
                )
            );
        } else {
            $this->asgarosforum->db->update(
                $this->asgarosforum->tables->ads,
                array(
                    'name'      => $ad_name,
                    'code'      => $ad_code,
                    'active'    => $ad_active,
                    'locations' => $ad_locations
                ),
                array('id' => $ad_id),
                array(
                    '%s',
                    '%s',
                    '%d',
                    '%s'
                ),
                array('%d')
            );
        }
    }

    public function delete_ad($ad_id) {
        $this->asgarosforum->db->delete($this->asgarosforum->tables->ads, array('id' => $ad_id), array('%d'));
    }

    public function get_location_name($key) {
        if (isset($this->locations[$key])) {
            return $this->locations[$key];
        }

        return false;
    }
}
