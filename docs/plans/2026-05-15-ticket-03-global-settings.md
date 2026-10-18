# T03 – Global Site Settings

**Date:** 2026-05-15
**Status:** Planned
**Sprint:** [BAM Production Sprint](2026-05-15-bam-production-sprint.md)
**Priority:** 🟠 High
**Estimate:** ~2–3 h

**Implement after T01/T02.** T01's `resolve_og_image()` already has a guarded call to
`Site_Settings::instance()->get()` – this ticket makes that class real.

---

## Goal

One WP options-backed settings object that provides:

- Global OG fallback image (URL)
- Title separator (default `–`)
- Organisation name, phone, address (for T04 LocalBusiness schema)
- Organisation logo URL (for T05 Organization schema)
- Homepage-level meta title + meta description (front page has no single `post_id` to edit
  in the Gutenberg sidebar)

---

## Option key

`lean_seo_site_settings`

---

## Task 1: `class-site-settings.php`

**Files:**
- Create: `includes/class-site-settings.php`

```php
<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Site_Settings {
    private const OPTION_KEY = 'lean_seo_site_settings';
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function get(): array {
        $value = function_exists( 'get_option' )
            ? get_option( self::OPTION_KEY, [] )
            : [];

        return $this->sanitize( is_array( $value ) ? $value : [] );
    }

    public function sanitize( array $input ): array {
        $input = array_merge( $this->defaults(), $input );

        return [
            // Meta
            'title_separator'   => $this->sanitize_text( $input['title_separator'] ),
            'homepage_title'    => $this->sanitize_text( $input['homepage_title'] ),
            'homepage_desc'     => $this->sanitize_textarea( $input['homepage_desc'] ),

            // OG
            'og_fallback_image' => $this->sanitize_url( $input['og_fallback_image'] ),

            // Organisation (feeds T04 LocalBusiness + T05 Organization schema)
            'org_name'          => $this->sanitize_text( $input['org_name'] ),
            'org_logo_url'      => $this->sanitize_url( $input['org_logo_url'] ),
            'org_phone'         => $this->sanitize_text( $input['org_phone'] ),
            'org_street'        => $this->sanitize_text( $input['org_street'] ),
            'org_postal'        => $this->sanitize_text( $input['org_postal'] ),
            'org_city'          => $this->sanitize_text( $input['org_city'] ),
            'org_country'       => $this->sanitize_text( $input['org_country'] ),
        ];
    }

    public function option_key(): string {
        return self::OPTION_KEY;
    }

    private function defaults(): array {
        return [
            'title_separator'   => '–',
            'homepage_title'    => '',
            'homepage_desc'     => '',
            'og_fallback_image' => '',
            'org_name'          => function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : '',
            'org_logo_url'      => '',
            'org_phone'         => '',
            'org_street'        => '',
            'org_postal'        => '',
            'org_city'          => '',
            'org_country'       => 'DE',
        ];
    }

    private function sanitize_text( $v ): string {
        return function_exists( 'sanitize_text_field' )
            ? sanitize_text_field( (string) $v )
            : trim( strip_tags( (string) $v ) );
    }

    private function sanitize_textarea( $v ): string {
        return function_exists( 'sanitize_textarea_field' )
            ? sanitize_textarea_field( (string) $v )
            : trim( strip_tags( (string) $v ) );
    }

    private function sanitize_url( $v ): string {
        $s = function_exists( 'esc_url_raw' )
            ? esc_url_raw( (string) $v )
            : filter_var( (string) $v, FILTER_VALIDATE_URL );

        return is_string( $s ) ? $s : '';
    }
}
```

---

## Task 2: `class-site-settings-admin.php`

**Files:**
- Create: `includes/class-site-settings-admin.php`

Single admin page under **Settings › Lean SEO**. Plain WP Settings API, no JS required.

