<?php
/**
 * Frontend rendering class for Speed Dial plugin
 *
 * @package SpeedDial
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class SD_Render
 * Handles frontend rendering of the Speed Dial interface
 */
class SD_Render {

	/**
	 * Initialize renderer
	 */
	public static function init() {
		// Register shortcode
		add_shortcode( 'speed-dial', array( __CLASS__, 'render_shortcode' ) );

		// Register widget
		add_action( 'widgets_init', array( __CLASS__, 'register_widget' ) );
	}

	/**
	 * Render shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'digits'     => '',
			'auto_focus' => 'true',
			'theme'      => '',
			'class'      => '',
		), $atts, 'speed-dial' );

		// Enqueue assets
		SD_Assets::enqueue_frontend();

		return self::render_phone( $atts );
	}

	/**
	 * Render the phone interface
	 *
	 * @param array $args Rendering arguments.
	 * @return string HTML output.
	 */
	public static function render_phone( $args = array() ) {
		$defaults = array(
			'digits'     => '',
			'auto_focus' => true,
			'theme'      => sd_get_option( 'theme', 'nokia-3310' ),
			'class'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Sanitize inputs
		$digits = sd_sanitize_number( $args['digits'] );
		$auto_focus = filter_var( $args['auto_focus'], FILTER_VALIDATE_BOOLEAN );
		$theme = sanitize_text_field( $args['theme'] );
		$extra_class = sanitize_html_class( $args['class'] );

		// Build classes
		$classes = array( 'sd-phone', 'sd-theme-' . $theme );
		if ( ! empty( $extra_class ) ) {
			$classes[] = $extra_class;
		}

		// Generate unique ID for multiple instances
		$phone_id = 'sd-phone-' . wp_rand( 1000, 9999 );

		ob_start();
		?>
		<div id="<?php echo esc_attr( $phone_id ); ?>"
		     class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
		     data-digits="<?php echo esc_attr( $digits ); ?>"
		     data-auto-focus="<?php echo esc_attr( $auto_focus ? 'true' : 'false' ); ?>">

			<div class="sd-shell">
				<!-- Speaker grille -->
				<div class="sd-speaker" aria-hidden="true">
					<span class="sd-speaker-hole"></span>
					<span class="sd-speaker-hole"></span>
					<span class="sd-speaker-hole"></span>
					<span class="sd-speaker-hole"></span>
					<span class="sd-speaker-hole"></span>
				</div>

				<!-- Screen -->
				<div class="sd-screen" role="group" aria-label="<?php esc_attr_e( 'Speed Dial display', 'speed-dial' ); ?>">
					<div class="sd-readout" aria-live="polite" aria-atomic="true">
						<span class="sd-cursor">_</span>
					</div>
					<div class="sd-status sr-only" aria-live="polite"></div>

					<!-- Overlay for connecting state -->
					<div class="sd-overlay" style="display: none;">
						<div class="sd-spinner"></div>
						<div class="sd-message"></div>
					</div>

					<!-- Result display -->
					<div class="sd-result" style="display: none;">
						<div class="sd-result-title"></div>
						<a class="sd-result-url" href="#" target="_blank" rel="noopener"></a>
						<button type="button" class="sd-result-visit">
							<?php echo esc_html( sd_get_option( 'visit_text' ) ); ?>
						</button>
					</div>
				</div>

				<!-- Soft keys -->
				<div class="sd-softkeys">
					<button type="button" class="sd-soft sd-clear" aria-label="<?php esc_attr_e( 'Clear', 'speed-dial' ); ?>">
						<?php echo esc_html( sd_get_option( 'clear_text' ) ); ?>
					</button>
					<button type="button" class="sd-soft sd-call" aria-label="<?php esc_attr_e( 'Call', 'speed-dial' ); ?>">
						<?php echo esc_html( sd_get_option( 'call_text' ) ); ?>
					</button>
				</div>

				<!-- Keypad -->
				<div class="sd-keypad" role="group" aria-label="<?php esc_attr_e( 'Dial pad', 'speed-dial' ); ?>">
					<?php echo self::render_keypad(); ?>
				</div>
			</div>

			<!-- Hidden input for mobile keyboards -->
			<input type="tel"
			       class="sd-hidden-input"
			       inputmode="numeric"
			       autocomplete="off"
			       aria-hidden="true"
			       tabindex="-1">
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render keypad buttons
	 *
	 * @return string HTML output.
	 */
	private static function render_keypad() {
		$keys = array(
			array( '1', '', '' ),
			array( '2', 'ABC', '' ),
			array( '3', 'DEF', '' ),
			array( '4', 'GHI', '' ),
			array( '5', 'JKL', '' ),
			array( '6', 'MNO', '' ),
			array( '7', 'PQRS', '' ),
			array( '8', 'TUV', '' ),
			array( '9', 'WXYZ', '' ),
			array( '*', '+', '' ),
			array( '0', ' ', '' ),
			array( '#', '', '' ),
		);

		$output = '';

		foreach ( $keys as $key ) {
			$main = $key[0];
			$sub = $key[1];

			$label = sprintf(
				/* translators: %1$s: main key, %2$s: letters on key */
				__( 'Number %1$s%2$s', 'speed-dial' ),
				$main,
				$sub ? ' ' . $sub : ''
			);

			$output .= sprintf(
				'<button type="button" class="sd-key" data-key="%1$s" aria-label="%2$s">
					<span class="sd-key-main">%1$s</span>
					%3$s
				</button>',
				esc_attr( $main ),
				esc_attr( $label ),
				$sub ? '<span class="sd-key-sub">' . esc_html( $sub ) . '</span>' : ''
			);
		}

		return $output;
	}

	/**
	 * Register widget
	 */
	public static function register_widget() {
		register_widget( 'SD_Widget' );
	}

	/**
	 * Render block (for Gutenberg)
	 *
	 * @param array $attributes Block attributes.
	 * @return string HTML output.
	 */
	public static function render_block( $attributes ) {
		$args = array(
			'digits'     => isset( $attributes['digits'] ) ? $attributes['digits'] : '',
			'auto_focus' => isset( $attributes['auto_focus'] ) ? $attributes['auto_focus'] : true,
		);

		// Enqueue assets
		SD_Assets::enqueue_frontend();

		return self::render_phone( $args );
	}
}

/**
 * Speed Dial Widget Class
 */
class SD_Widget extends WP_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'speed_dial_widget',
			__( 'Speed Dial', 'speed-dial' ),
			array(
				'description' => __( 'Nokia-style phone dialer', 'speed-dial' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Widget frontend output
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$digits = ! empty( $instance['digits'] ) ? $instance['digits'] : '';

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $title ) . $args['after_title'];
		}

		// Enqueue assets
		SD_Assets::enqueue_frontend();

		echo SD_Render::render_phone( array(
			'digits'     => $digits,
			'auto_focus' => false,
		) );

		echo $args['after_widget'];
	}

	/**
	 * Widget backend form
	 *
	 * @param array $instance Widget instance.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$digits = ! empty( $instance['digits'] ) ? $instance['digits'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'speed-dial' ); ?>
			</label>
			<input class="widefat"
			       id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       type="text"
			       value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'digits' ) ); ?>">
				<?php esc_html_e( 'Pre-filled digits:', 'speed-dial' ); ?>
			</label>
			<input class="widefat"
			       id="<?php echo esc_attr( $this->get_field_id( 'digits' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'digits' ) ); ?>"
			       type="text"
			       value="<?php echo esc_attr( $digits ); ?>">
		</p>
		<?php
	}

	/**
	 * Update widget instance
	 *
	 * @param array $new_instance New instance values.
	 * @param array $old_instance Old instance values.
	 * @return array Updated instance.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['digits'] = ! empty( $new_instance['digits'] ) ? sd_sanitize_number( $new_instance['digits'] ) : '';
		return $instance;
	}
}
