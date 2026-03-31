#!/usr/bin/env bash
#
# BNote Next Generation – Build script
#
# Creates a single folder you can upload (api + frontend + lang + config).
# For local debug use: cd frontend && npm run dev (see README).
#
# Usage:
#   ./build.sh [--out DIR] [--verify-only] [--with-developer-tools|--without-developer-tools]
#     --verify-only: only check existing build output, do not build
#     --with-developer-tools / --without-developer-tools: override NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS
#
# Production bundle (default): do not set NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS and do not pass --with-developer-tools — frontend prunes
#   /debug and /developer from out/, and this script removes api/debug/ from the deploy copy.
# Developer/staging bundle: pass --with-developer-tools (or set NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS=1)
#   so Next keeps debug routes and api/debug/ is included.
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT_DIR="build"
VERIFY_ONLY="false"
DEVELOPER_TOOLS_OVERRIDE=""
DEPLOY_CONFIG_FILE="$SCRIPT_DIR/.deploy.env"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --out)
      OUT_DIR="$2"
      shift 2
      ;;
    --verify-only)
      VERIFY_ONLY="true"
      shift
      ;;
    --with-developer-tools)
      DEVELOPER_TOOLS_OVERRIDE="1"
      shift
      ;;
    --without-developer-tools)
      DEVELOPER_TOOLS_OVERRIDE="0"
      shift
      ;;
    *)
      echo "Unknown option: $1"
      echo "Usage: $0 [--out DIR] [--verify-only] [--with-developer-tools|--without-developer-tools]"
      exit 1
      ;;
  esac
done

if [[ -n "$DEVELOPER_TOOLS_OVERRIDE" ]]; then
  export NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS="$DEVELOPER_TOOLS_OVERRIDE"
fi

DEVELOPER_TOOLS_ENABLED="false"
if [[ "${NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS:-}" == "1" ]]; then
  DEVELOPER_TOOLS_ENABLED="true"
fi

echo "=== BNote Next Generation build ==="
echo "Output folder: $OUT_DIR"
echo "Verify only: $VERIFY_ONLY"
echo "Developer tools: $DEVELOPER_TOOLS_ENABLED"
echo ""

DEPLOY_DIR="$SCRIPT_DIR/$OUT_DIR/bnote-next-generation"

if [[ "$VERIFY_ONLY" == "true" ]]; then
  if [[ ! -d "$DEPLOY_DIR" ]]; then
    echo "Build output not found: $DEPLOY_DIR"
    echo "Run ./build.sh --out $OUT_DIR first."
    exit 1
  fi
  if [[ ! -d "$DEPLOY_DIR/api/vendor/phpmailer/phpmailer" ]]; then
    echo "ERROR: api/vendor/phpmailer missing in build. Run a full ./build.sh (Composer install must succeed)."
    exit 1
  fi
  if [[ "$DEVELOPER_TOOLS_ENABLED" == "true" ]]; then
    if [[ ! -d "$DEPLOY_DIR/api/debug" ]]; then
      echo "ERROR: expected api/debug in developer-tools build output."
      exit 1
    fi
  else
    if [[ -d "$DEPLOY_DIR/api/debug" ]]; then
      echo "ERROR: api/debug must not be present in production build output."
      exit 1
    fi
    if [[ -f "$DEPLOY_DIR/api/mail_test_send.php" ]] || [[ -f "$DEPLOY_DIR/api/mail_config_check.php" ]] || [[ -f "$DEPLOY_DIR/api/mail_debug.php" ]]; then
      echo "ERROR: Legacy dev mail scripts at api/ root must not be present (use api/debug/ only in dev bundles)."
      exit 1
    fi
  fi
  if [[ "$DEVELOPER_TOOLS_ENABLED" == "true" ]]; then
    echo "Build output OK: $DEPLOY_DIR (vendor + api/debug present)"
  else
    echo "Build output OK: $DEPLOY_DIR (vendor + no api/debug)"
  fi
  exit 0
fi

# 0. PHP API dependencies (PHPMailer) — required before bundling api/
echo "Installing API dependencies (Composer)..."
API_DIR="$SCRIPT_DIR/api"
PHP_BIN=""
if command -v php >/dev/null 2>&1; then
  PHP_BIN="$(command -v php)"
