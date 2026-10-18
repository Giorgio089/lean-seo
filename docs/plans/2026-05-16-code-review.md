# Lean SEO v0.2.0 – Code Review (WordPress Wizard)

> **Reviewer:** WordPress Wizard Agent (via Pi)  
> **Datum:** 16.05.2026  
> **Plugin-Version:** 0.2.0  
> **Basis:** Sprint-Plan `docs/plans/2026-05-15-bam-production-sprint.md`  
> **Status:** Production-Ready Check vor BAM-Deployment

---

## Gesamtbewertung

**Architektur: ✅ Sauber** – Service-Container, `final`-Klassen, Namespace, `ABSPATH`-Guard, PSR-ähnlicher Autoloader. Sehr solide Grundlage für ein Lean-Plugin.

**Kritische Bugs: 2**  
**Sicherheit: 1 Medium**  
**Code Quality: 4 Verbesserungen**  
**Bekannte Gaps (Sprint-Items): T01–T07 noch offen (wie geplant)**

---

## Priorisierte Findings

| Prio | Klasse | Issue | Impact | ICE |
|------|--------|-------|--------|-----|
| 🔴 **Bug** | `class-frontend.php` | `array_merge` Reihenfolge falsch – Globals überschreiben Post-Daten | Post-spezifische SEO-Felder werden ignoriert | 9/9/9 |
| 🔴 **Bug** | `lean-seo.php` | Falscher Filtername `wp_sitemaps_posts_entry_args` → existiert nicht in WP Core | noindex-Posts erscheinen weiterhin in Sitemap | 8/9/8 |
| 🟠 **Security** | `class-meta-hijack.php` | Boolean-Rückgabe im `hijack_get_meta` gibt `''` statt `false` zurück | Gutenberg-Boolean-Felder verhalten sich falsch; könnte noindex-Checkbox nie anzeigen | 7/8/8 |
| 🟡 **Quality** | `lean-seo.php` | Kein `LEAN_SEO_PLUGIN_FILE`-Konstante | `plugins_url()` in `Admin_Assets` nicht zuverlässig; Debug-Pfade fehlen | 5/9/9 |
| 🟡 **Quality** | `lean-seo.php` | Sitemap-Filter: `new Data_Store()` im Callback | Singleton-Pattern wird umgangen; DB-Cache wird pro Post neu initialisiert | 4/8/7 |
| 🟡 **Quality** | `class-admin-assets.php` | `plugins_url()` mit falschem `$path`-Argument | Build-Asset-URL könnte in bestimmten Hosting-Setups falsch sein | 4/7/7 |
| ⚪ **Info** | `class-ai-endpoints-renderer.php` | `agents.json` Payload sehr minimal | Für LLM-Discovery ausreichend, aber `capabilities[]` fehlt | 2/9/8 |

---

## Bug 1 – `array_merge` Reihenfolge verkehrt 🔴

**Datei:** `includes/class-frontend.php`, Methode `get_current_seo_data()`

**Problem:**
```php
// AKTUELL (falsch):
return array_merge( $globals, $data );
//        ↑ globals     ↑ post-data
// Ergebnis: Globals ÜBERSCHREIBEN Post-spezifische Felder!
```

Wenn `$globals` einen `meta_title`-Fallback enthält und `$data` einen Post-spezifischen `meta_title`, gewinnt der Globals-Wert. Das bedeutet: Alle individuell gesetzten SEO-Titel werden ignoriert.

**Fix:**
```php
// KORREKT:
return array_merge( $globals, $data );
// → wird zu:
return array_merge( $globals, array_filter( $data, fn( $v ) => '' !== $v && null !== $v ) );
```

Wait – `array_filter` filtert zu aggressiv. Besser:
```php
private function get_current_seo_data(): array {
    if ( ! function_exists( 'get_queried_object_id' ) ) {
        return [];
    }

    $post_id = (int) get_queried_object_id();
    $globals = get_option( 'lean_seo_ai_endpoints', [] );
    
    if ( $post_id <= 0 ) {
        return $globals;
    }

    $data = $this->store->get_post_seo_data( $post_id );

    // Post-spezifische Werte haben Vorrang; nur nicht-leere Felder überschreiben
    foreach ( $data as $key => $value ) {
        if ( '' !== $value && 0 !== $value && null !== $value ) {
            $globals[ $key ] = $value;
        }
    }

    return $globals;
}
```

