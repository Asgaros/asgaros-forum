<?php

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')){
    require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

class Asgaros_Forum_Admin_Structure_Table extends WP_List_Table {
    var $table_data = array();

    function __construct($table_data) {
        $this->table_data = $table_data;

        parent::__construct(
            array(
                'singular'  => 'forum',
                'plural'    => 'forums',
                'ajax'      => false
            )
        );
    }

    function column_default($item, $column_name) {
        return $item[$column_name];
    }

    function column_name($item) {
        $forumIcon = trim(esc_html(stripslashes($item['icon'])));
        $forumIcon = (empty($forumIcon)) ? 'dashicons-editor-justify' : $forumIcon;

        $columnHTML = '';
        $columnHTML .= '<input type="hidden" id="forum_'.$item['id'].'_name" value="'.esc_html(stripslashes($item['name'])).'">';
        $columnHTML .= '<input type="hidden" id="forum_'.$item['id'].'_description" value="'.esc_html(stripslashes($item['description'])).'">';
        $columnHTML .= '<input type="hidden" id="forum_'.$item['id'].'_icon" value="'.$forumIcon.'">';
        $columnHTML .= '<input type="hidden" id="forum_'.$item['id'].'_closed" value="'.esc_html(stripslashes($item['closed'])).'">';
        $columnHTML .= '<input type="hidden" id="forum_'.$item['id'].'_order" value="'.esc_html(stripslashes($item['sort'])).'">';

        if ($item['parent_forum']) {
            $columnHTML .= '<span class="subforum">';
        } else {
            $columnHTML .= '<span class="parentforum">';
        }

        $columnHTML .= sprintf('<span class="forum-name">%1$s <span class="element-id">('.__('ID', 'asgaros-forum').': %2$s)</span></span><br><span class="forum-description">%3$s</span></span>', stripslashes($item['name']), $item['id'], stripslashes($item['description']));

        return $columnHTML;
    }

    function column_icon($item) {
        $forumIcon = trim(esc_html(stripslashes($item['icon'])));
        $forumIcon = (empty($forumIcon)) ? 'dashicons-editor-justify' : $forumIcon;
        $columnHTML = '<span class="forum-icon dashicons-before '.$forumIcon.'"></span>';
        return $columnHTML;
    }

    function column_status($item) {
        if ($item['closed'] == 1) {
            return __('Closed', 'asgaros-forum');
        } else {
            return __('Normal', 'asgaros-forum');
        }
    }

    function column_actions($item) {
        $actionHTML = '';
        $actionHTML .= '<a href="#" class="forum-delete-link" data-value-id="'.$item['id'].'" data-value-category="'.$item['parent_id'].'" data-value-editor-title="'.__('Delete Forum', 'asgaros-forum').'">';
        $actionHTML .= __('Delete Forum', 'asgaros-forum');
        $actionHTML .= '</a>';
        $actionHTML .= ' | ';
        $actionHTML .= '<a href="#" class="forum-editor-link" data-value-id="'.$item['id'].'" data-value-category="'.$item['parent_id'].'" data-value-parent-forum="'.$item['parent_forum'].'" data-value-editor-title="'.__('Edit Forum', 'asgaros-forum').'">';
        $actionHTML .= __('Edit Forum', 'asgaros-forum');
        $actionHTML .= '</a>';

        if (!$item['parent_forum']) {
            $actionHTML .= ' | ';
            $actionHTML .= '<a href="#" class="forum-editor-link" data-value-id="new" data-value-category="'.$item['parent_id'].'" data-value-parent-forum="'.$item['id'].'" data-value-editor-title="'.__('Add Sub-Forum', 'asgaros-forum').'">';
            $actionHTML .= __('Add Sub-Forum', 'asgaros-forum');
            $actionHTML .= '</a>';
        }

        return $actionHTML;
    }

    function get_columns() {
        $columns = array(
            'name'      => __('Name:', 'asgaros-forum'),
            'icon'      => __('Icon:', 'asgaros-forum'),
            'status'    => __('Status:', 'asgaros-forum'),
            'sort'      => __('Order:', 'asgaros-forum'),
            'actions'   => __('Actions:', 'asgaros-forum')
        );

        return $columns;
    }

    function prepare_items() {
        global $asgarosforum;

        $columns = $this->get_columns();
        $this->_column_headers = array($columns);

        $data = array();

        foreach ($this->table_data as $forum) {
            $data[] = $forum;

            if ($forum['count_subforums'] > 0) {
                $subforums = $asgarosforum->get_forums($forum['parent_id'], $forum['id'], true, ARRAY_A);

                if (!empty($subforums)) {
                    foreach ($subforums as $subforum) {
                        $data[] = $subforum;
                    }
                }
            }
        }

        $this->items = $data;
    }
}
