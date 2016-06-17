<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumUploads {
	const AF_UPLOADFOLDER = 'asgarosforum';

	private static $instance = null;
	private static $upload_path;
	private static $upload_url;
	private static $upload_allowed_filetypes;

	// AsgarosForumUploads instance creator
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self;
		} else {
			return self::$instance;
		}
	}

	// AsgarosForumUploads constructor
	private function __construct() {
		add_action('init', array($this, 'initUploadSettings'));
	}

	public static function initUploadSettings() {
		global $asgarosforum;

		$upload_dir = wp_upload_dir();
        self::$upload_path = $upload_dir['basedir'].'/'.self::AF_UPLOADFOLDER.'/';
        self::$upload_url = $upload_dir['baseurl'].'/'.self::AF_UPLOADFOLDER.'/';
		self::$upload_allowed_filetypes = explode(',', $asgarosforum->options['allowed_filetypes']);
    }

	public static function deletePostFiles($post_id) {
		$path = self::$upload_path.$post_id.'/';

        if (is_dir($path)) {
            $files = array_diff(scandir($path), array('..', '.'));

            foreach ($files as $file) {
                unlink($path.basename($file));
            }

            rmdir($path);
        }
	}

	public static function uploadFiles($post_id) {
        $files = array();
        $links = array();
        $path = self::$upload_path.$post_id.'/';

        // Register existing files
        if (isset($_POST['existingfile']) && !empty($_POST['existingfile'])) {
            foreach ($_POST['existingfile'] as $file) {
                if (is_dir($path) && file_exists($path.basename($file))) {
                    $links[] = $file;
                }
            }
        }

        // Remove deleted files
        if (isset($_POST['deletefile']) && !empty($_POST['deletefile'])) {
            foreach ($_POST['deletefile'] as $file) {
                if (is_dir($path) && file_exists($path.basename($file))) {
                    unlink($path.basename($file));
                }
            }
        }

        // Check for files to upload
        if (isset($_FILES['forumfile'])) {
            foreach ($_FILES['forumfile']['name'] as $index =>$tmpName) {
                if (empty($_FILES['forumfile']['error'][$index]) && !empty($_FILES['forumfile']['name'][$index])) {
                    $file_extension = strtolower(pathinfo($_FILES['forumfile']['name'][$index], PATHINFO_EXTENSION));

                    // Check if its allowed to upload an file with this extension.
                    if (in_array($file_extension, self::$upload_allowed_filetypes)) {
                        $files[$index] = true;
                    }
                }
            }
        }

        // Upload them
        if (count($files) > 0) {
            if (!is_dir(self::$upload_path)) {
                mkdir(self::$upload_path);
            }

            if (!is_dir($path)) {
                mkdir($path);
            }

            foreach($files as $index => $name) {
                $temp = $_FILES['forumfile']['tmp_name'][$index];
                $name = sanitize_file_name(stripslashes($_FILES['forumfile']['name'][$index]));

                if (!empty($name)) {
                    move_uploaded_file($temp, $path.$name);
                    $links[] = $name;
                }
            }
        }

        // Remove folder if it is empty
        if (is_dir($path) && count(array_diff(scandir($path), array('..', '.'))) == 0) {
            rmdir($path);
        }

        return $links;
    }

	public static function getFileList($post_id, $uploads, $frontend = false) {
        $path = self::$upload_path.$post_id.'/';
        $url = self::$upload_url.$post_id.'/';
        $uploads = maybe_unserialize($uploads);
        $upload_list = '';
        $upload_list_elements = '';

        if (!empty($uploads) && is_dir($path)) {
            foreach ($uploads as $upload) {
                if (file_exists($path.basename($upload))) {
                    if ($frontend) {
                        $upload_list_elements .= '<li><a href="'.$url.utf8_encode($upload).'" target="_blank">'.$upload.'</a></li>';
                    } else {
                        $upload_list_elements .= '<div class="uploaded-file">';
                        $upload_list_elements .= '<a href="'.$url.utf8_encode($upload).'" target="_blank">'.$upload.'</a> &middot; <a filename="'.$upload.'" class="delete">['.__('Delete', 'asgaros-forum').']</a>';
                        $upload_list_elements .= '<input type="hidden" name="existingfile[]" value="'.$upload.'">';
                        $upload_list_elements .= '</div>';
                    }
                }
            }

            if (!empty($upload_list_elements)) {
                if ($frontend) {
                    $upload_list .= '<strong>'.__('Uploaded files:', 'asgaros-forum').'</strong>';
                    $upload_list .= '<ul>'.$upload_list_elements.'</ul>';
                } else {
                    $upload_list .= '<div class="editor-row">';
                    $upload_list .= '<span class="row-title">'.__('Uploaded files:', 'asgaros-forum').'</span>';
                    $upload_list .= '<div class="files-to-delete"></div>';
                    $upload_list .= $upload_list_elements;
                    $upload_list .= '</div>';
                }
            }
        }

        echo $upload_list;
    }

	public static function showEditorUploadForm() {
		global $asgarosforum;

        // Check if this functionality is enabled and user is logged in
        if ($asgarosforum->options['allow_file_uploads']) {
			echo '<div class="editor-row editor-row-uploads">';
			echo '<span class="row-title">'.__('Upload Files:', 'asgaros-forum').'</span>';
			echo '<input type="file" name="forumfile[]"><br />';
			echo '<a id="add_file_link">'.__('Add another file ...', 'asgaros-forum').'</a><br />';
			echo '<span class="upload-filetypes">'.__('Allowed filetypes:', 'asgaros-forum').'&nbsp;<i>'.esc_html($asgarosforum->options['allowed_filetypes']).'</i></span>';
			echo '</div>';
		}
	}
}

?>
