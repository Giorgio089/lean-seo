<?php

use PHPUnit\Framework\TestCase;

final class MetaHijackTest extends TestCase {
	public function test_maps_editor_meta_key_to_custom_table_column(): void {
		$hijack = new \LeanSEO\Meta_Hijack( new \LeanSEO\Data_Store() );

		$this->assertSame( 'meta_title', $hijack->column_for_meta_key( '_lean_seo_title' ) );
	}
}