```php
<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Site_Settings_Admin {
    public function __construct( private Site_Settings $settings ) {
        if ( function_exists( 'add_action' ) ) {
            add_action( 'admin_menu',  [ $this, 'register_menu' ] );
            add_action( 'admin_init',  [ $this, 'register_settings' ] );
        }
    }

    public function register_menu(): void {
        if ( ! function_exists( 'add_options_page' ) ) {
            return;
        }

        add_options_page(
            'Lean SEO',
            'Lean SEO',
            'manage_options',
            'lean-seo',
            [ $this, 'render_page' ]
        );
    }

    public function register_settings(): void {
        if ( ! function_exists( 'register_setting' ) ) {
            return;
        }

        register_setting(
            'lean_seo_site_settings',
            $this->settings->option_key(),
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this->settings, 'sanitize' ],
                'default'           => $this->settings->get(),
            ]
        );
    }

    public function render_page(): void {
        $v = $this->settings->get();
        $k = $this->settings->option_key();
        $f = fn( $name ) => esc_attr( $k . '[' . $name . ']' );

        echo '<div class="wrap"><h1>Lean SEO – Einstellungen</h1>';
        settings_errors( 'lean_seo_site_settings' );
        echo '<form method="post" action="options.php">';
        if ( function_exists( 'settings_fields' ) ) {
            settings_fields( 'lean_seo_site_settings' );
        }

        // ── META ──────────────────────────────────────────────────────────
        echo '<h2>Meta</h2>';
        echo '<table class="form-table"><tbody>';
        $this->row( 'Titel-Trennzeichen', 'text', $f( 'title_separator' ), $v['title_separator'],
            'Wird zwischen Seitentitel und Sitename gesetzt. Standard: –' );
        $this->row( 'Homepage Meta-Titel', 'text', $f( 'homepage_title' ), $v['homepage_title'],
            'Überschreibt den automatischen Titel der Startseite.' );
        $this->textarea_row( 'Homepage Meta-Description', $f( 'homepage_desc' ), $v['homepage_desc'] );
        echo '</tbody></table>';

        // ── OPEN GRAPH ────────────────────────────────────────────────────
        echo '<h2>Open Graph</h2>';
        echo '<table class="form-table"><tbody>';
        $this->row( 'Fallback OG-Bild (URL)', 'url', $f( 'og_fallback_image' ), $v['og_fallback_image'],
            'Wird verwendet wenn kein Featured Image und kein per-Post-OG-Bild gesetzt ist.' );
        echo '</tbody></table>';

        // ── ORGANISATION ──────────────────────────────────────────────────
        echo '<h2>Organisation</h2>';
        echo '<p>Wird für LocalBusiness- und Organization-Schema verwendet.</p>';
        echo '<table class="form-table"><tbody>';
        $this->row( 'Name',       'text', $f( 'org_name' ),    $v['org_name'] );
        $this->row( 'Logo (URL)', 'url',  $f( 'org_logo_url' ), $v['org_logo_url'] );
        $this->row( 'Telefon',    'text', $f( 'org_phone' ),   $v['org_phone'],  '+49 40 123 456' );
        $this->row( 'Straße',     'text', $f( 'org_street' ),  $v['org_street'], 'Eppendorfer Weg 235' );
        $this->row( 'PLZ',        'text', $f( 'org_postal' ),  $v['org_postal'], '20251' );
        $this->row( 'Stadt',      'text', $f( 'org_city' ),    $v['org_city'],   'Hamburg' );
        $this->row( 'Land (ISO)', 'text', $f( 'org_country' ), $v['org_country'], 'DE' );
        echo '</tbody></table>';

        if ( function_exists( 'submit_button' ) ) {
            submit_button( 'Einstellungen speichern' );
        }

        echo '</form></div>';
    }

    private function row( string $label, string $type, string $name, string $value, string $placeholder = '' ): void {
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
        echo '<td><input type="' . esc_attr( $type ) . '" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="regular-text"' . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . '></td>';
        echo '</tr>';
    }

    private function textarea_row( string $label, string $name, string $value ): void {
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
        echo '<td><textarea id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" rows="3" class="large-text">' . esc_textarea( $value ) . '</textarea></td>';
        echo '</tr>';
    }
}
```

---

## Task 3: Register in Plugin::boot()

**Files:**
- Modify: `includes/class-plugin.php`

```php
$this->services['site_settings']       = Site_Settings::instance();
$this->services['site_settings_admin'] = new Site_Settings_Admin( $this->services['site_settings'] );
```

Add the two lines before `$this->booted = true`.

---

## Task 4: Autoloader

**Files:**
- Modify: `includes/class-loader.php`

Make sure `class-site-settings.php` and `class-site-settings-admin.php` are included in the
autoloader's class map. Follow the existing pattern in the loader.

---

## Task 5: Tests

**Files:**
- Create: `tests/SiteSettingsTest.php`

```php
final class SiteSettingsTest extends TestCase {
    public function test_sanitize_returns_all_keys(): void { ... }
    public function test_title_separator_default_is_dash(): void { ... }
    public function test_sanitize_strips_tags_from_text_fields(): void { ... }
    public function test_sanitize_rejects_invalid_url(): void { ... }
}
```

Run: `phpunit --configuration phpunit.xml.dist --filter SiteSettingsTest`
Expected: all pass.
