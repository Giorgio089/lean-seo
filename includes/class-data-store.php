<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Data_Store {
	private const ALLOWED_FIELDS = [
		'meta_title',
		'meta_desc',
		'robots_noindex',
		'robots_nofollow',
		'schema_type',
	];

	public function get_post_seo_data( int $post_id ): array {
		$cache_key = $this->cache_key( $post_id );
		$cached    = function_exists( 'wp_cache_get' ) ? wp_cache_get( $cache_key, 'lean_seo' ) : false;

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		if ( ! isset( $wpdb ) || ! method_exists( $wpdb, 'get_row' ) || ! method_exists( $wpdb, 'prepare' ) ) {
			$row = $this->normalize_row( [ 'post_id' => $post_id ] );
			$this->cache_row( $row );

			return $row;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name()} WHERE post_id = %d",
				$post_id
			),
			ARRAY_A
		);

		$row = $this->normalize_row( is_array( $row ) ? $row : [ 'post_id' => $post_id ] );
		$this->cache_row( $row );

		return $row;
	}

	public function update_field( int $post_id, string $field, $value ): bool {
		global $wpdb;

		if ( ! in_array( $field, self::ALLOWED_FIELDS, true ) ) {
			return false;
		}

		if ( ! isset( $wpdb ) || ! method_exists( $wpdb, 'query' ) || ! method_exists( $wpdb, 'prepare' ) ) {
			$this->delete_cache( $post_id );

			return false;
		}

		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->table_name()} (post_id, {$field}, updated_at) VALUES (%d, %s, %s)
				ON DUPLICATE KEY UPDATE {$field} = VALUES({$field}), updated_at = VALUES(updated_at)",
				$post_id,
				(string) $value,
				gmdate( 'Y-m-d H:i:s' )
			)
		);

		$this->delete_cache( $post_id );

		return false !== $result;
	}

	public function normalize_row( array $row ): array {
		return [
			'post_id'         => (int) ( $row['post_id'] ?? 0 ),
			'meta_title'      => (string) ( $row['meta_title'] ?? '' ),
			'meta_desc'       => (string) ( $row['meta_desc'] ?? '' ),
			'robots_noindex'  => ! empty( $row['robots_noindex'] ) ? 1 : 0,
			'robots_nofollow' => ! empty( $row['robots_nofollow'] ) ? 1 : 0,
			'schema_type'     => (string) ( $row['schema_type'] ?? 'none' ),
			'updated_at'      => $row['updated_at'] ?? null,
		];
	}

	public function delete_cache( int $post_id ): void {
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( $this->cache_key( $post_id ), 'lean_seo' );
		}
	}

	private function cache_key( int $post_id ): string {
		return 'lean_seo_post_' . $post_id;
	}

	private function cache_row( array $row ): void {
		if ( function_exists( 'wp_cache_set' ) ) {
			wp_cache_set( $this->cache_key( (int) $row['post_id'] ), $row, 'lean_seo' );
		}
	}

	private function table_name(): string {
		global $wpdb;

		$prefix = isset( $wpdb ) && isset( $wpdb->prefix ) ? $wpdb->prefix : 'wp_';

		return $prefix . 'leanseo_data';
	}
}
