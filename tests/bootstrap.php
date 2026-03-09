<?php

define( 'ABSPATH', __DIR__ . '/../' );

$GLOBALS['lean_seo_test_cache'] = [];

if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( string $key, string $group = '' ) {
		$bucket = $GLOBALS['lean_seo_test_cache'][ $group ] ?? [];

		return array_key_exists( $key, $bucket ) ? $bucket[ $key ] : false;
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( string $key, $value, string $group = '' ): bool {
		$GLOBALS['lean_seo_test_cache'][ $group ][ $key ] = $value;

		return true;
	}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
	function wp_cache_delete( string $key, string $group = '' ): bool {
		unset( $GLOBALS['lean_seo_test_cache'][ $group ][ $key ] );

		return true;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ): string {
		return trim( strip_tags( (string) $value ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $value ): string {
		return trim( strip_tags( (string) $value ) );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		return true;
	}
}

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( array $args = [], string $output = 'names' ): array {
		return [ 'post', 'page' ];
	}
}

if ( ! function_exists( 'register_post_meta' ) ) {
	function register_post_meta( string $post_type, string $meta_key, array $args = [] ): bool {
		return true;
	}
}

$loader = __DIR__ . '/../includes/class-loader.php';

if ( file_exists( $loader ) ) {
	require_once $loader;
	\LeanSEO\Loader::register();
}