**Warum `array_merge($globals, $data)` falsch ist:** In PHP gewinnt bei `array_merge` der zweite Array bei gleichen String-Keys. Aber `$data` enthält aus `normalize_row()` immer ALLE Felder (mit Leerstring als Default). `$globals` würde also bei jedem Merge mit Leerstrings überschrieben – oder umgekehrt, je nach Absicht.

**Einfachster sicherer Fix:**
```php
// In get_current_seo_data():
$globals = get_option( 'lean_seo_ai_endpoints', [] );
$data    = $post_id > 0 ? $this->store->get_post_seo_data( $post_id ) : [];

// Post-Daten haben Vorrang, aber nur wenn nicht leer
return array_merge(
    $globals,
    array_filter( $data, static fn( $v ) => '' !== (string) $v && '0' !== (string) $v || in_array( $v, [0, 1], true ) )
);
```

**Empfehlung:** Diesen Bug **vor** dem ersten BAM-Deploy fixen. Sonst sind alle individuell gesetzten Meta-Titles/Descriptions wirkungslos.

---

## Bug 2 – Falscher Sitemap-Filter-Name 🔴

**Datei:** `lean-seo.php` (am Ende, nach `Plugin::instance()->boot()`)

**Problem:**
```php
// AKTUELL (falsch) – dieser Filter existiert NICHT in WP Core:
add_filter( 'wp_sitemaps_posts_entry_args', function( $args, $post ) { ... }, 10, 2 );
```

Der korrekte Filter in WordPress Core (seit WP 5.5) ist:
- **`wp_sitemaps_posts_entry`** → filtert individual Post-Einträge im Sitemap-Array; `false` zurückgeben um Post auszuschließen

**Fix:**
```php
// KORREKT:
add_filter(
    'wp_sitemaps_posts_entry',
    static function ( array $entry, \WP_Post $post ): ?array {
        $seo = \LeanSEO\Plugin::instance()
            ->service( 'data_store' )  // oder via singleton
            ->get_post_seo_data( $post->ID );

        if ( ! empty( $seo['robots_noindex'] ) ) {
            return null; // WP 5.9+ akzeptiert null zum Ausschließen
        }

        return $entry;
    },
    10,
    2
);
```

**Oder besser:** Dieser Code gehört in eine eigene Methode in `class-frontend.php` und registriert sich dort via `add_action('init', ...)`, um die `Data_Store`-Instanz aus dem Service-Container zu nutzen – statt `new Data_Store()`.

**Alternative via Query-Filter (sauberer, aber aufwändiger):**
```php
add_filter( 'wp_sitemaps_posts_query_args', function( $args, $post_type ) {
    // Alle noindex-Post-IDs aus der DB holen und ausschließen
    global $wpdb;
    $excluded = $wpdb->get_col(
        "SELECT post_id FROM {$wpdb->prefix}leanseo_data WHERE robots_noindex = 1"
    );
    if ( ! empty( $excluded ) ) {
        $args['post__not_in'] = array_merge( $args['post__not_in'] ?? [], $excluded );
    }
    return $args;
}, 10, 2 );
```

---

## Security Medium – Boolean Return in `hijack_get_meta` 🟠

**Datei:** `includes/class-meta-hijack.php`

**Problem:**
```php
public function hijack_get_meta( $check, int $object_id, string $meta_key, bool $single ) {
    $column   = $this->column_for_meta_key( $meta_key );
    $seo_data = $this->store->get_post_seo_data( $object_id );
    $value    = $seo_data[ $column ] ?? '';

    if ( $single ) {
        return $value; // ← gibt '0' oder 1 (int) zurück, nicht true/false
    }
    return [ $value ];
}
```

Für `_lean_seo_noindex` und `_lean_seo_nofollow` erwartet der Gutenberg-Editor einen `boolean`-Typ (gemäß `register_post_meta` mit `'type' => 'boolean'`). Der aktuelle Code gibt `0` (int) oder `1` (int) zurück, was in PHP zwar als falsy/truthy funktioniert, aber im REST-Context zu Typ-Inkonsistenzen führen kann.

**Fix:**
```php
if ( $single ) {
    // Cast booleans korrekt
    $bool_columns = [ 'robots_noindex', 'robots_nofollow' ];
    if ( in_array( $column, $bool_columns, true ) ) {
        return (bool) $value;
    }
    return $value;
}
return [ $value ];
```

---

## Quality – Kein `LEAN_SEO_PLUGIN_FILE` 🟡

