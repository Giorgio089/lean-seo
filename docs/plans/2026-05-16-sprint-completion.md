# Lean SEO v0.2.0 – Sprint Completion Report

> **Datum:** 16.05.2026
> **Plugin-Version:** 0.2.0
> **Sprint:** [BAM Production Sprint](2026-05-15-bam-production-sprint.md)
> **Basis:** [Code Review](2026-05-16-code-review.md) + Sprint-Tickets T01–T07

---

## Übersicht

Alle Sprint-Items und alle Findings aus der Code Review wurden in einer Session implementiert.

| Kategorie | Anzahl | Status |
|-----------|--------|--------|
| Code Review Bugs (🔴) | 2 | ✅ Behoben |
| Code Review Security (🟠) | 1 | ✅ Behoben |
| Code Review Quality (🟡) | 3 | ✅ Behoben |
| Sprint-Tickets (T01–T07) | 7 | ✅ Erledigt |
| **Gesamt** | **13** | **✅ Komplett** |

---

## Teil 1: Code Review Fixes

### 🔴 Bug 1 – `array_merge` Reihenfolge verkehrt

**Datei:** [`includes/class-frontend.php`](../includes/class-frontend.php) · Methode `get_current_seo_data()`

**Problem:** `array_merge($globals, $data)` – die Post-Daten aus `normalize_row()` enthalten immer ALLE Felder mit Leerstring-Defaults. Dadurch überschrieben die leeren Post-Felder die Globals, statt umgekehrt.

**Lösung:** `array_merge` durch eine `foreach`-Schleife ersetzt. Nur nicht-leere Post-Felder (`'' !== $value && 0 !== $value && null !== $value`) überschreiben die Globals. Post-spezifische Werte haben Vorrang, leere Felder fallen auf den Global-Fallback zurück.

```php
foreach ( $data as $key => $value ) {
    if ( '' !== $value && 0 !== $value && null !== $value ) {
        $globals[ $key ] = $value;
    }
}
return $globals;
```

---

### 🔴 Bug 2 – Falscher Sitemap-Filter-Name

**Datei:** [`lean-seo.php`](../lean-seo.php)

**Problem:** `wp_sitemaps_posts_entry_args` existiert nicht in WordPress Core. Der Filter wurde nie aufgerufen – noindex-Posts erschienen weiterhin in der Sitemap.

**Lösung:**
- Filter korrigiert zu `wp_sitemaps_posts_entry` (WP 5.5+)
- `new \LeanSEO\Data_Store()` ersetzt durch `Plugin::instance()->service('data_store')` – nutzt den Service-Container statt eine neue Instanz
- Typ-Hints und `static` Closure hinzugefügt
- Rückgabe `null` zum Ausschließen (WP 5.9+)

```php
add_filter(
    'wp_sitemaps_posts_entry',
    static function ( array $entry, \WP_Post $post ): ?array {
        $seo = \LeanSEO\Plugin::instance()
            ->service( 'data_store' )
            ->get_post_seo_data( $post->ID );
        if ( ! empty( $seo['robots_noindex'] ) ) {
            return null;
        }
        return $entry;
    },
    10, 2
);
```

---

### 🟠 Security – Boolean Return in `hijack_get_meta`

**Datei:** [`includes/class-meta-hijack.php`](../includes/class-meta-hijack.php) · Methode `hijack_get_meta()`

**Problem:** `robots_noindex` und `robots_nofollow` gaben `0` (int) oder `1` (int) zurück. Der Gutenberg-Editor und die REST-API erwarten für `'type' => 'boolean'` registrierte Meta-Felder echte Booleans.

**Lösung:** Boolean-Spalten werden jetzt explizit als `(bool)` gecastet.

```php
$bool_columns = [ 'robots_noindex', 'robots_nofollow' ];
if ( in_array( $column, $bool_columns, true ) ) {
    return (bool) $value;
}
```

---

### 🟡 Quality – Plugin-Konstanten

**Datei:** [`lean-seo.php`](../lean-seo.php)

Neue Konstanten am Anfang der Plugin-Datei:

```php
define( 'LEAN_SEO_FILE',    __FILE__ );
define( 'LEAN_SEO_DIR',     plugin_dir_path( __FILE__ ) );
define( 'LEAN_SEO_URL',     plugin_dir_url( __FILE__ ) );
define( 'LEAN_SEO_VERSION', '0.2.0' );
```

Macht Asset-URLs und Pfade robust gegen Symlinks und Must-Use-Plugin-Setups.

---

### 🟡 Quality – `service()` Methode im Plugin-Container

