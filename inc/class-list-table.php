<?php
/**
 * List table class for Speed Dial admin interface
 *
 * @package SpeedDial
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load WP_List_Table if not already loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class SD_List_Table
 * Extends WP_List_Table for displaying Speed Dial numbers
 */
class SD_List_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'Number', 'speed-dial' ),
			'plural'   => __( 'Numbers', 'speed-dial' ),
			'ajax'     => false,
		) );
	}

	/**
	 * Get columns
	 *
	 * @return array Column information.
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox">',
			'number'     => __( 'Number', 'speed-dial' ),
			'title'      => __( 'Title', 'speed-dial' ),
			'url'        => __( 'URL', 'speed-dial' ),
			'is_active'  => __( 'Status', 'speed-dial' ),
			'updated_at' => __( 'Modified', 'speed-dial' ),
			'id'         => __( 'ID', 'speed-dial' ),
		);
	}

	/**
	 * Get sortable columns
	 *
	 * @return array Sortable columns.
	 */
	public function get_sortable_columns() {
		return array(
			'number'     => array( 'number', false ),
			'title'      => array( 'title', false ),
			'url'        => array( 'url', false ),
			'is_active'  => array( 'is_active', false ),
			'updated_at' => array( 'updated_at', false ),
			'id'         => array( 'id', false ),
		);
	}

	/**
	 * Get bulk actions
	 *
	 * @return array Bulk actions.
	 */
	public function get_bulk_actions() {
		return array(
			'activate'   => __( 'Activate', 'speed-dial' ),
			'deactivate' => __( 'Deactivate', 'speed-dial' ),
			'delete'     => __( 'Delete', 'speed-dial' ),
		);
	}

	/**
	 * Get views
	 *
	 * @return array Views.
	 */
	public function get_views() {
		$views = array();

		// Get counts
		$total_count = SD_Model::count();
		$active_count = SD_Model::count( true );
		$inactive_count = SD_Model::count( false );

		// Current view
		$current = isset( $_GET['status'] ) ? $_GET['status'] : 'all';

		// Build view links
		$views['all'] = sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			esc_url( remove_query_arg( 'status' ) ),
			$current === 'all' ? 'current' : '',
			__( 'All', 'speed-dial' ),
			$total_count
		);

		$views['active'] = sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			esc_url( add_query_arg( 'status', 'active' ) ),
			$current === 'active' ? 'current' : '',
			__( 'Active', 'speed-dial' ),
			$active_count
		);

		$views['inactive'] = sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			esc_url( add_query_arg( 'status', 'inactive' ) ),
			$current === 'inactive' ? 'current' : '',
			__( 'Inactive', 'speed-dial' ),
			$inactive_count
		);

		return $views;
	}

	/**
	 * Prepare items for display
	 */
	public function prepare_items() {
		// Set column headers
		$this->_column_headers = array(
			$this->get_columns(),
			array(), // hidden columns
			$this->get_sortable_columns(),
			'number', // primary column
		);

		// Process bulk actions
		$this->process_bulk_action();

		// Get per page
		$per_page = $this->get_items_per_page( 'sd_numbers_per_page', 20 );

		// Get current page
		$current_page = $this->get_pagenum();

		// Build query args
		$args = array(
			'number' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		// Add search
		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
			$args['search'] = sanitize_text_field( $_REQUEST['s'] );
		}

		// Add status filter
		if ( isset( $_GET['status'] ) ) {
			switch ( $_GET['status'] ) {
				case 'active':
					$args['is_active'] = 1;
					break;
				case 'inactive':
					$args['is_active'] = 0;
					break;
			}
		}

		// Add sorting
		if ( isset( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['orderby'] ) ) {
			$args['orderby'] = sanitize_text_field( $_REQUEST['orderby'] );
		}

		if ( isset( $_REQUEST['order'] ) && ! empty( $_REQUEST['order'] ) ) {
			$args['order'] = sanitize_text_field( $_REQUEST['order'] );
		}

		// Get data
		$data = SD_Model::get_all( $args );

		// Set items
		$this->items = $data['items'];

		// Set pagination
		$this->set_pagination_args( array(
			'total_items' => $data['total'],
			'per_page'    => $per_page,
			'total_pages' => ceil( $data['total'] / $per_page ),
		) );
	}

	/**
	 * Process bulk actions
	 */
	public function process_bulk_action() {
		// Handle individual delete action
		if ( 'delete' === $this->current_action() && isset( $_GET['id'] ) ) {
			// This is handled by the admin class
			return;
		}

		// Bulk actions are handled by form submission
	}

	/**
	 * Render checkbox column
	 *
	 * @param object $item Current item.
	 * @return string Checkbox HTML.
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="numbers[]" value="%d">',
			$item->id
		);
	}

	/**
	 * Render number column
	 *
	 * @param object $item Current item.
	 * @return string Column HTML.
	 */
	public function column_number( $item ) {
		// Build actions
		$actions = array();

		// Edit action
		$actions['edit'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=speed-dial-edit&id=' . $item->id ) ),
			__( 'Edit', 'speed-dial' )
		);

		// Toggle action
		if ( $item->is_active ) {
			$actions['deactivate'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url(
					admin_url( 'admin-post.php?action=sd_toggle&id=' . $item->id . '&status=0' ),
					'sd_toggle_' . $item->id
				) ),
				__( 'Deactivate', 'speed-dial' )
			);
		} else {
			$actions['activate'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url(
					admin_url( 'admin-post.php?action=sd_toggle&id=' . $item->id . '&status=1' ),
					'sd_toggle_' . $item->id
				) ),
				__( 'Activate', 'speed-dial' )
			);
		}

		// Delete action
		$actions['delete'] = sprintf(
			'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
			esc_url( wp_nonce_url(
				admin_url( 'admin-post.php?action=sd_delete_number&id=' . $item->id ),
				'sd_delete_' . $item->id
			) ),
			esc_js( __( 'Are you sure you want to delete this number?', 'speed-dial' ) ),
			__( 'Delete', 'speed-dial' )
		);

		// Build output
		$output = '<strong>' . esc_html( $item->number ) . '</strong>';
		$output .= $this->row_actions( $actions );

		return $output;
	}

	/**
	 * Render title column
	 *
	 * @param object $item Current item.
	 * @return string Column HTML.
	 */
	public function column_title( $item ) {
		$output = esc_html( $item->title );

		if ( ! empty( $item->note ) ) {
			$output .= '<br><small class="description">' . esc_html( wp_trim_words( $item->note, 10 ) ) . '</small>';
		}

		return $output;
	}

	/**
	 * Render URL column
	 *
	 * @param object $item Current item.
	 * @return string Column HTML.
	 */
	public function column_url( $item ) {
		return sprintf(
			'<a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
			esc_url( $item->url ),
			esc_html( wp_trim_words( $item->url, 5, '...' ) )
		);
	}

	/**
	 * Render status column
	 *
	 * @param object $item Current item.
	 * @return string Column HTML.
	 */
	public function column_is_active( $item ) {
		if ( $item->is_active ) {
			return '<span class="sd-status sd-status-active">' . __( 'Active', 'speed-dial' ) . '</span>';
		} else {
			return '<span class="sd-status sd-status-inactive">' . __( 'Inactive', 'speed-dial' ) . '</span>';
		}
	}

	/**
	 * Render date column
	 *
	 * @param object $item Current item.
	 * @return string Column HTML.
	 */
	public function column_updated_at( $item ) {
		$date = mysql2date( get_option( 'date_format' ), $item->updated_at );
		$time = mysql2date( get_option( 'time_format' ), $item->updated_at );

		return sprintf(
			'<abbr title="%1$s %2$s">%3$s</abbr>',
			esc_attr( $date ),
			esc_attr( $time ),
			esc_html( human_time_diff( strtotime( $item->updated_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'speed-dial' ) )
		);
	}

	/**
	 * Render ID column
	 *
	 * @param object $item Current item.
	 * @return string Column HTML.
	 */
	public function column_id( $item ) {
		return '<code>' . esc_html( $item->id ) . '</code>';
	}

	/**
	 * Default column renderer
	 *
	 * @param object $item Current item.
	 * @param string $column_name Column name.
	 * @return string Column HTML.
	 */
	public function column_default( $item, $column_name ) {
		return esc_html( $item->$column_name );
	}

	/**
	 * Display when no items found
	 */
	public function no_items() {
		esc_html_e( 'No numbers found.', 'speed-dial' );
	}

	/**
	 * Extra controls before/after the table
	 *
	 * @param string $which Top or bottom.
	 */
	public function extra_tablenav( $which ) {
		if ( $which === 'top' ) {
			?>
			<div class="alignleft actions">
				<?php
				// Add any extra filters here if needed
				?>
			</div>
			<?php
		}
	}
}
