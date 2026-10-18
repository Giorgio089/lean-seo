# T04 – Enhanced LocalBusiness Schema

**Date:** 2026-05-15
**Status:** Planned
**Sprint:** [BAM Production Sprint](2026-05-15-bam-production-sprint.md)
**Priority:** 🟡 Medium
**Estimate:** ~1 h

**Depends on T03** – pulls address and phone from `Site_Settings`.

---

## Goal

The current `local_business` schema output only emits `name`, `url`, and `description`.
For a Hamburg B2B agency with a real address and phone number, Google expects the full
`PostalAddress` block and a `telephone` field to qualify for rich results.

---

## Current output (before)

```json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Bamboo Consulting",
  "url": "https://www.bambooconsulting.de/",
  "description": "PR-Agentur Hamburg…"
}
```

## Target output (after)

```json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Bamboo Consulting",
  "url": "https://www.bambooconsulting.de/",
  "description": "PR-Agentur Hamburg…",
  "telephone": "+49 (0)40 33 46 108-11",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Eppendorfer Weg 235",
    "postalCode": "20251",
    "addressLocality": "Hamburg",
    "addressCountry": "DE"
  },
  "image": "https://…/logo.jpg"
}
```

---

## Task 1: Extend `generate_schema()` – `local_business` case

**Files:**
- Modify: `includes/class-frontend.php`

Replace the current `local_business` case body:

```php
case 'local_business':
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
        '@type' => 'LocalBusiness',
        'name'  => $name,
        'url'   => $url,
    ] );

    if ( '' !== $description ) {
        $schema['description'] = $description;
    }

    // Address block – only emit if at least street is configured
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

    // Telephone
    $phone = (string) ( $settings['org_phone'] ?? '' );
    if ( '' !== $phone ) {
        $schema['telephone'] = $phone;
    }

    // Logo / image
    $logo = (string) ( $settings['org_logo_url'] ?? '' );
    if ( '' !== $logo ) {
        $schema['image'] = $logo;
    } elseif ( $post_id > 0 && function_exists( 'get_post_thumbnail_id' ) ) {
        $thumb_id = (int) get_post_thumbnail_id( $post_id );
        if ( $thumb_id > 0 ) {
            $img_url = wp_get_attachment_image_url( $thumb_id, 'large' );
            if ( $img_url ) {
                $schema['image'] = (string) $img_url;
            }
        }
    }

    return $schema;
```

---

## Task 2: Tests

**Files:**
- Modify: `tests/FrontendTest.php`

Add test cases:

- `test_local_business_includes_address_when_settings_present()` – mock `Site_Settings` with
  street/city/postal → `address` key is present in returned schema array.
- `test_local_business_omits_address_when_street_empty()` – empty street → no `address` key.
- `test_local_business_includes_telephone_when_set()` – phone in settings → `telephone` key.

Run: `phpunit --configuration phpunit.xml.dist --filter FrontendTest`
Expected: all pass.
