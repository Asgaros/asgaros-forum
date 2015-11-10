<?php
//THIS STILL NEEDS LOTS OF CLEANUP
//BUT AT LEAST IT NO LONGER LOADS ON A SEPARATE INSTANCE

//Checking if current categories have been disabled to admin posting only
$the_forum_id = false;
if (isset($_POST['add_topic_forumid']) && !empty($_POST['add_topic_forumid']))
  $the_forum_id = $this->check_parms($_POST['add_topic_forumid']);
if (isset($_POST['add_post_forumid']) && !empty($_POST['add_post_forumid']))
{
  $the_thread_id = $this->check_parms($_POST['add_post_forumid']);
  $the_forum_id = $wpdb->get_var($wpdb->prepare("SELECT `parent_id` FROM {$this->t_threads} WHERE `id` = %d", $the_thread_id));
}
if (isset($_POST['thread_id']) && !empty($_POST['thread_id']) && isset($_POST['edit_post_submit']))
{
  $the_thread_id = $this->check_parms($_POST['thread_id']);
  $the_forum_id = $wpdb->get_var($wpdb->prepare("SELECT `parent_id` FROM {$this->t_threads} WHERE `id` = %d", $the_thread_id));
}
if (is_numeric($the_forum_id))
{
  $the_cat_id = $wpdb->get_var("SELECT `parent_id` FROM {$this->t_forums} WHERE `id` = {$the_forum_id}");

  if (isset($this->options['forum_disabled_cats']) && in_array($the_cat_id, $this->options['forum_disabled_cats']) && !is_super_admin($user_ID) && !$this->is_moderator($user_ID, $the_forum_id) && !$this->options['allow_user_replies_locked_cats'])
    wp_die(__("Oops only Administrators can post in this Forum!", "mingleforum"));
}
//End Check
//Spam time interval check
if (!is_super_admin() && !$this->is_moderator($user_ID, $the_forum_id))
{
  //We're going to not set a user ID here, I know unconventional, but it's an easy way to account for guests.
  $spam_meta_key = "mingle_forum_last_post_time_" . ip_to_string();
  $last_post_time = $wpdb->get_var($wpdb->prepare("SELECT `meta_value` FROM {$wpdb->usermeta} WHERE `meta_key` = %s", $spam_meta_key));
  if ((time() - (int) $last_post_time) < stripslashes($this->options['forum_posting_time_limit']))
    wp_die(__('To help prevent spam, we require that you wait', 'mingleforum') . ' ' . ceil(((int) (stripslashes($this->options['forum_posting_time_limit'])) / 60)) . ' ' . __('minutes before posting again. Please use your browsers back button to return.', 'mingleforum'));
  else
  if ($last_post_time !== null)
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->usermeta} SET `meta_value` = %d WHERE `meta_key` = %s", time(), $spam_meta_key));
  else
    $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->usermeta} (`meta_key`, `meta_value`) VALUES (%s, %d)", $spam_meta_key, time()));
}

function ip_to_string()
{
  return preg_replace("/[^0-9]/", "_", $_SERVER["REMOTE_ADDR"]);
}

//End Spam time interval check

function mf_u_key()
{
  $pref = "";
  for ($i = 0; $i < 5; $i++)
  {
    $d = rand(0, 1);
    $pref .= $d ? chr(rand(97, 122)) : chr(rand(48, 57));
  }
  return $pref . "-";
}

function MFAttachImage($temp, $name)
{
  //GET USERS UPLOAD PATH
  $upload_dir = wp_upload_dir();
  $path = $upload_dir['path'] . "/";
  $url = $upload_dir['url'] . "/";
  $u = mf_u_key();
  $name = sanitize_file_name($name);
  if (!empty($name))
    move_uploaded_file($temp, $path . $u . $name);
  return "\n[img]" . $url . $u . $name . "[/img]";
}

