<?php
/**
 * Render the WP-OTP section in the user profile in WP Admin
 *
 * @since 0.1.0
 */
?>

<a name="wp_otp"></a>
<h2>Set up WP-OTP</h2>
<table class="form-table">
	<tr>
		<th>
			<label for="wp_otp_qr_code_img"><?php _e( 'QR Code', 'wp-otp' ); ?></label><br>
			<span class="description">Download any OTP Authenticator app on your smart phone and
					scan this QR Code to setup WP-OTP.</span>
		</th>
		<td width="40%">
			<img src="<?php echo $otp_qr_code_uri; ?>" id="wp_otp_qr_code_img"/><br>
			<?php printf( __( 'OTP Secret: %s', 'wp-otp' ), implode( ' ', str_split( $secret, 4 ) ) ); ?>
		</td>
		<td>
			<?php foreach ( $otp_apps as $otp_app ): ?>
				<?php $app_name = esc_attr( $otp_app['name'] ); ?>
				<a href="<?php echo $otp_app['uri']; ?>"><strong><?php echo $otp_app['name']; ?></strong><br>
					<img src="<?php echo $otp_app['uri_logo']; ?>"
					     alt="<?php echo $app_name; ?>"
					     title="<?php echo $app_name; ?>"
					/></a>&nbsp;
				<?php foreach ( $app_providers as $app_provider_key => $app_provider ): ?>
					<?php
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
				<br><br>
			<?php endforeach; ?>
			<?php _e( 'Blackberry users can search and install any OTP app on their phone.', 'wp-otp' ); ?>
		</td>
	</tr>
	<tr>
		<?php if ( $otp_enabled ): ?>
			<th>
				<?php _e( 'WP-OTP Configured', 'wp-otp' ); ?>
			</th>
			<td colspan="2">
				<?php
				printf(
					'%1$s <a href="%2$s" onclick = "return confirm(\'%3$s\')">%4$s</a>',
					__( 'WP-OTP is already configured.', 'wp-otp' ),
					get_edit_profile_url() . '?wp-otp-delete',
					'Are you sure you want to reconfigure WP-OTP?',
					__( 'Reconfigure?', 'wp-otp' )
				);
				?>
			</td>
		<?php else: ?>
			<th>
				<label for="wp_otp_code"><?php _e( 'One Time Password', 'wp-otp' ); ?></label>
			</th>
			<td colspan="2">
				<input type="text" size="25"
				       value="<?php echo isset( $_POST['wp_otp_code'] ) ? $_POST['wp_otp_code'] : ''; ?>"
				       name="wp_otp_code" id="wp_otp_code"/><br/>

				<?php _e( 'Enter the One Time Password from Google Authenticator app on your smartphone <br>
						WP-OTP will not work unless you Enter the OTP here and click on <b>Update Profile</b> button below.',
					'wp-otp' ); ?>
			</td>
		<?php endif; ?>
	</tr>
</table>
