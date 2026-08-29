<?php
/**
 * API client tests.
 *
 * @package GetBirthChart
 */

use PHPUnit\Framework\TestCase;

class ApiClientTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['getbirthchart_test_options'] = array();
		$GLOBALS['getbirthchart_test_filters'] = array();
		$GLOBALS['getbirthchart_test_http'] = null;
		GetBirthChart_Settings::set_api_key( 'gbc_live_ab12xyzsecretvalue000000000000000' );
	}

	public function test_authorization_header_uses_bearer_and_not_query_string(): void {
		$captured = null;
		$GLOBALS['getbirthchart_test_http'] = function ( $url, $args ) use ( &$captured ) {
			$captured = array( 'url' => $url, 'args' => $args );
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => '{"ok":true}',
				'headers'  => array(),
			);
		};
		$client = new GetBirthChart_Api_Client();
		$client->calculate_natal(
			array(
				'local_date'   => '1990-01-15',
				'local_time'   => '12:00',
				'unknown_time' => false,
				'timezone'     => 'America/New_York',
				'latitude'     => 40.7,
				'longitude'    => -74.0,
			)
		);
		$this->assertNotNull( $captured );
		$this->assertStringContainsString( '/v1/charts/natal', $captured['url'] );
		$this->assertStringNotContainsString( 'gbc_live_', $captured['url'] );
		$this->assertSame(
			'Bearer gbc_live_ab12xyzsecretvalue000000000000000',
			$captured['args']['headers']['Authorization']
		);
		$this->assertSame( 0, $captured['args']['redirection'] );
		$this->assertSame( 15, $captured['args']['timeout'] );
	}

	public function test_public_filter_cannot_inject_credentials_into_base_url(): void {
		add_filter(
			'getbirthchart_api_base_url',
			static function () {
				return 'https://user:secret@evil.example/path?x=1';
			}
		);
		$this->assertSame( 'https://getbirthchart.com/api', GetBirthChart_Api_Client::get_base_url() );
	}

	public function test_connection_test_maps_400_after_auth_to_connected(): void {
		$GLOBALS['getbirthchart_test_http'] = static function () {
			return array(
				'response' => array( 'code' => 400 ),
				'body'     => '{"error":{"code":"validation_error","message":"Please check the request fields and try again."}}',
				'headers'  => array( 'x-request-id' => 'req_123' ),
			);
		};
		$client = new GetBirthChart_Api_Client();
		$result = $client->test_connection();
		$this->assertSame( 'connected', $result['status'] );
		$this->assertSame( 'req_123', $result['request_id'] );
	}

	public function test_connection_test_maps_401_to_invalid_key(): void {
		$GLOBALS['getbirthchart_test_http'] = static function () {
			return array(
				'response' => array( 'code' => 401 ),
				'body'     => '{"error":{"code":"invalid_api_key","message":"A valid API key is required."}}',
				'headers'  => array(),
			);
		};
		$client = new GetBirthChart_Api_Client();
		$result = $client->test_connection();
		$this->assertSame( 'invalid', $result['status'] );
		$this->assertSame( 'Invalid API key', GetBirthChart_Api_Client::connection_status_label( $result['status'] ) );
	}

	public function test_unknown_time_natal_payload_sends_null_time(): void {
		$payload = GetBirthChart_Api_Client::natal_payload(
			array(
				'date'         => '1990-01-15',
				'time'        => null,
				'unknown_time' => true,
			),
			array(
				'latitude'  => 40.7,
				'longitude' => -74.0,
				'timezone'  => 'America/New_York',
			)
		);
		$this->assertIsArray( $payload );
		$this->assertTrue( $payload['unknown_time'] );
		$this->assertNull( $payload['local_time'] );
		$this->assertNotSame( '12:00', $payload['local_time'] );
	}

	public function test_raw_upstream_error_is_not_exposed_publicly(): void {
		$error = ( new GetBirthChart_Api_Client() )->map_status(
			500,
			array(
				'error' => array(
					'code'    => 'internal_error',
					'message' => 'Redis down Authorization: Bearer gbc_live_ab12xyzsecretvalue000000000000000',
				),
			)
		);
		$this->assertInstanceOf( WP_Error::class, $error );
		$public = GetBirthChart_Api_Client::public_error_message( $error );
		$this->assertSame( 'This calculator is temporarily unavailable.', $public );
		$this->assertStringNotContainsString( 'Redis', $public );
		$this->assertStringNotContainsString( 'gbc_live_', $public );
	}

	public function test_places_search_does_not_send_authorization(): void {
		$captured = null;
		$GLOBALS['getbirthchart_test_http'] = function ( $url, $args ) use ( &$captured ) {
			$captured = array( 'url' => $url, 'args' => $args );
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => '{"places":[{"label":"New York, NY, United States","latitude":40.7128,"longitude":-74.006,"timezone":"America/New_York"}]}',
				'headers'  => array(),
			);
		};
		$client = new GetBirthChart_Api_Client();
		$place  = $client->resolve_place( 'New York, NY' );
		$this->assertIsArray( $place );
		$this->assertArrayNotHasKey( 'Authorization', $captured['args']['headers'] );
		$this->assertStringContainsString( '/places/search', $captured['url'] );
	}
}
