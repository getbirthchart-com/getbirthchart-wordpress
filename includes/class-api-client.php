<?php
/**
 * Server-side GetBirthChart public API client.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Talks to the GetBirthChart public API with the site owner's key.
 */
class GetBirthChart_Api_Client {

	public const TIMEOUT_SECONDS = 15;
	public const NATAL_PATH      = '/v1/charts/natal';
	public const PLACES_PATH     = '/places/search';

	/**
	 * Filterable API base URL. Public requests cannot set this.
	 */
	public static function get_base_url(): string {
		$default   = defined( 'GETBIRTHCHART_API_BASE_URL' ) ? GETBIRTHCHART_API_BASE_URL : 'https://getbirthchart.com/api';
		$filtered  = apply_filters( 'getbirthchart_api_base_url', $default );
		$validated = self::validate_base_url( is_string( $filtered ) ? $filtered : $default );
		return $validated ? $validated : $default;
	}

	/**
	 * Reject credentials, non-HTTP(S) schemes, and query/hash fragments.
	 *
	 * @param string $url Candidate base URL.
	 */
	public static function validate_base_url( string $url ): string {
		$url   = trim( $url );
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) ) {
			return '';
		}
		$scheme = strtolower( (string) $parts['scheme'] );
		$host   = strtolower( (string) $parts['host'] );
		$local  = in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );
		if ( 'https' !== $scheme && ! ( 'http' === $scheme && $local ) ) {
			return '';
		}
		$path   = isset( $parts['path'] ) ? rtrim( (string) $parts['path'], '/' ) : '';
		$origin = $scheme . '://' . $parts['host'];
		if ( ! empty( $parts['port'] ) ) {
			$origin .= ':' . (int) $parts['port'];
		}
		return $origin . $path;
	}

	/**
	 * Build a natal payload from already-validated input plus a resolved place.
	 *
	 * @param array<string, mixed> $input  Validated calculator input.
	 * @param array<string, mixed> $place  Resolved place.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function natal_payload( array $input, array $place ) {
		$latitude  = $place['latitude'] ?? null;
		$longitude = $place['longitude'] ?? null;
		$timezone  = $place['timezone'] ?? '';
		if ( ! is_numeric( $latitude ) || ! is_numeric( $longitude ) || ! is_string( $timezone ) || '' === trim( $timezone ) ) {
			return new WP_Error( 'invalid_input', GetBirthChart_Validator::check_birth_information_message() );
		}
		$latitude  = (float) $latitude;
		$longitude = (float) $longitude;
		if ( $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180 ) {
			return new WP_Error( 'invalid_input', GetBirthChart_Validator::check_birth_information_message() );
		}
		if ( strlen( $timezone ) > 100 ) {
			return new WP_Error( 'invalid_input', GetBirthChart_Validator::check_birth_information_message() );
		}
		return array(
			'local_date'   => $input['date'],
			'local_time'   => ! empty( $input['unknown_time'] ) ? null : $input['time'],
			'unknown_time' => ! empty( $input['unknown_time'] ),
			'timezone'     => trim( $timezone ),
			'latitude'     => $latitude,
			'longitude'    => $longitude,
		);
	}

	/**
	 * Resolve a birth place through GetBirthChart's public places search.
	 *
	 * @param string $query Place query.
	 * @return array<string, mixed>|WP_Error
	 */
	public function resolve_place( string $query ) {
		$result = $this->request(
			'GET',
			self::PLACES_PATH,
			null,
			array( 'q' => $query ),
			false
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$data = $result['data'];
		if ( ! is_array( $data ) || ! isset( $data['places'] ) || ! is_array( $data['places'] ) ) {
			return new WP_Error( 'unavailable', $this->unavailable_message() );
		}
		foreach ( $data['places'] as $candidate ) {
			$normalized = $this->normalize_place_result( $candidate );
			if ( null !== $normalized ) {
				return $normalized;
			}
		}
		return new WP_Error( 'invalid_input', GetBirthChart_Validator::check_birth_information_message() );
	}

	/**
	 * POST a natal calculation. Caller must already have validated input.
	 *
	 * @param array<string, mixed> $payload Natal request body.
	 * @return array<string, mixed>|WP_Error
	 */
	public function calculate_natal( array $payload ) {
		return $this->request( 'POST', self::NATAL_PATH, $payload, array(), true );
	}

	/**
	 * Lightweight authenticated connection test.
	 *
	 * Posts an empty object to the natal route. A 400 validation error after
	 * authentication means the key was accepted without running a calculation.
	 *
	 * @return array{status: string, request_id: string}
	 */
	public function test_connection(): array {
		if ( ! GetBirthChart_Settings::has_api_key() ) {
			return array(
				'status'     => 'invalid',
				'request_id' => '',
			);
		}
		$result     = $this->request( 'POST', self::NATAL_PATH, array(), array(), true );
		$request_id = '';
		if ( is_array( $result ) && ! empty( $result['request_id'] ) && is_string( $result['request_id'] ) ) {
			$request_id = $result['request_id'];
		} elseif ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();
			if ( is_array( $data ) && ! empty( $data['request_id'] ) && is_string( $data['request_id'] ) ) {
				$request_id = $data['request_id'];
			}
		}

		if ( is_wp_error( $result ) ) {
			$code = $result->get_error_code();
			if ( 'invalid_api_key' === $code ) {
				return array(
					'status'     => 'invalid',
					'request_id' => $request_id,
				);
			}
			if ( 'rate_limited' === $code ) {
				return array(
					'status'     => 'rate_limited',
					'request_id' => $request_id,
				);
			}
			if ( 'invalid_input' === $code ) {
				return array(
					'status'     => 'connected',
					'request_id' => $request_id,
				);
			}
			return array(
				'status'     => 'unreachable',
				'request_id' => $request_id,
			);
		}

		$status = (int) ( $result['status'] ?? 0 );
		if ( 401 === $status || 403 === $status ) {
			return array(
				'status'     => 'invalid',
				'request_id' => $request_id,
			);
		}
		if ( 429 === $status ) {
			return array(
				'status'     => 'rate_limited',
				'request_id' => $request_id,
			);
		}
		if ( $status >= 200 && $status < 500 ) {
			return array(
				'status'     => 'connected',
				'request_id' => $request_id,
			);
		}
		return array(
			'status'     => 'unreachable',
			'request_id' => $request_id,
		);
	}

	/**
	 * Perform an HTTP request against a fixed API path.
	 *
	 * @param string                $method        GET or POST.
	 * @param string                $path          Allowlisted path.
	 * @param array<mixed>|null     $body          JSON body for POST.
	 * @param array<string, string> $query       Query string for GET.
	 * @param bool                  $authenticate Attach the API key.
	 * @return array<string, mixed>|WP_Error
	 */
	public function request( string $method, string $path, $body = null, array $query = array(), bool $authenticate = true ) {
		if ( ! in_array( $path, array( self::NATAL_PATH, self::PLACES_PATH ), true ) ) {
			return new WP_Error( 'unavailable', $this->unavailable_message() );
		}
		$url = self::get_base_url() . $path;
		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		$headers = array(
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json',
		);
		$api_key = '';
		if ( $authenticate ) {
			$api_key = GetBirthChart_Settings::get_api_key();
			if ( '' === $api_key ) {
				return new WP_Error( 'unavailable', $this->unavailable_message() );
			}
			$headers['Authorization'] = 'Bearer ' . $api_key;
		}

		$args = array(
			'method'             => $method,
			'timeout'            => self::TIMEOUT_SECONDS,
			'redirection'        => 0,
			'headers'            => $headers,
			'sslverify'          => true,
			'reject_unsafe_urls' => true,
			'user-agent'         => 'GetBirthChart-WordPress/' . GETBIRTHCHART_VERSION,
		);
		if ( 'POST' === $method ) {
			$encoded = wp_json_encode( null === $body ? array() : $body );
			if ( ! is_string( $encoded ) ) {
				return new WP_Error( 'unavailable', $this->unavailable_message() );
			}
			$args['body'] = $encoded;
		}

		$pre = apply_filters( 'getbirthchart_pre_http_request', null, $method, $url, $args );
		if ( null !== $pre ) {
			$response = $pre;
		} else {
			$response = wp_remote_request( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'unreachable',
				__( 'Unable to reach GetBirthChart.', 'getbirthchart' )
			);
		}

		$status     = (int) wp_remote_retrieve_response_code( $response );
		$raw_body   = (string) wp_remote_retrieve_body( $response );
		$request_id = $this->extract_request_id( $response );
		$decoded    = json_decode( $raw_body, true );

		$mapped = $this->map_status( $status, is_array( $decoded ) ? $decoded : array(), $request_id, $api_key );
		if ( is_wp_error( $mapped ) ) {
			return $mapped;
		}

		return array(
			'status'     => $status,
			'data'       => is_array( $decoded ) ? $decoded : array(),
			'request_id' => $request_id,
		);
	}

	/**
	 * Map upstream HTTP status to a user-safe error or success.
	 *
	 * @param int                  $status     HTTP status.
	 * @param array<string, mixed> $payload    Decoded JSON.
	 * @param string               $request_id Request id if present.
	 * @param string               $api_key    Used only to redact accidental echoes.
	 * @return true|WP_Error
	 */
	public function map_status( int $status, array $payload, string $request_id = '', string $api_key = '' ) {
		if ( $status >= 200 && $status < 300 ) {
			return true;
		}

		$code    = '';
		$message = '';
		if ( isset( $payload['error'] ) && is_array( $payload['error'] ) ) {
			$code    = isset( $payload['error']['code'] ) && is_string( $payload['error']['code'] ) ? $payload['error']['code'] : '';
			$message = isset( $payload['error']['message'] ) && is_string( $payload['error']['message'] ) ? $payload['error']['message'] : '';
		}
		$message = $this->redact_secret( $message, $api_key );

		$data = array();
		if ( '' !== $request_id ) {
			$data['request_id'] = $request_id;
		}

		if ( 401 === $status || 403 === $status || 'invalid_api_key' === $code || 'UNAUTHORIZED' === $code ) {
			return new WP_Error( 'invalid_api_key', __( 'Invalid API key', 'getbirthchart' ), $data );
		}
		if ( 429 === $status || 'rate_limited' === $code || 'RATE_LIMITED' === $code || 'RATE_LIMIT_ERROR' === $code ) {
			return new WP_Error( 'rate_limited', __( 'Rate limited', 'getbirthchart' ), $data );
		}
		if ( 'UNKNOWN_BIRTH_TIME' === $code || 'BIRTH_TIME_REQUIRED' === $code || 'unknown_birth_time' === $code ) {
			return new WP_Error( 'birth_time_required', GetBirthChart_Validator::rising_requires_time_message(), $data );
		}
		if ( 400 === $status || 409 === $status || 422 === $status || 'validation_error' === $code || 'REQUEST_VALIDATION_ERROR' === $code ) {
			return new WP_Error( 'invalid_input', GetBirthChart_Validator::check_birth_information_message(), $data );
		}
		if ( 0 === $status ) {
			return new WP_Error( 'unreachable', __( 'Unable to reach GetBirthChart.', 'getbirthchart' ), $data );
		}

		unset( $message );
		return new WP_Error( 'unavailable', $this->unavailable_message(), $data );
	}

	/**
	 * Public-facing calculator error from a WP_Error.
	 *
	 * @param WP_Error $error Error from validation or the API client.
	 */
	public static function public_error_message( WP_Error $error ): string {
		$code = $error->get_error_code();
		if ( 'birth_time_required' === $code ) {
			return GetBirthChart_Validator::rising_requires_time_message();
		}
		if ( 'invalid_input' === $code || 'invalid_type' === $code ) {
			return GetBirthChart_Validator::check_birth_information_message();
		}
		if ( 'rate_limited' === $code ) {
			return __( 'Unable to calculate right now.', 'getbirthchart' );
		}
		return __( 'This calculator is temporarily unavailable.', 'getbirthchart' );
	}

	/**
	 * Admin-facing connection-test label.
	 *
	 * @param string $status Status token.
	 */
	public static function connection_status_label( string $status ): string {
		switch ( $status ) {
			case 'connected':
				return __( 'Connected', 'getbirthchart' );
			case 'invalid':
				return __( 'Invalid API key', 'getbirthchart' );
			case 'rate_limited':
				return __( 'Rate limited', 'getbirthchart' );
			default:
				return __( 'Unable to reach GetBirthChart', 'getbirthchart' );
		}
	}

	/**
	 * Normalize a places-search hit.
	 *
	 * @param mixed $candidate Place object.
	 * @return array<string, mixed>|null
	 */
	private function normalize_place_result( $candidate ) {
		if ( ! is_array( $candidate ) ) {
			return null;
		}
		$latitude  = $candidate['latitude'] ?? null;
		$longitude = $candidate['longitude'] ?? null;
		$timezone  = $candidate['timezone'] ?? '';
		$label     = $candidate['label'] ?? '';
		if ( ! is_numeric( $latitude ) || ! is_numeric( $longitude ) || ! is_string( $timezone ) || '' === trim( $timezone ) ) {
			return null;
		}
		$latitude  = (float) $latitude;
		$longitude = (float) $longitude;
		if ( $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180 ) {
			return null;
		}
		if ( ! is_string( $label ) || '' === $label ) {
			$label = '';
		}
		return array(
			'latitude'  => $latitude,
			'longitude' => $longitude,
			'timezone'  => sanitize_text_field( $timezone ),
			'label'     => sanitize_text_field( $label ),
		);
	}

	/**
	 * Read a request ID from response headers if present.
	 *
	 * @param mixed $response WordPress HTTP response.
	 */
	private function extract_request_id( $response ): string {
		$header = wp_remote_retrieve_header( $response, 'x-request-id' );
		if ( ! is_string( $header ) || '' === $header ) {
			$header = wp_remote_retrieve_header( $response, 'request-id' );
		}
		if ( ! is_string( $header ) ) {
			return '';
		}
		$header = sanitize_text_field( $header );
		return strlen( $header ) > 80 ? substr( $header, 0, 80 ) : $header;
	}

	/**
	 * Remove a stored secret from an upstream message.
	 *
	 * @param string $message Raw message.
	 * @param string $api_key Secret to redact.
	 */
	private function redact_secret( string $message, string $api_key ): string {
		if ( '' === $api_key ) {
			return $message;
		}
		return str_replace( $api_key, '[redacted]', $message );
	}

	/**
	 * User-safe unavailable copy.
	 */
	private function unavailable_message(): string {
		return __( 'This calculator is temporarily unavailable.', 'getbirthchart' );
	}
}