**Datei:** [`includes/class-plugin.php`](../includes/class-plugin.php)

Neue öffentliche `service(string $key): object` Methode, die Services aus dem Container zurückgibt. Wirft eine `RuntimeException` wenn der Service nicht existiert.

---

### 🟡 Quality – Admin Assets URL

**Datei:** [`includes/class-admin-assets.php`](../includes/class-admin-assets.php)

`plugins_url('build/index.js', dirname(__DIR__) . '/lean-seo.php')` ersetzt durch `LEAN_SEO_URL . 'build/index.js'`. Zusätzlich wird `LEAN_SEO_VERSION` als Fallback für die Asset-Version verwendet.

---

## Teil 2: Sprint-Tickets

### T01 – Open Graph + Twitter Card 🔴

**Priorität:** Critical · **Aufwand:** ~3 h · **Status:** ✅ Erledigt

#### Was wurde implementiert

**DB-Schema** – `og_image TEXT NULL` Spalte in [`class-installer.php`](../includes/class-installer.php) `dbDelta()` hinzugefügt.

**Data Store** – `og_image` in `ALLOWED_FIELDS` und `normalize_row()` in [`class-data-store.php`](../includes/class-data-store.php) registriert.

**Meta Hijack** – `_lean_seo_og_image => og_image` Mapping + URL-Sanitierung mit `esc_url_raw()` in [`class-meta-hijack.php`](../includes/class-meta-hijack.php).

**Frontend** – Drei neue Methoden in [`class-frontend.php`](../includes/class-frontend.php):

| Methode | Beschreibung |
|---------|-------------|
| `resolve_og_image()` | 3-stufiger Fallback: Per-Post Override → Featured Image → Globales Fallback (Site Settings) |
| `build_open_graph_tags()` | Erzeugt `og:type`, `og:title`, `og:description`, `og:url`, `og:image`, `og:site_name`, `og:locale` |
| `build_twitter_card_tags()` | Erzeugt `twitter:card` (summary/summary_large_image), `twitter:title`, `twitter:description`, `twitter:image` |

`og:type` ist `website` auf der Startseite und `article` auf allen anderen Seiten.

**Verdrahtung** – `output_meta_and_schema()` gibt jetzt OG + Twitter Tags vor dem Schema-Markup aus.

**Gutenberg Sidebar** – Neues "OG Bild URL" TextControl-Feld in [`assets/js/sidebar.js`](../assets/js/sidebar.js) mit Hilfstext.

#### Erwarteter Output

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

---

### T02 – Canonical URL ✅

**Status:** Bereits implementiert (vor diesem Sprint). `canonical TEXT NULL` Spalte und `output_canonical()` in `class-frontend.php` waren bereits vorhanden.

---

### T03 – Global Site Settings 🟠

**Priorität:** High · **Aufwand:** ~2 h · **Status:** ✅ Erledigt

#### Neue Dateien

| Datei | Beschreibung |
|-------|-------------|
| [`class-site-settings.php`](../includes/class-site-settings.php) | Singleton mit sanitized Option-Zugriff. Option Key: `lean_seo_site_settings` |
| [`class-site-settings-admin.php`](../includes/class-site-settings-admin.php) | WordPress Settings API Admin-Seite unter **Einstellungen › Lean SEO** |

#### Konfigurierbare Felder

| Feld | Typ | Default | Verwendung |
|------|-----|---------|------------|
| `title_separator` | Text | `–` | Zwischen Seitentitel und Sitename |
| `homepage_title` | Text | _(leer)_ | Überschreibt den automatischen Startseiten-Titel |
| `homepage_desc` | Textarea | _(leer)_ | Meta-Description der Startseite |
| `og_fallback_image` | URL | _(leer)_ | Fallback wenn kein Featured Image / OG Override |
| `org_name` | Text | Site Name | Firmenname für Schema |
| `org_logo_url` | URL | _(leer)_ | Logo für Organization/LocalBusiness Schema |
| `org_phone` | Text | _(leer)_ | Telefonnummer für Schema |
| `org_street` | Text | _(leer)_ | Straße für PostalAddress |
| `org_postal` | Text | _(leer)_ | PLZ für PostalAddress |
| `org_city` | Text | _(leer)_ | Stadt für PostalAddress |
| `org_country` | Text | `DE` | Land (ISO) für PostalAddress |

#### Registrierung

In [`class-plugin.php`](../includes/class-plugin.php) `boot()`:
```php
$this->services['site_settings']       = Site_Settings::instance();
$this->services['site_settings_admin'] = new Site_Settings_Admin( $this->services['site_settings'] );
```

