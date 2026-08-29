<?php
/**
 * Shortcode allowlist tests.
 *
 * @package GetBirthChart
 */

use PHPUnit\Framework\TestCase;

class ShortcodeTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['getbirthchart_test_options'] = array();
		$GLOBALS['getbirthchart_test_can']     = false;
	}

	public function test_default_type_is_birth_chart(): void {
		$this->assertSame( 'birth-chart', GetBirthChart_Settings::defaults()['default_type'] );
	}

	public function test_unknown_type_is_rejected_without_using_it_as_a_path(): void {
		$this->assertSame( '', GetBirthChart_Validator::sanitize_type( '/v1/charts/synastry' ) );
		$this->assertSame( '', GetBirthChart_Validator::sanitize_type( 'https://evil.example' ) );
	}

	public function test_type_labels_cover_v0_1_calculators_only(): void {
		$this->assertSame( 'Birth Chart', GetBirthChart_Shortcodes::type_label( 'birth-chart' ) );
		$this->assertSame( 'Moon Sign', GetBirthChart_Shortcodes::type_label( 'moon-sign' ) );
		$this->assertSame( 'Rising Sign', GetBirthChart_Shortcodes::type_label( 'rising-sign' ) );
		$this->assertSame( 'Big Three', GetBirthChart_Shortcodes::type_label( 'big-three' ) );
	}
}
