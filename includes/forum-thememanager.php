<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumThemeManager {
	const AF_THEMEPATH = 'themes-asgarosforum';
	const AF_SKINPATH = 'skin';
	const AF_DEFAULT_THEME = 'default';

	private static $instance = null;	// AsgarosForumThemeManager instance
	private static $themes_root;		// Path to themes directory
	private static $plugin_url;			// URL to plugin directory
	private static $themes = array();	// Array of available themes
	private static $current_theme;		// The current theme

	// AsgarosForumThemeManager instance creator
	public static function getInstance($plugin_url) {
		if (self::$instance === null) {
			self::$instance = new self($plugin_url);
		} else {
			return self::$instance;
		}
	}

	// AsgarosForumThemeManager constructor
	private function __construct($plugin_url) {
		global $asgarosforum;
		self::$themes_root = trailingslashit(WP_CONTENT_DIR.'/'.self::AF_THEMEPATH);
		self::$plugin_url = trailingslashit($plugin_url);
		$this->find_themes();

		if (!empty(self::$themes[$asgarosforum->options['theme']])) {
			self::$current_theme = $asgarosforum->options['theme'];
		} else {
			// If the selected theme is not there, use the default.
			self::$current_theme = self::AF_DEFAULT_THEME;
		}

		add_action('wp_head', array($this, 'setHeader'));
	}

	// Find what themes we have available
	private static function find_themes() {
		// Always ensure that the default theme is available.
		self::$themes[self::AF_DEFAULT_THEME] = array(
			'name'	=> 'Default theme',
			'url'	=> self::$plugin_url.self::AF_SKINPATH
		);

		// Create themes directory if it doesnt exist.
		if (!is_dir(self::$themes_root)) {
			wp_mkdir_p(self::$themes_root);
		} else {
			// Check the themes directory for more themes.
			$themes = glob(self::$themes_root.'*');

			if (is_array($themes) && count($themes) > 0) {
				foreach ($themes as $themepath) {
					// Check that only directories with style.css files are considered.
					if (is_dir($themepath) && is_file($themepath.'/style.css') && is_file($themepath.'/mobile.css') && is_file($themepath.'/widgets.css')) {
						$trimmed = preg_filter('/^.*\//', '', $themepath, 1);
						self::$themes[$trimmed] = array(
							'name'	=> $trimmed,
							'url'	=> content_url(self::AF_THEMEPATH.'/'.$trimmed)
						);
					}
				}
			}
		}
	}

	// Get all available themes
	public static function get_themes() {
		return self::$themes;
	}

	// Get the current theme
	public static function get_current_theme() {
		return self::$current_theme;
	}

	// Set the current theme
	public static function set_current_theme($theme) {
		if (empty(self::$themes[$theme])) {
			self::$current_theme = 'default';
		} else {
			self::$current_theme = $theme;
		}
	}

	// Returns the URL to the path of the selected theme
	public static function get_current_theme_url() {
		return self::$themes[self::get_current_theme()]['url'];
	}

	// Check if current theme is the default theme
	public static function is_default_theme() {
		return (self::get_current_theme() === self::AF_DEFAULT_THEME) ? true : false;
	}

	public static function setHeader() {
		global $asgarosforum;
		$themeurl = self::get_current_theme_url();

        echo '<link rel="stylesheet" type="text/css" href="'.$themeurl.'/widgets.css" />';

        if (!$asgarosforum->execute_plugin()) {
            return;
        }

        echo '<link rel="stylesheet" type="text/css" href="'.$themeurl.'/style.css" />';

        if (self::is_default_theme()) {
            if (($asgarosforum->options['custom_color'] !== $asgarosforum->options_default['custom_color']) || ($asgarosforum->options['custom_text_color'] !== $asgarosforum->options_default['custom_text_color']) || ($asgarosforum->options['custom_background_color'] !== $asgarosforum->options_default['custom_background_color'])) {
                echo '<link rel="stylesheet" type="text/css" href="'.$themeurl.'/custom-color.php?color='.substr($asgarosforum->options['custom_color'], 1).'&amp;text-color='.substr($asgarosforum->options['custom_text_color'], 1).'&amp;background-color='.substr($asgarosforum->options['custom_background_color'], 1).'" />';
            }
        }

        if (wp_is_mobile()) {
            echo '<link rel="stylesheet" type="text/css" href="'.$themeurl.'/mobile.css" />';
        }
	}
}

?>
