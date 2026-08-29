# Release checklist

v0.1.0 is a GitHub source release. Do not submit to WordPress.org until a human owner approves submission.

## Version alignment

- [x] Plugin header Version: 0.1.0
- [x] readme.txt Stable tag: 0.1.0
- [x] CHANGELOG.md 0.1.0
- [ ] Git tag: v0.1.0 (create when submitting)

## Production package

```bash
bash scripts/build-release.sh
```

Output: `dist/getbirthchart-0.1.0.zip` (not committed).

## WordPress.org assets (SVN after approval)

- [ ] Screenshot 1: Calculator embedded in a WordPress page
- [ ] Screenshot 2: Big Three result
- [ ] Screenshot 3: Settings → GetBirthChart
- [ ] Screenshot 4: Gutenberg block selector
- [ ] `icon-128x128.png` / `icon-256x256.png`
- [ ] `banner-772x250.png` / `banner-1544x500.png`
- [ ] WordPress.org contributor account matches `Contributors:` in readme.txt

## Before submission

- [ ] Confirm slug `getbirthchart` availability; if taken, use `getbirthchart-calculators`
- [ ] Confirm WordPress.org username for Contributors
- [ ] Re-test on WordPress 6.4 and current WordPress
- [ ] Upload the zip at https://wordpress.org/plugins/developers/add/
- [ ] Do not mark https://getbirthchart.com/developers/ WordPress as Available until a public distribution URL exists
