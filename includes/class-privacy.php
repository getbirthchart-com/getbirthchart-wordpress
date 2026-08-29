<?php
/**
 * Privacy-policy suggestion for WordPress privacy tools.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discloses that birth data is sent to GetBirthChart.
 */
class GetBirthChart_Privacy {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_privacy_policy' ) );
	}

	/**
	 * Suggested privacy-policy text. Does not claim retention details.
	 */
	public function register_privacy_policy(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$content = sprintf(
			'<p>%s</p><p>%s</p><p>%s</p>',
			esc_html__(
				'This site uses the GetBirthChart API to calculate astrology results. Information entered into a GetBirthChart calculator, such as birth date, birth time, and birth place, is sent to GetBirthChart for processing.',
				'getbirthchart'
			),
			esc_html__(
				'The site owner’s GetBirthChart API key is used on the server. Calculator visitors do not receive that key. This plugin does not save visitor birth information in the WordPress database.',
				'getbirthchart'
			),
			sprintf(
				/* translators: 1: privacy policy URL, 2: methodology URL */
				esc_html__( 'GetBirthChart’s own privacy practices are described at %1$s. Calculation methodology is described at %2$s.', 'getbirthchart' ),
				'https://getbirthchart.com/privacy/',
				'https://getbirthchart.com/methodology/'
			)
		);
		wp_add_privacy_policy_content(
			__( 'GetBirthChart – Birth Chart Calculators', 'getbirthchart' ),
			wp_kses_post( $content )
		);
	}
}
