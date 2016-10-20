<?php
/**
 * The admin-specific functionality of the plugin
 *
 * @package    Wp_Otp
 * @subpackage Admin
 * @since      0.1.0
 */

namespace Wp_Otp;

use OTPHP\TOTP;
use WP_User;

/**
 * The admin-specific functionality of the plugin.
 *
 * @since 0.1.0
 */
class Wp_Otp_Admin {
	/**
	 * Check and save the OTP data when saving the user profile.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function user_profile_updated( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		$user = get_userdata( $user_id );

		$user_meta_data = Wp_Otp_User_Meta::get_instance();

		$otp = new TOTP(
			$user->user_login,
			$user_meta_data->get_user_meta( 'secret' )
		);
		$otp->setIssuer( get_option( 'blogname' ) );
		$secret = $otp->getSecret();

		$user_meta_data->set_user_meta( 'secret', $secret );

		$otp_code = trim( $_POST['wp_otp_code'] );
		if ( $otp_code && ! $user_meta_data->get_user_meta( 'enabled', false ) ) {
			$otp_window = (int) apply_filters( 'wp_otp_code_expiration_window', 2 );

			$verification = $otp->verify( $otp_code, null, $otp_window );

			if ( $verification ) {
				$otp_recovery = bin2hex( random_bytes( 8 ) );
				$user_meta_data->set_user_metas( [
					'enabled'  => true,
					'recovery' => $otp_recovery,
					'notice'   => [
						'type'     => 'success',
						'messages' => [
							'<strong>' . __( 'WP-OTP configured successfully!', 'wp-otp' ) . '</strong>',
							__( 'If you change your phone or do not have access to the OTP Authenticator app you can use the following key as a One Time Password on your login screen and then reconfigure WP OTP. Never share this key with anyone!',
								'wp-otp' ),
							$otp_recovery,
						],
					],
				] );
			} else {
				Wp_Otp_User_Meta::delete();
				$user_meta_data->set_user_metas( [
					'secret' => $secret,
					'notice' => [
						'type'     => 'error',
						'messages' => [
							'<strong>' . __( 'WP-OTP configuration failed.', 'wp-otp' ) . '</strong>',
							__( 'The One Time Password entered was invalid! Please try again.', 'wp-otp' ),
						],
					],
				] );
			}

			$user_meta_data->save();
		}
	}

	/**
	 * Check if the OTP is being deleted and reconfigured.
	 *
	 * @since 0.1.0
	 */
	public function admin_init() {
		if ( isset( $_GET['wp-otp-delete'] ) && 'yes' === $_GET['wp-otp-delete'] ) {
			Wp_Otp_User_Meta::delete();
			wp_redirect( get_edit_profile_url() . '#wp_otp' );
			exit;
		}
	}

	/**
	 * Render the WP-OTP section on the user's profile edit screen.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_User $user
	 */
	public function user_profile_render( $user ) {
		$user_meta_data = Wp_Otp_User_Meta::get_instance();

		// Get the secret.
		$secret = $user_meta_data->get_user_meta( 'secret' );

		$otp = new TOTP( $user->user_login, $secret );

		// Check if the secret was loaded from the meta or not.
		if ( null === $secret ) {
			$secret = $otp->getSecret();
			$user_meta_data->set_user_meta( 'secret', $secret, true );
		}

		/**
		 * Filter for the OTP QR code provisioning URI.
		 *
		 * Set a custom QR code provisioning URI which has a data placeholder of {PROVISIONING_URI}.
		 *
		 * @since 0.1.0
		 *
		 * @param string $otp_window
		 */
		$otp_qr_code_uri = $otp->getQrCodeUri( apply_filters(
			'wp_otp_qr_code_provisioning_uri',
			'https://api.qrserver.com/v1/create-qr-code/?data={PROVISIONING_URI}&qzone=2&size=300x300'
		) );

		$otp_enabled = $user_meta_data->get_user_meta( 'enabled', false );

		$otp_apps = [
			[
				'name'           => 'FreeOTP',
				'uri'            => 'https://fedorahosted.org/freeotp/',
				'uri_app_store'  => 'https://itunes.apple.com/us/app/freeotp-authenticator/id872559395',
				'uri_play_store' => 'https://play.google.com/store/apps/details?id=org.fedorahosted.freeotp',
				'uri_f_droid'    => 'https://f-droid.org/repository/browse/?fdid=org.fedorahosted.freeotp',
				'uri_logo'       => plugins_url( 'images/freeotp.png', __FILE__ ),
			],
			[
				'name'           => 'Google Authenticator',
				'uri'            => 'https://github.com/google/google-authenticator/',
				'uri_app_store'  => 'https://itunes.apple.com/us/app/google-authenticator/id388497605',
				'uri_play_store' => 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2',
				'uri_f_droid'    => 'https://f-droid.org/repository/browse/?fdid=com.google.android.apps.authenticator2',
				'uri_logo'       => plugins_url( 'images/google-authenticator.png', __FILE__ ),
			],
		];

		$app_providers = [
			'f_droid'    => [ 'name' => 'F-Droid', 'uri_logo' => plugins_url( 'images/f-droid.png', __FILE__ ), ],
			'play_store' => [ 'name' => 'Play Store', 'uri_logo' => plugins_url( 'images/play-store.png', __FILE__ ), ],
			'app_store'  => [ 'name' => 'App Store', 'uri_logo' => plugins_url( 'images/app-store.png', __FILE__ ), ],
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
	public function show_user_notification( array $messages, $type = 'notice' ) {
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
			<p><?php echo implode( '<br>', $messages ); ?></p>
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
	public function admin_notices() {
		$user_meta_data = Wp_Otp_User_Meta::get_instance();

		if ( ! $user_meta_data->get_user_meta( 'enabled', false ) ) {
			$this->show_user_notification( [
				__( '<strong>Note:</strong> You have not yet configured WP-OTP.', 'wp-otp' ),
				sprintf(
					'<a href="%1$s#wp_otp">%2$s</a>',
					get_edit_profile_url(),
					_x( 'Configure now', 'Link text to go to WP-OTP section in user profile', 'wp-otp' )
				),
			] );
		} elseif ( null === $user_meta_data->get_user_meta( 'recovery' ) ) {
			$this->show_user_notification( [
				__( '<strong>Important:</strong> You have used your WP-OTP recovery hash. You must generate a new one.',
					'wp-otp' ),
				sprintf(
					'<a href="%1$s#wp_otp">%2$s</a>',
					get_edit_profile_url(),
					_x( 'Configure now', 'Link text to go to WP-OTP section in user profile', 'wp-otp' )
				),
			], 'error' );
		}

		if ( $notice = $user_meta_data->get_user_meta( 'notice' ) ) {
			$this->show_user_notification(
				(array) $notice['messages'],
				$notice['type']
			);

			// Remove any notices from the user meta.
			$user_meta_data->set_user_meta( 'notice', null, true );
		}
	}
}
