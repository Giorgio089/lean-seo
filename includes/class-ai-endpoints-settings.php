<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class AI_Endpoints_Settings {
	private const OPTION_KEY = 'lean_seo_ai_endpoints';

	public function get(): array {
		$value = function_exists( 'get_option' ) ? get_option( self::OPTION_KEY, [] ) : [];

		return $this->sanitize( is_array( $value ) ? $value : [] );
	}

	public function sanitize( array $input ): array {
		$input = array_merge(
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
			$input
		);

		return [
			'enabled_llms_txt'    => ! empty( $input['enabled_llms_txt'] ) ? 1 : 0,
			'enabled_agents_json' => ! empty( $input['enabled_agents_json'] ) ? 1 : 0,
			'llms_summary'        => $this->sanitize_textarea( $input['llms_summary'] ?? '' ),
			'llms_contact_url'    => $this->sanitize_url( $input['llms_contact_url'] ?? '' ),
			'llms_policy_url'     => $this->sanitize_url( $input['llms_policy_url'] ?? '' ),
			'agents_name'         => $this->sanitize_text( $input['agents_name'] ?? '' ),
			'agents_description'  => $this->sanitize_textarea( $input['agents_description'] ?? '' ),
			'agents_url'          => $this->sanitize_url( $input['agents_url'] ?? '' ),
		];
	}

	public function option_key(): string {
		return self::OPTION_KEY;
	}

	private function sanitize_text( $value ): string {
		return function_exists( 'sanitize_text_field' )
			? sanitize_text_field( $value )
			: trim( strip_tags( (string) $value ) );
	}

	private function sanitize_textarea( $value ): string {
		return function_exists( 'sanitize_textarea_field' )
			? sanitize_textarea_field( $value )
			: trim( strip_tags( (string) $value ) );
	}

	private function sanitize_url( $value ): string {
		$sanitized = function_exists( 'esc_url_raw' )
			? esc_url_raw( (string) $value )
			: filter_var( (string) $value, FILTER_VALIDATE_URL );

		return is_string( $sanitized ) ? $sanitized : '';
	}
}
