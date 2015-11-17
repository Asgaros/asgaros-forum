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
//End Check

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

if (!$user_ID)
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
    $msg = __("Security code does not match", "asgarosforum");
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
    $msg .= "<h2>" . __("An error occured", "asgarosforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a subject", "asgarosforum") . "</p></div>");
    $error = true;
  }
  elseif ($content == "")
  {
    $msg .= "<h2>" . __("An error occured", "asgarosforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a message", "asgarosforum") . "</p></div>");
    $error = true;
  }
  else
  {
    $date = $this->wpf_current_time_fixed('mysql', 0);

    $sql_thread = "INSERT INTO {$this->t_threads}
                      (subject, parent_id, status, starter)
                    VALUES
                      (%s, %d, 'open', %d)";
    $wpdb->query($wpdb->prepare($sql_thread, $subject, $forum_id, $cur_user_ID));

    $id = $wpdb->insert_id;

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
  }
  if (!$error)
  {
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

  if ($subject == "")
  {
    $msg .= "<h2>" . __("An error occured", "asgarosforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a subject", "asgarosforum") . "</p></div>");
    $error = true;
  }
  elseif ($content == "")
  {
    $msg .= "<h2>" . __("An error occured", "asgarosforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a message", "asgarosforum") . "</p></div>");
    $error = true;
  }
  else
  {
    $date = $this->wpf_current_time_fixed('mysql', 0);
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
  }

  if (!$error)
  {
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
    $msg .= "<h2>" . __("An error occured", "asgarosforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a subject", "asgarosforum") . "</p></div>");
    $error = true;
  }
  if ($content == "")
  {
    $msg .= "<h2>" . __("An error occured", "asgarosforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a message", "asgarosforum") . "</p></div>");
    $error = true;
  }
  //Major security check here, prevents hackers from editing the entire forums posts
  if (!is_super_admin($user_ID) && $user_ID != $this->get_post_owner($edit_post_id) && !$this->is_moderator($user_ID, $the_forum_id))
  {
    $msg .= "<h2>" . __("An error occured", "asgarosforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You do not have permission to edit this post!", "asgarosforum") . "</p></div>");
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
