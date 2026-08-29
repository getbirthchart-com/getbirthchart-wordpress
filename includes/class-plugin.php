<?php
/**
 * Plugin bootstrap.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads admin, public, REST, and privacy components.
 */
class GetBirthChart_Plugin {

	/**
	 * Plugin singleton.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Asset registrar.
	 *
	 * @var GetBirthChart_Assets
	 */
	private GetBirthChart_Assets $assets;

	/**
	 * Shortcode renderer.
	 *
	 * @var GetBirthChart_Shortcodes
	 */
	private GetBirthChart_Shortcodes $shortcodes;

	/**
	 * Singleton.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Activation: no external calls, no tables, no telemetry.
	 */
	public static function activate(): void {
		if ( false === get_option( GetBirthChart_Settings::SETTINGS_OPTION, false ) ) {
			add_option( GetBirthChart_Settings::SETTINGS_OPTION, GetBirthChart_Settings::defaults(), '', true );
		}
	}

	/**
	 * Deactivation leaves settings in place.
	 */
	public static function deactivate(): void {
		// Intentionally empty.
	}

	/**
	 * Wire components.
	 */
	public function init(): void {
		$this->assets     = new GetBirthChart_Assets();
		$this->shortcodes = new GetBirthChart_Shortcodes( $this->assets );
		$rest             = new GetBirthChart_Rest_Controller();
		$privacy          = new GetBirthChart_Privacy();
		$public           = new GetBirthChart_Public( $this->shortcodes );

		$this->assets->register();
		$this->shortcodes->register();
		$rest->register();
		$privacy->register();
		$public->register();

		if ( is_admin() ) {
			$admin = new GetBirthChart_Admin();
			$admin->register();
		}
	}
}
