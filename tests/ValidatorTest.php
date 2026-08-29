<?php
/**
 * Validator tests.
 *
 * @package GetBirthChart
 */

use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase {

	public function test_allowlisted_types(): void {
		$this->assertTrue( GetBirthChart_Validator::is_allowed_type( 'birth-chart' ) );
		$this->assertTrue( GetBirthChart_Validator::is_allowed_type( 'moon-sign' ) );
		$this->assertTrue( GetBirthChart_Validator::is_allowed_type( 'rising-sign' ) );
		$this->assertTrue( GetBirthChart_Validator::is_allowed_type( 'big-three' ) );
		$this->assertFalse( GetBirthChart_Validator::is_allowed_type( 'synastry' ) );
		$this->assertFalse( GetBirthChart_Validator::is_allowed_type( 'forecast' ) );
		$this->assertSame( '', GetBirthChart_Validator::sanitize_type( '../v1/charts/natal' ) );
		$this->assertSame( '', GetBirthChart_Validator::sanitize_type( array( 'birth-chart' ) ) );
	}

	public function test_invalid_date_rejected(): void {
		$error = GetBirthChart_Validator::sanitize_date( '1990-13-40' );
		$this->assertInstanceOf( WP_Error::class, $error );
		$error = GetBirthChart_Validator::sanitize_date( 'not-a-date' );
		$this->assertInstanceOf( WP_Error::class, $error );
		$this->assertSame( '1990-01-15', GetBirthChart_Validator::sanitize_date( '1990-01-15' ) );
	}

	public function test_unknown_time_rejects_clock_time(): void {
		$error = GetBirthChart_Validator::sanitize_time( '12:00', true );
		$this->assertInstanceOf( WP_Error::class, $error );
		$this->assertNull( GetBirthChart_Validator::sanitize_time( null, true ) );
		$this->assertSame( '09:30', GetBirthChart_Validator::sanitize_time( '09:30', false ) );
	}

	public function test_oversized_place_rejected(): void {
		$place = str_repeat( 'a', 81 );
		$error = GetBirthChart_Validator::sanitize_place( $place );
		$this->assertInstanceOf( WP_Error::class, $error );
		$this->assertSame( 'New York, NY', GetBirthChart_Validator::sanitize_place( 'New York, NY' ) );
	}

	public function test_rising_unknown_time_fails_before_api(): void {
		$result = GetBirthChart_Validator::validate_calculate_input(
			array(
				'type'         => 'rising-sign',
				'date'         => '1990-01-15',
				'place'        => 'New York, NY',
				'unknown_time' => true,
			)
		);
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'birth_time_required', $result->get_error_code() );
		$this->assertStringContainsString( 'Rising sign', $result->get_error_message() );
	}

	public function test_unknown_time_payload_does_not_invent_noon(): void {
		$result = GetBirthChart_Validator::validate_calculate_input(
			array(
				'type'         => 'birth-chart',
				'date'         => '1990-01-15',
				'place'        => 'London, UK',
				'unknown_time' => true,
			)
		);
		$this->assertIsArray( $result );
		$this->assertTrue( $result['unknown_time'] );
		$this->assertNull( $result['time'] );
	}

	public function test_malformed_shortcode_type_rejected(): void {
		$this->assertSame( '', GetBirthChart_Validator::sanitize_type( 'birth-chart"><script>' ) );
		$this->assertSame( '', GetBirthChart_Validator::sanitize_type( 'v1/charts/natal' ) );
	}

	public function test_api_key_prefix_and_masking(): void {
		$key = 'gbc_live_ab12xyzsecretvalue000000000000000';
		$this->assertSame( $key, GetBirthChart_Validator::sanitize_api_key( "  {$key}  " ) );
		$masked = GetBirthChart_Validator::mask_api_key( $key );
		$this->assertSame( 'gbc_live_ab12••••••••••', $masked );
		$this->assertStringNotContainsString( 'secret', $masked );
		$this->assertInstanceOf( WP_Error::class, GetBirthChart_Validator::sanitize_api_key( 'not-a-key' ) );
	}
}
