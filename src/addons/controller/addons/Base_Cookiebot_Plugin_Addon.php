<?php

namespace cybot\cookiebot\addons\controller\addons;

use cybot\cookiebot\addons\lib\buffer\Buffer_Output_Interface;
use cybot\cookiebot\addons\lib\Cookie_Consent_Interface;
use cybot\cookiebot\addons\lib\script_loader_tag\Script_Loader_Tag_Interface;
use cybot\cookiebot\addons\lib\Settings_Service_Interface;
use Exception;

abstract class Base_Cookiebot_Plugin_Addon extends Base_Cookiebot_Addon {

	const PLUGIN_FILE_PATH = '';

	/**
	 * @param $settings          Settings_Service_Interface
	 * @param $script_loader_tag Script_Loader_Tag_Interface
	 * @param $cookie_consent    Cookie_Consent_Interface
	 * @param $buffer_output     Buffer_Output_Interface
	 *
	 * @throws Exception
	 * @since 1.3.0
	 */
	public function __construct(
		Settings_Service_Interface $settings,
		Script_Loader_Tag_Interface $script_loader_tag,
		Cookie_Consent_Interface $cookie_consent,
		Buffer_Output_Interface $buffer_output
	) {
		parent::__construct( $settings, $script_loader_tag, $cookie_consent, $buffer_output );
		$this->validate_required_string_class_constant( 'PLUGIN_FILE_PATH' );
	}

	/**
	 * @return bool
	 */
	final public function is_addon_installed() {
		$path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . static::PLUGIN_FILE_PATH;
		return ( file_exists( $path ) && ! is_wp_error( validate_plugin( static::PLUGIN_FILE_PATH ) ) );
	}

	/**
	 * Checks if addon plugin is activated
	 *
	 * @since 1.3.0
	 */
	final public function is_addon_activated() {
		return $this->is_addon_installed() && is_plugin_active( static::PLUGIN_FILE_PATH );
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	final public function get_version() {
		$plugin_data = $this->get_plugin_data();
		if ( ! isset( $plugin_data['Version'] ) ) {
			throw new Exception( 'Check if plugin is installed before calling get_version()' );
		}
		return $plugin_data['Version'];
	}

	/**
	 * @return string[]
	 */
	final private function get_plugin_data() {
		return get_file_data(
			WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . static::PLUGIN_FILE_PATH,
			array( 'Version' => 'version' ),
			false
		);
	}
}
