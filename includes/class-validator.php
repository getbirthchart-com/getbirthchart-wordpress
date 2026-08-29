<?php
/**
 * Input validation for calculator requests and shortcodes.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates public calculator input before any upstream request.
 */
class GetBirthChart_Validator {

	public const TYPES              = array( 'birth-chart', 'moon-sign', 'rising-sign', 'big-three' );
	public const THEMES             = array( 'inherit', 'light' );
	public const MAX_PLACE_LENGTH   = 80;
	public const MIN_PLACE_LENGTH   = 2;
	public const MAX_BODY_BYTES     = 4096;
	public const API_KEY_PREFIX     = 'gbc_live_';
	public const API_KEY_MAX_LENGTH = 160;
	public const API_KEY_MIN_LENGTH = 20;

	/**
	 * Whether a calculator type is allowlisted.
	 *
	 * @param mixed $type Raw type value.
	 */
	public static function is_allowed_type( $type ): bool {
		return is_string( $type ) && in_array( $type, self::TYPES, true );
	}

	/**
	 * Whether a theme is allowlisted.
	 *
	 * @param mixed $theme Raw theme value.
	 */
	public static function is_allowed_theme( $theme ): bool {
		return is_string( $theme ) && in_array( $theme, self::THEMES, true );
	}

	/**
	 * Normalize a calculator type or return empty string.
	 *
	 * @param mixed $type Raw type value.
	 */
	public static function sanitize_type( $type ): string {
		if ( ! is_string( $type ) ) {
			return '';
		}
		$type = strtolower( trim( $type ) );
		return self::is_allowed_type( $type ) ? $type : '';
	}

	/**
	 * Normalize a theme or return the default.
	 *
	 * @param mixed $theme Raw theme value.
	 */
	public static function sanitize_theme( $theme ): string {
		if ( ! is_string( $theme ) ) {
			return 'inherit';
		}
		$theme = strtolower( trim( $theme ) );
		return self::is_allowed_theme( $theme ) ? $theme : 'inherit';
	}

	/**
	 * Validate and normalize a public calculate payload.
	 *
	 * Extra keys such as url/base_url/endpoint/host are ignored and never used.
	 *
	 * @param array<string, mixed> $input Raw request body.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function validate_calculate_input( array $input ) {
		$type = self::sanitize_type( $input['type'] ?? 'birth-chart' );
		if ( '' === $type ) {
			return new WP_Error(
				'invalid_type',
				__( 'This calculator is unavailable.', 'getbirthchart' )
			);
		}

		$date = self::sanitize_date( $input['date'] ?? '' );
		if ( is_wp_error( $date ) ) {
			return $date;
		}

		$unknown_time = self::normalize_boolean( $input['unknown_time'] ?? false );
		$time         = self::sanitize_time( $input['time'] ?? null, $unknown_time );
		if ( is_wp_error( $time ) ) {
			return $time;
		}

		if ( 'rising-sign' === $type && $unknown_time ) {
			return new WP_Error(
				'birth_time_required',
				self::rising_requires_time_message()
			);
		}

		$place = self::sanitize_place( $input['place'] ?? '' );
		if ( is_wp_error( $place ) ) {
			return $place;
		}

		return array(
			'type'         => $type,
			'date'         => $date,
			'time'         => $unknown_time ? null : $time,
			'place'        => $place,
			'unknown_time' => $unknown_time,
		);
	}

	/**
	 * Official GetBirthChart wording for Rising without a birth time.
	 */
	public static function rising_requires_time_message(): string {
		return __(
			"I can't determine your Rising sign without an accurate birth time. Rising and house placements need a known local birth time.",
			'getbirthchart'
		);
	}

