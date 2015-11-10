<?php

if (!defined('ABSPATH'))
{
  die('You are not allowed to call this page directly.');
}

$usergroups = $mingleforum->get_usergroups();
$image = WPFURL . "images/user.png";

echo "<div class='wrap'>
<h2><img src='$image'> Add users</h2>";
echo "<form name='add_usertogroup_form' action='admin.php?page=mfgroups&mingleforum_action=usergroups' method='post'>
<table class='widefat'>
    <thead>
      <tr>
        <th>User names </th>
        <th>User group</th>
      </tr>
    </thead>
    <tr class='alternate'>
      <td><textarea name='togroupusers' " . ADMIN_ROW_COL . "></textarea><br/>
  <i>separate user names by comma sign</i></td>
      <td>";
echo "<select name='usergroup'>
    <option selected='selected' value='add_user_null'>" . __("Select User group", "mingleforum") . "
          </option>";

foreach ($usergroups as $usergroup)
  echo "<option value='{$usergroup->id}'>
      $usergroup->name</option>";

echo "</select></td>
    </tr>
    <tr class='alternate'>
      <td colspan='2'><input class='button' name='add_user_togroup' type='submit' value='" . __("Add users", "mingleforum") . "' /></td>
    </tr>
  </table>
</form>
</div>";
?>
