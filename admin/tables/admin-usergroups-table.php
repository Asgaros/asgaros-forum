<?php

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')){
    require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

class Asgaros_Forum_Admin_UserGroups_Table extends WP_List_Table {
    var $table_data = array();

    function __construct($table_data) {
        $this->table_data = $table_data;

        parent::__construct(
            array(
                'singular'  => 'usergroup',
                'plural'    => 'usergroups',
                'ajax'      => false
            )
        );
    }

    function column_default($item, $column_name) {
        return $item[$column_name];
    }

    function column_name($item) {
        $columnHTML = '';
        $columnHTML .= '<input type="hidden" id="usergroup_'.$item['term_id'].'_name" value="'.esc_html(stripslashes($item['name'])).'">';
        $columnHTML .= '<input type="hidden" id="usergroup_'.$item['term_id'].'_color" value="'.esc_html(stripslashes($item['color'])).'">';
        $columnHTML .= '<a class="usergroup-name" href="'.admin_url('users.php?forum-user-group='.$item['term_id']).'">'.stripslashes($item['name']).'</a>';

        return $columnHTML;
    }

    function column_color($item) {
        return '<div class="usergroup-color" style="background-color: '.$item['color'].';"></div>';
    }

    function column_actions($item) {
        $columnHTML = '';
        $columnHTML .= '<a href="#" class="usergroup-delete-link" data-value-id="'.$item['term_id'].'" data-value-editor-title="'.__('Delete User Group', 'asgaros-forum').'">'.__('Delete', 'asgaros-forum').'</a>';
        $columnHTML .= ' | ';
        $columnHTML .= '<a href="#" class="usergroup-editor-link" data-value-id="'.$item['term_id'].'" data-value-editor-title="'.__('Edit User Group', 'asgaros-forum').'">'.__('Edit', 'asgaros-forum').'</a>';

        return $columnHTML;
    }

    function get_columns() {
        $columns = array(
            'name'      => __('Name:', 'asgaros-forum'),
            'color'     => __('Color:', 'asgaros-forum'),
            'users'     => __('Users:', 'asgaros-forum'),
            'actions'   => __('Actions:', 'asgaros-forum')
        );

        return $columns;
    }

    function prepare_items() {
        $columns = $this->get_columns();
        $this->_column_headers = array($columns);

        $data = array();

        foreach ($this->table_data as $usergroup) {
            $usergroup = (array)$usergroup; // Convert object to array.
            $usergroup['color'] = AsgarosForumUserGroups::getUserGroupColor($usergroup['term_id']);
            $usergroup['users'] = AsgarosForumUserGroups::countUsersInUserGroup($usergroup['term_id']);
            $data[] = $usergroup;
        }

        $this->items = $data;
    }
}
