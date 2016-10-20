<?php
/**
 * Uninstall the plugin and clean up.
 *
 * @since   0.1.0
 *
 * @package Wp_Otp
 */

namespace Wp_Otp;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Require the class that manages the uninstall process.
 */
require_once __DIR__ . '/includes/class-wp-otp-setup.php';

Wp_Otp_Setup::uninstall( __FILE__ );
