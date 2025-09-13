<?php
/**
 * Database model class for Speed Dial plugin
 *
 * @package SpeedDial
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class SD_Model
 * Handles all database operations for Speed Dial mappings
 */
class SD_Model {

	/**
	 * Cache group name
	 */
	const CACHE_GROUP = 'speed_dial';

	/**
	 * Get table name
	 *
	 * @return string Full table name with prefix.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'speed_dial_map';
	}

	/**
	 * Activate plugin - create table and set defaults
	 */
	public static function activate() {
		self::create_table();
		self::set_default_options();
		self::seed_sample_data();

		// Clear any existing cache
		wp_cache_flush();
	}

	/**
	 * Deactivate plugin
	 */
	public static function deactivate() {
		// Clear cache
		wp_cache_flush();
	}

	/**
	 * Create database table
	 */
	private static function create_table() {
		global $wpdb;

		$table_name = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			number VARCHAR(16) NOT NULL,
			title VARCHAR(255) NOT NULL,
			url TEXT NOT NULL,
			note TEXT NULL,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY number (number),
			KEY is_active (is_active),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Set default options
	 */
	private static function set_default_options() {
		add_option( 'sd_connecting_text', __( 'Connecting you to the site...', 'speed-dial' ) );
		add_option( 'sd_auto_redirect', false );
		add_option( 'sd_redirect_delay_ms', 1200 );
		add_option( 'sd_sound_enabled', true );
		add_option( 'sd_vibration_enabled', false );
		add_option( 'sd_theme', 'nokia-3310' );
		add_option( 'sd_keep_data_on_uninstall', false );
		add_option( 'sd_not_found_text', __( 'Number not assigned', 'speed-dial' ) );
		add_option( 'sd_db_version', SD_VERSION );
	}

	/**
	 * Seed sample data (optional)
	 */
	private static function seed_sample_data() {
		// Only seed if table is empty
		if ( self::count() > 0 ) {
			return;
		}

		$samples = array(
			array(
				'number' => '411',
				'title'  => __( 'WordPress.org', 'speed-dial' ),
				'url'    => 'https://wordpress.org/',
				'note'   => __( 'WordPress home page', 'speed-dial' ),
			),
			array(
				'number' => '911',
				'title'  => __( 'WordPress Support', 'speed-dial' ),
				'url'    => 'https://wordpress.org/support/',
				'note'   => __( 'Get help with WordPress', 'speed-dial' ),
			),
		);

		foreach ( $samples as $sample ) {
			self::create( $sample );
		}
	}

	/**
	 * Get entry by number
	 *
	 * @param string $number Phone number.
	 * @return object|null Database row or null.
	 */
	public static function get_by_number( $number ) {
		global $wpdb;

		$number = sd_sanitize_number( $number );
		if ( empty( $number ) ) {
			return null;
		}

		// Try cache first
		$cache_key = 'number_' . $number;
		$cached = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		$table = self::table();
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE number = %s AND is_active = 1 LIMIT 1",
				$number
			)
		);

		// Cache the result
		if ( $result ) {
			wp_cache_set( $cache_key, $result, self::CACHE_GROUP, HOUR_IN_SECONDS );
		}