function MFGetExt($str)
{
  //GETS THE FILE EXTENSION BELONGING TO THE UPLOADED FILE
  $i = strrpos($str, ".");
  if (!$i)
  {
    return "";
  }
  $l = strlen($str) - $i;
  $ext = substr($str, $i + 1, $l);
  return $ext;
}

function mf_check_uploaded_images()
{
  $valid = array('im1' => true, 'im2' => true, 'im3' => true);
  if (!empty($_FILES))
  {
    if ($_FILES["mfimage1"]["error"] > 0 && !empty($_FILES["mfimage1"]["name"]))
      $valid['im1'] = false;
    if ($_FILES["mfimage2"]["error"] > 0 && !empty($_FILES["mfimage2"]["name"]))
      $valid['im2'] = false;
    if ($_FILES["mfimage3"]["error"] > 0 && !empty($_FILES["mfimage3"]["name"]))
      $valid['im3'] = false;
  }
  if (!empty($_FILES["mfimage1"]["name"]))
  {
    $ext = strtolower(MFGetExt(stripslashes($_FILES["mfimage1"]["name"])));
    if ($ext != "jpg" && $ext != "jpeg" && $ext != "bmp" && $ext != "png" && $ext != "gif")
      $valid['im1'] = false;
  }
  else
    $valid['im1'] = false;
  if (!empty($_FILES["mfimage2"]["name"]))
  {
    $ext = strtolower(MFGetExt(stripslashes($_FILES["mfimage2"]["name"])));
    if ($ext != "jpg" && $ext != "jpeg" && $ext != "bmp" && $ext != "png" && $ext != "gif")
      $valid['im2'] = false;
  }
  else
    $valid['im2'] = false;
  if (!empty($_FILES["mfimage3"]["name"]))
  {
    $ext = strtolower(MFGetExt(stripslashes($_FILES["mfimage3"]["name"])));
    if ($ext != "jpg" && $ext != "jpeg" && $ext != "bmp" && $ext != "png" && $ext != "gif")
      $valid['im2'] = false;
  }
  else
    $valid['im3'] = false;
  return $valid;
}

//--weaver-- check if guest filled in form
if (!isset($_POST['edit_post_submit']))
{
  $errormsg = apply_filters('wpwf_check_guestinfo', "");
  if ($errormsg != "")
  {
    $error = true;
    wp_die($errormsg); //plugin failed
  }
}
//--weaver-- end guest form check

if (isset($this->options['forum_captcha']) && $this->options['forum_captcha'] == true && !$user_ID)
{
  include_once("captcha/shared.php");
  $wpf_code = wpf_str_decrypt($_POST['wpf_security_check']);
  if (($wpf_code == $_POST['wpf_security_code']) && (!empty($wpf_code)))
  {
    //It passed
  }
  else
  {
    $error = true;
    $msg = __("Security code does not match", "mingleforum");
    wp_die($msg);
  }
}

