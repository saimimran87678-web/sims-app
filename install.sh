#!/usr/bin/env bash
# SIMS Quick-Launch Installer (Delegates to scripts/linux/install.sh)
set -e

ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

if [ -f "${ROOT_DIR}/scripts/linux/install.sh" ]; then
    chmod +x "${ROOT_DIR}/scripts/linux/install.sh" 2>/dev/null || true
    exec "${ROOT_DIR}/scripts/linux/install.sh" "$@"
fi

echo "❌ Error: Installer engine not found at scripts/linux/install.sh!"
echo "Please ensure the release package was extracted completely."
exit 1
