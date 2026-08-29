# GetBirthChart – Birth Chart Calculators

Official WordPress plugin for embedding GetBirthChart-powered calculators in pages and posts.

The plugin is a thin, secure integration layer. It does not reimplement Swiss Ephemeris or astrology logic in PHP. Calculations run on the GetBirthChart public API using the site owner's developer API key.

```text
WordPress plugin
    ↓
GetBirthChart public API
    ↓
GetBirthChart calculation engine
```

## Features

- Birth Chart calculator
- Moon Sign calculator
- Rising Sign calculator
- Big Three calculator
- Shortcodes and a Gutenberg block
- Server-side API-key storage
- Unknown birth-time handling without guessing Rising, houses, or noon
- No visitor birth-data storage in WordPress
- No telemetry

## Requirements

- WordPress 6.4 or later
- PHP 8.1 or later
- A GetBirthChart developer API key (`gbc_live_…`)

## Installation

1. Install the plugin from a release zip or by cloning this repository into `wp-content/plugins/getbirthchart`.
2. Activate **GetBirthChart – Birth Chart Calculators**.
3. Open **Settings → GetBirthChart**.
4. Paste your API key and save.
5. Use **Test connection** to confirm the stored key.

The plugin is inert until an API key is saved and a calculator is embedded. Activation does not call the API, create pages, or send telemetry.

## API key setup

Create a key at [https://getbirthchart.com/developers](https://getbirthchart.com/developers).

The key stays on the WordPress server. It is never printed in frontend HTML, JavaScript, shortcodes, or public REST responses. After save, the settings screen shows a masked value such as `gbc_live_ab12••••••••••`.

## Shortcodes

Default (Birth Chart):

```text
[getbirthchart]
```

Other v0.1 types:

```text
[getbirthchart type="moon-sign"]
[getbirthchart type="rising-sign"]
[getbirthchart type="big-three"]
```

You can also insert the **GetBirthChart Calculator** block and choose the type from the sidebar.

## Screenshots

Screenshot assets are not bundled in v0.1. For WordPress.org, capture:

1. Calculator embedded in a WordPress page
2. Big Three result
3. Settings → GetBirthChart
4. Gutenberg block selector

## Development

```bash
composer install
vendor/bin/phpcs
vendor/bin/phpunit
composer audit
```

Composer is for development and CI. Runtime distribution uses the plugin PHP files and does not require `vendor/` on production WordPress installs.

## Security

- Settings require `manage_options` and a nonce.
- The public calculator posts to `/wp-json/getbirthchart/v1/calculate`.
- That route is not a generic proxy: calculator type is allowlisted, and `url` / `base_url` / `endpoint` / `host` are rejected.
- Site-side rate limiting uses a hashed IP plus calculator type.
- HTTP timeouts are 15 seconds. Redirects are disabled.

See [SECURITY.md](SECURITY.md).

## Privacy / external service

Submitting a calculator sends birth date, optional birth time or the official unknown-time flag, and birth place to GetBirthChart so the chart can be calculated. Place lookup uses GetBirthChart’s public places search on the same API host so latitude, longitude, and timezone can be resolved. The site owner’s API key is attached only on the server.

The plugin does not store those inputs in WordPress.

## Uninstall

Deactivation keeps settings. Uninstall deletes plugin-owned options, including the API key, so the secret is not left in the database.

## Links

- [GetBirthChart](https://getbirthchart.com/)
- [Developers](https://getbirthchart.com/developers)
- [Methodology](https://getbirthchart.com/methodology/)
- [Privacy Policy](https://getbirthchart.com/privacy/)
