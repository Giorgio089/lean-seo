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

### Geplante Datenstruktur

Eine Zeile pro Beitrag in `{$wpdb->prefix}leanseo_data` mit:

- `post_id`
- `meta_title`
- `meta_desc`
- `robots_noindex`
- `robots_nofollow`
- `schema_type`
- `updated_at`

## Was wurde umgesetzt

### Plugin-Fundament

- WordPress-Plugin-Bootstrap in [`lean-seo.php`](./lean-seo.php)
- schlanker Namespace-Autoloader in [`includes/class-loader.php`](./includes/class-loader.php)
- zentrale Plugin-Verdrahtung in [`includes/class-plugin.php`](./includes/class-plugin.php)
- Installationsroutine fuer die Custom Table in [`includes/class-installer.php`](./includes/class-installer.php)

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
- einfache JSON-LD-Ausgabe fuer `article`, `local_business` und `faq`
- Implementierung in [`includes/class-frontend.php`](./includes/class-frontend.php)

### Gutenberg-Sidebar

- nativer Sidebar-Panel-Build mit WordPress-Komponenten
- Felder fuer SEO-Titel, Description, `noindex`, `nofollow` und Schema-Typ
- React-Code in [`assets/js/sidebar.js`](./assets/js/sidebar.js)
- Build-Entrypoint in [`src/index.js`](./src/index.js)
- generierter Build in [`build/index.js`](./build/index.js) und [`build/index.asset.php`](./build/index.asset.php)
- Enqueue-Logik in [`includes/class-admin-assets.php`](./includes/class-admin-assets.php)

### Tests und Tooling

- PHPUnit-Test-Harness in [`phpunit.xml.dist`](./phpunit.xml.dist) und [`tests/bootstrap.php`](./tests/bootstrap.php)
- Tests fuer Plugin-Boot, Datenspeicher, Meta-Hijack und Frontend
- Node-Setup ueber [`package.json`](./package.json) mit `@wordpress/scripts`

## Aktueller Reifegrad

### Implementierter V1-Stand

- Custom-Table-Storage, Meta-Hijacking, Frontend-Ausgabe und Editor-Sidebar sind als erste nutzbare Plugin-Basis vorhanden.
- Der aktuelle Fokus liegt nicht auf Feature-Breite, sondern auf einer schlanken, stabilen WordPress-Integration.

### Bekannte Luecken

- Die vorhandenen PHPUnit-Tests decken vor allem Kernverhalten mit Test-Doubles ab und noch keine echte WordPress-Laufzeitintegration.
- Installations-, Uninstall- und CI-Themen werden nachgezogen, bevor neue groessere Produktflaechen dazukommen.

### Bewusst verschobener Scope

- `llms.txt`, `agents.json` und eine eigene Lean-SEO-REST-API bleiben ausserhalb des aktuellen Release-Hardening-Zyklus.
- Importer fuer Yoast oder Rank Math sind ebenfalls nicht Teil des aktuellen V1-Umfangs.

## Welche Tasks und Schritte noch offen sind

### Phase 3: AI- und GEO-Features

- virtuelle Endpunkte fuer `llms.txt`
- virtuelle Endpunkte fuer `agents.json`
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

### Lokale Befehle

```bash
phpunit --configuration phpunit.xml.dist
npm install
npm run build
```

## Planung

Die bisherigen Design- und Implementierungsdokumente liegen unter:

- [`docs/plans/2026-03-09-lean-seo-v1-design.md`](./docs/plans/2026-03-09-lean-seo-v1-design.md)
- [`docs/plans/2026-03-09-lean-seo-v1.md`](./docs/plans/2026-03-09-lean-seo-v1.md)
