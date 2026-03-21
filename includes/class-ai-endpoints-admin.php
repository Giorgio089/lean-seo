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
		settings_errors( 'lean_seo_ai_endpoints' );
		echo '<form method="post" action="options.php">';
		if ( function_exists( 'settings_fields' ) ) {
			settings_fields( 'lean_seo_ai_endpoints' );
		}

		echo '<p><label><input type="checkbox" name="lean_seo_ai_endpoints[enabled_llms_txt]" value="1" ' . checked( 1, (int) $values['enabled_llms_txt'], false ) . '> Enable llms.txt</label></p>';
		echo '<p><label><input type="checkbox" name="lean_seo_ai_endpoints[enabled_agents_json]" value="1" ' . checked( 1, (int) $values['enabled_agents_json'], false ) . '> Enable agents.json</label></p>';
		echo '<p><label>LLMS Summary<br><textarea name="lean_seo_ai_endpoints[llms_summary]" rows="4" cols="50">' . esc_textarea( $values['llms_summary'] ) . '</textarea></label></p>';
		echo '<p><label>Contact URL<br><input type="url" name="lean_seo_ai_endpoints[llms_contact_url]" value="' . esc_attr( $values['llms_contact_url'] ) . '" class="regular-text"></label></p>';
		echo '<p><label>Policy URL<br><input type="url" name="lean_seo_ai_endpoints[llms_policy_url]" value="' . esc_attr( $values['llms_policy_url'] ) . '" class="regular-text"></label></p>';
		echo '<p><label>Agent Name<br><input type="text" name="lean_seo_ai_endpoints[agents_name]" value="' . esc_attr( $values['agents_name'] ) . '" class="regular-text"></label></p>';
		echo '<p><label>Agent Description<br><textarea name="lean_seo_ai_endpoints[agents_description]" rows="4" cols="50">' . esc_textarea( $values['agents_description'] ) . '</textarea></label></p>';
		echo '<p><label>Agent URL<br><input type="url" name="lean_seo_ai_endpoints[agents_url]" value="' . esc_attr( $values['agents_url'] ) . '" class="regular-text"></label></p>';
		if ( function_exists( 'submit_button' ) ) {
			submit_button( 'Save AI Endpoint Settings' );
		}
		echo '</form>';
		echo '</div>';
	}
}
