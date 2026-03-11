<?php

use PHPUnit\Framework\TestCase;

final class MetaHijackTest extends TestCase {
	protected function setUp(): void {
		lean_seo_test_reset_state();
		lean_seo_test_reset_wpdb();
	}

	public function test_maps_editor_meta_key_to_custom_table_column(): void {
		$hijack = new \LeanSEO\Meta_Hijack( new \LeanSEO\Data_Store() );

		$this->assertSame( 'meta_title', $hijack->column_for_meta_key( '_lean_seo_title' ) );
	}

	public function test_hijack_get_meta_returns_scalar_value_for_single_requests(): void {
		global $wpdb;

		$wpdb->rows[88] = [
			'post_id'    => 88,
			'meta_title' => 'Stored title',
		];

		$hijack = new \LeanSEO\Meta_Hijack( new \LeanSEO\Data_Store() );

		$this->assertSame( 'Stored title', $hijack->hijack_get_meta( null, 88, '_lean_seo_title', true ) );
	}

	public function test_hijack_update_meta_sanitizes_invalid_schema_to_none(): void {
		global $wpdb;

		$hijack = new \LeanSEO\Meta_Hijack( new \LeanSEO\Data_Store() );

		$this->assertTrue( $hijack->hijack_update_meta( null, 77, '_lean_seo_schema', 'totally-invalid', null ) );
		$this->assertSame( 'none', $wpdb->rows[77]['schema_type'] ?? null );
	}
}
