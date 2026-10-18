# T05 – Organization Schema Type

**Date:** 2026-05-15
**Status:** Planned
**Sprint:** [BAM Production Sprint](2026-05-15-bam-production-sprint.md)
**Priority:** 🟡 Medium
**Estimate:** ~1 h

**Depends on T03** – pulls org data from `Site_Settings`.

---

## Goal

Add `Organization` as a selectable schema type in the Gutenberg sidebar.

`LocalBusiness` is correct for the `/kontakt/` page (physical location, local search).
`Organization` is better for the homepage – it signals the brand entity to Google's
Knowledge Graph and to LLMs parsing the site.

---

## Target output

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Bamboo Consulting",
  "url": "https://www.bambooconsulting.de/",
  "description": "PR-Agentur Hamburg…",
  "logo": {
    "@type": "ImageObject",
    "url": "https://www.bambooconsulting.de/…/logo.png"
  },
  "telephone": "+49 (0)40 33 46 108-11",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Eppendorfer Weg 235",
    "postalCode": "20251",
    "addressLocality": "Hamburg",
    "addressCountry": "DE"
  }
}
```

---

## Task 1: Add `organization` case to `generate_schema()`

**Files:**
- Modify: `includes/class-frontend.php`

Add before the `default:` case:

```php
case 'organization':
    $settings = class_exists( '\LeanSEO\Site_Settings' )
        ? \LeanSEO\Site_Settings::instance()->get()
        : [];

    $name = ! empty( $settings['org_name'] )
        ? $settings['org_name']
        : ( function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'name' ) : '' );

    $url = function_exists( 'home_url' ) ? (string) home_url( '/' ) : '/';

    $description = '' !== ( $seo['meta_desc'] ?? '' )
        ? wp_strip_all_tags( $seo['meta_desc'] )
        : ( function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'description' ) : '' );

    $schema = array_merge( $base, [
        '@type' => 'Organization',
        'name'  => $name,
        'url'   => $url,
    ] );

    if ( '' !== $description ) {
        $schema['description'] = $description;
    }

    // Logo as ImageObject (Google prefers this over plain URL for Organization)
    $logo = (string) ( $settings['org_logo_url'] ?? '' );
    if ( '' !== $logo ) {
        $schema['logo'] = [
            '@type' => 'ImageObject',
            'url'   => $logo,
        ];
    }

    // Telephone
    $phone = (string) ( $settings['org_phone'] ?? '' );
    if ( '' !== $phone ) {
        $schema['telephone'] = $phone;
    }

    // Address
    $street = (string) ( $settings['org_street'] ?? '' );
    if ( '' !== $street ) {
        $schema['address'] = array_filter( [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $street,
            'postalCode'      => (string) ( $settings['org_postal']  ?? '' ),
            'addressLocality' => (string) ( $settings['org_city']    ?? '' ),
            'addressCountry'  => (string) ( $settings['org_country'] ?? 'DE' ),
        ] );
    }

    return $schema;
```

---

## Task 2: Allow `organization` in Meta_Hijack sanitizer

**Files:**
- Modify: `includes/class-meta-hijack.php`

In `sanitize_value()`, extend the `_lean_seo_schema` allowed values:

```php
$allowed = [ 'none', 'article', 'local_business', 'organization', 'faq' ];
```

---

## Task 3: Sidebar – add option to SelectControl

**Files:**
- Modify: `assets/js/sidebar.js`

```jsx
options={ [
    { label: 'Kein Schema',     value: 'none'           },
    { label: 'Artikel',         value: 'article'         },
    { label: 'Local Business',  value: 'local_business'  },
    { label: 'Organisation',    value: 'organization'    },
    { label: 'FAQ',             value: 'faq'             },
] }
```

Run `npm run build` after editing.

---

## Task 4: Tests

**Files:**
- Modify: `tests/FrontendTest.php`

Add test cases:

- `test_organization_schema_returns_correct_type()` – `schema_type = 'organization'` → `@type`
  is `Organization`.
- `test_organization_logo_is_image_object()` – logo URL in settings → `logo` key is an array
  with `@type: ImageObject`.
- `test_meta_hijack_allows_organization_schema_type()` – `sanitize_value( '_lean_seo_schema',
  'organization' )` returns `'organization'`.

Run: `phpunit --configuration phpunit.xml.dist --filter FrontendTest`
Expected: all pass.
