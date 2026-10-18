<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Site_Settings_Admin {
	public function __construct( private Site_Settings $settings ) {
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
			'Lean SEO',
			'Lean SEO',
			'manage_options',
			'lean-seo',
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		if ( ! function_exists( 'register_setting' ) ) {
			return;
		}

		register_setting(
			'lean_seo_site_settings',
			$this->settings->option_key(),
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this->settings, 'sanitize' ],
				'default'           => $this->settings->get(),
			]
		);
	}

	public function render_page(): void {
		$v = $this->settings->get();
		$k = $this->settings->option_key();
		$f = fn( $name ) => esc_attr( $k . '[' . $name . ']' );

		echo '<div class="wrap"><h1>Lean SEO – Einstellungen</h1>';
		settings_errors( 'lean_seo_site_settings' );
		echo '<form method="post" action="options.php">';
		if ( function_exists( 'settings_fields' ) ) {
			settings_fields( 'lean_seo_site_settings' );
		}

		// ── META ──────────────────────────────────────────────────────────
		echo '<h2>Meta</h2>';
		echo '<table class="form-table"><tbody>';
		$this->row( 'Titel-Trennzeichen', 'text', $f( 'title_separator' ), $v['title_separator'],
			'Wird zwischen Seitentitel und Sitename gesetzt. Standard: –' );
		$this->row( 'Homepage Meta-Titel', 'text', $f( 'homepage_title' ), $v['homepage_title'],
			'Überschreibt den automatischen Titel der Startseite.' );
		$this->textarea_row( 'Homepage Meta-Description', $f( 'homepage_desc' ), $v['homepage_desc'] );
		echo '</tbody></table>';

		// ── OPEN GRAPH ────────────────────────────────────────────────────
		echo '<h2>Open Graph</h2>';
		echo '<table class="form-table"><tbody>';
		$this->row( 'Fallback OG-Bild (URL)', 'url', $f( 'og_fallback_image' ), $v['og_fallback_image'],
			'Wird verwendet wenn kein Featured Image und kein per-Post-OG-Bild gesetzt ist.' );
		echo '</tbody></table>';

		// ── ORGANISATION ──────────────────────────────────────────────────
		echo '<h2>Organisation</h2>';
		echo '<p>Wird für LocalBusiness- und Organization-Schema verwendet.</p>';
		echo '<table class="form-table"><tbody>';
		$this->row( 'Name',       'text', $f( 'org_name' ),    $v['org_name'] );
		$this->row( 'Logo (URL)', 'url',  $f( 'org_logo_url' ), $v['org_logo_url'] );
		$this->row( 'Telefon',    'text', $f( 'org_phone' ),   $v['org_phone'],  '+49 40 123 456' );
		$this->row( 'Straße',     'text', $f( 'org_street' ),  $v['org_street'], 'Eppendorfer Weg 235' );
		$this->row( 'PLZ',        'text', $f( 'org_postal' ),  $v['org_postal'], '20251' );
		$this->row( 'Stadt',      'text', $f( 'org_city' ),    $v['org_city'],   'Hamburg' );
		$this->row( 'Land (ISO)', 'text', $f( 'org_country' ), $v['org_country'], 'DE' );
		echo '</tbody></table>';

		if ( function_exists( 'submit_button' ) ) {
			submit_button( 'Einstellungen speichern' );
		}

		echo '</form></div>';
	}

	private function row( string $label, string $type, string $name, string $value, string $placeholder = '' ): void {
		echo '<tr>';
		echo '<th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td><input type="' . esc_attr( $type ) . '" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="regular-text"' . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . '></td>';
		echo '</tr>';
	}

	private function textarea_row( string $label, string $name, string $value ): void {
		echo '<tr>';
		echo '<th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td><textarea id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" rows="3" class="large-text">' . esc_textarea( $value ) . '</textarea></td>';
		echo '</tr>';
	}
}
