<?php
/**
 * The admin-specific functionality of the plugin
 *
 * @package    Wp_Otp
 * @subpackage Admin
 * @since      0.1.0
 */

namespace Wp_Otp;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Throwable;
use WP_User;

/**
 * The admin-specific functionality of the plugin.
 *
 * @since 0.1.0
 */
class Wp_Otp_Admin {

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Page on which this hook is called.
	 */
	public function enqueue_styles( string $hook ): void {
		if ( 'profile.php' === $hook ) {
			wp_enqueue_style( WP_OTP_SLUG . '-admin', plugin_dir_url( __FILE__ ) . 'css/wp-otp-admin.css', [], WP_OTP_VERSION );
		}
	}

	/**
	 * Register the scripts for the admin area.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Page on which this hook is called.
	 */
	public function enqueue_scripts( string $hook ): void {
		if ( 'profile.php' === $hook ) {
			$handle = WP_OTP_SLUG . '-admin';

			wp_enqueue_script( $handle, plugin_dir_url( __FILE__ ) . 'js/wp-otp-admin.js', [ 'jquery' ], WP_OTP_VERSION, true );
			wp_localize_script( $handle, 'wp_otp', [
				'confirm_reconfigure'        => __( 'Are you sure you want to reconfigure WP-OTP?', 'wp-otp' ),
				'confirm_new_recovery_codes' => __( 'Are you sure you want to regenerate your recovery codes?', 'wp-otp' ),
			] );
		}
	}

	/**
	 * Check and save the OTP data when saving the user profile.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id WordPress User ID.
	 *
	 * @return void
	 */
	public function user_profile_updated( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		check_admin_referer( 'wp_otp_nonce', 'wp_otp_nonce' );

		$user = get_userdata( $user_id );

		$user_meta_data = Wp_Otp_User_Meta::get_instance();

		// Get the secret.
		$secret = $user_meta_data->get( 'secret', $this->get_random_secret() );

		$otp = TOTP::create( $secret );
		$otp->setLabel( $user->user_login );

		$otp_code = sanitize_key( $_POST['wp-otp-code'] ?? '' );
		if ( $otp_code && ! $user_meta_data->get( 'enabled', false ) ) {
			/** Filter documented in class-wp-otp-public.php */
			$otp_window = (int) apply_filters( 'wp_otp_code_expiration_window', 2 );

			if ( $otp->verify( $otp_code, null, $otp_window ) ) {
				$otp_recovery_codes = $this->get_random_recovery_codes();
				$user_meta_data->set_all( [
					'enabled'        => true,
					'recovery_codes' => $otp_recovery_codes,
					'notice'         => [
						'type'     => 'success',
						'messages' => [
							'<strong>' . __( 'WP-OTP configured successfully!', 'wp-otp' ) . '</strong>',
							__( 'If you change your phone or do not have access to the OTP Authenticator app you can use the following codes as One Time Passwords on your login screen and then reconfigure WP-OTP.', 'wp-otp' ),
							'<br>' . __( 'Keep these codes secret!', 'wp-otp' ),
							implode( '<br>', array_keys( $otp_recovery_codes ) ),
						],
					],
				] );
			} else {
				Wp_Otp_User_Meta::clear();
				$user_meta_data->set_all( [
					'notice' => [
						'type'     => 'error',
						'messages' => [
							'<strong>' . __( 'WP-OTP configuration failed.', 'wp-otp' ) . '</strong>',
							__( 'The One Time Password entered was invalid! Please try again.', 'wp-otp' ),
						],
					],
				] );
			}

			$user_meta_data->set( 'secret', $secret, true );
		}
	}

