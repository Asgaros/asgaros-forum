<?php

//include("wpf_define.php");
class mingleforumadmin
{

  var $admin_tabs = array();
  var $cur_tab = "";

  function delete_usergroups()
  {
    if (isset($_POST['delete_usergroups']))
    {
      global $wpdb, $table_prefix;
      $delete_usrgrp = $_POST['delete_usrgrp'];
      $groups = "";
      $count = count($delete_usrgrp);
      for ($i = 0; $i < $count; $i++)
      {
        if (is_numeric($delete_usrgrp[$i]))
        {
          $wpdb->query("DELETE FROM " . $table_prefix . "forum_usergroups WHERE id = {$delete_usrgrp[$i]}");
          $wpdb->query("DELETE FROM " . $table_prefix . "forum_usergroup2user WHERE `group` = {$delete_usrgrp[$i]}");
        }
      }
      return true;
    }
    return false;
  }

  function add_usergroup()
  {
    if (isset($_GET['do']) && $_GET['do'] == "addusergroup" && !isset($_POST['add_usergroup']))
    {
      include("wpf-add-usergroup.php");
      return false;
    }

    global $wpdb, $table_prefix;
    $name = isset($_POST['group_name']) ? $wpdb->escape($_POST['group_name']) : "";
    $desc = isset($_POST['group_description']) ? $wpdb->escape($_POST['group_description']) : "";
    if (isset($_POST['add_usergroup']))
    {
      if ($_POST['group_name'] == null || $_POST['group_name'] == "")
        return __("You must specify a user group name.", "mingleforum");
      else if ($wpdb->get_var("SELECT id FROM " . $table_prefix . "forum_usergroups WHERE name = '$name'"))
        return __("You have choosen a name that already exists in the database, please specify another", "mingleforum");
      $wpdb->query("INSERT INTO " . $table_prefix . "forum_usergroups (name, description) VALUES('$name', '$desc')");
      return __("User Group successfully added.", "mingleforum");
    }
    return false;
  }

  function add_user_togroup()
  {
    global $wpdb, $table_prefix, $mingleforum;
    if (isset($_GET['do']) && $_GET['do'] == "add_user_togroup" && !isset($_POST['add_user_togroup']))
    {
      include("wpf-addusers.php");
      return false;
    }
    $warnings = 0;
    $errors = 0;
    $added = 0;
    if (isset($_POST['add_user_togroup']))
    {
      $users = explode(",", $_POST['togroupusers']);

      if ($_POST['togroupusers'] == "")
      {
        return __("You haven't specified any user to add:", "mingleforum");
      }
      $group = (is_numeric($_POST['usergroup'])) ? $_POST['usergroup'] : "add_user_null";
      if ($group == "add_user_null")
        return __("You must choose a user group", "mingleforum");

      foreach ($users as $user)
      {
        if ($user)
        {
          trim($user);
          $id = username_exists($user);
          if (!$id)
          {
            $user = htmlentities($user, ENT_QUOTES);
            $msg = "<strong>" . __("Error", "mingleforum") . " - </strong> " . __("No such user:", "mingleforum") . " \"{$user}\"<br />";
            ++$errors;
          }
          elseif ($mingleforum->is_user_ingroup($id, $group))
          {
            $user = htmlentities($user, ENT_QUOTES);
            $msg = "<strong>" . __("Warning", "mingleforum") . " - </strong> " . __("User", "mingleforum") . " \"{$user}\" " . __("is already in this group", "mingleforum") . "<br />";
            ++$warnings;
          }
          else
          {
            $user = htmlentities($user, ENT_QUOTES);
            $msg = __("User", "mingleforum") . " \"{$user}\" " . __("added successfully", "mingleforum") . "<br />";
            $sql = "INSERT INTO " . $table_prefix . "forum_usergroup2user (user_id, `group`) VALUES('$id', '$group')";
            $wpdb->query($sql);
            ++$added;
          }
        }
      }
      return
              __("Errors:", "mingleforum") . " $errors,
          " . __("Warnings:", "mingleforum") . " $warnings,
          " . __("Users added:", "mingleforum") . " $added
          <br/>-------------------------------<br/> $msg";
    }
    return false;
  }

  function usergroups()
  {
    global $wpdb, $mingleforum, $table_prefix;
    $usergroups = $mingleforum->get_usergroups();

    echo "<div class='wrap'>";

    if ($this->delete_usergroups())
      echo '<div id="message" class="updated fade"><p>' . __('User Group(s) successfully deleted.', 'mingleforum') . '</p></div>';
    if ($msg = $this->add_usergroup())
      echo "<div id='message' class='updated fade'><p>$msg</p></div>";

    if ($msg = $this->add_user_togroup())
      echo "<div id='message' class='updated fade'><p>$msg</p></div>";
    if (isset($_GET['do']) && $_GET['do'] == "removemember" && is_numeric($_GET['memberid']) && is_numeric($_GET['groupid']))
    {
      $count = $wpdb->query("DELETE FROM " . $table_prefix . "forum_usergroup2user WHERE user_id = {$_GET['memberid']} AND `group` = {$_GET['groupid']}");
      echo "<div id='message' class='updated fade'><p>" . __("Member successfully removed.", "mingleforum") . "</p></div>";
    }
    if (isset($_GET['do']) && $_GET['do'] == "edit_usergroup")
    {
      include("wpf-usergroup-edit.php");
    }
    $image = WPFURL . "images/user.png";
    echo "<h2><img src='$image' />" . __("Mingle Forum >> Manage User Groups", "mingleforum") . " <a class='button' href='admin.php?page=mfgroups&mingleforum_action=usergroups&do=addusergroup'> " . __("add new", "mingleforum") . "</a></h2> ";
    $usergroups = $mingleforum->get_usergroups();
    /*     * ************************************** */
    if ($usergroups)
    {
      echo "<form method='post' name='delete_usergroups_form' action='admin.php?page=mfgroups&mingleforum_action=usergroups'>";
      echo "<div class='tablenav'>
            <div class='alignleft'>
  <input type='submit' name='delete_usergroups' class='button-secondary delete' value='" . __("Delete", "mingleforum") . "'/>
            </div>
            <br class='clear' />
          </div>
            <br class='clear' />";

      foreach ($usergroups as $usergroup)
      {
        echo "<table class='widefat'>
            <thead>
              <tr>
                <th  class='check-column'><input type='checkbox' value='$usergroup->id' name='delete_usrgrp[]' /></th>
                <th><a href='admin.php?page=mfgroups&mingleforum_action=usergroups&do=edit_usergroup&usergroup_id=$usergroup->id'>" . stripslashes($usergroup->name) . "</th>
                <th>" . stripslashes($usergroup->description) . "</th>
              </tr>
            </thead>";

        /* echo "<tr class='alternate'>
          <th class='check-column'><input type='checkbox' value='$usergroup->id' name='delete_usrgrp[]' /></th>
          <td><a href='admin.php?page=mfgroups&mingleforum_action=usergroups&do=edit_usergroup&usergroup_id=$usergroup->id'>$usergroup->name</a></td>
          <td>$usergroup->description</td>
          </tr>"; */

        $members = $mingleforum->get_members($usergroup->id);
        if ($members)
        {
          echo "<tr>
                  <td colspan='3'>
                    <table class='wpf-wide'>
                  <tr>
                    <th>" . __("Members", "mingleforum") . "</th>
                    <th>Name</th>
                    <th>Info</th>
                  </tr>";
          foreach ($members as $member)
          {
            $user = get_userdata($member->user_id);
            echo "<tr><td>" . $user->user_login . " <a href='admin.php?page=mfgroups&mingleforum_action=usergroups&do=removemember&memberid=$member->user_id&groupid=$usergroup->id'> (" . __("Remove", "mingleforum") . ")</a></td>
                  <td>" . get_user_meta($member->user_id, "first_name", true) . " " . get_user_meta($member->user_id, "last_name", true) . "</td>
                  <td><a href='" . ADMIN_PROFILE_URL . "$member->user_id'>" . __("View profile", "mingleforum") . "</a></td>
                  </tr>";
          }
          echo "<tr>
              <td colspan='3' align='right'><a href='admin.php?page=mfgroups&mingleforum_action=usergroups&do=add_user_togroup'>" . __("Add members", "mingleforum") . "</a></td>
            </tr></table>
            </td></tr>";
        }
        else
        {
          echo "<tr><td colspan='3'>" . __("No members in this group", "mingleforum") . "</tr></td>";
          echo "<tr><td align='right' colspan='3'><a href='admin.php?page=mfgroups&mingleforum_action=usergroups&do=add_user_togroup'>" . __("Add members", "mingleforum") . "</td></tr>";
        }
        echo "</table><br class='clear' /><br />";
      }
      echo "</form>";
    }


    echo "</div>";
  }

