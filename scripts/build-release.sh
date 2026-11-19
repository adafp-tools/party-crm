#!/usr/bin/env bash
# build-release.sh — Build a distributable Party CRM release archive
# Usage: ./scripts/build-release.sh [version]
# Output: party-crm-<version>.zip and party-crm-<version>.tar.gz in dist/
set -euo pipefail

VERSION="${1:-$(cat VERSION 2>/dev/null || echo '1.0.0')}"
DIST_DIR="dist"
BUILD_DIR="${DIST_DIR}/party-crm-${VERSION}"

echo "Building Party CRM release v${VERSION}…"

# Clean previous build
rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}"

# Install production-only PHP dependencies
echo "→ Installing Composer dependencies (no-dev)…"
composer install --no-dev --optimize-autoloader --no-interaction --quiet

# Build frontend assets
echo "→ Building frontend assets…"
npm ci --silent
npm run build

# Copy application files (excluding dev/git artifacts)
echo "→ Copying files…"
rsync -a --quiet \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='.env.*' \
  --exclude='node_modules' \
  --exclude='tests' \
  --exclude='storage/app/private/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='storage/logs/*' \
  --exclude='dist' \
  --exclude='scripts/build-release.sh' \
  . "${BUILD_DIR}/"

# Create placeholder storage directories
mkdir -p "${BUILD_DIR}/storage/app/private"
mkdir -p "${BUILD_DIR}/storage/framework/cache"
mkdir -p "${BUILD_DIR}/storage/framework/sessions"
mkdir -p "${BUILD_DIR}/storage/framework/views"
mkdir -p "${BUILD_DIR}/storage/logs"
touch "${BUILD_DIR}/storage/logs/.gitkeep"

# Write version file
echo "${VERSION}" > "${BUILD_DIR}/VERSION"

# Create archives
echo "→ Creating archives…"
cd "${DIST_DIR}"
zip -qr "party-crm-${VERSION}.zip" "party-crm-${VERSION}/"
tar czf "party-crm-${VERSION}.tar.gz" "party-crm-${VERSION}/"
cd ..

# Checksums
echo "→ Computing checksums…"
sha256sum "${DIST_DIR}/party-crm-${VERSION}.zip" | awk '{print $1}' > "${DIST_DIR}/party-crm-${VERSION}.zip.sha256"
sha256sum "${DIST_DIR}/party-crm-${VERSION}.tar.gz" | awk '{print $1}' > "${DIST_DIR}/party-crm-${VERSION}.tar.gz.sha256"

# Restore dev dependencies for local development
composer install --no-interaction --quiet

echo ""
echo "✓ Release v${VERSION} built:"
echo "  ${DIST_DIR}/party-crm-${VERSION}.zip"
echo "  ${DIST_DIR}/party-crm-${VERSION}.tar.gz"
echo ""
echo "SHA-256 checksums written to .sha256 sidecar files."
echo "Sign the archives with: minisign -S -m ${DIST_DIR}/party-crm-${VERSION}.zip"
