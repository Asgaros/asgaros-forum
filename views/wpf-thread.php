<?php

if ($this->forum_exists($_GET['forum'])) {
    if ($user_ID || $this->allow_unreg())
    {
      echo "<form action='' name='addform' method='post' enctype='multipart/form-data'>";
      echo "<table class='wpf-table' width='100%'>
    			<tr>
    				<th colspan='2'>" . __("Post new Topic", "asgarosforum") . "</th>
    			</tr>
    			<tr>
    				<td>" . __("Subject:", "asgarosforum") . "</td>
    				<td><input size='50%' type='text' name='add_topic_subject' class='wpf-input' /></td>
    			</tr>
    			<tr>
    				<td valign='top'>" . __("Message:", "asgarosforum") . "</td>
    				<td>
    					<textarea rows='20' cols='80' name='message'></textarea>
    				</td>
    			</tr>";
      echo $this->get_captcha();
      if ($this->options['forum_allow_image_uploads'])
      {
        echo "
    					<tr>
    						<td valign='top'>" . __("Images:", "asgarosforum") . "</td>
    						<td colspan='2'>
    							<input type='file' name='mfimage1' id='mfimage' /><br/>
    							<input type='file' name='mfimage2' id='mfimage' /><br/>
    							<input type='file' name='mfimage3' id='mfimage' /><br/>
    						</td>
    					</tr>";
      }
      echo "
    			<tr>
    				<td></td>
    				<td><input type='submit' id='wpf-post-submit' name='add_topic_submit' value='" . __("Submit", "asgarosforum") . "' /></td>
    				<input type='hidden' name='add_topic_forumid' value='" . $this->check_parms($_GET['forum']) . "'/>
    			</tr>
    			</table></form>";
    } else {
        wp_die(__("Sorry, you don't have permission to post.", "asgarosforum"));
    }
 } else {
    wp_die(__("Sorry, this forum does not exist.", "asgarosforum"));
    }


?>
