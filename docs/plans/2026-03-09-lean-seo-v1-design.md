# Lean SEO V1 Design

**Date:** 2026-03-09

**Status:** Approved

**Goal:** Build the first usable version of a lean WordPress SEO plugin with a custom-table storage model and a native Gutenberg sidebar, while deferring AI-facing endpoints and import/migration tooling to later phases.

## Product Scope

V1 includes:

- A new standalone WordPress plugin in `/Users/georgsimic/lean-seo`
- Plugin bootstrap, autoloading, and activation/install flow
- A dedicated table, `{$wpdb->prefix}leanseo_data`, as the source of truth
- Native Gutenberg sidebar UI built with `@wordpress/components`
- Transparent meta interception so the editor still works through `register_post_meta()` and REST
- Frontend title, meta description, robots, and schema output
- Basic caching and invalidation around SEO row reads
- Automated PHP tests for storage and frontend behavior

V1 excludes:

- `llms.txt`
- `agents.json`
- Custom Lean SEO REST API routes
- Yoast/RankMath importer
- Advanced admin screens, scoring, audits, or recommendations

## Recommended Architecture

The plugin will use standard WordPress post-meta registration purely as an editor compatibility layer. The Gutenberg sidebar writes to registered meta keys such as `_lean_seo_title` through `useEntityProp()`. PHP intercepts reads and writes for those keys and redirects them into a dedicated custom table.

This preserves a native editor workflow without letting the data live in `wp_postmeta`. It also keeps the JavaScript surface area minimal and makes the storage backend independent from the editor implementation.

## Folder Shape

Planned project structure:

```text
lean-seo/
  assets/
    js/
      sidebar.js
  build/
  includes/
    class-loader.php
    class-installer.php
    class-data-store.php
    class-meta-hijack.php
    class-frontend.php
    class-admin-assets.php
    class-plugin.php
  tests/
    bootstrap.php
    DataStoreTest.php
    MetaHijackTest.php
    FrontendTest.php
  docs/
    plans/
  lean-seo.php
  package.json
  phpunit.xml.dist
```

## Data Model

The custom table stores one row per post:

```sql
CREATE TABLE {$wpdb->prefix}leanseo_data (
  post_id BIGINT(20) UNSIGNED NOT NULL,
  meta_title TEXT NULL,
  meta_desc TEXT NULL,
  robots_noindex TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  robots_nofollow TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  schema_type VARCHAR(50) NOT NULL DEFAULT 'none',
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (post_id)
)
```

Design notes:

- `post_id` is the primary key to enforce exactly one SEO row per post.
- Text fields stay flexible and avoid premature limits.
- Boolean flags use `TINYINT(1)` for fast filtering and simple casting.
- `updated_at` helps later with imports, APIs, or debugging without changing the schema again.

## Core Components

### Bootstrap and Loader

`lean-seo.php` defines plugin metadata, guards direct access, loads the autoloader, registers activation hooks, and boots the plugin service container or coordinator class.

`class-loader.php` follows the small autoloader pattern already used in the local `lightweight-consent` reference plugin.

### Installer

`class-installer.php` owns schema creation through `dbDelta()`. Activation creates or updates the table schema and can store a lightweight schema version option for future migrations.

### Data Store

`class-data-store.php` is the only component allowed to talk directly to `$wpdb` for SEO row access. It exposes methods like:

- `get_post_seo_data( int $post_id ): array`
- `update_field( int $post_id, string $field, mixed $value ): bool`
- `prime_defaults( int $post_id ): array`
- `delete_cache( int $post_id ): void`

This keeps SQL, caching, normalization, and future extensions in one place.

### Meta Hijack

`class-meta-hijack.php` registers the editor-visible meta keys via `register_post_meta()` and intercepts `get_post_metadata` plus `update_post_metadata`.

Behavior:

- Only Lean SEO keys are intercepted.
- Reads pull from `DataStore`.
- Writes sanitize and cast values, update the mapped table column, clear cache, and return `true` to prevent `wp_postmeta` writes.
- Unsupported keys fall through to core behavior untouched.

### Admin Assets and Sidebar

`class-admin-assets.php` enqueues the sidebar bundle only inside the block editor. The bundle uses `@wordpress/plugins`, `@wordpress/edit-post`, `@wordpress/components`, `@wordpress/core-data`, and `@wordpress/data`.

The UI stays intentionally small:

- SEO title
- Meta description
- `noindex`
- `nofollow`
- Schema selector

No scoring system, no suggestions, no extra styling framework.

### Frontend Output

`class-frontend.php` reads normalized SEO data from `DataStore` and:

- filters document title parts
- filters `wp_robots`
- prints escaped meta description
- prints JSON-LD when `schema_type` is not `none`

The cleanup hooks that remove unneeded head output should be conservative in V1. Only clearly harmless removals should be included so the plugin does not become a broad site-hardening plugin by accident.

## Data Flow

1. Editor loads sidebar.
2. Sidebar reads/writes registered meta keys using core editor APIs.
3. WordPress invokes meta filters.
4. Lean SEO intercepts only its own keys.
5. `DataStore` reads or writes the single row in `wp_leanseo_data`.
6. Cache is invalidated after writes.
7. Frontend resolves SEO data through the same `DataStore`.

This gives one storage backend, one cache strategy, and one place to evolve the schema.

## Caching Strategy

Per-post SEO data is cached in the object cache group `lean_seo` with keys like `lean_seo_post_{$post_id}`.

Rules:

- Read-through cache on first lookup
- Delete cache after any successful write
- Treat missing rows as normalized empty defaults to avoid repeated misses

This works with the built-in object cache and improves automatically when a persistent cache is present.

## Validation and Sanitization

- `meta_title`: `sanitize_text_field()`
- `meta_desc`: `sanitize_textarea_field()`
- `robots_noindex`: strict boolean to `0` or `1`
- `robots_nofollow`: strict boolean to `0` or `1`
- `schema_type`: whitelist of supported values, fallback to `none`

Output always escapes at render time with `esc_attr()` and `wp_json_encode()`.

## Error Handling

V1 should fail quietly and predictably:

- Missing table or failed query returns normalized empty data instead of fatal errors
- Frontend emits nothing when no usable value is available
- Admin notices, if any, should be limited to privileged users and only for actionable installation problems

The plugin should not assume every request has a valid global post object. Frontend methods must guard for archives, 404s, feeds, and admin requests.

## Testing Strategy

Priority is on PHP behavior:

- `DataStoreTest`: defaults, reads, writes, cache invalidation, normalization
- `MetaHijackTest`: key interception, type handling, prevention of `wp_postmeta` writes
- `FrontendTest`: title override, robots flags, description output, schema output

JavaScript testing can stay minimal in V1 because the sidebar is a thin wrapper around WordPress components. Manual block-editor verification remains part of acceptance testing.

## Extensibility for Later Phases

Phase 3 features should build on these seams:

- `DataStore` remains the source for future REST routes
- schema generation should live behind a method boundary so more schema types can be added later
- plugin bootstrap can register future services for rewrite endpoints without refactoring the editor storage path

This keeps V1 lean while avoiding a redesign when AI-oriented features land later.
