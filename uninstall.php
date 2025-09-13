<?php
/**
 * Uninstall Speed Dial Plugin
 *
 * Deletes all plugin data when uninstalled.
 *
 * @package SpeedDial
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if we should keep data
$keep_data = get_option( 'sd_keep_data_on_uninstall', false );

if ( ! $keep_data ) {
	global $wpdb;

	// Drop custom table
	$table_name = $wpdb->prefix . 'speed_dial_map';
	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

	// Delete all plugin options
	$options = array(
		'sd_connecting_text',
		'sd_auto_redirect',
		'sd_redirect_delay_ms',
		'sd_sound_enabled',
		'sd_vibration_enabled',
		'sd_theme',
		'sd_keep_data_on_uninstall',
		'sd_not_found_text',
		'sd_clear_text',
		'sd_call_text',
		'sd_visit_text',
		'sd_db_version',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Delete transients
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sd_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_sd_%'" );

	// Clear any cached data
	wp_cache_flush();

} else {
	// Just remove the uninstall option so it defaults to false on next install
	delete_option( 'sd_keep_data_on_uninstall' );
}

// Remove capabilities if they were added
$role = get_role( 'administrator' );
if ( $role ) {
	$role->remove_cap( 'manage_speed_dial' );
}
