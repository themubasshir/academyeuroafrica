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

# Publish Livewire's runtime. The CSP-safe build is what @livewireScripts loads
# from the static URL /vendor/livewire/livewire.csp.js, so it must exist in
# public/vendor before staging. Deterministic here rather than relying on the
# composer post-install/post-update hook.
php artisan livewire:publish --assets --no-interaction

echo "==> Stage private application tree"
mkdir -p "$OUT_DIR" "$APP_STAGE" "$PUB_STAGE"
# Copy app minus dev/test artefacts, secrets, and public/ (goes in the web tarball).
tar --exclude='./dist' --exclude='./node_modules' --exclude='./.git' \
    --exclude='./tests' --exclude='./.env' --exclude='./.env.*' \
    --exclude='./storage/framework/cache/*' --exclude='./storage/framework/views/*' \
    --exclude='./storage/framework/sessions/*' --exclude='./storage/framework/testing/*' \
    --exclude='./storage/logs/*' \
    --exclude='./storage/app/*' --exclude='./storage/qa' --exclude='./database/*.sqlite*' \
    --exclude='./acceptance-screenshots*' --exclude='./phpunit.xml' --exclude='./.ai' \
    --exclude='./.agents' --exclude='./.agent' --exclude='./.codex' \
    --exclude='./.gemini' --exclude='./.impeccable' --exclude='./.claude' \
    --exclude='./.DS_Store' --exclude='./.phpunit.result.cache' \
    --exclude='./boost.json' --exclude='./CLAUDE.md' --exclude='./AGENTS.md' \
    --exclude='./.cursor*' --exclude='./editorconfig' \
    --exclude='./public' --exclude='./vendor' \
    -cf - . | tar -xf - -C "$APP_STAGE"
# Vendor must land under the app dir too.
cp -R vendor "$APP_STAGE/"

echo "==> Stage public webroot contents"
# public/ ships flat — its contents become the docroot. public/vendor holds the
# published CSP-safe Livewire runtime (@livewireScripts serves it via static URL
# /vendor/livewire/*.js), so it MUST ship in the public archive.
tar --exclude='./.git' --exclude='./.DS_Store' -cf - -C public . | tar -xf - -C "$PUB_STAGE"

# Ensure runtime dirs exist post-extract (tar drops fully-empty dirs).
# framework/cache/locks is required by the deploy runner's file-store lock.
for d in storage/framework/{cache/data,cache/locks,views,sessions} storage/logs \
        storage/app/{private,public} bootstrap/cache; do
    mkdir -p "$APP_STAGE/$d"; touch "$APP_STAGE/$d/.gitkeep"
done

echo "==> Env template (private only)"
cp .env.example "$APP_STAGE/.env.production.example"
# Prod-safe defaults baked into the template (overridable on host).
sed -i '' \
    -e 's|^APP_ENV=local|APP_ENV=production|' \
    -e 's|^APP_DEBUG=true|APP_DEBUG=false|' \
    -e 's|^APP_URL=.*|APP_URL=https://academy.euroafricamedia.eu|' \
    -e 's|^DB_CONNECTION=sqlite|DB_CONNECTION=mysql|' \
    -e 's|^# DB_HOST=127.0.0.1|DB_HOST=127.0.0.1|' \
    -e 's|^# DB_PORT=3306|DB_PORT=3306|' \
    -e 's|^# DB_DATABASE=laravel|DB_DATABASE=euroafri_academycli|' \
    -e 's|^# DB_USERNAME=root|DB_USERNAME=euroafri_academycli|' \
    -e 's|^# DB_PASSWORD=$|DB_PASSWORD=|' \
    "$APP_STAGE/.env.production.example"
# Ensure test gateway can't be selected: PAYMENT_GATEWAY set on host.
grep -q '^PAYMENT_GATEWAY=' "$APP_STAGE/.env.production.example" || \
    echo 'PAYMENT_GATEWAY=paypal' >> "$APP_STAGE/.env.production.example"
