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
        $columnHTML .= '<input type="hidden" id="forum_'.$item['id'].'_count_subforums" value="'.esc_html(stripslashes($item['count_subforums'])).'">';

        if ($item['parent_forum']) {
            $columnHTML .= '<div class="subforum">';
        } else {
            $columnHTML .= '<div class="parentforum">';
        }

        $forum_icon = trim(esc_html(stripslashes($item['icon'])));
        $forum_icon = (empty($forum_icon)) ? 'dashicons-editor-justify' : $forum_icon;
        $columnHTML .= '<span class="forum-icon dashicons-before '.$forum_icon.'"></span>';
        $columnHTML .= '<span class="make-bold">'.stripslashes($item['name']).' <span class="element-id">('.__('ID', 'asgaros-forum').': '.$item['id'].')</span></span>';
        $columnHTML .= '<br>';
        $columnHTML .= '<span class="forum-description">';

        if (empty($item['description'])) {
            $columnHTML .= '<span class="element-id">'.__('No description yet ...', 'asgaros-forum').'</span>';
        } else {
            $columnHTML .= stripslashes($item['description']);
        }

        $columnHTML .= '</span>';
        $columnHTML .= '<div class="clear"></div>';
        $columnHTML .= '</div>';

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
        $actionHTML .= '<a href="#" class="forum-delete-link link-delete" data-value-id="'.$item['id'].'" data-value-category="'.$item['parent_id'].'" data-value-editor-title="'.__('Delete Forum', 'asgaros-forum').'">';
        $actionHTML .= __('Delete Forum', 'asgaros-forum');
        $actionHTML .= '</a>';
        $actionHTML .= ' &middot; ';
        $actionHTML .= '<a href="#" class="forum-editor-link" data-value-id="'.$item['id'].'" data-value-category="'.$item['parent_id'].'" data-value-parent-forum="'.$item['parent_forum'].'" data-value-editor-title="'.__('Edit Forum', 'asgaros-forum').'">';
        $actionHTML .= __('Edit Forum', 'asgaros-forum');
        $actionHTML .= '</a>';

        if (!$item['parent_forum']) {
            $actionHTML .= ' &middot; ';
            $actionHTML .= '<a href="#" class="forum-editor-link" data-value-id="new" data-value-category="'.$item['parent_id'].'" data-value-parent-forum="'.$item['id'].'" data-value-editor-title="'.__('Add Sub-Forum', 'asgaros-forum').'">';
            $actionHTML .= __('Add Sub-Forum', 'asgaros-forum');
            $actionHTML .= '</a>';
        }

        return $actionHTML;
    }

    function get_columns() {
        $columns = array(
            'name'      => __('Name:', 'asgaros-forum'),
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
