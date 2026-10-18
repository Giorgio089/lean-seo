# T01 – Open Graph + Twitter Card

**Date:** 2026-05-15
**Status:** Planned
**Sprint:** [BAM Production Sprint](2026-05-15-bam-production-sprint.md)
**Priority:** 🔴 Critical
**Estimate:** ~3–4 h

**Implement after T02** – T02 already sets up the DB migration and adds the `og_image`
column. T01 builds the full output layer on top.

---

## Goal

Output complete Open Graph and Twitter Card meta tags for every public post/page.
Sources (in priority order):

1. Per-post override fields stored in `wp_leanseo_data`
2. Post thumbnail (featured image)
3. Global fallback OG image from Site Settings (T03)

---

## Output

```html
<!-- Open Graph -->
<meta property="og:type"        content="article">
<meta property="og:title"       content="PR Agentur Hamburg – Kommunikation trifft Daten">
<meta property="og:description" content="Bamboo Consulting in Hamburg: B2B-PR, LinkedIn…">
<meta property="og:url"         content="https://www.bambooconsulting.de/">
<meta property="og:image"       content="https://www.bambooconsulting.de/…/og-fallback.jpg">
<meta property="og:site_name"   content="Bamboo Consulting">
<meta property="og:locale"      content="de_DE">

<!-- Twitter / X Card -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="PR Agentur Hamburg – Kommunikation trifft Daten">
<meta name="twitter:description" content="Bamboo Consulting in Hamburg: B2B-PR, LinkedIn…">
<meta name="twitter:image"       content="https://www.bambooconsulting.de/…/og-fallback.jpg">
```

`og:type` is `website` on the front page and `article` on all posts.

---

## Task 1: DB column (shared with T02 migration)

T02 already adds `og_image TEXT NULL` to the `dbDelta()` definition and bumps the schema
version to `'2'`. No additional migration work needed here – this task is **complete after
T02 is merged**.

Verify that `og_image` is in `ALLOWED_FIELDS` and `normalize_row()` in `class-data-store.php`
(added in T02).

---

## Task 2: Meta hijack – `_lean_seo_og_image`

T02 already adds `'_lean_seo_og_image' => 'og_image'` to `META_TO_COLUMN`. Add the
sanitize branch in `sanitize_value()`:

**Files:**
- Modify: `includes/class-meta-hijack.php`

```php
if ( '_lean_seo_og_image' === $meta_key ) {
    return function_exists( 'esc_url_raw' )
        ? esc_url_raw( (string) $meta_value )
        : filter_var( (string) $meta_value, FILTER_VALIDATE_URL ) ?: '';
}
```

---

## Task 3: Resolve the OG image URL

**Files:**
- Modify: `includes/class-frontend.php`

Add a private helper that returns the best available image URL:

```php
private function resolve_og_image( array $seo, int $post_id ): string {
    // 1. Per-post override
    if ( ! empty( $seo['og_image'] ) ) {
        return (string) $seo['og_image'];
    }

    // 2. Post featured image
    if ( $post_id > 0 && function_exists( 'get_post_thumbnail_id' ) ) {
        $thumb_id = (int) get_post_thumbnail_id( $post_id );
        if ( $thumb_id > 0 ) {
            $url = wp_get_attachment_image_url( $thumb_id, 'large' );
            if ( $url ) {
                return (string) $url;
            }
        }
    }

    // 3. Global fallback (T03 – Site_Settings class)
    //    Guarded so T01 can ship before T03 is complete.
    if ( class_exists( '\LeanSEO\Site_Settings' ) ) {
        $global = \LeanSEO\Site_Settings::instance()->get();
        if ( ! empty( $global['og_fallback_image'] ) ) {
            return (string) $global['og_fallback_image'];
        }
    }

    return '';
}
```

---

## Task 4: Build OG tag string

**Files:**
- Modify: `includes/class-frontend.php`

