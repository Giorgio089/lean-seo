<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class AI_Endpoints_Routes {
	public function __construct(
		private AI_Endpoints_Settings $settings,
		private AI_Endpoints_Renderer $renderer
	) {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'init', [ $this, 'register_rewrite_rules' ] );
			add_action( 'template_redirect', [ $this, 'template_redirect' ] );
		}

		if ( function_exists( 'add_filter' ) ) {
			add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
		}
	}

	public static function register_rewrite_rules(): void {
		if ( ! function_exists( 'add_rewrite_rule' ) ) {
			return;
		}

		add_rewrite_rule( '^llms\.txt$', 'index.php?lean_seo_ai_endpoint=llms.txt', 'top' );
		add_rewrite_rule( '^agents\.json$', 'index.php?lean_seo_ai_endpoint=agents.json', 'top' );
	}

	public function register_query_vars( array $vars ): array {
		$vars[] = 'lean_seo_ai_endpoint';

		return array_values( array_unique( $vars ) );
	}

	public function build_endpoint_response( string $endpoint, ?array $settings = null ): array {
		$settings = null === $settings ? $this->settings->get() : $this->settings->sanitize( $settings );

		if ( 'llms.txt' === $endpoint ) {
			if ( empty( $settings['enabled_llms_txt'] ) ) {
				return $this->not_found_response();
			}

			return [
				'status'       => 200,
				'content_type' => 'text/plain; charset=utf-8',
				'body'         => $this->renderer->render_llms_txt( $settings ),
			];
		}

		if ( 'agents.json' === $endpoint ) {
			if ( empty( $settings['enabled_agents_json'] ) ) {
				return $this->not_found_response();
			}

			return [
				'status'       => 200,
				'content_type' => 'application/json; charset=utf-8',
				'body'         => $this->renderer->render_agents_json( $settings ),
			];
		}

		return $this->not_found_response();
	}

	public function maybe_handle_request(): ?array {
		if ( ! function_exists( 'get_query_var' ) ) {
			return null;
		}

		$endpoint = (string) get_query_var( 'lean_seo_ai_endpoint' );

		if ( '' === $endpoint ) {
			return null;
		}

		return $this->build_endpoint_response( $endpoint );
	}

	public function template_redirect(): void {
		$response = $this->maybe_handle_request();

		if ( null === $response ) {
			return;
		}
	}

	private function not_found_response(): array {
		return [
			'status'       => 404,
			'content_type' => 'text/plain; charset=utf-8',
			'body'         => '',
		];
	}
}
