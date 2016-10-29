<?php
/**
 * The public-facing functionality of the plugin
 *
 * This is basically the input field for the OTP code on the login form.
 *
 * @package    Wp_Otp
 * @subpackage Public
 * @since      0.1.0
 */

namespace Wp_Otp;

use OTPHP\TOTP;
use WP_Error;
use WP_User;

/**
 * The public-facing functionality of the plugin.
 *
 * @since 0.1.0
 */
class Wp_Otp_Public {
	/**
	 * Render the WP-OTP input field on the login form.
	 *
	 * @since 0.1.0
	 */
	public function login_form_render() {
		/**
		 * Filter for the OTP login form text.
		 *
		 * @since 0.1.0
		 *
		 * @param string $otp_text
		 */
		$otp_text = apply_filters(
			'wp_otp_login_form_text',
			__( 'One Time Password', 'wp-otp' )
		);

		/**
		 * Filter for the OTP login form sub text.
		 *
		 * @since 0.1.0
		 *
		 * @param string $otp_text_sub
		 */
		$otp_text_sub = apply_filters(
			'wp_otp_login_form_text_sub',
			__( 'OTP code from your authenticator app. (Blank if not yet configured)', 'wp-otp' )
		);
		?>
		<p>
			<label for="wp_otp_code"><?php echo $otp_text; ?></label><br/>
			<?php '' !== $otp_text_sub && printf( '<em>%s</em>', $otp_text_sub ); ?>
			<input type="password" class="input" name="wp_otp_code" id="wp_otp_code"/>
		</p>
		<?php
	}

	/**
	 * Validation of the user login, to check if the OTP was correct.
	 *
	 * @param WP_User $user The user that's trying to log in.
	 *
	 * @return WP_Error|WP_User
	 */
	public function login_form_validate( $user ) {
		if ( ! $user instanceof WP_User ) {
			return $user;
		}

		/**
		 * Filter for the OTP login form error text when an invalid code is entered.
		 *
		 * @since 0.1.0
		 *
		 * @param string $otp_invalid_code_text
		 */
		$otp_invalid_code_text = apply_filters(
			'wp_otp_login_form_invalid_code_text',
			__( '<strong>Invalid code!</strong> Please try again.', 'wp-otp' )
		);

		$user_meta_data = Wp_Otp_User_Meta::get_instance( $user->ID );

		if ( true === $user_meta_data->get( 'enabled' ) && null !== $user_meta_data->get( 'secret' ) ) {
			$otp_code = $_POST['wp_otp_code'];

			/**
			 * Filter for the OTP code expiration window.
			 *
			 * @since 0.1.0
			 *
			 * @param string $otp_window
			 */
			$otp_window = (int) apply_filters( 'wp_otp_code_expiration_window', 2 );

			$otp          = new TOTP( '', $user_meta_data->get( 'secret' ) );
			$verification = $otp->verify( $otp_code, null, $otp_window );

			if ( true !== $verification ) {
				if ( $otp_code === $user_meta_data->get( 'recovery' ) ) {
					$user_meta_data->set( 'recovery', null, true );
				} else {
					return new WP_Error( 'invalid_otp', $otp_invalid_code_text );
				}
			}
		}

		return $user;
	}
}
