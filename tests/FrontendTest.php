<?php

use PHPUnit\Framework\TestCase;

final class FrontendTest extends TestCase {
	public function test_description_markup_uses_custom_meta_description(): void {
		$frontend = new \LeanSEO\Frontend( new \LeanSEO\Data_Store() );
		$html     = $frontend->build_description_tag(
			[
				'meta_desc' => 'Lean description',
			]
		);

		$this->assertStringContainsString( 'Lean description', $html );
	}
}
