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

require_once __DIR__ . '/includes/class-loader.php';

\LeanSEO\Loader::register();
\LeanSEO\Plugin::instance()->boot();
