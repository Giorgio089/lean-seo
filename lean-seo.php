<?php
/**
 * Plugin Name: Lean SEO
 * Description: Lean, performance-first SEO plugin for WordPress.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Georg Simic
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LEAN_SEO_SCHEMA_VERSION' ) ) {
	define( 'LEAN_SEO_SCHEMA_VERSION', '1' );
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