else
  for candidate in \
    /Applications/MAMP/bin/php/php8.4.15/bin/php \
    /Applications/MAMP/bin/php/php8.3.28/bin/php \
    /Applications/MAMP/bin/php/php8.3.27/bin/php \
    /usr/bin/php; do
    if [[ -x "$candidate" ]]; then
      PHP_BIN="$candidate"
      break
    fi
  done
fi

cd "$API_DIR"
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction
elif [[ -n "$PHP_BIN" ]] && [[ -f "$API_DIR/composer.phar" ]]; then
  "$PHP_BIN" composer.phar install --no-dev --optimize-autoloader --no-interaction
elif [[ -n "$PHP_BIN" ]]; then
  echo "Downloading Composer to api/composer.phar (one-time)..."
  "$PHP_BIN" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  "$PHP_BIN" composer-setup.php --install-dir=. --filename=composer.phar
  rm -f composer-setup.php
  "$PHP_BIN" composer.phar install --no-dev --optimize-autoloader --no-interaction
else
  echo "ERROR: Need Composer on PATH, or PHP + api/composer.phar."
  echo "  Install PHP and run: cd api && php -r \"copy('https://getcomposer.org/installer','composer-setup.php');\" && php composer-setup.php --install-dir=. --filename=composer.phar && php composer.phar install --no-dev"
  exit 1
fi
cd "$SCRIPT_DIR"
if [[ ! -f "$API_DIR/vendor/autoload.php" ]]; then
  echo "ERROR: api/vendor/autoload.php missing after composer install."
  exit 1
fi

# 1. Build frontend
echo "Building frontend..."
cd "$SCRIPT_DIR/frontend"
if ! npm run build; then
  echo "Frontend build failed."
  exit 1
fi
cd "$SCRIPT_DIR"

if [[ ! -d "frontend/out" ]]; then
  echo "Frontend build did not produce frontend/out/"
  exit 1
fi

# 2. Create build folder with bnote-next-generation subfolder (matches deployment path)
echo "Assembling build folder..."
rm -rf "$SCRIPT_DIR/$OUT_DIR"
mkdir -p "$DEPLOY_DIR"

# Verify required API modules exist before copying
if [[ ! -f "$SCRIPT_DIR/api/modules/share.php" ]]; then
  echo "ERROR: api/modules/share.php is missing. Share module will not work in production."
  exit 1
fi

cp -R "$SCRIPT_DIR/api"       "$DEPLOY_DIR/"
cp -R "$SCRIPT_DIR/lang"      "$DEPLOY_DIR/"
cp    "$SCRIPT_DIR/iso3166-alpha3-to-alpha2.json" "$DEPLOY_DIR/" 2>/dev/null || true

# Loopback mail previews / SMTP test — omit from production deploy (see header comment for dev bundle)
if [[ "$DEVELOPER_TOOLS_ENABLED" != "true" ]]; then
  rm -rf "$DEPLOY_DIR/api/debug"
fi

# Merge frontend static export (index.html, _next/, login/, etc.)
for item in "$SCRIPT_DIR/frontend/out"/*; do
  [[ -e "$item" ]] && cp -R "$item" "$DEPLOY_DIR/"
done
# Copy hidden files from out if any (e.g. .nojekyll)
for item in "$SCRIPT_DIR/frontend/out"/.*; do
  [[ -e "$item" && "$item" != */. && "$item" != */.. ]] && cp -R "$item" "$DEPLOY_DIR/"
done 2>/dev/null || true

