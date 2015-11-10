<?php

if (!defined('ABSPATH'))
{
  die('You are not allowed to call this page directly.');
}

global $wpdb, $table_prefix, $mingleforum;
$users = $mingleforum->get_users();
$groups = $mingleforum->get_groups();
$image = WPFURL . "images/user.png";
echo "<h2><img src='$image' alt=''>" . __("Add moderator", "mingleforum") . "</h2>
<form name='add_mod_form' method='post' action='admin.php?page=mfmods&mingleforum_action=moderators'>
<table class='widefat'>
<thead>
<tr>
  <th>User</th>
  <th>Moderate</th>
</tr>
</thead>
  <tr>
  <td>
    <select name='addmod_user_id'><option selected='selected' value='add_mod_null'>" . __("Select user", "mingleforum") . "</option>";

foreach ($users as $user)
  echo "<option value='$user->ID'>$user->user_login ($user->ID)</option>";

echo "</select>";
echo "</td>
  <td>";
echo "<p class='wpf-alignright'><input type='checkbox'  id='mod_global' name='mod_global' onclick='invertAll(this, this.form, \"mod_forum_id\");' value='true' /> <strong>" . __("Global moderator: (User can moderate all forums)", "mingleforum") . "</strong></p>";

foreach ($groups as $group)
{
  $forums = $mingleforum->get_forums($group->id);
  echo "<p class='wpf-bordertop'><strong>" . stripslashes($group->name) . "</strong></p>";
  foreach ($forums as $forum)
  {
    echo "<p class='wpf-indent'><input type='checkbox' name='mod_forum_id[]' onclick='uncheckglobal(this, this.form);' id='mod_forum_id' value='$forum->id' /> $forum->name</p>";
  }
}

echo "</td></tr>
            <tr>
              <td colspan='2'>
              <span style='float:left'><input class='button' type='submit' name='add_mod_submit' value='" . __("Add moderator", "mingleforum") . "' /></span><span class='button' style='float:right'><a href='http://cartpauj.com' target='_blank'>cartpauj.com</a></span></td>
            </tr>
    </table>";
?>