	/**
	 * Validate YYYY-MM-DD as a real calendar date.
	 *
	 * @param mixed $value Raw date.
	 * @return string|WP_Error
	 */
	public static function sanitize_date( $value ) {
		if ( ! is_string( $value ) ) {
			return new WP_Error( 'invalid_input', self::check_birth_information_message() );
		}
		$date = trim( $value );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return new WP_Error( 'invalid_input', self::check_birth_information_message() );
		}
		$parts = explode( '-', $date );
		$year  = (int) $parts[0];
		$month = (int) $parts[1];
		$day   = (int) $parts[2];
		if ( $year < 1800 || $year > 2210 ) {
			return new WP_Error( 'invalid_input', self::check_birth_information_message() );
		}
		if ( ! checkdate( $month, $day, $year ) ) {
			return new WP_Error( 'invalid_input', self::check_birth_information_message() );
		}
		return $date;
	}

	/**
	 * Validate HH:MM or HH:MM:SS, or require null when time is unknown.
	 *
	 * @param mixed $value        Raw time.
	 * @param bool  $unknown_time Whether the unknown-time flag is set.
	 * @return string|null|WP_Error
	 */
	public static function sanitize_time( $value, bool $unknown_time ) {
		if ( $unknown_time ) {
			if ( null === $value || '' === $value || false === $value ) {
				return null;
			}
			if ( is_string( $value ) && '' === trim( $value ) ) {
				return null;
			}
			return new WP_Error( 'invalid_input', self::check_birth_information_message() );
		}

		if ( ! is_string( $value ) ) {
			return new WP_Error( 'invalid_input', self::check_birth_information_message() );
		}
		$time = trim( $value );
		if ( ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time ) ) {
			return new WP_Error( 'invalid_input', self::check_birth_information_message() );
		}
		return $time;
	}

	/**
	 * Validate a birth-place query.
	 *
	 * @param mixed $value Raw place.
	 * @return string|WP_Error
	 */
	public static function sanitize_place( $value ) {
		if ( ! is_string( $value ) ) {
			return new WP_Error( 'invalid_input', self::check_birth_information_message() );
		}
		$place  = preg_replace( '/[\x00-\x1F\x7F]/', ' ', $value );
		$place  = is_string( $place ) ? trim( preg_replace( '/\s+/', ' ', $place ) ) : '';
		$length = strlen( $place );
		if ( $length < self::MIN_PLACE_LENGTH || $length > self::MAX_PLACE_LENGTH ) {
			return new WP_Error( 'invalid_input', self::check_birth_information_message() );
		}
		return $place;
	}

	/**
	 * Normalize truthy/falsey request values to boolean.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function normalize_boolean( $value ): bool {
		if ( true === $value || 1 === $value || '1' === $value || 'true' === $value || 'on' === $value || 'yes' === $value ) {
			return true;
		}
		return false;
	}

	/**
	 * Trim and validate an API key without mutating secret characters.
	 *
	 * @param mixed $value Raw key.
	 * @return string|WP_Error
	 */
	public static function sanitize_api_key( $value ) {
		if ( ! is_string( $value ) ) {
			return new WP_Error( 'invalid_api_key', __( 'Enter a valid GetBirthChart API key.', 'getbirthchart' ) );
		}
		$key = trim( $value );
		if ( strlen( $key ) < self::API_KEY_MIN_LENGTH || strlen( $key ) > self::API_KEY_MAX_LENGTH ) {
			return new WP_Error( 'invalid_api_key', __( 'Enter a valid GetBirthChart API key.', 'getbirthchart' ) );
		}
		if ( ! str_starts_with( $key, self::API_KEY_PREFIX ) ) {
			return new WP_Error( 'invalid_api_key', __( 'Enter a valid GetBirthChart API key.', 'getbirthchart' ) );
		}
		if ( ! preg_match( '/^gbc_live_[A-Za-z0-9_-]+$/', $key ) ) {
			return new WP_Error( 'invalid_api_key', __( 'Enter a valid GetBirthChart API key.', 'getbirthchart' ) );
		}
		return $key;
	}

	/**
	 * Mask a stored API key for admin display. Never returns the full secret.
	 *
	 * @param string $key Stored key.
	 */
	public static function mask_api_key( string $key ): string {
		if ( ! str_starts_with( $key, self::API_KEY_PREFIX ) ) {
			return '••••••••••';
		}
		$rest = substr( $key, strlen( self::API_KEY_PREFIX ) );
		$head = substr( $rest, 0, 4 );
		return self::API_KEY_PREFIX . $head . '••••••••••';
	}

	/**
	 * User-safe validation copy.
	 */
	public static function check_birth_information_message(): string {
		return __( 'Please check the birth information.', 'getbirthchart' );
	}
}
