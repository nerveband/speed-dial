<?php
/**
 * Plugin Name: Speed Dial
 * Plugin URI: https://github.com/nerveband/speed-dial
 * Description: Nokia-style dialer that connects numbers to websites. Experience the nostalgic Nokia 3310 interface while navigating to your favorite sites.
 * Version: 1.0.0
 * Author: Nerveband
 * Author URI: https://github.com/nerveband
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: speed-dial
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package SpeedDial
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants
define( 'SD_VERSION', '1.0.0' );
define( 'SD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load required files
require_once SD_PLUGIN_DIR . 'inc/helpers.php';
require_once SD_PLUGIN_DIR . 'inc/class-assets.php';
require_once SD_PLUGIN_DIR . 'inc/class-model.php';
require_once SD_PLUGIN_DIR . 'inc/class-admin.php';
require_once SD_PLUGIN_DIR . 'inc/class-list-table.php';
require_once SD_PLUGIN_DIR . 'inc/class-rest.php';
require_once SD_PLUGIN_DIR . 'inc/class-render.php';

// Activation and deactivation hooks
register_activation_hook( __FILE__, array( 'SD_Model', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SD_Model', 'deactivate' ) );

// Initialize plugin
add_action( 'plugins_loaded', 'sd_init_plugin' );

/**
 * Initialize the plugin
 */
function sd_init_plugin() {
	// Load text domain for translations
	load_plugin_textdomain( 'speed-dial', false, dirname( SD_PLUGIN_BASENAME ) . '/languages' );

	// Initialize components
	SD_Assets::init();
	SD_Render::init();
	SD_REST::init();

	// Admin only initialization
	if ( is_admin() ) {
		SD_Admin::init();
	}
}

// Register block on init
add_action( 'init', 'sd_register_block' );

/**
 * Register the Gutenberg block
 */
function sd_register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type( SD_PLUGIN_DIR . 'block/block.json' );
}

// Add settings link on plugin page
add_filter( 'plugin_action_links_' . SD_PLUGIN_BASENAME, 'sd_add_settings_link' );

/**
 * Add settings link to plugins page
 *
 * @param array $links Plugin action links.
 * @return array Modified links.
 */
function sd_add_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=speed-dial' ) . '">' . __( 'Settings', 'speed-dial' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
