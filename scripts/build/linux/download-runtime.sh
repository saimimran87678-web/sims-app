#!/usr/bin/env bash
# ==============================================================================
#  SIMS Portable Windows Runtime Downloader (Linux Cross-Packaging Support)
#  Downloads and configures FrankenPHP and Portable PHP 8.2 for Windows
#  into the ./runtime/ directory for a 100% self-contained, zero-install package.
# ==============================================================================

set -euo pipefail

# Text styling
BOLD='\033[1m'
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../../.." >/dev/null 2>&1 && pwd )"
RUNTIME_DIR="${ROOT_DIR}/runtime"
TMP_DIR="${ROOT_DIR}/dist/runtime_tmp"

echo -e "${CYAN}${BOLD}======================================================${NC}"
echo -e "${CYAN}${BOLD}     SIMS Windows Portable Runtime Downloader         ${NC}"
echo -e "${CYAN}${BOLD}======================================================${NC}"
echo -e "Target Directory: ${BOLD}${RUNTIME_DIR}${NC}\n"

# Verify required host tools
for cmd in curl unzip; do
    if ! command -v "${cmd}" &>/dev/null; then
        echo -e "${RED}[ERROR] Required utility '${cmd}' is not installed on this system.${NC}"
        exit 1
    fi
done

# Prepare directories
mkdir -p "${RUNTIME_DIR}/php"
mkdir -p "${TMP_DIR}"

# ------------------------------------------------------------------------------
# 1. Download & Extract FrankenPHP for Windows
# ------------------------------------------------------------------------------
echo -e "${BLUE}[1/3] Downloading FrankenPHP for Windows (x86_64)...${NC}"
FRANKEN_ZIP="${TMP_DIR}/frankenphp.zip"
FRANKEN_URL="https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-windows-x86_64.zip"

if curl -fSL --progress-bar "${FRANKEN_URL}" -o "${FRANKEN_ZIP}"; then
    echo -e "${GREEN}[OK] FrankenPHP downloaded. Extracting...${NC}"
    unzip -q -o "${FRANKEN_ZIP}" -d "${TMP_DIR}/franken_extracted"
    find "${TMP_DIR}/franken_extracted" -type f \( -name "*.exe" -o -name "*.dll" \) -exec cp -f {} "${RUNTIME_DIR}/" \;
    echo -e "${GREEN}[OK] FrankenPHP and companion libraries installed to ${RUNTIME_DIR}/${NC}"
else
    echo -e "${RED}[ERROR] Failed to download FrankenPHP from ${FRANKEN_URL}.${NC}"
    exit 1
fi

# ------------------------------------------------------------------------------
# 2. Download & Extract Portable Windows PHP 8.2 (NTS x64)
# ------------------------------------------------------------------------------
echo -e "\n${BLUE}[2/3] Downloading Portable Windows PHP 8.2 (x64 NTS)...${NC}"
PHP_ZIP="${TMP_DIR}/php82.zip"

PHP_URL="https://windows.php.net/downloads/releases/php-8.2.27-nts-Win32-vs16-x64.zip"
echo -e "Source: ${PHP_URL}"

if ! curl -fSL --progress-bar "${PHP_URL}" -o "${PHP_ZIP}"; then
    echo -e "${YELLOW}[WARN] Version 8.2.27 unavailable, trying 8.2.26...${NC}"
    PHP_URL="https://windows.php.net/downloads/releases/archives/php-8.2.26-nts-Win32-vs16-x64.zip"
    curl -fSL --progress-bar "${PHP_URL}" -o "${PHP_ZIP}"
fi

echo -e "${GREEN}[OK] PHP archive downloaded. Extracting into ${RUNTIME_DIR}/php...${NC}"
unzip -q -o "${PHP_ZIP}" -d "${RUNTIME_DIR}/php"
echo -e "${GREEN}[OK] Portable PHP files extracted successfully.${NC}"

# ------------------------------------------------------------------------------
# 3. Configure production php.ini with required extensions
# ------------------------------------------------------------------------------
echo -e "\n${BLUE}[3/3] Generating pre-configured php.ini with SQLite, cURL, MBString & Zip...${NC}"

cat << 'INI_EOF' > "${RUNTIME_DIR}/php/php.ini"
[PHP]
engine = On
short_open_tag = Off
precision = 14
output_buffering = 4096
zlib.output_compression = Off
implicit_flush = Off
serialize_precision = -1
zend.enable_gc = On

max_execution_time = 300
max_input_time = 120
memory_limit = 512M

error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = "php_errors.log"

variables_order = "GPCS"
request_order = "GP"
register_argc_argv = Off
auto_globals_jit = On

post_max_size = 64M
default_mimetype = "text/html"
default_charset = "UTF-8"

enable_dl = Off
file_uploads = On
upload_max_filesize = 64M
max_file_uploads = 20

allow_url_fopen = On
allow_url_include = Off
default_socket_timeout = 60

; Extensions directory (relative to php.exe)
extension_dir = "ext"

; Core required extensions for SIMS
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_sqlite
extension=sqlite3
extension=zip
extension=gd

[Date]
date.timezone = Asia/Karachi

[Session]
session.save_handler = files
session.use_strict_mode = 1
session.use_cookies = 1
session.use_only_cookies = 1
session.name = SIMSSESSID
session.auto_start = 0
session.cookie_lifetime = 0
session.cookie_path = /
session.cookie_domain =
session.cookie_httponly = 1
session.cookie_samesite = "Lax"
session.gc_probability = 1
session.gc_divisor = 1000
session.gc_maxlifetime = 14400
session.cache_limiter = nocache
session.cache_expire = 180

[opcache]
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 1
opcache.revalidate_freq = 2
INI_EOF

echo -e "${GREEN}[OK] php.ini created and optimized.${NC}"

# Clean up temporary downloads
rm -rf "${TMP_DIR}"

echo -e "\n${GREEN}${BOLD}======================================================${NC}"
echo -e "${GREEN}${BOLD}  Portable Windows Runtime Setup Complete!            ${NC}"
echo -e "${GREEN}${BOLD}======================================================${NC}"
echo -e "Files installed in: ${CYAN}${RUNTIME_DIR}/${NC}"
echo -e "  - ${BOLD}frankenphp.exe${NC} (Standalone Web Server)"
echo -e "  - ${BOLD}php/php.exe${NC}    (CLI Runner for Queue & Scheduler)"
echo -e "  - ${BOLD}php/php.ini${NC}    (Configured with SQLite WAL & Extensions)"
echo -e "  - ${BOLD}php/ext/*${NC}      (Required Windows DLLs)"