  function activate_skin()
  {
    if (isset($_GET['action']) && $_GET['action'] == "activateskin")
    {
      $op = get_option('mingleforum_options');

      $options = array('wp_posts_to_forum' => $op['wp_posts_to_forum'],
          'forum_posts_per_page' => $op['forum_posts_per_page'],
          'forum_threads_per_page' => $op['forum_threads_per_page'],
          'forum_require_registration' => $op['forum_require_registration'],
          'forum_show_login_form' => $op['forum_show_login_form'],
          'forum_date_format' => $op['forum_date_format'],
          'forum_use_gravatar' => $op['forum_use_gravatar'],
          'forum_show_bio' => $op['forum_show_bio'],
          'forum_skin' => $_GET['skin'],
          'forum_use_rss' => $op['forum_use_rss'],
          'forum_use_seo_friendly_urls' => $op['forum_use_seo_friendly_urls'],
          'forum_allow_image_uploads' => $op['forum_allow_image_uploads'],
          'notify_admin_on_new_posts' => $op['notify_admin_on_new_posts'],
          'forum_captcha' => $op['forum_captcha'],
          'hot_topic' => $op['hot_topic'],
          'veryhot_topic' => $op['veryhot_topic'],
          'forum_display_name' => $op['forum_display_name'],
          'level_one' => $op['level_one'],
          'level_two' => $op['level_two'],
          'level_three' => $op['level_three'],
          'level_newb_name' => $op['level_newb_name'],
          'level_one_name' => $op['level_one_name'],
          'level_two_name' => $op['level_two_name'],
          'level_three_name' => $op['level_three_name'],
          'forum_db_version' => $op['forum_db_version'],
          'forum_disabled_cats' => $op['forum_disabled_cats'],
          'allow_user_replies_locked_cats' => $op['allow_user_replies_locked_cats'],
          'forum_posting_time_limit' => $op['forum_posting_time_limit'],
          'forum_hide_branding' => $op['forum_hide_branding'],
          'forum_login_url' => $op['forum_login_url'],
          'forum_signup_url' => $op['forum_signup_url'],
          'forum_logout_redirect_url' => $op['forum_logout_redirect_url'],
      );

      update_option('mingleforum_options', $options);

      return true;
    }
    return false;
  }

  function skins()
  {
    $class = "";
    // Find all skins within directory
    // Open a known directory, and proceed to read its contents
    if ($this->activate_skin())
      echo '<div id="message" class="updated fade"><p>' . __('Skin successfully activated.', 'mingleforum') . '</p></div>';

    $op = get_option('mingleforum_options');
    if (is_dir(SKINDIR))
    {
      if ($dh = opendir(SKINDIR))
      {
        $image = WPFURL . "images/logomain.png";
        echo "<div class='wrap'><h2><img src='$image' />" . __("Mingle Forum >> Skin options", "mingleforum") . "</h2><br class='clear' /><table class='widefat'>
          <h3><a style='color:blue;' href='http://cartpauj.icomnow.com/forum/?mingleforumaction=viewforum&f=5.0'>" . __("Get More Skins", "mingleforum") . "</a></h3>
            <thead>
              <tr>
                <th>" . __("Screenshot", "mingleforum") . "</th>
                <th >" . __("Name", "mingleforum") . "</th>
                <th >" . __("Version", "mingleforum") . "</th>
                <th >" . __("Description", "mingleforum") . "</th>
                <th >" . __("Action", "mingleforum") . "</th>

              </tr>
            </thead>";
        //SHOW DEFAULT THEME
        $filed = "Default";
        $p = file_get_contents(OLDSKINDIR . "Default/style.css");
        $class = ($class == "alternate") ? "" : "alternate";
        echo "<tr class='{$class}'>
                <td><a href='" . OLDSKINURL . "Default/screenshot.jpg'><img src='" . OLDSKINURL . "Default/screenshot.jpg' width='100' height='100'></a></td>
                <td>" . $this->get_skinmeta('Name', $p) . "</td>
                <td>" . $this->get_skinmeta('Version', $p) . "</td>
                <td>" . $this->get_skinmeta('Description', $p) . "</td>";
        if ($op['forum_skin'] == "Default")
          echo "<td>" . __("In Use", "mingleforum") . "</td></tr>";
        else
          echo "<td><a href='admin.php?page=mfskins&mingleforum_action=skins&action=activateskin&skin={$filed}'>" . __("Activate", "mingleforum") . "</a></td></tr>";
        //SHOW THE REST OF THE THEMES
        while (($file = readdir($dh)) !== false)
        {
          if (filetype(SKINDIR . $file) == "dir" && $file != ".." && $file != "." && substr($file, 0, 1) != ".")
          {
            $p = file_get_contents(SKINDIR . $file . "/style.css");
            $class = ($class == "alternate") ? "" : "alternate";

            echo "<tr class='$class'>
                  <td>" . $this->get_skinscreenshot($file) . "</td>
                  <td>" . $this->get_skinmeta('Name', $p) . "</td>
                  <td>" . $this->get_skinmeta('Version', $p) . "</td>
                  <td>" . $this->get_skinmeta('Description', $p) . "</td>";
            if ($op['forum_skin'] == $file)
              echo "<td>" . __("In Use", "mingleforum") . "</td></tr>";
            else
              echo "<td><a href='admin.php?page=mfskins&mingleforum_action=skins&action=activateskin&skin={$file}'>" . __("Activate", "mingleforum") . "</a></td></tr>";
          }
        }
      }
    }
    echo "</table></div>";
  }

  // PNG | JPG | GIF | only
  function get_skinscreenshot($file)
  {
    $exts = array("png", "jpg", "gif");
    foreach ($exts as $ext)
    {
      if (file_exists(SKINDIR . "$file/screenshot.$ext"))
      {
        $image = SKINURL . "$file/screenshot.$ext";
        return "<a href='$image'><img src='$image' width='100' height='100'></a>";
      }
    }
    return "<img src='" . NO_SKIN_SCREENSHOT_URL . "' width='100' height='100'>";
  }

  function get_skinmeta($field, $data)
  {
    if (preg_match("|$field:(.*)|i", $data, $match))
    {
      $match = $match[1];
    }
    return $match;
  }

