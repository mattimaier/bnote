#!/usr/bin/env bash
#
# BNote Next Generation – Build script
#
# Creates a single folder you can upload (api + frontend + lang + config).
# For local debug use: cd frontend && npm run dev (see README).
#
# Usage:
#   ./build.sh [--out DIR] [--verify-only]
#     --verify-only: only check existing build output, do not build
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT_DIR="build"
VERIFY_ONLY="false"

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
    *)
      echo "Unknown option: $1"
      echo "Usage: $0 [--out DIR] [--verify-only]"
      exit 1
      ;;
  esac
done

echo "=== BNote Next Generation build ==="
echo "Output folder: $OUT_DIR"
echo "Verify only: $VERIFY_ONLY"
echo ""

if [[ "$VERIFY_ONLY" == "true" ]]; then
  DEPLOY_DIR="$SCRIPT_DIR/$OUT_DIR/bnote-next-generation"
  if [[ ! -d "$DEPLOY_DIR" ]]; then
    echo "Build output not found: $DEPLOY_DIR"
    echo "Run ./build.sh --out $OUT_DIR first."
    exit 1
  fi
  echo "Build output exists: $DEPLOY_DIR"
  exit 0
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
mkdir -p "$SCRIPT_DIR/$OUT_DIR/bnote-next-generation"
DEPLOY_DIR="$SCRIPT_DIR/$OUT_DIR/bnote-next-generation"

# Verify required API modules exist before copying
if [[ ! -f "$SCRIPT_DIR/api/modules/share.php" ]]; then
  echo "ERROR: api/modules/share.php is missing. Share module will not work in production."
  exit 1
fi

cp -R "$SCRIPT_DIR/api"       "$DEPLOY_DIR/"
cp -R "$SCRIPT_DIR/lang"      "$DEPLOY_DIR/"
cp    "$SCRIPT_DIR/iso3166-alpha3-to-alpha2.json" "$DEPLOY_DIR/" 2>/dev/null || true

# Merge frontend static export (index.html, _next/, login/, etc.)
for item in "$SCRIPT_DIR/frontend/out"/*; do
  [[ -e "$item" ]] && cp -R "$item" "$DEPLOY_DIR/"
done
# Copy hidden files from out if any (e.g. .nojekyll)
for item in "$SCRIPT_DIR/frontend/out"/.*; do
  [[ -e "$item" && "$item" != */. && "$item" != */.. ]] && cp -R "$item" "$DEPLOY_DIR/"
done 2>/dev/null || true

# Drop a short README in the build folder
cat > "$SCRIPT_DIR/$OUT_DIR/BUILD_README.txt" << EOF
BNote Next Generation – build bundle

Upload the folder $OUT_DIR/bnote-next-generation/ to your server so the app is served at:
  https://your-domain/.../bnote-next-generation/

Folder structure matches deployment path (bnote-next-generation/ contains api/, lang/, index.html, etc.).

Required on server:
  - PHP (for api/index.php)
  - BNote core at ../BNote relative to this folder (same as in dev)

Local debug: use npm run dev in frontend/ (see repo README).
EOF

echo "Build folder ready: $SCRIPT_DIR/$OUT_DIR/bnote-next-generation/"
echo ""
echo "Upload: copy the folder $OUT_DIR/bnote-next-generation/ to your server (same name = same path)."
echo ""
echo "IMPORTANT for Share module: The api/ folder (including api/modules/share.php) MUST be deployed."
echo "If you only deploy frontend/out/, the API will return 'Module not found: share' and Share will not appear."
echo ""
