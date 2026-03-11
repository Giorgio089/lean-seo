<?php

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'ARRAY_A', 'ARRAY_A' );

$GLOBALS['lean_seo_test_cache'] = [];
$GLOBALS['lean_seo_test_state'] = [
	'actions'          => [],
	'dbdelta_queries'  => [],
	'enqueued_scripts' => [],
	'filters'          => [],
	'is_admin'         => false,
	'menus'            => [],
	'options'          => [],
	'plugins_url_base' => 'https://example.com/wp-content/plugins/lean-seo/',
	'queried_object_id'=> 0,
	'registered_settings' => [],
	'registered_meta'  => [],
	'rewrite_rules'    => [],
	'rewrite_rules_flushed' => false,
	'query_vars'       => [],
	'site_description' => 'Lean SEO test site description',
	'site_name'        => 'Lean SEO Test Site',
	'site_url'         => 'https://example.com/',
];

function lean_seo_test_reset_state(): void {
	$GLOBALS['lean_seo_test_cache'] = [];
	$GLOBALS['lean_seo_test_state'] = [
		'actions'          => [],
		'dbdelta_queries'  => [],
		'enqueued_scripts' => [],
		'filters'          => [],
		'is_admin'         => false,
		'menus'            => [],
		'options'          => [],
		'plugins_url_base' => 'https://example.com/wp-content/plugins/lean-seo/',
		'queried_object_id'=> 0,
		'registered_settings' => [],
		'registered_meta'  => [],
		'rewrite_rules'    => [],
		'rewrite_rules_flushed' => false,
		'query_vars'       => [],
		'site_description' => 'Lean SEO test site description',
		'site_name'        => 'Lean SEO Test Site',
		'site_url'         => 'https://example.com/',
	];
}

final class Lean_SEO_Test_WPDB {
	public string $prefix = 'wp_';
	public array $rows = [];
	public array $get_row_queries = [];
	public array $query_log = [];
	public array $prepared_queries = [];
	public array $dropped_tables = [];

	public function prepare( string $query, ...$args ): string {
		$index = 0;
		$prepared = preg_replace_callback(
			'/%(?:\d+\$)?[ds]/',
			static function ( array $matches ) use ( $args, &$index ): string {
				$value = $args[ $index++ ] ?? '';

				if ( str_ends_with( $matches[0], 'd' ) ) {
					return (string) (int) $value;
				}

				return "'" . addslashes( (string) $value ) . "'";
			},
			$query
		);

		$this->prepared_queries[] = $prepared;

		return $prepared;
	}

	public function get_row( string $query, string $output = ARRAY_A ) {
		$this->get_row_queries[] = $query;

		if ( preg_match( '/WHERE post_id = (\d+)/', $query, $matches ) ) {
			$post_id = (int) $matches[1];

			return $this->rows[ $post_id ] ?? null;
		}

		return null;
	}

	public function query( string $query ) {
		$this->query_log[] = $query;

		if ( preg_match( '/DROP TABLE IF EXISTS ([a-zA-Z0-9_]+)/', $query, $matches ) ) {
			$this->dropped_tables[] = $matches[1];

			return 1;
		}

		if ( preg_match( '/INSERT INTO [a-zA-Z0-9_]+ \(post_id, ([a-z_]+), updated_at\) VALUES \((\d+), \'(.*)\', \'(.*)\'\)/', $query, $matches ) ) {
			$field   = $matches[1];
			$post_id = (int) $matches[2];
			$value   = stripslashes( $matches[3] );

			$row = $this->rows[ $post_id ] ?? [ 'post_id' => $post_id ];
			$row[ $field ]      = $value;
			$row['updated_at']  = stripslashes( $matches[4] );
			$this->rows[ $post_id ] = $row;

			return 1;
		}

		return 1;
	}

	public function get_charset_collate(): string {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
	}
}

function lean_seo_test_reset_wpdb(): void {
	$GLOBALS['wpdb'] = new Lean_SEO_Test_WPDB();
}

