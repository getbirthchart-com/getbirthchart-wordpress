<?php
/**
 * Calculator shortcodes and shared frontend markup.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers [getbirthchart] and renders the calculator form.
 */
class GetBirthChart_Shortcodes {

	/**
	 * Frontend assets helper.
	 *
	 * @var GetBirthChart_Assets
	 */
	private GetBirthChart_Assets $assets;

	/**
	 * Store the asset registrar.
	 *
	 * @param GetBirthChart_Assets $assets Asset registrar.
	 */
	public function __construct( GetBirthChart_Assets $assets ) {
		$this->assets = $assets;
	}

	/**
	 * Register the shortcode.
	 */
	public function register(): void {
		add_shortcode( 'getbirthchart', array( $this, 'render' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function render( $atts ): string {
		$settings = GetBirthChart_Settings::get_settings();
		$atts     = shortcode_atts(
			array(
				'type' => $settings['default_type'],
			),
			is_array( $atts ) ? $atts : array(),
			'getbirthchart'
		);
		$type     = GetBirthChart_Validator::sanitize_type( $atts['type'] );
		if ( '' === $type ) {
			return $this->invalid_type_message();
		}
		return $this->render_calculator( $type, $settings['theme'] );
	}

	/**
	 * Gutenberg render callback. Same markup as the shortcode.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function render_block( array $attributes ): string {
		$settings = GetBirthChart_Settings::get_settings();
		$type     = GetBirthChart_Validator::sanitize_type( $attributes['type'] ?? $settings['default_type'] );
		if ( '' === $type ) {
			return $this->invalid_type_message();
		}
		return $this->render_calculator( $type, $settings['theme'] );
	}

	/**
	 * Human-readable type label.
	 *
	 * @param string $type Calculator type.
	 */
	public static function type_label( string $type ): string {
		$labels = array(
			'birth-chart' => __( 'Birth Chart', 'getbirthchart' ),
			'moon-sign'   => __( 'Moon Sign', 'getbirthchart' ),
			'rising-sign' => __( 'Rising Sign', 'getbirthchart' ),
			'big-three'   => __( 'Big Three', 'getbirthchart' ),
		);
		return $labels[ $type ] ?? __( 'Birth Chart', 'getbirthchart' );
	}

	/**
	 * Render the calculator form.
	 *
	 * @param string $type  Allowlisted type.
	 * @param string $theme inherit|light.
	 */
	public function render_calculator( string $type, string $theme ): string {
		$this->assets->enqueue_public_assets();
		$theme         = GetBirthChart_Validator::sanitize_theme( $theme );
		$uid           = wp_unique_id( 'gbc-' );
		$requires_time = ( 'rising-sign' === $type );
		ob_start();
		$calculator_type          = $type;
		$calculator_theme         = $theme;
		$calculator_uid           = $uid;
		$calculator_requires_time = $requires_time;
		include GETBIRTHCHART_PLUGIN_DIR . 'public/views/calculator.php';
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Safe message for unknown shortcode/block types.
	 */
	private function invalid_type_message(): string {
		if ( GetBirthChart_Settings::current_user_can_manage() ) {
			return '<p class="getbirthchart-admin-error">' . esc_html__( 'Invalid GetBirthChart calculator type. Use birth-chart, moon-sign, rising-sign, or big-three.', 'getbirthchart' ) . '</p>';
		}
		return '<p class="getbirthchart-error">' . esc_html__( 'This calculator is unavailable.', 'getbirthchart' ) . '</p>';
	}
}
