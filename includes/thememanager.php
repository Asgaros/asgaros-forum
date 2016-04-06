<?php

if (!defined('ABSPATH')) exit;

class ThemeManager {
	const AF_THEMEPATH = 'themes-asgarosforum';
	const AF_SKINPATH = 'skin';
	const AF_THEMES = 'themes';
	const AF_DEFAULT_THEME = 'default';

	protected static $instance = null;	// ThemeManager instance
	private static $themes_root;		// Path to themes directory
	private static $plugin_root;		// Path to plugin directory
	private static $themes = array();	// Array of available themes
	private static $current_theme;		// The current theme

	// ThemeManager instance creator
	public static function instance($plugin_root) {
		if (static::$instance === null) {
			static::$instance = new static($plugin_root);
		} else {
			return static::$instance;
		}
	}

	// ThemeManager constructor
	private function __construct($plugin_root) {
		global $asgarosforum;
		self::$themes_root = trailingslashit(WP_CONTENT_DIR.'/'.self::AF_THEMEPATH);
		self::$plugin_root = trailingslashit($plugin_root);
		static::find_themes();

		if (!empty(self::$themes[$asgarosforum->options['theme']])) {
			self::$current_theme = $asgarosforum->options['theme'];
		} else {
			// If the selected theme is not there, use the default.
			self::$current_theme = self::AF_DEFAULT_THEME;
		}
	}

	// Find what themes we have available
	private static function find_themes() {
		// Always ensure the default theme is available.
		self::$themes[ self::AF_DEFAULT_THEME ] = array(
			'name' => 'Default Asgaros theme',
			'path' => self::$plugin_root . '/' . self::AF_SKINPATH,
			'url'  => '',
		);

		// Create themes directory if it doesnt exist
		if ( ! is_dir( self::$themes_root ) ) {
			wp_mkdir_p( self::$themes_root );
		} else {
			// Check the themes directory for more themes.
			foreach ( glob( self::$themes_root . '/*' ) as $themepath ) {
				// Check that only directories with style.css files are considered.
				if ( is_dir( $themepath ) && is_file( $themepath . '/style.css' ) ) {
					$trimmed = preg_filter( '/^.*\//', '', $themepath, 1 );
					self::$themes[ $trimmed ] = array(
						'name' => $trimmed,
						'path' => $themepath,
						'url' => self::AF_THEMEPATH . '/' . $trimmed,
					);
				}
			}
		}
	}

	/**
	 * Get the themes discovered
	 *
	 * @return array
	 */
	public static function get_themes() {
		return self::$themes;
	}

	/**
	 * Get the currently selected theme
	 *
	 * @return string
	 */
	public static function get_current_theme() {
		return self::$current_theme;
	}

	/**
	 * Set the current theme
	 *
	 * @param string $theme Name of theme selected.
	 */
	public static function set_current_theme( $theme ) {
		if ( empty( self::$themes[ $theme ] ) ) {
			self::$current_theme = 'default';
		}

		self::$current_theme = $theme;
	}

	/**
	 * Returns the URL to the path of the selected theme
	 *
	 * @return string
	 */
	public static function get_current_theme_path() {
		$path = self::$themes[ self::get_current_theme() ]['url'];

		if ( empty( $path ) ) {
			$path = self::$themes[ self::AF_DEFAULT_THEME ]['path'];
			return plugin_dir_url( $path . '/' . self::AF_SKINPATH );
		}

		$url = content_url( $path );
		return $url;
	}

	/**
	 * Following a change of themes (install for example) a rescan will be needed
	 */
	public static function rescan_themes() {
		self::$themes = array();
		static::find_themes();
	}
}

?>
