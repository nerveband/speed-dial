<?php
/**
 * Helper functions for Speed Dial plugin
 *
 * @package SpeedDial
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Sanitize a phone number to digits only
 *
 * @param string $number The phone number to sanitize.
 * @return string Digits only.
 */
function sd_sanitize_number( $number ) {
	return preg_replace( '/\D/', '', (string) $number );
}

/**
 * Normalize URL with https:// if no scheme present
 *
 * @param string $url The URL to normalize.
 * @return string Normalized URL.
 */
function sd_normalize_url( $url ) {
	$url = trim( $url );

	if ( empty( $url ) ) {
		return '';
	}

	// If no scheme, add https://
	if ( ! preg_match( '/^https?:\/\//i', $url ) ) {
		$url = 'https://' . $url;
	}

	return esc_url_raw( $url );
}

/**
 * Get the maximum allowed digits
 *
 * @return int Maximum digits allowed.
 */
function sd_get_max_digits() {
	return (int) apply_filters( 'speed_dial_max_digits', 16 );
}

/**
 * Check if a number is valid
 *
 * @param string $number The number to validate.
 * @return bool True if valid.
 */
function sd_is_valid_number( $number ) {
	$number = sd_sanitize_number( $number );
	$length = strlen( $number );

	return $length > 0 && $length <= sd_get_max_digits();
}

/**
 * Get plugin option with default fallback
 *
 * @param string $option Option name (without sd_ prefix).
 * @param mixed  $default Default value.
 * @return mixed Option value.
 */
function sd_get_option( $option, $default = null ) {
	$defaults = array(
		'connecting_text'        => __( 'Connecting you to the site...', 'speed-dial' ),
		'auto_redirect'          => false,
		'redirect_delay_ms'      => 1200,
		'sound_enabled'          => true,
		'vibration_enabled'      => false,
		'theme'                  => 'nokia-3310',
		'keep_data_on_uninstall' => false,
		'not_found_text'         => __( 'Number not assigned', 'speed-dial' ),
		'clear_text'             => __( 'Clear', 'speed-dial' ),
		'call_text'              => __( 'Call', 'speed-dial' ),
		'visit_text'             => __( 'Visit', 'speed-dial' ),
	);

	if ( $default === null && isset( $defaults[ $option ] ) ) {
		$default = $defaults[ $option ];
	}

	return get_option( 'sd_' . $option, $default );
}

/**
 * Check if user can manage Speed Dial
 *
 * @param int $user_id User ID (optional).
 * @return bool True if user can manage.
 */
function sd_can_manage( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	// Allow filtering of capability
	$capability = apply_filters( 'speed_dial_manage_capability', 'manage_options' );

	return user_can( $user_id, $capability );
}

/**
 * Format a number for display
 *
 * @param string $number The number to format.
 * @param string $format Format type: 'display', 'tel'.
 * @return string Formatted number.
 */
function sd_format_number( $number, $format = 'display' ) {
	$number = sd_sanitize_number( $number );

	if ( $format === 'tel' ) {
		// Format for tel: links
		return $number;
	}

	// Allow custom formatting via filter
	return apply_filters( 'speed_dial_format_number', $number, $format );
}

/**
 * Get available themes
 *
 * @return array Theme options.
 */
function sd_get_themes() {
	$themes = array(
		'nokia-3310' => __( 'Nokia 3310 Classic', 'speed-dial' ),
		'minimal'    => __( 'Minimal', 'speed-dial' ),
	);

	return apply_filters( 'speed_dial_themes', $themes );
}

/**
 * Log a Speed Dial event
 *
 * @param string $event Event name.
 * @param array  $data Event data.
 */
function sd_log_event( $event, $data = array() ) {
	// Allow hooking into events
	do_action( 'speed_dial_event', $event, $data );

	// Optionally log to database or file
	if ( apply_filters( 'speed_dial_enable_logging', false ) ) {
		// Implement logging if needed
		do_action( 'speed_dial_log', $event, $data );
	}
}

/**
 * Get CSV headers for import/export
 *
 * @return array CSV headers.
 */
function sd_get_csv_headers() {
	return array( 'number', 'title', 'url', 'note', 'is_active' );
}

/**
 * Parse CSV row
 *
 * @param array $row CSV row data.
 * @return array|WP_Error Parsed data or error.
 */
function sd_parse_csv_row( $row ) {
	$headers = sd_get_csv_headers();
	$data = array();

	// Map CSV columns to data array
	foreach ( $headers as $index => $header ) {
		if ( isset( $row[ $index ] ) ) {
			$data[ $header ] = $row[ $index ];
		}
	}

	// Validate required fields
	if ( empty( $data['number'] ) || empty( $data['title'] ) || empty( $data['url'] ) ) {
		return new WP_Error( 'missing_fields', __( 'Required fields missing', 'speed-dial' ) );
	}

	// Sanitize data
	$data['number'] = sd_sanitize_number( $data['number'] );
	$data['title'] = sanitize_text_field( $data['title'] );
	$data['url'] = sd_normalize_url( $data['url'] );
	$data['note'] = isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : '';
	$data['is_active'] = isset( $data['is_active'] ) ? (int) $data['is_active'] : 1;

	// Validate number
	if ( ! sd_is_valid_number( $data['number'] ) ) {
		return new WP_Error( 'invalid_number', __( 'Invalid number format', 'speed-dial' ) );
	}

	// Validate URL
	if ( ! wp_http_validate_url( $data['url'] ) ) {
		return new WP_Error( 'invalid_url', __( 'Invalid URL', 'speed-dial' ) );
	}

	return $data;
}

/**
 * Generate a unique filename for exports
 *
 * @param string $prefix File prefix.
 * @param string $extension File extension.
 * @return string Filename.
 */
function sd_generate_filename( $prefix = 'speed-dial', $extension = 'csv' ) {
	$date = wp_date( 'Y-m-d-His' );
	return sanitize_file_name( $prefix . '-' . $date . '.' . $extension );
}