	/**
	 * Check if the OTP is being deleted and reconfigured.
	 *
	 * @since 0.1.0
	 */
	public function admin_init(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'yes' === ( sanitize_key( $_GET['wp-otp-reconfigure'] ?? '' ) ) ) {
			Wp_Otp_User_Meta::clear();
			wp_safe_redirect( get_edit_profile_url() . '#wp-otp' );
			exit;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'yes' === ( sanitize_key( $_GET['wp-otp-new-recovery-codes'] ?? '' ) ) ) {
			$otp_recovery_codes = $this->get_random_recovery_codes();
			Wp_Otp_User_Meta::get_instance()->set_all( [
				'recovery_codes' => $otp_recovery_codes,
				'notice'         => [
					'type'     => 'success',
					'messages' => [
						'<strong>' . __( 'WP-OTP recovery codes regenerated!', 'wp-otp' ) . '</strong>',
						__( 'Here are your new recovery codes.', 'wp-otp' ),
						'<br>' . __( 'Keep these codes secret!', 'wp-otp' ),
						implode( '<br>', array_keys( $otp_recovery_codes ) ),
					],
				],
			], true );

			wp_safe_redirect( get_edit_profile_url() );
			exit;
		}
	}

	/**
	 * Get a set of random recovery codes.
	 *
	 * Returns an array in the format [ 'code_1' => true, ...,'code_n' => true ]
	 *
	 * @since 0.1.0
	 *
	 * @param null|int $codes_count_override  Override the filter and default for the codes count.
	 * @param null|int $codes_length_override Override the filter and default for the codes length.
	 *
	 * @return array
	 */
	public function get_random_recovery_codes( $codes_count_override = null, $codes_length_override = null ): array {
		/**
		 * Filter for the number of random recovery codes to generate (between 1 and 20).
		 *
		 * @since 0.1.0
		 *
		 * @param int $codes_count
		 */
		$codes_count = $codes_count_override ?: (int) apply_filters( 'wp_otp_recovery_codes_count', 5 );
		$codes_count = min( max( 1, $codes_count ), 20 );

		/**
		 * Filter for the length of the random recovery codes to generate (between 8 and 64).
		 *
		 * @since 0.1.0
		 *
		 * @param int $codes_length
		 */
		$codes_length = $codes_length_override ?: (int) apply_filters( 'wp_otp_recovery_codes_length', 16 );
		$codes_length = min( max( 8, $codes_length ), 64 );

		$codes = [];
		// phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
		while ( count( $codes ) < $codes_count ) {
			$code = $this->get_random_hash( $codes_length );
			if ( ! array_key_exists( $code, $codes ) ) {
				$codes[ $code ] = true;
			}
		}

		return $codes;
	}

	/**
	 * Get a new random OTP secret.
	 *
	 * @since 0.1.0
	 *
	 * @param null|int $secret_length_override Override the filter and default for the codes count.
	 *
	 * @return string
	 */
	public function get_random_secret( $secret_length_override = null ): string {
		/**
		 * Filter for the length of the secret to be generated (between 8 and 64).
		 *
		 * @since 0.1.0
		 *
		 * @param int $secret_length
		 */
		$secret_length = $secret_length_override ?: (int) apply_filters( 'wp_otp_secret_length', 16 );
		$secret_length = min( max( 8, $secret_length ), 64 );

		return $this->get_random_hash( $secret_length );
	}

	/**
	 * Get a random hash string of up to 100 characters.
	 *
	 * @since 0.5.0
	 *
	 * @param int $length Length of the random hash (max 100).
	 *
	 * @return string
	 */
	public function get_random_hash( $length = 0 ): string {
		try {
			$random_hash = Base32::encode( random_bytes( 64 ) );
		} catch ( Throwable $e ) {
			$random_hash = Base32::encode( md5( microtime( true ) ) . md5( microtime( true ) ) );
		}

		if ( $length <= 0 ) {
			return substr( $random_hash, 0, 100 );
		}

		return substr( $random_hash, 0, min( $length, 100 ) );
	}

	/**
	 * Render the WP-OTP section on the user's profile edit screen.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_User $user WordPress User Object.
	 */
	public function user_profile_render( WP_User $user ): void {
		$user_meta_data = Wp_Otp_User_Meta::get_instance();

		// Get and save the secret.
		$secret = $user_meta_data->get( 'secret', $this->get_random_secret() );
		$user_meta_data->set( 'secret', $secret, true );

		$otp = TOTP::create( $secret );
		$otp->setLabel( $user->user_login );

		// Issuer isn't allowed to have any colon.
		$otp->setIssuer( str_replace( [ ':', '%3a', '%3A' ], '', get_bloginfo( 'name' ) ) );

		$qr_code_provisioning_uri_default = 'https://api.qrserver.com/v1/create-qr-code/?data={PROVISIONING_URI}&qzone=2';
		/**
		 * Filter for the OTP QR code provisioning URI.
		 *
		 * Set a custom QR code provisioning URI which has a data placeholder of {PROVISIONING_URI}.
		 *
		 * @since 0.1.0
		 *
		 * @param string $qr_code_provisioning_uri
		 */
		$qr_code_provisioning_uri = apply_filters( 'wp_otp_qr_code_provisioning_uri', $qr_code_provisioning_uri_default );

		// If no custom provisioning URI is set, opt for internal QR code processing, if possible.
		if ( $qr_code_provisioning_uri === $qr_code_provisioning_uri_default ) {
			try {
				$qr_code_options     = new QROptions( [ 'quietzoneSize' => 2 ] );
				$qr_code             = new QRCode( $qr_code_options );
				$otp_qr_code_raw_uri = $otp->getProvisioningUri();
				$otp_qr_code_img_uri = $qr_code->render( $otp_qr_code_raw_uri );
			} catch ( Throwable $e ) {
				$otp_qr_code_img_uri = null;
			}
		}

		if ( ! isset( $otp_qr_code_img_uri ) ) {
			$otp_qr_code_img_uri = $otp->getQrCodeUri( $qr_code_provisioning_uri, '{PROVISIONING_URI}' );
		}

		$otp_enabled = $user_meta_data->get( 'enabled' );

		$otp_apps = [
			[
				'name'           => 'Aegis Authenticator',
				'uri'            => 'https://getaegis.app/',
				'uri_play_store' => 'https://play.google.com/store/apps/details?id=com.beemdevelopment.aegis',
				'uri_f_droid'    => 'https://f-droid.org/en/packages/com.beemdevelopment.aegis',
				'uri_logo'       => plugins_url( 'images/aegis.png', __FILE__ ),
			],
			[
				'name'           => 'andOTP',
				'uri'            => 'https://github.com/andOTP/andOTP',
				'uri_play_store' => 'https://play.google.com/store/apps/details?id=org.shadowice.flocke.andotp',
				'uri_f_droid'    => 'https://f-droid.org/packages/org.shadowice.flocke.andotp',
				'uri_logo'       => plugins_url( 'images/andotp.png', __FILE__ ),
			],
			[
				'name'        => 'OneTimePass',
				'uri'         => 'https://github.com/OneTimePass/OneTimePass',
				'uri_f_droid' => 'https://f-droid.org/en/packages/com.github.onetimepass',
				'uri_logo'    => plugins_url( 'images/onetimepass.png', __FILE__ ),
			],
			[
				'name'           => 'FreeOTP+',
				'uri'            => 'https://github.com/helloworld1/FreeOTPPlus',
				'uri_play_store' => 'https://play.google.com/store/apps/details?id=org.liberty.android.freeotpplus',
				'uri_f_droid'    => 'https://f-droid.org/en/packages/org.liberty.android.freeotpplus',
				'uri_logo'       => plugins_url( 'images/freeotpplus.png', __FILE__ ),
			],
			[
				'name'           => 'OTP Authenticator',
				'uri'            => 'https://www.swiss-safelab.com/en-us/products/otpauthenticator.aspx',
				'uri_app_store'  => 'https://itunes.apple.com/us/app/otp-authenticator/id915359210',
				'uri_play_store' => 'https://www.swiss-safelab.com/de-de/community/downloadcenter.aspx?Command=Core_Download&EntryId=684',
				'uri_logo'       => plugins_url( 'images/otp-authenticator.png', __FILE__ ),
			],
		];

		$app_providers = [
			'f_droid'    => [
				'name'     => 'F-Droid',
				'uri_logo' => plugins_url( 'images/f-droid.png', __FILE__ ),
			],
			'play_store' => [
				'name'     => 'Play Store',
				'uri_logo' => plugins_url( 'images/play-store.png', __FILE__ ),
			],
			'app_store'  => [
				'name'     => 'App Store',
				'uri_logo' => plugins_url( 'images/app-store.png', __FILE__ ),
			],
		];

		include __DIR__ . '/partials/wp-otp-profile-display.php';
	}

	/**
	 * Show the user a notification.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $messages List of messages to be displayed.
	 * @param string $type     Type of notification to show (notice (default), success, error).
	 *
	 * @return void
	 */
	public function show_user_notification( array $messages, $type = 'notice' ): void {
		if ( empty( $messages ) ) {
			return;
		}

		$classes = [
			'notice'  => 'update-nag',
			'success' => 'updated',
			'error'   => 'error',
		];
		$class   = $classes[ array_key_exists( $type, $classes ) ? $type : 'notice' ];
		?>
		<div id="message" class="<?php echo esc_attr( $class ); ?>">
			<p><?php echo implode( '<br>', $messages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		</div>
		<?php
	}

	/**
	 * Display any saved admin notices.
	 *
	 * These notices are saved to the user meta and get cleared after showing.
	 *
	 * @since 0.1.0
	 */
	public function admin_notices(): void {
		$user_meta_data = Wp_Otp_User_Meta::get_instance();

		if ( $user_meta_data->get( 'enabled' ) ) {
			$recovery_codes       = array_filter( $user_meta_data->get( 'recovery_codes' ) );
			$recovery_codes_count = count( $recovery_codes );
			if ( $recovery_codes_count < 3 ) {
				$this->show_user_notification( [
					'<strong>' . esc_html__( 'Important', 'wp-otp' ) . '</strong>',
					sprintf(
						_n( // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
							'You have %d WP-OTP recovery code left. You should generate new ones.',
							'You have %d WP-OTP recovery codes left. You should generate new ones.',
							$recovery_codes_count,
							'wp-otp'
						),
						$recovery_codes_count
					),
					sprintf(
						'<a href="%1$s" class="button">%2$s</a>',
						esc_url( add_query_arg( 'wp-otp-new-recovery-codes', 'yes', get_edit_profile_url() ) ),
						esc_html_x( 'Regenerate', 'Link to regenerate the WP-OTP recovery codes', 'wp-otp' )
					),
				], 'error' );
			}
		}

		$notice = $user_meta_data->get( 'notice' );
		if ( $notice ) {
			$this->show_user_notification(
				(array) $notice['messages'],
				$notice['type']
			);

			// Remove any notices from the user meta.
			$user_meta_data->set( 'notice', null, true );
		}
	}
}
