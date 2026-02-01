#!/usr/bin/env bash
#
# BNote Next Generation – Build script
#
# Creates a single folder you can upload (api + frontend + lang + config).
# For local debug use: cd frontend && npm run dev (see README).
#
# Usage:
#   ./build.sh [--out DIR]   # Create build folder (default: build/)
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT_DIR="build"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --out)
      OUT_DIR="$2"
      shift 2
      ;;
    *)
      echo "Unknown option: $1"
      echo "Usage: $0 [--out DIR]"
      exit 1
      ;;
  esac
done

echo "=== BNote Next Generation build ==="
echo "Output folder: $OUT_DIR"
echo ""

# 1. Build frontend
echo "Building frontend..."
cd "$SCRIPT_DIR/frontend"
if ! npm run build >/dev/null 2>&1; then
  echo "Frontend build failed. Run: cd frontend && npm run build"
  exit 1
fi
cd "$SCRIPT_DIR"

if [[ ! -d "frontend/out" ]]; then
  echo "Frontend build did not produce frontend/out/"
  exit 1
fi

# 2. Create build folder and copy everything
echo "Assembling build folder..."
rm -rf "$SCRIPT_DIR/$OUT_DIR"
mkdir -p "$SCRIPT_DIR/$OUT_DIR"

cp -R "$SCRIPT_DIR/api"       "$SCRIPT_DIR/$OUT_DIR/"
cp -R "$SCRIPT_DIR/lang"      "$SCRIPT_DIR/$OUT_DIR/"
cp    "$SCRIPT_DIR/iso3166-alpha3-to-alpha2.json" "$SCRIPT_DIR/$OUT_DIR/" 2>/dev/null || true

# Merge frontend static export into build root (index.html, _next/, login/, etc.)
for item in "$SCRIPT_DIR/frontend/out"/*; do
  [[ -e "$item" ]] && cp -R "$item" "$SCRIPT_DIR/$OUT_DIR/"
done
# Copy hidden files from out if any (e.g. .nojekyll)
for item in "$SCRIPT_DIR/frontend/out"/.*; do
  [[ -e "$item" && "$item" != */. && "$item" != */.. ]] && cp -R "$item" "$SCRIPT_DIR/$OUT_DIR/"
done 2>/dev/null || true

# Drop a short README in the build folder
cat > "$SCRIPT_DIR/$OUT_DIR/BUILD_README.txt" << 'EOF'
BNote Next Generation – build bundle

Upload the *contents* of this folder to your server so the app is served at:
  https://your-domain/.../bnote-next-generation/

Required on server:
  - PHP (for api/index.php)
  - BNote core at ../BNote relative to this folder (same as in dev)

Local debug: use npm run dev in frontend/ (see repo README).
EOF

echo "Build folder ready: $SCRIPT_DIR/$OUT_DIR"
echo ""
echo "Upload: copy the *contents* of $OUT_DIR/ to your server (e.g. into the folder that will be served at .../bnote-next-generation/)."
echo ""
