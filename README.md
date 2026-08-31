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

A GetBirthChart API key is required. This plugin is not affiliated with or endorsed by WordPress.

## Features

- Birth Chart calculator
- Moon Sign calculator
- Rising Sign calculator
- Big Three calculator
- Shortcodes and a Gutenberg block
- Server-side API-key storage
- Unknown birth-time handling without guessing Rising, houses, or noon
- No visitor birth-data storage in the WordPress database
- No telemetry

## `gbc-astro` 1.13.0 compatibility

The current GetBirthChart API contract is backed by `gbc-astro` 1.13.0. This
plugin uses the API's basic natal calculation path and sends the resolved local
date, optional local time, timezone, latitude, and longitude. It does not expose
the core's Sidereal, Custom aspect, Mean Node, Lilith, or advanced house-system
settings in the WordPress UI. Those are core/API capabilities, not plugin
features.

The API's natal schema is `1.9.0`. The plugin does not require a
`calculationHash` field in the HTTP response.

## Requirements

- WordPress 6.4 or later
- PHP 8.1 or later
- A GetBirthChart developer API key (`gbc_live_…`)

## Installation

### From this GitHub repository

This is the source repository. Install by cloning into `wp-content/plugins/getbirthchart`, or by building the production zip:

```bash
bash scripts/build-release.sh
```

Then upload `dist/getbirthchart-0.1.0.zip` in **Plugins → Add Plugin → Upload Plugin**.

### WordPress.org

This plugin is **not listed on WordPress.org yet**. Do not expect an in-dashboard install from the WordPress Plugin Directory until that listing exists.

## API key setup

Create a key at [https://getbirthchart.com/developers/](https://getbirthchart.com/developers/).

1. Activate **GetBirthChart – Birth Chart Calculators**.
2. Open **Settings → GetBirthChart**.
3. Paste your API key and save.
4. Use **Test connection** to confirm the stored key.

The plugin is inert until an API key is saved and a calculator is embedded. Activation does not call the API, create pages, or send telemetry.

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

## Unknown birth time

Birth time is optional for Birth Chart, Moon Sign, and Big Three calculators.
When the visitor checks **I don't know my birth time**, the plugin sends
`unknown_time=true` and `local_time=null`. The backend owns the labeled
unknown-time assessment; this plugin never substitutes noon, invents a Rising
sign, or displays houses that require an exact birth time. The Rising Sign
calculator requires a time and rejects the unknown-time option.

## Troubleshooting

- **Calculator unavailable:** confirm an API key is saved under **Settings →
  GetBirthChart**, then use **Test connection**. Also confirm the WordPress
  server can reach `https://getbirthchart.com/api` over HTTPS.
- **Place not found:** enter a more specific place name. Place resolution is
  performed by the GetBirthChart places endpoint; the plugin does not geocode
  locally.
- **Rising sign unavailable:** provide a reliable local birth time. Unknown-time
  requests intentionally cannot calculate an Ascendant.
- **REST request rejected:** load the calculator from the WordPress page so the
  current WordPress REST nonce is available; the endpoint is not a public
  unauthenticated proxy.

## Screenshots

Screenshot files are not bundled in the plugin zip. WordPress.org directory screenshots belong in the SVN `assets/` folder after approval:

1. Calculator embedded in a WordPress page
2. Big Three result
3. Settings → GetBirthChart
4. Gutenberg block selector

Directory icon and banner files (`icon-128x128.png`, `icon-256x256.png`, `banner-772x250.png`, `banner-1544x500.png`) are also a post-approval SVN task.

## Development

```bash
composer install
vendor/bin/phpcs
vendor/bin/phpunit
composer audit
bash scripts/build-release.sh
```

Composer is for development and CI. Runtime distribution uses the plugin PHP files and does not require `vendor/` on production WordPress installs.

## Security

- Settings require `manage_options` and a nonce.
- The public calculator posts to `/wp-json/getbirthchart/v1/calculate`.
- That route is not a generic proxy: calculator type is allowlisted, and `url` / `base_url` / `endpoint` / `host` / `scheme` / `headers` are rejected.
- Site-side rate limiting uses a hashed IP plus calculator type.
- HTTP timeouts are 15 seconds. Redirects are disabled.

See [SECURITY.md](SECURITY.md).

## Privacy / external service

Submitting a calculator sends birth date, optional birth time or the official unknown-time flag, and birth place to GetBirthChart so the chart can be calculated. Place lookup uses GetBirthChart’s public places search on the same API host so latitude, longitude, and timezone can be resolved. The site owner’s API key is attached only on the server.

This plugin does not save those inputs in the WordPress database. GetBirthChart’s own privacy practices are described at [https://getbirthchart.com/privacy/](https://getbirthchart.com/privacy/).

## Uninstall

Deactivation keeps settings. Uninstall deletes plugin-owned options, including the API key, so the secret is not left in the database.

## Links

- [GetBirthChart](https://getbirthchart.com/)
- [Developers](https://getbirthchart.com/developers/)
- [Methodology](https://getbirthchart.com/methodology/)
- [Privacy Policy](https://getbirthchart.com/privacy/)
- [Core 1.13.0 GitHub release](https://github.com/getbirthchart-com/gbc-astro-engine/releases/tag/v1.13.0)
- [Core 1.13.0 on PyPI](https://pypi.org/project/gbc-astro/1.13.0/)
- [Core concept DOI](https://doi.org/10.5281/zenodo.22052875)
- [Core version DOI](https://doi.org/10.5281/zenodo.22206006)
