<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Meta_Hijack {
	private const META_TO_COLUMN = [
		'_lean_seo_title'    => 'meta_title',
		'_lean_seo_desc'     => 'meta_desc',
		'_lean_seo_noindex'  => 'robots_noindex',
		'_lean_seo_nofollow' => 'robots_nofollow',
		'_lean_seo_schema'   => 'schema_type',
	];

	public function __construct( private Data_Store $store ) {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'init', [ $this, 'register_rest_fields' ] );
		}

		if ( function_exists( 'add_filter' ) ) {
			add_filter( 'get_post_metadata', [ $this, 'hijack_get_meta' ], 10, 4 );
			add_filter( 'update_post_metadata', [ $this, 'hijack_update_meta' ], 10, 5 );
		}
	}

	public function column_for_meta_key( string $meta_key ): ?string {
		return self::META_TO_COLUMN[ $meta_key ] ?? null;
	}

	public function register_rest_fields(): void {
		if ( ! function_exists( 'get_post_types' ) || ! function_exists( 'register_post_meta' ) ) {
			return;
		}

		$post_types = get_post_types( [ 'show_in_rest' => true ], 'names' );

		foreach ( array_keys( self::META_TO_COLUMN ) as $meta_key ) {
			$type = str_contains( $meta_key, 'noindex' ) || str_contains( $meta_key, 'nofollow' ) ? 'boolean' : 'string';

			foreach ( $post_types as $post_type ) {
				register_post_meta(
					$post_type,
					$meta_key,
					[
						'show_in_rest' => true,
						'single'       => true,
						'type'         => $type,
					]
				);
			}
		}
	}

	public function hijack_get_meta( $check, int $object_id, string $meta_key, bool $single ) {
		$column = $this->column_for_meta_key( $meta_key );

		if ( null === $column ) {
			return $check;
		}

		$seo_data = $this->store->get_post_seo_data( $object_id );
		$value    = $seo_data[ $column ] ?? '';

		if ( $single ) {
			return $value;
		}

		return [ $value ];
	}

	public function hijack_update_meta( $check, int $object_id, string $meta_key, $meta_value, $prev_value ) {
		$column = $this->column_for_meta_key( $meta_key );

		if ( null === $column ) {
			return $check;
		}

		$sanitized = $this->sanitize_value( $meta_key, $meta_value );
		$this->store->update_field( $object_id, $column, $sanitized );

		return true;
	}

	private function sanitize_value( string $meta_key, $meta_value ) {
		if ( in_array( $meta_key, [ '_lean_seo_noindex', '_lean_seo_nofollow' ], true ) ) {
			return ! empty( $meta_value ) ? 1 : 0;
		}

		if ( '_lean_seo_desc' === $meta_key ) {
			return function_exists( 'sanitize_textarea_field' )
				? sanitize_textarea_field( $meta_value )
				: trim( strip_tags( (string) $meta_value ) );
		}

		if ( '_lean_seo_schema' === $meta_key ) {
			$allowed = [ 'none', 'article', 'local_business', 'faq' ];
			$value   = function_exists( 'sanitize_text_field' )
				? sanitize_text_field( $meta_value )
				: trim( strip_tags( (string) $meta_value ) );

			return in_array( $value, $allowed, true ) ? $value : 'none';
		}

		return function_exists( 'sanitize_text_field' )
			? sanitize_text_field( $meta_value )
			: trim( strip_tags( (string) $meta_value ) );
	}
}
