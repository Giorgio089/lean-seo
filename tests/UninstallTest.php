<?php

use PHPUnit\Framework\TestCase;

final class UninstallTest extends TestCase {
	protected function setUp(): void {
		lean_seo_test_reset_state();
		lean_seo_test_reset_wpdb();
	}

	public function test_uninstall_drops_table_and_deletes_plugin_options(): void {
		global $wpdb;

		$GLOBALS['lean_seo_test_state']['options']['lean_seo_schema_version'] = '1';
		$uninstall_file = dirname( __DIR__ ) . '/uninstall.php';

		$this->assertFileExists( $uninstall_file );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		require $uninstall_file;

		$this->assertContains( 'wp_leanseo_data', $wpdb->dropped_tables );
		$this->assertNull( get_option( 'lean_seo_schema_version', null ) );
	}
}
