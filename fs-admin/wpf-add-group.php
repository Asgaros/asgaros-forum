<?php

if (!defined('ABSPATH'))
{
  die('You are not allowed to call this page directly.');
}

$image = WPFURL . "images/table.png";
echo "<div class='wrap'>
<h2><img src='{$image}'> " . __('Add category', 'mingleforum') . "</h2>
<form name='add_group_form' method='post' id='add_group_form' action='admin.php?page=mfstructure&mingleforum_action=structure'>
<table class='widefat'>
  <thead>
    <tr>
      <th>" . __('Name', 'mingleforum') . "</th>
      <th>" . __('Description', 'mingleforum') . "</th>
    </tr>
  </thead>
  <tr class='alternate'>
    <td> <input type='text' value='' name='add_group_name' /> </td>
    <td><textarea name='add_group_description' " . ADMIN_ROW_COL . "></textarea> </td>
  </tr>
  <tr class='alternate'>
    <td colspan='2'><input class='button' type='submit' value='" . __('Save category', 'mingleforum') . "' name='add_group_submit' /></td>
  </tr>
</table></form>
</div>";
?>
