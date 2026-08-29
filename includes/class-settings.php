<?php
/**
 * Plugin settings stored through the WordPress options API.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API key and display settings.
 */
class GetBirthChart_Settings {

	public const API_KEY_OPTION  = 'getbirthchart_api_key';
	public const SETTINGS_OPTION = 'getbirthchart_settings';
	public const CAPABILITY      = 'manage_options';

	/**
	 * Default non-secret settings.
	 *
	 * @return array{default_type: string, theme: string}
	 */
	public static function defaults(): array {
		return array(
			'default_type' => 'birth-chart',
			'theme'        => 'inherit',
		);
	}

	/**
	 * Whether the current user may manage plugin settings.
	 */
	public static function current_user_can_manage(): bool {
		return current_user_can( self::CAPABILITY );
	}

	/**
	 * Stored API key, or empty string.
	 */
	public static function get_api_key(): string {
		$value = get_option( self::API_KEY_OPTION, '' );
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Whether an API key is stored.
	 */
	public static function has_api_key(): bool {
		return '' !== self::get_api_key();
	}

	/**
	 * Store an API key without autoloading it on every request.
	 *
	 * @param string $key Validated API key.
	 */
	public static function set_api_key( string $key ): bool {
		$existing = get_option( self::API_KEY_OPTION, null );
		if ( false === $existing || null === $existing ) {
			return add_option( self::API_KEY_OPTION, $key, '', false );
		}
		return update_option( self::API_KEY_OPTION, $key, false );
	}

	/**
	 * Remove the stored API key.
	 */
	public static function delete_api_key(): bool {
		return delete_option( self::API_KEY_OPTION );
	}

	/**
	 * Non-secret settings.
	 *
	 * @return array{default_type: string, theme: string}
	 */
	public static function get_settings(): array {
		$stored   = get_option( self::SETTINGS_OPTION, array() );
		$defaults = self::defaults();
		if ( ! is_array( $stored ) ) {
			return $defaults;
		}
		$type  = GetBirthChart_Validator::sanitize_type( $stored['default_type'] ?? '' );
		$theme = GetBirthChart_Validator::sanitize_theme( $stored['theme'] ?? '' );
		return array(
			'default_type' => '' !== $type ? $type : $defaults['default_type'],
			'theme'        => $theme,
		);
	}

	/**
	 * Persist non-secret settings.
	 *
	 * @param array<string, mixed> $settings Raw settings.
	 */
	public static function update_settings( array $settings ): bool {
		$current = self::get_settings();
		$type    = GetBirthChart_Validator::sanitize_type( $settings['default_type'] ?? $current['default_type'] );
		$theme   = GetBirthChart_Validator::sanitize_theme( $settings['theme'] ?? $current['theme'] );
		$value   = array(
			'default_type' => '' !== $type ? $type : $current['default_type'],
			'theme'        => $theme,
		);
		return update_option( self::SETTINGS_OPTION, $value, true );
	}

	/**
	 * Masked API key for admin HTML. Empty when none is stored.
	 */
	public static function masked_api_key(): string {
		$key = self::get_api_key();
		if ( '' === $key ) {
			return '';
		}
		return GetBirthChart_Validator::mask_api_key( $key );
	}
}
