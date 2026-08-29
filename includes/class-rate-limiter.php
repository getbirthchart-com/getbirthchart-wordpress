<?php
/**
 * Lightweight site-side throttle for the public calculator endpoint.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rate-limits by hashed IP and calculator type.
 */
class GetBirthChart_Rate_Limiter {

	public const LIMIT          = 8;
	public const WINDOW_SECONDS = 60;

	/**
	 * Whether the request is allowed. Does not store the raw IP.
	 *
	 * @param string $ip   Remote address.
	 * @param string $type Calculator type.
	 */
	public static function allow( string $ip, string $type ): bool {
		$key   = self::transient_key( $ip, $type );
		$count = get_transient( $key );
		if ( false === $count || ! is_numeric( $count ) ) {
			set_transient( $key, 1, self::WINDOW_SECONDS );
			return true;
		}
		$count = (int) $count;
		if ( $count >= self::LIMIT ) {
			return false;
		}
		set_transient( $key, $count + 1, self::WINDOW_SECONDS );
		return true;
	}

	/**
	 * Hash identity so raw IPs are not stored as transient keys.
	 *
	 * @param string $ip   Remote address.
	 * @param string $type Calculator type.
	 */
	public static function transient_key( string $ip, string $type ): string {
		$type = GetBirthChart_Validator::sanitize_type( $type );
		if ( '' === $type ) {
			$type = 'birth-chart';
		}
		$material = $ip . '|' . $type . '|' . wp_salt( 'nonce' );
		return 'gbc_rl_' . substr( hash( 'sha256', $material ), 0, 32 );
	}
}
