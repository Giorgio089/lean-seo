<?php

define( 'ABSPATH', __DIR__ . '/../' );

$loader = __DIR__ . '/../includes/class-loader.php';

if ( file_exists( $loader ) ) {
	require_once $loader;
	\LeanSEO\Loader::register();
}
