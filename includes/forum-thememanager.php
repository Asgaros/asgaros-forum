<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumThemeManager {
	const AF_THEMEPATH = 'themes-asgarosforum';
	const AF_SKINPATH = 'skin';
	const AF_DEFAULT_THEME = 'default';
	private static $asgarosforum = null;
	private static $themes_root;		// Path to themes directory.
	private static $plugin_url;			// URL to plugin directory.
	private static $themes = array();	// Array of available themes.
	private static $current_theme;		// The current theme.

	public function __construct($object) {
		self::$asgarosforum = $object;
		self::$themes_root = trailingslashit(WP_CONTENT_DIR.'/'.self::AF_THEMEPATH);
		self::$plugin_url = self::$asgarosforum->directory;
		$this->find_themes();

		if (!empty(self::$themes[self::$asgarosforum->options['theme']])) {
			self::$current_theme = self::$asgarosforum->options['theme'];
		} else {
			// If the selected theme is not there, use the default.
			self::$current_theme = self::AF_DEFAULT_THEME;
		}

		add_action('wp_head', array($this, 'setHeader'));
	}

	// Find available themes.
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

			if (is_array($themes) && !empty($themes)) {
				foreach ($themes as $themepath) {
					// Ensure that only themes appears which contains all necessary files.
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

	// Get all available themes.
	public static function get_themes() {
		return self::$themes;
	}

	// Get the current theme.
	public static function get_current_theme() {
		return self::$current_theme;
	}

	// Set the current theme.
	public static function set_current_theme($theme) {
		if (empty(self::$themes[$theme])) {
			self::$current_theme = self::AF_DEFAULT_THEME;
		} else {
			self::$current_theme = $theme;
		}
	}

	// Returns the URL to the path of the selected theme.
	public static function get_current_theme_url() {
		return self::$themes[self::get_current_theme()]['url'];
	}

	// Check if current theme is the default theme.
	public static function is_default_theme() {
		return (self::get_current_theme() === self::AF_DEFAULT_THEME) ? true : false;
	}

	public static function setHeader() {
		echo '<!-- Asgaros Forum: BEGIN -->'.PHP_EOL;

		// SEO stuff.
		if (self::$asgarosforum->executePlugin) {
			$currentLink = (self::$asgarosforum->current_page > 0) ? self::$asgarosforum->getLink('current') : esc_url(remove_query_arg('part', self::$asgarosforum->getLink('current', false, false, '', false)));
			$currentTitle = (self::$asgarosforum->current_title) ? self::$asgarosforum->current_title : get_the_title();
			$currentTitle = (self::$asgarosforum->current_page > 0) ? $currentTitle.' - '.__('Page', 'asgaros-forum').' '.(self::$asgarosforum->current_page + 1) : $currentTitle;
			$currentDescription = (self::$asgarosforum->current_description) ? self::$asgarosforum->current_description : $currentTitle;

			echo '<link rel="canonical" href="'.$currentLink.'" />'.PHP_EOL;
			echo '<meta name="description" content="'.$currentDescription.'" />'.PHP_EOL;
			echo '<meta property="og:url" content="'.$currentLink.'" />'.PHP_EOL;
			echo '<meta property="og:title" content="'.$currentTitle.'" />'.PHP_EOL;
			echo '<meta property="og:description" content="'.$currentDescription.'" />'.PHP_EOL;
			echo '<meta property="og:site_name" content="'.get_bloginfo('name').'" />'.PHP_EOL;
			echo '<meta name="twitter:title" content="'.$currentTitle.'" />'.PHP_EOL;
			echo '<meta name="twitter:description" content="'.$currentDescription.'" />'.PHP_EOL;
		}

		$themeurl = self::get_current_theme_url();

        echo '<link rel="stylesheet" type="text/css" href="'.$themeurl.'/widgets.css?ver='.self::$asgarosforum->version.'" />'.PHP_EOL;

		if (self::$asgarosforum->executePlugin) {
	        echo '<link rel="stylesheet" type="text/css" href="'.$themeurl.'/style.css?ver='.self::$asgarosforum->version.'" />'.PHP_EOL;

	        if (self::is_default_theme()) {
	            if ((self::$asgarosforum->options['custom_color'] !== self::$asgarosforum->options_default['custom_color']) || (self::$asgarosforum->options['custom_text_color'] !== self::$asgarosforum->options_default['custom_text_color']) || (self::$asgarosforum->options['custom_background_color'] !== self::$asgarosforum->options_default['custom_background_color'])) {
	                echo '<link rel="stylesheet" type="text/css" href="'.$themeurl.'/custom-color.php?color='.substr(self::$asgarosforum->options['custom_color'], 1).'&amp;text-color='.substr(self::$asgarosforum->options['custom_text_color'], 1).'&amp;background-color='.substr(self::$asgarosforum->options['custom_background_color'], 1).'&ver='.self::$asgarosforum->version.'" />'.PHP_EOL;
	            }
	        }

	        if (wp_is_mobile()) {
	            echo '<link rel="stylesheet" type="text/css" href="'.$themeurl.'/mobile.css?ver='.self::$asgarosforum->version.'" />'.PHP_EOL;
	        }
		}

		echo '<!-- Asgaros Forum: END -->'.PHP_EOL;
	}
}
