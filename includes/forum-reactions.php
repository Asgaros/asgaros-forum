<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumReactions {
    private $asgarosforum = null;
    private $reactions_list = array('up', 'down');
    private $post_reactions = array();

    public function __construct($object) {
        $this->asgarosforum = $object;

        add_action('asgarosforum_prepare_thread', array($this, 'prepare'));
        add_action('asgarosforum_prepare_post', array($this, 'prepare'));
    }

    public function prepare() {
        if ($this->asgarosforum->options['enable_reactions']) {
            // Load the reactions for the current topic.
            $this->load_reactions();

            if (isset($_GET['reaction'])) {
                $post_id = (!empty($_GET['post'])) ? absint($_GET['post']) : 0;
                $user_id = get_current_user_id();
                $reaction = (!empty($_GET['reaction'])) ? $_GET['reaction'] : '';

                $this->reaction_change($post_id, $user_id, $reaction);
            }
        }
    }

    public function render_reactions_area($post_id, $topic_id) {
        if ($this->asgarosforum->options['enable_reactions']) {
            echo '<div class="post-reactions">';
            
                $active = array(
                    'down' => '',
                    'up' => ''
                );
                $links = false;

                // Generate the links and active-indicators if necessary.
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $reaction_exists = $this->reaction_exists($post_id, $user_id);

                    $links['down'] = AsgarosForumRewrite::getLink('topic', $topic_id, array('post' => $post_id, 'reaction' => 'down', 'part' => ($this->asgarosforum->current_page + 1)), '#postid-'.$post_id);
                    $links['up'] = AsgarosForumRewrite::getLink('topic', $topic_id, array('post' => $post_id, 'reaction' => 'up', 'part' => ($this->asgarosforum->current_page + 1)), '#postid-'.$post_id);

                    if ($reaction_exists) {
                        $active[$reaction_exists] = 'reaction-active';
                    }
                }

                // Set up the reactions counter.
                $reactions_counter_down = (isset($this->post_reactions[$post_id]['down'])) ? number_format_i18n(count($this->post_reactions[$post_id]['down'])) : 0;
                $reactions_counter_up = (isset($this->post_reactions[$post_id]['up'])) ? number_format_i18n(count($this->post_reactions[$post_id]['up'])) : 0;

                // Build the reactions.
                $reaction_down = '<span class="reaction down"><span class="reaction-icon dashicons-before dashicons-thumbs-down '.$active['down'].'"></span><span class="reaction-number">'.$reactions_counter_down.'</span></span>';
                $reaction_up = '<span class="reaction up"><span class="reaction-icon dashicons-before dashicons-thumbs-up '.$active['up'].'"></span><span class="reaction-number">'.$reactions_counter_up.'</span></span>';

                // Output of the reactions.
                if ($links) {
                    echo '<a href="'.$links['down'].'">'.$reaction_down.'</a>';
                    echo '<a href="'.$links['up'].'">'.$reaction_up.'</a>';
                } else {
                    echo $reaction_down;
                    echo $reaction_up;
                }

            echo '</div>';
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

    public function reaction_change($post_id, $user_id, $reaction) {
        // Only add a reaction when the post exists ...
        if (AsgarosForumContent::postExists($post_id)) {
            // ... and the user is logged in ...
            if (is_user_logged_in()) {
                // ... and when it is a valid reaction ...
                if (in_array($reaction, $this->reactions_list)) {
                    $reaction_check = $this->reaction_exists($post_id, $user_id);

                    // ... and when there is not already a reaction from the user.
                    if ($reaction_check === false) {
                        $this->add_reaction($post_id, $user_id, $reaction);
                    } else if ($reaction_check === $reaction) {
                        $this->remove_reaction($post_id, $user_id, $reaction);
                    } else if ($reaction_check !== $reaction) {
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
        $redirect_link = AsgarosForumRewrite::get_post_link($post_id);
        wp_redirect(html_entity_decode($redirect_link));
        exit;
    }
}