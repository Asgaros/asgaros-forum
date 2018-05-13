<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumReactions {
    private $asgarosforum = null;
    private $reactions_list = array('down', 'up');
    private $post_reactions = array();

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_action('asgarosforum_prepare_topic', array($this, 'prepare'));
        add_action('asgarosforum_prepare_post', array($this, 'prepare'));
    }

    public function prepare() {
        if ($this->asgarosforum->options['enable_reactions']) {
            // Load the reactions for the current topic.
            $this->load_reactions();

            if (isset($_GET['reaction']) && isset($_GET['reaction_action'])) {
                $post_id = (!empty($_GET['post'])) ? absint($_GET['post']) : 0;
                $user_id = get_current_user_id();
                $reaction = (!empty($_GET['reaction'])) ? $_GET['reaction'] : '';

                $this->reaction_change($post_id, $user_id, $reaction);
            }
        }
    }

    public function load_reactions() {
        if ($this->asgarosforum->current_topic) {
            $reactions = $this->asgarosforum->db->get_results("SELECT r.* FROM {$this->asgarosforum->tables->reactions} AS r, {$this->asgarosforum->tables->posts} AS p WHERE p.parent_id = {$this->asgarosforum->current_topic} AND p.id = r.post_id;");

            foreach ($reactions as $reaction) {
                if (!isset($this->post_reactions[$reaction->post_id])) {
                    $this->post_reactions[$reaction->post_id] = array();
                }

                if (!isset($this->post_reactions[$reaction->post_id][$reaction->reaction])) {
                    $this->post_reactions[$reaction->post_id][$reaction->reaction] = array();
                }

                $this->post_reactions[$reaction->post_id][$reaction->reaction][] = $reaction->user_id;
            }
        }
    }

    public function render_reactions_area($post_id, $topic_id) {
        if ($this->asgarosforum->options['enable_reactions']) {
            echo '<div class="post-reactions">';
                $active = array(
                    'down'  => '',
                    'up'    => ''
                );
                $action = array(
                    'down'  => 'add',
                    'up'    => 'add'
                );

                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $reaction_exists = $this->reaction_exists($post_id, $user_id);

                    if ($reaction_exists) {
                        $active[$reaction_exists] = 'reaction-active';
                        $action[$reaction_exists] = 'remove';
                    }
                }

                foreach ($this->reactions_list as $reaction) {
                    $counter = (isset($this->post_reactions[$post_id][$reaction])) ? number_format_i18n(count($this->post_reactions[$post_id][$reaction])) : 0;
                    $output = '<span class="reaction '.$reaction.'"><span class="reaction-icon dashicons-before dashicons-thumbs-'.$reaction.' '.$active[$reaction].'"></span><span class="reaction-number">'.$counter.'</span></span>';

                    if (is_user_logged_in()) {
                        $link = $this->asgarosforum->rewrite->get_link(
                            'topic',
                            $topic_id,
                            array(
                                'post'              => $post_id,
                                'reaction'          => $reaction,
                                'reaction_action'   => $action[$reaction],
                                'part'              => ($this->asgarosforum->current_page + 1)
                            ),
                            '#postid-'.$post_id
                        );

                        echo '<a href="'.$link.'">'.$output.'</a>';
                    } else {
                        echo $output;
                    }
                }

            echo '</div>';
        }
    }

    public function reaction_change($post_id, $user_id, $reaction) {
        // Only add a reaction when the post exists ...
        if ($this->asgarosforum->content->post_exists($post_id)) {
            // ... and the user is logged in ...
            if (is_user_logged_in()) {
                // ... and when it is a valid reaction ...
                if (in_array($reaction, $this->reactions_list)) {
                    $reaction_check = $this->reaction_exists($post_id, $user_id);

                    // ... and when there is not already a reaction from the user.
                    if ($reaction_check === false && $_GET['reaction_action'] == 'add') {
                        $this->add_reaction($post_id, $user_id, $reaction);
                    } else if ($reaction_check === $reaction && $_GET['reaction_action'] == 'remove') {
                        $this->remove_reaction($post_id, $user_id, $reaction);
                    } else if ($reaction_check !== $reaction && $_GET['reaction_action'] == 'add') {
                        $this->update_reaction($post_id, $user_id, $reaction);
                    }
                }
            }
        }

        // Redirect back to the topic.
        $this->redirect($post_id);
    }

    public function add_reaction($post_id, $user_id, $reaction) {
        $this->asgarosforum->db->insert($this->asgarosforum->tables->reactions, array('post_id' => $post_id, 'user_id' => $user_id, 'reaction' => $reaction), array('%d', '%d', '%s'));
    }

    public function remove_reaction($post_id, $user_id, $reaction) {
        $this->asgarosforum->db->delete($this->asgarosforum->tables->reactions, array('post_id' => $post_id, 'user_id' => $user_id, 'reaction' => $reaction), array('%d', '%d', '%s'));
    }

    public function update_reaction($post_id, $user_id, $reaction) {
        $this->asgarosforum->db->update($this->asgarosforum->tables->reactions, array('reaction' => $reaction), array('post_id' => $post_id, 'user_id' => $user_id), array('%s'), array('%d', '%d'));
    }

    // Removes all reactions from a specific post.
    public function remove_all_reactions($post_id) {
        // Remove reactions from database.
        $this->asgarosforum->db->delete($this->asgarosforum->tables->reactions, array('post_id' => $post_id), array('%d'));

        // Remove reactions from object.
        if (isset($this->post_reactions[$post_id])) {
            unset($this->post_reactions[$post_id]);
        }
    }

    public function reaction_exists($post_id, $user_id) {
        if (isset($this->post_reactions[$post_id]['down']) && in_array($user_id, $this->post_reactions[$post_id]['down'])) {
            return 'down';
        }

        if (isset($this->post_reactions[$post_id]['up']) && in_array($user_id, $this->post_reactions[$post_id]['up'])) {
            return 'up';
        }

        return false;
    }

    public function redirect($post_id) {
        $redirect_link = $this->asgarosforum->rewrite->get_post_link($post_id);
        wp_redirect(html_entity_decode($redirect_link));
        exit;
    }
}