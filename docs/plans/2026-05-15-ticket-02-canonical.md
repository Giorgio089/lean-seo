# T02 – Canonical URL

**Date:** 2026-05-15
**Status:** Planned
**Sprint:** [BAM Production Sprint](2026-05-15-bam-production-sprint.md)
**Priority:** 🟠 High
**Estimate:** ~1 h

**Implement first** – smallest change, exercises the DB migration path that T01 also needs.

---

## Goal

Output a `<link rel="canonical">` tag for every public post/page. Default to `get_permalink()`.
Allow per-post override via a new Gutenberg sidebar field.

---

## Output

```html
<link rel="canonical" href="https://www.bambooconsulting.de/leistungen/">
```

---

## Task 1: DB migration – add `canonical` column

**Files:**
- Modify: `lean-seo.php`
- Modify: `includes/class-installer.php`

**Step 1: Bump the schema version constant**

In `lean-seo.php`, change:

```php
define( 'LEAN_SEO_SCHEMA_VERSION', '1' );
```

to:

```php
define( 'LEAN_SEO_SCHEMA_VERSION', '2' );
```

**Step 2: Add the column to the `dbDelta()` table definition**

In `class-installer.php`, in the `CREATE TABLE` string passed to `dbDelta()`, add the
`canonical` column. The full column list should now include:

```sql
og_image  TEXT NULL,
canonical TEXT NULL,
```

> Note: `og_image` is added at the same time (see T01). Include both columns in one
> `dbDelta()` call to avoid running two separate migrations.

**Step 3: Add a schema-version migration guard**

`dbDelta()` handles new columns idempotently, but add a version check so future migrations
have a pattern to follow:

```php
$stored = (int) get_option( 'lean_seo_schema_version', 0 );
if ( $stored < (int) LEAN_SEO_SCHEMA_VERSION ) {
    // run dbDelta
    update_option( 'lean_seo_schema_version', LEAN_SEO_SCHEMA_VERSION );
}
```

---

## Task 2: Data layer – add `canonical` field

**Files:**
- Modify: `includes/class-data-store.php`

**Step 1: Add `canonical` to `ALLOWED_FIELDS`**

```php
private const ALLOWED_FIELDS = [
    'meta_title',
    'meta_desc',
    'robots_noindex',
    'robots_nofollow',
    'schema_type',
    'og_image',   // added by T01
    'canonical',  // added here
];
```

**Step 2: Add `canonical` to `normalize_row()`**

```php
'canonical' => (string) ( $row['canonical'] ?? '' ),
```

---

## Task 3: Meta hijack – register `_lean_seo_canonical`

**Files:**
- Modify: `includes/class-meta-hijack.php`

Add the mapping:

```php
private const META_TO_COLUMN = [
    '_lean_seo_title'     => 'meta_title',
    '_lean_seo_desc'      => 'meta_desc',
    '_lean_seo_noindex'   => 'robots_noindex',
    '_lean_seo_nofollow'  => 'robots_nofollow',
    '_lean_seo_schema'    => 'schema_type',
    '_lean_seo_og_image'  => 'og_image',   // T01
    '_lean_seo_canonical' => 'canonical',  // here
];
```

Add sanitize branch in `sanitize_value()`:

```php
if ( '_lean_seo_canonical' === $meta_key ) {
    return function_exists( 'esc_url_raw' )
        ? esc_url_raw( (string) $meta_value )
        : filter_var( (string) $meta_value, FILTER_VALIDATE_URL ) ?: '';
}
```

---

## Task 4: Frontend output

**Files:**
- Modify: `includes/class-frontend.php`

**Step 1: Add `build_canonical_tag()` method**

```php
public function build_canonical_tag( array $seo, int $post_id ): string {
    // 1. Explicit per-post override
    if ( ! empty( $seo['canonical'] ) ) {
        return '<link rel="canonical" href="' . esc_url( $seo['canonical'] ) . '">';
    }

    // 2. Default: get_permalink for posts, home_url for front page
    if ( ! function_exists( 'get_permalink' ) ) {
        return '';
    }

    if ( function_exists( 'is_front_page' ) && is_front_page() ) {
        $url = function_exists( 'home_url' ) ? home_url( '/' ) : '';
    } else {
        $url = $post_id > 0 ? (string) get_permalink( $post_id ) : '';
    }

    if ( '' === $url ) {
        return '';
    }

    return '<link rel="canonical" href="' . esc_url( $url ) . '">';
}
```

**Step 2: Call it in `output_meta_and_schema()`**

```php
$canonical = $this->build_canonical_tag( $seo, (int) ( $seo['post_id'] ?? 0 ) );
if ( '' !== $canonical ) {
    echo $canonical . "\n";
}
```

---

## Task 5: Gutenberg sidebar

**Files:**
- Modify: `assets/js/sidebar.js`

Add a `TextControl` for the canonical override inside `LeanSeoPanel`, after the nofollow
toggle:

```jsx
<TextControl
    label="Canonical URL (optional)"
    value={ meta._lean_seo_canonical || '' }
    onChange={ ( value ) => updateMeta( '_lean_seo_canonical', value ) }
    help="Leer lassen = Standard-URL wird verwendet."
/>
```

Run `npm run build` after editing.

---

## Task 6: Tests

**Files:**
- Modify: `tests/FrontendTest.php`

Add test cases:

- `test_canonical_uses_explicit_override()` – non-empty `canonical` in `$seo` returns `<link
  rel="canonical" href="...">` with that exact URL.
- `test_canonical_returns_empty_string_when_no_post_id_and_no_override()` – `post_id = 0`,
  no override, WordPress functions unavailable → returns `''`.

Run: `phpunit --configuration phpunit.xml.dist --filter FrontendTest`
Expected: all pass.