  function about()
  {
    $image = WPFURL . "images/logomain.png";
    echo " <div class='wrap'>
        <h2><img src='$image'>" . __("About Mingle Forum", "mingleforum") . "</h2>
               <table class='widefat'> <thead>
              <tr>
        <th>" . __("Current Version: ", "mingleforum") . "<strong>" . $this->get_version() . "</strong></th>

              </tr>
            </thead><tr class='alternate'><td style='padding: 20px'>
        <p><strong>" . __("Mingle Forum has one simple mission; to 'KEEP IT SIMPLE!' It was taken over from WP Forum and has been improved upon GREATLY. It now fully supports integration with or without the Mingle plugin (by Blair Williams). Also I want to give a big thanks to Eric Hamby for his previous work on the forum script.", "mingleforum") . "</strong></p>
        <ul>
<li><h3>" . __("Author: ", "mingleforum") . "<a href='http://cartpauj.com'>Cartpauj</a></h3></li>
<strong>" . __("Plugin Page:", "mingleforum") . "</strong> <a class='button' href='http://cartpauj.com/projects/mingle-forum-plugin'>Mingle Forum</a><br /><br />
<strong>" . __("Support Forum:", "mingleforum") . "</strong>  <a class='button' href='http://cartpauj.icomnow.com/forum'>Support Forum</a><br /><br />
<strong>" . __("Mingle Forum Skins:", "mingleforum") . "</strong>  <a class='button' href='http://cartpauj.icomnow.com/forum/?mingleforumaction=viewforum&f=5.0'>Get More Skins</a>
        </ul>
                </td></tr>
       </table>
      </div>";
  }

  function get_usercount()
  {
    global $wpdb, $table_prefix;
    return $wpdb->get_var("SELECT count(*) from " . $table_prefix . "users");
  }

  function get_dbsize()
  {
    global $wpdb;
    $size = '';
    $res = $wpdb->get_results("SHOW TABLE STATUS");
    foreach ($res as $r)
      $size += $r->Data_length + $r->Index_length;

    return $this->formatfilesize($size);
  }

  function formatfilesize($data)
  {
    // bytes
    if ($data < 1024)
    {
      return $data . " bytes";
    }
    // kilobytes
    else if ($data < 1024000)
    {
      return round(( $data / 1024), 1) . "k";
    }
    // megabytes
    else
    {
      return round(( $data / 1024000), 1) . " MB";
    }
  }

  function get_version()
  {
    $plugin_data = implode('', file(ABSPATH . "wp-content/plugins/" . WPFPLUGIN . "/wpf-main.php"));
    if (preg_match("|Version:(.*)|i", $plugin_data, $version))
    {
      $version = $version[1];
    }
    return $version;
  }

