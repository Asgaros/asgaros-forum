<?php

if (!defined('ABSPATH'))
{
  die('You are not allowed to call this page directly.');
}

echo "<h2>" . __("Add forum to", "mingleforum") . " \"" . stripslashes($mingleforum->get_groupname($_GET['groupid'])) . "\"</h2>";
echo "<form name='add_forum_form' id='add_forum_form' method='post' action='admin.php?page=mfstructure&mingleforum_action=structure'>";
echo "<table class='form-table'>
    <tr>
      <th>" . __("Name:", "mingleforum") . "</th>
      <td><input type='text' value='' name='add_forum_name' /></td>
    </tr>
    <tr>
      <th>" . __("Description:", "mingleforum") . "</th>
      <td><textarea name='add_forum_description' " . ADMIN_ROW_COL . "></textarea></td>
    </tr>
    <tr>";

$gr_id = (is_numeric($_GET['groupid'])) ? $_GET['groupid'] : 0;

echo "<tr>
      <th></th>
      <td><input type='submit' value='" . __("Save forum", "mingleforum") . "' name='add_forum_submit' /></td>
    </tr>
    <input type='hidden' name='add_forum_group_id' value='{$gr_id}' />";
echo "</form></table>";
?>