```php
public function build_open_graph_tags( array $seo, int $post_id ): string {
    $title       = ! empty( $seo['meta_title'] )
        ? wp_strip_all_tags( $seo['meta_title'] )
        : ( function_exists( 'get_the_title' ) && $post_id > 0
            ? wp_strip_all_tags( get_the_title( $post_id ) )
            : ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : '' ) );

    $description = ! empty( $seo['meta_desc'] )
        ? wp_strip_all_tags( $seo['meta_desc'] )
        : ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'description' ) : '' );

    $url         = function_exists( 'get_permalink' ) && $post_id > 0
        ? (string) get_permalink( $post_id )
        : ( function_exists( 'home_url' ) ? home_url( '/' ) : '' );

    $type        = ( function_exists( 'is_front_page' ) && is_front_page() )
        ? 'website'
        : 'article';

    $site_name   = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : '';
    $locale      = function_exists( 'get_locale' )   ? get_locale()           : 'de_DE';
    $image       = $this->resolve_og_image( $seo, $post_id );

    $tags = [];

    if ( '' !== $type )      { $tags[] = '<meta property="og:type"        content="' . esc_attr( $type )        . '">'; }
    if ( '' !== $title )     { $tags[] = '<meta property="og:title"       content="' . esc_attr( $title )       . '">'; }
    if ( '' !== $description){ $tags[] = '<meta property="og:description" content="' . esc_attr( $description ) . '">'; }
    if ( '' !== $url )       { $tags[] = '<meta property="og:url"         content="' . esc_url( $url )          . '">'; }
    if ( '' !== $image )     { $tags[] = '<meta property="og:image"       content="' . esc_url( $image )        . '">'; }
    if ( '' !== $site_name ) { $tags[] = '<meta property="og:site_name"   content="' . esc_attr( $site_name )   . '">'; }
    if ( '' !== $locale )    { $tags[] = '<meta property="og:locale"      content="' . esc_attr( $locale )      . '">'; }

    return implode( "\n", $tags );
}
```

---

## Task 5: Build Twitter Card tag string

**Files:**
- Modify: `includes/class-frontend.php`

```php
public function build_twitter_card_tags( array $seo, int $post_id ): string {
    $image = $this->resolve_og_image( $seo, $post_id );
    $card  = '' !== $image ? 'summary_large_image' : 'summary';

    $title = ! empty( $seo['meta_title'] )
        ? wp_strip_all_tags( $seo['meta_title'] )
        : ( function_exists( 'get_the_title' ) && $post_id > 0
            ? wp_strip_all_tags( get_the_title( $post_id ) )
            : '' );

    $description = ! empty( $seo['meta_desc'] )
        ? wp_strip_all_tags( $seo['meta_desc'] )
        : '';

    $tags = [ '<meta name="twitter:card" content="' . esc_attr( $card ) . '">' ];

    if ( '' !== $title )       { $tags[] = '<meta name="twitter:title"       content="' . esc_attr( $title )       . '">'; }
    if ( '' !== $description ) { $tags[] = '<meta name="twitter:description" content="' . esc_attr( $description ) . '">'; }
    if ( '' !== $image )       { $tags[] = '<meta name="twitter:image"       content="' . esc_url( $image )        . '">'; }

    return implode( "\n", $tags );
}
```

---

## Task 6: Wire into `output_meta_and_schema()`

**Files:**
- Modify: `includes/class-frontend.php`

Extend `output_meta_and_schema()`:

```php
$post_id = (int) ( $seo['post_id'] ?? 0 );

$og = $this->build_open_graph_tags( $seo, $post_id );
if ( '' !== $og ) {
    echo $og . "\n";
}

$twitter = $this->build_twitter_card_tags( $seo, $post_id );
if ( '' !== $twitter ) {
    echo $twitter . "\n";
}
```

---

## Task 7: Gutenberg sidebar – OG image override field

**Files:**
- Modify: `assets/js/sidebar.js`

Add after the existing Meta Description field:

```jsx
<TextControl
    label="OG Bild URL (optional)"
    value={ meta._lean_seo_og_image || '' }
    onChange={ ( value ) => updateMeta( '_lean_seo_og_image', value ) }
    help="Leer lassen = Featured Image oder globales Fallback-Bild wird verwendet."
/>
```

Run `npm run build` after editing.

---

## Task 8: Tests

**Files:**
- Modify: `tests/FrontendTest.php`

Add test cases:

- `test_og_title_uses_meta_title()` – `meta_title` in `$seo` appears in `og:title`.
- `test_og_type_is_website_on_front_page()` – when `is_front_page()` is true, `og:type` is
  `website`.
- `test_og_type_is_article_on_post()` – when `is_front_page()` is false, `og:type` is
  `article`.
- `test_og_image_from_seo_override()` – non-empty `og_image` in `$seo` returns that URL in
  `og:image`.
- `test_og_image_empty_when_no_source()` – `og_image` empty, no thumbnail, no global
  fallback → no `og:image` tag output.
- `test_twitter_card_is_summary_when_no_image()` – no image source → `twitter:card` is
  `summary`.
- `test_twitter_card_is_summary_large_image_when_image_present()` – image resolved →
  `summary_large_image`.

Run: `phpunit --configuration phpunit.xml.dist --filter FrontendTest`
Expected: all pass.
