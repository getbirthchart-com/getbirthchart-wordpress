# Security Policy

## Reporting a vulnerability

Do not disclose exploitable security issues in a public GitHub issue.

Report vulnerabilities privately through the repository's
[GitHub Security Advisory form](https://github.com/getbirthchart-com/getbirthchart-wordpress/security/advisories/new).
Include the affected version, a concise reproduction, impact, and a suggested
fix if available. Do not include live API keys or personal birth data in the
report.

## Plugin security principles

The plugin must:

- keep the site owner's API key on the server
- never expose the API key in frontend HTML, JavaScript, shortcodes, or REST responses
- never put the API key in a URL or query string
- never log the full API key
- require `manage_options` to view or change API configuration
- protect settings mutations with a nonce
- call only fixed GetBirthChart endpoints
- reject public attempts to supply `url`, `base_url`, `endpoint`, or `host`
- treat API responses as untrusted data
- escape frontend output
- avoid telemetry, analytics, and phone-home behavior

## Birth data

Birth date, birth time, and birth place can be personal information.

v0.1 does not store visitor birth data or calculation results in the WordPress
database. Calculation requests are proxied to GetBirthChart and discarded
after the response is returned.

## Supported versions

Until `1.0.0`, security fixes should target the latest published minor line unless a separate support policy is announced.
