<?php
/**
 * Render the WP-OTP section in the user profile in WP Admin
 *
 * @since 0.1.0
 */
?>

<a name="wp_otp"></a>
<h2><?php _e( 'Set up WP-OTP (WordPress One Time Password)', 'wp-otp' ); ?></h2>
<table class="form-table">
	<tr>
		<th scope="row">
			<?php echo __( 'OTP Secret', 'wp-otp' ) . ':<br>' . implode( ' ', str_split( $secret, 4 ) ) . '<br><br>'; ?>
			<?php if ( $otp_enabled ) : ?>
				<?php
				printf(
					'<em>%1$s</em><br><a href="%2$s" class="button button-small wp-otp-link-reconfigure">%3$s</a>',
					__( 'WP-OTP has been configured successfully.', 'wp-otp' ),
					add_query_arg( 'wp-otp-reconfigure', 'yes' ),
					_x( 'Reconfigure', 'Link to reset and reconfigure WP-OTP secret', 'wp-otp' )
				);
				?>
				<br><br>
				<div class="wp-otp-recovery-codes-box">
					<?php _e( 'Recovery codes', 'wp-otp' ); ?>:<br>
					<?php
					foreach ( $user_meta_data->get( 'recovery_codes' ) as $code => $unused ) {
						printf( '<%1$s>%2$s</%1$s>', $unused ? 'span' : 'del', $code );
					}
					printf(
						'<a href="%1$s" class="button button-small wp-otp-link-new-recovery-codes">%2$s</a>',
						add_query_arg( 'wp-otp-new-recovery-codes', 'yes' ),
						_x( 'Regenerate', 'Link to regenerate the WP-OTP recovery codes', 'wp-otp' )
					);
					?>
				</div>
			<?php else : ?>
				<em><?php _e( 'To activate WP-OTP, enter the One Time Password from your authenticator app and save your profile.', 'wp-otp' ); ?></em><br><br>
				<label for="wp_otp_code"><?php _e( 'One Time Password', 'wp-otp' ); ?></label><br>
				<input type="text" size="25" name="wp_otp_code" id="wp_otp_code"/>
			<?php endif; ?>
		</th>
		<td>
			<img src="<?php echo $otp_qr_code_img_uri; ?>" alt="<?php _e( 'QR Code to scan with mobile app', 'wp-otp' ); ?>"/><br>
		</td>
		<td>
			<span class="description">
				<?php _e( 'Download any OTP Authenticator app on your smart phone and scan the QR Code to activate WP-OTP.', 'wp-otp' ); ?>
			</span><br><br>

			<?php foreach ( $otp_apps as $otp_app ) : ?>
				<span class="wp-otp-app-box">
					<?php $app_name = esc_attr( $otp_app['name'] ); ?>
					<strong><?php echo $otp_app['name']; ?></strong><br>
					<a href="<?php echo $otp_app['uri']; ?>" target="_blank">
						<img src="<?php echo $otp_app['uri_logo']; ?>"
							 alt="<?php echo $app_name; ?>"
							 title="<?php echo $app_name; ?>"
						/></a>&nbsp;
					<?php foreach ( $app_providers as $app_provider_key => $app_provider ) : ?>
						<?php
						if ( ! array_key_exists( 'uri_' . $app_provider_key, $otp_app ) ) {
							continue;
						}
						$get_it_on_text = sprintf(
							esc_attr__( 'Get it on %s', 'wp-otp' ),
							$app_provider['name']
						);
						?>
						<a href="<?php echo $otp_app[ 'uri_' . $app_provider_key ]; ?>" target="_blank">
							<img src="<?php echo $app_provider['uri_logo']; ?>"
								 alt="<?php echo $get_it_on_text; ?>"
								 title="<?php echo $get_it_on_text; ?>"
							/></a>&nbsp;
					<?php endforeach; ?>
				</span>
			<?php endforeach; ?>
		</td>
	</tr>
</table>
