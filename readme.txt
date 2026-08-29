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

GetBirthChart – Birth Chart Calculators lets you embed calculators on WordPress pages and posts. A GetBirthChart developer API key is required. Calculations are processed by the GetBirthChart API; this plugin does not calculate astrology in PHP.

Site visitors enter a birth date, optional birth time, and birth place. The plugin sends that information from your WordPress server to GetBirthChart and displays a concise result.

Supported calculators:

* Birth Chart
* Moon Sign
* Rising Sign
* Big Three

If a visitor does not know their birth time, the plugin passes GetBirthChart’s official unknown-time flag. It does not substitute noon or invent a Rising sign.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/getbirthchart`, or install the plugin zip from Plugins → Add Plugin → Upload Plugin.
2. Activate the plugin through the Plugins screen.
3. Go to Settings → GetBirthChart.
4. Enter a GetBirthChart API key from https://getbirthchart.com/developers/ and save.
5. Embed a calculator with a shortcode or the GetBirthChart Calculator block.

== Frequently Asked Questions ==

= Where do I get an API key? =

Create one in the GetBirthChart developer dashboard: https://getbirthchart.com/developers/

= Does this plugin store birth data in WordPress? =

No. This plugin does not save visitor birth date, birth time, birth place, or calculation results in the WordPress database. GetBirthChart’s handling of data it receives is described in its Privacy Policy.

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
* GetBirthChart API-key settings and a server-side connection test.
* Secure server-side GetBirthChart API proxy.
* Unknown birth-time handling.

== Upgrade Notice ==

= 0.1.0 =
Initial release.

== External Services ==

This plugin connects to the GetBirthChart API to calculate astrology results.

When a visitor uses a GetBirthChart calculator, information entered into the calculator — such as birth date, birth time, and birth place — may be sent to GetBirthChart for processing. Birth place is resolved through GetBirthChart’s places search so the calculation can use coordinates and timezone.

The site owner's GetBirthChart API key is used server-side to authenticate natal calculation requests and is not intentionally exposed to site visitors.

Connection tests from Settings → GetBirthChart send an authenticated request to the GetBirthChart natal API to confirm the stored key. That test does not run a full chart calculation.

The plugin does not send installation telemetry or unrelated site data.

GetBirthChart:
https://getbirthchart.com/

Privacy Policy:
https://getbirthchart.com/privacy/

Developers:
https://getbirthchart.com/developers/

Methodology:
https://getbirthchart.com/methodology/
