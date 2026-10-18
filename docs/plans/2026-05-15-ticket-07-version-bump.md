# T07 – Version Bump + README

**Date:** 2026-05-15
**Status:** Planned
**Sprint:** [BAM Production Sprint](2026-05-15-bam-production-sprint.md)
**Priority:** ⚪ Wrap-up
**Estimate:** ~30 min

**Implement last** – after all other sprint tickets are merged and tested on DDEV.

---

## Goal

Ship lean-seo 0.2.0 as a clean, documented release commit.

---

## Task 1: Bump version in `lean-seo.php`

**Files:**
- Modify: `lean-seo.php`

```php
 * Version: 0.2.0
```

---

## Task 2: Bump DB schema version

Already done in T02. Confirm the constant reads:

```php
define( 'LEAN_SEO_SCHEMA_VERSION', '2' );
```

---

## Task 3: Update README

**Files:**
- Modify: `README.md`

Update the implemented features section to reflect the sprint additions:

```markdown
## Implemented (v0.2.0)

- Custom-table storage (`wp_leanseo_data`) – no wp_postmeta bloat
- Gutenberg sidebar: meta title, meta description, noindex, nofollow, schema type,
  OG image override, canonical URL override
- Frontend output: title, meta description, robots, canonical, Open Graph, Twitter Card,
  JSON-LD schema
- Schema types: Article, LocalBusiness (with address + phone), Organization
- LLMs.txt + agents.json endpoints (GEO / AI-visibility)
- Global site settings: OG fallback image, org data, homepage title/desc, title separator
- Sitemap noindex filter (excludes noindex posts from /wp-sitemap.xml)
- WP object cache integration
- Full PHPUnit test suite

## Deferred

- Redirect manager (use .htaccess or a dedicated redirect plugin)
- Yoast / Rank Math importer
- FAQ schema (requires FAQ content model)
- Scoring / content analysis
- XML sitemap generation (WP core sitemap is used)
```

---

## Task 4: Full test run before tagging

Run: `phpunit --configuration phpunit.xml.dist`
Expected: all tests pass, no regressions.

Run: `npm run build`
Expected: Gutenberg sidebar builds without errors.

---

## Task 5: Commit + tag

```bash
git add -A
git commit -m "feat: lean seo 0.2.0 – open graph, canonical, global settings, enhanced schema, sitemap filter"
git tag -a v0.2.0 -m "0.2.0 – BAM production sprint"
```

---

## Task 6: Install on BAM DDEV

Copy the plugin folder (or symlink) into the BAM relaunch WordPress:

```bash
cp -r /Users/georgsimic/Homeserver/development/active/lean-seo \
      /Users/georgsimic/Documents/01_Projects/BAM_relaunch/wordpress/wp-content/plugins/lean-seo
```

Activate via WP-CLI:

```bash
cd /Users/georgsimic/Documents/01_Projects/BAM_relaunch/wordpress
ddev wp plugin activate lean-seo
ddev wp rewrite flush
```

Verify:
- `/wp-sitemap.xml` loads
- `https://bam-relaunch.ddev.site/llms.txt` returns text
- `https://bam-relaunch.ddev.site/agents.json` returns JSON
- Gutenberg sidebar visible on any page/post
- `view-source:` on homepage shows `<meta property="og:title"` and `<link rel="canonical"`
