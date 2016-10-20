<?php
/**
 * Define the internationalisation functionality
 *
 * Loads and defines the internationalisation files for this plugin
 * so that it is ready for translation.
 *
 * @package    Wp_Otp
 * @subpackage Internationalisation
 * @since      0.1.0
 */

namespace Wp_Otp;

/**
 * Define the internationalisation functionality.
 *
 * Loads and defines the internationalisation files for this plugin so that it is ready for translation.
 *
 * @since 0.1.0
 */
class Wp_Otp_i18n {
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 0.1.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			WP_OTP_SLUG,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
