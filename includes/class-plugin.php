<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	private static ?self $instance = null;
	private bool $booted = false;
	private ?Data_Store $data_store = null;
	private ?Meta_Hijack $meta_hijack = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->data_store  = new Data_Store();
		$this->meta_hijack = new Meta_Hijack( $this->data_store );
		$this->booted      = true;
	}
}
