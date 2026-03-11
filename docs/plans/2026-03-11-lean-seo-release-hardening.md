# Lean SEO Release Hardening Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Turn the current Lean SEO V1 codebase into a release-ready foundation by hardening tests, operational safety, and repository hygiene before starting AI/GEO features.

**Architecture:** Keep the current custom-table plus Gutenberg-meta compatibility design intact. The next work should increase confidence around that architecture instead of expanding scope: verify data flow more deeply, add failure-path handling, and establish CI and release basics so later API work lands on stable ground.

**Tech Stack:** PHP 8.1+, WordPress 6.4+, PHPUnit, Node.js, `@wordpress/scripts`, GitHub Actions

---

### Task 1: Commit and tighten project documentation

**Files:**
- Modify: `README.md`
- Modify: `docs/plans/2026-03-11-lean-seo-release-hardening.md`

**Step 1: Review the untracked README against the implemented plugin**

Run: `git status --short --branch`
Expected: `README.md` is still untracked and needs an explicit decision.

**Step 2: Align README claims with current implementation**

Make sure the README clearly separates:
- implemented V1 behavior
- known gaps in test depth
- deferred AI/GEO scope

**Step 3: Add the README to version control**

Run: `git add README.md docs/plans/2026-03-11-lean-seo-release-hardening.md`
Expected: both planning artifacts are staged.

**Step 4: Commit the documentation baseline**

Run: `git commit -m "docs: add lean seo project documentation"`
Expected: the repository gains a committed project overview and next-step plan.

### Task 2: Deepen PHP test coverage around real plugin behavior

**Files:**
- Modify: `tests/bootstrap.php`
- Modify: `tests/DataStoreTest.php`
- Modify: `tests/MetaHijackTest.php`
- Modify: `tests/FrontendTest.php`
- Modify: `tests/PluginLoadsTest.php`
- Add: `tests/InstallerTest.php`
- Add: `tests/AdminAssetsTest.php`

**Step 1: Write failing tests for the missing behaviors**

Add tests for:
- `Data_Store::get_post_seo_data()` cache hits and cache invalidation after `update_field()`
- `Meta_Hijack::hijack_get_meta()` returning Lean SEO values for registered keys
- `Meta_Hijack::hijack_update_meta()` sanitizing booleans, textarea, and schema fallback
- `Frontend::filter_title()` and `Frontend::filter_robots()` using queried post data
- `Installer::activate()` creating the expected schema string
- `Admin_Assets::enqueue_sidebar_assets()` bailing safely when build assets are missing

**Step 2: Expand the bootstrap doubles instead of only class-existence stubs**

Add minimal test doubles for:
- `get_queried_object_id()`
- `wp_enqueue_script()`
- `plugins_url()`
- `dbDelta()`
- a controllable `$wpdb` test object with `prepare()`, `get_row()`, `query()`, `get_charset_collate()`

**Step 3: Run the PHP test suite and confirm the new failures**

Run: `phpunit --configuration phpunit.xml.dist`
Expected: failures prove the new tests exercise behavior the current suite does not cover.

**Step 4: Implement only the minimal production changes required by the new tests**

Likely production files:
- `includes/class-data-store.php`
- `includes/class-meta-hijack.php`
- `includes/class-frontend.php`
- `includes/class-installer.php`
- `includes/class-admin-assets.php`

Focus on closing behavioral gaps, not adding new features.

**Step 5: Re-run the PHP test suite**

Run: `phpunit --configuration phpunit.xml.dist`
Expected: all tests pass with materially stronger coverage than the current `6` tests / `9` assertions baseline.

**Step 6: Commit the test-hardening pass**

Run: `git add tests/bootstrap.php tests/DataStoreTest.php tests/MetaHijackTest.php tests/FrontendTest.php tests/PluginLoadsTest.php tests/InstallerTest.php tests/AdminAssetsTest.php includes/class-data-store.php includes/class-meta-hijack.php includes/class-frontend.php includes/class-installer.php includes/class-admin-assets.php`

Run: `git commit -m "test: harden lean seo plugin behavior coverage"`
Expected: tests capture core storage, rendering, and bootstrap behavior.

### Task 3: Add install safety, schema versioning, and uninstall cleanup

**Files:**
- Modify: `lean-seo.php`
- Modify: `includes/class-installer.php`
- Modify: `includes/class-plugin.php`
- Add: `uninstall.php`
- Add: `tests/UninstallTest.php`

**Step 1: Write a failing test for schema version persistence and uninstall cleanup**

Cover:
- activation stores a schema version option
- uninstall removes the custom table and plugin-owned options only when executed through WordPress uninstall flow

**Step 2: Add a lightweight schema version constant and option write**

Implementation target:
- `lean-seo.php` or `includes/class-installer.php` defines a schema version
- activation persists something like `lean_seo_schema_version`

**Step 3: Add an uninstall entrypoint**

`uninstall.php` should:
- guard with `defined( 'WP_UNINSTALL_PLUGIN' )`
- drop `{$wpdb->prefix}leanseo_data`
- delete Lean SEO options

**Step 4: Verify the tests**

Run: `phpunit --configuration phpunit.xml.dist --filter "InstallerTest|UninstallTest"`
Expected: pass with no unrelated regressions.

**Step 5: Commit the lifecycle hardening**

Run: `git add lean-seo.php includes/class-installer.php includes/class-plugin.php uninstall.php tests/InstallerTest.php tests/UninstallTest.php`

Run: `git commit -m "feat: harden plugin lifecycle and cleanup"`
Expected: installation and removal paths stop being implicit assumptions.

### Task 4: Establish continuous verification for PHP and JS

**Files:**
- Add: `.github/workflows/ci.yml`
- Modify: `README.md`

**Step 1: Add a failing or missing CI check**

Confirm the repo currently has no GitHub Actions workflow.

Run: `test -d .github && echo present || echo missing`
Expected: `missing`

**Step 2: Add a CI workflow for build and test**

Workflow should:
- run on push and pull request
- install Node dependencies with `npm ci`
- run `npm run build`
- run `phpunit --configuration phpunit.xml.dist`

**Step 3: Document local verification commands**

Ensure README lists:
- `npm ci`
- `npm run build`
- `phpunit --configuration phpunit.xml.dist`

**Step 4: Verify locally**

Run: `npm run build`
Expected: webpack build succeeds.

Run: `phpunit --configuration phpunit.xml.dist`
Expected: test suite passes after workflow-related changes.

**Step 5: Commit CI**

Run: `git add .github/workflows/ci.yml README.md`

Run: `git commit -m "ci: add lean seo verification workflow"`
Expected: repository gains repeatable verification outside the local machine.

### Task 5: Prepare the next feature phase instead of starting it ad hoc

**Files:**
- Add: `docs/plans/2026-03-11-lean-seo-ai-phase-design.md`

**Step 1: Write a short design note for the next product phase**

The design note should answer:
- whether `llms.txt` and `agents.json` are virtual endpoints or generated files
- whether the future REST API is read-only first or full read/write
- which permissions model applies for automation use cases

**Step 2: Keep the scope narrow**

Do not implement Phase 3 in the same branch as release hardening. The output here is design only.

**Step 3: Commit the deferred-scope design**

Run: `git add docs/plans/2026-03-11-lean-seo-ai-phase-design.md`

Run: `git commit -m "docs: outline lean seo ai phase"`
Expected: the next phase is prepared without destabilizing V1 hardening.
