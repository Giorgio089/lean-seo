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

		// Pass post_id (stored in $seo by Data_Store::normalize_row) so the
		// schema builder can pull live WordPress data for the current post.
		$schema_markup = $this->build_schema_script( $seo, (int) ( $seo['post_id'] ?? 0 ) );

		if ( '' !== $schema_markup ) {
			echo $schema_markup . "\n";
		}
	}

	public function build_schema_script( array $seo, int $post_id = 0 ): string {
		if ( empty( $seo['schema_type'] ) || 'none' === $seo['schema_type'] ) {
			return '';
		}

		// Fall back to post_id embedded in $seo when not explicitly supplied.
		$post_id = $post_id > 0 ? $post_id : (int) ( $seo['post_id'] ?? 0 );
		$schema  = $this->generate_schema( (string) $seo['schema_type'], $seo, $post_id );

		if ( [] === $schema ) {
			return '';
		}

		return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
	}

	private function generate_schema( string $schema_type, array $seo, int $post_id ): array {
		$base = [ '@context' => 'https://schema.org' ];

		switch ( $schema_type ) {

			case 'article':
				if ( $post_id <= 0 || ! function_exists( 'get_post' ) ) {
					return [];
				}

				$post = get_post( $post_id );
				if ( ! $post ) {
					return [];
				}

				$headline    = '' !== ( $seo['meta_title'] ?? '' )
					? wp_strip_all_tags( $seo['meta_title'] )
					: wp_strip_all_tags( get_the_title( $post_id ) );

				$description = '' !== ( $seo['meta_desc'] ?? '' )
					? wp_strip_all_tags( $seo['meta_desc'] )
					: wp_strip_all_tags( wp_trim_words( get_the_excerpt( $post_id ), 30, '' ) );

				$schema = array_merge( $base, [
					'@type'         => 'Article',
					'headline'      => $headline,
					'url'           => (string) get_permalink( $post_id ),
					'datePublished' => (string) get_the_date( 'c', $post_id ),
					'dateModified'  => (string) get_the_modified_date( 'c', $post_id ),
					'author'        => [
						'@type' => 'Person',
						'name'  => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
					],
				] );

				if ( '' !== $description ) {
					$schema['description'] = $description;
				}

				$image_id = function_exists( 'get_post_thumbnail_id' ) ? (int) get_post_thumbnail_id( $post_id ) : 0;
				if ( $image_id > 0 ) {
					$image_url = wp_get_attachment_image_url( $image_id, 'large' );
					if ( $image_url ) {
						$schema['image'] = $image_url;
					}
				}

				return $schema;

			case 'local_business':
				$name        = function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'name' ) : '';
				$url         = function_exists( 'home_url' ) ? (string) home_url( '/' ) : '/';
				$description = '' !== ( $seo['meta_desc'] ?? '' )
					? wp_strip_all_tags( $seo['meta_desc'] )
					: ( function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'description' ) : '' );

				$schema = array_merge( $base, [
					'@type' => 'LocalBusiness',
					'name'  => $name,
					'url'   => $url,
				] );

				if ( '' !== $description ) {
					$schema['description'] = $description;
				}

				if ( $post_id > 0 && function_exists( 'get_post_thumbnail_id' ) ) {
					$image_id = (int) get_post_thumbnail_id( $post_id );
					if ( $image_id > 0 ) {
						$image_url = wp_get_attachment_image_url( $image_id, 'large' );
						if ( $image_url ) {
							$schema['image'] = $image_url;
						}
					}
				}

				return $schema;

			case 'faq':
				// FAQPage requires mainEntity (Question + acceptedAnswer pairs).
				// Suppressed until a FAQ content field is added to the data model.
				return [];

			default:
				return [];
		}
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
