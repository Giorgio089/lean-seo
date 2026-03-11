<?php

use PHPUnit\Framework\TestCase;

final class InstallerTest extends TestCase {
	protected function setUp(): void {
		lean_seo_test_reset_state();
		lean_seo_test_reset_wpdb();
	}

	public function test_activate_passes_expected_schema_to_dbdelta(): void {
		\LeanSEO\Installer::activate();

		$this->assertCount( 1, $GLOBALS['lean_seo_test_state']['dbdelta_queries'] );
		$this->assertStringContainsString(
			'CREATE TABLE wp_leanseo_data',
			$GLOBALS['lean_seo_test_state']['dbdelta_queries'][0]
		);
		$this->assertStringContainsString(
			'PRIMARY KEY  (post_id)',
			$GLOBALS['lean_seo_test_state']['dbdelta_queries'][0]
		);
	}
}
