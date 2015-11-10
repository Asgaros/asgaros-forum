<?php

if ($user_ID || $this->allow_unreg())
{
  if (isset($_GET['quote']))
  {
    $quote_id = $this->check_parms($_GET['quote']);
    $text = $wpdb->get_row($wpdb->prepare("SELECT text, author_id, `date` FROM {$this->t_posts} WHERE id = %d", $quote_id));
    $user = get_userdata($text->author_id);
    $display_type = $this->options['forum_display_name'];
    $display_name = (!empty($user)) ? $user->$display_type : __('Guest', 'mingleforum');
    $q = "[quote][quotetitle]" . __("Quote from", "mingleforum") . " " . $display_name . " " . __("on", "mingleforum") . " " . $this->format_date($text->date) . "[/quotetitle]\n" . $text->text . "[/quote]";
  }

  if (($_GET['mingleforumaction'] == "postreply"))
  {
    $this->current_view = POSTREPLY;
    $thread = $this->check_parms($_GET['thread']);
    $out = $this->header();
    $out .= "<form action='' name='addform' method='post' enctype='multipart/form-data'>";
    $out .= "<table class='wpf-table' width='100%'>
      <tr>
        <th colspan='2'>" . __("Post Reply:", "mingleforum") . ' ' . $this->get_subject($thread) . "</th>
      </tr>";

    $out .= "<tr>
            <td valign='top'>" . __("Message:", "mingleforum") . "</td>
            <td>";
              $out .= $this->form_buttons() . $this->form_smilies();
              $out .= "<br /><textarea " . ROW_COL . " name='message' class='wpf-textarea' >" . stripslashes($q) . "</textarea>";
              $out .= '<input type="hidden" name="add_post_subject" value="'.$this->get_subject($thread).'" />';
              $out .= "
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
        <td colspan='2'><input type='submit' id='wpf-post-submit' name='add_post_submit' value='" . __("Submit", "mingleforum") . "' /></td>
        <input type='hidden' name='add_post_forumid' value='" . $this->check_parms($thread) . "'/>
      </tr>
      </table></form>";
    $this->o .= $out;
  }

  if (($_GET['mingleforumaction'] == "editpost"))
  {
    $this->current_view = EDITPOST;
    $id = (isset($_GET['id']) && !empty($_GET['id'])) ? (int)$_GET['id'] : 0;
    $thread = $this->check_parms($_GET['t']);
    $out = $this->header();
    $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->t_posts} WHERE id = %d", $id));

    if (($user_ID == $post->author_id && $user_ID) || $this->is_moderator($user_ID, $this->forum_get_forum_from_post($thread))) //Make sure only admins/mods/post authors can edit posts
    {
      $out .= "<form action='' name='addform' method='post'>";
      $out .= "<table class='wpf-table' width='100%'>
        <tr>
          <th colspan='2'>" . __("Edit Post:", "mingleforum") . " " . stripslashes($post->subject) . "
            <input type='hidden' name='edit_post_subject' value='" . stripslashes($post->subject) . "' />
          </th>
        </tr>";

      if(false) //Need to enable this eventually if we're editing the first post in the thread
        $out .= "<tr>
              <td>" . __("Subject:", "mingleforum") . "</td>
              <td><input size='50%' type='text' name='edit_post_subject' class='wpf-input' value='" . stripslashes($post->subject) . "'/></td>
            </tr>";

      $out .= "<tr>
              <td valign='top'>" . __("Message:", "mingleforum") . "</td>
              <td>";
                $out .= $this->form_buttons() . "<br/>" . $this->form_smilies();
                $out .= "<br /><textarea " . ROW_COL . " name='message' class='wpf-textarea' >" . stripslashes($post->text) . "</textarea>";
                $out .= "</td>
            </tr>
            <tr>
              <td colspan='2'><input type='submit' id='wpf-post-submit' name='edit_post_submit' value='" . __("Save Post", "mingleforum") . "' /></td>
              <input type='hidden' name='edit_post_id' value='" . $post->id . "'/>
              <input type='hidden' name='thread_id' value='" . $thread . "'/>
            </tr>
          </table></form>";
      $this->o .= $out;
    }
    else
      wp_die("Hey, that's not vary nice ... didn't your mother raise you better?");
  }
}
else
  wp_die("You do not have permission.");
?>
