<?php
/**
 * REST, settings, and security regression tests.
 *
 * @package GetBirthChart
 */

use PHPUnit\Framework\TestCase;

class SecurityFlowsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['getbirthchart_test_options']     = array();
		$GLOBALS['getbirthchart_test_filters']     = array();
		$GLOBALS['getbirthchart_test_transients']  = array();
		$GLOBALS['getbirthchart_test_can']         = true;
		$GLOBALS['getbirthchart_test_nonce_ok']    = true;
		$GLOBALS['getbirthchart_test_http']        = null;
		GetBirthChart_Settings::set_api_key( 'gbc_live_ab12xyzsecretvalue000000000000000' );
	}

	public function test_public_request_cannot_override_api_url(): void {
		$controller = new GetBirthChart_Rest_Controller();
		$this->assertTrue(
			$controller->has_forbidden_override(
				array(
					'type'     => 'birth-chart',
					'base_url' => 'https://evil.example',
				)
			)
		);
		$this->assertTrue(
			$controller->has_forbidden_override(
				array(
					'url' => 'https://evil.example/v1/charts/natal',
				)
			)
		);
		$this->assertTrue(
			$controller->has_forbidden_override(
				array(
					'scheme' => 'http',
					'host'   => 'evil.example',
				)
			)
		);
		$this->assertTrue(
			$controller->has_forbidden_override(
				array(
					'headers' => array( 'Authorization' => 'Bearer stolen' ),
				)
			)
		);
		$this->assertFalse(
			$controller->has_forbidden_override(
				array(
					'type'  => 'birth-chart',
					'date'  => '1990-01-15',
					'place' => 'London',
				)
			)
		);
	}

	public function test_public_calculate_never_returns_api_key(): void {
		$GLOBALS['getbirthchart_test_http'] = static function ( $url ) {
			if ( str_contains( $url, '/places/search' ) ) {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => '{"places":[{"label":"New York","latitude":40.7128,"longitude":-74.006,"timezone":"America/New_York"}]}',
					'headers'  => array(),
				);
			}
			$chart = file_get_contents( __DIR__ . '/fixtures/natal-known.json' );
			$decoded = json_decode( $chart, true );
			$decoded['leak'] = 'gbc_live_ab12xyzsecretvalue000000000000000';
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( $decoded ),
				'headers'  => array(),
			);
		};

		$request = new WP_REST_Request();
		$request->headers['x-wp-nonce'] = 'valid-nonce';
		$request->params = array(
			'type'  => 'birth-chart',
			'date'  => '1990-01-15',
			'time'  => '12:00',
			'place' => 'New York, NY',
		);
		$request->body = wp_json_encode( $request->params );

		$controller = new GetBirthChart_Rest_Controller();
		$response   = $controller->calculate( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$encoded = wp_json_encode( $response->get_data() );
		$this->assertStringNotContainsString( 'gbc_live_ab12xyzsecretvalue000000000000000', $encoded );
		$this->assertTrue( $response->get_data()['ok'] );
		$this->assertSame( 'Capricorn', $response->get_data()['result']['sun']['sign'] );
	}

	public function test_missing_nonce_is_rejected(): void {
		$GLOBALS['getbirthchart_test_nonce_ok'] = false;
		$controller = new GetBirthChart_Rest_Controller();
		$request   = new WP_REST_Request();
		$request->headers['x-wp-nonce'] = 'nope';
		$this->assertFalse( $controller->permission_calculate( $request ) );
	}

	public function test_unauthorized_user_cannot_manage_settings(): void {
		$GLOBALS['getbirthchart_test_can'] = false;
		$this->assertFalse( GetBirthChart_Settings::current_user_can_manage() );
	}

	public function test_csrf_settings_request_rejected(): void {
		$GLOBALS['getbirthchart_test_nonce_ok'] = false;
		$admin = new GetBirthChart_Admin();
		$this->expectException( RuntimeException::class );
		$admin->handle_save();
	}

	public function test_api_key_is_masked_and_not_stored_in_display_settings(): void {
		$masked = GetBirthChart_Settings::masked_api_key();
		$this->assertSame( 'gbc_live_ab12••••••••••', $masked );
		GetBirthChart_Settings::update_settings( array( 'default_type' => 'moon-sign', 'theme' => 'light' ) );
		$settings = GetBirthChart_Settings::get_settings();
		$this->assertSame( 'moon-sign', $settings['default_type'] );
		$encoded = wp_json_encode( $settings );
		$this->assertStringNotContainsString( 'gbc_live_ab12xyzsecretvalue000000000000000', $encoded );
	}

	public function test_oversized_body_rejected_before_upstream(): void {
		$called = false;
		$GLOBALS['getbirthchart_test_http'] = static function () use ( &$called ) {
			$called = true;
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => '{}',
				'headers'  => array(),
			);
		};
		$request = new WP_REST_Request();
		$request->headers['x-wp-nonce'] = 'valid-nonce';
		$request->body = str_repeat( 'a', GetBirthChart_Validator::MAX_BODY_BYTES + 1 );
		$request->params = array(
			'type'  => 'birth-chart',
			'date'  => '1990-01-15',
			'time'  => '12:00',
			'place' => 'New York, NY',
		);
		$controller = new GetBirthChart_Rest_Controller();
		$response   = $controller->calculate( $request );
		$this->assertSame( 413, $response->get_status() );
		$this->assertFalse( $called );
	}

	public function test_rate_limit_uses_hashed_identity(): void {
		$key = GetBirthChart_Rate_Limiter::transient_key( '203.0.113.10', 'birth-chart' );
		$this->assertStringStartsWith( 'gbc_rl_', $key );
		$this->assertStringNotContainsString( '203.0.113.10', $key );
		for ( $i = 0; $i < GetBirthChart_Rate_Limiter::LIMIT; $i++ ) {
			$this->assertTrue( GetBirthChart_Rate_Limiter::allow( '203.0.113.10', 'birth-chart' ) );
		}
		$this->assertFalse( GetBirthChart_Rate_Limiter::allow( '203.0.113.10', 'birth-chart' ) );
		$this->assertTrue( GetBirthChart_Rate_Limiter::allow( '203.0.113.10', 'moon-sign' ) );
	}

	public function test_unknown_time_rising_does_not_call_upstream(): void {
		$called = false;
		$GLOBALS['getbirthchart_test_http'] = static function () use ( &$called ) {
			$called = true;
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => '{}',
				'headers'  => array(),
			);
		};
		$request = new WP_REST_Request();
		$request->headers['x-wp-nonce'] = 'valid-nonce';
		$request->params = array(
			'type'         => 'rising-sign',
			'date'         => '1990-01-15',
			'place'        => 'New York, NY',
			'unknown_time' => true,
		);
		$request->body = wp_json_encode( $request->params );
		$controller = new GetBirthChart_Rest_Controller();
		$response   = $controller->calculate( $request );
		$this->assertFalse( $called );
		$this->assertFalse( $response->get_data()['ok'] );
		$this->assertSame( 'birth_time_required', $response->get_data()['error']['code'] );
	}

	public function test_unknown_time_omits_rising_from_birth_chart(): void {
		$chart  = json_decode( file_get_contents( __DIR__ . '/fixtures/natal-unknown.json' ), true );
		$result = GetBirthChart_Result_Mapper::map( 'birth-chart', $chart );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['birth_time_known'] );
		$this->assertArrayNotHasKey( 'rising', $result );
		$this->assertTrue( $result['moon']['uncertain'] );
		$rising = GetBirthChart_Result_Mapper::map( 'rising-sign', $chart );
		$this->assertInstanceOf( WP_Error::class, $rising );
	}

	public function test_known_time_maps_big_three(): void {
		$chart  = json_decode( file_get_contents( __DIR__ . '/fixtures/natal-known.json' ), true );
		$result = GetBirthChart_Result_Mapper::map( 'big-three', $chart );
		$this->assertSame( 'Capricorn', $result['sun']['sign'] );
		$this->assertSame( 'Virgo', $result['moon']['sign'] );
		$this->assertSame( 'Taurus', $result['rising']['sign'] );
	}

	public function test_frontend_config_never_includes_api_key(): void {
		$config = GetBirthChart_Assets::frontend_config();
		$encoded = wp_json_encode( $config );
		$this->assertSame( array( 'restUrl', 'nonce', 'i18n' ), array_keys( $config ) );
		$this->assertStringNotContainsString( 'gbc_live_ab12xyzsecretvalue000000000000000', $encoded );
		$this->assertStringNotContainsString( 'Authorization', $encoded );
	}
}
