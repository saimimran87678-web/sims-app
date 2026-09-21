#!/usr/bin/env bash
# ==============================================================================
#  SIMS Production Release Packaging & Distribution Pipeline
#  Builds standalone release packages with strict secret sanitization,
#  production dependency optimization, and SHA-256 checksum manifest generation.
# ==============================================================================

set -euo pipefail

# Text styling
BOLD='\033[1m'
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
APP_DIR="${ROOT_DIR}/sims-app"
DIST_DIR="${ROOT_DIR}/dist"
RELEASES_DIR="${DIST_DIR}/releases"

echo -e "${CYAN}${BOLD}======================================================${NC}"
echo -e "${CYAN}${BOLD}     SIMS Developer Release Packaging Pipeline        ${NC}"
echo -e "${CYAN}${BOLD}======================================================${NC}"

# 1. Determine Target Version
VERSION="${1:-}"
if [ -z "${VERSION}" ]; then
    VERSION=$(php -r "require '${APP_DIR}/vendor/autoload.php'; echo config('app.version', '2.5.0');" 2>/dev/null || echo "2.5.0")
fi
echo -e "${BLUE}[*] Target Release Version:${NC} ${BOLD}v${VERSION}${NC}"

# 2. Pre-flight verification
echo -e "${BLUE}[*] Running pre-flight security checks...${NC}"
if [ ! -f "${APP_DIR}/public.pem" ]; then
    echo -e "${RED}[ERROR] public.pem is missing from ${APP_DIR}! Client PCs need public.pem to verify licenses.${NC}"
    exit 1
fi

if [ -f "${APP_DIR}/private.pem" ]; then
    echo -e "${YELLOW}[INFO] Master private.pem detected on developer PC. It will be strictly excluded from release packages.${NC}"
fi

# 3. Optimize Composer Dependencies for Production
echo -e "${BLUE}[*] Optimizing composer autoloader and excluding dev packages...${NC}"
cd "${APP_DIR}"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --quiet

# 4. Prepare clean staging area
STAGING_PARENT="${DIST_DIR}/staging"
STAGING_DIR="${STAGING_PARENT}/SIMS-v${VERSION}"
rm -rf "${STAGING_PARENT}"
mkdir -p "${STAGING_DIR}/sims-app"
mkdir -p "${RELEASES_DIR}"

# 5. Copy Application Files with Strict Exclusions
echo -e "${BLUE}[*] Staging application files (excluding secrets, tests, and dev logs)...${NC}"

rsync -a \
    --exclude='private.pem' \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='.vscode' \
    --exclude='.idea' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='phpunit.xml' \
    --exclude='.phpunit.result.cache' \
    --exclude='storage/snapshots' \
    --exclude='storage/updates' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='database/database.sqlite*' \
    --exclude='database/*.sqlite-wal' \
    --exclude='database/*.sqlite-shm' \
    --exclude='database/*.sqlite-journal' \
    --exclude='.env' \
    --exclude='.env.backup*' \
    --exclude='debug_plan.md' \
    --exclude='tests_output.txt' \
    --exclude='scratch' \
    "${APP_DIR}/" "${STAGING_DIR}/sims-app/"

# 6. Generate Clean Production .env Template in Staging
echo -e "${BLUE}[*] Creating sanitized production .env (APP_KEY and LICENSE_KEY strictly empty)...${NC}"

# Extract non-sensitive credentials from local .env if available, fallback to defaults
FB_KEY=$(grep '^FIREBASE_API_KEY=' "${APP_DIR}/.env" 2>/dev/null | cut -d '=' -f2- || echo '""')
FB_PID=$(grep '^FIREBASE_PROJECT_ID=' "${APP_DIR}/.env" 2>/dev/null | cut -d '=' -f2- || echo '"sims-licensing"')
INT_KEY=$(grep '^LICENSE_INTEGRITY_KEY=' "${APP_DIR}/.env" 2>/dev/null | cut -d '=' -f2- || echo '""')
RSA_PUB=$(grep '^LICENSE_RSA_PUBLIC_KEY=' "${APP_DIR}/.env" 2>/dev/null | cut -d '=' -f2- || echo '""')

