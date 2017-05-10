<?php

if (!defined('ABSPATH')) exit;

class uc_usergroups {
    var $directory = '';

    function __construct($directory) {
        $this->directory = $directory;

        // Users list stuff
        add_action('delete_user', array($this, 'delete_term_relationships'));
        add_action('pre_user_query', array($this, 'user_query'));
		add_filter('views_users', array($this, 'views'));

        // Taxonomy stuff
        add_filter('manage_edit-user-group_columns', array($this,'manage_usergroup_columns'));
        add_action('manage_user-group_custom_column', array($this,'manage_usergroup_custom_column'), 10, 3);
        add_action('user-group_add_form_fields', array($this, 'add_color_form_field'));
		add_action('user-group_edit_form_fields', array($this, 'edit_color_form_field'));
        add_action('create_user-group', array($this, 'save_usergroup'));
		add_action('edit_user-group', array($this, 'save_usergroup'));

		/* Bulk edit */
		//add_action('admin_init', array(&$this, 'bulk_edit_action'));
		//add_filter('views_users', array(&$this, 'bulk_edit'));
	}

    /* USERS LIST STUFF */
    function delete_term_relationships($user_id) {
		wp_delete_object_term_relationships($user_id, 'user-group');
	}

    /* TAXONOMY STUFF */
    function manage_usergroup_columns($columns) {
		unset($columns['description'], $columns['posts'], $columns['slug']);

        $columns['users'] = __('Users', 'usergroup-content');
		$columns['color'] = __('Color', 'usergroup-content');

		return $columns;
	}

