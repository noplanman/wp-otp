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
	public function login_form_render(): void {
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
			<input type="text" class="input" name="wp_otp_code" id="wp_otp_code"/>
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

		$user_meta_data = Wp_Otp_User_Meta::get_instance( $user->ID );

		$otp = $this->get_otp_if_enabled( $user_meta_data );
		if ( null === $otp ) {
			return $user;
		}
		$otp_code = $_POST['wp_otp_code'] ?? '';

		// If this is a valid OTP code, all good!
		if ( $this->verify_otp( $otp, $otp_code ) ) {
			return $user;
		}

		// Check if a recovery code is being used.
		$recovery_codes = $user_meta_data->get( 'recovery_codes' );
		if ( array_key_exists( $otp_code, array_filter( $recovery_codes ) ) ) {
			// Unset the recovery code that has just been used.
			$recovery_codes[ $otp_code ] = false;
			$user_meta_data->set( 'recovery_codes', $recovery_codes, true );
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

		return new WP_Error( 'invalid_otp', $otp_invalid_code_text );
	}

	/**
	 * Validation of the user login, to check if the stealth OTP was correct.
	 *
	 * @param string $username The username that's trying to log in.
	 * @param string $password The password being used to log in.
	 *
	 * @return void
	 */
	public function login_form_stealth_validate( &$username, &$password ): void {
		$user = get_user_by( 'login', $username );
		if ( ! $user ) {
			return;
		}

		$user_meta_data = Wp_Otp_User_Meta::get_instance( $user->ID );

		$otp = $this->get_otp_if_enabled( $user_meta_data );
		if ( null === $otp ) {
			return;
		}

		// Check if no OTP code has been added.
		if ( wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			// To prevent a login without OTP, modify password.
			$password .= '_nootp';
			return;
		}

		// First let's check for a valid OTP code input.
		$otp_code = substr( $password, - 6 );
		$tmp_pass = substr( $password, 0, - 6 );
		if ( wp_check_password( $tmp_pass, $user->user_pass, $user->ID ) && $this->verify_otp( $otp, $otp_code ) ) {
			$password = $tmp_pass;
			return;
		}

		// Then check if it's a recovery code.
		$recovery_codes = $user_meta_data->get( 'recovery_codes' );
		foreach ( array_keys( array_filter( $recovery_codes ) ) as $recovery_code ) {
			$otp_code = substr( $password, - strlen( $recovery_code ) );
			if ( $otp_code !== $recovery_code ) {
				continue;
			}

			$tmp_pass = substr( $password, 0, - strlen( $recovery_code ) );
			if ( wp_check_password( $tmp_pass, $user->user_pass, $user->ID ) ) {
				// Unset the recovery code that has just been used.
				$recovery_codes[ $otp_code ] = false;
				$user_meta_data->set( 'recovery_codes', $recovery_codes, true );
				$password = $tmp_pass;
				return;
			}
		}
	}

	/**
	 * Get the TOTP object if applicable for this user.
	 *
	 * @since 0.3.0
	 *
	 * @param Wp_Otp_User_Meta $user_meta_data
	 *
	 * @return TOTP
	 */
	private function get_otp_if_enabled( $user_meta_data ): TOTP {
		if ( $user_meta_data->get( 'enabled' ) && null !== $user_meta_data->get( 'secret' ) ) {
			return TOTP::create( $user_meta_data->get( 'secret' ) );
		}

		return null;
	}

	/**
	 * Verify the OTP code using the passed TOTP object.
	 *
	 * @since 0.3.0
	 *
	 * @param TOTP   $otp
	 * @param string $otp_code
	 *
	 * @return bool
	 */
	private function verify_otp( $otp, $otp_code ): bool {
		/**
		 * Filter for the OTP code expiration window.
		 *
		 * @since 0.1.0
		 *
		 * @param string $otp_window
		 */
		$otp_window = (int) apply_filters( 'wp_otp_code_expiration_window', 2 );

		return $otp->verify( $otp_code, null, $otp_window );
	}
}
