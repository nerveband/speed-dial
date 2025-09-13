<?php
/**
 * Admin interface class for Speed Dial plugin
 *
 * @package SpeedDial
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class SD_Admin
 * Handles all admin interface functionality
 */
class SD_Admin {

	/**
	 * Initialize admin interface
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_sd_add_number', array( __CLASS__, 'handle_add_number' ) );
		add_action( 'admin_post_sd_edit_number', array( __CLASS__, 'handle_edit_number' ) );
		add_action( 'admin_post_sd_delete_number', array( __CLASS__, 'handle_delete_number' ) );
		add_action( 'admin_post_sd_bulk_action', array( __CLASS__, 'handle_bulk_action' ) );
		add_action( 'admin_post_sd_import_csv', array( __CLASS__, 'handle_import_csv' ) );
		add_action( 'admin_post_sd_export_csv', array( __CLASS__, 'handle_export_csv' ) );
	}

	/**
	 * Add admin menu
	 */
	public static function add_menu() {
		// Main menu
		add_menu_page(
			__( 'Speed Dial', 'speed-dial' ),
			__( 'Speed Dial', 'speed-dial' ),
			'manage_options',
			'speed-dial',
			array( __CLASS__, 'render_numbers_page' ),
			'dashicons-phone',
			30
		);

		// Submenu items
		add_submenu_page(
			'speed-dial',
			__( 'All Numbers', 'speed-dial' ),
			__( 'All Numbers', 'speed-dial' ),
			'manage_options',
			'speed-dial',
			array( __CLASS__, 'render_numbers_page' )
		);

		add_submenu_page(
			'speed-dial',
			__( 'Add New', 'speed-dial' ),
			__( 'Add New', 'speed-dial' ),
			'manage_options',
			'speed-dial-add',
			array( __CLASS__, 'render_add_page' )
		);

		add_submenu_page(
			'speed-dial',
			__( 'Import/Export', 'speed-dial' ),
			__( 'Import/Export', 'speed-dial' ),
			'manage_options',
			'speed-dial-import-export',
			array( __CLASS__, 'render_import_export_page' )
		);

		add_submenu_page(
			'speed-dial',
			__( 'Settings', 'speed-dial' ),
			__( 'Settings', 'speed-dial' ),
			'manage_options',
			'speed-dial-settings',
			array( __CLASS__, 'render_settings_page' )
		);

		// Hidden edit page
		add_submenu_page(
			null,
			__( 'Edit Number', 'speed-dial' ),
			__( 'Edit Number', 'speed-dial' ),
			'manage_options',
			'speed-dial-edit',
			array( __CLASS__, 'render_edit_page' )
		);
	}

