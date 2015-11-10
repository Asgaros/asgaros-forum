<?php

if (!defined('ABSPATH'))
{
  die('You are not allowed to call this page directly.');
}

if (isset($_POST['edit_usergroup_submit']))
{
  global $wpdb, $table_prefix;
  $edit_usergroup_name = $wpdb->escape($_POST['edit_usergroup_name']);
  $edit_usergroup_description = $wpdb->escape($_POST['edit_usergroup_description']);
  $edit_usergroup_id = $wpdb->escape($_POST['edit_usergroup_id']);

  if (!$edit_usergroup_name)
    echo "<div id='message' class='updated fade'><p>" . __("You must specify a name for the User Group", "mingleforum") . "</p></div>";

  else if ($wpdb->get_var("SELECT id FROM " . $table_prefix . "forum_usergroups WHERE name = '$edit_usergroup_name' AND id <> $edit_usergroup_id"))
    echo "<div id='message' class='updated fade'><p>" . __("You have choosen a name that already exists in the database, please specify another", "mingleforum") . "</p></div>";

  else
  {
    $wpdb->query("UPDATE " . $table_prefix . "forum_usergroups SET name = '$edit_usergroup_name', description = '$edit_usergroup_description' WHERE id = $edit_usergroup_id");
    echo "<div id='message' class='updated fade'><p>" . __("User Group updated successfully", "mingleforum") . "</p></div>";
  }
}
else
{
  $ug_id = (is_numeric($_GET['usergroup_id'])) ? $_GET['usergroup_id'] : 0;
  $name = $mingleforum->get_usergroup_name($ug_id);
  echo "<h2>" . __("Edit User Group", "mingleforum") . " \"$name\"</h2>";
  echo "<form name='edit_usergroup_form' action='' method='post'>";

  echo "<table class='form-table'>
    <tr>
      <th>" . __("Name:", "mingleforum") . "</th>
      <td><input type='' value='$name' name='edit_usergroup_name' /></td>
    </tr>
    <tr>
      <th>" . __("Description:", "mingleforum") . "</th>
      <td><textarea name='edit_usergroup_description' " . ADMIN_ROW_COL . ">" . $mingleforum->get_usergroup_description($ug_id) . "</textarea></td>
    </tr>
    <tr>
      <th></th>
      <td><input type='submit' name='edit_usergroup_submit' value='" . __("Save User Group", "mingleforum") . "'</td>
    </tr>

    <input type='hidden' value='{$ug_id}' name='edit_usergroup_id' />";
  echo "</table></form>";
}
?>
