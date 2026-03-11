# Lean SEO AI Endpoints Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add public `llms.txt` and `agents.json` site endpoints backed by Lean SEO plugin settings and WordPress site metadata.

**Architecture:** Introduce one site-settings service for endpoint configuration, one admin settings page for editing those values, and one endpoint service for rewrites, request handling, and rendering. Keep the existing post SEO data flow unchanged so the new AI-facing discovery layer is isolated from the custom-table storage path.

**Tech Stack:** PHP 8.1+, WordPress 6.4+, PHPUnit, GitHub Actions, WordPress Settings API

---

**Implementation Status:** Implemented on `codex/ai-endpoints`

### Task 1: Add a normalized settings service for AI endpoints

**Files:**
- Create: `includes/class-ai-endpoints-settings.php`
- Modify: `includes/class-plugin.php`
- Modify: `tests/bootstrap.php`
- Create: `tests/AIEndpointsSettingsTest.php`
- Modify: `tests/PluginLoadsTest.php`

**Step 1: Write the failing test**

```php
<?php

use PHPUnit\Framework\TestCase;

final class AIEndpointsSettingsTest extends TestCase {
	public function test_defaults_include_both_endpoints_enabled(): void {
		$settings = new \LeanSEO\AI_Endpoints_Settings();

		$this->assertSame(
			[
				'enabled_llms_txt'    => 1,
				'enabled_agents_json' => 1,
				'llms_summary'        => '',
				'llms_contact_url'    => '',
				'llms_policy_url'     => '',
				'agents_name'         => '',
				'agents_description'  => '',
				'agents_url'          => '',
			],
			$settings->get()
		);
	}
}
```

**Step 2: Run test to verify it fails**

Run: `phpunit --configuration phpunit.xml.dist --filter AIEndpointsSettingsTest`
Expected: FAIL with `Class "LeanSEO\AI_Endpoints_Settings" not found`

**Step 3: Write minimal implementation**

Create a settings service that:

- stores data under `lean_seo_ai_endpoints`
- exposes `get(): array`
- exposes `sanitize( array $input ): array`
- returns normalized defaults
- sanitizes booleans, text fields, and URLs

Wire the service into `Plugin::boot()` as `ai_endpoints_settings`.

**Step 4: Run test to verify it passes**

Run: `phpunit --configuration phpunit.xml.dist --filter AIEndpointsSettingsTest`
Expected: PASS

**Step 5: Commit**

```bash
git add includes/class-ai-endpoints-settings.php includes/class-plugin.php tests/bootstrap.php tests/AIEndpointsSettingsTest.php tests/PluginLoadsTest.php
git commit -m "feat: add ai endpoint settings service"
```

### Task 2: Add a minimal WordPress settings page for endpoint configuration

**Files:**
- Create: `includes/class-ai-endpoints-admin.php`
- Modify: `includes/class-plugin.php`
- Modify: `tests/bootstrap.php`
- Create: `tests/AIEndpointsAdminTest.php`

**Step 1: Write the failing test**

```php
<?php

use PHPUnit\Framework\TestCase;

final class AIEndpointsAdminTest extends TestCase {
	public function test_registers_settings_page_under_options_menu(): void {
		$admin = new \LeanSEO\AI_Endpoints_Admin( new \LeanSEO\AI_Endpoints_Settings() );
		$admin->register_menu();

		$this->assertSame( 'options-general.php', $GLOBALS['lean_seo_test_state']['menus'][0]['parent_slug'] );
	}
}
```

**Step 2: Run test to verify it fails**

Run: `phpunit --configuration phpunit.xml.dist --filter AIEndpointsAdminTest`
Expected: FAIL because the class or menu registration behavior does not exist yet

**Step 3: Write minimal implementation**

Create a small admin service that:

- adds a page under Settings
- registers the `lean_seo_ai_endpoints` option
- delegates sanitization to `AI_Endpoints_Settings`
- renders a simple form with the agreed fields

Wire the service into `Plugin::boot()` as `ai_endpoints_admin`.

**Step 4: Run test to verify it passes**

Run: `phpunit --configuration phpunit.xml.dist --filter AIEndpointsAdminTest`
Expected: PASS

**Step 5: Commit**

```bash
git add includes/class-ai-endpoints-admin.php includes/class-plugin.php tests/bootstrap.php tests/AIEndpointsAdminTest.php
git commit -m "feat: add ai endpoint settings page"
```

### Task 3: Add deterministic renderers for `llms.txt` and `agents.json`

**Files:**
- Create: `includes/class-ai-endpoints-renderer.php`
- Modify: `tests/bootstrap.php`
- Create: `tests/AIEndpointsRendererTest.php`

