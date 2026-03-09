<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Data_Store {
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
}