# 3. Optional runtime mail config injection for manual deployment bundles.
# If .deploy.env exists, generate .htaccess + api/config/mail.local.php from MAIL_* values.
if [[ -f "$DEPLOY_CONFIG_FILE" ]]; then
  # shellcheck source=/dev/null
  source "$DEPLOY_CONFIG_FILE"

  BUILD_SYNC_HTACCESS="${DEPLOY_SYNC_HTACCESS:-true}"
  BUILD_SYNC_MAIL_CONFIG="${DEPLOY_SYNC_MAIL_CONFIG:-true}"

  if [[ "$BUILD_SYNC_HTACCESS" == "true" || "$BUILD_SYNC_MAIL_CONFIG" == "true" ]]; then
    for v in MAIL_HOST MAIL_PORT MAIL_ENCRYPTION MAIL_USERNAME MAIL_PASSWORD MAIL_FROM_ADDRESS MAIL_FROM_NAME; do
      if [[ -z "${!v:-}" ]]; then
        echo "ERROR: Missing $v in .deploy.env (required for build-time runtime config generation)."
        exit 1
      fi
    done
    if [[ -z "${BNOTE_NEXT_GENERATION_PUBLIC_URL:-}" ]]; then
      echo "ERROR: Missing BNOTE_NEXT_GENERATION_PUBLIC_URL in .deploy.env."
      exit 1
    fi
  fi

  export DEPLOY_MAIL_HOST="${MAIL_HOST:-}"
  export DEPLOY_MAIL_PORT="${MAIL_PORT:-}"
  export DEPLOY_MAIL_ENCRYPTION="${MAIL_ENCRYPTION:-}"
  export DEPLOY_MAIL_USERNAME="${MAIL_USERNAME:-}"
  export DEPLOY_MAIL_PASSWORD="${MAIL_PASSWORD:-}"
  export DEPLOY_MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-}"
  export DEPLOY_MAIL_FROM_NAME="${MAIL_FROM_NAME:-}"
  export DEPLOY_BNOTE_NEXT_GENERATION_PUBLIC_URL="${BNOTE_NEXT_GENERATION_PUBLIC_URL:-}"
  export DEPLOY_BNOTE_NEXT_GENERATION_MAIL_BULK_DELAY_MS="${BNOTE_NEXT_GENERATION_MAIL_BULK_DELAY_MS:-}"
  export DEPLOY_BNOTE_NEXT_GENERATION_REMINDER_SECRET="${BNOTE_NEXT_GENERATION_REMINDER_SECRET:-}"
  export DEPLOY_BNOTE_NEXT_GENERATION_REMINDER_ALLOWED_SKEW_SECONDS="${BNOTE_NEXT_GENERATION_REMINDER_ALLOWED_SKEW_SECONDS:-}"

  if [[ "$BUILD_SYNC_HTACCESS" == "true" ]]; then
    export DEPLOY_HTACCESS_ROOT_PATH="$DEPLOY_DIR/.htaccess"
    export DEPLOY_HTACCESS_API_PATH="$DEPLOY_DIR/api/.htaccess"
    if ! python3 - <<'PY'
import os, re, pathlib
keys = ["MAIL_HOST","MAIL_PORT","MAIL_ENCRYPTION","MAIL_USERNAME","MAIL_PASSWORD","MAIL_FROM_ADDRESS","MAIL_FROM_NAME","BNOTE_NEXT_GENERATION_PUBLIC_URL"]
values = {
    "MAIL_HOST": os.environ.get("DEPLOY_MAIL_HOST",""),
    "MAIL_PORT": os.environ.get("DEPLOY_MAIL_PORT",""),
    "MAIL_ENCRYPTION": os.environ.get("DEPLOY_MAIL_ENCRYPTION",""),
    "MAIL_USERNAME": os.environ.get("DEPLOY_MAIL_USERNAME",""),
    "MAIL_PASSWORD": os.environ.get("DEPLOY_MAIL_PASSWORD",""),
    "MAIL_FROM_ADDRESS": os.environ.get("DEPLOY_MAIL_FROM_ADDRESS",""),
    "MAIL_FROM_NAME": os.environ.get("DEPLOY_MAIL_FROM_NAME",""),
    "BNOTE_NEXT_GENERATION_PUBLIC_URL": os.environ.get("DEPLOY_BNOTE_NEXT_GENERATION_PUBLIC_URL",""),
}
def q(v: str) -> str:
    if v == "" or re.search(r"\s|['\"#]", v):
        return '"' + v.replace("\\", "\\\\").replace('"', '\\"') + '"'
    return v
lines = [f"SetEnv {k} {q(values[k])}" for k in keys]
pathlib.Path(os.environ["DEPLOY_HTACCESS_ROOT_PATH"]).write_text("\n".join(lines) + "\n", encoding="utf-8")
pathlib.Path(os.environ["DEPLOY_HTACCESS_API_PATH"]).write_text("\n".join(lines) + "\n", encoding="utf-8")
PY
    then
      echo "ERROR: Failed to generate .htaccess files from .deploy.env"
      exit 1
    fi
    echo "Generated .htaccess files in build bundle from .deploy.env"
  fi

  if [[ "$BUILD_SYNC_MAIL_CONFIG" == "true" ]]; then
    mkdir -p "$DEPLOY_DIR/api/config"
    export DEPLOY_MAIL_LOCAL_CONFIG_PATH="$DEPLOY_DIR/api/config/mail.local.php"
    if ! python3 - <<'PY'