$cur_user_ID = apply_filters('wpwf_change_userid', $user_ID); // --weaver-- use real id or generated guest ID
//ADDING A NEW TOPIC?
if (isset($_POST['add_topic_submit']))
{
  $myReplaceSub = array("\\");
  $subject = str_replace($myReplaceSub, "", $this->input_filter($_POST['add_topic_subject']));
  $content = $this->input_filter($_POST['message']);
  $forum_id = $this->check_parms($_POST['add_topic_forumid']);
  $msg = '';

  if ($subject == "")
  {
    $msg .= "<h2>" . __("An error occured", "mingleforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a subject", "mingleforum") . "</p></div>");
    $error = true;
  }
  elseif ($content == "")
  {
    $msg .= "<h2>" . __("An error occured", "mingleforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a message", "mingleforum") . "</p></div>");
    $error = true;
  }
  else
  {
    $date = $this->wpf_current_time_fixed('mysql', 0);

    $sql_thread = "INSERT INTO {$this->t_threads}
                      (last_post, subject, parent_id, `date`, status, starter)
                    VALUES
                      (%s, %s, %d, %s, 'open', %d)";
    $wpdb->query($wpdb->prepare($sql_thread, $date, $subject, $forum_id, $date, $cur_user_ID));

    $id = $wpdb->insert_id;
    //Add to mingle board
    $myMingID = -1;
    if (!function_exists('is_plugin_active'))
      require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    if (is_plugin_active('mingle/mingle.php') and is_user_logged_in())
    {
      $board_post = & MnglBoardPost::get_stored_object();
      $myMingID = $board_post->create($cur_user_ID, $cur_user_ID, "[b]" . __("created the forum topic:", "mingleforum") . "[/b] <a href='" . $this->get_threadlink($id) . "'>" . $this->output_filter($subject) . "</a>");
    }
    //End add to mingle board
    //MAYBE ATTACH IMAGES
    $images = mf_check_uploaded_images();
    if ($images['im1'] || $images['im2'] || $images['im3'])
    {
      if ($images['im1'])
        $content .= MFAttachImage($_FILES["mfimage1"]["tmp_name"], stripslashes($_FILES["mfimage1"]["name"]));
      if ($images['im2'])
        $content .= MFAttachImage($_FILES["mfimage2"]["tmp_name"], stripslashes($_FILES["mfimage2"]["name"]));
      if ($images['im3'])
        $content .= MFAttachImage($_FILES["mfimage3"]["tmp_name"], stripslashes($_FILES["mfimage3"]["name"]));
    }

    $sql_post = "INSERT INTO {$this->t_posts}
                    (text, parent_id, `date`, author_id, subject)
                  VALUES
                    (%s, %d, %s, %d, %s)";
    $wpdb->query($wpdb->prepare($sql_post, $content, $id, $date, $cur_user_ID, $subject));
    $new_post_id = $wpdb->insert_id;

    //UPDATE PROPER Mngl ID
    $sql_thread = "UPDATE {$this->t_threads}
                      SET mngl_id = %d
                      WHERE id = %d";
    $wpdb->query($wpdb->prepare($sql_thread, $myMingID, $id));
    //END UPDATE PROPER Mngl ID
  }
  if (!$error)
  {
    $this->notify_forum_subscribers($id, $subject, $content, $date, $forum_id);
    $this->notify_admins($id, $subject, $content, $date);
    $unused = apply_filters('wpwf_add_guest_sub', $id); //--weaver-- Maybe add a subscription
    wp_redirect(html_entity_decode($this->get_threadlink($id) . "#postid-" . $new_post_id));
    exit;
  }
  else
    wp_die($msg);
}

