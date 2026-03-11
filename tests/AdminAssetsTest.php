<?php

use PHPUnit\Framework\TestCase;

final class AdminAssetsTest extends TestCase {
	protected function setUp(): void {
		lean_seo_test_reset_state();
		lean_seo_test_reset_wpdb();
	}

	public function test_enqueue_sidebar_assets_bails_when_asset_file_is_missing(): void {
		$asset_file   = dirname( __DIR__ ) . '/build/index.asset.php';
		$backup_file  = dirname( __DIR__ ) . '/build/index.asset.php.bak';
		$assets_class = new \LeanSEO\Admin_Assets();

		rename( $asset_file, $backup_file );

		try {
			$assets_class->enqueue_sidebar_assets();
			$this->assertSame( [], $GLOBALS['lean_seo_test_state']['enqueued_scripts'] );
		} finally {
			rename( $backup_file, $asset_file );
		}
	}
}
