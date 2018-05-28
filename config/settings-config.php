<?php

namespace cookiebot_addons_framework\config;

use cookiebot_addons_framework\lib\Settings_Service_Interface;

class Settings_Config {

	/**
	 * @var Settings_Service_Interface
	 */
	protected $settings_service;

	public function __construct( Settings_Service_Interface $settings_service ) {
		$this->settings_service = $settings_service;
	}

	public function load() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_wp_admin_style' ) );
	}

	public function add_submenu() {
		add_options_page( 'Cookiebot Addons', __( 'Cookiebot Addons', 'cookiebot-addons' ), 'manage_options', 'cookiebot-addons', array(
			$this,
			'setting_page'
		) );
	}

	/**
	 * TODO Refactor use Settings_Service_Interface to load checkbox fields
	 */
	public function register_settings() {
		if ( 'not_installed_plugins' === $_GET['tab'] ) {
			add_settings_section( "not_installed_plugins", "Unavailable plugins", array(
				$this,
				"header_uninstalled_plugins"
			), "cookiebot-addons" );
		} else {
			add_settings_section( "installed_plugins", "Available plugins", array(
				$this,
				"header_installed_plugins"
			), "cookiebot-addons" );
		}

		foreach ( $this->settings_service->get_addon_by_generator() as $addon ) {
			if ( $addon->is_addon_enabled() && $addon->is_addon_installed() && 'not_installed_plugins' !== $_GET['tab'] ) {
				add_settings_field( "addon_enabled", $addon->get_addon_name(), array(
					$this,
					"available_addon_callback"
				), "cookiebot-addons", "installed_plugins", array( 'addon' => $addon ) );
				register_setting( "installed_plugins", "addon_enabled" );
			} else if ( 'not_installed_plugins' === $_GET['tab'] && ( ! $addon->is_addon_enabled() || ! $addon->is_addon_installed() ) ) {
				// not installed plugins
				add_settings_field( "uninstalled_" . $addon->get_addon_name(), $addon->get_addon_name(), array(
					$this,
					"unavailable_addon_callback"
				), "cookiebot-addons", "not_installed_plugins", array( 'addon' => $addon ) );
				register_setting( "not_installed_plugins", "background_picture", "handle_file_upload" );
			}
		}
	}

	/**
	 * Load css styling to the settings page
	 *
	 * @since 1.3.0
	 */
	public function add_wp_admin_style( $hook ) {
		if ( $hook != 'settings_page_cookiebot-addons' ) {
			return;
		}

		wp_enqueue_style( 'cookiebot_addons_custom_css', plugins_url( 'style/css/admin_styles.css', dirname( __FILE__ ) ) );
	}

	public function header_installed_plugins() {
		?>
        <p>
			<?php _e( 'Below is a list of addons for Cookiebot. Addons help you making contributed plugins GDPR compliant.', 'cookiebot-addons' ); ?>
            <br/>
			<?php _e( 'Deactive addons if you want to handle GDPR compliance yourself or using another plugin.', 'cookiebot-addons' ); ?>
        </p>
		<?php
	}

	public function header_uninstalled_plugins() {
		?>
        <p>
			<?php _e( 'Following addons are unavailable. This is usual because the addon is not useable because the main plugin is not activated or installed.' ); ?>
        </p>
		<?php
	}

	public function unavailable_addon_callback( $args ) {
		$addon = $args['addon'];

		?>
        <div class="postbox cookiebot-addon">
            <i><?php _e( 'Unavailable', 'cookiebot-addons' ); ?></i>
        </div>
		<?php
	}

	public function available_addon_callback( $args ) {
		$addon = $args['addon'];

		?>
        <div class="postbox cookiebot-addon">
            <i><?php _e( 'Available', 'cookiebot-addons' ); ?></i>
        </div>
		<?php
	}


	/**
	 * TODO This will be used for settings through Settings_Service_Interface
	 *
	 * @param string $active_tab
	 */
	public function setting_page( $active_tab = '' ) {
		?>
        <!-- Create a header in the default WordPress 'wrap' container -->
        <div class="wrap">

            <div id="icon-themes" class="icon32"></div>
            <h2>Cookiebot addons</h2>
			<?php settings_errors(); ?>

			<?php if ( isset( $_GET['tab'] ) ) {
				$active_tab = $_GET['tab'];
			} else if ( $active_tab == 'not_installed_plugins' ) {
				$active_tab = 'not_installed_plugins';
			} else {
				$active_tab = 'installed_plugins';
			} // end if/else ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=cookiebot-addons&tab=installed_plugins"
                   class="nav-tab <?php echo $active_tab == 'installed_plugins' ? 'nav-tab-active' : ''; ?>">Installed
                    plugins</a>
                <a href="?page=cookiebot-addons&tab=not_installed_plugins"
                   class="nav-tab <?php echo $active_tab == 'not_installed_plugins' ? 'nav-tab-active' : ''; ?>">Not
                    installed plugins</a>
            </h2>

            <form method="post" action="options.php">
				<?php

				if ( $active_tab == 'installed_plugins' ) {
					settings_fields( 'cookiebot_installed_options' );
					do_settings_sections( 'cookiebot-addons' );
				} else {
					settings_fields( 'cookiebot_not_installed_options' );
					do_settings_sections( 'cookiebot-addons' );
				} // end if/else

				submit_button();

				?>
            </form>

        </div><!-- /.wrap -->
		<?php
	}

	/**
	 * Settign page for Cookiebot addons
	 *
	 * @since 1.2.0
	 */
	function setting_page123() {
		if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'deactivate' || $_GET['action'] == 'activate' ) ) {
			$active = ( $_GET['action'] == 'activate' ) ? 'yes' : 'no';
			update_option( 'cookiebot-addons-active-' . sanitize_key( $_GET['addon'] ), $active );


			$status = ( $active == 'yes' ) ? 'The addon is now activated' : 'The addon is now deactivated';
			?>
            <div class="updated notice is-dismissible">
                <p><?php _e( $status, 'cookiebot-addons' ); ?></p>
            </div>
			<?php
		}

		$addons = $this->settings_service->get_addon_list();

		?>
        <div class="wrap">
            <h1><?php _e( 'Cookiebot Addons', 'cookiebot-addons' ); ?></h1>
            <p>
				<?php _e( 'Below is a list of addons for Cookiebot. Addons help you making contributed plugins GDPR compliant.', 'cookiebot-addons' ); ?>
                <br/>
				<?php _e( 'Deactive addons if you want to handle GDPR compliance yourself or using another plugin.', 'cookiebot-addons' ); ?>
            </p>
			<?php

			foreach ( $addons['available'] as $plugin_class => $plugin ) {
				?>
                <div class="postbox cookiebot-addon">
                    <h2><?php echo $plugin['name']; ?></h2>
					<?php
					if ( get_option( 'cookiebot-addons-active-' . sanitize_key( $plugin_class ), 'yes' ) == 'yes' ) {
						?>
                        <a href="<?php echo admin_url( 'options-general.php?page=cookiebot-addons&action=deactivate&addon=' . $plugin_class ); ?>">
							<?php _e( 'Deactivate addon', 'cookiebot-addons' ); ?>
                        </a>
						<?php
					} else {
						?>
                        <a href="<?php echo admin_url( 'options-general.php?page=cookiebot-addons&action=activate&addon=' . $plugin_class ); ?>">
							<?php _e( 'Activate addon', 'cookiebot-addons' ); ?>
                        </a>
						<?php
					}
					?>
                </div>
				<?php
			}
			?>
            <h2><?php _e( 'Unavailable Addons', 'cookiebot-addons' ); ?></h2>
            <p>
				<?php _e( 'Following addons are unavailable. This is usual because the addon is not useable because the main plugin is not activated.' ); ?>
            </p>
			<?php
			foreach ( $addons['unavailable'] as $plugin_class => $plugin ) {
				?>
                <div class="postbox cookiebot-addon">
                    <h2><?php echo $plugin['name']; ?></h2>
                    <i><?php _e( 'Unavailable', 'cookiebot-addons' ); ?></i>
                </div>
				<?php
			}
			?>
        </div>
		<?php
	}
}