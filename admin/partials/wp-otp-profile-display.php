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
		<th scope="row">
			<?php if ( $otp_enabled ): ?>
				<?php
				printf(
					'<em>%1$s</em><br><a href="%2$s" onclick = "return confirm(\'%3$s\')">%4$s</a>',
					__( 'WP-OTP has been configured successfully.', 'wp-otp' ),
					add_query_arg( 'wp-otp-delete', 'yes' ),
					__( 'Are you sure you want to reconfigure WP-OTP?', 'wp-otp' ),
					__( 'Reconfigure?', 'wp-otp' )
				);
				?>
			<?php else: ?>
				<em><?php _e( 'To activate WP-OTP, enter the One Time Password from your authenticator app and save your profile.',
					'wp-otp' ); ?></em><br><br>
				<label for="wp_otp_code"><?php _e( 'One Time Password', 'wp-otp' ); ?></label><br>
				<input type="text" size="25" name="wp_otp_code" id="wp_otp_code"/>
			<?php endif; ?>
			<br><br>
			<?php printf( __( 'OTP Secret:<br> %s', 'wp-otp' ), implode( ' ', str_split( $secret, 4 ) ) ); ?>
		</th>
		<td>
			<img src="<?php echo $otp_qr_code_uri; ?>"/><br>
		</td>
		<td>
			<span class="description">
				<?php _e( 'Download any OTP Authenticator app on your smart phone and scan the QR Code to activate WP-OTP.',
					'wp-otp' ); ?>
			</span><br><br>

			<?php foreach ( $otp_apps as $otp_app ): ?>
				<?php $app_name = esc_attr( $otp_app['name'] ); ?>
				<strong><?php echo $otp_app['name']; ?></strong><br>
				<a href="<?php echo $otp_app['uri']; ?>" target="_blank">
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
		</td>
	</tr>
</table>
