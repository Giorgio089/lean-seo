#!/bin/bash

# Configuration
PLUGIN_NAME="lean-seo"
DIST_DIR="./dist"
TEMP_DIR="$DIST_DIR/$PLUGIN_NAME"
ZIP_NAME="$PLUGIN_NAME.zip"

echo "🚀 Starting Lean-SEO Deployment Build..."

# 1. Clean up old builds
echo "🧹 Cleaning up old dist files..."
rm -rf "$DIST_DIR"
mkdir -p "$TEMP_DIR"

# 2. Build JS Assets (Optional: remove if not using npm build)
# echo "🔨 Building assets..."
# npm run build

# 3. Copy production files only
echo "📦 Copying production files..."
cp -r includes "$TEMP_DIR/"
cp -r build "$TEMP_DIR/"
cp -r assets "$TEMP_DIR/"
cp lean-seo.php "$TEMP_DIR/"
cp README.md "$TEMP_DIR/"
cp uninstall.php "$TEMP_DIR/"

# 4. Create ZIP
echo "🤐 Creating ZIP file..."
cd "$DIST_DIR"
zip -rq "$ZIP_NAME" "$PLUGIN_NAME"
cd ..

# 5. Report results
SIZE=$(du -sh "$DIST_DIR/$ZIP_NAME" | cut -f1)
echo "✅ Done! Clean ZIP created: $DIST_DIR/$ZIP_NAME ($SIZE)"
echo "✨ Your plugin is now truly LEAN."
