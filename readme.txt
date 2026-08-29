=== GetBirthChart – Birth Chart Calculators ===
Contributors: getbirthchart
Tags: astrology, birth-chart, calculator, moon-sign, rising-sign
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed GetBirthChart-powered Birth Chart, Moon Sign, Rising Sign, and Big Three calculators in WordPress.

== Description ==

GetBirthChart – Birth Chart Calculators lets you embed calculators on WordPress pages and posts. Site visitors enter a birth date, optional birth time, and birth place. The plugin sends that information to the GetBirthChart public API from your WordPress server and displays a concise result.

Supported calculators:

* Birth Chart
* Moon Sign
* Rising Sign
* Big Three

The plugin does not calculate astrology in PHP. It uses your GetBirthChart developer API key on the server so the key is not exposed in the browser.

If a visitor does not know their birth time, the plugin passes GetBirthChart’s official unknown-time flag. It does not substitute noon or invent a Rising sign.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/getbirthchart` or install it from a zip file.
2. Activate the plugin through the Plugins screen.
3. Go to Settings → GetBirthChart.
4. Enter a GetBirthChart API key from https://getbirthchart.com/developers and save.
5. Embed a calculator with a shortcode or the GetBirthChart Calculator block.

== Frequently Asked Questions ==

= Where do I get an API key? =

Create one in the GetBirthChart developer dashboard: https://getbirthchart.com/developers

= Does this plugin store birth data? =

No. Visitor birth date, birth time, birth place, and calculation results are not saved in the WordPress database.

= What happens if someone does not know their birth time? =

The unknown-time option is sent to GetBirthChart. Rising sign is not calculated without a birth time.

= Can visitors see my API key? =

No. The key is stored as a WordPress option and used only in server-side requests.

== Screenshots ==

1. Calculator embedded in a WordPress page.
2. Big Three result.
3. Settings → GetBirthChart.
4. Gutenberg block selector.

== Changelog ==

= 0.1.0 =
* Birth Chart, Moon Sign, Rising Sign, and Big Three calculators.
* API key settings and a server-side connection test.
* Secure server-side GetBirthChart API proxy.
* Unknown birth-time handling.

== Upgrade Notice ==

= 0.1.0 =
Initial release.

== External Services ==

This plugin connects to GetBirthChart to calculate astrology results.

Service: GetBirthChart
Service URL: https://getbirthchart.com/

What is sent and when:

* When a visitor submits a calculator, the plugin sends birth date, optional birth time or an unknown-time flag, and birth place to GetBirthChart.
* Birth place is first resolved through GetBirthChart’s places search so the calculation can use coordinates and timezone.
* The site owner’s API key is sent in an Authorization header from the WordPress server. It is not sent from the visitor’s browser.
* Connection tests from Settings → GetBirthChart send an authenticated request to the GetBirthChart natal API to confirm the stored key. That test does not run a full chart calculation.

The plugin does not send installation telemetry or unrelated site data.

Links:

* Privacy Policy: https://getbirthchart.com/privacy/
* Methodology: https://getbirthchart.com/methodology/
* Developers: https://getbirthchart.com/developers