**Step 1: Write the failing test**

```php
<?php

use PHPUnit\Framework\TestCase;

final class AIEndpointsRendererTest extends TestCase {
	public function test_renders_llms_txt_from_site_data_and_settings(): void {
		$renderer = new \LeanSEO\AI_Endpoints_Renderer( new \LeanSEO\AI_Endpoints_Settings() );

		$output = $renderer->render_llms_txt(
			[
				'llms_summary' => 'Lean site summary',
			]
		);

		$this->assertStringContainsString( 'Lean site summary', $output );
	}
}
```

**Step 2: Run test to verify it fails**

Run: `phpunit --configuration phpunit.xml.dist --filter AIEndpointsRendererTest`
Expected: FAIL because the renderer class does not exist yet

**Step 3: Write minimal implementation**

Create a renderer that:

- builds `llms.txt` from site name, description, URL, and plugin settings
- builds `agents.json` from configured values plus site defaults
- omits empty optional values
- returns a stable JSON structure

**Step 4: Run test to verify it passes**

Run: `phpunit --configuration phpunit.xml.dist --filter AIEndpointsRendererTest`
Expected: PASS

**Step 5: Commit**

```bash
git add includes/class-ai-endpoints-renderer.php tests/bootstrap.php tests/AIEndpointsRendererTest.php
git commit -m "feat: add ai endpoint renderers"
```

### Task 4: Add public request handling for `/llms.txt` and `/agents.json`

**Files:**
- Create: `includes/class-ai-endpoints-routes.php`
- Modify: `includes/class-plugin.php`
- Modify: `includes/class-installer.php`
- Modify: `lean-seo.php`
- Modify: `tests/bootstrap.php`
- Create: `tests/AIEndpointsRoutesTest.php`
- Modify: `tests/InstallerTest.php`

**Step 1: Write the failing test**

```php
<?php

use PHPUnit\Framework\TestCase;

final class AIEndpointsRoutesTest extends TestCase {
	public function test_disabled_llms_endpoint_returns_404(): void {
		$settings = new \LeanSEO\AI_Endpoints_Settings();
		$renderer = new \LeanSEO\AI_Endpoints_Renderer( $settings );
		$routes   = new \LeanSEO\AI_Endpoints_Routes( $settings, $renderer );

		$response = $routes->build_endpoint_response(
			'llms.txt',
			[
				'enabled_llms_txt' => 0,
			]
		);

		$this->assertSame( 404, $response['status'] );
	}
}
```

**Step 2: Run test to verify it fails**

Run: `phpunit --configuration phpunit.xml.dist --filter AIEndpointsRoutesTest`
Expected: FAIL because route handling does not exist yet

**Step 3: Write minimal implementation**

Create a route service that:

- registers rewrite rules for `llms.txt` and `agents.json`
- registers query vars for endpoint detection
- builds endpoint responses with status, content type, and body
- emits text or JSON before theme rendering
- returns `404` for disabled endpoints

Also update activation so rewrite rules are flushed after registration.

**Step 4: Run test to verify it passes**

Run: `phpunit --configuration phpunit.xml.dist --filter AIEndpointsRoutesTest`
Expected: PASS

**Step 5: Commit**

```bash
git add includes/class-ai-endpoints-routes.php includes/class-plugin.php includes/class-installer.php lean-seo.php tests/bootstrap.php tests/AIEndpointsRoutesTest.php tests/InstallerTest.php
git commit -m "feat: add public ai endpoint routes"
```

### Task 5: Document and verify the new endpoint behavior end-to-end

**Files:**
- Modify: `README.md`
- Modify: `docs/plans/2026-03-11-lean-seo-ai-endpoints-design.md`
- Modify: `docs/plans/2026-03-11-lean-seo-ai-endpoints.md`

**Step 1: Write the failing documentation/test expectation**

Confirm the repository docs do not yet mention:

- the new settings page
- `llms.txt`
- `agents.json`

**Step 2: Update documentation**

Add concise docs covering:

- where the settings live
- what each endpoint returns
- how to verify them locally

**Step 3: Run the full verification suite**

Run: `phpunit --configuration phpunit.xml.dist`
Expected: PASS with all old and new tests green

Run: `npm run build`
Expected: PASS with no frontend regressions

**Step 4: Commit**

```bash
git add README.md docs/plans/2026-03-11-lean-seo-ai-endpoints-design.md docs/plans/2026-03-11-lean-seo-ai-endpoints.md
git commit -m "docs: document ai endpoint feature"
```
