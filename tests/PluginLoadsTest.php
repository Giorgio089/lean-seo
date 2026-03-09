<?php

use PHPUnit\Framework\TestCase;

final class PluginLoadsTest extends TestCase {
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
		$this->assertTrue( $plugin->has_service( 'meta_hijack' ) );
		$this->assertTrue( $plugin->has_service( 'frontend' ) );
		$this->assertTrue( $plugin->has_service( 'admin_assets' ) );
	}
}
