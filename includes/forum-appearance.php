<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumAppearance {
	private $theme_path = 'themes-asgarosforum';
	private $skin_path = 'skin';
	private $default_theme = 'default';
	private $asgarosforum = null;
	private $themes_root;		// Path to themes directory.
	private $plugin_url;			// URL to plugin directory.
	private $themes = array();	// Array of available themes.
	private $current_theme;		// The current theme.
	public $options = array();
	public $options_default = array(
		'theme'                     => 'default',
		'custom_color'              => '#2d89cc',
        'custom_text_color'         => '#444444',
        'custom_background_color'	=> '#ffffff',
        'custom_border_color'       => '#eeeeee',
		'custom_font'				=> 'Verdana, Tahoma, sans-serif',
		'custom_font_size'			=> '13px'
	);

	public function __construct($object) {
		$this->asgarosforum = $object;

		add_action('init', array($this, 'initialize'));
	}

	public function initialize() {
		$this->themes_root = trailingslashit(WP_CONTENT_DIR.'/'.$this->theme_path);
		$this->plugin_url = $this->asgarosforum->directory;
		$this->find_themes();
		$this->load_options();

		add_filter('mce_css', array($this, 'add_editor_css'));
		add_action('wp_enqueue_scripts', array($this, 'add_css'));
		add_action('wp_head', array($this, 'set_header'));
	}

	public function load_options() {
		// Load options.
		$this->options = array_merge($this->options_default, get_option('asgarosforum_appearance', array()));

		// Set the used theme.
		if (empty($this->themes[$this->options['theme']])) {
			$this->current_theme = $this->default_theme;
		} else {
			$this->current_theme = $this->options['theme'];
		}
	}

	public function save_options($options) {
		update_option('asgarosforum_appearance', $options);

		// Reload options after saving them.
		$this->load_options();
	}

	// Find available themes.
	private function find_themes() {
		// Always ensure that the default theme is available.
		$this->themes[$this->default_theme] = array(
			'name'	=> __('Default Theme', 'asgaros-forum'),
			'url'	=> $this->plugin_url.$this->skin_path
		);

		// Create themes directory if it doesnt exist.
		if (!is_dir($this->themes_root)) {
			wp_mkdir_p($this->themes_root);
		} else {
			// Check the themes directory for more themes.
			$themes = glob($this->themes_root.'*');

			if (is_array($themes) && !empty($themes)) {
				foreach ($themes as $themepath) {
					// Ensure that only themes appears which contains all necessary files.
					if (is_dir($themepath) && is_file($themepath.'/style.css') && is_file($themepath.'/widgets.css')) {
						$trimmed = preg_filter('/^.*\//', '', $themepath, 1);
						$this->themes[$trimmed] = array(
							'name'	=> $trimmed,
							'url'	=> content_url($this->theme_path.'/'.$trimmed)
						);
					}
				}
			}
		}
	}

	// Get all available themes.
	public function get_themes() {
		return $this->themes;
	}

	// Get the current theme.
	public function get_current_theme() {
		return $this->current_theme;
	}

	// Returns the URL to the path of the selected theme.
	public function get_current_theme_url() {
		return $this->themes[$this->get_current_theme()]['url'];
	}

	// Check if current theme is the default theme.
	public function is_default_theme() {
		return ($this->get_current_theme() === $this->default_theme) ? true : false;
	}

	public function set_header() {
		echo '<!-- Asgaros Forum: BEGIN -->'.PHP_EOL;

		// SEO stuff.
		if ($this->asgarosforum->executePlugin) {
			$currentLink = ($this->asgarosforum->current_page > 0) ? $this->asgarosforum->get_link('current') : esc_url(remove_query_arg('part', $this->asgarosforum->get_link('current', false, false, '', false)));
			$currentTitle = ($this->asgarosforum->getMetaTitle()) ? $this->asgarosforum->getMetaTitle() : get_the_title();
			$currentDescription = ($this->asgarosforum->current_description) ? $this->asgarosforum->current_description : $currentTitle;

			// Prevent indexing of some views.
			switch ($this->asgarosforum->current_view) {
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

	public function add_css() {
		$themeurl = $this->get_current_theme_url();

		wp_enqueue_style('af-widgets', $themeurl.'/widgets.css', array(), $this->asgarosforum->version);

		if ($this->asgarosforum->executePlugin) {
			wp_enqueue_style('af-style', $themeurl.'/style.css', array(), $this->asgarosforum->version);

			if ($this->is_default_theme()) {
				if ($this->options != $this->options_default) {
					$custom_styles_url = add_query_arg(
						array(
							'color'				=> substr($this->options['custom_color'], 1),
							'text-color'		=> substr($this->options['custom_text_color'], 1),
							'background-color'	=> substr($this->options['custom_background_color'], 1),
							'border-color'		=> substr($this->options['custom_border_color'], 1),
							'font'				=> $this->options['custom_font'],
							'font-size'			=> $this->options['custom_font_size']
						),
						$themeurl.'/custom-color.php'
					);

					wp_enqueue_style('af-custom-color', $custom_styles_url, array(), $this->asgarosforum->version);
				}
			}
		}
	}

	// Add a custom stylesheet to the TinyMCE editor.
	public function add_editor_css($mce_css) {
		if (!empty($mce_css)) {
			$mce_css .= ',';
		}

		$mce_css .= $this->get_current_theme_url().'/editor.css?ver='.$this->asgarosforum->version;

		return $mce_css;
	}
}
