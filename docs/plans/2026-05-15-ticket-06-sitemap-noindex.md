# T06 – Sitemap noindex Filter

**Date:** 2026-05-15
**Status:** Planned
**Sprint:** [BAM Production Sprint](2026-05-15-bam-production-sprint.md)
**Priority:** 🟡 Medium
**Estimate:** ~1 h

**No dependencies.** Can be implemented in any order.

---

## Goal

Posts and pages marked `noindex` in Lean SEO must not appear in the WP core sitemap
(`/wp-sitemap.xml`). Since Lean SEO stores `robots_noindex` in a custom table (not in
`wp_postmeta`), the WP sitemap's built-in `exclude_from_search` logic does not apply.
A custom SQL subquery filter is required.

---

## Why a subquery and not a loop

The WP sitemap queries posts in batches. Filtering post-by-post via
`wp_sitemaps_posts_entry` would fire one `Data_Store::get_post_seo_data()` call per URL.
For a site with 50+ posts that is 50 extra DB reads per sitemap request.

A single `NOT IN (SELECT …)` subquery is one extra read regardless of batch size.

---

## Task 1: New class `class-sitemap.php`

**Files:**
- Create: `includes/class-sitemap.php`

```php
<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Sitemap {
    public function __construct() {
        if ( function_exists( 'add_filter' ) ) {
            add_filter( 'wp_sitemaps_posts_query_args', [ $this, 'exclude_noindex_posts' ] );
        }
    }

    /**
     * Adds a posts_where filter that excludes noindex entries via a subquery.
     * Uses a custom query arg as a flag so the posts_where hook only fires in
     * the sitemap context.
     *
     * @param array $args WP_Query args passed by the sitemap provider.
     * @return array
     */
    public function exclude_noindex_posts( array $args ): array {
        add_filter( 'posts_where', [ $this, 'inject_noindex_exclusion' ], 10, 2 );
        $args['lean_seo_exclude_noindex'] = true;

        return $args;
    }

    /**
     * Injects NOT IN subquery only when the lean_seo_exclude_noindex flag is set.
     * Immediately removes itself to avoid affecting unrelated queries.
     *
     * @param string   $where SQL WHERE clause.
     * @param \WP_Query $query Current WP_Query instance.
     * @return string
     */
    public function inject_noindex_exclusion( string $where, $query ): string {
        if ( ! $query->get( 'lean_seo_exclude_noindex' ) ) {
            return $where;
        }

        remove_filter( 'posts_where', [ $this, 'inject_noindex_exclusion' ], 10 );

        global $wpdb;

        if ( ! isset( $wpdb ) ) {
            return $where;
        }

        $table   = $wpdb->prefix . 'leanseo_data';
        $where  .= " AND {$wpdb->posts}.ID NOT IN (
                        SELECT post_id FROM {$table} WHERE robots_noindex = 1
                    )";

        return $where;
    }
}
```

---

## Task 2: Register in `Plugin::boot()`

**Files:**
- Modify: `includes/class-plugin.php`

```php
$this->services['sitemap'] = new Sitemap();
```

Add before `$this->booted = true`.

---

## Task 3: Autoloader

**Files:**
- Modify: `includes/class-loader.php`

Add `class-sitemap.php` to the class map, following the existing pattern.

---

## Task 4: Tests

**Files:**
- Create: `tests/SitemapTest.php`

```php
final class SitemapTest extends TestCase {

    public function test_exclude_noindex_posts_adds_flag_to_query_args(): void {
        $sitemap = new \LeanSEO\Sitemap();
        $args    = $sitemap->exclude_noindex_posts( [] );

        $this->assertTrue( (bool) ( $args['lean_seo_exclude_noindex'] ?? false ) );
    }

    public function test_inject_noindex_exclusion_returns_where_unchanged_without_flag(): void {
        $sitemap = new \LeanSEO\Sitemap();

        // WP_Query stub without the flag
        $query = new class {
            public function get( string $key ) { return false; }
        };

        $original = 'WHERE 1=1';
        $result   = $sitemap->inject_noindex_exclusion( $original, $query );

        $this->assertSame( $original, $result );
    }

    public function test_inject_noindex_exclusion_appends_subquery_when_flag_set(): void {
        $sitemap = new \LeanSEO\Sitemap();

        $query = new class {
            public function get( string $key ) { return 'lean_seo_exclude_noindex' === $key; }
        };

        $result = $sitemap->inject_noindex_exclusion( 'WHERE 1=1', $query );

        $this->assertStringContainsString( 'NOT IN', $result );
        $this->assertStringContainsString( 'robots_noindex = 1', $result );
    }
}
```

Run: `phpunit --configuration phpunit.xml.dist --filter SitemapTest`
Expected: all pass.

---

## Note on WP core sitemap availability

The WP core sitemap was introduced in WordPress 5.5. `lean-seo.php` already requires
`Requires at least: 6.4`, so no version guard is needed.

To verify the sitemap filter is active after installation:

1. Open `https://bam-relaunch.ddev.site/wp-sitemap.xml`
2. Mark one post as `noindex` via the Lean SEO sidebar
3. Reload the sitemap – the post URL must be absent
