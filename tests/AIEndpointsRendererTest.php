<?php

use PHPUnit\Framework\TestCase;

final class AIEndpointsRendererTest extends TestCase {
	protected function setUp(): void {
		lean_seo_test_reset_state();
		lean_seo_test_reset_wpdb();
	}

	public function test_renders_llms_txt_from_site_data_and_settings(): void {
		$renderer = new \LeanSEO\AI_Endpoints_Renderer( new \LeanSEO\AI_Endpoints_Settings() );

		$output = $renderer->render_llms_txt(
			[
				'llms_summary' => 'Lean site summary',
			]
		);

		$this->assertStringContainsString( 'Lean site summary', $output );
	}

	public function test_renders_agents_json_with_site_defaults(): void {
		$renderer = new \LeanSEO\AI_Endpoints_Renderer( new \LeanSEO\AI_Endpoints_Settings() );
		$output   = $renderer->render_agents_json( [] );
		$decoded  = json_decode( $output, true );

		$this->assertSame( 'Lean SEO Test Site', $decoded['name'] );
		$this->assertSame( 'https://example.com/', $decoded['url'] );
	}
}
