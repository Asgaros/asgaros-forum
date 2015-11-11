<?php

global $user_ID, $user_level;
if (isset($_POST['edit_user_submit']))
{
  $ops = array("allow_profile" => $_POST['allow_profile']);
  update_user_meta($user_ID, "wpf_useroptions", $ops);
}
$user_id = $_GET['user_id'];
if (!is_numeric($user_id))
  wp_die(__("No such user", "mingleforum"));
if ($user_ID == $user_id or $user_level > 8)
{
  $this->header();
  $options = get_user_meta($user_ID, "wpf_useroptions", true);
  $allow_profile_v = ($options['allow_profile'] == true) ? "checked" : "";
  $out = "<form name='addform' method='post' action=''>
			<table class='wpf-table' cellpadding='0' cellspacing='0' width='100%'>
				<tr>
					<th>" . __("Edit forum options", "mingleforum") . "</th>
				</tr>
				<tr>
					 <td  valign='top'>
					 	<p>
					 		<input type='checkbox' name='allow_profile' value='true' $allow_profile_v /> " . __("Allow others to view my profile?", "mingleforum") . "<br />
					 	</p>";
  $out .= "</td>
					 </tr>
					 <tr>
					 	<td><input type='submit' name='edit_user_submit' class='wpf-edit-button' value='" . __("Save options", "mingleforum") . "'</td>
					 </tr>
				</table></form>";
  $this->o .= $out;
}
else
  wp_die(__("An unknown error has occured. Please try again.", "mingleforum"));
?>
