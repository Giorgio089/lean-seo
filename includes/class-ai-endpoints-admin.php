<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class AI_Endpoints_Admin {
	public function __construct( private AI_Endpoints_Settings $settings ) {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'admin_menu', [ $this, 'register_menu' ] );
			add_action( 'admin_init', [ $this, 'register_settings' ] );
		}
	}

	public function register_menu(): void {
		if ( ! function_exists( 'add_options_page' ) ) {
			return;
		}

		add_options_page(
			'Lean SEO AI Endpoints',
			'Lean SEO AI',
			'manage_options',
			'lean-seo-ai-endpoints',
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		if ( ! function_exists( 'register_setting' ) ) {
			return;
		}

		register_setting(
			'lean_seo_ai_endpoints',
			$this->settings->option_key(),
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this->settings, 'sanitize' ],
				'default'           => $this->settings->get(),
			]
		);
	}

	public function render_page(): void {
		$values = $this->settings->get();

		echo '<div class="wrap">';
		echo '<h1>Lean SEO AI Endpoints</h1>';
		echo '<form method="post" action="options.php">';
		echo '<input type="hidden" name="lean_seo_ai_endpoints[enabled_llms_txt]" value="' . esc_attr( (string) $values['enabled_llms_txt'] ) . '">';
		echo '<input type="hidden" name="lean_seo_ai_endpoints[enabled_agents_json]" value="' . esc_attr( (string) $values['enabled_agents_json'] ) . '">';
		echo '</form>';
		echo '</div>';
	}
}
