<?php
/**
 * Plugin Name:       WP-OTP
 * Plugin URI:        https://wordpress.org/plugins/wp-otp/
 * Description:       WP-OTP adds 2 Factor Authentication using TOTP. (Based on "WP Secure Login" by Brijesh Kothari)
 * Version:           0.5.1
 * Author:            Armando Lüscher
 * Author URI:        https://noplanman.ch
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * GitLab Plugin URI: https://git.feneas.org/noplanman/wp-otp
 * GitLab Branch:     master
 *
 * @package Wp_Otp
 * @since   0.1.0
 */

namespace Wp_Otp;

defined( 'WPINC' ) || exit;

// Define constants.
define( 'WP_OTP_SLUG', 'wp-otp' );
define( 'WP_OTP_VERSION', '0.5.1' );

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/class-wp-otp.php';

register_activation_hook( __FILE__, [ Wp_Otp_Setup::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Wp_Otp_Setup::class, 'deactivate' ] );

( new Wp_Otp() )->run();
