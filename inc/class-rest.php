<?php
/**
 * REST API endpoints for Speed Dial plugin
 *
 * @package SpeedDial
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class SD_REST
 * Handles REST API endpoints
 */
class SD_REST {

	/**
	 * Initialize REST API
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public static function register_routes() {
		$namespace = 'sd/v1';

		// Lookup endpoint
		register_rest_route( $namespace, '/lookup', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'lookup' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'number' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sd_sanitize_number',
					'validate_callback' => function( $param ) {
						return sd_is_valid_number( $param );
					},
				),
			),
		) );

		// Suggest endpoint (optional feature)
		register_rest_route( $namespace, '/suggest', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'suggest' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'prefix' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sd_sanitize_number',
				),
				'limit' => array(
					'required'          => false,
					'type'              => 'integer',
					'default'           => 5,
					'minimum'           => 1,
					'maximum'           => 10,
				),
			),
		) );

		// Stats endpoint (for admin)
		register_rest_route( $namespace, '/stats', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'stats' ),
			'permission_callback' => function() {
				return sd_can_manage();
			},
		) );
	}

	/**
	 * Lookup a phone number
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public static function lookup( $request ) {
		$number = sd_sanitize_number( $request->get_param( 'number' ) );

		// Check rate limiting (optional)
		if ( ! self::check_rate_limit( $number ) ) {
			return new WP_Error(
				'rate_limit_exceeded',
				__( 'Too many requests. Please try again later.', 'speed-dial' ),
				array( 'status' => 429 )
			);
		}

		// Get entry from database
		$entry = SD_Model::get_by_number( $number );

		if ( ! $entry || ! $entry->is_active ) {
			// Log failed lookup
			sd_log_event( 'lookup_failed', array( 'number' => $number ) );

			return new WP_REST_Response(
				array(
					'found'   => false,
					'number'  => $number,
					'message' => sd_get_option( 'not_found_text' ),
				),
				404
			);
		}

		// Log successful lookup
		sd_log_event( 'lookup_success', array(
			'number' => $number,
			'title'  => $entry->title,
			'url'    => $entry->url,
		) );

		// Fire action hook
		do_action( 'speed_dial_call', $number, $entry );

		// Prepare response
		$response_data = array(
			'found'  => true,
			'number' => $number,
			'title'  => $entry->title,
			'url'    => esc_url( $entry->url ),
			'note'   => $entry->note,
		);

		// Allow filtering of response
		$response_data = apply_filters( 'speed_dial_lookup_response', $response_data, $entry );

		return new WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Suggest numbers based on prefix
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function suggest( $request ) {
		$prefix = sd_sanitize_number( $request->get_param( 'prefix' ) );
		$limit  = $request->get_param( 'limit' );

		if ( empty( $prefix ) ) {
			return new WP_REST_Response( array(), 200 );
		}

		// Get suggestions from database
		$suggestions = SD_Model::search_prefix( $prefix, $limit );

		// Format response
		$response_data = array();
		foreach ( $suggestions as $suggestion ) {
			$response_data[] = array(
				'number' => $suggestion->number,
				'title'  => $suggestion->title,
			);
		}

		return new WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Get statistics
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function stats( $request ) {
		$stats = array(
			'total_entries'   => SD_Model::count(),
			'active_entries'  => SD_Model::count( true ),
			'inactive_entries' => SD_Model::count( false ),
		);

		return new WP_REST_Response( $stats, 200 );
	}

	/**
	 * Check rate limiting
	 *
	 * @param string $identifier Identifier for rate limiting.
	 * @return bool True if within limits.
	 */
	private static function check_rate_limit( $identifier ) {
		// Skip rate limiting if disabled
		if ( ! apply_filters( 'speed_dial_enable_rate_limit', true ) ) {
			return true;
		}

		$ip = self::get_client_ip();
		$key = 'sd_rate_' . md5( $ip . '_' . $identifier );
		$max_requests = apply_filters( 'speed_dial_rate_limit_max', 30 );
		$window = apply_filters( 'speed_dial_rate_limit_window', MINUTE_IN_SECONDS );

		$current = get_transient( $key );

		if ( false === $current ) {
			set_transient( $key, 1, $window );
			return true;
		}

		if ( $current >= $max_requests ) {
			return false;
		}

		set_transient( $key, $current + 1, $window );
		return true;
	}

	/**
	 * Get client IP address
	 *
	 * @return string IP address.
	 */
	private static function get_client_ip() {
		$ip_keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				$ips = explode( ',', $_SERVER[ $key ] );
				foreach ( $ips as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}

	/**
	 * Register AJAX fallbacks for hosts that block REST
	 */
	public static function register_ajax_fallbacks() {
		add_action( 'wp_ajax_sd_lookup', array( __CLASS__, 'ajax_lookup' ) );
		add_action( 'wp_ajax_nopriv_sd_lookup', array( __CLASS__, 'ajax_lookup' ) );

		add_action( 'wp_ajax_sd_suggest', array( __CLASS__, 'ajax_suggest' ) );
		add_action( 'wp_ajax_nopriv_sd_suggest', array( __CLASS__, 'ajax_suggest' ) );
	}

	/**
	 * AJAX lookup handler
	 */
	public static function ajax_lookup() {
		// Verify nonce
		if ( ! check_ajax_referer( 'sd_frontend', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'speed-dial' ) ) );
		}

		$number = isset( $_GET['number'] ) ? sd_sanitize_number( $_GET['number'] ) : '';

		if ( ! sd_is_valid_number( $number ) ) {
			wp_send_json_error( array(
				'found'   => false,
				'message' => __( 'Invalid number format.', 'speed-dial' ),
			) );
		}

		// Create a mock request object
		$request = new WP_REST_Request( 'GET' );
		$request->set_param( 'number', $number );

		// Use the same logic as REST endpoint
		$response = self::lookup( $request );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_data() );
		} else {
			$data = $response->get_data();
			if ( $data['found'] ) {
				wp_send_json_success( $data );
			} else {
				wp_send_json_error( $data );
			}
		}
	}

	/**
	 * AJAX suggest handler
	 */
	public static function ajax_suggest() {
		// Verify nonce
		if ( ! check_ajax_referer( 'sd_frontend', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'speed-dial' ) ) );
		}

		$prefix = isset( $_GET['prefix'] ) ? sd_sanitize_number( $_GET['prefix'] ) : '';
		$limit  = isset( $_GET['limit'] ) ? absint( $_GET['limit'] ) : 5;
		$limit  = min( max( $limit, 1 ), 10 );

		// Create a mock request object
		$request = new WP_REST_Request( 'GET' );
		$request->set_param( 'prefix', $prefix );
		$request->set_param( 'limit', $limit );

		// Use the same logic as REST endpoint
		$response = self::suggest( $request );
		$data = $response->get_data();

		wp_send_json_success( $data );
	}
}

// Register AJAX fallbacks
add_action( 'init', array( 'SD_REST', 'register_ajax_fallbacks' ) );