lean_seo_test_reset_wpdb();

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

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		$sanitized = filter_var( $url, FILTER_VALIDATE_URL );

		return false === $sanitized ? '' : $sanitized;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		$GLOBALS['lean_seo_test_state']['actions'][] = [
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];

		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		$GLOBALS['lean_seo_test_state']['filters'][] = [
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];

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
		$GLOBALS['lean_seo_test_state']['registered_meta'][] = [
			'post_type' => $post_type,
			'meta_key'  => $meta_key,
			'args'      => $args,
		];

		return true;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin(): bool {
		return (bool) $GLOBALS['lean_seo_test_state']['is_admin'];
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $value ): string {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( string $text ): string {
		return trim( strip_tags( $text ) );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value, int $flags = 0 ): string|false {
		return json_encode( $value, $flags );
	}
}

if ( ! function_exists( 'get_the_excerpt' ) ) {
	function get_the_excerpt(): string {
		return '';
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( string $show = '' ): string {
		return match ( $show ) {
			'name' => $GLOBALS['lean_seo_test_state']['site_name'],
			'description' => $GLOBALS['lean_seo_test_state']['site_description'],
			default => '',
		};
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( string $path = '' ): string {
		return rtrim( $GLOBALS['lean_seo_test_state']['site_url'], '/' ) . '/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'get_queried_object_id' ) ) {
	function get_queried_object_id(): int {
		return (int) $GLOBALS['lean_seo_test_state']['queried_object_id'];
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( string $handle, string $src = '', array $deps = [], $ver = false, array|bool $args = false ): bool {
		$GLOBALS['lean_seo_test_state']['enqueued_scripts'][] = [
			'handle' => $handle,
			'src'    => $src,
			'deps'   => $deps,
			'ver'    => $ver,
			'args'   => $args,
		];

		return true;
	}
}

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( string $path = '', string $plugin = '' ): string {
		return rtrim( $GLOBALS['lean_seo_test_state']['plugins_url_base'], '/' ) . '/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( string $query ): array {
		$GLOBALS['lean_seo_test_state']['dbdelta_queries'][] = $query;

		return [ $query ];
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option, $value ): bool {
		$GLOBALS['lean_seo_test_state']['options'][ $option ] = $value;

		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $option ): bool {
		unset( $GLOBALS['lean_seo_test_state']['options'][ $option ] );

		return true;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, $default = false ) {
		return $GLOBALS['lean_seo_test_state']['options'][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'add_options_page' ) ) {
	function add_options_page( string $page_title, string $menu_title, string $capability, string $menu_slug, ?callable $callback = null ): string {
		$GLOBALS['lean_seo_test_state']['menus'][] = [
			'parent_slug' => 'options-general.php',
			'page_title'  => $page_title,
			'menu_title'  => $menu_title,
			'capability'  => $capability,
			'menu_slug'   => $menu_slug,
			'callback'    => $callback,
		];

		return $menu_slug;
	}
}

if ( ! function_exists( 'register_setting' ) ) {
	function register_setting( string $option_group, string $option_name, array $args = [] ): bool {
		$GLOBALS['lean_seo_test_state']['registered_settings'][] = [
			'option_group' => $option_group,
			'option_name'  => $option_name,
			'args'         => $args,
		];

		return true;
	}
}

if ( ! function_exists( 'add_rewrite_rule' ) ) {
	function add_rewrite_rule( string $regex, string $query, string $after = 'bottom' ): bool {
		$GLOBALS['lean_seo_test_state']['rewrite_rules'][] = [
			'regex' => $regex,
			'query' => $query,
			'after' => $after,
		];

		return true;
	}
}

if ( ! function_exists( 'flush_rewrite_rules' ) ) {
	function flush_rewrite_rules(): bool {
		$GLOBALS['lean_seo_test_state']['rewrite_rules_flushed'] = true;

		return true;
	}
}

if ( ! function_exists( 'get_query_var' ) ) {
	function get_query_var( string $var, $default = '' ) {
		return $GLOBALS['lean_seo_test_state']['query_vars'][ $var ] ?? $default;
	}
}

$loader = dirname( __DIR__ ) . '/includes/class-loader.php';

if ( file_exists( $loader ) ) {
	require_once $loader;
	\LeanSEO\Loader::register();
}
