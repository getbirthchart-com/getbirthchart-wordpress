<?php
/**
 * Script and style registration.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend and editor assets.
 */
class GetBirthChart_Assets {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_public_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_public_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Register public assets. Enqueued only when a calculator is rendered.
	 */
	public function register_public_assets(): void {
		wp_register_style(
			'getbirthchart-frontend',
			GETBIRTHCHART_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			GETBIRTHCHART_VERSION
		);
		wp_register_script(
			'getbirthchart-frontend',
			GETBIRTHCHART_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			GETBIRTHCHART_VERSION,
			true
		);
	}

	/**
	 * Enqueue on singular content that already contains the shortcode or block.
	 */
	public function maybe_enqueue_public_assets(): void {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		if ( has_shortcode( $post->post_content, 'getbirthchart' ) || has_block( 'getbirthchart/calculator', $post ) ) {
			$this->enqueue_public_assets();
		}
	}

	/**
	 * Script localization payload. Never includes the API key.
	 *
	 * @return array<string, mixed>
	 */
	public static function frontend_config(): array {
		return array(
			'restUrl' => esc_url_raw( rest_url( 'getbirthchart/v1/calculate' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'i18n'    => array(
				'calculating'        => __( 'Calculating…', 'getbirthchart' ),
				'calculate'          => __( 'Calculate', 'getbirthchart' ),
				'unable'             => __( 'Unable to calculate right now.', 'getbirthchart' ),
				'checkBirth'         => GetBirthChart_Validator::check_birth_information_message(),
				'risingRequiresTime' => GetBirthChart_Validator::rising_requires_time_message(),
				'sun'                => __( 'Sun', 'getbirthchart' ),
				'moon'               => __( 'Moon', 'getbirthchart' ),
				'rising'             => __( 'Rising', 'getbirthchart' ),
				'planets'            => __( 'Planetary placements', 'getbirthchart' ),
				'unknownTimeNote'    => __( 'Birth time was marked unknown. Rising and houses are omitted rather than guessed.', 'getbirthchart' ),
			),
		);
	}

	/**
	 * Localize and enqueue frontend calculator assets.
	 */
	public function enqueue_public_assets(): void {
		wp_enqueue_style( 'getbirthchart-frontend' );
		wp_enqueue_script( 'getbirthchart-frontend' );
		wp_localize_script(
			'getbirthchart-frontend',
			'getbirthchartFrontend',
			self::frontend_config()
		);
	}

	/**
	 * Gutenberg editor script. No build step.
	 */
	public function enqueue_block_editor_assets(): void {
		wp_enqueue_script(
			'getbirthchart-block',
			GETBIRTHCHART_PLUGIN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			GETBIRTHCHART_VERSION,
			true
		);
		wp_set_script_translations( 'getbirthchart-block', 'getbirthchart' );
	}
}
