<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Admin_Assets {
	public function __construct() {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_sidebar_assets' ] );
		}
	}

	public function enqueue_sidebar_assets(): void {
		if ( ! function_exists( 'wp_enqueue_script' ) ) {
			return;
		}

		$asset_file = dirname( __DIR__ ) . '/build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'lean-seo-sidebar',
			defined( 'LEAN_SEO_URL' ) ? LEAN_SEO_URL . 'build/index.js' : '',
			$asset['dependencies'] ?? [],
			$asset['version'] ?? LEAN_SEO_VERSION ?? false,
			true
		);
	}
}
