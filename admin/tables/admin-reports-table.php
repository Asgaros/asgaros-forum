<?php

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')){
    require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

class Asgaros_Forum_Admin_Reports_Table extends WP_List_Table {
    var $table_data = array();

    function __construct($table_data) {
        $this->table_data = $table_data;

        parent::__construct(
            array(
                'singular'  => 'report',
                'plural'    => 'reports',
                'ajax'      => false
            )
        );
    }

    function column_post($item) {
        $columnHTML = '';
        $columnHTML .= '<a class="make-bold" href="'.$item['post_link'].'" target="_blank">'.$item['topic_name'].'</a>';
        $columnHTML .= '<br>';

        $text = $item['post_text'];

        if (strlen($text) > 300) {
            $columnHTML .= mb_substr($text, 0, 300, 'UTF-8') . ' &hellip;';
        } else {
            $columnHTML .= $text;
        }

        return $columnHTML;
    }

    function column_author($item) {
        global $asgarosforum;
        $userdata = get_userdata($item['author_id']);

        if ($userdata) {
            return '<a href="'.admin_url('user-edit.php?user_id='.$item['author_id']).'">'.$userdata->display_name.'</a>';
        } else {
            return $asgarosforum->getUsername($item['author_id']);
        }
    }

    function column_reporters($item) {
        global $asgarosforum;
        $columnHTML = '';

        foreach ($item['reporters'] as $reporter) {
            $userdata = get_userdata($reporter);

            if ($userdata) {
                $columnHTML .= '<a href="'.admin_url('user-edit.php?user_id='.$reporter).'">'.$userdata->display_name.'</a><br>';
            } else {
                $columnHTML .= $asgarosforum->getUsername($reporter).'<br>';
            }
        }

        return $columnHTML;
    }

    function column_actions($item) {
        $columnHTML = '';
        $columnHTML .= '<a href="'.$item['post_link'].'" target="_blank">'.__('View Post', 'asgaros-forum').'</a>';
        $columnHTML .= ' &middot; ';
        $columnHTML .= '<a href="#" class="report-delete-link link-delete" data-value-id="'.$item['post_id'].'" data-value-editor-title="'.__('Delete Report', 'asgaros-forum').'">'.__('Delete Report', 'asgaros-forum').'</a>';

        return $columnHTML;
    }

    function get_columns() {
        $columns = array(
            'post'      => __('Post:', 'asgaros-forum'),
            'author'    => __('Post Author:', 'asgaros-forum'),
            'reporters' => __('Reporters:', 'asgaros-forum'),
            'actions'   => __('Actions:', 'asgaros-forum')
        );

        return $columns;
    }

    function prepare_items() {
        global $asgarosforum;

        $columns = $this->get_columns();
        $this->_column_headers = array($columns);

        // We need to initialize the links to make them work in the admin area.
        $asgarosforum->rewrite->set_links();

        $this->items = array();

        foreach ($this->table_data as $post_id => $reporter_ids) {
            $this->items[] = $asgarosforum->reports->get_report($post_id, $reporter_ids);
        }
    }
}
