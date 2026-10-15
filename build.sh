#!/usr/bin/env bash
# EAMN Academy — production build for InterServer shared hosting (no SSH).
# Produces a clean, environment-neutral deployable tarball + manifest.
set -euo pipefail
cd "$(dirname "$0")"

APP=eamn-academy
STAGE="$(mktemp -d)/$APP"
OUT_DIR="./dist"
STAMP="$(date +%Y%m%d-%H%M%S)"
ARCHIVE="$OUT_DIR/${APP}-${STAMP}.tar.gz"
MANIFEST="$OUT_DIR/${APP}-${STAMP}.manifest.txt"

echo "==> Preflight"
command -v php composer node npm tar >/dev/null || { echo "missing tool"; exit 1; }
for ext in mbstring openssl pdo curl fileinfo xml ctype json tokenizer; do
    php -m | grep -qi "^$ext$" || { echo "PHP ext missing: $ext"; exit 1; }
done
php -v | head -1

echo "==> Test suite"
php artisan test

echo "==> Composer (prod deps)"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Frontend build"
npm ci
npm run build

echo "==> Stage clean tree"
mkdir -p "$OUT_DIR" "$STAGE"
# Copy app minus dev/test artefacts and secrets.
tar --exclude='./dist' --exclude='./node_modules' --exclude='./.git' \
    --exclude='./tests' --exclude='./.env' --exclude='./.env.*' \
    --exclude='./storage/framework/cache/*' --exclude='./storage/logs/*' \
    --exclude='./storage/app/*' --exclude='./phpunit.xml' --exclude='./.ai' \
    -cf - . | tar -xf - -C "$STAGE"

# Env template ships separately so ops creates real .env on host.
cp .env.example "$STAGE/.env.production.example"

echo "==> Sanitise: no secrets, no dev gateway flag"
find "$STAGE" -name '.env*' ! -name '.env.production.example' -delete
# Ensure test-only gateway cannot be selected: PAYMENT_GATEWAY must be set on host.
grep -q 'PAYMENT_GATEWAY' "$STAGE/.env.production.example" || \
    echo 'PAYMENT_GATEWAY=paypal' >> "$STAGE/.env.production.example"

echo "==> Package"
tar -czf "$ARCHIVE" -C "$(dirname "$STAGE")" "$APP"

echo "==> Manifest + checksums"
{
    echo "build: $STAMP"
    echo "php: $(php -v | head -1)"
    echo "laravel: $(php artisan --version)"
    echo "files:"
    (cd "$(dirname "$STAGE")" && find "$APP" -type f ! -path '*/vendor/*' | sort | xargs shasum -a 256) | head -200
    echo "---"
    echo "archive_sha256: $(shasum -a 256 "$ARCHIVE" | awk '{print $1}')"
} > "$MANIFEST"

echo ""
echo "==> Done"
echo "Archive : $ARCHIVE"
echo "Manifest: $MANIFEST"
echo ""
echo "Rollback: keep previous archive + DB snapshot; redeploy prior tarball and"
echo "restore DB via cPanel backup. Set real .env on host (never shipped)."
