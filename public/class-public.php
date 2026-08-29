<?php
/**
 * Public-facing block registration.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenberg block that renders through the same shortcode view.
 */
class GetBirthChart_Public {

	/**
	 * Shared calculator renderer.
	 *
	 * @var GetBirthChart_Shortcodes
	 */
	private GetBirthChart_Shortcodes $shortcodes;

	/**
	 * Store the shared shortcode renderer.
	 *
	 * @param GetBirthChart_Shortcodes $shortcodes Shortcode renderer.
	 */
	public function __construct( GetBirthChart_Shortcodes $shortcodes ) {
		$this->shortcodes = $shortcodes;
	}

	/**
	 * Register the dynamic block.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register GetBirthChart Calculator.
	 */
	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		register_block_type(
			'getbirthchart/calculator',
			array(
				'api_version'     => 3,
				'title'           => __( 'GetBirthChart Calculator', 'getbirthchart' ),
				'description'     => __( 'Embed a GetBirthChart birth chart, Moon sign, Rising sign, or Big Three calculator.', 'getbirthchart' ),
				'category'        => 'widgets',
				'icon'            => 'star-filled',
				'keywords'        => array( 'birth chart', 'moon sign', 'rising', 'astrology' ),
				'textdomain'      => 'getbirthchart',
				'attributes'      => array(
					'type' => array(
						'type'    => 'string',
						'default' => 'birth-chart',
						'enum'    => GetBirthChart_Validator::TYPES,
					),
				),
				'render_callback' => array( $this->shortcodes, 'render_block' ),
			)
		);
	}
}
