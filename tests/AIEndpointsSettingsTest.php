<?php

use PHPUnit\Framework\TestCase;

final class AIEndpointsSettingsTest extends TestCase {
	protected function setUp(): void {
		lean_seo_test_reset_state();
		lean_seo_test_reset_wpdb();
	}

	public function test_defaults_include_both_endpoints_enabled(): void {
		$settings = new \LeanSEO\AI_Endpoints_Settings();

		$this->assertSame(
			[
				'enabled_llms_txt'    => 1,
				'enabled_agents_json' => 1,
				'llms_summary'        => '',
				'llms_contact_url'    => '',
				'llms_policy_url'     => '',
				'agents_name'         => '',
				'agents_description'  => '',
				'agents_url'          => '',
			],
			$settings->get()
		);
	}
}
