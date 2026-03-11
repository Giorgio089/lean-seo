<?php

use PHPUnit\Framework\TestCase;

final class FrontendTest extends TestCase {
	protected function setUp(): void {
		lean_seo_test_reset_state();
		lean_seo_test_reset_wpdb();
	}

	public function test_description_markup_uses_custom_meta_description(): void {
		$frontend = new \LeanSEO\Frontend( new \LeanSEO\Data_Store() );
		$html     = $frontend->build_description_tag(
			[
				'meta_desc' => 'Lean description',
			]
		);

		$this->assertStringContainsString( 'Lean description', $html );
	}

	public function test_filter_title_uses_current_post_seo_title(): void {
		global $wpdb;

		$GLOBALS['lean_seo_test_state']['queried_object_id'] = 15;
		$wpdb->rows[15] = [
			'post_id'    => 15,
			'meta_title' => 'SEO override',
		];

		$frontend = new \LeanSEO\Frontend( new \LeanSEO\Data_Store() );

		$this->assertSame(
			'SEO override',
			$frontend->filter_title( [ 'title' => 'Original title' ] )['title']
		);
	}

	public function test_filter_robots_applies_current_post_flags(): void {
		global $wpdb;

		$GLOBALS['lean_seo_test_state']['queried_object_id'] = 16;
		$wpdb->rows[16] = [
			'post_id'         => 16,
			'robots_noindex'  => 1,
			'robots_nofollow' => 1,
		];

		$frontend = new \LeanSEO\Frontend( new \LeanSEO\Data_Store() );
		$robots   = $frontend->filter_robots( [] );

		$this->assertTrue( $robots['noindex'] );
		$this->assertTrue( $robots['nofollow'] );
	}
}
