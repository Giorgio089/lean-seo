<?php

namespace LeanSEO;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	private static ?self $instance = null;
	private bool $booted = false;
	private array $services = [];

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->services['data_store']   = new Data_Store();
		$this->services['meta_hijack']  = new Meta_Hijack( $this->services['data_store'] );
		$this->services['frontend']     = new Frontend( $this->services['data_store'] );
		$this->services['admin_assets'] = new Admin_Assets();
		$this->booted                   = true;
	}

	public function has_service( string $key ): bool {
		return isset( $this->services[ $key ] );
	}
}
