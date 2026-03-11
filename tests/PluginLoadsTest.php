<?php

use PHPUnit\Framework\TestCase;

final class PluginLoadsTest extends TestCase {
	protected function setUp(): void {
		lean_seo_test_reset_state();
		lean_seo_test_reset_wpdb();
	}

	public function test_plugin_class_exists(): void {
		$this->assertTrue( class_exists( \LeanSEO\Plugin::class ) );
	}

	public function test_admin_assets_class_exists(): void {
		$this->assertTrue( class_exists( \LeanSEO\Admin_Assets::class ) );
	}

	public function test_plugin_boot_registers_core_services(): void {
		$plugin = \LeanSEO\Plugin::instance();
		$plugin->boot();

		$this->assertTrue( $plugin->has_service( 'data_store' ) );
		$this->assertTrue( $plugin->has_service( 'ai_endpoints_admin' ) );
		$this->assertTrue( $plugin->has_service( 'ai_endpoints_renderer' ) );
		$this->assertTrue( $plugin->has_service( 'ai_endpoints_routes' ) );
		$this->assertTrue( $plugin->has_service( 'ai_endpoints_settings' ) );
		$this->assertTrue( $plugin->has_service( 'meta_hijack' ) );
		$this->assertTrue( $plugin->has_service( 'frontend' ) );
		$this->assertTrue( $plugin->has_service( 'admin_assets' ) );
	}
}
