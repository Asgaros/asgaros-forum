<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumUploads {
	const AF_UPLOADFOLDER = 'asgarosforum';
	private static $asgarosforum = null;
	private static $upload_path;
	private static $upload_url;
	private static $upload_allowed_filetypes;

	public function __construct($object) {
		self::$asgarosforum = $object;

		$upload_dir = wp_upload_dir();
        self::$upload_path = $upload_dir['basedir'].'/'.self::AF_UPLOADFOLDER.'/';
        self::$upload_url = $upload_dir['baseurl'].'/'.self::AF_UPLOADFOLDER.'/';
		self::$upload_allowed_filetypes = explode(',', self::$asgarosforum->options['allowed_filetypes']);
	}

	public static function deletePostFiles($post_id) {
		$path = self::$upload_path.$post_id.'/';

        if (is_dir($path)) {
            $files = array_diff(scandir($path), array('.', '..'));

            foreach ($files as $file) {
                unlink($path.$file);
            }

            rmdir($path);
        }
	}

	// Generates the list of new files to upload.
	public static function prepareFileList() {
		$files = array();

		if (self::$asgarosforum->options['allow_file_uploads'] && !empty($_FILES['forumfile'])) {
            foreach ($_FILES['forumfile']['name'] as $index => $tmpName) {
                if (empty($_FILES['forumfile']['error'][$index]) && !empty($_FILES['forumfile']['name'][$index])) {
                    $file_extension = strtolower(pathinfo($_FILES['forumfile']['name'][$index], PATHINFO_EXTENSION));

                    // Check if its allowed to upload an file with this extension.
                    if (in_array($file_extension, self::$upload_allowed_filetypes)) {
						// Check if the size of the file is not too big.
						$maximumFileSize = (1024 * (1024 * self::$asgarosforum->options['uploads_maximum_size']));

						if ($maximumFileSize == 0 || $_FILES['forumfile']['size'][$index] <= $maximumFileSize) {
							$name = sanitize_file_name(stripslashes($_FILES['forumfile']['name'][$index]));

			                if (!empty($name)) {
								$files[$index] = $name;
							}
						}
                    }
                }
            }
        }

		return $files;
	}

	public static function uploadFiles($post_id, $uploadList = false) {
		$path = self::$upload_path.$post_id.'/';
		$links = array();
		$files = ($uploadList) ? $uploadList : self::prepareFileList();

		// When there are files to upload, create the folders first.
        if (!empty($files)) {
            if (!is_dir(self::$upload_path)) {
                mkdir(self::$upload_path);
            }

            if (!is_dir($path)) {
                mkdir($path);
            }
		}

		// Continue when the destination-folder got created correctly.
		if (is_dir($path)) {
	        // Register existing files.
	        if (!empty($_POST['existingfile'])) {
	            foreach ($_POST['existingfile'] as $file) {
	                if (is_file($path.basename($file))) {
	                    $links[] = $file;
	                }
	            }
	        }

	        // Remove deleted files.
	        if (!empty($_POST['deletefile'])) {
	            foreach ($_POST['deletefile'] as $file) {
	                if (is_file($path.basename($file))) {
	                    unlink($path.basename($file));
	                }
	            }
	        }

			// Upload new files.
	        if (!empty($files)) {
	            foreach($files as $index => $name) {
	                move_uploaded_file($_FILES['forumfile']['tmp_name'][$index], $path.$name);
	                $links[] = $name;
	            }
	        }

			// Remove folder if it is empty.
	        if (count(array_diff(scandir($path), array('.', '..'))) == 0) {
	            rmdir($path);
	        }
		}

        return $links;
    }

	public static function getFileList($postObject) {
		if ($postObject) {
			$path = self::$upload_path.$postObject->id.'/';
	        $url = self::$upload_url.$postObject->id.'/';
	        $uploads = maybe_unserialize($postObject->uploads);
	        $uploadedFiles = '';

	        if (!empty($uploads) && is_dir($path)) {
				// Generate special message instead of file-list when hiding uploads for guests.
				if (!is_user_logged_in() && self::$asgarosforum->options['hide_uploads_from_guests']) {
					$uploadedFiles .= '<li>'.__('You need to login to have access to uploads.', 'asgaros-forum').'&nbsp;<a href="'.esc_url(wp_login_url(self::$asgarosforum->getLink('current'))).'">&raquo; '.__('Login', 'asgaros-forum').'</a></li>';
				} else {
					foreach ($uploads as $upload) {
		                if (is_file($path.basename($upload))) {
							$imageThumbnail = (self::$asgarosforum->options['uploads_show_thumbnails']) ? wp_get_image_editor($path.basename($upload)) : false;

							if ($imageThumbnail && !is_wp_error($imageThumbnail)) {
								$uploadedFiles .= '<li><a class="uploaded-file" href="'.$url.utf8_encode($upload).'" target="_blank"><img class="resize" src="'.$url.utf8_encode($upload).'" alt="'.$upload.'" /></a></li>';
							} else {
								$uploadedFiles .= '<li><a class="uploaded-file" href="'.$url.utf8_encode($upload).'" target="_blank">'.$upload.'</a></li>';
							}
		                }
		            }
				}

				if (!empty($uploadedFiles)) {
	                echo '<strong>'.__('Uploaded files:', 'asgaros-forum').'</strong>';
	                echo '<ul>'.$uploadedFiles.'</ul>';
				}
	        }
		}
    }

	public static function showEditorUploadForm($postObject) {
		$uploadedFilesCounter = 0;

		// Show list of uploaded files first. Also shown when uploads are disabled to manage existing files if it was enabled before.
		if ($postObject) {
			$path = self::$upload_path.$postObject->id.'/';
	        $url = self::$upload_url.$postObject->id.'/';
	        $uploads = maybe_unserialize($postObject->uploads);
	        $uploadedFiles = '';

			if (!empty($uploads) && is_dir($path) && self::$asgarosforum->current_view === 'editpost') {
				foreach ($uploads as $upload) {
	                if (is_file($path.basename($upload))) {
						$uploadedFilesCounter++;
	                    $uploadedFiles .= '<li>';
	                    $uploadedFiles .= '<a href="'.$url.utf8_encode($upload).'" target="_blank">'.$upload.'</a> &middot; <a data-filename="'.$upload.'" class="delete">['.__('Delete', 'asgaros-forum').']</a>';
	                    $uploadedFiles .= '<input type="hidden" name="existingfile[]" value="'.$upload.'">';
	                    $uploadedFiles .= '</li>';
	                }
	            }

				if (!empty($uploadedFiles)) {
	                echo '<div class="editor-row">';
	                	echo '<span class="row-title">'.__('Uploaded files:', 'asgaros-forum').'</span>';
	                	echo '<div class="files-to-delete"></div>';
	                	echo '<ul class="uploaded-files">'.$uploadedFiles.'</ul>';
	                echo '</div>';
	            }
			}
		}

		// Show upload controls.
        if (self::$asgarosforum->options['allow_file_uploads'] && (is_user_logged_in() || self::$asgarosforum->options['allow_file_uploads_guests'])) {
			echo '<div class="editor-row editor-row-uploads">';
				echo '<span class="row-title">'.__('Upload Files:', 'asgaros-forum').'</span>';

				// Set maximum file size.
				if (self::$asgarosforum->options['uploads_maximum_size'] != 0) {
					echo '<input type="hidden" name="MAX_FILE_SIZE" value="'.(1024 * (1024 * self::$asgarosforum->options['uploads_maximum_size'])).'" />';
				}

				$flag = 'style="display: none;"';

				if (self::$asgarosforum->options['uploads_maximum_number'] == 0 || $uploadedFilesCounter < self::$asgarosforum->options['uploads_maximum_number']) {
					$uploadedFilesCounter++;
					echo '<input type="file" name="forumfile[]"><br />';

					if (self::$asgarosforum->options['uploads_maximum_number'] == 0 || $uploadedFilesCounter < self::$asgarosforum->options['uploads_maximum_number']) {
						$flag = '';
					}
				}

				echo '<a id="add_file_link" data-maximum-number="'.self::$asgarosforum->options['uploads_maximum_number'].'" '.$flag.'>'.__('Add another file ...', 'asgaros-forum').'</a>';

				if (self::$asgarosforum->options['uploads_maximum_number'] != 0) {
					echo '<span class="upload-filetypes">'.__('Maximum files per post:', 'asgaros-forum').'&nbsp;<i>'.number_format_i18n(esc_html(self::$asgarosforum->options['uploads_maximum_number'])).'</i></span>';
				}

				if (self::$asgarosforum->options['uploads_maximum_size'] != 0) {
					echo '<span class="upload-filetypes">'.__('Maximum file size (in megabyte):', 'asgaros-forum').'&nbsp;<i>'.number_format_i18n(esc_html(self::$asgarosforum->options['uploads_maximum_size'])).'</i></span>';
				}

				echo '<span class="upload-filetypes">'.__('Allowed filetypes:', 'asgaros-forum').'&nbsp;<i>'.esc_html(self::$asgarosforum->options['allowed_filetypes']).'</i></span>';
			echo '</div>';
		}
	}
}
