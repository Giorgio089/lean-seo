<?php
/**
 * Plugin Name: Lean SEO
 * Description: Lean, performance-first SEO plugin for WordPress.
 * Version: 0.5.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: LeanPlugins
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'LEAN_SEO_FILE',    __FILE__ );
define( 'LEAN_SEO_DIR',     plugin_dir_path( __FILE__ ) );
define( 'LEAN_SEO_URL',     plugin_dir_url( __FILE__ ) );
define( 'LEAN_SEO_VERSION', '0.5.0' );

if ( ! defined( 'LEAN_SEO_SCHEMA_VERSION' ) ) {
	define( 'LEAN_SEO_SCHEMA_VERSION', '2' );
}

require_once __DIR__ . '/includes/class-loader.php';

\LeanSEO\Loader::register();

register_activation_hook( __FILE__, [ \LeanSEO\Installer::class, 'activate' ] );

register_deactivation_hook( __FILE__, function () {
	if ( function_exists( 'flush_rewrite_rules' ) ) {
		flush_rewrite_rules();
	}
} );

\LeanSEO\Plugin::instance()->boot();


// Sitemap: noindex-Posts aus der WordPress-Sitemap ausschließen.
add_filter(
	'wp_sitemaps_posts_entry',
	static function ( array $entry, \WP_Post $post ): ?array {
		$seo = \LeanSEO\Plugin::instance()
			->service( 'data_store' )
			->get_post_seo_data( $post->ID );

		if ( ! empty( $seo['robots_noindex'] ) ) {
			return null; // WP 5.9+ akzeptiert null zum Ausschließen.
		}

		return $entry;
	},
	10,
	2
);

