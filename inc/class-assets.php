<?php
/**
 * Assets management class for Speed Dial plugin
 *
 * @package SpeedDial
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class SD_Assets
 * Handles enqueuing of CSS and JavaScript assets
 */
class SD_Assets {

	/**
	 * Initialize assets
	 */
	public static function init() {
		// Frontend assets
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend' ) );

		// Admin assets
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );

		// Block editor assets
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor' ) );
	}

	/**
	 * Check if we should load frontend assets
	 *
	 * @return bool True if assets should be loaded.
	 */
	private static function should_load_frontend() {
		// Always load if we're in the block editor
		if ( is_admin() ) {
			return false;
		}

		// Check for shortcode
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'speed-dial' ) ) {
			return true;
		}

		// Check for block
		if ( is_a( $post, 'WP_Post' ) && has_block( 'speed-dial/phone', $post ) ) {
			return true;
		}

		// Check for widget
		if ( is_active_widget( false, false, 'speed_dial_widget', true ) ) {
			return true;
		}

		// Allow filtering
		return apply_filters( 'speed_dial_load_frontend_assets', false );
	}

	/**
	 * Enqueue frontend assets
	 */
	public static function enqueue_frontend() {
		// Only load where needed
		if ( ! self::should_load_frontend() ) {
			return;
		}

		self::register_frontend_assets();
		self::enqueue_frontend_styles();
		self::enqueue_frontend_scripts();
	}

	/**
	 * Register frontend assets
	 */
	private static function register_frontend_assets() {
		// Register styles
		wp_register_style(
			'sd-frontend',
			SD_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			self::get_version( 'assets/css/frontend.css' )
		);

		// Register theme styles
		$theme = sd_get_option( 'theme', 'nokia-3310' );
		if ( $theme === 'nokia-3310' ) {
			wp_register_style(
				'sd-theme-nokia',
				SD_PLUGIN_URL . 'assets/css/theme-nokia.css',
				array( 'sd-frontend' ),
				self::get_version( 'assets/css/theme-nokia.css' )
			);
		}

		// Register Google Font for Nokia theme
		if ( $theme === 'nokia-3310' ) {
			wp_register_style(
				'sd-font-vt323',
				'https://fonts.googleapis.com/css2?family=VT323&display=swap',
				array(),
				null
			);
		}

		// Register scripts
		wp_register_script(
			'sd-dtmf',
			SD_PLUGIN_URL . 'assets/js/dtmf.js',
			array(),
			self::get_version( 'assets/js/dtmf.js' ),
			true
		);

		wp_register_script(
			'sd-frontend',
			SD_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'sd-dtmf' ),
			self::get_version( 'assets/js/frontend.js' ),
			true
		);
	}

	/**
	 * Enqueue frontend styles
	 */
	private static function enqueue_frontend_styles() {
		wp_enqueue_style( 'sd-frontend' );

		$theme = sd_get_option( 'theme', 'nokia-3310' );
		if ( $theme === 'nokia-3310' ) {
			wp_enqueue_style( 'sd-font-vt323' );
			wp_enqueue_style( 'sd-theme-nokia' );
		}

		// Add inline styles for custom colors if needed
		$custom_css = self::get_custom_css();
		if ( ! empty( $custom_css ) ) {
			wp_add_inline_style( 'sd-frontend', $custom_css );
		}
	}

	/**
	 * Enqueue frontend scripts
	 */
	private static function enqueue_frontend_scripts() {
		wp_enqueue_script( 'sd-dtmf' );
		wp_enqueue_script( 'sd-frontend' );

		// Localize script
		wp_localize_script( 'sd-frontend', 'SDN', self::get_frontend_data() );
	}

	/**
	 * Get frontend localization data
	 *
	 * @return array Localization data.
	 */
	private static function get_frontend_data() {
		return array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'restUrl'       => esc_url_raw( rest_url( 'sd/v1/' ) ),
			'nonce'         => wp_create_nonce( 'sd_frontend' ),
			'restNonce'     => wp_create_nonce( 'wp_rest' ),
			'connectText'   => sd_get_option( 'connecting_text' ),
			'notFoundText'  => sd_get_option( 'not_found_text' ),
			'clearText'     => sd_get_option( 'clear_text' ),
			'callText'      => sd_get_option( 'call_text' ),
			'visitText'     => sd_get_option( 'visit_text' ),
			'autoRedirect'  => (bool) sd_get_option( 'auto_redirect' ),
			'redirectDelay' => (int) sd_get_option( 'redirect_delay_ms' ),
			'soundEnabled'  => (bool) sd_get_option( 'sound_enabled' ),
			'vibrationEnabled' => (bool) sd_get_option( 'vibration_enabled' ),
			'maxDigits'     => sd_get_max_digits(),
			'theme'         => sd_get_option( 'theme' ),
			'debug'         => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'i18n'          => array(
				'error'        => __( 'An error occurred. Please try again.', 'speed-dial' ),
				'loading'      => __( 'Loading...', 'speed-dial' ),
				'redirecting'  => __( 'Redirecting...', 'speed-dial' ),
			),
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_admin( $hook ) {
		// Only load on our admin pages
		if ( ! self::is_speed_dial_admin_page( $hook ) ) {
			return;
		}

		// Admin styles
		wp_enqueue_style(
			'sd-admin',
			SD_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			self::get_version( 'assets/css/admin.css' )
		);

		// Admin scripts
		wp_enqueue_script(
			'sd-admin',
			SD_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-util' ),
			self::get_version( 'assets/js/admin.js' ),
			true
		);

		// Localize admin script
		wp_localize_script( 'sd-admin', 'SDAdmin', array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'sd_admin' ),
			'i18n'         => array(
				'confirmDelete'     => __( 'Are you sure you want to delete this entry?', 'speed-dial' ),
				'confirmBulkDelete' => __( 'Are you sure you want to delete the selected entries?', 'speed-dial' ),
				'importing'         => __( 'Importing...', 'speed-dial' ),
				'exporting'         => __( 'Exporting...', 'speed-dial' ),
				'success'           => __( 'Operation completed successfully.', 'speed-dial' ),
				'error'             => __( 'An error occurred. Please try again.', 'speed-dial' ),
				'invalidFile'       => __( 'Please select a valid CSV file.', 'speed-dial' ),
				'noSelection'       => __( 'Please select at least one item.', 'speed-dial' ),
			),
			'maxDigits'    => sd_get_max_digits(),
		) );

		// Media uploader if needed
		if ( $hook === 'speed-dial_page_speed-dial-import-export' ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Enqueue block editor assets
	 */
	public static function enqueue_block_editor() {
		// Block editor script
		wp_enqueue_script(
			'sd-block-editor',
			SD_PLUGIN_URL . 'block/edit.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
			self::get_version( 'block/edit.js' ),
			true
		);

		// Block editor styles
		wp_enqueue_style(
			'sd-block-editor',
			SD_PLUGIN_URL . 'block/style.css',
			array( 'wp-edit-blocks' ),
			self::get_version( 'block/style.css' )
		);

		// Also enqueue frontend styles for preview
		self::register_frontend_assets();
		wp_enqueue_style( 'sd-frontend' );

		$theme = sd_get_option( 'theme', 'nokia-3310' );
		if ( $theme === 'nokia-3310' ) {
			wp_enqueue_style( 'sd-font-vt323' );
			wp_enqueue_style( 'sd-theme-nokia' );
		}

		// Localize for block editor
		wp_localize_script( 'sd-block-editor', 'SDBlock', array(
			'previewUrl' => admin_url( 'admin-ajax.php?action=sd_block_preview' ),
			'nonce'      => wp_create_nonce( 'sd_block' ),
		) );
	}

	/**
	 * Check if current page is a Speed Dial admin page
	 *
	 * @param string $hook Current admin page hook.
	 * @return bool True if Speed Dial admin page.
	 */
	private static function is_speed_dial_admin_page( $hook ) {
		$speed_dial_pages = array(
			'toplevel_page_speed-dial',
			'speed-dial_page_speed-dial-add',
			'speed-dial_page_speed-dial-edit',
			'speed-dial_page_speed-dial-import-export',
			'speed-dial_page_speed-dial-settings',
		);

		return in_array( $hook, $speed_dial_pages, true );
	}

	/**
	 * Get version for cache busting
	 *
	 * @param string $file File path relative to plugin directory.
	 * @return string Version string.
	 */
	private static function get_version( $file ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$file_path = SD_PLUGIN_DIR . $file;
			if ( file_exists( $file_path ) ) {
				return (string) filemtime( $file_path );
			}
		}
		return SD_VERSION;
	}

	/**
	 * Get custom CSS for inline styles
	 *
	 * @return string Custom CSS.
	 */
	private static function get_custom_css() {
		$css = '';

		// Add any dynamic CSS based on settings
		$css = apply_filters( 'speed_dial_custom_css', $css );

		return $css;
	}

	/**
	 * Preload critical assets
	 */
	public static function preload_assets() {
		if ( ! self::should_load_frontend() ) {
			return;
		}

		// Preload font for Nokia theme
		$theme = sd_get_option( 'theme', 'nokia-3310' );
		if ( $theme === 'nokia-3310' ) {
			echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
			echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
		}
	}
}