cat << ENV_EOF > "${STAGING_DIR}/sims-app/.env"
APP_NAME="SIMS Institute Portal"
APP_VERSION="${VERSION}"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# SQLite High-Concurrency Database
DB_CONNECTION=sqlite
DB_FOREIGN_KEYS=true

# Database-backed Queue, Cache, and Sessions (Zero-Redis dependency)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_EXPIRE_ON_CLOSE=true

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
BROADCAST_CONNECTION=log

# SIMS Offline Licensing & Authenticity Keys
FIREBASE_API_KEY=${FB_KEY}
FIREBASE_PROJECT_ID=${FB_PID}
LICENSE_KEY=
LICENSE_INTEGRITY_KEY=${INT_KEY}
LICENSE_RSA_PUBLIC_KEY=${RSA_PUB}
ENV_EOF

# Copy .env as .env.example as well
cp "${STAGING_DIR}/sims-app/.env" "${STAGING_DIR}/sims-app/.env.example"

# 7. Copy Installers and Release Documentation
cp "${ROOT_DIR}/install.bat" "${STAGING_DIR}/install.bat"
cp "${ROOT_DIR}/install.sh" "${STAGING_DIR}/install.sh"
chmod +x "${STAGING_DIR}/install.sh"

if [ -f "${ROOT_DIR}/start-server.bat" ]; then
    cp "${ROOT_DIR}/start-server.bat" "${STAGING_DIR}/start-server.bat"
fi

if [ -d "${ROOT_DIR}/bin" ]; then
    mkdir -p "${STAGING_DIR}/bin"
    cp -r "${ROOT_DIR}/bin/"* "${STAGING_DIR}/bin/"
fi

cat << 'README_EOF' > "${STAGING_DIR}/README.txt"
============================================================
           🏫 SIMS - School Information Management System
============================================================

QUICK INSTALLATION GUIDE:

