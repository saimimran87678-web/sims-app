#!/usr/bin/env bash
# ==============================================================================
# SIMS Automated Lightweight Delta Patch Packaging Engine (Linux Bash)
# ==============================================================================
set -euo pipefail

VERSION="${1:-}"
CHANGELOG="${2:-Maintenance release, bug fixes, and security patches.}"
MIN_PHP_VERSION="${3:-8.2.0}"
REPO_URL="https://github.com/saimimran87678-web/sims-app"

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
ROOT_DIR="$( cd "${SCRIPT_DIR}/../../.." >/dev/null 2>&1 && pwd )"
APP_DIR="${ROOT_DIR}/sims-app"
DIST_DIR="${ROOT_DIR}/dist/patches"

echo "===================================================="
echo " 🐧 SIMS Delta Patch Packaging Engine (Linux)"
echo "===================================================="
echo "Repository Root: ${ROOT_DIR}"
echo "Application Dir: ${APP_DIR}"

if [ ! -f "${APP_DIR}/artisan" ]; then
    echo "❌ Error: Could not locate Laravel application at ${APP_DIR}!"
    exit 1
fi

# Auto-detect version if not provided
if [ -z "${VERSION}" ]; then
    VERSION_FILE="${APP_DIR}/config/app.php"
    if [ -f "${VERSION_FILE}" ]; then
        DETECTED=$(grep -oP "'version'\s*=>\s*env\('APP_VERSION',\s*'\K[^']+" "${VERSION_FILE}" || true)
        if [ -n "${DETECTED}" ]; then
            IFS='.' read -r MAJOR MINOR PATCH <<< "${DETECTED}"
            PATCH=$((PATCH + 1))
            VERSION="${MAJOR}.${MINOR}.${PATCH}"
        fi
    fi
    if [ -z "${VERSION}" ]; then
        VERSION="2.5.1"
    fi
fi

echo "✅ Target Patch Version: v${VERSION}"
echo "ℹ️ Changelog: ${CHANGELOG}"

mkdir -p "${DIST_DIR}"
STAGING_DIR="${DIST_DIR}/staging_patch_v${VERSION}"
rm -rf "${STAGING_DIR}"
mkdir -p "${STAGING_DIR}/sims-app"

echo "ℹ️ Collecting application code for delta package..."
for DIR in app resources routes database/migrations config public/build; do
    if [ -d "${APP_DIR}/${DIR}" ]; then
        mkdir -p "${STAGING_DIR}/sims-app/$(dirname "${DIR}")"
        cp -r "${APP_DIR}/${DIR}" "${STAGING_DIR}/sims-app/${DIR}"
        echo "  - Included: sims-app/${DIR}"
    fi
done

# Include operational scripts
if [ -d "${ROOT_DIR}/scripts" ]; then
    cp -r "${ROOT_DIR}/scripts" "${STAGING_DIR}/scripts"
    rm -rf "${STAGING_DIR}/scripts/build"
    echo "  - Included: scripts/ (operational runners)"
fi

# Include resources and icons if present
if [ -d "${ROOT_DIR}/resources/icons" ]; then
    mkdir -p "${STAGING_DIR}/resources"
    cp -r "${ROOT_DIR}/resources/icons" "${STAGING_DIR}/resources/"
    echo "  - Included: resources/icons (Adminova branding)"
fi

# Top-level launchers
for LAUNCHER in install.bat install.sh sims.bat control-center.bat; do
    if [ -f "${ROOT_DIR}/${LAUNCHER}" ]; then
        cp "${ROOT_DIR}/${LAUNCHER}" "${STAGING_DIR}/"
    fi
done

# Include vendor/ (PHP dependencies).
# REQUIRED: Without vendor/, any update adding new PHP packages will cause Fatal errors on client.
# The vendor directory is pre-optimized for production and does not contain dev dependencies.
if [ -d "${APP_DIR}/vendor" ]; then
    echo "  - Staging vendor/ (PHP dependencies)..."
    cp -r "${APP_DIR}/vendor" "${STAGING_DIR}/sims-app/vendor"
    # Remove dev-only packages to keep patch size minimal
    rm -rf "${STAGING_DIR}/sims-app/vendor/phpunit" 2>/dev/null || true
    echo "  - Included: sims-app/vendor/ (production PHP dependencies)"
else
    echo "WARNING: vendor/ not found at ${APP_DIR}/vendor — patch will not include PHP dependencies!"
    echo "         Run 'composer install --no-dev --optimize-autoloader' before building patches."
fi

# Strict secret & state sanitation
echo "ℹ️ Sanitizing staging files..."
find "${STAGING_DIR}" -type f \( -name "*.pem" -o -name "*.key" -o -name "*.sqlite*" -o -name ".env*" -o -name ".sims-server.state" -o -name "*.log" \) -delete

# ─────────────────────────────────────────────────────────────────────────────
# ZIP STRUCTURE NOTE (CRITICAL — do not change):
#   The zip is built by cd-ing into STAGING_DIR and zipping "."
#   This produces entries like:
#     ./sims-app/app/...
#     ./sims-app/resources/...
#     ./sims-app/vendor/...
#     ./scripts/windows/...
#     ./scripts/linux/...
#     ./install.bat
#   SimsUpdate.php extracts to dirname(base_path()) = the INSTALLATION ROOT
#   so these paths land at: <install>\sims-app\app\... and <install>\scripts\...
#   DO NOT change the staging structure or the extract path without updating both.
# ─────────────────────────────────────────────────────────────────────────────

ZIP_FILE="sims-patch-v${VERSION}.zip"
ZIP_PATH="${DIST_DIR}/${ZIP_FILE}"
rm -f "${ZIP_PATH}"

echo "ℹ️ Compressing patch into ${ZIP_FILE}..."
(cd "${STAGING_DIR}" && zip -rq "${ZIP_PATH}" .)
rm -rf "${STAGING_DIR}"

# Compute SHA-256
FILE_HASH=$(sha256sum "${ZIP_PATH}" | awk '{print $1}')
FILE_SIZE_BYTES=$(stat -c%s "${ZIP_PATH}")
DOWNLOAD_URL="${REPO_URL}/releases/download/v${VERSION}/${ZIP_FILE}"
RELEASED_AT=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

# Generate manifest.json
cat <<EOF > "${DIST_DIR}/manifest.json"
{
  "version": "${VERSION}",
  "download_url": "${DOWNLOAD_URL}",
  "checksum": "${FILE_HASH}",
  "sha256": "${FILE_HASH}",
  "min_php_version": "${MIN_PHP_VERSION}",
  "changelog": "${CHANGELOG}",
  "size_bytes": ${FILE_SIZE_BYTES},
  "released_at": "${RELEASED_AT}"
}
EOF

cp "${DIST_DIR}/manifest.json" "${DIST_DIR}/manifest-v${VERSION}.json"

echo "===================================================="
echo " 🎉 Delta Patch Successfully Generated!"
echo " Package Archive : ${ZIP_PATH}"
echo " Package Size    : ${FILE_SIZE_BYTES} bytes"
echo " SHA-256 Hash    : ${FILE_HASH}"
echo " Manifest File   : ${DIST_DIR}/manifest.json"
echo " Download URL    : ${DOWNLOAD_URL}"
echo "===================================================="