  function options()
  {
    if ($this->option_save())
      echo '<div id="message" class="updated fade"><p>' . __('Options successfully saved.', 'mingleforum') . '</p></div>';
    global $mingleforum;
    $op = get_option('mingleforum_options');
    $image = WPFURL . "images/chart.png";
    echo '<div class="wrap">
      <h2><img src="' . $image . '" />' . __("Mingle Forum", "mingleforum") . '</h2><br class="clear" />
      <table class="widefat">
        <thead>
          <tr>
            <th scope="col">' . __("Statistic", "mingleforum") . '</th>
            <th scope="col">' . __("Value", "mingleforum") . '</th>
          </tr>
        </thead>
          <tr class="alternate">
            <td>' . __("Number of posts:", "mingleforum") . '</td>
            <td>' . $mingleforum->num_posts_total() . '</td>
          </tr>
          <tr>
            <td>' . __("Number of threads:", "mingleforum") . '</td>
            <td>' . $mingleforum->num_threads_total() . '</td>
          </tr>
          <tr class="alternate">
            <td>' . __("Number of users:", "mingleforum") . '</td>
            <td>' . $this->get_usercount() . '</td>
          </tr>
          <tr>
            <td>' . __("Total database size:", "mingleforum") . '</td>
            <td>' . $this->get_dbsize() . '</td>
          </tr>
          <tr class="alternate">
            <td>' . __("Database server:", "mingleforum") . '</td>
            <td>' . mysql_get_server_info() . '</td>
          </tr>
          <tr>
            <td>' . __("Mingle Forum version:", "mingleforum") . '</td>
            <td>' . $this->get_version() . '</td>
          </tr>
      </table>';
    $image = WPFURL . "images/logomain.png";
    echo '<h2><img src="' . $image . '" />' . __("Mingle Forum >> General Options", "mingleforum") . '</h2>';
    echo '<form id="mingleforum_option_form" name="mingleforum_option_form" method="post" action="">';

    if (function_exists('wp_nonce_field'))
      wp_nonce_field('mingleforum-manage_option');
    $defStr = __("default ", "mingleforum");
    echo "<table class='widefat'>
    <thead>
      <tr>
      <th>" . __("Option Name", "mingleforum") . "</th>
      <th>" . __("Option Input", "mingleforum") . "</th>
      </tr>
    </thead>

    <tr class='alternate'>
      <td>" . __("Integrate WordPress Posts with Forum:", "mingleforum") . "</td>
        <td><input type='checkbox' name='wp_posts_to_forum' value='true'";
    if ($op['wp_posts_to_forum'] == 'true')
      echo "checked='checked'";
    echo "/> ($defStr = " . __('Off', 'mingleforum') . ")</td>
    </tr>

    <tr class='alternate'>
      <td>" . __("Posts per page:", "mingleforum") . "</td>
      <td><input type='text' name='forum_posts_per_page' value='" . $op['forum_posts_per_page'] . "' /> ($defStr = 10)</td>
    </tr>
    <tr class='alternate'>
      <td>" . __("Threads per page:", "mingleforum") . "</td>
      <td><input type='text' name='forum_threads_per_page' value='" . $op['forum_threads_per_page'] . "' /> ($defStr = 20)</td>

    </tr>

    <tr class='alternate'>
      <td>" . __("Number of posts for Hot Topic:", "mingleforum") . "</td>
      <td><input type='text' name='hot_topic' value='" . $op['hot_topic'] . "' /> ($defStr = 15)</td>
    </tr>
    <tr class='alternate'>
      <td>" . __("Number of posts for Very Hot Topic:", "mingleforum") . "</td>
      <td><input type='text' name='veryhot_topic' value='" . $op['veryhot_topic'] . "' /> ($defStr = 25)</td>
    </tr>
    <tr class='alternate'>
      <td>" . __("Username Display:", "mingleforum") . "</td>
      <td>
        <select name='forum_display_name'>";
    $display_names = array('user_login', 'nickname', 'display_name', 'first_name', 'last_name');
    foreach ($display_names as $name)
      if ($name == $op['forum_display_name'])
        echo "<option value='" . $name . "' selected='selected'>" . $name . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>";
      else
        echo "<option value='" . $name . "'>" . $name . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>";
    echo "</select> ($defStr = user_login)
      </td>
    </tr>
    <tr class='alternate'>
      <td>" . __("New User's Title:", "mingleforum") . "</td>
      <td><input type='text' name='level_newb_name' value='" . $op['level_newb_name'] . "' /> ($defStr = " . __('Newbie', 'mingleforum') . ")</td>
    </tr>
    <tr class='alternate'>
      <td>" . __("User Level 1 Title:", "mingleforum") . "</td>
      <td><input type='text' name='level_one_name' value='" . $op['level_one_name'] . "' /> ($defStr = " . __('Beginner', 'mingleforum') . ")</td>
    </tr>
    <tr class='alternate'>
      <td>" . __("User Level 1 Count:", "mingleforum") . "</td>
      <td><input type='text' name='level_one' value='" . $op['level_one'] . "' /> ($defStr = " . __('25', 'mingleforum') . ")</td>
    </tr>
    <tr class='alternate'>
      <td>" . __("User Level 2 Title:", "mingleforum") . "</td>
      <td><input type='text' name='level_two_name' value='" . $op['level_two_name'] . "' /> ($defStr = " . __('Advanced', 'mingleforum') . ")</td>
    </tr>
    <tr class='alternate'>
      <td>" . __("User Level 2 Count:", "mingleforum") . "</td>
      <td><input type='text' name='level_two' value='" . $op['level_two'] . "' /> ($defStr = " . __('50', 'mingleforum') . ")</td>
    </tr>
    <tr class='alternate'>
      <td>" . __("User Level 3 Title:", "mingleforum") . "</td>
      <td><input type='text' name='level_three_name' value='" . $op['level_three_name'] . "' /> ($defStr = " . __('Pro', 'mingleforum') . ")</td>
    </tr>
    <tr class='alternate'>
      <td>" . __("User Level 3 Count:", "mingleforum") . "</td>
      <td><input type='text' name='level_three' value='" . $op['level_three'] . "' /> ($defStr = " . __('100', 'mingleforum') . ")</td>
    </tr>
    <tr class='alternate'>
      <td>" . __("Notify Admin on new posts:", "mingleforum") . "</td>
        <td><input type='checkbox' name='notify_admin_on_new_posts' value='true'";
    if ($op['notify_admin_on_new_posts'] == 'true')
      echo "checked='checked'";
    echo "/> ($defStr = " . __('Off', 'mingleforum') . ")</td>
    </tr>

    <tr class='alternate'>
      <td>" . __("Show Forum Login Form:", "mingleforum") . "</td>
        <td><input type='checkbox' name='forum_show_login_form' value='true'";
    if ($op['forum_show_login_form'] == 'true')
      echo "checked='checked'";
    echo "/> ($defStr = " . __('On', 'mingleforum') . ")</td>
    </tr>";

    $login_url = (isset($op['forum_login_url']) && !empty($op['forum_login_url'])) ? stripslashes($op['forum_login_url']) : wp_login_url(get_permalink($mingleforum->page_id));
    $signup_url = (isset($op['forum_signup_url']) && !empty($op['forum_signup_url'])) ? stripslashes($op['forum_signup_url']) : wp_login_url() . '?action=register';
    $redirect_url = (isset($op['forum_logout_redirect_url']) && !empty($op['forum_logout_redirect_url'])) ? stripslashes($op['forum_logout_redirect_url']) : get_permalink($mingleforum->page_id);

    echo "
    <tr class='alternate'>
      <td>" . __("Login URL:", "mingleforum") . "<br/><small>" . __('Used only if Login Form disabled above', 'mingleforum') . "</small></td>
      <td><input type='text' name='forum_login_url' value='" . $login_url . "' /></td>
    </tr>

    <tr class='alternate'>
      <td>" . __("Signup URL:", "mingleforum") . "</td>
      <td><input type='text' name='forum_signup_url' value='" . $signup_url . "' /></td>
    </tr>

    <tr class='alternate'>
      <td>" . __("Logout Redirect URL:", "mingleforum") . "</td>
      <td><input type='text' name='forum_logout_redirect_url' value='" . $redirect_url . "' /></td>
    </tr>

    <tr class='alternate'>
      <td>" . __("Show Avatars in the forum:", "mingleforum") . "</td>
        <td><input type='checkbox' name='forum_use_gravatar' value='true'";
    if ($op['forum_use_gravatar'] == 'true')
      echo "checked='checked'";
    echo "/> ($defStr = " . __('On', 'mingleforum') . ")</td>
    </tr>

    <tr class='alternate'>
      <td>" . __("Allow Images to be uploaded:", "mingleforum") . "</td>
        <td><input type='checkbox' name='forum_allow_image_uploads' value='true'";
    if ($op['forum_allow_image_uploads'] == 'true')
      echo "checked='checked'";
    echo "/> ($defStr = " . __('Off', 'mingleforum') . ")</td>
    </tr>

    <tr class='alternate'>
      <td>" . __("Show users Signature at the bottom of posts:", "mingleforum") . "</td>
        <td><input type='checkbox' name='forum_show_bio' value='true'";
    if ($op['forum_show_bio'] == 'true')
      echo "checked='checked'";
    echo "/> ($defStr = " . __('On', 'mingleforum') . ")</td>
    </tr>

    <tr class='alternate'>
      <td>" . __("Use Forum RSS:", "mingleforum") . "</td>
        <td><input type='checkbox' name='forum_use_rss' value='true'";
    if ($op['forum_use_rss'] == 'true')
      echo "checked='checked'";
    echo "/> ($defStr = " . __('On', 'mingleforum') . ")</td>
    </tr>

    <tr class='alternate'>
      <td>" . __("Use SEO friendly URLs:", "mingleforum") . "<br/><small>" . __("IMPORTANT: Leave this option off if your permalinks are set to 'default'", "mingleforum") . "</small></td>
        <td><input type='checkbox' name='forum_use_seo_friendly_urls' value='true'";
    if ($op['forum_use_seo_friendly_urls'] == 'true')
      echo "checked='checked'";
    echo "/> ($defStr = " . __('Off', 'mingleforum') . ")</td>
    </tr>

      <tr class='alternate'>
        <td>" . __("Registration required to post:", "mingleforum") . "</td>
          <td><input type='checkbox' name='forum_require_registration' value='true'";
    if ($op['forum_require_registration'] == 'true')
      echo "checked='checked'";
    echo "/> ($defStr = " . __('On', 'mingleforum') . ")</td></tr>";

    if (function_exists("gd_info"))
    {
      $gd = gd_info();
      $status = "";
      $lib = "<br /><strong>" . __("Installed version:", "mingleforum") . " {$gd['GD Version']}</strong>";
    }
    else
    {
      $status = "disabled";
      $lib = "<br /><strong>" . __("GD Library is not installed", "mingleforum") . "</strong>";
    }
    echo "<tr class='alternate'>
        <td>" . __("Use Captcha for unregistered users:", "mingleforum") . "</td>
          <td><input type='checkbox' name='forum_captcha' value='true' $status";
    if ($op['forum_captcha'] == 'true')
      echo "checked='checked'";
    echo "/> (" . __("Requires ", "mingleforum") . "<a href='http://www.libgd.org/Main_Page'>GD</a> library - " . __("If you have 'Registration required to post' above enabled, leave this off", "mingleforum") . ") $lib</td>
      </tr>";

    echo "<tr class='alternate'>
        <td valign='top'>" . __("Date format:", "mingleforum") . "</td><td><input type='text' name='forum_date_format' value='" . $op['forum_date_format'] . "' /> <p>" . __("Default date:", "mingleforum") . " \"F j, Y, H:i\". <br />" . __("Check ", "mingleforum") . "<a href='http://www.php.net'>http://www.php.net</a> " . __("for date formatting.", "mingleforum") . "</p></td>
      </tr>";

    echo "<tr class='alternate'>
        <td valign='top'>"
    . __("Closed Categories (Admin posting only):", "mingleforum") . "<br/><small>" . __("Comma separated list of Category ID's (Ex: 1, 2, 3)", "mingleforum") . "</small>
          <br/><br/>"
    . __("Users can reply in locked categories", "mingleforum") . "
        </td>
        <td>
          <input type='text' name='forum_disabled_cats' value='" . implode(",", $op['forum_disabled_cats']) . "' />
          <br/><br/>
          <input type='checkbox' name='allow_user_replies_locked_cats' value='true'";
    if ($op['allow_user_replies_locked_cats'] == 'true')
      echo "checked='checked'";
    echo "/> ($defStr = " . __('Off', 'mingleforum') . ")
        </td>
      </tr>";

    echo "<tr class='alternate'>
        <td valign='top'>" . __("Time limit between posting:", "mingleforum") . "<br/><small>" . __("Prevent lots of SPAM by making users wait a time period between posts.", "mingleforum") . "</small></td><td><input type='text' name='forum_posting_time_limit' value='" . stripslashes($op['forum_posting_time_limit']) . "' />" . __('seconds', 'mingleforum') . "</td>
      </tr>";

    echo "<tr class='alternate'>
        <td>" . __("Disable Branding:", "mingleforum") . "</td>
          <td><input type='checkbox' name='forum_hide_branding' value='true'";
    if ($op['forum_hide_branding'] == 'true')
      echo "checked='checked'";
    echo "/> ($defStr = " . __('Off', 'mingleforum') . ")</td></tr>";

    echo "<tr class='alternate'>
      <td colspan='2'>
      <span style='float:left'><input class='button' type='submit' name='mingleforum_option_save' value='" . __("Save options", 'mingleforum') . "'  /></span>
      <span class='button' style='float:right'><a href='http://cartpauj.com' target='_blank'>cartpauj.com</a></span>
    </tr>
    </table>
    </form>";
  }

