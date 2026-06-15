Create an SEO/AEO/GEO-oriented article plan and article draft using the provided product and research data.

Return strict JSON with:

* title
* slug
* excerpt
* meta_description
* concise_answer
* body_html
* faq
* tags
* categories
* related_product_ids
* source_urls
* social_posts
* schema
* quality_score
* risk_score
  EOF

cat > bin/build-zip.sh <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="onkupon-autonomous-commerce-agent"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${ROOT_DIR}/dist"
ZIP_FILE="${DIST_DIR}/${PLUGIN_SLUG}.zip"

mkdir -p "$DIST_DIR"
rm -f "$ZIP_FILE"

cd "$ROOT_DIR"

if command -v zip >/dev/null 2>&1; then
zip -r "$ZIP_FILE" . 
-x "*.git*" 
-x "node_modules/*" 
-x "dist/*" 
-x ".DS_Store" 
-x "*.zip"
else
echo "zip command not found. Please install zip."
exit 1
fi

echo "Built: $ZIP_FILE"