		return $result;
	}

	/**
	 * Get entry by ID
	 *
	 * @param int $id Entry ID.
	 * @return object|null Database row or null.
	 */
	public static function get_by_id( $id ) {
		global $wpdb;

		$table = self::table();
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d LIMIT 1",
				$id
			)
		);
	}

	/**
	 * Search by prefix
	 *
	 * @param string $prefix Number prefix.
	 * @param int    $limit  Maximum results.
	 * @return array Array of matching entries.
	 */
	public static function search_prefix( $prefix, $limit = 5 ) {
		global $wpdb;

		$prefix = sd_sanitize_number( $prefix );
		if ( empty( $prefix ) ) {
			return array();
		}

		$table = self::table();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT number, title FROM $table
				WHERE number LIKE %s AND is_active = 1
				ORDER BY number ASC
				LIMIT %d",
				$prefix . '%',
				$limit
			)
		);
	}

	/**
	 * Create new entry
	 *
	 * @param array $data Entry data.
	 * @return int|false Insert ID or false on error.
	 */
	public static function create( $data ) {
		global $wpdb;

		// Sanitize data
		$data['number'] = sd_sanitize_number( $data['number'] );
		$data['title'] = sanitize_text_field( $data['title'] );
		$data['url'] = sd_normalize_url( $data['url'] );
		$data['note'] = isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : '';
		$data['is_active'] = isset( $data['is_active'] ) ? (int) $data['is_active'] : 1;

		// Validate
		if ( ! sd_is_valid_number( $data['number'] ) ) {
			return false;
		}

		if ( empty( $data['title'] ) || empty( $data['url'] ) ) {
			return false;
		}

		$table = self::table();
		$result = $wpdb->insert(
			$table,
			array(
				'number'     => $data['number'],
				'title'      => $data['title'],
				'url'        => $data['url'],
				'note'       => $data['note'],
				'is_active'  => $data['is_active'],
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( $result ) {
			// Clear cache
			self::clear_cache( $data['number'] );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update entry
	 *
	 * @param int   $id   Entry ID.
	 * @param array $data Update data.
	 * @return bool Success.
	 */
	public static function update( $id, $data ) {
		global $wpdb;

		$update_data = array();
		$update_format = array();

		// Build update array
		if ( isset( $data['number'] ) ) {
			$update_data['number'] = sd_sanitize_number( $data['number'] );
			$update_format[] = '%s';
		}

		if ( isset( $data['title'] ) ) {
			$update_data['title'] = sanitize_text_field( $data['title'] );
			$update_format[] = '%s';
		}

		if ( isset( $data['url'] ) ) {
			$update_data['url'] = sd_normalize_url( $data['url'] );
			$update_format[] = '%s';
		}

		if ( isset( $data['note'] ) ) {
			$update_data['note'] = sanitize_textarea_field( $data['note'] );
			$update_format[] = '%s';
		}

		if ( isset( $data['is_active'] ) ) {
			$update_data['is_active'] = (int) $data['is_active'];
			$update_format[] = '%d';
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$update_data['updated_at'] = current_time( 'mysql' );
		$update_format[] = '%s';

		$table = self::table();
		$result = $wpdb->update(
			$table,
			$update_data,
			array( 'id' => $id ),
			$update_format,
			array( '%d' )
		);

		if ( false !== $result ) {
			// Clear cache for old and new number
			$entry = self::get_by_id( $id );
			if ( $entry ) {
				self::clear_cache( $entry->number );
				if ( isset( $update_data['number'] ) && $update_data['number'] !== $entry->number ) {
					self::clear_cache( $update_data['number'] );
				}
			}
			return true;
		}

		return false;
	}

	/**
	 * Delete entry
	 *
	 * @param int $id Entry ID.
	 * @return bool Success.
	 */
	public static function delete( $id ) {
		global $wpdb;

		// Get entry for cache clearing
		$entry = self::get_by_id( $id );

		$table = self::table();
		$result = $wpdb->delete(
			$table,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( $result ) {
			// Clear cache
			if ( $entry ) {
				self::clear_cache( $entry->number );
			}
			return true;
		}

		return false;
	}

	/**
	 * Toggle active status
	 *
	 * @param int  $id Entry ID.
	 * @param bool $active Active status.
	 * @return bool Success.
	 */
	public static function toggle( $id, $active = true ) {
		return self::update( $id, array( 'is_active' => $active ? 1 : 0 ) );
	}

	/**
	 * Bulk import entries
	 *
	 * @param array $rows Array of entry data.
	 * @return array Results array with success/error counts.
	 */
	public static function bulk_import( $rows ) {
		$results = array(
			'success' => 0,
			'errors'  => 0,
			'details' => array(),
		);

		foreach ( $rows as $index => $row ) {
			// Check if number exists
			$existing = self::get_by_number( $row['number'] );

			if ( $existing ) {
				// Update existing
				$success = self::update( $existing->id, $row );
				$action = 'updated';
			} else {
				// Create new
				$success = self::create( $row );
				$action = 'created';
			}

			if ( $success ) {
				$results['success']++;
				$results['details'][] = array(
					'row'    => $index + 1,
					'number' => $row['number'],
					'action' => $action,
					'status' => 'success',
				);
			} else {
				$results['errors']++;
				$results['details'][] = array(
					'row'    => $index + 1,
					'number' => $row['number'] ?? '',
					'action' => $action,
					'status' => 'error',
					'message' => __( 'Failed to import', 'speed-dial' ),
				);
			}
		}

		return $results;
	}

	/**
	 * Export all entries
	 *
	 * @return array All entries.
	 */
	public static function export_all() {
		global $wpdb;

		$table = self::table();
		return $wpdb->get_results(
			"SELECT number, title, url, note, is_active
			FROM $table
			ORDER BY number ASC",
			ARRAY_A
		);
	}

	/**
	 * Get all entries with pagination
	 *
	 * @param array $args Query arguments.
	 * @return array Results and total count.
	 */
	public static function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'number'     => 0,
			'offset'     => 0,
			'orderby'    => 'number',
			'order'      => 'ASC',
			'search'     => '',
			'is_active'  => null,
		);

		$args = wp_parse_args( $args, $defaults );
		$table = self::table();

		// Build WHERE clause
		$where = array( '1=1' );
		$prepare_args = array();

		if ( ! empty( $args['search'] ) ) {
			$where[] = '(number LIKE %s OR title LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$prepare_args[] = $search_term;
			$prepare_args[] = $search_term;
		}

		if ( $args['is_active'] !== null ) {
			$where[] = 'is_active = %d';
			$prepare_args[] = (int) $args['is_active'];
		}

		$where_clause = implode( ' AND ', $where );

		// Validate orderby
		$allowed_orderby = array( 'id', 'number', 'title', 'url', 'is_active', 'created_at', 'updated_at' );
		if ( ! in_array( $args['orderby'], $allowed_orderby, true ) ) {
			$args['orderby'] = 'number';
		}

		// Validate order
		$args['order'] = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

		// Get total count
		if ( empty( $prepare_args ) ) {
			$total_query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
			$total = $wpdb->get_var( $total_query );
		} else {
			$total_query = $wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE $where_clause",
				$prepare_args
			);
			$total = $wpdb->get_var( $total_query );
		}

		// Get results
		$query = "SELECT * FROM $table WHERE $where_clause ORDER BY {$args['orderby']} {$args['order']}";

		if ( $args['number'] > 0 ) {
			$query .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['number'], $args['offset'] );
		}

		if ( empty( $prepare_args ) ) {
			$results = $wpdb->get_results( $query );
		} else {
			$results = $wpdb->get_results( $wpdb->prepare( $query, $prepare_args ) );
		}

		return array(
			'items' => $results,
			'total' => (int) $total,
		);
	}

	/**
	 * Count total entries
	 *
	 * @param bool|null $active Filter by active status.
	 * @return int Count.
	 */
	public static function count( $active = null ) {
		global $wpdb;

		$table = self::table();

		if ( $active === null ) {
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE is_active = %d",
				$active ? 1 : 0
			)
		);
	}

	/**
	 * Clear cache for a number
	 *
	 * @param string $number Phone number.
	 */
	private static function clear_cache( $number ) {
		$cache_key = 'number_' . sd_sanitize_number( $number );
		wp_cache_delete( $cache_key, self::CACHE_GROUP );
	}

	/**
	 * Check if number exists
	 *
	 * @param string $number Phone number.
	 * @param int    $exclude_id ID to exclude from check.
	 * @return bool True if exists.
	 */
	public static function number_exists( $number, $exclude_id = 0 ) {
		global $wpdb;

		$number = sd_sanitize_number( $number );
		$table = self::table();

		if ( $exclude_id > 0 ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table WHERE number = %s AND id != %d",
					$number,
					$exclude_id
				)
			);
		} else {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table WHERE number = %s",
					$number
				)
			);
		}

		return $count > 0;
	}
}
