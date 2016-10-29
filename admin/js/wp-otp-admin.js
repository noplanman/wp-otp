(function ( $ ) {
	'use strict';

	$( function () {
		$( '.wp-otp-link-reconfigure' ).click( function () {
			return confirm( wp_otp.confirm_reconfigure );
		} );
		$( '.wp-otp-link-new-recovery-codes' ).click( function () {
			return confirm( wp_otp.confirm_new_recovery_codes );
		} );
	} );
})( jQuery );