    function manage_usergroup_custom_column($out, $column, $term_id) {
        global $wpdb;

        if ($column === 'users') {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term_id));
			$term = $this->get_usergroup_by($term_id);
			$out = '<a href="'.admin_url('users.php?user-group='.$term->slug).'">'.sprintf(_n(__('%s User'), __('%s Users'), $count), $count).'</a>';
		} else if ($column === 'color') {
            $color = $this->get_usergroup_color($term_id);
			$out = '<div class="usergroup-color" style="background-color: '.$color.';"></div>';
		}

		return $out;
	}

    function add_color_form_field() {
        echo '<div class="form-field">';
            echo '<input type="text" value="#333333" class="custom-color" name="usergroup-color" data-default-color="#333333" />';
        echo '</div>';
    }

    function edit_color_form_field($term) {
        echo '<tr class="form-field">';
            echo '<th scope="row">'.__('Color', 'usergroup-content').'</th>';
            echo '<td>';
                $color = $this->get_usergroup_color($term->term_id);
                echo '<input type="text" value="'.$color.'" class="custom-color" name="usergroup-color" data-default-color="#333333" />';
            echo '</td>';
        echo '</tr>';
    }

    function save_usergroup($term_id) {
        if (isset($_POST['usergroup-color'])) {
            update_term_meta($term_id, 'usergroup-color', $_POST['usergroup-color']);
		}
	}

    /* NOT YET IMPLEMENTED */
    /*
	function bulk_edit_action() {
		if (!isset( $_REQUEST['bulkedituser-groupsubmit'] ) || empty($_POST['user-group'])) { return; }

		check_admin_referer('bulk-edit-user-group');

		// Get an array of users from the string
		parse_str(urldecode($_POST['users']), $users);

		if(empty($users)) { return; }

		$action = $_POST['groupaction'];

        foreach($users['users'] as $user) {
			$update_groups = array();
			$groups = $this->get_usergroups_for_user($user);
			foreach($groups as $group) {
				$update_groups[$group->slug] = $group->slug;
			}

			if($action === 'add') {
				if(!in_array($_POST['user-group'], $update_groups)) {
					$update_groups[] = $_POST['user-group'];
				}
			} elseif($action === 'remove') {
				unset($update_groups[$_POST['user-group']]);
			}

			// Delete all user groups if they're empty
			if(empty($update_groups)) { $update_groups = null; }

			self::save_user_usergroups( $user, $update_groups, true);
		}
	}

	function bulk_edit($views) {
		if (!current_user_can('edit_users') ) { return $views; }
		$terms = get_terms('user-group', array('hide_empty' => false));
		?>
		<form method="post" id="bulkedituser-groupform" class="alignright" style="clear:right; margin:0 10px;">
			<fieldset>
				<legend class="screen-reader-text"><?php _e('Update User Groups', 'usergroup-content'); ?></legend>
				<div>
					<label for="groupactionadd" style="margin-right:5px;"><input name="groupaction" value="add" type="radio" id="groupactionadd" checked="checked" /> <?php _e('Add users to', 'usergroup-content'); ?></label>
					<label for="groupactionremove"><input name="groupaction" value="remove" type="radio" id="groupactionremove" /> <?php _e('Remove users from', 'usergroup-content'); ?></label>
				</div>
				<div>
					<input name="users" value="" type="hidden" id="bulkedituser-groupusers" />

					<label for="usergroups-select" class="screen-reader-text"><?php _('User Group', 'usergroup-content'); ?></label>
					<select name="user-group" id="usergroups-select" style="max-width: 300px;">
						<?php
						$select = '<option value="">'.__( 'Select User Group&hellip;', 'usergroup-content').'</option>';
						foreach($terms as $term) {
							$select .= '<option value="'.$term->slug.'">'.$term->name.'</option>'."\n";
						}
						echo $select;
						?>
					</select>
					<?php wp_nonce_field('bulk-edit-user-group') ?>
				</div>
				<div class="clear" style="margin-top:.5em;">
					<?php submit_button( __( 'Update' ), 'small', 'bulkedituser-groupsubmit', false ); ?>
				</div>
			</fieldset>
		</form>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#bulkedituser-groupform').remove().insertAfter('ul.subsubsub');
				$('#bulkedituser-groupform').live('submit', function() {
					var users = $('.wp-list-table.users .check-column input:checked').serialize();
					$('#bulkedituser-groupusers').val(users);
				});
			});
		</script>
		<?php
		return $views;
	}*/

	function views($views) {
        $terms = $this->get_usergroups();

        if ($terms) {
            // Show name of current usergroup.
            $usergroup = (!empty($_GET['user-group'])) ? $this->get_usergroup_by($_GET['user-group'], 'slug') : false;

            if ($usergroup) {
                $color = $this->get_usergroup_color($usergroup->term_id);
                echo '<h2><div class="userlist-color" style="background-color: '.$color.';"></div>'.$usergroup->name.'</h2>';
            }

            // Build usergroup select dropdown
            $args = array();

            if (isset($_GET['s'])) {
                $args['s'] = $_GET['s'];
            }

            if (isset($_GET['role'])) {
                $args['role'] = $_GET['role'];
            }

            $form = '<label for="usergroups-select">'.__('Usergroups:', 'usergroup-content').' </label>';
            $form .= '<form method="get" action="'.esc_url(preg_replace('/(.*?)\/users/ism', 'users', add_query_arg($args, remove_query_arg('user-group')))).'" style="display: inline;">';
            $form .= '<select name="user-group" id="usergroups-select"><option value="0">'.__('All Users', 'usergroup-content').'</option>';

            foreach($terms as $term) {
    			$form .= '<option value="'.$term->slug.'"'.selected($term->slug, ($usergroup) ? $usergroup->slug : '', false).'>'.$term->name.'</option>';
    		}

            $form .= '</select>';
		    $form .= '</form>';
            $views['user-group'] = $form;
        }

		return $views;
	}

	function user_query($Query = '') {
		global $pagenow, $wpdb;

		if ($pagenow !== 'users.php') {
            return;
        }

		if (!empty($_GET['user-group'])) {
			$group = $_GET['user-group'];
			$ids = array();
			$term = $this->get_usergroup_by(esc_attr($group), 'slug');

            if (!empty($term)) {
    			$user_ids = $this->get_users_in_usergroup($term->term_id);

                if (!empty($user_ids)) {
        		    $ids = array_merge($user_ids, $ids);
        			$ids = implode(',', wp_parse_id_list( $user_ids ) );
        			$Query->query_where .= " AND $wpdb->users.ID IN ($ids)";
                } else {
                    $Query->query_where .= " AND $wpdb->users.ID IN (-1)";
                }
            }
		}
	}
}

?>
