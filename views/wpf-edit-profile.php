<?php

global $user_ID, $user_level;
if (isset($_POST['edit_user_submit']))
{
  $ops = array("allow_profile" => $_POST['allow_profile'],
      "signature" => $this->sig_input_filter(stripslashes($_POST['message'])));
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
  $topics = $this->get_subscribed_threads();
  $forums = $this->get_subscribed_forums();
  if (is_array($topics) && !empty($topics))
  {
    $tops = "<ul>";
    foreach ($topics as $t)
    {
      $tops .= "<li><a href='" . $this->get_threadlink($t) . "'>" . $this->get_subject($t) . "</a></li>";
    }
    $tops .= "</ul>";
  }
  else
    $tops = "<ul><li>" . __("You have no Topic subscriptions at this time", "mingleforum") . "</li></ul>";
  if (is_array($forums) && !empty($forums))
  {
    $fors = "<ul>";
    foreach ($forums as $f)
    {
      $fors .= "<li><a href='" . $this->get_forumlink($f) . "'>" . $this->get_forumname($f) . "</a></li>";
    }
    $fors .= "</ul>";
  }
  else
    $fors = "<ul><li>" . __("You have no Forum subscriptions at this time", "mingleforum") . "</li></ul>";
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
  if ($this->options['forum_show_bio'])
    $out .= __('Edit Signature:', 'mingleforum') . "<br/>" . $this->form_buttons() . $this->form_smilies() . "<br/>
						<textarea rows='4' id='sig-box' name='message'>" . $options['signature'] . "</textarea>";
  $out .= "</td>
					 </tr>
					 <tr>
					 	<td><strong>" . __("You have email notifications for these Forums:", "mingleforum") . "</strong><br /><p>$fors</p></td>
					 </tr>
					 <tr>
					 	<td><strong>" . __("You have email notifications for these Topics:", "mingleforum") . "</strong><br /><p>$tops</p></td>
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