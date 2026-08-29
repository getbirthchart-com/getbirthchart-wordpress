<?php
/**
 * PHPUnit bootstrap without a full WordPress install.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/getbirthchart-wp/' );
}
if ( ! defined( 'GETBIRTHCHART_VERSION' ) ) {
	define( 'GETBIRTHCHART_VERSION', '0.1.0' );
}
if ( ! defined( 'GETBIRTHCHART_API_BASE_URL' ) ) {
	define( 'GETBIRTHCHART_API_BASE_URL', 'https://getbirthchart.com/api' );
}

$GLOBALS['getbirthchart_test_options'] = array();
$GLOBALS['getbirthchart_test_transients'] = array();
$GLOBALS['getbirthchart_test_filters'] = array();
$GLOBALS['getbirthchart_test_can'] = true;
$GLOBALS['getbirthchart_test_nonce_ok'] = true;
$GLOBALS['getbirthchart_test_http'] = null;
$GLOBALS['getbirthchart_test_remote_addr'] = '203.0.113.10';

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $errors = array();
		public $error_data = array();

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( '' !== $code ) {
				$this->errors[ $code ][] = $message;
				if ( '' !== $data ) {
					$this->error_data[ $code ] = $data;
				}
			}
		}

		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return $codes[0] ?? '';
		}

		public function get_error_message( $code = '' ) {
			if ( '' === $code ) {
				$code = $this->get_error_code();
			}
			return $this->errors[ $code ][0] ?? '';
		}

		public function get_error_data( $code = '' ) {
			if ( '' === $code ) {
				$code = $this->get_error_code();
			}
			return $this->error_data[ $code ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		public array $params = array();
		public string $body = '';
		public array $headers = array();

		public function get_json_params() {
			return $this->params;
		}

		public function get_params() {
			return $this->params;
		}

		public function get_param( $key ) {
			return $this->params[ $key ] ?? null;
		}

		public function get_body() {
			return $this->body;
		}

		public function get_header( $key ) {
			$key = strtolower( $key );
			return $this->headers[ $key ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		public $data;
		public int $status;

		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = (int) $status;
		}

		public function get_data() {
			return $this->data;
		}

		public function get_status() {
			return $this->status;
		}
	}
}

function __( $text, $domain = 'default' ) { // phpcs:ignore
	unset( $domain );
	return $text;
}

function esc_html__( $text, $domain = 'default' ) { // phpcs:ignore
	unset( $domain );
	return $text;
}

function sanitize_text_field( $value ) {
	return is_string( $value ) ? trim( $value ) : '';
}

function wp_unslash( $value ) {
	return $value;
}

function wp_json_encode( $data ) {
	return json_encode( $data );
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
}

function wp_salt( $scheme = 'auth' ) {
	return 'test-salt-' . $scheme;
}

function add_query_arg( $args, $url ) {
	$separator = str_contains( $url, '?' ) ? '&' : '?';
	return $url . $separator . http_build_query( $args );
}

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

function current_user_can( $capability ) {
	unset( $capability );
	return (bool) $GLOBALS['getbirthchart_test_can'];
}

function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {
	unset( $action, $query_arg );
	if ( ! $GLOBALS['getbirthchart_test_nonce_ok'] ) {
		throw new RuntimeException( 'nonce_failed' );
	}
	return true;
}

function wp_verify_nonce( $nonce, $action = -1 ) {
	unset( $action );
	if ( ! $GLOBALS['getbirthchart_test_nonce_ok'] ) {
		return false;
	}
	return 'valid-nonce' === $nonce;
}

function wp_create_nonce( $action = -1 ) {
	unset( $action );
	return 'valid-nonce';
}

function rest_url( $path = '', $scheme = 'rest' ) {
	unset( $scheme );
	return 'https://example.test/wp-json/' . ltrim( (string) $path, '/' );
}

function esc_url_raw( $url, $protocols = null ) {
	unset( $protocols );
	return is_string( $url ) ? $url : '';
}

function get_option( $option, $default = false ) {
	return $GLOBALS['getbirthchart_test_options'][ $option ] ?? $default;
}

function add_option( $option, $value, $deprecated = '', $autoload = 'yes' ) {
	unset( $deprecated, $autoload );
	$GLOBALS['getbirthchart_test_options'][ $option ] = $value;
	return true;
}

function update_option( $option, $value, $autoload = null ) {
	unset( $autoload );
	$GLOBALS['getbirthchart_test_options'][ $option ] = $value;
	return true;
}

function delete_option( $option ) {
	unset( $GLOBALS['getbirthchart_test_options'][ $option ] );
	return true;
}

function get_transient( $transient ) {
	return $GLOBALS['getbirthchart_test_transients'][ $transient ] ?? false;
}

function set_transient( $transient, $value, $expiration = 0 ) {
	unset( $expiration );
	$GLOBALS['getbirthchart_test_transients'][ $transient ] = $value;
	return true;
}

function apply_filters( $hook, $value, ...$args ) {
	if ( empty( $GLOBALS['getbirthchart_test_filters'][ $hook ] ) ) {
		return $value;
	}
	foreach ( $GLOBALS['getbirthchart_test_filters'][ $hook ] as $callback ) {
		$value = $callback( $value, ...$args );
	}
	return $value;
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	unset( $priority, $accepted_args );
	$GLOBALS['getbirthchart_test_filters'][ $hook ][] = $callback;
}

function wp_remote_request( $url, $args = array() ) {
	if ( is_callable( $GLOBALS['getbirthchart_test_http'] ) ) {
		return $GLOBALS['getbirthchart_test_http']( $url, $args );
	}
	return array(
		'response' => array( 'code' => 0 ),
		'body'     => '',
		'headers'  => array(),
	);
}

function wp_remote_retrieve_response_code( $response ) {
	return (int) ( $response['response']['code'] ?? 0 );
}

function wp_remote_retrieve_body( $response ) {
	return (string) ( $response['body'] ?? '' );
}

function wp_remote_retrieve_header( $response, $header ) {
	$header = strtolower( $header );
	$headers = $response['headers'] ?? array();
	return $headers[ $header ] ?? '';
}

require_once dirname( __DIR__ ) . '/includes/class-validator.php';
require_once dirname( __DIR__ ) . '/includes/class-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-api-client.php';
require_once dirname( __DIR__ ) . '/includes/class-result-mapper.php';
require_once dirname( __DIR__ ) . '/includes/class-rate-limiter.php';
require_once dirname( __DIR__ ) . '/includes/class-rest-controller.php';
require_once dirname( __DIR__ ) . '/includes/class-shortcodes.php';
require_once dirname( __DIR__ ) . '/includes/class-assets.php';
require_once dirname( __DIR__ ) . '/admin/class-admin.php';
