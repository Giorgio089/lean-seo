# Lean SEO AI Phase Design

**Date:** 2026-03-11

**Status:** Draft for next phase after release hardening

## Goal

Define the narrowest useful AI/GEO expansion for Lean SEO without destabilizing the current V1 storage and editor foundation.

## Recommended Direction

### `llms.txt` and `agents.json`

Empfehlung: virtuelle Endpunkte statt generierter Dateien.

Begruendung:

- keine zusaetzlichen Schreibpfade ins Dateisystem
- Inhalte bleiben direkt an WordPress-Daten und Plugin-Konfiguration gekoppelt
- spaetere Erweiterungen koennen zentral ueber PHP-Services und Cache gesteuert werden

Startpunkt:

- `llms.txt` als Rewrite- oder Request-Endpoint mit Plain-Text-Response
- `agents.json` als JSON-Endpoint mit klar versioniertem Response-Schema

### REST API Scope

Empfehlung: zuerst read-only.

Begruendung:

- geringeres Risiko bei Authentifizierung und Schreibrechten
- ausreichend fuer erste Automations- und Analyse-Workflows
- Schreiboperationen koennen spaeter ergaenzt werden, sobald die Datenvertraege stabil sind

Erste Route-Familie:

- `GET /lean-seo/v1/posts/<id>`
- `GET /lean-seo/v1/posts/<id>/seo`
- optional spaeter Listen- oder Bulk-Endpunkte fuer interne Automationen

### Permissions Model

Empfehlung:

- oeffentliche Lesbarkeit nur fuer bewusst oeffentliche AI-Endpunkte wie `llms.txt` und `agents.json`
- REST-Routen standardmaessig nur fuer authentifizierte Nutzer mit `edit_posts`
- spaetere Schreib-Endpunkte strenger, zum Beispiel `edit_post` pro Objekt

Begruendung:

- oeffentliche Discovery und interne Automationsschreibpfade sollten nicht denselben Sicherheitsrahmen teilen
- die bestehende Post-zentrierte WordPress-Berechtigungslogik kann weiter genutzt werden

## Architekturgrenzen fuer Phase 2

- `Data_Store` bleibt die Source of Truth
- keine Duplizierung von SEO-Daten in Dateien oder separaten Tabellen
- neue Endpunkte duerfen die Editor-Speicherpfade nicht veraendern
- zuerst einfache Responses, keine KI-Generierung im Plugin-Core

## Nicht Teil der naechsten Phase

- Yoast- oder Rank-Math-Importer
- generative Textvorschlaege im Editor
- komplexe Bulk-Mutationen per REST
- externe API-Integrationen im Plugin-Core
