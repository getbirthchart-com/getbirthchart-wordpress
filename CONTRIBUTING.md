# Contributing

Thank you for contributing to the official GetBirthChart WordPress plugin.

## Development

Requirements:

```text
PHP >= 8.1
Composer
```

Install:

```bash
composer install
```

Run checks:

```bash
vendor/bin/phpcs
vendor/bin/phpunit
composer audit
```

Composer is a development dependency. Production WordPress sites do not need
Composer to install the plugin zip.

## Scope

This repository is a thin WordPress integration layer over the GetBirthChart
developer API.

Do not implement Swiss Ephemeris or astrology calculation rules here.

Do not call private/internal engine hosts. Use the public API:

```text
https://getbirthchart.com/api
```

The API base URL may be overridden in development with the
`getbirthchart_api_base_url` filter. Public calculator requests cannot set it.

## Unknown birth time

Changes touching unknown birth-time behavior require dedicated tests.

Never:

- assume noon
- invent an Ascendant
- invent houses
- flatten ambiguous Moon results into false precision

Rising sign calculation must not run when birth time is unknown.

## Testing

No normal unit test may require the production GetBirthChart API.

Use the `getbirthchart_pre_http_request` filter or the test HTTP stub.

## Pull requests

- keep the public v0.1 calculator set to Birth Chart, Moon Sign, Rising Sign, and Big Three
- keep user-facing strings translatable with the `getbirthchart` text domain
- do not add synastry, forecast, AI reading, or PDF features in this line
