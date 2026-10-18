# Lean SEO

Lean SEO ist ein schlankes, performance-orientiertes WordPress-SEO-Plugin als Gegenentwurf zu ueberladenen Loesungen wie Yoast oder Rank Math. Der Fokus liegt auf sauberem Code, nativer WordPress-Integration und einer klaren technischen Basis fuer spaetere AI-/GEO-Erweiterungen.

## Geplante Architektur und Anforderungen

### Produktziele

- Zero Bloat: keine Werbebanner, keine Scores, keine Keyword-Dichte-Features
- Performance First: moeglichst viel ueber WordPress-Core, Object Cache und eine eigene Tabelle
- Native Editor-Integration: Gutenberg-Sidebar mit `@wordpress/components`
- Spaetere AI-/GEO-Erweiterbarkeit: klare Erweiterungspunkte fuer `llms.txt`, `agents.json` und eigene REST-Routen

### Technische Leitplanken

- SEO-Daten liegen nicht in `wp_postmeta`, sondern in einer dedizierten Tabelle `wp_leanseo_data`
- `register_post_meta()` bleibt nur die Kompatibilitaetsschicht fuer Editor und REST
- Alle Lean-SEO-Lese- und Schreibzugriffe laufen ueber eine zentrale PHP-Datenschicht
- Frontend-Ausgabe fuer Title, Meta Description, Robots und Schema bleibt bewusst minimal und nativ
- Build fuer die Gutenberg-Sidebar ueber `@wordpress/scripts`

## Was wurde umgesetzt (v0.5.0)

### Plugin-Fundament

- WordPress-Plugin-Bootstrap in [`lean-seo.php`](./lean-seo.php)
- schlanker Namespace-Autoloader in [`includes/class-loader.php`](./includes/class-loader.php)
- zentrale Plugin-Verdrahtung in [`includes/class-plugin.php`](./includes/class-plugin.php)
- Installationsroutine fuer die Custom Table in [`includes/class-installer.php`](./includes/class-installer.php)
- Plugin-Konstanten: `LEAN_SEO_FILE`, `LEAN_SEO_DIR`, `LEAN_SEO_URL`, `LEAN_SEO_VERSION`

### Datenspeicherung und Meta-Hijacking

- zentrale Datenschicht in [`includes/class-data-store.php`](./includes/class-data-store.php)
- Objekt-Cache-Nutzung fuer Post-bezogene SEO-Daten
- Abfangen von `get_post_metadata` und `update_post_metadata`
- Mapping der Lean-SEO-Meta-Keys auf die Custom Table in [`includes/class-meta-hijack.php`](./includes/class-meta-hijack.php)
- Registrierung der Meta-Keys fuer Gutenberg/REST als Kompatibilitaetsschicht

### Frontend-Ausgabe

