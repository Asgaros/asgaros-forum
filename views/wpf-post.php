<?php

if ($user_ID || $this->allow_unreg())
{
  if (isset($_GET['quote']))
  {
    $quote_id = $this->check_parms($_GET['quote']);
    $text = $wpdb->get_row($wpdb->prepare("SELECT text, author_id, `date` FROM {$this->t_posts} WHERE id = %d", $quote_id));
    $user = get_userdata($text->author_id);
    $display_type = $this->options['forum_display_name'];
    $display_name = (!empty($user)) ? $user->$display_type : __('Guest', 'asgarosforum');
    $q = "[quote][quotetitle]" . __("Quote from", "asgarosforum") . " " . $display_name . " " . __("on", "asgarosforum") . " " . $this->format_date($text->date) . "[/quotetitle]\n" . $text->text . "[/quote]";
  }

  if (($_GET['forumaction'] == "postreply"))
  {
    $thread = $this->check_parms($_GET['thread']);
    echo "<form action='' name='addform' method='post' enctype='multipart/form-data'>";
    echo "<table class='wpf-table' width='100%'>
      <tr>
        <th colspan='2'>" . __("Post Reply:", "asgarosforum") . ' ' . $this->get_subject($thread) . "</th>
      </tr>";

    echo "<tr>
            <td valign='top'>" . __("Message:", "asgarosforum") . "</td>
            <td>";
              echo "<textarea rows='20' cols='80' name='message'></textarea>";
              echo '<input type="hidden" name="add_post_subject" value="'.$this->get_subject($thread).'" />';
              echo "
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
        <td colspan='2'><input type='submit' id='wpf-post-submit' name='add_post_submit' value='" . __("Submit", "asgarosforum") . "' /></td>
        <input type='hidden' name='add_post_forumid' value='" . $this->check_parms($thread) . "'/>
      </tr>
      </table></form>";
  }

  if (($_GET['forumaction'] == "editpost"))
  {
    $id = (isset($_GET['id']) && !empty($_GET['id'])) ? (int)$_GET['id'] : 0;
    $thread = $this->check_parms($_GET['t']);
    $t = $wpdb->get_row($wpdb->prepare("SELECT subject FROM {$this->t_threads} WHERE id = %d", $thread));
    $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->t_posts} WHERE id = %d", $id));

    if (($user_ID == $post->author_id && $user_ID) || $this->is_moderator($user_ID)) //Make sure only admins/mods/post authors can edit posts
    {
      echo "<form action='' name='addform' method='post'>";
      echo "<table class='wpf-table' width='100%'>
        <tr>
          <th colspan='2'>" . __("Edit Post:", "asgarosforum") . " " . stripslashes($t->subject) . "</th>
        </tr>";

      if(false) //Need to enable this eventually if we're editing the first post in the thread
        echo "<tr>
              <td>" . __("Subject:", "asgarosforum") . "</td>
              <td><input size='50%' type='text' name='edit_post_subject' class='wpf-input' value='" . stripslashes($t->subject) . "'/></td>
            </tr>";

      echo "<tr>
              <td valign='top'>" . __("Message:", "asgarosforum") . "</td>
              <td>";
                echo "<textarea rows='20' cols='80' name='message'>" . stripslashes($post->text) . "</textarea>";
                echo "</td>
            </tr>
            <tr>
              <td colspan='2'><input type='submit' id='wpf-post-submit' name='edit_post_submit' value='" . __("Save Post", "asgarosforum") . "' /></td>
              <input type='hidden' name='edit_post_id' value='" . $post->id . "'/>
              <input type='hidden' name='thread_id' value='" . $thread . "'/>
              <input type='hidden' name='page_id' value='" . $this->curr_page . "'/>
            </tr>
          </table></form>";
    }
    else
      wp_die("Hey, that's not vary nice ... didn't your mother raise you better?");
  }
}
else
  wp_die("You do not have permission.");
?>
