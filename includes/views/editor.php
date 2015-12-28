<?php
$post = "";
$thread = "";
$threadname = "";
$quote = "";
$error = false;

if (!$user_ID) {
    $error = true;
    echo '<div class="notice">'.__("Sorry, you don't have permission to post.", "asgarosforum").'</div>';
}

if (!$error) {
    if ($_GET['view'] == "addthread") {
        if (!$this->current_forum || !$this->access) {
            $error = true;
            echo '<div class="notice">'.__("Sorry, this forum does not exist.", "asgarosforum").'</div>';
        }
    } else if ($_GET['view'] == "addpost") {
        if (!$this->current_thread || !$this->access) {
            $error = true;
            echo '<div class="notice">'.__("Sorry, this thread does not exist.", "asgarosforum").'</div>';
        }

        if (!$error && $this->get_status('closed') && !$this->is_moderator()) {
            $error = true;
            echo '<div class="notice">'.__("Sorry, but you are not allowed to do this.", "asgarosforum").'</div>';
        }

        if (!$error) {
            if (isset($_GET['quote']) && $this->element_exists($_GET['quote'], $this->table_posts)) {
                $quote_id = $_GET['quote'];
                $text = $wpdb->get_row($wpdb->prepare("SELECT text, author_id, date FROM {$this->table_posts} WHERE id = %d;", $quote_id));
                $display_name = $this->get_username($text->author_id);
                $quote = "<blockquote><div class='quotetitle'>" . __("Quote from", "asgarosforum") . " " . $display_name . " " . __("on", "asgarosforum") . " " . $this->format_date($text->date) . "</div>" . $text->text . "</blockquote><br />";
            }
        }
    } else if ($_GET['view'] == "editpost") {
        if (!$this->element_exists($_GET['id'], $this->table_posts) || !$this->access) {
            $error = true;
            echo '<div class="notice">'.__("Sorry, this post does not exist.", "asgarosforum").'</div>';
        }

        if (!$error) {
            $id = (isset($_GET['id']) && !empty($_GET['id'])) ? (int)$_GET['id'] : 0;
            $post = $wpdb->get_row($wpdb->prepare("SELECT id, text, parent_id, author_id, uploads FROM {$this->table_posts} WHERE id = %d;", $id));

            if ($user_ID != $post->author_id && !$this->is_moderator()) {
                $error = true;
                echo '<div class="notice">'.__("Sorry, you are not allowed to edit this post.", "asgarosforum").'</div>';
            }
        }

        if (!$error) {
            if ($this->is_first_post($post->id)) {
                $thread = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$this->table_threads} WHERE id = %d;", $post->parent_id));
                $threadname = $thread->name;
            }
        }
    }
}

if (!$error) { ?>
    <form name='addform' method='post' enctype='multipart/form-data'>
        <div class='title-element'>
            <?php
            if ($_GET['view'] == "addthread") {
                _e("Post new Thread", "asgarosforum");
            } else if ($_GET['view'] == "addpost") {
                echo __("Post Reply:", "asgarosforum") . ' ' . $this->get_name($this->current_thread, $this->table_threads);
            } else if ($_GET['view'] == "editpost") {
                _e("Edit Post", "asgarosforum");
            }
            ?>
        </div>
        <div class='content-element editor'>
            <table>
                <?php if ($_GET['view'] == "addthread" || ($_GET['view'] == "editpost" && $this->is_first_post($post->id))) { ?>
                    <tr>
                        <td><?php _e("Subject:", "asgarosforum"); ?></td>
                        <td><input type="text" name="subject" value="<?php echo $threadname; ?>" /></td>
                    </tr>
                <?php } ?>
                <tr>
                    <td><?php _e("Message:", "asgarosforum"); ?></td>
                    <td class="message-editor">
                        <?php
                        if ($_GET['view'] == "editpost") {
                            wp_editor(stripslashes($post->text), 'message', $this->options_editor);
                        } else {
                            wp_editor($quote, 'message', $this->options_editor);
                        }
                        ?>
                    </td>
                </tr>

                <?php if ($_GET['view'] == "editpost") { ?>
                    <?php $this->file_list($post->id, $post->uploads); ?>
                <?php } ?>


                <?php if ($this->options['allow_file_uploads']) { ?>
        		<tr>
        			<td><?php _e("Upload Files:", "asgarosforum"); ?></td>
        			<td>
        				<input type="file" name="forumfile[]" /><br />
                        <a id="add_file_link" href="#"><?php _e("Add another file ...", "asgarosforum"); ?></a>
        			</td>
        		</tr>
                <?php } ?>
                <tr>
                    <td></td>
                    <?php if ($_GET['view'] == "addthread") { ?>
                        <td>
                            <input type='submit' name='add_thread_submit' value='<?php _e("Submit", "asgarosforum"); ?>' />
                        </td>
                    <?php } else if ($_GET['view'] == "addpost") { ?>
                        <td>
                            <input type='submit' name='add_post_submit' value='<?php _e("Submit", "asgarosforum"); ?>' />
                        </td>
                    <?php } else if ($_GET['view'] == "editpost") { ?>
                        <td>
                            <input type='submit' name='edit_post_submit' value='<?php _e("Submit", "asgarosforum"); ?>' />
                            <input type='hidden' name='page_id' value='<?php echo $_GET['part']; ?>' />
                        </td>
                    <?php } ?>
                </tr>
            </table>
        </div>
    </form>
<?php } ?>
