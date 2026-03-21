<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

if ( isset( $wpdb ) && method_exists( $wpdb, 'query' ) ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}leanseo_data" );
}

if ( function_exists( 'delete_option' ) ) {
	delete_option( 'lean_seo_schema_version' );
	delete_option( 'lean_seo_ai_endpoints' );
}
