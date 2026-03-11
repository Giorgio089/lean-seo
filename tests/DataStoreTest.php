<?php

use PHPUnit\Framework\TestCase;

final class DataStoreTest extends TestCase {
	protected function setUp(): void {
		lean_seo_test_reset_state();
		lean_seo_test_reset_wpdb();
	}

	public function test_normalizes_missing_rows_to_default_shape(): void {
		$store = new \LeanSEO\Data_Store();

		$this->assertSame(
			[
				'post_id'         => 99,
				'meta_title'      => '',
				'meta_desc'       => '',
				'robots_noindex'  => 0,
				'robots_nofollow' => 0,
				'schema_type'     => 'none',
				'updated_at'      => null,
			],
			$store->normalize_row( [ 'post_id' => 99 ] )
		);
	}

	public function test_reads_from_database_once_then_serves_from_cache(): void {
		global $wpdb;

		$wpdb->rows[42] = [
			'post_id'         => 42,
			'meta_title'      => 'Lean title',
			'meta_desc'       => 'Lean desc',
			'robots_noindex'  => 0,
			'robots_nofollow' => 1,
			'schema_type'     => 'article',
			'updated_at'      => '2026-03-11 09:00:00',
		];

		$store = new \LeanSEO\Data_Store();

		$first  = $store->get_post_seo_data( 42 );
		$second = $store->get_post_seo_data( 42 );

		$this->assertSame( 'Lean title', $first['meta_title'] );
		$this->assertSame( $first, $second );
		$this->assertCount( 1, $wpdb->get_row_queries );
	}

	public function test_update_field_rejects_unknown_columns(): void {
		global $wpdb;

		$store = new \LeanSEO\Data_Store();

		$this->assertFalse( $store->update_field( 42, 'not_a_real_column', 'value' ) );
		$this->assertCount( 0, $wpdb->query_log );
	}
}
