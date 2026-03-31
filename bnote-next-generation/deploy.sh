#!/usr/bin/env bash
#
# BNote Next Generation – Deploy script (SFTP + 1Password)
#
# Usage:
#   ./deploy.sh --with-build
#   ./deploy.sh --no-build
#   ./deploy.sh --test-only
#   ./deploy.sh --with-build --with-developer-tools
#   ./deploy.sh --with-build --without-developer-tools
#   ./deploy.sh                 # uses DEPLOY_WITH_BUILD from .deploy.env
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$SCRIPT_DIR/.deploy.env"
BUILD_SCRIPT="$SCRIPT_DIR/build.sh"

if [[ ! -f "$CONFIG_FILE" ]]; then
  echo "Missing $CONFIG_FILE"
  echo "Copy .deploy.env.example to .deploy.env and fill in local values."
  exit 1
fi

# shellcheck source=/dev/null
source "$CONFIG_FILE"

require_var() {
  local var_name="$1"
  if [[ -z "${!var_name:-}" ]]; then
    echo "Required config missing: $var_name"
    exit 1
  fi
}

require_cmd() {
  local cmd_name="$1"
  if ! command -v "$cmd_name" >/dev/null 2>&1; then
    echo "Required command not found: $cmd_name"
    exit 1
  fi
}

run_build="${DEPLOY_WITH_BUILD:-false}"
test_only="false"
developer_tools_override=""
while [[ $# -gt 0 ]]; do
  case "$1" in
    --with-build)
      run_build="true"
      shift
      ;;
    --no-build)
      run_build="false"
      shift
      ;;
    --test-only)
      test_only="true"
      shift
      ;;
    --with-developer-tools)
      developer_tools_override="1"
      shift
      ;;
    --without-developer-tools)
      developer_tools_override="0"
      shift
      ;;
    *)
      echo "Unknown option: $1"
      echo "Usage: $0 [--with-build|--no-build|--test-only] [--with-developer-tools|--without-developer-tools]"
      exit 1
      ;;
  esac
done

require_var "BUILD_DIR"
require_var "SFTP_URL"
require_var "OP_USERNAME_REF"
require_var "OP_PASSWORD_REF"
require_cmd "op"
require_cmd "lftp"

SFTP_USERNAME="$(op read "$OP_USERNAME_REF")"
SFTP_PASSWORD="$(op read "$OP_PASSWORD_REF")"
if [[ -z "$SFTP_USERNAME" || -z "$SFTP_PASSWORD" ]]; then
  echo "Failed to read SFTP credentials from 1Password."
  exit 1
fi

if [[ "$test_only" == "true" ]]; then
  echo "Testing remote SFTP connection..."
  if ! lftp "$SFTP_URL" -u "$SFTP_USERNAME","$SFTP_PASSWORD" -e "set sftp:auto-confirm yes; bye" >/dev/null 2>&1; then
    echo "Remote SFTP connection test failed."
    exit 1
  fi
  echo "Remote SFTP connection test successful."
  echo "Test-only mode: skipping build and deploy."
  exit 0
fi

if [[ "$run_build" == "true" ]]; then
  echo "Running build..."
  if [[ -n "$developer_tools_override" ]]; then
    if [[ "$developer_tools_override" == "1" ]]; then
      "$BUILD_SCRIPT" --out "$BUILD_DIR" --with-developer-tools
    else
      "$BUILD_SCRIPT" --out "$BUILD_DIR" --without-developer-tools
    fi
  else
    "$BUILD_SCRIPT" --out "$BUILD_DIR"
  fi
else
  echo "Verifying existing build output..."
  if [[ -n "$developer_tools_override" ]]; then
    if [[ "$developer_tools_override" == "1" ]]; then
      "$BUILD_SCRIPT" --out "$BUILD_DIR" --verify-only --with-developer-tools
    else
      "$BUILD_SCRIPT" --out "$BUILD_DIR" --verify-only --without-developer-tools
    fi
  else
    "$BUILD_SCRIPT" --out "$BUILD_DIR" --verify-only
  fi
fi

LOCAL_DEPLOY_DIR="$SCRIPT_DIR/$BUILD_DIR/bnote-next-generation"

echo "Testing remote SFTP connection..."
if ! lftp "$SFTP_URL" -u "$SFTP_USERNAME","$SFTP_PASSWORD" -e "set sftp:auto-confirm yes; bye" >/dev/null 2>&1; then
  echo "Remote SFTP connection test failed."
  exit 1
fi

echo "Deploying $LOCAL_DEPLOY_DIR to remote target..."
set +x
lftp "$SFTP_URL" -u "$SFTP_USERNAME","$SFTP_PASSWORD" -e "set sftp:auto-confirm yes; mirror -R --delete --verbose \"$LOCAL_DEPLOY_DIR\" .; bye"
echo "Remote deploy complete."
