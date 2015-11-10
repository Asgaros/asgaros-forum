<?php

if (!defined('ABSPATH'))
{
  die('You are not allowed to call this page directly.');
}

if (isset($_POST['edit_save_group']))
{
  global $wpdb, $table_prefix;
  $usergroups = isset($_POST['usergroups']) ? $_POST['usergroups'] : "";
  $edit_group_name = isset($_POST['edit_group_name']) ? $wpdb->escape($_POST['edit_group_name']) : "";
  $edit_group_description = isset($_POST['edit_group_description']) ? $wpdb->escape($_POST['edit_group_description']) : "";
  $edit_group_id = isset($_POST['edit_group_id']) ? $_POST['edit_group_id'] : "";

  if ($_POST['edit_group_name'] == "")
    echo "<div id='message' class='updated fade'><p>" . __("You must specify a group name", "mingleforum") . "</p></div>";

  $sql = "SELECT id FROM " . $table_prefix . "forum_groups WHERE name = '$edit_group_name' AND id <> $edit_group_id";
  $name = $wpdb->get_var($sql);
  if ($name)
    echo "<div id='message' class='updated fade'><p>" . __("You have choosen a name that already exists in the database, please specify another", "mingleforum") . "</p></div>";

  else
  {
    global $wpdb, $table_prefix;
    $wpdb->query("UPDATE " . $table_prefix . "forum_groups SET name = '$edit_group_name', description = '$edit_group_description' WHERE id = $edit_group_id");

    $this->update_usergroups($usergroups, $edit_group_id);
    echo "<div id='message' class='updated fade'><p>" . __("Group updated successfully", "mingleforum") . "</p></div>";
  }
}
if (isset($_POST['edit_save_forum']))
{

  global $wpdb, $table_prefix;
  $edit_forum_name = $wpdb->escape(strip_tags($_POST['edit_forum_name']));
  $edit_forum_description = $wpdb->escape(strip_tags($_POST['edit_forum_description']));
  $edit_forum_id = $wpdb->escape($_POST['edit_forum_id']);
  if ($edit_forum_name == "")
    echo "<div id='message' class='updated fade'><p>" . __("You must specify a forum name", "mingleforum") . "</p></div>";

  $wpdb->query("UPDATE " . $table_prefix . "forum_forums SET name = '$edit_forum_name', description = '$edit_forum_description' WHERE id = $edit_forum_id");
  echo "<div id='message' class='updated fade'><p>" . __("Forum updated successfully", "mingleforum") . "</p></div>";
}

if (($_GET['do'] == "editgroup") && (!isset($_POST['edit_save_group'])))
{
  $gr_id = (is_numeric($_GET['groupid'])) ? $_GET['groupid'] : 0;
  $usergroups = $mingleforum->get_usergroups();
  $usergroups_with_access = $this->get_usersgroups_with_access_to_group($gr_id);
  $group_name = stripslashes($mingleforum->get_groupname($gr_id));
  global $wpdb, $table_prefix;
  $table = $table_prefix . "forum_groups";

  echo "<h2>" . __("Edit category", "mingleforum") . " \"$group_name\"</h2>";

  echo "<form name='edit_group_form' method='post' action=''>";

  echo "<table class='form-table'>
    <tr>
      <th>" . __("Name:", "mingleforum") . "</th>
      <td><input type='text' value='$group_name' name='edit_group_name' /></td>
    </tr>
    <tr>
      <th>" . __("Description", "mingleforum") . "</th>
      <td><textarea name='edit_group_description' " . ADMIN_ROW_COL . ">" . stripslashes($mingleforum->get_group_description($gr_id)) . "</textarea></td>
    </tr>
    <tr>
      <th>" . __("User Groups:", "mingleforum") . "</th>
      <td>";

  echo "<strong>" . __("Members of the checked User Groups have access to the forums in this category:", "mingleforum") . "</strong>";
  if ($usergroups)
  {
    $i = 0;
    echo "<table class='wpf-wide'>";
    echo "<tr>";

    foreach ($usergroups as $usergroup)
    {
      $col = 4;
      if ($mingleforum->array_search($usergroup->id, $usergroups_with_access))
        $checked = "checked='checked'";
      else
        $checked = "";
      $e = "<p><input type='checkbox' $checked name='usergroups[]' value='$usergroup->id'/> " . stripslashes($usergroup->name) . "</p>\n\r";

      if ($i == 0)
      {
        echo "<td>$e";
        ++$i;
      }
      elseif ($i < $col)
      {
        echo "$e";
        ++$i;
      }
      else
      {
        echo "$e</td>";
        $i = 0;
      }
    }
    echo "</tr></table>";
  }
  else
    echo __("There are no User Groups", "mingleforum");


  echo "</td>
    </tr>
    <tr>
      <th></th>
      <td><input type='submit' name='edit_save_group' value='" . __("Save group", "mingleforum") . "' /></td>
    </tr>

    <input type='hidden' name='edit_group_id' value='" . $gr_id . "' />";

  echo "</table>";

  echo "</form>";
}


if (($_GET['do'] == "editforum") && (!isset($_POST['edit_save_forum'])))
{

  $fo_id = (is_numeric($_GET['forumid'])) ? $_GET['forumid'] : 0;

  echo "<h2>" . __("Edit forum", "mingleforum") . " \"" . stripslashes($mingleforum->get_forumname($fo_id)) . "\"</h2>";
  echo "<form id='edit_forum_form' name='edit_forum_form' action='' method='post'>";
  echo "<table class='form-table'>";
  echo "<tr>
    <th>" . __("Name:", "mingleforum") . "</th>
    <td><input type='text' name='edit_forum_name' value='" . stripslashes($mingleforum->get_forumname($fo_id)) . "' /></td>
  </tr>
  <tr>
    <th>" . __("Description:", "mingleforum") . "</th>
    <td><textarea name='edit_forum_description' " . ADMIN_ROW_COL . ">" . stripslashes($mingleforum->get_forum_description($fo_id)) . "</textarea></td>
  </tr>
  <tr>
    <th></th>
    <td><input type='submit' name='edit_save_forum' value='" . __("Save forum", "mingleforum") . "' /></td>
  </tr>
  <input type='hidden' name='edit_forum_id' value='" . $fo_id . "' />";

  echo "</table></form>";
}
?>