<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Loader {
	public static function register(): void {
		spl_autoload_register(
			static function ( string $class ): void {
				$prefix = __NAMESPACE__ . '\\';

				if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
					return;
				}

				$relative   = substr( $class, strlen( $prefix ) );
				$segments   = explode( '\\', $relative );
				$class_name = array_pop( $segments );
				$directory  = empty( $segments ) ? '' : '/' . strtolower( implode( '/', $segments ) );
				$file_name  = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
				$path       = __DIR__ . $directory . '/' . $file_name;

				if ( file_exists( $path ) ) {
					require_once $path;
				}
			}
		);
	}
}
