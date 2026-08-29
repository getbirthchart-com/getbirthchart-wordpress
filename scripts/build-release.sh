#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

version="$(grep -E "define\( 'GETBIRTHCHART_VERSION'" getbirthchart.php | sed -E "s/.*'([0-9.]+)'.*/\1/")"
if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	echo "Could not determine plugin version." >&2
	exit 1
fi

dist="$root/dist"
staging="$dist/staging"
plugin_dir="$staging/getbirthchart"
zip_path="$dist/getbirthchart-${version}.zip"

rm -rf "$staging" "$zip_path"
mkdir -p "$plugin_dir"

copy_paths=(
	getbirthchart.php
	readme.txt
	uninstall.php
	LICENSE
	includes
	admin
	public
	assets
	languages
)

for path in "${copy_paths[@]}"; do
	cp -R "$root/$path" "$plugin_dir/"
done

# Drop directory indexes from being the only runtime files; keep them.
find "$plugin_dir" -name '.DS_Store' -delete

(
	cd "$staging"
	zip -r -X -q "$zip_path" getbirthchart
)

echo "Built $zip_path"
unzip -l "$zip_path"

echo
echo "Inspecting packaged files..."
tmp="$(mktemp -d)"
unzip -q "$zip_path" -d "$tmp"

top="$(find "$tmp" -mindepth 1 -maxdepth 1 -type d -print)"
if [[ "$(basename "$top")" != "getbirthchart" ]]; then
	echo "ZIP top-level directory must be getbirthchart/" >&2
	exit 1
fi
if [[ ! -f "$tmp/getbirthchart/getbirthchart.php" ]]; then
	echo "Missing getbirthchart/getbirthchart.php in ZIP." >&2
	exit 1
fi

if find "$tmp/getbirthchart" -name '.env' -o -name '.env.*' | grep -q .; then
	echo "ZIP contains an .env file." >&2
	exit 1
fi

if grep -R --include='*.php' --include='*.js' -nE 'var_dump\(|console\.log' "$tmp/getbirthchart" >/dev/null; then
	echo "Debug logging found in production ZIP." >&2
	grep -R --include='*.php' --include='*.js' -nE 'var_dump\(|console\.log' "$tmp/getbirthchart" || true
	exit 1
fi

file_count="$(find "$tmp/getbirthchart" -type f | wc -l | tr -d ' ')"
size="$(wc -c < "$zip_path" | tr -d ' ')"
echo "Packaged files: $file_count"
echo "ZIP size bytes: $size"

rm -rf "$tmp" "$staging"
echo "OK $zip_path"
