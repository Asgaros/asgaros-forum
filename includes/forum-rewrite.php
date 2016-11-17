<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumRewrite {
    private $linkList = array();

    public function __construct() {
        add_action('init', array($this, 'initialize'));
	}

    // Set all base links.
    public function initialize() {
        global $asgarosforum, $wp;
        $this->linkList['home']        = esc_url(get_page_link($asgarosforum->options['location']));
        $this->linkList['forum']       = esc_url(add_query_arg(array('view' => 'forum'), $this->linkList['home']));
        $this->linkList['topic']       = esc_url(add_query_arg(array('view' => 'thread'), $this->linkList['home']));
        $this->linkList['topic_add']   = esc_url(add_query_arg(array('view' => 'addthread'), $this->linkList['home']));
        $this->linkList['topic_move']  = esc_url(add_query_arg(array('view' => 'movethread'), $this->linkList['home']));
        $this->linkList['post_add']    = esc_url(add_query_arg(array('view' => 'addpost'), $this->linkList['home']));
        $this->linkList['post_edit']   = esc_url(add_query_arg(array('view' => 'editpost'), $this->linkList['home']));
        $this->linkList['current']     = add_query_arg($_SERVER['QUERY_STRING'], '', trailingslashit(home_url($wp->request)));
    }

    // Builds and returns a requested link.
    public function getLink($type, $elementID = false, $additionalParameters = false, $appendix = '') {
        // Set an ID if available, otherwise initialize the base-link.
        $link = ($elementID) ? add_query_arg('id', $elementID, $this->linkList[$type]) : $this->linkList[$type];

        // Set additional parameters if available, otherwise let the link unchanged.
        $link = ($additionalParameters) ? add_query_arg($additionalParameters, $link) : $link;

        // Return escaped URL with optional appendix at the end if set.
        return esc_url($link.$appendix);
    }
}

?>
