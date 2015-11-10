<?php
if(!class_exists("MFAdmin"))
{
  class MFAdmin
  {
    public static function load_hooks()
    {
      add_action('admin_init', 'MFAdmin::maybe_save_options');
      add_action('admin_init', 'MFAdmin::maybe_save_ads_options');
      add_action('admin_init', 'MFAdmin::maybe_save_structure');
      add_action('admin_init', 'MFAdmin::maybe_save_user_groups');
      // add_action('admin_menu', 'MFAdmin::admin_menus');
      add_action('admin_enqueue_scripts', 'MFAdmin::enqueue_admin_scripts');
      add_action('edit_user_profile', 'MFAdmin::show_moderator_form');
      add_action('edit_user_profile_update', 'MFAdmin::save_moderator_form');
    }

    public static function enqueue_admin_scripts($hook)
    {
      global $mingleforum;

      $plug_url = plugin_dir_url(__FILE__) . '../';
      $l10n_vars = array( 'remove_category_warning' => __('WARNING: Deleting this Category will also PERMANENTLY DELETE ALL Forums, Topics, and Replies associated with it!!! Are you sure you want to delete this Category???', 'mingle-forum'),
                          'category_name_label' => __('Category Name:', 'mingle-forum'),
                          'category_description_label' => __('Description:', 'mingle-forum'),
                          'remove_category_a_title' => __('Remove this Category', 'mingle-forum'),
                          'images_url' => WPFURL . 'images/',
                          'remove_forum_warning' => __('WARNING: Deleting this Forum will also PERMANENTLY DELETE ALL Topics, and Replies associated with it!!! Are you sure you want to delete this Forum???', 'mingle-forum'),
                          'forum_name_label' => __('Forum Name:', 'mingle-forum'),
                          'forum_description_label' => __('Description:', 'mingle-forum'),
                          'remove_forum_a_title' => __('Remove this Forum', 'mingle-forum'),
                          'remove_user_group_warning' => __('Are you sure you want to remove this Group?', 'mingle-forum'),
                          'user_group_name_label' => __('Name:', 'mingle-forum'),
                          'user_group_description_label' => __('Description:', 'mingle-forum'),
                          'remove_user_group_a_title' => __('Remove this User Group', 'mingle-forum'),
                          'users_list' => json_encode($mingleforum->get_all_users_list()) );

      //Let's only load our shiz on mingle-forum admin pages
      if(strstr($hook, 'mingle-forum') !== false || $hook == 'user-edit.php')
      {
        $wp_scripts = new WP_Scripts();
        $ui = $wp_scripts->query('jquery-ui-core');
        $url = "//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/start/jquery-ui.css";

        wp_enqueue_style('mingle-forum-ui-css', $url);
        wp_enqueue_style('mingle-forum-inputosaurus-css', $plug_url . 'css/inputosaurus.css');
        wp_enqueue_style('mingle-forum-admin-css', $plug_url . "css/mf_admin.css");
        wp_enqueue_script('mingle-forum-inputosaurus-js', $plug_url . 'js/inputosaurus.js', array('jquery', 'jquery-ui-widget', 'jquery-ui-autocomplete'));
        wp_enqueue_script('mingle-forum-admin-js', $plug_url . "js/mf_admin.js", array('jquery-ui-accordion', 'jquery-ui-sortable'));
        wp_localize_script('mingle-forum-admin-js', 'MFAdmin', $l10n_vars);
      }
    }

    public static function show_moderator_form($user)
    {
      global $mingleforum;
      $mod = $mingleforum->get_moderator_forums($user->ID);

      if(is_super_admin())
      {
        $categories = $mingleforum->get_groups();
        require('views/moderators_profile_form.php');
      }
    }

    public static function save_moderator_form($user_id)
    {
      $global = isset($_POST['mf_global_moderator']);

      if($global)
        update_user_meta($user_id, 'wpf_moderator', 'mod_global');
      else
      {
        $forums = (isset($_POST['mf_moderator_forum_ids']))?$_POST['mf_moderator_forum_ids']:false;

        if(is_array($forums) && !empty($forums))
          update_user_meta($user_id, 'wpf_moderator', $forums);
        else
          delete_user_meta($user_id, 'wpf_moderator', $forums);
      }
    }

    public static function options_page()
    {
      global $mingleforum;

      $saved = (isset($_GET['saved']) && $_GET['saved'] == 'true');

      require('views/options_page.php');
    }

    public static function ads_options_page()
    {
      global $mingleforum;

      $saved = (isset($_GET['saved']) && $_GET['saved'] == 'true');

      require('views/ads_options_page.php');
    }

    public static function moderators_page()
    {
      global $mingleforum;

      $moderators = $mingleforum->get_moderators();

      require('views/moderators_page.php');
    }

    public static function user_groups_page()
    {
      global $mingleforum;

      $user_groups = $mingleforum->get_usergroups();
      $action = (isset($_GET['action']))?$_GET['action']:false;

      switch($action)
      {
        case 'users':
          $id = isset($_GET['id'])?$_GET['id']:false;

          if($id)
          {
            $usergroup = $mingleforum->get_usergroup($id);
            $usergroup_users = $mingleforum->get_members($id);
            require('views/user_groups_users_page.php');
          }
          else
            require('views/user_groups_page.php');
          break;

        default:
          require('views/user_groups_page.php');
          break;
      }
    }

    public static function structure_page()
    {
      global $mingleforum;

      $action = (isset($_GET['action']) && !empty($_GET['action']))?$_GET['action']:false;
      $categories = $mingleforum->get_groups();

      switch($action)
      {
        case 'forums':
          require('views/structure_page_forums.php');
          break;
        default:
          require('views/structure_page_categories.php');
          break;
      }
    }

    public static function maybe_save_user_groups()
    {
      global $mingleforum, $wpdb;
      $listed_user_groups = array();

      if(!isset($_POST['mf_user_groups_save']) || empty($_POST['mf_user_groups_save']))
        return;

      if(!isset($_POST['user_group_name']) || empty($_POST['user_group_name']))
        return;

      foreach($_POST['user_group_name'] as $i => $v)
      {
        $id = $_POST['mf_user_group_id'][$i];
        $name = stripslashes($v);
        $description = (!empty($_POST['user_group_description'][$i]))?stripslashes($_POST['user_group_description'][$i]):'';

        if(empty($name)) //If no name, don't save this User Group
          continue;

        if($id == 'new') //Create a new User Group
        {
          $wpdb->insert($mingleforum->t_usergroups,
                        array('name' => $name, 'description' => $description),
                        array('%s', '%s'));

          $listed_user_groups[] = $wpdb->insert_id;
        }
        else //Update an existing User Group
        {
          $q = "UPDATE {$mingleforum->t_usergroups}
                  SET `name` = %s, `description` = %s
                  WHERE `id` = %d";

          $wpdb->query($wpdb->prepare($q, $name, $description, $id));

          $listed_user_groups[] = $id;
        }
      }

      //Delete user groups that the user removed from the list
      if(!empty($listed_user_groups))
      {
        $listed_user_groups = implode(',', $listed_user_groups);
        $user_group_ids = $wpdb->get_col("SELECT `id` FROM {$mingleforum->t_usergroups} WHERE `id` NOT IN ({$listed_user_groups})");

        if(!empty($user_group_ids))
          foreach($user_group_ids as $ugid)
            self::delete_usergroup($ugid);
      }

      wp_redirect(admin_url('admin.php?page=mingle-forum-user-groups&saved=true'));
      exit();
    }

    public static function delete_usergroup($ugid)
    {
      global $mingleforum, $wpdb;

      $wpdb->query("DELETE FROM {$mingleforum->t_usergroup2user} WHERE `group` = {$ugid}");
      $wpdb->query("DELETE FROM {$mingleforum->t_usergroups} WHERE `id` = {$ugid}");

      //Remove this group from categories too
      $cats = $wpdb->get_results("SELECT * FROM {$mingleforum->t_groups}");

      if(!empty($cats))
        foreach($cats as $cat)
        {
          $usergroups = (array)unserialize($cat->usergroups);

          if(in_array($ugid, $usergroups))
          {
            $usergroups = serialize(array_diff($usergroups, array($ugid)));

            $wpdb->query("UPDATE {$mingleforum->t_groups} SET `usergroups` = '{$usergroups}' WHERE `id` = {$cat->id}");
          }
        }
    }

    public static function maybe_save_options()
    {
      global $wpdb, $mingleforum;

      $saved_ops = array();

      if(!isset($_POST['mf_options_submit']) || empty($_POST['mf_options_submit']))
        return;

      foreach($mingleforum->default_ops as $k => $v)
      {
        if(isset($_POST[$k]) && !empty($_POST[$k]))
        {
          if(is_array($v))
            $saved_ops[$k] = explode(',', $_POST[$k]);
          elseif(is_numeric($v))
            $saved_ops[$k] = (int)$_POST[$k];
          elseif(is_bool($v))
            $saved_ops[$k] = true;
          else
            $saved_ops[$k] = $wpdb->escape(stripslashes($_POST[$k]));
        }
        else
        {
          if(is_array($v))
            $saved_ops[$k] = array();
          elseif(is_numeric($v))
            $saved_ops[$k] = $v;
          elseif(is_bool($v))
            $saved_ops[$k] = false;
          else
            $saved_ops[$k] = '';
        }
      }

      //Set some stuff that isn't on the options page
      $saved_ops['forum_skin'] = $mingleforum->options['forum_skin'];
      $saved_ops['forum_db_version'] = $mingleforum->options['forum_db_version'];

      update_option('mingleforum_options', $saved_ops);
      wp_redirect(admin_url('admin.php?page=mingle-forum&saved=true'));
      exit();
    }

    public static function maybe_save_ads_options()
    {
      global $wpdb, $mingleforum;

      if(!isset($_POST['mf_ads_options_save']) || empty($_POST['mf_ads_options_save']))
        return;

      $mingleforum->ads_options = array('mf_ad_above_forum_on' => isset($_POST['mf_ad_above_forum_on']),
                                        'mf_ad_above_forum' => $wpdb->escape(stripslashes($_POST['mf_ad_above_forum_text'])),
                                        'mf_ad_below_forum_on' => isset($_POST['mf_ad_below_forum_on']),
                                        'mf_ad_below_forum' => $wpdb->escape(stripslashes($_POST['mf_ad_below_forum_text'])),
                                        'mf_ad_above_branding_on' => isset($_POST['mf_ad_above_branding_on']),
                                        'mf_ad_above_branding' => $wpdb->escape(stripslashes($_POST['mf_ad_above_branding_text'])),
                                        'mf_ad_above_info_center_on' => isset($_POST['mf_ad_above_info_center_on']),
                                        'mf_ad_above_info_center' => $wpdb->escape(stripslashes($_POST['mf_ad_above_info_center_text'])),
                                        'mf_ad_above_quick_reply_on' => isset($_POST['mf_ad_above_quick_reply_on']),
                                        'mf_ad_above_quick_reply' => $wpdb->escape(stripslashes($_POST['mf_ad_above_quick_reply_text'])),
                                        'mf_ad_below_menu_on' => isset($_POST['mf_ad_below_menu_on']),
                                        'mf_ad_below_menu' => $wpdb->escape(stripslashes($_POST['mf_ad_below_menu_text'])),
                                        'mf_ad_below_first_post_on' => isset($_POST['mf_ad_below_first_post_on']),
                                        'mf_ad_below_first_post' => $wpdb->escape(stripslashes($_POST['mf_ad_below_first_post_text'])),
                                        'mf_ad_custom_css' => strip_tags($_POST['mf_ad_custom_css']));

      update_option('mingleforum_ads_options', $mingleforum->ads_options);

      wp_redirect(admin_url('admin.php?page=mingle-forum-ads&saved=true'));
      exit();
    }

    public static function maybe_save_structure()
    {
      if(isset($_POST['mf_categories_save']) && !empty($_POST['mf_categories_save']))
        self::process_save_categories();

      if(isset($_POST['mf_forums_save']) && !empty($_POST['mf_forums_save']))
        self::process_save_forums();
    }

    public static function process_save_categories()
    {
      global $wpdb, $mingleforum;

      $order = 10000; //Order is DESC for some reason
      $listed_categories = array();
      $name = $description = $id = null;

      foreach($_POST['mf_category_id'] as $key => $value)
      {
        $name = (!empty($_POST['category_name'][$key]))?stripslashes($_POST['category_name'][$key]):false;
        $description = (!empty($_POST['category_description'][$key]))?stripslashes($_POST['category_description'][$key]):'';
        $id = (isset($value) && is_numeric($value))?$value:'new';

        if($name !== false) //$name is required before we do any saving
        {
          if($id == 'new')
          {
            //Save new category
            $wpdb->insert($mingleforum->t_groups,
                          array('name' => $name, 'description' => $description, 'sort' => $order),
                          array('%s', '%s', '%d'));

            $listed_categories[] = $wpdb->insert_id;
          }
          else
          {
            //Update existing category
            $usergroups = (isset($_POST['category_usergroups_'.$id]))?serialize((array)$_POST['category_usergroups_'.$id]):'';
            $q = "UPDATE {$mingleforum->t_groups}
                    SET `name` = %s, `description` = %s, `sort` = %d, `usergroups` = %s
                    WHERE `id` = %d";

            $wpdb->query($wpdb->prepare($q, $name, $description, $order, $usergroups, $id));

            $listed_categories[] = $id;
          }
        }

        $order -= 5;
      }

      //Delete categories that the user removed from the list
      if(!empty($listed_categories))
      {
        $listed_categories = implode(',', $listed_categories);
        $category_ids = $wpdb->get_col("SELECT `id` FROM {$mingleforum->t_groups} WHERE `id` NOT IN ({$listed_categories})");

        if(!empty($category_ids))
          foreach($category_ids as $cid)
            self::delete_category($cid);
      }

      wp_redirect(admin_url('admin.php?page=mingle-forum-structure&saved=true'));
      exit();
    }

    public static function process_save_forums()
    {
      global $wpdb, $mingleforum;

      $order = 100000; //Order is DESC for some reason
      $listed_forums = array();
      $name = $description = $id = null;
      $categories = $mingleforum->get_groups();

      if(empty($categories)) //This should never happen, but just in case
        return;

      foreach($categories as $category)
      {
        foreach($_POST['mf_forum_id'][$category->id] as $key => $value)
        {
          $name = (!empty($_POST['forum_name'][$category->id][$key]))?stripslashes($_POST['forum_name'][$category->id][$key]):false;
          $description = (!empty($_POST['forum_description'][$category->id][$key]))?stripslashes($_POST['forum_description'][$category->id][$key]):'';
          $id = (isset($value) && is_numeric($value))?$value:'new';

          if($name !== false) //$name is required before we do any saving
          {
            if($id == 'new')
            {
              //Save new forum
              $wpdb->insert($mingleforum->t_forums,
                            array('name' => $name, 'description' => $description, 'sort' => $order, 'parent_id' => $category->id),
                            array('%s', '%s', '%d', '%d'));

              $listed_forums[] = $wpdb->insert_id;
            }
            else
            {
              //Update existing forum
              $q = "UPDATE {$mingleforum->t_forums}
                      SET `name` = %s, `description` = %s, `sort` = %d, `parent_id` = %d
                      WHERE `id` = %d";

              $wpdb->query($wpdb->prepare($q, $name, $description, $order, $category->id, $id));

              $listed_forums[] = $id;
            }
          }

          $order -= 5;
        }
      }

      //Delete forums that the user removed from the list
      if(!empty($listed_forums))
      {
        $listed_forums = implode(',', $listed_forums);
        $forum_ids = $wpdb->get_col("SELECT `id` FROM {$mingleforum->t_forums} WHERE `id` NOT IN ({$listed_forums})");

        if(!empty($forum_ids))
          foreach($forum_ids as $fid)
            self::delete_forum($fid);
      }

      wp_redirect(admin_url('admin.php?page=mingle-forum-structure&action=forums&saved=true'));
      exit();
    }

    public static function delete_category($cid)
    {
      global $wpdb, $mingleforum;

      //First delete all associated forums
      $forum_ids = $wpdb->get_col("SELECT `id` FROM {$mingleforum->t_forums} WHERE `parent_id` = {$cid}");
      if(!empty($forum_ids))
        foreach($forum_ids as $fid)
          self::delete_forum($fid);

      $wpdb->query("DELETE FROM {$mingleforum->t_groups} WHERE `id` = {$cid}");
    }

    public static function delete_forum($fid)
    {
      global $wpdb, $mingleforum;

      //First delete all associated topics
      $topic_ids = $wpdb->get_col("SELECT `id` FROM {$mingleforum->t_threads} WHERE `parent_id` = {$fid}");
      if(!empty($topic_ids))
        foreach($topic_ids as $tid)
          self::delete_topic($tid);

      $wpdb->query("DELETE FROM {$mingleforum->t_forums} WHERE `id` = {$fid}");
    }

    public static function delete_topic($tid)
    {
      global $wpdb, $mingleforum;

      //First delete all associated replies
      $wpdb->query("DELETE FROM {$mingleforum->t_posts} WHERE `parent_id` = {$tid}");
      $wpdb->query("DELETE FROM {$mingleforum->t_threads} WHERE `id` = {$tid}");
    }
  } //End class
} //End if
?>
