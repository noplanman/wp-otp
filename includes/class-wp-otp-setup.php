<?php
/**
 * Handle all activation, deactivation and uninstallation tasks
 *
 * @package    Wp_Otp
 * @subpackage Setup
 * @since      0.1.0
 */

namespace Wp_Otp;

defined( 'WPINC' ) || exit;

/**
 * Handle all activation, deactivation and uninstallation tasks.
 *
 * @since      0.1.0
 */
class Wp_Otp_Setup {
	/**
	 * Activation on a single or multisite network.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $network_wide TRUE if multisite/network and superadmin uses the "Network Activate" action.
	 *                           FALSE is no multisite install or plugin gets activated on a single blog.
	 */
	public static function activate( $network_wide ) {
		if ( $network_wide && is_multisite() ) {
			foreach ( get_sites() as $site ) {
				switch_to_blog( $site->blog_id );
				self::do_activation();
			}

			restore_current_blog();
		} else {
			self::do_activation();
		}
	}

	/**
	 * Deactivation on a single or multisite/network.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $network_wide TRUE if multisite/network and superadmin uses the "Network Deactivate" action.
	 *                           FALSE is no multisite install or plugin gets deactivated on a single blog.
	 */
	public static function deactivate( $network_wide ) {
		if ( $network_wide && is_multisite() ) {
			foreach ( get_sites() as $site ) {
				switch_to_blog( $site->blog_id );
				self::do_deactivation();
			}

			restore_current_blog();
		} else {
			self::do_deactivation();
		}
	}

	/**
	 * The actual tasks performed during activation of a plugin.
	 *
	 * Should handle only stuff that happens during a single site activation,
	 * as the process will repeated for each site on a multisite/network installation
	 * if the plugin is activated network wide.
	 *
	 * @since 0.1.0
	 */
	private static function do_activation() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "activate-plugin_{$plugin}" );
	}

	/**
	 * The actual tasks performed during deactivation of a plugin.
	 *
	 * Should handle only stuff that happens during a single site deactivation,
	 * as the process will repeated for each site on a multisite/network installation
	 * if the plugin is deactivated network wide.
	 *
	 * @since 0.1.0
	 */
	private static function do_deactivation() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "deactivate-plugin_{$plugin}" );
	}

	/**
	 * Clean up process when this plugin is deleted. (called from uninstall.php)
	 *
	 * @since 0.1.0
	 *
	 * @param string $file Path of uninstall.php.
	 */
	public static function uninstall( $file ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		check_admin_referer( 'bulk-plugins' );

		// Important: Check if the file is the one that was registered during the uninstall hook.
		if ( $file !== WP_UNINSTALL_PLUGIN ) {
			return;
		}
	}
}
