#!/usr/bin/env bash
# EAMN Academy — production build for InterServer shared hosting (no SSH).
# Produces two archives for a split deployment:
#   * eamn-app-<stamp>.tar.gz    — private application (upload + extract to
#                                  /home/euroafri/eamn-academy/, NOT web root)
#   * eamn-public-<stamp>.tar.gz — public/ contents ONLY (extract into the
#                                  existing academy.euroafricamedia.eu docroot)
# + manifest with checksums.
set -euo pipefail
cd "$(dirname "$0")"

APP=eamn-academy
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT_DIR="./dist"
STAGE_ROOT="$(mktemp -d)"
APP_STAGE="$STAGE_ROOT/$APP"          # private app tree
PUB_STAGE="$STAGE_ROOT/webroot"       # what lands inside the web docroot
APP_ARCHIVE="$OUT_DIR/${APP}-app-${STAMP}.tar.gz"
PUB_ARCHIVE="$OUT_DIR/${APP}-public-${STAMP}.tar.gz"
MANIFEST="$OUT_DIR/${APP}-${STAMP}.manifest.txt"

echo "==> Preflight"
command -v php composer node npm tar >/dev/null || { echo "missing tool"; exit 1; }
PHPMODS="$(php -m 2>/dev/null)"
for ext in mbstring openssl pdo pdo_mysql curl fileinfo xml ctype json tokenizer zip gd intl bcmath; do
    echo "$PHPMODS" | grep -qi "^$ext$" || { echo "PHP ext missing: $ext"; exit 1; }
done
php -v | head -1

echo "==> Dev deps for test"
composer install --prefer-dist --optimize-autoloader --no-interaction

echo "==> Test suite"
# artisan's `#!/usr/bin/env php` shebang drops -d flags; call phpunit directly
# so the raised memory_limit (real-PDF test needs >128M) applies.
php -d memory_limit=512M vendor/bin/phpunit

echo "==> Composer (prod deps — strip dev)"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Frontend build"
npm ci
npm run build

echo "==> Stage private application tree"
mkdir -p "$OUT_DIR" "$APP_STAGE" "$PUB_STAGE"
# Copy app minus dev/test artefacts, secrets, and public/ (goes in the web tarball).
tar --exclude='./dist' --exclude='./node_modules' --exclude='./.git' \
    --exclude='./tests' --exclude='./.env' --exclude='./.env.*' \
    --exclude='./storage/framework/cache/*' --exclude='./storage/framework/views/*' \
    --exclude='./storage/framework/sessions/*' --exclude='./storage/framework/testing/*' \
    --exclude='./storage/logs/*' \
    --exclude='./storage/app/*' --exclude='./phpunit.xml' --exclude='./.ai' \
    --exclude='./.agents' --exclude='./.agent' --exclude='./.codex' \
    --exclude='./.gemini' --exclude='./.impeccable' --exclude='./.claude' \
    --exclude='./.DS_Store' --exclude='./.phpunit.result.cache' \
    --exclude='./boost.json' --exclude='./CLAUDE.md' \
    --exclude='./public' --exclude='./vendor' \
    -cf - . | tar -xf - -C "$APP_STAGE"
# Vendor must land under the app dir too.
cp -R vendor "$APP_STAGE/"

echo "==> Stage public webroot contents"
# public/ ships flat — its contents become the docroot.
tar --exclude='./.git' --exclude='./.DS_Store' -cf - -C public . | tar -xf - -C "$PUB_STAGE"

# Ensure runtime dirs exist post-extract (tar drops fully-empty dirs).
for d in storage/framework/{cache/data,views,sessions} storage/logs storage/app/{private,public} bootstrap/cache; do
    mkdir -p "$APP_STAGE/$d"; touch "$APP_STAGE/$d/.gitkeep"
done

echo "==> Env template (private only)"
cp .env.example "$APP_STAGE/.env.production.example"
# Prod-safe defaults baked into the template (overridable on host).
sed -i '' \
    -e 's|^APP_ENV=local|APP_ENV=production|' \
    -e 's|^APP_DEBUG=true|APP_DEBUG=false|' \
    -e 's|^APP_URL=.*|APP_URL=https://academy.euroafricamedia.eu|' \
    "$APP_STAGE/.env.production.example"
# Ensure test gateway can't be selected: PAYMENT_GATEWAY set on host.
grep -q 'PAYMENT_GATEWAY' "$APP_STAGE/.env.production.example" || \
    echo 'PAYMENT_GATEWAY=paypal' >> "$APP_STAGE/.env.production.example"
# APP_BASE_PATH required for the split layout — docroot index.php reads it
# to locate this private tree.
grep -q '^APP_BASE_PATH' "$APP_STAGE/.env.production.example" || \
    echo 'APP_BASE_PATH=/home/euroafri/eamn-academy' >> "$APP_STAGE/.env.production.example"

echo "==> Sanitise: no secrets, no dev artefacts"
find "$APP_STAGE" "$PUB_STAGE" -name '.env*' ! -name '.env.production.example' -delete
find "$APP_STAGE" "$PUB_STAGE" -name '*.sqlite' -o -name 'hot' -o -name 'deploy.token' -o -name 'deploy.done' -delete 2>/dev/null || true

echo "==> Package"
tar -czf "$APP_ARCHIVE" -C "$STAGE_ROOT" "$APP"
tar -czf "$PUB_ARCHIVE" -C "$PUB_STAGE" .

echo "==> Manifest + checksums"
{
    echo "build: $STAMP"
    echo "php: $(php -v | head -1)"
    echo "laravel: $(php artisan --version)"
    echo "layout: split — app => /home/euroafri/eamn-academy/ ; public => academy docroot"
    echo "app_archive: ${APP}-app-${STAMP}.tar.gz"
    echo "public_archive: ${APP}-public-${STAMP}.tar.gz"
    echo "files (app, non-vendor):"
    (cd "$APP_STAGE" && find . -type f ! -path './vendor/*' | sort | head -200 | xargs shasum -a 256)
    echo "files (public):"
    (cd "$PUB_STAGE" && find . -type f | sort | xargs shasum -a 256)
    echo "---"
    echo "app_archive_sha256: $(shasum -a 256 "$APP_ARCHIVE" | awk '{print $1}')"
    echo "public_archive_sha256: $(shasum -a 256 "$PUB_ARCHIVE" | awk '{print $1}')"
} > "$MANIFEST"

rm -rf "$STAGE_ROOT"

echo ""
echo "==> Done"
echo "Private app : $APP_ARCHIVE"
echo "Public web  : $PUB_ARCHIVE"
echo "Manifest    : $MANIFEST"
echo ""
echo "Deploy:"
echo "  1) Extract app tarball into /home/euroafri/eamn-academy/ (File Manager)"
echo "  2) Extract public tarball into the academy docroot (existing FTP home)"
echo "  3) Create /home/euroafri/eamn-academy/.env — MUST set APP_BASE_PATH"
echo "     to the private dir + DB creds + APP_KEY + PayPal sandbox."
echo "Rollback: keep prior archives + DB snapshot; re-extract both and restore DB."
