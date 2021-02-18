<?php
/**
 * Render the WP-OTP section in the user profile in WP Admin
 *
 * @package    Wp_Otp
 * @subpackage Admin
 * @since      0.1.0
 */

?>

<a id="wp-otp"></a>
<h2><?php esc_html_e( 'Set up WP-OTP (WordPress One Time Password)', 'wp-otp' ); ?></h2>
<table class="form-table">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'OTP Secret', 'wp-otp' ); ?>:<br>
			<?php echo esc_html( chunk_split( $secret, 4, ' ' ) ); ?><br><br>
			<?php if ( $otp_enabled ) : ?>
				<?php
				printf(
					'<em>%1$s</em><br><a href="%2$s" class="button button-small wp-otp-link-reconfigure">%3$s</a>',
					esc_html__( 'WP-OTP has been configured successfully.', 'wp-otp' ),
					esc_url( add_query_arg( 'wp-otp-reconfigure', 'yes' ) ),
					esc_html_x( 'Reconfigure', 'Link to reset and reconfigure WP-OTP secret', 'wp-otp' )
				);
				?>
				<br><br>
				<div class="wp-otp-recovery-codes-box">
					<?php esc_html_e( 'Recovery codes', 'wp-otp' ); ?>:<br>
					<?php
					foreach ( $user_meta_data->get( 'recovery_codes' ) as $code => $unused ) {
						printf( '<%1$s>%2$s</%1$s>', $unused ? 'span' : 'del', $code ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					printf(
						'<a href="%1$s" class="button button-small wp-otp-link-new-recovery-codes">%2$s</a>',
						esc_url( add_query_arg( 'wp-otp-new-recovery-codes', 'yes' ) ),
						esc_html_x( 'Regenerate', 'Link to regenerate the WP-OTP recovery codes', 'wp-otp' )
					);
					?>
				</div>
			<?php else : ?>
				<em><?php esc_html_e( 'To activate WP-OTP, enter the One Time Password from your authenticator app and save your profile.', 'wp-otp' ); ?></em><br><br>
				<label for="wp-otp-code"><?php esc_html_e( 'One Time Password', 'wp-otp' ); ?></label><br>
				<input type="text" class="input" name="wp-otp-code" id="wp-otp-code"/>
			<?php endif; ?>
			<?php wp_nonce_field( 'wp_otp_nonce', 'wp_otp_nonce', false ); ?>
		</th>
		<td>
			<img id="wp-otp-qr-code" src="<?php echo esc_attr( $otp_qr_code_img_uri ); ?>" alt="<?php esc_attr_e( 'QR Code to scan with mobile app', 'wp-otp' ); ?>"/><br>
		</td>
		<td>
			<span class="description">
				<?php esc_html_e( 'Download any OTP Authenticator app on your smart phone and scan the QR Code to activate WP-OTP.', 'wp-otp' ); ?>
			</span><br><br>

			<?php foreach ( $otp_apps as $otp_app ) : ?>
				<span class="wp-otp-app-box">
					<strong><?php echo esc_html( $otp_app['name'] ); ?></strong><br>
					<a href="<?php echo esc_html( $otp_app['uri'] ); ?>" target="_blank">
						<img src="<?php echo esc_url( $otp_app['uri_logo'] ); ?>"
							 alt="<?php echo esc_attr( $otp_app['name'] ); ?>"
							 title="<?php echo esc_attr( $otp_app['name'] ); ?>"
						/></a>&nbsp;
					<?php foreach ( $app_providers as $app_provider_key => $app_provider ) : ?>
						<?php
						if ( ! array_key_exists( 'uri_' . $app_provider_key, $otp_app ) ) {
							continue;
						}

						// translators: Placeholder is an app provider name.
						$get_it_on_text = sprintf( esc_attr__( 'Get it on %s', 'wp-otp' ), $app_provider['name'] );
						?>
						<a href="<?php echo esc_url( $otp_app[ 'uri_' . $app_provider_key ] ); ?>" target="_blank">
							<img src="<?php echo esc_url( $app_provider['uri_logo'] ); ?>"
								 alt="<?php echo esc_attr( $get_it_on_text ); ?>"
								 title="<?php echo esc_attr( $get_it_on_text ); ?>"
							/></a>&nbsp;
					<?php endforeach; ?>
				</span>
			<?php endforeach; ?>
		</td>
	</tr>
</table>
