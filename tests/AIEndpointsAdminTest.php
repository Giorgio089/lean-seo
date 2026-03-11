<?php

use PHPUnit\Framework\TestCase;

final class AIEndpointsAdminTest extends TestCase {
	protected function setUp(): void {
		lean_seo_test_reset_state();
		lean_seo_test_reset_wpdb();
	}

	public function test_registers_settings_page_under_options_menu(): void {
		$admin = new \LeanSEO\AI_Endpoints_Admin( new \LeanSEO\AI_Endpoints_Settings() );
		$admin->register_menu();

		$this->assertSame( 'options-general.php', $GLOBALS['lean_seo_test_state']['menus'][0]['parent_slug'] );
	}

	public function test_registers_plugin_option_for_ai_endpoints(): void {
		$admin = new \LeanSEO\AI_Endpoints_Admin( new \LeanSEO\AI_Endpoints_Settings() );
		$admin->register_settings();

		$this->assertSame( 'lean_seo_ai_endpoints', $GLOBALS['lean_seo_test_state']['registered_settings'][0]['option_name'] );
	}
}