Der Autoloader (`class-loader.php`) lädt die Klassen automatisch über die PSR-Namenskonvention.

---

### T04 – Enhanced LocalBusiness Schema 🟡

**Priorität:** Medium · **Aufwand:** ~1 h · **Status:** ✅ Erledigt · **Dependency:** T03

**Datei:** [`includes/class-frontend.php`](../includes/class-frontend.php) · `generate_schema()` → Case `local_business`

#### Änderungen

- `org_name` aus Site Settings als Firmenname (Fallback: `get_bloginfo('name')`)
- `PostalAddress`-Block mit `streetAddress`, `postalCode`, `addressLocality`, `addressCountry` – wird nur ausgegeben wenn mindestens `org_street` konfiguriert ist
- `telephone` aus Site Settings
- `org_logo_url` als `image` (Fallback: Featured Image)

#### Erwarteter Output

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

### T05 – Organization Schema 🟡

**Priorität:** Medium · **Aufwand:** ~1 h · **Status:** ✅ Erledigt · **Dependency:** T03

#### Änderungen

**Frontend** – Neuer `organization` Case in [`class-frontend.php`](../includes/class-frontend.php) `generate_schema()`:
- Logo als `ImageObject` (Google-Empfehlung für Organization)
- Telefon und PostalAddress wie bei LocalBusiness

**Meta Hijack** – `organization` in `$allowed` Array in [`class-meta-hijack.php`](../includes/class-meta-hijack.php) `sanitize_value()`.

**Sidebar** – "Organisation" Option im Schema-Dropdown in [`assets/js/sidebar.js`](../assets/js/sidebar.js).

