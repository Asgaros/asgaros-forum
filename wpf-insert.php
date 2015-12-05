<?php
global $wpdb;
$error = false;
$the_forum_id = false;
$the_thread_id = false;

if (isset($_POST['add_thread_forumid']) && !empty($_POST['add_thread_forumid'])) {
    $the_forum_id = $_POST['add_thread_forumid'];
}

if (isset($_POST['add_post_forumid']) && !empty($_POST['add_post_forumid'])) {
    $the_thread_id = $_POST['add_post_forumid'];
    $the_forum_id = $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$this->table_threads} WHERE id = %d", $the_thread_id));
}

if (isset($_POST['thread_id']) && !empty($_POST['thread_id']) && isset($_POST['edit_post_submit'])) {
    $the_thread_id = $_POST['thread_id'];
    $the_forum_id = $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$this->table_threads} WHERE id = %d", $the_thread_id));
}

function mf_u_key() {
    $pref = "";

    for ($i = 0; $i < 5; $i++) {
        $d = rand(0, 1);
        $pref .= $d ? chr(rand(97, 122)) : chr(rand(48, 57));
    }

    return $pref . "-";
}

function MFAttachImage($temp, $name) {
    $upload_dir = wp_upload_dir();
    $path = $upload_dir['path'] . "/";
    $url = $upload_dir['url'] . "/";
    $u = mf_u_key();
    $name = sanitize_file_name($name);

    if (!empty($name)) {
        move_uploaded_file($temp, $path . $u . $name);
    }

    return "\n[img]" . $url . $u . $name . "[/img]";
}

function MFGetExt($str)
{
  // GETS THE FILE EXTENSION BELONGING TO THE UPLOADED FILE
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

//ADDING A NEW THREAD?
if (isset($_POST['add_thread_submit']))
{
  $subject = $_POST['add_thread_subject'];
  $content = $_POST['message'];
  $forum_id = $_POST['add_thread_forumid'];
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
    $date = $this->wpf_current_time_fixed();

    $sql_thread = "INSERT INTO {$this->table_threads}
                      (name, parent_id, status)
                    VALUES
                      (%s, %d, 'normal_open')";
    $wpdb->query($wpdb->prepare($sql_thread, $subject, $forum_id));

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

    $sql_post = "INSERT INTO {$this->table_posts}
                    (text, parent_id, `date`, author_id)
                  VALUES
                    (%s, %d, %s, %d)";
    $wpdb->query($wpdb->prepare($sql_post, $content, $id, $date, $user_ID));
    $new_post_id = $wpdb->insert_id;
  }
  if (!$error)
  {
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
  $content = $_POST['message'];
  $thread = $_POST['add_post_forumid'];
  $msg = '';

  if ($content == "")
  {
    $msg .= "<h2>" . __("An error occured", "asgarosforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You must enter a message", "asgarosforum") . "</p></div>");
    $error = true;
  }
  else
  {
    $date = $this->wpf_current_time_fixed();
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

    $sql_post = "INSERT INTO {$this->table_posts}
            (text, parent_id, `date`, author_id)
         VALUES(%s, %d, %s, %d)";
    $wpdb->query($wpdb->prepare($sql_post, $content, $thread, $date, $user_ID));
    $new_id = $wpdb->insert_id;
  }

  if (!$error)
  {
    wp_redirect(html_entity_decode($this->get_postlink($thread, $new_id)));
    exit;
  }
  else
    wp_die($msg);
}

//EDITING A POST?
if (isset($_POST['edit_post_submit']))
{
  $subject = "";
  if (isset($_POST['edit_post_subject'])) {
  $subject = $_POST['edit_post_subject'];
  }
  $content = $_POST['message'];
  $thread = $_POST['thread_id'];
  $edit_post_id = $_POST['edit_post_id'];
  $msg = '';

  if (isset($_POST['edit_post_subject']) && $subject == "")
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
  if (!is_super_admin($user_ID) && $user_ID != $this->get_post_owner($edit_post_id) && !$this->is_moderator($user_ID))
  {
    $msg .= "<h2>" . __("An error occured", "asgarosforum") . "</h2>";
    $msg .= ("<div id='error'><p>" . __("You do not have permission to edit this post!", "asgarosforum") . "</p></div>");
    $error = true;
  }

  if ($error)
    wp_die($msg);

  $sql = ("UPDATE {$this->table_posts} SET text = %s WHERE id = %d");
  $wpdb->query($wpdb->prepare($sql, $content, $edit_post_id));
  if (isset($_POST['edit_post_subject'])) {
  $ret = $wpdb->get_results($wpdb->prepare("select id from {$this->table_posts} where parent_id = %d order by date asc limit 1", $thread));
  if ($ret[0]->id == $edit_post_id)
  {
    $sql = ("UPDATE {$this->table_threads} set name = %s where id = %d");
    $wpdb->query($wpdb->prepare($sql, $subject, $thread));
  }
  }
  wp_redirect(html_entity_decode($this->get_postlink($thread, $edit_post_id, $_POST['page_id'])));
  exit;
}
?>