**Datei:** `lean-seo.php`

**Problem:** Es gibt keine Konstante, die den absoluten Pfad zur Plugin-Datei hält. `Admin_Assets::enqueue_sidebar_assets()` ruft `plugins_url()` mit einem konstruierten Pfad auf, der auf manchen Hosting-Setups (Symlinks, Must-Use Plugins) fehlschlagen kann.

**Fix – am Anfang von `lean-seo.php` hinzufügen:**
```php
define( 'LEAN_SEO_FILE',    __FILE__ );
define( 'LEAN_SEO_DIR',     plugin_dir_path( __FILE__ ) );
define( 'LEAN_SEO_URL',     plugin_dir_url( __FILE__ ) );
define( 'LEAN_SEO_VERSION', '0.2.0' );
```

Dann in `class-admin-assets.php`:
```php
// Statt:
plugins_url( 'build/index.js', dirname( __DIR__ ) . '/lean-seo.php' )

// Verwenden:
defined( 'LEAN_SEO_URL' ) ? LEAN_SEO_URL . 'build/index.js' : ''
```

---

## Quality – `new Data_Store()` im Sitemap-Callback 🟡

**Problem:** Der Sitemap-Filter in `lean-seo.php` instanziiert `new \LeanSEO\Data_Store()` direkt, anstatt die bereits vom Service-Container gebookte Instanz zu verwenden. Das umgeht das interne `wp_cache` nicht (da jede Instanz den Cache korrekt nutzt), aber es ist inkonsistent mit dem Singleton-Ansatz des Plugins.

**Fix:** Nach Lösung von Bug 2 (korrekter Filter-Name) die `Data_Store`-Instanz aus dem Service-Container holen.

---

## Info – `agents.json` Minimal-Payload ⚪

**Datei:** `includes/class-ai-endpoints-renderer.php`

Das aktuelle Format ist valide und funktioniert. Für bessere LLM-Discovery könntest du später ergänzen:
```json
{
  "name": "Bamboo Consulting",
  "description": "B2B PR-Agentur für Industrie, Tech, Logistik und GreenTech.",
  "url": "https://www.bambooconsulting.de/",
  "capabilities": ["pr", "linkedin-marketing", "content-marketing", "seo"],
  "contact": "https://www.bambooconsulting.de/kontakt/",
  "language": "de"
}
```

Das `capabilities`-Array ist kein Standard, wird aber von einigen AI-Crawlern bereits ausgewertet. Low priority.

---

## Sprint-Items (T01–T07) – Status

Die noch ausstehenden Sprint-Items sind bewusst nicht Teil dieses Reviews, da sie per Sprint-Plan als "geplant" geführt werden. Vollständigkeit-Check:

| Ticket | Was | Status |
|--------|-----|--------|
| T01 | Open Graph + Twitter Card | ⏳ Geplant – `og_image` Spalte + Output-Layer fehlt noch |
| T02 | Canonical URL | ✅ Canonical ist bereits implementiert (in `class-frontend.php`) |
| T03 | Global Site Settings | ⏳ Geplant – `class-site-settings.php` fehlt noch |
| T04 | Enhanced LocalBusiness Schema | ⏳ Geplant – Adresse/Telefon in Schema fehlen noch |
| T05 | Organization Schema | ⏳ Geplant – nur `LocalBusiness` + `Article` implementiert |
| T06 | Sitemap noindex Filter | ⚠️ **Buggy** – falscher Filter-Name (siehe Bug 2 oben) |
| T07 | Version Bump + README | ⏳ Ausstehend |

**Hinweis T02:** Die Canonical-Implementierung ist bereits in `class-frontend.php` vorhanden (`output_canonical()`, `add_action('wp_head', [$this, 'output_canonical'], 1)`). T02 gilt damit als erledigt, sofern die DB-Spalte `canonical` korrekt via `dbDelta` angelegt wird.

---

## Empfohlene Fix-Reihenfolge

```
Bug 1 (array_merge) → Bug 2 (Sitemap-Filter) → Security (Boolean) → Constants → T01 → T03 → T04 → T05
```

**Alles in allem:** Das Plugin ist für ein v0.2.0 sehr sauber. Bug 1 und Bug 2 müssen vor dem BAM-Go-Live gepatcht werden. Der Rest ist entweder bereits geplant oder nice-to-have.

---

*Code Review erstellt via WordPress Wizard Skill · Pi · 16.05.2026*