//ADDING A POST REPLY?
if (isset($_POST['add_post_submit']))
{
  $myReplaceSub = array("\\");
  $subject = str_replace($myReplaceSub, "", $this->input_filter($_POST['add_post_subject']));
  $content = $this->input_filter($_POST['message']);
  $thread = $this->check_parms($_POST['add_post_forumid']);
  $msg = '';

  //GET PROPER Mngl ID
  $MngBID = $wpdb->get_var($wpdb->prepare("SELECT mngl_id FROM {$this->t_threads} WHERE id = %d", $thread));
  //END GET PROPER Mngl ID

  if ($subject == "")
  {
    $msg .= "<h2>" . __("An error occured", "mingleforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a subject", "mingleforum") . "</p></div>");
    $error = true;
  }
  elseif ($content == "")
  {
    $msg .= "<h2>" . __("An error occured", "mingleforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a message", "mingleforum") . "</p></div>");
    $error = true;
  }
  else
  {
    $date = $this->wpf_current_time_fixed('mysql', 0);
    //Add to mingle board
    if (!function_exists('is_plugin_active'))
      require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    if (is_plugin_active('mingle/mingle.php') and is_user_logged_in() and $MngBID > 0)
    {
      $board_post = & MnglBoardPost::get_stored_object();
      global $mngl_board_comment;
      $mngl_board_comment->create($MngBID, $cur_user_ID, "[b]" . __("replied to the forum topic:", "mingleforum") . "[/b] <a href='" . $this->get_threadlink($thread) . "'>" . $this->output_filter($subject) . "</a>");
    }
    //End add to mingle board
    //MAYBE ATTACH IMAGES
    $images = mf_check_uploaded_images();
    if ($images['im1'] || $images['im2'] || $images['im3'])
    {
      if ($images['im1'])
        $content .= MFAttachImage($_FILES["mfimage1"]["tmp_name"], stripslashes($_FILES["mfimage1"]["name"]));
      if ($images['im2'])
        $content .= MFAttachImage($_FILES["mfimage2"]["tmp_name"], stripslashes($_FILES["mfimage2"]["name"]));
      if ($images['im3'])
        $content .= MFAttachImage($_FILES["mfimage3"]["tmp_name"], stripslashes($_FILES["mfimage3"]["name"]));
    }

    $sql_post = "INSERT INTO {$this->t_posts}
            (text, parent_id, `date`, author_id, subject)
         VALUES(%s, %d, %s, %d, %s)";
    $wpdb->query($wpdb->prepare($sql_post, $content, $thread, $date, $cur_user_ID, $subject));
    $new_id = $wpdb->insert_id;
    $wpdb->query($wpdb->prepare("UPDATE {$this->t_threads} SET last_post = %s WHERE id = %d", $date, $thread));
  }

  if (!$error)
  {
    $this->notify_thread_subscribers($thread, $subject, $content, $date);
    $this->notify_admins($thread, $subject, $content, $date);
    $unused = apply_filters('wpwf_add_guest_sub', $thread); //--weaver-- Maybe add a subscription
    wp_redirect(html_entity_decode($this->get_paged_threadlink($thread) . "#postid-" . $new_id));
    exit;
  }
  else
    wp_die($msg);
}

//EDITING A POST?
if (isset($_POST['edit_post_submit']))
{
  $myReplaceSub = array("\\");
  $subject = str_replace($myReplaceSub, "", $this->input_filter($_POST['edit_post_subject']));
  $content = $this->input_filter($_POST['message']);
  $thread = $this->check_parms($_POST['thread_id']);
  $edit_post_id = $_POST['edit_post_id'];
  $msg = '';

  if ($subject == "")
  {
    $msg .= "<h2>" . __("An error occured", "mingleforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a subject", "mingleforum") . "</p></div>");
    $error = true;
  }
  if ($content == "")
  {
    $msg .= "<h2>" . __("An error occured", "mingleforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a message", "mingleforum") . "</p></div>");
    $error = true;
  }
  //Major security check here, prevents hackers from editing the entire forums posts
  if (!is_super_admin($user_ID) && $user_ID != $this->get_post_owner($edit_post_id) && !$this->is_moderator($user_ID, $the_forum_id))
  {
    $msg .= "<h2>" . __("An error occured", "mingleforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You do not have permission to edit this post!", "mingleforum") . "</p></div>");
    $error = true;
  }

  if ($error)
    wp_die($msg);

  $sql = ("UPDATE {$this->t_posts} SET text = %s, subject = %s WHERE id = %d");
  $wpdb->query($wpdb->prepare($sql, $content, $subject, $edit_post_id));

  $ret = $wpdb->get_results($wpdb->prepare("select id from {$this->t_posts} where parent_id = %d order by date asc limit 1", $thread));
  if ($ret[0]->id == $edit_post_id)
  {
    $sql = ("UPDATE {$this->t_threads} set subject = %s where id = %d");
    $wpdb->query($wpdb->prepare($sql, $subject, $thread));
  }

  wp_redirect(html_entity_decode($this->get_paged_threadlink($thread) . "#postid-" . $edit_post_id));
  exit;
}
?>
