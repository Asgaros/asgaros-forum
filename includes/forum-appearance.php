<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumAppearance {
	const AF_THEMEPATH = 'themes-asgarosforum';
	const AF_SKINPATH = 'skin';
	const AF_DEFAULT_THEME = 'default';
	private static $asgarosforum = null;
	private static $themes_root;		// Path to themes directory.
	private static $plugin_url;			// URL to plugin directory.
	private static $themes = array();	// Array of available themes.
	private static $current_theme;		// The current theme.
	public static $options = array();
	public static $options_default = array(
		'theme'                     => 'default',
		'custom_color'              => '#2d89cc',
        'custom_text_color'         => '#444444',
        'custom_background_color'	=> '#ffffff',
        'custom_border_color'       => '#eeeeee',
		'custom_font'				=> 'Verdana, Tahoma, sans-serif',
		'custom_font_size'			=> '13px'
	);

	public function __construct($object) {
		self::$asgarosforum = $object;

		add_action('init', array($this, 'initialize'));
	}

	public function initialize() {
		self::$themes_root = trailingslashit(WP_CONTENT_DIR.'/'.self::AF_THEMEPATH);
		self::$plugin_url = self::$asgarosforum->directory;
		self::find_themes();
		self::loadOptions();

		add_filter('mce_css', array($this, 'addEditorCSS'));
		add_action('wp_enqueue_scripts', array($this, 'addCSS'));
		add_action('wp_head', array($this, 'setHeader'));
	}

	public static function loadOptions() {
		// Load options.
		self::$options = array_merge(self::$options_default, get_option('asgarosforum_appearance', array()));

		// Set the used theme.
		if (empty(self::$themes[self::$options['theme']])) {
			self::$current_theme = self::AF_DEFAULT_THEME;
		} else {
			self::$current_theme = self::$options['theme'];
		}
	}

	public static function saveOptions($options) {
		update_option('asgarosforum_appearance', $options);

		// Reload options after saving them.
		self::loadOptions();
	}

	// Find available themes.
	private static function find_themes() {
		// Always ensure that the default theme is available.
		self::$themes[self::AF_DEFAULT_THEME] = array(
			'name'	=> __('Default Theme', 'asgaros-forum'),
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

	// Returns the URL to the path of the selected theme.
	public static function get_current_theme_url() {
		return self::$themes[self::get_current_theme()]['url'];
	}

	// Check if current theme is the default theme.
	public static function is_default_theme() {
		return (self::get_current_theme() === self::AF_DEFAULT_THEME) ? true : false;
	}

	public function setHeader() {
		echo '<!-- Asgaros Forum: BEGIN -->'.PHP_EOL;

		// SEO stuff.
		if (self::$asgarosforum->executePlugin) {
			$currentLink = (self::$asgarosforum->current_page > 0) ? self::$asgarosforum->getLink('current') : esc_url(remove_query_arg('part', self::$asgarosforum->getLink('current', false, false, '', false)));
			$currentTitle = (self::$asgarosforum->getMetaTitle()) ? self::$asgarosforum->getMetaTitle() : get_the_title();
			$currentDescription = (self::$asgarosforum->current_description) ? self::$asgarosforum->current_description : $currentTitle;

			// Prevent indexing of some views.
			switch (self::$asgarosforum->current_view) {
				case 'addtopic':
				case 'movetopic':
				case 'addpost':
				case 'editpost':
				case 'search':
					echo '<meta name="robots" content="noindex, follow" />'.PHP_EOL;
				break;
				default:
				break;
			}

			echo '<link rel="canonical" href="'.$currentLink.'" />'.PHP_EOL;
			echo '<meta name="description" content="'.$currentDescription.'" />'.PHP_EOL;
			echo '<meta property="og:url" content="'.$currentLink.'" />'.PHP_EOL;
			echo '<meta property="og:title" content="'.$currentTitle.'" />'.PHP_EOL;
			echo '<meta property="og:description" content="'.$currentDescription.'" />'.PHP_EOL;
			echo '<meta property="og:site_name" content="'.get_bloginfo('name').'" />'.PHP_EOL;
			echo '<meta name="twitter:title" content="'.$currentTitle.'" />'.PHP_EOL;
			echo '<meta name="twitter:description" content="'.$currentDescription.'" />'.PHP_EOL;
		}

		echo '<!-- Asgaros Forum: END -->'.PHP_EOL;
	}

	public function addCSS() {
		$themeurl = self::get_current_theme_url();

		wp_enqueue_style('af-widgets', $themeurl.'/widgets.css', array(), self::$asgarosforum->version);

		if (self::$asgarosforum->executePlugin) {
			wp_enqueue_style('af-style', $themeurl.'/style.css', array(), self::$asgarosforum->version);

			if (self::is_default_theme()) {
				if (self::$options != self::$options_default) {
					$custom_styles_url = add_query_arg(
						array(
							'color'				=> substr(self::$options['custom_color'], 1),
							'text-color'		=> substr(self::$options['custom_text_color'], 1),
							'background-color'	=> substr(self::$options['custom_background_color'], 1),
							'border-color'		=> substr(self::$options['custom_border_color'], 1),
							'font'				=> self::$options['custom_font'],
							'font-size'			=> self::$options['custom_font_size']
						),
						$themeurl.'/custom-color.php'
					);

					wp_enqueue_style('af-custom-color', $custom_styles_url, array(), self::$asgarosforum->version);
				}
			}

			if (wp_is_mobile()) {
				wp_enqueue_style('af-mobile', $themeurl.'/mobile.css', array(), self::$asgarosforum->version);
			}
		}
	}

	// Add a custom stylesheet to the TinyMCE editor.
	public function addEditorCSS($mce_css) {
		if (!empty($mce_css)) {
			$mce_css .= ',';
		}

		$mce_css .= self::get_current_theme_url().'/editor.css?ver='.self::$asgarosforum->version;

		return $mce_css;
	}
}
