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

		$this->services['data_store']            = new Data_Store();
		$this->services['ai_endpoints_settings'] = new AI_Endpoints_Settings();
		$this->services['ai_endpoints_admin']    = new AI_Endpoints_Admin( $this->services['ai_endpoints_settings'] );
		$this->services['ai_endpoints_renderer'] = new AI_Endpoints_Renderer( $this->services['ai_endpoints_settings'] );
		$this->services['ai_endpoints_routes']   = new AI_Endpoints_Routes(
			$this->services['ai_endpoints_settings'],
			$this->services['ai_endpoints_renderer']
		);
		$this->services['meta_hijack']           = new Meta_Hijack( $this->services['data_store'] );
		$this->services['frontend']              = new Frontend( $this->services['data_store'] );
		$this->services['site_settings']         = Site_Settings::instance();
		$this->services['site_settings_admin']   = new Site_Settings_Admin( $this->services['site_settings'] );
		$this->services['admin_assets']          = new Admin_Assets();
		$this->booted                            = true;
	}

	public function has_service( string $key ): bool {
		return isset( $this->services[ $key ] );
	}

	public function service( string $key ): object {
		if ( ! isset( $this->services[ $key ] ) ) {
			throw new \RuntimeException( "LeanSEO: Service '{$key}' not found." );
		}

		return $this->services[ $key ];
	}
}