WINDOWS:
  1. Right-click "install.bat" and select "Run as administrator".
  2. The installer will automatically:
     - Configure Port 80 permissions.
     - Generate a unique database encryption key.
     - Register background background services in Task Scheduler.
     - Launch SIMS in your default web browser (http://localhost).
  3. Enter your License Key and follow the 4-step setup wizard.

LINUX:
  1. Open terminal in this folder and run:
     chmod +x install.sh && ./install.sh
  2. Open your web browser to http://localhost

REQUIREMENTS:
  - Windows 10/11 or Linux (Ubuntu, Debian, CentOS)
  - PHP 8.2+ with SQLite, cURL, MBString, and Zip extensions
  - Port 80 available
============================================================
README_EOF

# 7b. Bundle Portable Runtime Environment (if available)
if [ -d "${ROOT_DIR}/runtime" ] && [ -f "${ROOT_DIR}/runtime/frankenphp.exe" ] && [ -f "${ROOT_DIR}/runtime/php/php.exe" ]; then
    echo -e "${BLUE}[*] Bundling portable Windows runtime (FrankenPHP & PHP 8.2)...${NC}"
    mkdir -p "${STAGING_DIR}/runtime"
    cp -r "${ROOT_DIR}/runtime/"* "${STAGING_DIR}/runtime/"
    echo -e "${GREEN}[OK] Portable runtime bundled successfully (Zero-Install enabled).${NC}"
else
    echo -e "${YELLOW}[NOTICE] Portable runtime/ not detected in project root.${NC}"
    echo -e "         Package will rely on target machine's system PHP (Fallback mode)."
    echo -e "         Tip: Run ./download-windows-runtime.sh to enable Zero-Install packages."
fi

# 8. CRITICAL SECURITY ASSERTIONS
echo -e "${BLUE}[*] Performing security integrity audit on staging package...${NC}"

# Check 1: private.pem MUST NOT exist
if find "${STAGING_DIR}" -name "private.pem" | grep -q "private.pem"; then
    echo -e "${RED}[FATAL ERROR] private.pem was found in the release package! Aborting immediately.${NC}"
    rm -rf "${STAGING_PARENT}"
    exit 1
fi

# Check 2: APP_KEY MUST be empty
STAGING_APP_KEY=$(grep '^APP_KEY=' "${STAGING_DIR}/sims-app/.env" | cut -d '=' -f2-)
if [ -n "${STAGING_APP_KEY}" ]; then
    echo -e "${RED}[FATAL ERROR] APP_KEY in staging .env is not empty! Value: ${STAGING_APP_KEY}${NC}"
    rm -rf "${STAGING_PARENT}"
    exit 1
fi

# Check 3: LICENSE_KEY MUST be empty
STAGING_LIC_KEY=$(grep '^LICENSE_KEY=' "${STAGING_DIR}/sims-app/.env" | cut -d '=' -f2-)
if [ -n "${STAGING_LIC_KEY}" ]; then
    echo -e "${RED}[FATAL ERROR] LICENSE_KEY in staging .env is not empty! Value: ${STAGING_LIC_KEY}${NC}"
    rm -rf "${STAGING_PARENT}"
    exit 1
fi

# Check 4: public.pem MUST be present
if [ ! -f "${STAGING_DIR}/sims-app/public.pem" ]; then
    echo -e "${RED}[FATAL ERROR] public.pem is missing from staging package!${NC}"
    rm -rf "${STAGING_PARENT}"
    exit 1
fi

echo -e "${GREEN}[OK] All security integrity checks passed!${NC}"
echo -e "   - private.pem : STRICTLY EXCLUDED"
echo -e "   - APP_KEY     : STRICTLY EMPTY (generated on client install)"
echo -e "   - LICENSE_KEY : STRICTLY EMPTY (entered during setup wizard)"
echo -e "   - public.pem  : VERIFIED PRESENT"

# 9. Create Standalone Release ZIP
ZIP_FILENAME="SIMS-v${VERSION}.zip"
ZIP_FILEPATH="${RELEASES_DIR}/${ZIP_FILENAME}"
rm -f "${ZIP_FILEPATH}"

echo -e "${BLUE}[*] Compressing release package into ${ZIP_FILENAME}...${NC}"
cd "${STAGING_PARENT}"
zip -r -q "${ZIP_FILEPATH}" "SIMS-v${VERSION}"

# Clean up staging folder
rm -rf "${STAGING_PARENT}"

# 10. Compute SHA-256 Checksum
echo -e "${BLUE}[*] Computing cryptographic SHA-256 checksum...${NC}"
CHECKSUM=$(sha256sum "${ZIP_FILEPATH}" | awk '{print $1}')
FILE_SIZE=$(ls -lh "${ZIP_FILEPATH}" | awk '{print $5}')

# 11. Generate GitHub Pages / CDN update manifest.json
MANIFEST_FILEPATH="${RELEASES_DIR}/manifest.json"
RELEASE_DATE=$(date -u +"%Y-%m-%d")

cat << MANIFEST_EOF > "${MANIFEST_FILEPATH}"
{
  "version": "${VERSION}",
  "download_url": "https://updates.sims.pk/${ZIP_FILENAME}",
  "checksum": "${CHECKSUM}",
  "sha256": "${CHECKSUM}",
  "min_php_version": "8.2.0",
  "release_date": "${RELEASE_DATE}",
  "changelog": "SIMS v${VERSION} standalone distribution with offline licensing, background scheduler, and Driver.js product tour"
}
MANIFEST_EOF

# 12. Verification & Summary Output
echo -e "${GREEN}${BOLD}======================================================${NC}"
echo -e "${GREEN}${BOLD}  🎉 SIMS Release v${VERSION} Built Successfully!     ${NC}"
echo -e "${GREEN}${BOLD}======================================================${NC}"
echo -e " Package File : ${BOLD}${ZIP_FILEPATH}${NC} (${FILE_SIZE})"
echo -e " SHA-256 Hash : ${CYAN}${BOLD}${CHECKSUM}${NC}"
echo -e " Manifest     : ${BOLD}${MANIFEST_FILEPATH}${NC}"
echo -e " Security     : ${GREEN}Zero secrets, clean .env, public.pem verified${NC}"
echo -e "${GREEN}======================================================${NC}"
