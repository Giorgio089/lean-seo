<?php

use PHPUnit\Framework\TestCase;

final class DataStoreTest extends TestCase {
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
}
