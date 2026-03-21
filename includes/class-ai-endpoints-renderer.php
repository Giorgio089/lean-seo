<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class AI_Endpoints_Renderer {
	public function __construct( private AI_Endpoints_Settings $settings ) {}

	public function render_llms_txt( array $settings ): string {
		$settings = $this->settings->sanitize( $settings );
		$lines    = [
			'Site: ' . $this->site_name(),
			'URL: ' . $this->site_url(),
			'Description: ' . $this->site_description( $settings ),
		];

		if ( '' !== $settings['llms_contact_url'] ) {
			$lines[] = 'Contact: ' . $settings['llms_contact_url'];
		}

		if ( '' !== $settings['llms_policy_url'] ) {
			$lines[] = 'Policy: ' . $settings['llms_policy_url'];
		}

		return implode( "\n", $lines ) . "\n";
	}

	public function render_agents_json( array $settings ): string {
		$settings = $this->settings->sanitize( $settings );
		$payload  = [
			'name'        => '' !== $settings['agents_name'] ? $settings['agents_name'] : $this->site_name(),
			'description' => '' !== $settings['agents_description'] ? $settings['agents_description'] : $this->site_description( $settings ),
			'url'         => '' !== $settings['agents_url'] ? $settings['agents_url'] : $this->site_url(),
		];

		return (string) wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private function site_name(): string {
		return function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'name' ) : '';
	}

	private function site_description( array $settings ): string {
		if ( '' !== $settings['llms_summary'] ) {
			return $settings['llms_summary'];
		}

		return function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'description' ) : '';
	}

	private function site_url(): string {
		return function_exists( 'home_url' ) ? (string) home_url( '/' ) : '/';
	}
}
