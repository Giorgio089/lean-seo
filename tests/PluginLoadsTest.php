<?php

use PHPUnit\Framework\TestCase;

final class PluginLoadsTest extends TestCase {
	public function test_plugin_class_exists(): void {
		$this->assertTrue( class_exists( \LeanSEO\Plugin::class ) );
	}

	public function test_admin_assets_class_exists(): void {
		$this->assertTrue( class_exists( \LeanSEO\Admin_Assets::class ) );
	}
}
