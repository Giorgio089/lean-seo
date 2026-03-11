<?php

use PHPUnit\Framework\TestCase;

final class AIEndpointsRoutesTest extends TestCase {
	protected function setUp(): void {
		lean_seo_test_reset_state();
		lean_seo_test_reset_wpdb();
	}

	public function test_disabled_llms_endpoint_returns_404(): void {
		$settings = new \LeanSEO\AI_Endpoints_Settings();
		$renderer = new \LeanSEO\AI_Endpoints_Renderer( $settings );
		$routes   = new \LeanSEO\AI_Endpoints_Routes( $settings, $renderer );

		$response = $routes->build_endpoint_response(
			'llms.txt',
			[
				'enabled_llms_txt' => 0,
			]
		);

		$this->assertSame( 404, $response['status'] );
	}

	public function test_agents_endpoint_returns_json_content_type(): void {
		$settings = new \LeanSEO\AI_Endpoints_Settings();
		$renderer = new \LeanSEO\AI_Endpoints_Renderer( $settings );
		$routes   = new \LeanSEO\AI_Endpoints_Routes( $settings, $renderer );

		$response = $routes->build_endpoint_response( 'agents.json' );

		$this->assertSame( 'application/json; charset=utf-8', $response['content_type'] );
	}

	public function test_maybe_handle_request_returns_llms_response_for_query_var(): void {
		$settings = new \LeanSEO\AI_Endpoints_Settings();
		$renderer = new \LeanSEO\AI_Endpoints_Renderer( $settings );
		$routes   = new \LeanSEO\AI_Endpoints_Routes( $settings, $renderer );

		$GLOBALS['lean_seo_test_state']['query_vars']['lean_seo_ai_endpoint'] = 'llms.txt';

		$response = $routes->maybe_handle_request();

		$this->assertSame( 200, $response['status'] );
		$this->assertSame( 'text/plain; charset=utf-8', $response['content_type'] );
	}
}