# APP_BASE_PATH required for the split layout — docroot index.php reads it
# to locate this private tree. Match only an uncommented assignment, otherwise
# the commented hint in .env.example makes grep succeed and no live line is added.
grep -q '^APP_BASE_PATH=' "$APP_STAGE/.env.production.example" || \
    echo 'APP_BASE_PATH=/home/euroafri/eamn-academy' >> "$APP_STAGE/.env.production.example"
# The commented placeholder must not survive next to the live value.
grep -q '^# APP_BASE_PATH=' "$APP_STAGE/.env.production.example" && \
    sed -i '' '/^# APP_BASE_PATH=/d' "$APP_STAGE/.env.production.example"

echo "==> Verify env template (prod-safe)"
grep -q '^APP_ENV=production$' "$APP_STAGE/.env.production.example" || { echo "FATAL: APP_ENV not production"; exit 1; }
grep -q '^APP_DEBUG=false$' "$APP_STAGE/.env.production.example" || { echo "FATAL: APP_DEBUG not false"; exit 1; }
grep -q '^DB_CONNECTION=mysql$' "$APP_STAGE/.env.production.example" || { echo "FATAL: DB_CONNECTION not mysql"; exit 1; }
grep -q '^APP_BASE_PATH=/home/euroafri/eamn-academy$' "$APP_STAGE/.env.production.example" || { echo "FATAL: APP_BASE_PATH missing"; exit 1; }
[ "$(grep -c '^APP_BASE_PATH=' "$APP_STAGE/.env.production.example")" = "1" ] || { echo "FATAL: duplicate APP_BASE_PATH"; exit 1; }
! grep -q '^DB_CONNECTION=sqlite$' "$APP_STAGE/.env.production.example" || { echo "FATAL: sqlite in prod template"; exit 1; }

echo "==> Sanitise: no secrets, no dev artefacts"
# NOTE: -o needs explicit grouping, otherwise -delete binds only to the last
# predicate and every earlier match survives into the archives.
find "$APP_STAGE" "$PUB_STAGE" -type f -name '.env*' ! -name '.env.production.example' -delete
find "$APP_STAGE" "$PUB_STAGE" -type f \( -name '*.sqlite' -o -name '*.sqlite-journal' \
    -o -name 'hot' -o -name 'deploy.token' -o -name 'deploy.done' -o -name 'deploy.done.*' \
    -o -name '.DS_Store' \) -delete

echo "==> Verify staged contents"
test -f "$PUB_STAGE/vendor/livewire/livewire.csp.js" || {
    echo "FATAL: CSP Livewire runtime missing from public staging"; exit 1; }
test -f "$PUB_STAGE/vendor/livewire/livewire.js" || {
    echo "FATAL: Livewire runtime missing from public staging"; exit 1; }
test -f "$PUB_STAGE/build/manifest.json" || { echo "FATAL: Vite manifest missing"; exit 1; }
test -f "$APP_STAGE/artisan" || { echo "FATAL: artisan missing from app staging"; exit 1; }
test -f "$APP_STAGE/vendor/autoload.php" || { echo "FATAL: composer autoloader missing"; exit 1; }
test -d "$APP_STAGE/vendor/livewire/livewire" || { echo "FATAL: livewire package missing"; exit 1; }
[ ! -e "$APP_STAGE/database/database.sqlite" ] || { echo "FATAL: dev SQLite DB staged"; exit 1; }
if find "$APP_STAGE" "$PUB_STAGE" \( -name '.env' -o -name '.env.*' \) ! -name '.env.production.example' -print -quit | grep -q .; then
    echo "FATAL: .env present in staging"; exit 1
fi
for cmd in catalogue:import catalogue:variants; do
    php artisan list --raw | grep -q "^$cmd " || { echo "FATAL: $cmd missing"; exit 1; }
done

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
