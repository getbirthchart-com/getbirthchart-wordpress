<?php
/**
 * Public REST proxy for calculator calculations.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fixed calculate route. Not a generic upstream proxy.
 */
class GetBirthChart_Rest_Controller {

	public const NAMESPACE = 'getbirthchart/v1';

	/**
	 * Register routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the calculate endpoint.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/calculate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'calculate' ),
				'permission_callback' => array( $this, 'permission_calculate' ),
				'args'                => array(),
			)
		);
	}

	/**
	 * Public visitors may call this, but a REST nonce is required.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function permission_calculate( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! is_string( $nonce ) || '' === $nonce ) {
			$nonce = $request->get_param( '_wpnonce' );
		}
		return is_string( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Calculate handler.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function calculate( WP_REST_Request $request ) {
		$raw = $request->get_body();
		if ( strlen( $raw ) > GetBirthChart_Validator::MAX_BODY_BYTES ) {
			return $this->error_response( 'invalid_input', GetBirthChart_Validator::check_birth_information_message(), 413 );
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_params();
		}
		if ( ! is_array( $params ) ) {
			return $this->error_response( 'invalid_input', GetBirthChart_Validator::check_birth_information_message(), 400 );
		}

		if ( $this->has_forbidden_override( $params ) ) {
			return $this->error_response( 'invalid_input', GetBirthChart_Validator::check_birth_information_message(), 400 );
		}

		$validated = GetBirthChart_Validator::validate_calculate_input( $params );
		if ( is_wp_error( $validated ) ) {
			return $this->error_from_wp( $validated );
		}

		$ip = $this->client_ip();
		if ( ! GetBirthChart_Rate_Limiter::allow( $ip, $validated['type'] ) ) {
			return $this->error_response( 'rate_limited', __( 'Unable to calculate right now.', 'getbirthchart' ), 429 );
		}

		if ( ! GetBirthChart_Settings::has_api_key() ) {
			return $this->error_response( 'unavailable', __( 'This calculator is temporarily unavailable.', 'getbirthchart' ), 503 );
		}

		$client = new GetBirthChart_Api_Client();
		$place  = $client->resolve_place( $validated['place'] );
		if ( is_wp_error( $place ) ) {
			return $this->error_from_wp( $place );
		}

		$payload = GetBirthChart_Api_Client::natal_payload( $validated, $place );
		if ( is_wp_error( $payload ) ) {
			return $this->error_from_wp( $payload );
		}

		$upstream = $client->calculate_natal( $payload );
		if ( is_wp_error( $upstream ) ) {
			return $this->error_from_wp( $upstream );
		}

		$mapped = GetBirthChart_Result_Mapper::map( $validated['type'], $upstream['data'] );
		if ( is_wp_error( $mapped ) ) {
			return $this->error_from_wp( $mapped );
		}

		$mapped = $this->assert_no_secret( $mapped );
		return new WP_REST_Response(
			array(
				'ok'     => true,
				'result' => $mapped,
			),
			200
		);
	}

	/**
	 * Reject public attempts to steer the upstream URL.
	 *
	 * @param array<string, mixed> $params Request params.
	 */
	public function has_forbidden_override( array $params ): bool {
		$forbidden = array( 'url', 'base_url', 'endpoint', 'host', 'api_key', 'apiKey', 'authorization', 'path' );
		foreach ( $forbidden as $key ) {
			if ( array_key_exists( $key, $params ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Map an internal WP_Error to a public REST error.
	 *
	 * @param WP_Error $error Error.
	 */
	private function error_from_wp( WP_Error $error ): WP_REST_Response {
		$code   = (string) $error->get_error_code();
		$status = 400;
		if ( 'rate_limited' === $code ) {
			$status = 429;
		} elseif ( 'unavailable' === $code || 'unreachable' === $code || 'invalid_api_key' === $code ) {
			$status = 503;
		}
		return $this->error_response( $code, GetBirthChart_Api_Client::public_error_message( $error ), $status );
	}

	/**
	 * Build a public error payload.
	 *
	 * @param string $code    Error code.
	 * @param string $message User-safe message.
	 * @param int    $status  HTTP status.
	 */
	private function error_response( string $code, string $message, int $status ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'ok'    => false,
				'error' => array(
					'code'    => $code,
					'message' => $message,
				),
			),
			$status
		);
	}

	/**
	 * Strip accidental API-key leakage from mapped output.
	 *
	 * @param array<string, mixed> $result Mapped result.
	 * @return array<string, mixed>
	 */
	private function assert_no_secret( array $result ): array {
		$encoded = wp_json_encode( $result );
		$key     = GetBirthChart_Settings::get_api_key();
		if ( is_string( $encoded ) && '' !== $key && str_contains( $encoded, $key ) ) {
			return array(
				'type' => $result['type'] ?? 'birth-chart',
			);
		}
		return $result;
	}

	/**
	 * Hashable client identity. Raw IP is not stored.
	 */
	private function client_ip(): string {
		$ip = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return $ip;
	}
}