	/**
	 * Render numbers list page
	 */
	public static function render_numbers_page() {
		// Load list table class
		if ( ! class_exists( 'SD_List_Table' ) ) {
			require_once SD_PLUGIN_DIR . 'inc/class-list-table.php';
		}

		$list_table = new SD_List_Table();
		$list_table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Speed Dial Numbers', 'speed-dial' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=speed-dial-add' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'speed-dial' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php self::display_admin_notices(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sd_bulk_action">
				<?php wp_nonce_field( 'sd_bulk_action' ); ?>
				<?php $list_table->search_box( __( 'Search', 'speed-dial' ), 'sd-search' ); ?>
				<?php $list_table->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render add number page
	 */
	public static function render_add_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Add New Number', 'speed-dial' ); ?></h1>

			<?php self::display_admin_notices(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sd_add_number">
				<?php wp_nonce_field( 'sd_add_number' ); ?>

				<?php self::render_number_form(); ?>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Add Number', 'speed-dial' ); ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=speed-dial' ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'speed-dial' ); ?>
					</a>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render edit number page
	 */
	public static function render_edit_page() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$entry = SD_Model::get_by_id( $id );

		if ( ! $entry ) {
			wp_die( __( 'Number not found.', 'speed-dial' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Edit Number', 'speed-dial' ); ?></h1>

			<?php self::display_admin_notices(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sd_edit_number">
				<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
				<?php wp_nonce_field( 'sd_edit_number_' . $id ); ?>

				<?php self::render_number_form( $entry ); ?>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Update Number', 'speed-dial' ); ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=speed-dial' ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'speed-dial' ); ?>
					</a>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render number form fields
	 *
	 * @param object|null $entry Existing entry data.
	 */
	private static function render_number_form( $entry = null ) {
		$number = $entry ? $entry->number : '';
		$title = $entry ? $entry->title : '';
		$url = $entry ? $entry->url : '';
		$note = $entry ? $entry->note : '';
		$is_active = $entry ? $entry->is_active : 1;
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="number"><?php esc_html_e( 'Number', 'speed-dial' ); ?> <span class="required">*</span></label>
				</th>
				<td>
					<input type="text"
					       id="number"
					       name="number"
					       value="<?php echo esc_attr( $number ); ?>"
					       class="regular-text"
					       required
					       pattern="[0-9]+"
					       maxlength="<?php echo esc_attr( sd_get_max_digits() ); ?>">
					<p class="description">
						<?php esc_html_e( 'Enter digits only (0-9). Maximum', 'speed-dial' ); ?>
						<?php echo esc_html( sd_get_max_digits() ); ?>
						<?php esc_html_e( 'digits.', 'speed-dial' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="title"><?php esc_html_e( 'Title', 'speed-dial' ); ?> <span class="required">*</span></label>
				</th>
				<td>
					<input type="text"
					       id="title"
					       name="title"
					       value="<?php echo esc_attr( $title ); ?>"
					       class="regular-text"
					       required>
					<p class="description">
						<?php esc_html_e( 'Display name for this website.', 'speed-dial' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="url"><?php esc_html_e( 'URL', 'speed-dial' ); ?> <span class="required">*</span></label>
				</th>
				<td>
					<input type="url"
					       id="url"
					       name="url"
					       value="<?php echo esc_attr( $url ); ?>"
					       class="large-text"
					       required>
					<p class="description">
						<?php esc_html_e( 'Full website URL. Will be normalized to https:// if no protocol specified.', 'speed-dial' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="note"><?php esc_html_e( 'Note', 'speed-dial' ); ?></label>
				</th>
				<td>
					<textarea id="note"
					          name="note"
					          rows="3"
					          class="large-text"><?php echo esc_textarea( $note ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Optional description or notes about this mapping.', 'speed-dial' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Status', 'speed-dial' ); ?>
				</th>
				<td>
					<label for="is_active">
						<input type="checkbox"
						       id="is_active"
						       name="is_active"
						       value="1"
						       <?php checked( $is_active, 1 ); ?>>
						<?php esc_html_e( 'Active', 'speed-dial' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Inactive numbers will not be accessible via the dialer.', 'speed-dial' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render import/export page
	 */
	public static function render_import_export_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import/Export', 'speed-dial' ); ?></h1>

			<?php self::display_admin_notices(); ?>

			<div class="sd-import-export">
				<div class="card">
					<h2><?php esc_html_e( 'Import from CSV', 'speed-dial' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="sd_import_csv">
						<?php wp_nonce_field( 'sd_import_csv' ); ?>

						<p><?php esc_html_e( 'Upload a CSV file to import number mappings.', 'speed-dial' ); ?></p>

						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="csv_file"><?php esc_html_e( 'CSV File', 'speed-dial' ); ?></label>
								</th>
								<td>
									<input type="file" id="csv_file" name="csv_file" accept=".csv" required>
									<p class="description">
										<?php esc_html_e( 'Format: number,title,url,note,is_active', 'speed-dial' ); ?>
									</p>
								</td>
							</tr>
						</table>

						<p class="submit">
							<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Import CSV', 'speed-dial' ); ?>">
						</p>
					</form>
				</div>

				<div class="card">
					<h2><?php esc_html_e( 'Export to CSV', 'speed-dial' ); ?></h2>
					<p><?php esc_html_e( 'Download all number mappings as a CSV file.', 'speed-dial' ); ?></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sd_export_csv">
						<?php wp_nonce_field( 'sd_export_csv' ); ?>

						<p class="submit">
							<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Export CSV', 'speed-dial' ); ?>">
						</p>
					</form>
				</div>

				<div class="card">
					<h2><?php esc_html_e( 'CSV Format', 'speed-dial' ); ?></h2>
					<p><?php esc_html_e( 'CSV files should use the following format:', 'speed-dial' ); ?></p>
					<pre>number,title,url,note,is_active
411,Directory,https://example.com/directory,Main directory,1
911,Support,https://example.com/support,Get help,1</pre>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render settings page
	 */
	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Speed Dial Settings', 'speed-dial' ); ?></h1>

			<?php self::display_admin_notices(); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'sd_settings' );
				do_settings_sections( 'sd_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register settings
	 */
	public static function register_settings() {
		// Register settings
		register_setting( 'sd_settings', 'sd_connecting_text', 'sanitize_text_field' );
		register_setting( 'sd_settings', 'sd_not_found_text', 'sanitize_text_field' );
		register_setting( 'sd_settings', 'sd_clear_text', 'sanitize_text_field' );
		register_setting( 'sd_settings', 'sd_call_text', 'sanitize_text_field' );
		register_setting( 'sd_settings', 'sd_visit_text', 'sanitize_text_field' );
		register_setting( 'sd_settings', 'sd_auto_redirect', 'absint' );
		register_setting( 'sd_settings', 'sd_redirect_delay_ms', 'absint' );
		register_setting( 'sd_settings', 'sd_sound_enabled', 'absint' );
		register_setting( 'sd_settings', 'sd_vibration_enabled', 'absint' );
		register_setting( 'sd_settings', 'sd_theme', 'sanitize_text_field' );
		register_setting( 'sd_settings', 'sd_keep_data_on_uninstall', 'absint' );

		// Add settings sections
		add_settings_section(
			'sd_display_settings',
			__( 'Display Settings', 'speed-dial' ),
			null,
			'sd_settings'
		);

		add_settings_section(
			'sd_behavior_settings',
			__( 'Behavior Settings', 'speed-dial' ),
			null,
			'sd_settings'
		);

		add_settings_section(
			'sd_advanced_settings',
			__( 'Advanced Settings', 'speed-dial' ),
			null,
			'sd_settings'
		);

		// Display settings fields
		add_settings_field(
			'sd_theme',
			__( 'Theme', 'speed-dial' ),
			array( __CLASS__, 'render_theme_field' ),
			'sd_settings',
			'sd_display_settings'
		);

		add_settings_field(
			'sd_connecting_text',
			__( 'Connecting Text', 'speed-dial' ),
			array( __CLASS__, 'render_text_field' ),
			'sd_settings',
			'sd_display_settings',
			array( 'field' => 'sd_connecting_text', 'default' => __( 'Connecting you to the site...', 'speed-dial' ) )
		);

		add_settings_field(
			'sd_not_found_text',
			__( 'Not Found Text', 'speed-dial' ),
			array( __CLASS__, 'render_text_field' ),
			'sd_settings',
			'sd_display_settings',
			array( 'field' => 'sd_not_found_text', 'default' => __( 'Number not assigned', 'speed-dial' ) )
		);

		// Behavior settings fields
		add_settings_field(
			'sd_auto_redirect',
			__( 'Auto Redirect', 'speed-dial' ),
			array( __CLASS__, 'render_checkbox_field' ),
			'sd_settings',
			'sd_behavior_settings',
			array( 'field' => 'sd_auto_redirect', 'label' => __( 'Automatically redirect to the target URL', 'speed-dial' ) )
		);

		add_settings_field(
			'sd_redirect_delay_ms',
			__( 'Redirect Delay', 'speed-dial' ),
			array( __CLASS__, 'render_number_field' ),
			'sd_settings',
			'sd_behavior_settings',
			array( 'field' => 'sd_redirect_delay_ms', 'default' => 1200, 'min' => 0, 'max' => 10000, 'step' => 100 )
		);

		add_settings_field(
			'sd_sound_enabled',
			__( 'Sound Effects', 'speed-dial' ),
			array( __CLASS__, 'render_checkbox_field' ),
			'sd_settings',
			'sd_behavior_settings',
			array( 'field' => 'sd_sound_enabled', 'label' => __( 'Enable DTMF sound effects', 'speed-dial' ) )
		);

		add_settings_field(
			'sd_vibration_enabled',
			__( 'Vibration', 'speed-dial' ),
			array( __CLASS__, 'render_checkbox_field' ),
			'sd_settings',
			'sd_behavior_settings',
			array( 'field' => 'sd_vibration_enabled', 'label' => __( 'Enable haptic feedback (mobile only)', 'speed-dial' ) )
		);

		// Advanced settings
		add_settings_field(
			'sd_keep_data_on_uninstall',
			__( 'Data Retention', 'speed-dial' ),
			array( __CLASS__, 'render_checkbox_field' ),
			'sd_settings',
			'sd_advanced_settings',
			array( 'field' => 'sd_keep_data_on_uninstall', 'label' => __( 'Keep data when plugin is uninstalled', 'speed-dial' ) )
		);
	}

	/**
	 * Render theme field
	 */
	public static function render_theme_field() {
		$value = get_option( 'sd_theme', 'nokia-3310' );
		$themes = sd_get_themes();
		?>
		<select name="sd_theme" id="sd_theme">
			<?php foreach ( $themes as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render text field
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_text_field( $args ) {
		$field = $args['field'];
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$value = get_option( $field, $default );
		?>
		<input type="text"
		       name="<?php echo esc_attr( $field ); ?>"
		       id="<?php echo esc_attr( $field ); ?>"
		       value="<?php echo esc_attr( $value ); ?>"
		       class="regular-text">
		<?php
	}

	/**
	 * Render checkbox field
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_checkbox_field( $args ) {
		$field = $args['field'];
		$label = $args['label'];
		$value = get_option( $field, false );
		?>
		<label for="<?php echo esc_attr( $field ); ?>">
			<input type="checkbox"
			       name="<?php echo esc_attr( $field ); ?>"
			       id="<?php echo esc_attr( $field ); ?>"
			       value="1"
			       <?php checked( $value, 1 ); ?>>
			<?php echo esc_html( $label ); ?>
		</label>
		<?php
	}

	/**
	 * Render number field
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_number_field( $args ) {
		$field = $args['field'];
		$default = isset( $args['default'] ) ? $args['default'] : 0;
		$value = get_option( $field, $default );
		$min = isset( $args['min'] ) ? $args['min'] : 0;
		$max = isset( $args['max'] ) ? $args['max'] : '';
		$step = isset( $args['step'] ) ? $args['step'] : 1;
		?>
		<input type="number"
		       name="<?php echo esc_attr( $field ); ?>"
		       id="<?php echo esc_attr( $field ); ?>"
		       value="<?php echo esc_attr( $value ); ?>"
		       min="<?php echo esc_attr( $min ); ?>"
		       max="<?php echo esc_attr( $max ); ?>"
		       step="<?php echo esc_attr( $step ); ?>"
		       class="small-text">
		<span class="description"><?php esc_html_e( 'milliseconds', 'speed-dial' ); ?></span>
		<?php
	}

	/**
	 * Handle add number form submission
	 */
	public static function handle_add_number() {
		// Check nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'sd_add_number' ) ) {
			wp_die( __( 'Security check failed.', 'speed-dial' ) );
		}

		// Check permissions
		if ( ! sd_can_manage() ) {
			wp_die( __( 'You do not have permission to perform this action.', 'speed-dial' ) );
		}

		// Get form data
		$data = array(
			'number'    => sd_sanitize_number( $_POST['number'] ),
			'title'     => sanitize_text_field( $_POST['title'] ),
			'url'       => sd_normalize_url( $_POST['url'] ),
			'note'      => sanitize_textarea_field( $_POST['note'] ),
			'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
		);

		// Validate
		if ( empty( $data['number'] ) || empty( $data['title'] ) || empty( $data['url'] ) ) {
			wp_redirect( add_query_arg( 'message', 'missing_fields', wp_get_referer() ) );
			exit;
		}

		// Check if number exists
		if ( SD_Model::number_exists( $data['number'] ) ) {
			wp_redirect( add_query_arg( 'message', 'number_exists', wp_get_referer() ) );
			exit;
		}

		// Add to database
		$result = SD_Model::create( $data );

		if ( $result ) {
			wp_redirect( add_query_arg( 'message', 'added', admin_url( 'admin.php?page=speed-dial' ) ) );
		} else {
			wp_redirect( add_query_arg( 'message', 'error', wp_get_referer() ) );
		}
		exit;
	}

	/**
	 * Handle edit number form submission
	 */
	public static function handle_edit_number() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		// Check nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'sd_edit_number_' . $id ) ) {
			wp_die( __( 'Security check failed.', 'speed-dial' ) );
		}

		// Check permissions
		if ( ! sd_can_manage() ) {
			wp_die( __( 'You do not have permission to perform this action.', 'speed-dial' ) );
		}

		// Get form data
		$data = array(
			'number'    => sd_sanitize_number( $_POST['number'] ),
			'title'     => sanitize_text_field( $_POST['title'] ),
			'url'       => sd_normalize_url( $_POST['url'] ),
			'note'      => sanitize_textarea_field( $_POST['note'] ),
			'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
		);

		// Validate
		if ( empty( $data['number'] ) || empty( $data['title'] ) || empty( $data['url'] ) ) {
			wp_redirect( add_query_arg( 'message', 'missing_fields', wp_get_referer() ) );
			exit;
		}

		// Check if number exists (excluding current)
		if ( SD_Model::number_exists( $data['number'], $id ) ) {
			wp_redirect( add_query_arg( 'message', 'number_exists', wp_get_referer() ) );
			exit;
		}

		// Update in database
		$result = SD_Model::update( $id, $data );

		if ( $result ) {
			wp_redirect( add_query_arg( 'message', 'updated', admin_url( 'admin.php?page=speed-dial' ) ) );
		} else {
			wp_redirect( add_query_arg( 'message', 'error', wp_get_referer() ) );
		}
		exit;
	}

	/**
	 * Handle delete number
	 */
	public static function handle_delete_number() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		// Check nonce
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'sd_delete_' . $id ) ) {
			wp_die( __( 'Security check failed.', 'speed-dial' ) );
		}

		// Check permissions
		if ( ! sd_can_manage() ) {
			wp_die( __( 'You do not have permission to perform this action.', 'speed-dial' ) );
		}

		// Delete from database
		$result = SD_Model::delete( $id );

		if ( $result ) {
			wp_redirect( add_query_arg( 'message', 'deleted', admin_url( 'admin.php?page=speed-dial' ) ) );
		} else {
			wp_redirect( add_query_arg( 'message', 'error', admin_url( 'admin.php?page=speed-dial' ) ) );
		}
		exit;
	}

	/**
	 * Handle bulk actions
	 */
	public static function handle_bulk_action() {
		// Check nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'sd_bulk_action' ) ) {
			wp_die( __( 'Security check failed.', 'speed-dial' ) );
		}

		// Check permissions
		if ( ! sd_can_manage() ) {
			wp_die( __( 'You do not have permission to perform this action.', 'speed-dial' ) );
		}

		$action = isset( $_POST['action'] ) ? $_POST['action'] : '';
		$ids = isset( $_POST['numbers'] ) ? array_map( 'absint', $_POST['numbers'] ) : array();

		if ( empty( $ids ) ) {
			wp_redirect( add_query_arg( 'message', 'no_selection', wp_get_referer() ) );
			exit;
		}

		$count = 0;

		switch ( $action ) {
			case 'delete':
				foreach ( $ids as $id ) {
					if ( SD_Model::delete( $id ) ) {
						$count++;
					}
				}
				$message = 'bulk_deleted';
				break;

			case 'activate':
				foreach ( $ids as $id ) {
					if ( SD_Model::toggle( $id, true ) ) {
						$count++;
					}
				}
				$message = 'bulk_activated';
				break;

			case 'deactivate':
				foreach ( $ids as $id ) {
					if ( SD_Model::toggle( $id, false ) ) {
						$count++;
					}
				}
				$message = 'bulk_deactivated';
				break;

			default:
				$message = 'invalid_action';
		}

		wp_redirect( add_query_arg( array( 'message' => $message, 'count' => $count ), admin_url( 'admin.php?page=speed-dial' ) ) );
		exit;
	}

	/**
	 * Handle CSV import
	 */
	public static function handle_import_csv() {
		// Check nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'sd_import_csv' ) ) {
			wp_die( __( 'Security check failed.', 'speed-dial' ) );
		}

		// Check permissions
		if ( ! sd_can_manage() ) {
			wp_die( __( 'You do not have permission to perform this action.', 'speed-dial' ) );
		}

		// Check file upload
		if ( ! isset( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_redirect( add_query_arg( 'message', 'upload_error', wp_get_referer() ) );
			exit;
		}

		// Read CSV file
		$file = $_FILES['csv_file']['tmp_name'];
		$rows = array();

		if ( ( $handle = fopen( $file, 'r' ) ) !== false ) {
			// Skip header row
			$header = fgetcsv( $handle );

			while ( ( $data = fgetcsv( $handle ) ) !== false ) {
				$row = sd_parse_csv_row( $data );
				if ( ! is_wp_error( $row ) ) {
					$rows[] = $row;
				}
			}
			fclose( $handle );
		}

		if ( empty( $rows ) ) {
			wp_redirect( add_query_arg( 'message', 'import_empty', wp_get_referer() ) );
			exit;
		}

		// Import rows
		$results = SD_Model::bulk_import( $rows );

		wp_redirect( add_query_arg(
			array(
				'message' => 'imported',
				'success' => $results['success'],
				'errors' => $results['errors'],
			),
			wp_get_referer()
		) );
		exit;
	}

	/**
	 * Handle CSV export
	 */
	public static function handle_export_csv() {
		// Check nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'sd_export_csv' ) ) {
			wp_die( __( 'Security check failed.', 'speed-dial' ) );
		}

		// Check permissions
		if ( ! sd_can_manage() ) {
			wp_die( __( 'You do not have permission to perform this action.', 'speed-dial' ) );
		}

		// Get all entries
		$entries = SD_Model::export_all();

		// Generate filename
		$filename = sd_generate_filename( 'speed-dial-export' );

		// Set headers for download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Open output stream
		$output = fopen( 'php://output', 'w' );

		// Add BOM for Excel UTF-8 compatibility
		fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );

		// Write headers
		fputcsv( $output, sd_get_csv_headers() );

		// Write data
		foreach ( $entries as $entry ) {
			fputcsv( $output, $entry );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Display admin notices
	 */
	private static function display_admin_notices() {
		if ( ! isset( $_GET['message'] ) ) {
			return;
		}

		$message = $_GET['message'];
		$count = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0;
		$success = isset( $_GET['success'] ) ? absint( $_GET['success'] ) : 0;
		$errors = isset( $_GET['errors'] ) ? absint( $_GET['errors'] ) : 0;

		$messages = array(
			'added' => array( 'success', __( 'Number added successfully.', 'speed-dial' ) ),
			'updated' => array( 'success', __( 'Number updated successfully.', 'speed-dial' ) ),
			'deleted' => array( 'success', __( 'Number deleted successfully.', 'speed-dial' ) ),
			'bulk_deleted' => array( 'success', sprintf( __( '%d numbers deleted.', 'speed-dial' ), $count ) ),
			'bulk_activated' => array( 'success', sprintf( __( '%d numbers activated.', 'speed-dial' ), $count ) ),
			'bulk_deactivated' => array( 'success', sprintf( __( '%d numbers deactivated.', 'speed-dial' ), $count ) ),
			'imported' => array( 'success', sprintf( __( 'Import complete. %d successful, %d errors.', 'speed-dial' ), $success, $errors ) ),
			'missing_fields' => array( 'error', __( 'Please fill in all required fields.', 'speed-dial' ) ),
			'number_exists' => array( 'error', __( 'This number already exists.', 'speed-dial' ) ),
			'error' => array( 'error', __( 'An error occurred. Please try again.', 'speed-dial' ) ),
			'no_selection' => array( 'warning', __( 'No items selected.', 'speed-dial' ) ),
			'invalid_action' => array( 'error', __( 'Invalid action.', 'speed-dial' ) ),
			'upload_error' => array( 'error', __( 'File upload failed.', 'speed-dial' ) ),
			'import_empty' => array( 'warning', __( 'No valid data found in CSV file.', 'speed-dial' ) ),
		);

		if ( isset( $messages[ $message ] ) ) {
			list( $type, $text ) = $messages[ $message ];
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $type ),
				esc_html( $text )
			);
		}
	}
}
