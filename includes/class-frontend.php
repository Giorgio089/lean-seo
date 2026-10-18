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
			add_action( 'wp_head', [ $this, 'output_canonical' ], 1 );
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
			// Set the full custom title and remove tagline/site-name parts
			// so WordPress does not append the site name a second time.
			$parts['title'] = $seo_title;
			unset( $parts['tagline'], $parts['site'] );
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

	public function output_canonical(): void {
		$seo = $this->get_current_seo_data();

		if ( empty( $seo['canonical'] ) ) {
			return;
		}

		$url = esc_url( $seo['canonical'] );
		echo '<link rel="canonical" href="' . $url . '">' . "\n";
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
		$post_id = (int) ( $seo['post_id'] ?? 0 );

		$og = $this->build_open_graph_tags( $seo, $post_id );
		if ( '' !== $og ) {
			echo $og . "\n";
		}

		$twitter = $this->build_twitter_card_tags( $seo, $post_id );
		if ( '' !== $twitter ) {
			echo $twitter . "\n";
		}

		$schema_markup = $this->build_schema_script( $seo, $post_id );

		if ( '' !== $schema_markup ) {
			echo $schema_markup . "\n";
		}
	}

	public function build_open_graph_tags( array $seo, int $post_id ): string {
		$title = ! empty( $seo['meta_title'] )
			? wp_strip_all_tags( $seo['meta_title'] )
			: ( function_exists( 'get_the_title' ) && $post_id > 0
				? wp_strip_all_tags( get_the_title( $post_id ) )
				: ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : '' ) );

		$description = ! empty( $seo['meta_desc'] )
			? wp_strip_all_tags( $seo['meta_desc'] )
			: ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'description' ) : '' );

		$url = function_exists( 'get_permalink' ) && $post_id > 0
			? (string) get_permalink( $post_id )
			: ( function_exists( 'home_url' ) ? home_url( '/' ) : '' );

		$type      = ( function_exists( 'is_front_page' ) && is_front_page() ) ? 'website' : 'article';
		$site_name = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : '';
		$locale    = function_exists( 'get_locale' )   ? get_locale()           : 'de_DE';
		$image     = $this->resolve_og_image( $seo, $post_id );

		$tags = [];

		if ( '' !== $type )        { $tags[] = '<meta property="og:type"        content="' . esc_attr( $type )        . '">'; }
		if ( '' !== $title )       { $tags[] = '<meta property="og:title"       content="' . esc_attr( $title )       . '">'; }
		if ( '' !== $description ) { $tags[] = '<meta property="og:description" content="' . esc_attr( $description ) . '">'; }
		if ( '' !== $url )         { $tags[] = '<meta property="og:url"         content="' . esc_url( $url )          . '">'; }
		if ( '' !== $image )       { $tags[] = '<meta property="og:image"       content="' . esc_url( $image )        . '">'; }
		if ( '' !== $site_name )   { $tags[] = '<meta property="og:site_name"   content="' . esc_attr( $site_name )   . '">'; }
		if ( '' !== $locale )      { $tags[] = '<meta property="og:locale"      content="' . esc_attr( $locale )      . '">'; }

		return implode( "\n", $tags );
	}

	public function build_twitter_card_tags( array $seo, int $post_id ): string {
		$image = $this->resolve_og_image( $seo, $post_id );
		$card  = '' !== $image ? 'summary_large_image' : 'summary';

		$title = ! empty( $seo['meta_title'] )
			? wp_strip_all_tags( $seo['meta_title'] )
			: ( function_exists( 'get_the_title' ) && $post_id > 0
				? wp_strip_all_tags( get_the_title( $post_id ) )
				: '' );

		$description = ! empty( $seo['meta_desc'] )
			? wp_strip_all_tags( $seo['meta_desc'] )
			: '';

		$tags = [ '<meta name="twitter:card" content="' . esc_attr( $card ) . '">' ];

		if ( '' !== $title )       { $tags[] = '<meta name="twitter:title"       content="' . esc_attr( $title )       . '">'; }
		if ( '' !== $description ) { $tags[] = '<meta name="twitter:description" content="' . esc_attr( $description ) . '">'; }
		if ( '' !== $image )       { $tags[] = '<meta name="twitter:image"       content="' . esc_url( $image )        . '">'; }

		return implode( "\n", $tags );
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
				$settings = class_exists( '\LeanSEO\Site_Settings' )
					? \LeanSEO\Site_Settings::instance()->get()
					: [];

				$name = ! empty( $settings['org_name'] )
					? $settings['org_name']
					: ( function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'name' ) : '' );

				$url = function_exists( 'home_url' ) ? (string) home_url( '/' ) : '/';

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

				// Address block – only emit if at least street is configured
				$street = (string) ( $settings['org_street'] ?? '' );
				if ( '' !== $street ) {
					$schema['address'] = array_filter( [
						'@type'           => 'PostalAddress',
						'streetAddress'   => $street,
						'postalCode'      => (string) ( $settings['org_postal']  ?? '' ),
						'addressLocality' => (string) ( $settings['org_city']    ?? '' ),
						'addressCountry'  => (string) ( $settings['org_country'] ?? 'DE' ),
					] );
				}

				// Telephone
				$phone = (string) ( $settings['org_phone'] ?? '' );
				if ( '' !== $phone ) {
					$schema['telephone'] = $phone;
				}

				// Logo / image
				$logo = (string) ( $settings['org_logo_url'] ?? '' );
				if ( '' !== $logo ) {
					$schema['image'] = $logo;
				} elseif ( $post_id > 0 && function_exists( 'get_post_thumbnail_id' ) ) {
					$thumb_id = (int) get_post_thumbnail_id( $post_id );
					if ( $thumb_id > 0 ) {
						$img_url = wp_get_attachment_image_url( $thumb_id, 'large' );
						if ( $img_url ) {
							$schema['image'] = (string) $img_url;
						}
					}
				}

				return $schema;

			case 'organization':
				$settings = class_exists( '\LeanSEO\Site_Settings' )
					? \LeanSEO\Site_Settings::instance()->get()
					: [];

				$name = ! empty( $settings['org_name'] )
					? $settings['org_name']
					: ( function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'name' ) : '' );

				$url = function_exists( 'home_url' ) ? (string) home_url( '/' ) : '/';

				$description = '' !== ( $seo['meta_desc'] ?? '' )
					? wp_strip_all_tags( $seo['meta_desc'] )
					: ( function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'description' ) : '' );

				$schema = array_merge( $base, [
					'@type' => 'Organization',
					'name'  => $name,
					'url'   => $url,
				] );

				if ( '' !== $description ) {
					$schema['description'] = $description;
				}

				// Logo as ImageObject (Google prefers this over plain URL for Organization)
				$logo = (string) ( $settings['org_logo_url'] ?? '' );
				if ( '' !== $logo ) {
					$schema['logo'] = [
						'@type' => 'ImageObject',
						'url'   => $logo,
					];
				}

				// Telephone
				$phone = (string) ( $settings['org_phone'] ?? '' );
				if ( '' !== $phone ) {
					$schema['telephone'] = $phone;
				}

				// Address
				$street = (string) ( $settings['org_street'] ?? '' );
				if ( '' !== $street ) {
					$schema['address'] = array_filter( [
						'@type'           => 'PostalAddress',
						'streetAddress'   => $street,
						'postalCode'      => (string) ( $settings['org_postal']  ?? '' ),
						'addressLocality' => (string) ( $settings['org_city']    ?? '' ),
						'addressCountry'  => (string) ( $settings['org_country'] ?? 'DE' ),
					] );
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

	private function resolve_og_image( array $seo, int $post_id ): string {
		// 1. Per-post override
		if ( ! empty( $seo['og_image'] ) ) {
			return (string) $seo['og_image'];
		}

		// 2. Post featured image
		if ( $post_id > 0 && function_exists( 'get_post_thumbnail_id' ) ) {
			$thumb_id = (int) get_post_thumbnail_id( $post_id );
			if ( $thumb_id > 0 ) {
				$url = wp_get_attachment_image_url( $thumb_id, 'large' );
				if ( $url ) {
					return (string) $url;
				}
			}
		}

		// 3. Global fallback (Site_Settings)
		if ( class_exists( '\LeanSEO\Site_Settings' ) ) {
			$global = \LeanSEO\Site_Settings::instance()->get();
			if ( ! empty( $global['og_fallback_image'] ) ) {
				return (string) $global['og_fallback_image'];
			}
		}

		return '';
	}

	private function get_current_seo_data(): array {
		if ( ! function_exists( 'get_queried_object_id' ) ) {
			return [];
		}

		$post_id = (int) get_queried_object_id();
		$globals = get_option( 'lean_seo_ai_endpoints', [] );

		if ( $post_id <= 0 ) {
			return $globals;
		}

		$data = $this->store->get_post_seo_data( $post_id );

		// Post-spezifische Werte haben Vorrang; nur nicht-leere Felder überschreiben.
		foreach ( $data as $key => $value ) {
			if ( '' !== $value && 0 !== $value && null !== $value ) {
				$globals[ $key ] = $value;
			}
		}

		return $globals;
	}
}
