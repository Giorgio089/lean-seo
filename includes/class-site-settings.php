<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Site_Settings {
	private const OPTION_KEY = 'lean_seo_site_settings';
	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function get(): array {
		$value = function_exists( 'get_option' )
			? get_option( self::OPTION_KEY, [] )
			: [];

		return $this->sanitize( is_array( $value ) ? $value : [] );
	}

	public function sanitize( array $input ): array {
		$input = array_merge( $this->defaults(), $input );

		return [
			// Meta
			'title_separator'   => $this->sanitize_text( $input['title_separator'] ),
			'homepage_title'    => $this->sanitize_text( $input['homepage_title'] ),
			'homepage_desc'     => $this->sanitize_textarea( $input['homepage_desc'] ),

			// OG
			'og_fallback_image' => $this->sanitize_url( $input['og_fallback_image'] ),

			// Organisation (feeds T04 LocalBusiness + T05 Organization schema)
			'org_name'          => $this->sanitize_text( $input['org_name'] ),
			'org_logo_url'      => $this->sanitize_url( $input['org_logo_url'] ),
			'org_phone'         => $this->sanitize_text( $input['org_phone'] ),
			'org_street'        => $this->sanitize_text( $input['org_street'] ),
			'org_postal'        => $this->sanitize_text( $input['org_postal'] ),
			'org_city'          => $this->sanitize_text( $input['org_city'] ),
			'org_country'       => $this->sanitize_text( $input['org_country'] ),
		];
	}

	public function option_key(): string {
		return self::OPTION_KEY;
	}

	private function defaults(): array {
		return [
			'title_separator'   => '–',
			'homepage_title'    => '',
			'homepage_desc'     => '',
			'og_fallback_image' => '',
			'org_name'          => function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : '',
			'org_logo_url'      => '',
			'org_phone'         => '',
			'org_street'        => '',
			'org_postal'        => '',
			'org_city'          => '',
			'org_country'       => 'DE',
		];
	}

	private function sanitize_text( $v ): string {
		return function_exists( 'sanitize_text_field' )
			? sanitize_text_field( (string) $v )
			: trim( strip_tags( (string) $v ) );
	}

	private function sanitize_textarea( $v ): string {
		return function_exists( 'sanitize_textarea_field' )
			? sanitize_textarea_field( (string) $v )
			: trim( strip_tags( (string) $v ) );
	}

	private function sanitize_url( $v ): string {
		$s = function_exists( 'esc_url_raw' )
			? esc_url_raw( (string) $v )
			: filter_var( (string) $v, FILTER_VALIDATE_URL );

		return is_string( $s ) ? $s : '';
	}
}