import os, pathlib
path = pathlib.Path(os.environ["DEPLOY_MAIL_LOCAL_CONFIG_PATH"])
values = {
    "MAIL_HOST": os.environ.get("DEPLOY_MAIL_HOST",""),
    "MAIL_PORT": os.environ.get("DEPLOY_MAIL_PORT",""),
    "MAIL_ENCRYPTION": os.environ.get("DEPLOY_MAIL_ENCRYPTION",""),
    "MAIL_USERNAME": os.environ.get("DEPLOY_MAIL_USERNAME",""),
    "MAIL_PASSWORD": os.environ.get("DEPLOY_MAIL_PASSWORD",""),
    "MAIL_FROM_ADDRESS": os.environ.get("DEPLOY_MAIL_FROM_ADDRESS",""),
    "MAIL_FROM_NAME": os.environ.get("DEPLOY_MAIL_FROM_NAME",""),
    "BNOTE_NEXT_GENERATION_PUBLIC_URL": os.environ.get("DEPLOY_BNOTE_NEXT_GENERATION_PUBLIC_URL",""),
}
bulk = os.environ.get("DEPLOY_BNOTE_NEXT_GENERATION_MAIL_BULK_DELAY_MS","").strip()
reminder_secret = os.environ.get("DEPLOY_BNOTE_NEXT_GENERATION_REMINDER_SECRET","").strip()
reminder_skew = os.environ.get("DEPLOY_BNOTE_NEXT_GENERATION_REMINDER_ALLOWED_SKEW_SECONDS","").strip()
def sq(v: str) -> str:
    return "'" + v.replace("\\", "\\\\").replace("'", "\\'") + "'"
lines = ["<?php", "return ["]
for key in ["MAIL_HOST","MAIL_PORT","MAIL_ENCRYPTION","MAIL_USERNAME","MAIL_PASSWORD","MAIL_FROM_ADDRESS","MAIL_FROM_NAME","BNOTE_NEXT_GENERATION_PUBLIC_URL"]:
    lines.append(f"    {sq(key)} => {sq(values[key])},")
if bulk:
    lines.append(f"    {sq('BNOTE_NEXT_GENERATION_MAIL_BULK_DELAY_MS')} => {sq(bulk)},")
if reminder_secret:
    lines.append(f"    {sq('BNOTE_NEXT_GENERATION_REMINDER_SECRET')} => {sq(reminder_secret)},")
if reminder_skew:
    lines.append(f"    {sq('BNOTE_NEXT_GENERATION_REMINDER_ALLOWED_SKEW_SECONDS')} => {sq(reminder_skew)},")
lines.append("];")
lines.append("")
path.write_text("\n".join(lines), encoding="utf-8")
PY
    then
      echo "ERROR: Failed to generate api/config/mail.local.php from .deploy.env"
      exit 1
    fi
    echo "Generated api/config/mail.local.php in build bundle from .deploy.env"
  fi
else
  echo "WARN: .deploy.env not found; skipping build-time runtime mail config generation."
fi

# Drop a short README in the build folder
cat > "$SCRIPT_DIR/$OUT_DIR/BUILD_README.txt" << EOF
BNote Next Generation – build bundle

Upload the folder $OUT_DIR/bnote-next-generation/ to your server so the app is served at:
  https://your-domain/.../bnote-next-generation/

Folder structure matches deployment path (bnote-next-generation/ contains api/, lang/, index.html, etc.).

Required on server:
  - PHP (for api/index.php)
  - BNote core at ../BNote relative to this folder (same as in dev)

Outbound mail (password reset, notifications): configure SMTP on the server — see docs/MAIL.md in the repository (Strato shared hosting tutorial included).

Local debug: use npm run dev in frontend/ (see repo README).

Developer tools in static deploy: run ./build.sh --with-developer-tools
  (equivalent to NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS=1), so api/debug/ and debug Next routes are included.
EOF

echo "Build folder ready: $SCRIPT_DIR/$OUT_DIR/bnote-next-generation/"
echo ""
echo "Upload: copy the folder $OUT_DIR/bnote-next-generation/ to your server (same name = same path)."
echo ""
echo "IMPORTANT for Share module: The api/ folder (including api/modules/share.php) MUST be deployed."
echo "If you only deploy frontend/out/, the API will return 'Module not found: share' and Share will not appear."
echo ""
