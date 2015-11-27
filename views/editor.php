<?php
$this->editor_settings['textarea_rows'] = 12;
$thread = "";
$post = "";
$t = "";

if ($_GET['forumaction'] == "addtopic") {
    if (!$this->forum_exists($_GET['forum'])) {
        wp_die(__("Sorry, this forum does not exist.", "asgarosforum"));
    }

    if (!$user_ID && !$this->allow_unreg()) {
        wp_die(__("Sorry, you don't have permission to post.", "asgarosforum"));
    }
}

if ($_GET['forumaction'] == "postreply") {
    if (!$user_ID && !$this->allow_unreg()) {
        wp_die(__("Sorry, you don't have permission to post.", "asgarosforum"));
    }

    $thread = $this->check_parms($_GET['thread']);

    // TODO: Überprüfen
    if (isset($_GET['quote'])) {
        $quote_id = $this->check_parms($_GET['quote']);
        $text = $wpdb->get_row($wpdb->prepare("SELECT text, author_id, `date` FROM {$this->t_posts} WHERE id = %d", $quote_id));
        $user = get_userdata($text->author_id);
        $display_type = $this->options['forum_display_name'];
        $display_name = (!empty($user)) ? $user->$display_type : __('Guest', 'asgarosforum');
        $q = "[quote][quotetitle]" . __("Quote from", "asgarosforum") . " " . $display_name . " " . __("on", "asgarosforum") . " " . $this->format_date($text->date) . "[/quotetitle]\n" . $text->text . "[/quote]";
    }
}

if ($_GET['forumaction'] == "editpost") {
    if (!$user_ID && !$this->allow_unreg()) {
        wp_die(__("Sorry, you don't have permission to post.", "asgarosforum"));
    }

    $id = (isset($_GET['id']) && !empty($_GET['id'])) ? (int)$_GET['id'] : 0;
    $thread = $this->check_parms($_GET['t']);
    $t = $wpdb->get_row($wpdb->prepare("SELECT subject FROM {$this->t_threads} WHERE id = %d", $thread));
    $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->t_posts} WHERE id = %d", $id));

    if (!($user_ID == $post->author_id && $user_ID) && !$this->is_moderator($user_ID)) {
        wp_die("Sorry, you are not allowed to edit this post.", "asgarosforum");
    }
}

?>

<form action='' name='addform' method='post' enctype='multipart/form-data'>
    <table class='wpf-table' width='100%'>
        <tr>
            <th colspan='2'>
                <?php
                if ($_GET['forumaction'] == "addtopic") {
                    _e("Post new Topic", "asgarosforum");
                } else if ($_GET['forumaction'] == "postreply") {
                    echo __("Post Reply:", "asgarosforum") . ' ' . $this->get_subject($thread);
                } else if ($_GET['forumaction'] == "editpost") {
                    echo __("Edit Post:", "asgarosforum") . ' ' . stripslashes($t->subject);
                }
                ?>
            </th>
        </tr>
        <?php if ($_GET['forumaction'] == "addtopic") { ?>
        <tr>
            <td><?php _e("Subject:", "asgarosforum"); ?></td>
            <td><input size='50%' type='text' name='add_topic_subject' class='wpf-input' /></td>
        </tr>
        <?php } ?>
        <?php
        /*if(false) //Need to enable this eventually if we're editing the first post in the thread
        echo "<tr>
        <td>" . __("Subject:", "asgarosforum") . "</td>
        <td><input size='50%' type='text' name='edit_post_subject' class='wpf-input' value='" . stripslashes($t->subject) . "'/></td>
        </tr>";*/
        ?>
        <tr>
            <td valign='top'><?php _e("Message:", "asgarosforum"); ?></td>
            <td>
                <?php
                if ($_GET['forumaction'] == "editpost") {
                    wp_editor(stripslashes($post->text), 'message', $this->editor_settings);
                } else {
                    wp_editor('', 'message', $this->editor_settings);
                }
                ?>
            </td>
        </tr>
        <?php
        if ($_GET['forumaction'] != "editpost") {
            echo $this->get_captcha();
        }
        if ($_GET['forumaction'] != "editpost" && $this->options['forum_allow_image_uploads']) { ?>
		<tr>
			<td valign='top'><?php _e("Images:", "asgarosforum"); ?></td>
			<td colspan='2'>
				<input type='file' name='mfimage1' id='mfimage' /><br/>
				<input type='file' name='mfimage2' id='mfimage' /><br/>
				<input type='file' name='mfimage3' id='mfimage' /><br/>
			</td>
		</tr>
        <?php } ?>
        <tr>
            <td></td>
            <?php if ($_GET['forumaction'] == "addtopic") { ?>
                <td><input type='submit' id='wpf-post-submit' name='add_topic_submit' value='<?php _e("Submit", "asgarosforum"); ?>' /></td>
                <input type='hidden' name='add_topic_forumid' value='<?php echo $this->check_parms($_GET['forum']); ?>' />
            <?php } else if ($_GET['forumaction'] == "postreply") { ?>
                <td><input type='submit' id='wpf-post-submit' name='add_post_submit' value='<?php _e("Submit", "asgarosforum"); ?>' /></td>
                <input type='hidden' name='add_post_forumid' value='<?php echo $thread; ?>' />
            <?php } else if ($_GET['forumaction'] == "editpost") { ?>
                <td><input type='submit' id='wpf-post-submit' name='edit_post_submit' value='<?php _e("Submit", "asgarosforum"); ?>' /></td>
                <input type='hidden' name='edit_post_id' value='<?php echo $post->id; ?>' />
                <input type='hidden' name='thread_id' value='<?php echo $thread; ?>' />
                <input type='hidden' name='page_id' value='<?php echo $this->curr_page; ?>' />
            <?php } ?>
        </tr>
    </table>
</form>
