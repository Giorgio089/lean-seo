# Lean SEO AI Endpoints Design

**Date:** 2026-03-11

**Status:** Approved and implemented on `codex/ai-endpoints`

**Goal:** Add the first public AI-facing discovery endpoints to Lean SEO as global site endpoints, backed by small plugin-managed settings and WordPress site metadata.

## Scope

This phase includes:

- a public `llms.txt` root endpoint
- a public `agents.json` root endpoint
- one plugin-owned settings option for both endpoints
- a minimal admin settings screen for site-wide endpoint configuration
- text and JSON rendering based only on site metadata and plugin configuration

This phase excludes:

- per-post or per-URL endpoint variants
- REST API routes for post SEO data
- generated files on disk
- AI text generation
- importer or migration tooling

## Recommended Architecture

The new functionality should live beside the existing post SEO services instead of extending the post data table. The plugin should introduce a small site-settings service for endpoint configuration, a lightweight admin settings screen, and an endpoint service that registers root-level request handling plus deterministic renderers.

This preserves the current architecture:

- `Data_Store` remains focused on post-level SEO data
- site-level AI endpoint configuration lives in one dedicated option
- public discovery responses are generated at request time
- the editor sidebar path remains unchanged

## Configuration Model

The new option should be stored under a single key such as `lean_seo_ai_endpoints`.

Normalized shape:

```php
[
	'enabled_llms_txt'   => true,
	'enabled_agents_json'=> true,
	'llms_summary'       => '',
	'llms_contact_url'   => '',
	'llms_policy_url'    => '',
	'agents_name'        => '',
	'agents_description' => '',
	'agents_url'         => '',
]
```

Normalization rules:

- booleans are stored strictly as `0` or `1`
- free-text fields are sanitized with text or textarea sanitizers
- URLs are sanitized and invalid values collapse to empty strings
- missing values fall back to safe defaults

## Admin Experience

Lean SEO should add a minimal settings page under the standard WordPress Settings area. This page should edit only the endpoint fields listed above.

The UI should stay intentionally small:

- enable/disable `llms.txt`
- enable/disable `agents.json`
- short site summary for `llms.txt`
- optional contact URL
- optional policy URL
- agent display name
- agent description
- optional agent URL override

No previews, no scoring, no advanced schema editing.

## Request Handling

The plugin should register root-level rewrites or query vars for:

- `/llms.txt`
- `/agents.json`

Recommended flow:

1. register rewrites on `init`
2. add query vars for endpoint detection
3. intercept the request before theme rendering
4. render the endpoint body directly
5. send the correct `Content-Type`
6. stop further rendering

If an endpoint is disabled, it should return a real `404`.

## Rendering Rules

### `llms.txt`

`llms.txt` should render plain text using:

- site name from `get_bloginfo( 'name' )`
- site description from `get_bloginfo( 'description' )`
- site URL from `home_url( '/' )`
- plugin summary override when configured
- optional contact and policy URLs

The format should be deterministic and small. Missing optional fields should simply be omitted.

### `agents.json`

`agents.json` should render a stable JSON object based on:

- configured agent name
- configured agent description
- configured or default site URL
- site name fallback when no explicit agent name is set
- site description fallback when no explicit agent description is set

The JSON contract should stay narrow and versionable rather than accepting arbitrary JSON blobs from settings.

## Error Handling

- invalid settings are normalized during save and again before rendering
- disabled endpoints return `404`
- empty optional values do not break rendering
- rendering must not depend on post context or the custom SEO table
- no filesystem writes and no fatal failure if optional fields are unset

## Testing Strategy

Automated coverage should focus on deterministic PHP behavior:

- settings normalization and defaults
- renderer output for `llms.txt`
- renderer output for `agents.json`
- request handling for enabled and disabled endpoints
- admin registration for the new settings page and option

The current lightweight PHPUnit harness can support this by adding test doubles for settings, rewrites, headers, and request state.