  function option_save()
  {
    if (isset($_POST['mingleforum_option_save']))
    {
      global $wpdb, $table_prefix, $mingleforum;
      $op = get_option('mingleforum_options');
      $options = array('wp_posts_to_forum' => $_POST['wp_posts_to_forum'],
          'forum_posts_per_page' => $wpdb->escape($_POST['forum_posts_per_page']),
          'forum_threads_per_page' => $wpdb->escape($_POST['forum_threads_per_page']),
          'forum_require_registration' => $_POST['forum_require_registration'],
          'forum_show_login_form' => $_POST['forum_show_login_form'],
          'forum_date_format' => $wpdb->escape($_POST['forum_date_format']),
          'forum_use_gravatar' => $_POST['forum_use_gravatar'],
          'forum_show_bio' => $_POST['forum_show_bio'],
          'forum_skin' => $op['forum_skin'],
          'forum_use_rss' => $_POST['forum_use_rss'],
          'forum_use_seo_friendly_urls' => $_POST['forum_use_seo_friendly_urls'],
          'forum_allow_image_uploads' => $_POST['forum_allow_image_uploads'],
          'notify_admin_on_new_posts' => $_POST['notify_admin_on_new_posts'],
          'forum_captcha' => $_POST['forum_captcha'],
          'hot_topic' => $wpdb->escape($_POST['hot_topic']),
          'veryhot_topic' => $wpdb->escape($_POST['veryhot_topic']),
          'forum_display_name' => $_POST['forum_display_name'],
          'level_one' => $wpdb->escape($_POST['level_one']),
          'level_two' => $wpdb->escape($_POST['level_two']),
          'level_three' => $wpdb->escape($_POST['level_three']),
          'level_newb_name' => $wpdb->escape($_POST['level_newb_name']),
          'level_one_name' => $wpdb->escape($_POST['level_one_name']),
          'level_two_name' => $wpdb->escape($_POST['level_two_name']),
          'level_three_name' => $wpdb->escape($_POST['level_three_name']),
          'forum_db_version' => $op['forum_db_version'],
          'forum_disabled_cats' => explode(",", $wpdb->escape($_POST['forum_disabled_cats'])),
          'allow_user_replies_locked_cats' => $_POST['allow_user_replies_locked_cats'],
          'forum_posting_time_limit' => $wpdb->escape($_POST['forum_posting_time_limit']),
          'forum_hide_branding' => $_POST['forum_hide_branding'],
          'forum_login_url' => $wpdb->escape(stripslashes($_POST['forum_login_url'])),
          'forum_signup_url' => $wpdb->escape(stripslashes($_POST['forum_signup_url'])),
          'forum_logout_redirect_url' => $wpdb->escape(stripslashes($_POST['forum_logout_redirect_url']))
      );

      update_option('mingleforum_options', $options);

      //Update rewrite
      $mingleforum->flush_wp_rewrite_rules();

      return true;
    }
    return false;
  }

  function delete_forum_group()
  {
    if (isset($_POST['delete_forum_groups']))
    {
      global $wpdb, $table_prefix;
      $msg = "";
      $table_forums = $table_prefix . "forum_forums";
      $table_groups = $table_prefix . "forum_groups";
      $table_threads = $table_prefix . "forum_threads";
      $table_posts = $table_prefix . "forum_posts";
      $thread_count = 0;
      $post_count = 0;
      $group_count = 0;
      $forum_count = 0;

      $groups = $_POST['delete_groups'];
      $forums = $_POST['delete_forums'];

      $forum_num = count($forums);
      $group_num = count($groups);

      // Delete marked groups
      for ($i = 0; $i < $group_num; $i++)
      {

        // Get all forums
        $forumsb = $wpdb->get_results("select id from $table_forums where parent_id = {$groups[$i]}");

        // Loop trough the forums
        foreach ($forumsb as $forum)
        {

          // Get all threads
          $threads = $wpdb->get_results("select id from $table_threads where parent_id = $forum->id");

          // Delete threads
          $thread_count += $wpdb->query("DELETE FROM $table_threads WHERE parent_id = $forum->id");

          // Loop through the threads
          foreach ($threads as $thread)
          {

            // Delete posts
            $post_count += $wpdb->query("DELETE FROM $table_posts WHERE parent_id = $thread->id");
          }
          // Delete forums
          $forum_count += $wpdb->query("DELETE FROM $table_forums WHERE parent_id = {$groups[$i]}");
        }
        // Delete the group
        $group_count += $wpdb->query("DELETE FROM $table_groups WHERE id = {$groups[$i]}");
      }

      // Delete marked forums
      for ($i = 0; $i < $forum_num; $i++)
      {

        $threads = $wpdb->get_results("select id from $table_threads where parent_id = {$forums[$i]}");

        foreach ($threads as $thread)
        {

          $post_count += $wpdb->query("DELETE FROM $table_posts WHERE parent_id = $thread->id");
        }
        $thread_count += $wpdb->query("DELETE FROM $table_threads WHERE parent_id = {$forums[$i]}");

        $forum_count += $wpdb->query("DELETE FROM $table_forums WHERE id = {$forums[$i]}");
      }
      $msg .= __("Groups deleted:", "mingleforum") . " " . $group_count . "<br/>"
              . __("Forums deleted:", "mingleforum") . " " . $forum_count . "<br/>"
              . __("Threads deleted:", "mingleforum") . " " . $thread_count . "<br/>"
              . __("Posts deleted:", "mingleforum") . " " . $post_count . "<br/>";

      return $msg;
    }
    return false;
  }

  function edit_forum_group()
  {
    global $mingleforum;
    if (isset($_GET['do']) && $_GET['do'] == "editgroup")
    {
      include("wpf-edit-forum-group.php");
    }
    if (isset($_GET['do']) && $_GET['do'] == "editforum")
    {
      include("wpf-edit-forum-group.php");
    }
  }

