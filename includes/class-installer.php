<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Installer {
	public static function activate(): void {
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		global $wpdb;

		$table_name      = $wpdb->prefix . 'leanseo_data';
		$charset_collate = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table_name} (
				post_id BIGINT(20) UNSIGNED NOT NULL,
				meta_title TEXT NULL,
				meta_desc TEXT NULL,
				robots_noindex TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
				robots_nofollow TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
				schema_type VARCHAR(50) NOT NULL DEFAULT 'none',
				updated_at DATETIME NULL,
				PRIMARY KEY  (post_id)
			) {$charset_collate};"
		);

		if ( function_exists( 'update_option' ) ) {
			update_option(
				'lean_seo_schema_version',
				defined( 'LEAN_SEO_SCHEMA_VERSION' ) ? LEAN_SEO_SCHEMA_VERSION : '1'
			);
		}
	}
}