#### Erwarteter Output

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Bamboo Consulting",
  "url": "https://www.bambooconsulting.de/",
  "description": "PR-Agentur Hamburg…",
  "logo": {
    "@type": "ImageObject",
    "url": "https://…/logo.png"
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

### T06 – Sitemap noindex Filter 🟡

**Status:** ✅ Erledigt (als Bug 2 der Code Review gefixt). Der Filter existierte bereits, hatte aber den falschen Hook-Namen. Korrigiert zu `wp_sitemaps_posts_entry`.

---

### T07 – Version Bump + README ⚪

**Priorität:** Wrap-up · **Status:** ✅ Erledigt

| Prüfpunkt | Status |
|-----------|--------|
| `Version: 0.2.0` im Plugin Header | ✅ |
| `LEAN_SEO_SCHEMA_VERSION = '2'` | ✅ |
| `LEAN_SEO_VERSION = '0.2.0'` Konstante | ✅ |
| `og_image TEXT NULL` in DB Schema | ✅ |
| README mit v0.2.0 Feature-Liste | ✅ |
| Gutenberg Build (`npm run build`) | ✅ (280ms) |
| Git Tag `v0.2.0` | ⏳ Manuell erstellen |

---

## Geänderte Dateien

| Datei | Aktion | Tickets |
|-------|--------|---------|
| `lean-seo.php` | Modifiziert | Bug 2, Quality (Konstanten) |
| `includes/class-frontend.php` | Modifiziert | Bug 1, T01, T04, T05 |
| `includes/class-meta-hijack.php` | Modifiziert | Security, T01, T05 |
| `includes/class-plugin.php` | Modifiziert | Quality (service), T03 |
| `includes/class-admin-assets.php` | Modifiziert | Quality (URL) |
| `includes/class-data-store.php` | Modifiziert | T01 |
| `includes/class-installer.php` | Modifiziert | T01 |
| `includes/class-site-settings.php` | **Neu** | T03 |
| `includes/class-site-settings-admin.php` | **Neu** | T03 |
| `assets/js/sidebar.js` | Modifiziert | T01, T05 |
| `build/index.js` | Regeneriert | Build |
| `build/index.asset.php` | Regeneriert | Build |
| `README.md` | Modifiziert | T07 |

---

## Architektur nach v0.2.0

```
lean-seo.php                          ← Bootstrap + Konstanten + Sitemap-Filter
├── includes/
│   ├── class-loader.php              ← PSR-Autoloader
│   ├── class-plugin.php              ← Service-Container (Singleton)
│   ├── class-installer.php           ← dbDelta (8 Spalten)
│   ├── class-data-store.php          ← Custom Table CRUD + wp_cache
│   ├── class-meta-hijack.php         ← get/update_post_metadata Interception
│   ├── class-frontend.php            ← Title, Meta, Canonical, OG, Twitter, Schema
│   ├── class-site-settings.php       ← ⭐ NEU: Globale Einstellungen (Singleton)
│   ├── class-site-settings-admin.php ← ⭐ NEU: Settings API Admin-Seite
│   ├── class-admin-assets.php        ← Gutenberg-Sidebar Enqueue
│   ├── class-ai-endpoints-*.php      ← llms.txt + agents.json
│   └── ...
├── assets/js/sidebar.js              ← Gutenberg Sidebar (7 Felder)
├── src/index.js                      ← Webpack Entry
└── build/                            ← Generierter Build
```

---

## Service-Container (`Plugin::boot()`)

| Key | Klasse | Beschreibung |
|-----|--------|-------------|
| `data_store` | `Data_Store` | Custom Table CRUD + Cache |
| `ai_endpoints_settings` | `AI_Endpoints_Settings` | LLMs.txt/agents.json Konfiguration |
| `ai_endpoints_admin` | `AI_Endpoints_Admin` | Admin-Seite AI-Endpunkte |
| `ai_endpoints_renderer` | `AI_Endpoints_Renderer` | Template-Rendering |
| `ai_endpoints_routes` | `AI_Endpoints_Routes` | Rewrite Rules |
| `meta_hijack` | `Meta_Hijack` | Meta-Interception Layer |
| `frontend` | `Frontend` | SEO-Output auf `wp_head` |
| `site_settings` | `Site_Settings` | ⭐ Globale Einstellungen |
| `site_settings_admin` | `Site_Settings_Admin` | ⭐ Admin-Seite |
| `admin_assets` | `Admin_Assets` | Editor JS Enqueue |

---

## Datenbank-Schema v2

```sql
CREATE TABLE {prefix}leanseo_data (
    post_id         BIGINT(20) UNSIGNED NOT NULL,
    meta_title      TEXT NULL,
    meta_desc       TEXT NULL,
    robots_noindex  TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    robots_nofollow TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    schema_type     VARCHAR(50) NOT NULL DEFAULT 'none',
    canonical       TEXT NULL,
    og_image        TEXT NULL,                              -- ⭐ NEU (T01)
    updated_at      DATETIME NULL,
    PRIMARY KEY  (post_id)
);
```

---

## Gutenberg Sidebar Felder

| Feld | Meta Key | Typ | Neu |
|------|----------|-----|-----|
| SEO Titel | `_lean_seo_title` | TextControl | |
| Meta Description | `_lean_seo_desc` | TextareaControl | |
| OG Bild URL | `_lean_seo_og_image` | TextControl | ⭐ |
| noindex | `_lean_seo_noindex` | ToggleControl | |
| nofollow | `_lean_seo_nofollow` | ToggleControl | |
| Schema.org Markup | `_lean_seo_schema` | SelectControl | erweitert |

Schema-Optionen: `none`, `article`, `local_business`, `organization` ⭐, `faq`

---

## Verifikation

| Check | Status |
|-------|--------|
| `npm run build` | ✅ webpack compiled successfully (280ms) |
| PHP Syntax (visuell) | ✅ Alle 13 Dateien geprüft |
| PHPUnit Tests | ⚠️ Lokal nicht ausführbar (kein PHP) – auf Server/CI prüfen |

---

## Deployment-Checkliste

```bash
# 1. Plugin auf BAM DDEV kopieren
cp -r /Users/georgsimic/Homeserver/development/active/lean-seo \
      /Users/georgsimic/Documents/01_Projects/BAM_relaunch/wordpress/wp-content/plugins/lean-seo

# 2. Aktivieren + DB-Migration triggern
cd /Users/georgsimic/Documents/01_Projects/BAM_relaunch/wordpress
ddev wp plugin deactivate lean-seo  # falls aktiv
ddev wp plugin activate lean-seo    # triggert dbDelta → og_image Spalte
ddev wp rewrite flush

# 3. Verifizieren
# - /wp-sitemap.xml lädt
# - https://bam-relaunch.ddev.site/llms.txt gibt Text zurück
# - https://bam-relaunch.ddev.site/agents.json gibt JSON zurück
# - Gutenberg Sidebar mit 7 Feldern sichtbar
# - view-source: zeigt <meta property="og:title"> und <link rel="canonical">
# - Einstellungen › Lean SEO zeigt Admin-Seite

# 4. Git Tag
git add -A
git commit -m "feat: lean seo 0.2.0 – open graph, global settings, enhanced schema, sitemap filter, code review fixes"
git tag -a v0.2.0 -m "0.2.0 – BAM production sprint"
```

---

*Dokumentation erstellt am 16.05.2026*
