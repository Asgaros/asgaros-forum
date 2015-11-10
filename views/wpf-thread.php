<?php

if ($user_ID || $this->allow_unreg())
{
  $this->current_view = NEWTOPIC;
  $out = $this->header();
  $out .= "<form action='' name='addform' method='post' enctype='multipart/form-data'>";
  $out .= "<table class='wpf-table' width='100%'>
			<tr>
				<th colspan='2'>" . __("Post new Topic", "mingleforum") . "</th>
			</tr>
			<tr>
				<td>" . __("Subject:", "mingleforum") . "</td>
				<td><input size='50%' type='text' name='add_topic_subject' class='wpf-input' /></td>
			</tr>
			<tr>
				<td valign='top'>" . __("Message:", "mingleforum") . "</td>
				<td>
					" . $this->form_buttons() . $this->form_smilies() . "

					<br/><textarea " . ROW_COL . " name='message' class='wpf-textarea'></textarea>
				</td>
			</tr>";
  $out .= apply_filters('wpwf_form_guestinfo', ''); //--weaver--
  $out .= $this->get_captcha();
  if ($this->options['forum_allow_image_uploads'])
  {
    $out .= "
					<tr>
						<td valign='top'>" . __("Images:", "mingleforum") . "</td>
						<td colspan='2'>
							<input type='file' name='mfimage1' id='mfimage' /><br/>
							<input type='file' name='mfimage2' id='mfimage' /><br/>
							<input type='file' name='mfimage3' id='mfimage' /><br/>
						</td>
					</tr>";
  }
  $out .= "
			<tr>
				<td></td>
				<td><input type='submit' id='wpf-post-submit' name='add_topic_submit' value='" . __("Submit", "mingleforum") . "' /></td>
				<input type='hidden' name='add_topic_forumid' value='" . $this->check_parms($_GET['forum']) . "'/>
			</tr>
			</table></form>";
  $this->o .= $out;
}
else
  wp_die(__("Sorry. you don't have permission to post.", "mingleforum"))

?>