  function add_group()
  {
    if (isset($_POST['add_group_submit']))
    {
      global $wpdb, $table_prefix;

      $add_group_description = $wpdb->escape($_POST['add_group_description']);
      $add_group_name = $wpdb->escape($_POST['add_group_name']);

      if ($add_group_name == "")
        return __("You must enter a name", "mingleforum");
      if ($wpdb->get_var("SELECT id FROM " . $table_prefix . "forum_groups WHERE name = '$add_group_name'"))
        return __("You have choosen a name that already exists in the database, please specify another", "mingleforum");

      $max = $wpdb->get_var("SELECT MAX(sort) from " . $table_prefix . "forum_groups") + 1;

      $wpdb->query("INSERT INTO " . $table_prefix . "forum_groups (name, description, sort)
        VALUES('$add_group_name', '$add_group_description', '$max')");

      return __("Category added successfully", "mingleforum");
    }
    return false;
  }

  function add_forum()
  {
    if (isset($_POST['add_forum_submit']))
    {
      global $wpdb, $table_prefix;
      $add_forum_description = $wpdb->escape(strip_tags($_POST['add_forum_description']));
      $add_forum_name = $wpdb->escape(strip_tags($_POST['add_forum_name']));
      $add_forum_group_id = $wpdb->escape($_POST['add_forum_group_id']);
      if ($_POST['add_forum_group_id'] == "add_forum_null")
        return __("You must select a category", "mingleforum");

      if ($_POST['add_forum_name'] == "")
        return __("You must enter a name", "mingleforum");

      if ($wpdb->get_var("select id from " . $table_prefix . "forum_forums where name = '$add_forum_name' and parent_id = $add_forum_group_id"))
        return __("You have choosen a forum name that already exists in this group, please specify another", "mingleforum");

      $max = $wpdb->get_var("SELECT MAX(sort) from " . $table_prefix . "forum_forums WHERE parent_id = $add_forum_group_id") + 1;

      $wpdb->query("INSERT INTO " . $table_prefix . "forum_forums (name, description, parent_id, sort)
        VALUES('$add_forum_name', '$add_forum_description', '$add_forum_group_id', '$max')");

      return __("Forum added successfully", "mingleforum");
    }
    return false;
  }

  function structure()
  {
    global $mingleforum;
    if ($msg = $this->delete_forum_group())
      echo "<div id='message' class='updated fade'><p>$msg</p></div>";
    if ($msg = $this->move_up_down())
      echo "<div id='message' class='updated fade'><p>$msg</p></div>";
    if ($msg = $this->add_group())
      echo "<div id='message' class='updated fade'><p>$msg</p></div>";
    if ($msg = $this->add_forum())
      echo "<div id='message' class='updated fade'><p>$msg</p></div>";

    if (isset($_GET['do']) && $_GET['do'] == "addforum")
      include('wpf-add-forum.php');

    if (isset($_GET['do']) && $_GET['do'] == "addgroup")
      include('wpf-add-group.php');


    // Check if group/forum update is nessesrary
    $image = WPFURL . "images/table.png";
    $this->edit_forum_group();
    echo "<div class='wrap'>";
    echo "<h2><img src='$image' />" . __("Mingle Forum >> Categories and Forums ", "mingleforum") . "</h2>";



    $groups = $mingleforum->get_groups();

    echo "<a href='admin.php?page=mfstructure&mingleforum_action=structure&do=addgroup' class='button'>" . __("add new", "mingleforum") . "</a>";

    echo "<form method='post' name='delete_forum_groups_form' action='admin.php?page=mfstructure&mingleforum_action=structure'>";



    //echo "<tr><td><a href='$edit_link'>$group->sort $group->name</a></td><td><a href='$up_link'>&#x2191;</a> | <a href='$down_link'>&#x2193;</a></td>";

    foreach ($groups as $group)
    {
      $up_link = "admin.php?page=mfstructure&mingleforum_action=structure&do=group_up&id=$group->id";
      $down_link = "admin.php?page=mfstructure&mingleforum_action=structure&do=group_down&id=$group->id";
      $edit_link = "admin.php?page=mfstructure&mingleforum_action=structure&do=editgroup&groupid=$group->id";

      echo "<table class='widefat'>";
      echo "<thead><tr>
          <th class='check-column'><input type='checkbox' value='$group->id' name='delete_groups[]' /></th>
          <th>" . stripslashes($group->name) . " <a href='$edit_link'>" . __("Modify", "mingleforum") . "</a></th>
          <th nowrap><a href='$up_link'>&#x2191;</a> | <a href='$down_link'>&#x2193;</a></th>
          <th></th>
          <th></th>
          </tr></thead><br />";


      /* echo "<tr class='alternate'>
        <th class='check-column'><input type='checkbox' value='$group->id' name='delete_groups[]' /></th>
        <td>$group->name</td>
        <td></td>
        <td>$group->description</td>
        <td><a href='$up_link'>&#x2191;</a> | <a href='$down_link'>&#x2193;</a></td>
        <td><strong><a href='$edit_link'>".__("Modify", "mingleforum")."</a></strong></td>
        </tr><br />"; */


      $forums = $mingleforum->get_forums($group->id);

      if ($forums)
      {
        foreach ($forums as $forum)
        {
          $up_link = "admin.php?page=mfstructure&mingleforum_action=structure&do=forum_up&id=$forum->id";
          $down_link = "admin.php?page=mfstructure&mingleforum_action=structure&do=forum_down&id=$forum->id";
          $edit_link = "admin.php?page=mfstructure&mingleforum_action=structure&do=editforum&forumid=$forum->id";

          echo "<tr>
            <th class='check-column'><input type='checkbox' value='$forum->id' name='delete_forums[]' /></th>
            <td> -- " . stripslashes($forum->name) . "</td>
            <th nowrap><a href='$up_link'>&#x2191;</a> | <a href='$down_link'>&#x2193;</a></th>
            <td>" . stripslashes($forum->description) . "</td>
            <td><a href='$edit_link'>" . __("Modify", "mingleforum") . "</a></td>
          </tr>";
        } // foreach($forums as $forum)
      } // if($forums)
      echo "<tr>
    <td colspan='2' align='left'><input type='submit' name='delete_forum_groups' class='button-secondary delete' value='" . __("Delete", "mingleforum") . "'/></td>
          <td colspan='3' align='right'><a href='admin.php?page=mfstructure&mingleforum_action=structure&do=addforum&groupid=$group->id'>" . __("Add forum", "mingleforum") . "</a></td>
        </tr>
      </table><br class='clear' />";
    } // foreach($groups as $group)



    echo "</form></div>";
  }

  function move_up_down()
  {
    global $wpdb, $table_prefix;
    $msg = "";
    if (isset($_GET['do']) && is_numeric($_GET['id']))
    {
      switch ($_GET['do'])
      {
        /* ------------------------------------------------------------------------------------------------------------------------ */
        case "group_down":
          $ginfo = $wpdb->get_row("SELECT * FROM {$table_prefix}forum_groups WHERE id = '" . ($_GET['id'] * 1) . "'", ARRAY_A);
          $above = $wpdb->get_row("SELECT * FROM {$table_prefix}forum_groups WHERE sort < '" . $ginfo['sort'] . "' ORDER BY sort DESC", ARRAY_A);
          if ($above['id'] > 0)
          {
            $wpdb->query("UPDATE {$table_prefix}forum_groups SET sort = '" . $above['sort'] . "' WHERE id = '" . ($_GET['id'] * 1) . "'");
            $wpdb->query("UPDATE {$table_prefix}forum_groups SET sort = '" . $ginfo['sort'] . "' WHERE id = '" . $above['id'] . "'");
          }
          $msg = __("Group Moved Down", "mingleforum");
          break;
        /* ------------------------------------------------------------------------------------------------------------------------ */
        case "forum_down":
          $ginfo = $wpdb->get_row("SELECT * FROM {$table_prefix}forum_forums WHERE id = '" . ($_GET['id'] * 1) . "'", ARRAY_A);
          $above = $wpdb->get_row("SELECT * FROM {$table_prefix}forum_forums WHERE parent_id = '" . $ginfo['parent_id'] . "' && sort < '" . $ginfo['sort'] . "' ORDER BY sort DESC", ARRAY_A);
          if ($above['id'] > 0)
          {
            $wpdb->query("UPDATE {$table_prefix}forum_forums SET sort = '" . $above['sort'] . "' WHERE id = '" . ($_GET['id'] * 1) . "'");
            $wpdb->query("UPDATE {$table_prefix}forum_forums SET sort = '" . $ginfo['sort'] . "' WHERE id = '" . $above['id'] . "'");
          }
          $msg = __("Forum Moved Down", "mingleforum");
          break;
        /* ------------------------------------------------------------------------------------------------------------------------ */
        case "group_up":
          $ginfo = $wpdb->get_row("SELECT * FROM {$table_prefix}forum_groups WHERE id = '" . ($_GET['id'] * 1) . "'", ARRAY_A);
          $above = $wpdb->get_row("SELECT * FROM {$table_prefix}forum_groups WHERE sort > '" . $ginfo['sort'] . "' ORDER BY sort ASC", ARRAY_A);
          if ($above['id'] > 0)
          {
            $wpdb->query("UPDATE {$table_prefix}forum_groups SET sort = '" . $above['sort'] . "' WHERE id = '" . ($_GET['id'] * 1) . "'");
            $wpdb->query("UPDATE {$table_prefix}forum_groups SET sort = '" . $ginfo['sort'] . "' WHERE id = '" . $above['id'] . "'");
          }
          $msg = __("Group Moved Up", "mingleforum");
          break;
        /* ------------------------------------------------------------------------------------------------------------------------ */
        case "forum_up":
          $ginfo = $wpdb->get_row("SELECT * FROM {$table_prefix}forum_forums WHERE id = '" . ($_GET['id'] * 1) . "'", ARRAY_A);
          $above = $wpdb->get_row("SELECT * FROM {$table_prefix}forum_forums WHERE parent_id = '" . $ginfo['parent_id'] . "' && sort > '" . $ginfo['sort'] . "' ORDER BY sort ASC", ARRAY_A);
          if ($above['id'] > 0)
          {
            $wpdb->query("UPDATE {$table_prefix}forum_forums SET sort = '" . $above['sort'] . "' WHERE id = '" . ($_GET['id'] * 1) . "'");
            $wpdb->query("UPDATE {$table_prefix}forum_forums SET sort = '" . $ginfo['sort'] . "' WHERE id = '" . $above['id'] . "'");
          }
          $msg = __("Forum Moved Up", "mingleforum");
          break;
        /* ------------------------------------------------------------------------------------------------------------------------ */
      }
      return $msg;
    }
    return false;
  }

  function update_usergroups($new_groups, $group_id)
  {
    global $wpdb, $table_prefix;
    $new_groups = maybe_serialize($new_groups);
    $wpdb->query("UPDATE " . $table_prefix . "forum_groups SET usergroups = '$new_groups' WHERE id = $group_id");
  }

  function get_usersgroups_with_access_to_group($groupid)
  {
    global $wpdb, $table_prefix;
    $string = $wpdb->get_var("select usergroups from " . $table_prefix . "forum_groups where id = $groupid");
    return maybe_unserialize($string);
  }

  function edit_moderator()
  {
    if (isset($_POST['update_mod']))
    {
      $forums = (isset($_POST['mod_forum_id'])) ? $_POST['mod_forum_id'] : array();
      $forums = maybe_unserialize($forums);

      $global = (isset($_POST['mod_global'])) ? true : false;
      $user_id = $_POST['update_mod_user_id'];
      if ($global)
      {
        update_user_meta($user_id, "wpf_moderator", "mod_global");
      }
      else
        update_user_meta($user_id, "wpf_moderator", $forums);

      if (empty($forums))
        return __('Moderator successfully removed.', 'mingleforum');
      else
        return __('Moderator successfully saved.', 'mingleforum');
    }
    if (isset($_POST['delete_mod']))
    {
      $user_id = $_POST['update_mod_user_id'];
      if (delete_user_meta($user_id, "wpf_moderator"))
        return __('Moderator successfully removed.', 'mingleforum');
      else
        return __('Moderator NOT removed.', 'mingleforum');
    }

    return false;
  }

  function add_moderator()
  {
    if (isset($_POST['add_mod_submit']))
    {
      global $wpdb, $table_prefix;
      $user_id = $_POST['addmod_user_id'];
      $forums = (isset($_POST['mod_forum_id'])) ? $_POST['mod_forum_id'] : array();
      $forums = maybe_unserialize($forums);
      $global = (isset($_POST['mod_global'])) ? true : false;
      if ($user_id == "add_mod_null")
        return __("You must select a user", "mingleforum");

      if ($global)
      {
        update_user_meta($user_id, "wpf_moderator", "mod_global");
        return __("Global Moderator added successfully", "mingleforum");
      }
      else
        update_user_meta($user_id, "wpf_moderator", $forums);
      return __("Moderator added successfully", "mingleforum");
    }
    return false;
  }

  function moderators()
  {
    global $wpdb, $table_prefix, $mingleforum;

    $forums = $mingleforum->get_forums();
    $groups = $mingleforum->get_groups();

    if ($msg = $this->edit_moderator())
      echo "<div id='message' class='updated fade'><p>$msg</p></div>";

    if ($msg = $this->add_moderator())
      echo "<div id='message' class='updated fade'><p>$msg</p></div>";
    echo "<div class='wrap'>";

    if (isset($_GET['do']) && $_GET['do'] == "add_moderator")
    {
      include('wpf-moderator.php');
    }
    $mods = $mingleforum->get_moderators();
    $image = WPFURL . "images/user.png";
    echo "<h2><img src='$image' />" . __("Mingle Forum >> Manage Moderators", "mingleforum") . " <a class='button' href='admin.php?page=mfmods&mingleforum_action=moderators&do=add_moderator'>(" . __("add new", "mingleforum") . ")</a></h2>";

    if ($mods)
    {
      foreach ($mods as $mod)
      {
        echo "<form name='update_mod_form-$mod->user_id' action='admin.php?page=mfmods&mingleforum_action=moderators' method='post'>
          <table class='widefat''>
            <thead>
              <tr>
                <th>$mod->user_login</th>
                <th>" . __("Currently moderating", "mingleforum") . "</th>
              </tr>
            </thead>
              <tr>
                <td><input type='submit' name='update_mod' value='" . __("Update", "mingleforum") . "' /><br />
                <input type='submit' name='delete_mod' value='" . __("Remove", "mingleforum") . "' />
                </td>
                <td>";
        if (get_user_meta($mod->user_id, "wpf_moderator", true) == "mod_global")
          $global_checked = "checked='checked'";
        else
          $global_checked = "";

        echo "<p class='wpf-alignright'
          ><input type='checkbox' onclick='invertAll(this, this.form, \"mod_forum_id\");' name='mod_global' id='mod_global' $global_checked value='mod_global'/> <strong>" . __("Global moderator: (User can moderate all forums)", "mingleforum") . "</strong></p>";
        foreach ($groups as $group)
        {
          $forums = $mingleforum->get_forums($group->id);
          echo "<p class='wpf-bordertop'><strong>" . stripslashes($group->name) . "</strong></p>";
          foreach ($forums as $forum)
          {
            if ($mingleforum->is_moderator($mod->user_id, $forum->id))
              $checked = "checked='checked'";
            else
              $checked = "";
            echo "<p class='wpf-indent'><input type='checkbox' onclick='uncheckglobal(this, this.form);' $checked name='mod_forum_id[]' id='mod_forum_id' value='$forum->id' /> $forum->name</p>
                      <input type='hidden' name='update_mod_user_id' value='$mod->user_id' />";
          }
        }
        echo "</td>
              </tr>
              </form></table><br class='clear' />";
      }
    }
    else
      echo "<p>" . __("No moderators yet", "mingleforum") . "</p>";
    echo "</div>";
  }

  function convert_moderators()
  {
    global $wpdb, $table_prefix;
    if (!get_option('wpf_mod_option_vers'))
    {
      $mods = $wpdb->get_results("SELECT user_id, user_login, meta_value FROM $wpdb->usermeta
          INNER JOIN $wpdb->users ON $wpdb->usermeta.user_id=$wpdb->users.ID WHERE meta_key = 'moderator' AND meta_value <> ''");
      foreach ($mods as $mod)
      {
        $string = explode(",", substr_replace($mod->meta_value, "", 0, 1));
        update_user_meta($mod->user_id, 'wpf_moderator', maybe_serialize($string));
        update_option('wpf_mod_option_vers', '2');
      }
    }
    else
      echo "Moderators updated";
  }

  function ads()
  {
    global $mingleforum;
    $image = WPFURL . "images/logomain.png";
    if ($this->save_mf_ads())
    {
      ?>
      <div id='message' class='updated fade'><p><?php echo __("Ads saved successfully", "mingleforum"); ?></p></div>
      <?php
    }
    ?>
    <div class="wrap">
      <h2><img src="<?php echo $image; ?>" /><?php echo __("Mingle Forum Ads >> options", "mingleforum"); ?></h2>
      <form method="post" action="">
        <h4><?php echo __('HTML is allowed in all ad areas below', 'mingleforum'); ?></h4>
        <table class="widefat">
          <thead>
            <tr>
              <th width="100%"><?php echo __('Ads Option', 'mingleforum'); ?></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <input type="checkbox" name="mf_ad_above_forum_on" value="true" <?php
                if ($mingleforum->ads_options['mf_ad_above_forum_on'])
                {
                  echo 'checked="checked"';
                }
                ?> />
                <strong><?php echo __('Enable Area Above Forum', 'mingleforum'); ?></strong><br/>
                <textarea name="mf_ad_above_forum_text" rows="10" cols="60"><?php echo stripslashes($mingleforum->ads_options['mf_ad_above_forum']) ?></textarea><br/>
                <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-above-forum</small>
              </td>
            </tr>
            <tr>
              <td>
                <input type="checkbox" name="mf_ad_below_forum_on" value="true" <?php
                if ($mingleforum->ads_options['mf_ad_below_forum_on'])
                {
                  echo 'checked="checked"';
                }
                ?> />
                <strong><?php echo __('Enable Area Below Forum', 'mingleforum'); ?></strong><br/>
                <textarea name="mf_ad_below_forum_text" rows="10" cols="60"><?php echo stripslashes($mingleforum->ads_options['mf_ad_below_forum']) ?></textarea><br/>
                <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-below-forum</small>
              </td>
            </tr>
            <tr>
              <td>
                <input type="checkbox" name="mf_ad_above_branding_on" value="true" <?php
                if ($mingleforum->ads_options['mf_ad_above_branding_on'])
                {
                  echo 'checked="checked"';
                }
                ?> />
                <strong><?php echo __('Enable Area Above Branding', 'mingleforum'); ?></strong><br/>
                <textarea name="mf_ad_above_branding_text" rows="10" cols="60"><?php echo stripslashes($mingleforum->ads_options['mf_ad_above_branding']) ?></textarea><br/>
                <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-above-branding</small>
              </td>
            </tr>
            <tr>
              <td>
                <input type="checkbox" name="mf_ad_above_info_center_on" value="true" <?php
                if ($mingleforum->ads_options['mf_ad_above_info_center_on'])
                {
                  echo 'checked="checked"';
                }
                ?> />
                <strong><?php echo __('Enable Area Above Info Center', 'mingleforum'); ?></strong><br/>
                <textarea name="mf_ad_above_info_center_text" rows="10" cols="60"><?php echo stripslashes($mingleforum->ads_options['mf_ad_above_info_center']) ?></textarea><br/>
                <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-above-info-center</small>
              </td>
            </tr>
            <tr>
              <td>
                <input type="checkbox" name="mf_ad_below_menu_on" value="true" <?php
                if ($mingleforum->ads_options['mf_ad_below_menu_on'])
                {
                  echo 'checked="checked"';
                }
                ?> />
                <strong><?php echo __('Enable Area Below Menu', 'mingleforum'); ?></strong><br/>
                <textarea name="mf_ad_below_menu_text" rows="10" cols="60"><?php echo stripslashes($mingleforum->ads_options['mf_ad_below_menu']) ?></textarea><br/>
                <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-below-menu</small>
              </td>
            </tr>
            <tr>
              <td>
                <input type="checkbox" name="mf_ad_above_quick_reply_on" value="true" <?php
                if ($mingleforum->ads_options['mf_ad_above_quick_reply_on'])
                {
                  echo 'checked="checked"';
                }
                ?> />
                <strong><?php echo __('Enable Area Above Quick Reply Form', 'mingleforum'); ?></strong><br/>
                <textarea name="mf_ad_above_quick_reply_text" rows="10" cols="60"><?php echo stripslashes($mingleforum->ads_options['mf_ad_above_quick_reply']) ?></textarea><br/>
                <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-above-quick-reply</small>
              </td>
            </tr>
            <tr>
              <td>
                <input type="checkbox" name="mf_ad_below_first_post_on" value="true" <?php
                if ($mingleforum->ads_options['mf_ad_below_first_post_on'])
                {
                  echo 'checked="checked"';
                }
                ?> />
                <strong><?php echo __('Enable Area Below First Post', 'mingleforum'); ?></strong><br/>
                <textarea name="mf_ad_below_first_post_text" rows="10" cols="60"><?php echo stripslashes($mingleforum->ads_options['mf_ad_below_first_post']) ?></textarea><br/>
                <small><strong><?php echo __('css-value:', 'mingleforum'); ?></strong> div.mf-ad-below-first-post</small>
              </td>
            </tr>
            <tr>
              <td>
                <strong><?php echo __('Below you can modify/add your own CSS', 'mingleforum'); ?></strong><br/>
                <small><?php echo __('NOTE: If you do not know what this is for, leave it blank', 'mingleforum'); ?></small><br/>
                <textarea name="mf_ad_custom_css" rows="10" cols="60"><?php echo stripslashes($mingleforum->ads_options['mf_ad_custom_css']) ?></textarea>
              </td>
            </tr>
            <tr>
              <td>
                <span>
                  <input class="button" type="submit" name="mf_ads_admin_save" value="<?php echo __('Save Options', 'mingleforum'); ?>" />
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </form>
    </div>
    <?php
  }

  function save_mf_ads()
  {
    global $mingleforum;

    if (isset($_POST['mf_ads_admin_save']))
    {
      $mingleforum->ads_options = array('mf_ad_above_forum_on' => isset($_POST['mf_ad_above_forum_on']),
          'mf_ad_above_forum' => $_POST['mf_ad_above_forum_text'],
          'mf_ad_below_forum_on' => isset($_POST['mf_ad_below_forum_on']),
          'mf_ad_below_forum' => $_POST['mf_ad_below_forum_text'],
          'mf_ad_above_branding_on' => isset($_POST['mf_ad_above_branding_on']),
          'mf_ad_above_branding' => $_POST['mf_ad_above_branding_text'],
          'mf_ad_above_info_center_on' => isset($_POST['mf_ad_above_info_center_on']),
          'mf_ad_above_info_center' => $_POST['mf_ad_above_info_center_text'],
          'mf_ad_above_quick_reply_on' => isset($_POST['mf_ad_above_quick_reply_on']),
          'mf_ad_above_quick_reply' => $_POST['mf_ad_above_quick_reply_text'],
          'mf_ad_below_menu_on' => isset($_POST['mf_ad_below_menu_on']),
          'mf_ad_below_menu' => $_POST['mf_ad_below_menu_text'],
          'mf_ad_below_first_post_on' => isset($_POST['mf_ad_below_first_post_on']),
          'mf_ad_below_first_post' => $_POST['mf_ad_below_first_post_text'],
          'mf_ad_custom_css' => strip_tags($_POST['mf_ad_custom_css'])
      );

      update_option('mingleforum_ads_options', $mingleforum->ads_options);

      return true;
    }

    return false;
  }

}

// End class
?>