- Ueberschreiben des Dokument-Titels, wenn ein eigener SEO-Titel vorhanden ist
- Setzen von `noindex` und `nofollow` ueber `wp_robots`
- Ausgabe der Meta Description
- Canonical URL Output
- Open Graph Tags (`og:type`, `og:title`, `og:description`, `og:url`, `og:image`, `og:site_name`, `og:locale`)
- Twitter Card Tags (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`)
- JSON-LD Schema fuer `Article`, `LocalBusiness` (mit Adresse + Telefon), `Organization`
- Sitemap noindex Filter (schliesst noindex-Posts aus `/wp-sitemap.xml` aus)
- Implementierung in [`includes/class-frontend.php`](./includes/class-frontend.php)

### Globale Site Settings

- Zentrale Einstellungen unter Settings > Lean SEO
- OG Fallback-Bild, Titel-Trennzeichen, Homepage Meta-Titel/Description
- Organisationsdaten (Name, Logo, Telefon, Adresse) fuer Schema-Ausgabe
- Implementierung in [`includes/class-site-settings.php`](./includes/class-site-settings.php) und [`includes/class-site-settings-admin.php`](./includes/class-site-settings-admin.php)

### Gutenberg-Sidebar

- nativer Sidebar-Panel-Build mit WordPress-Komponenten
- Felder fuer SEO-Titel, Description, OG Bild URL, `noindex`, `nofollow` und Schema-Typ
- Schema-Optionen: Kein Schema, Artikel, Local Business, Organisation, FAQ
- React-Code in [`assets/js/sidebar.js`](./assets/js/sidebar.js)
- Build-Entrypoint in [`src/index.js`](./src/index.js)
- generierter Build in [`build/index.js`](./build/index.js) und [`build/index.asset.php`](./build/index.asset.php)
- Enqueue-Logik in [`includes/class-admin-assets.php`](./includes/class-admin-assets.php)

### AI-Discovery-Endpunkte

- globale Root-Endpunkte fuer [`llms.txt`](./includes/class-ai-endpoints-routes.php) und [`agents.json`](./includes/class-ai-endpoints-routes.php)
- eigene Site-Settings ueber [`includes/class-ai-endpoints-settings.php`](./includes/class-ai-endpoints-settings.php)
- kleine Admin-Seite fuer die Konfiguration in [`includes/class-ai-endpoints-admin.php`](./includes/class-ai-endpoints-admin.php)
- deterministische Renderer in [`includes/class-ai-endpoints-renderer.php`](./includes/class-ai-endpoints-renderer.php)

### Tests und Tooling

- PHPUnit-Test-Harness in [`phpunit.xml.dist`](./phpunit.xml.dist) und [`tests/bootstrap.php`](./tests/bootstrap.php)
- Tests fuer Plugin-Boot, Datenspeicher, Meta-Hijack und Frontend
- Node-Setup ueber [`package.json`](./package.json) mit `@wordpress/scripts`

## Datenstruktur

Eine Zeile pro Beitrag in `{$wpdb->prefix}leanseo_data` mit:

- `post_id`
- `meta_title`
- `meta_desc`
- `robots_noindex`
- `robots_nofollow`
- `schema_type`
- `canonical`
- `og_image`
- `updated_at`

## Bewusst verschobener Scope

- Redirect Manager (`.htaccess` oder dediziertes Redirect-Plugin verwenden)
- Yoast / Rank Math Importer
- FAQ Schema (benoetigt FAQ Content-Modell)
- Scoring / Content-Analyse
- XML Sitemap Generierung (WP Core Sitemap unter `/wp-sitemap.xml` wird genutzt)

## Welche Tasks und Schritte noch offen sind

### Phase 3: AI- und GEO-Features

- eigene Lean-SEO-REST-API fuer AI-/Automation-Workflows
- saubere Permissions- und Response-Formate fuer GET/POST-Endpunkte

### Phase 4: Migration und Produktreife

- One-Click-Importer fuer Yoast- und RankMath-Daten
- erweiterte Tests fuer echte WordPress-Laufzeit und Editor-Speicherpfade
- manuelle Integrationstests in einer lokalen WordPress-Instanz
- Hartere Fehlerbehandlung und Admin-Hinweise bei Installations-/DB-Problemen

### Projekt- und Distributionsthemen

- GitHub Actions fuer automatisierte Tests und Build
- WordPress-konforme Readme fuer spaeteres Distribution-/Release-Setup
- Versionierung und erstes Release
- optional: saubere Deinstallationsroutine und spaetere Schema-Migrationen

## Entwicklung

### AI-Endpunkte lokal pruefen

- in WordPress die Lean-SEO-AI-Einstellungen speichern
- danach `llms.txt` unter `/llms.txt` und `agents.json` unter `/agents.json` abrufen
- bei deaktivierten Endpunkten sollte jeweils ein `404` zurueckkommen

### Lokale Befehle

```bash
npm ci
phpunit --configuration phpunit.xml.dist
npm run build
```

Die schnellste lokale Komplettpruefung ist:

```bash
npm ci
phpunit --configuration phpunit.xml.dist
npm run build
```

## Continuous Integration

Automatisierte Verifikation soll dieselben Kernschritte wie lokal ausfuehren:

- `npm ci`
- `npm run build`
- `phpunit --configuration phpunit.xml.dist`

## Planung

Die bisherigen Design- und Implementierungsdokumente liegen unter:

- [`docs/plans/2026-03-09-lean-seo-v1-design.md`](./docs/plans/2026-03-09-lean-seo-v1-design.md)
- [`docs/plans/2026-03-09-lean-seo-v1.md`](./docs/plans/2026-03-09-lean-seo-v1.md)
- [`docs/plans/2026-03-11-lean-seo-ai-endpoints-design.md`](./docs/plans/2026-03-11-lean-seo-ai-endpoints-design.md)
- [`docs/plans/2026-03-11-lean-seo-ai-endpoints.md`](./docs/plans/2026-03-11-lean-seo-ai-endpoints.md)
