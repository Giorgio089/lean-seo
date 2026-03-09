<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Frontend {
	public function __construct( private Data_Store $store ) {
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return;
		}

		if ( function_exists( 'add_filter' ) ) {
			add_filter( 'document_title_parts', [ $this, 'filter_title' ] );
			add_filter( 'wp_robots', [ $this, 'filter_robots' ] );
		}

		if ( function_exists( 'add_action' ) ) {
			add_action( 'wp_head', [ $this, 'output_meta_and_schema' ], 1 );
		}
	}

	public function build_description_tag( array $seo ): string {
		if ( empty( $seo['meta_desc'] ) ) {
			return '';
		}

		return '<meta name="description" content="' . esc_attr( wp_strip_all_tags( (string) $seo['meta_desc'] ) ) . '">';
	}

	public function filter_title( array $parts ): array {
		$seo_title = $this->get_current_seo_data()['meta_title'] ?? '';

		if ( '' !== $seo_title ) {
			$parts['title'] = $seo_title;
		}

		return $parts;
	}

	public function filter_robots( array $robots ): array {
		$seo = $this->get_current_seo_data();

		if ( ! empty( $seo['robots_noindex'] ) ) {
			$robots['noindex'] = true;
		}

		if ( ! empty( $seo['robots_nofollow'] ) ) {
			$robots['nofollow'] = true;
		}

		return $robots;
	}

	public function output_meta_and_schema(): void {
		$seo = $this->get_current_seo_data();

		if ( empty( $seo ) ) {
			return;
		}

		$description = $this->build_description_tag( $seo );

		if ( '' !== $description ) {
			echo $description . "\n";
		}

		$schema_markup = $this->build_schema_script( $seo );

		if ( '' !== $schema_markup ) {
			echo $schema_markup . "\n";
		}
	}

	public function build_schema_script( array $seo ): string {
		if ( empty( $seo['schema_type'] ) || 'none' === $seo['schema_type'] ) {
			return '';
		}

		$schema = $this->generate_schema( (string) $seo['schema_type'] );

		if ( [] === $schema ) {
			return '';
		}

		return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
	}

	private function generate_schema( string $schema_type ): array {
		return match ( $schema_type ) {
			'article' => [
				'@context' => 'https://schema.org',
				'@type'    => 'Article',
			],
			'local_business' => [
				'@context' => 'https://schema.org',
				'@type'    => 'LocalBusiness',
			],
			'faq' => [
				'@context' => 'https://schema.org',
				'@type'    => 'FAQPage',
			],
			default => [],
		};
	}

	private function get_current_seo_data(): array {
		if ( ! function_exists( 'get_queried_object_id' ) ) {
			return [];
		}

		$post_id = (int) get_queried_object_id();

		if ( $post_id <= 0 ) {
			return [];
		}

		return $this->store->get_post_seo_data( $post_id );
	}
}
