<?php
/**
 * The core functionality of the plugin
 *
 * @package Wp_Otp
 * @since   0.1.0
 */

namespace Wp_Otp;

/**
 * The core plugin class.
 *
 * @since 0.1.0
 */
class Wp_Otp {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power the plugin.
	 *
	 * @since  0.1.0
	 * @access private
	 * @var    Wp_Otp_Loader $loader
	 */
	private Wp_Otp_Loader $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->define_constants();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since  0.1.0
	 * @access private
	 */
	private function load_dependencies(): void {
		/**
		 * The class responsible for activation, deactivation and deletion of the plugin.
		 */
		require_once __DIR__ . '/class-wp-otp-setup.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once __DIR__ . '/class-wp-otp-loader.php';

		/**
		 * The class responsible for managing all user meta data.
		 */
		require_once __DIR__ . '/class-wp-otp-user-meta.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once dirname( __DIR__ ) . '/admin/class-wp-otp-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site.
		 */
		require_once dirname( __DIR__ ) . '/public/class-wp-otp-public.php';

		$this->loader = new Wp_Otp_Loader();
	}

	/**
	 * Define the required constants for this plugin.
	 *
	 * @since  0.3.0
	 * @access private
	 */
	private function define_constants(): void {
		defined( 'WP_OTP_STEALTH' ) || define( 'WP_OTP_STEALTH', false );
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since  0.1.0
	 * @access private
	 */
	private function define_admin_hooks(): void {
		$plugin_admin = new Wp_Otp_Admin();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_init', $plugin_admin );
		$this->loader->add_action( 'admin_notices', $plugin_admin );

		$this->loader->add_action( 'profile_personal_options', $plugin_admin, 'user_profile_render' );
		$this->loader->add_action( 'personal_options_update', $plugin_admin, 'user_profile_updated' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 *
	 * @since  0.1.0
	 * @access private
	 */
	private function define_public_hooks(): void {
		$plugin_public = new Wp_Otp_Public();

		if ( WP_OTP_STEALTH ) {
			$this->loader->add_action( 'wp_authenticate', $plugin_public, 'login_form_stealth_validate', 33, 2 );
			return;
		}

		$this->loader->add_action( 'login_form', $plugin_public, 'login_form_render' );
		$this->loader->add_action( 'authenticate', $plugin_public, 'login_form_validate', 33 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 0.1.0
	 */
	public function run(): void {
		$this->loader->run();
	}
}
