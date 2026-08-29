<?php
/**
 * WordPress.org readme and version alignment tests.
 *
 * @package GetBirthChart
 */

use PHPUnit\Framework\TestCase;

class ReadmeTest extends TestCase {

	public function test_readme_has_required_wordpress_org_sections(): void {
		$readme = file_get_contents( dirname( __DIR__ ) . '/readme.txt' );
		$this->assertNotFalse( $readme );
		foreach ( array(
			'=== GetBirthChart – Birth Chart Calculators ===',
			'Contributors:',
			'Requires at least: 6.4',
			'Tested up to:',
			'Requires PHP: 8.1',
			'Stable tag: 0.1.0',
			'License: GPLv2 or later',
			'== Description ==',
			'== Installation ==',
			'== Frequently Asked Questions ==',
			'== Screenshots ==',
			'== Changelog ==',
			'== Upgrade Notice ==',
			'== External Services ==',
			'https://getbirthchart.com/privacy/',
			'https://getbirthchart.com/developers/',
			'https://getbirthchart.com/methodology/',
		) as $needle ) {
			$this->assertStringContainsString( $needle, $readme );
		}
		$this->assertStringNotContainsString( 'https://getbirthchart.com/terms/', $readme );
		$this->assertStringNotContainsString( 'synastry', strtolower( $readme ) );
		$this->assertStringNotContainsString( 'AI reading', $readme );
	}

	public function test_plugin_version_is_aligned(): void {
		$header = file_get_contents( dirname( __DIR__ ) . '/getbirthchart.php' );
		$this->assertNotFalse( $header );
		$this->assertStringContainsString( '* Version: 0.1.0', $header );
		$this->assertStringContainsString( "define( 'GETBIRTHCHART_VERSION', '0.1.0' );", $header );
		$this->assertStringContainsString( 'License: GPL-2.0-or-later', $header );
		$this->assertStringContainsString( 'Text Domain: getbirthchart', $header );
	}
}
