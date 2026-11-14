/**
 * wp-admin: colour pickers and the "Send a test report" button on the
 * settings page. The button calls the myplugin_send_test_report AJAX action
 * (src/Ajax.php).
 */
/* global jQuery, ajaxurl */
jQuery( function ( $ ) {
	$( '.myplugin-color-field' ).wpColorPicker();

	$( '.myplugin-test-report' ).on( 'click', function () {
		const $button = $( this );
		const $result = $button.siblings( '.myplugin-test-report__result' );

		$button.prop( 'disabled', true );
		$result.text( '' );

		$.post( ajaxurl, {
			action: 'myplugin_send_test_report',
			nonce: $button.data( 'nonce' ),
		} )
			.always( function ( response ) {
				// Errors arrive as a jqXHR whose responseJSON holds the message.
				const data = ( response && response.responseJSON ) || response;
				$result.text(
					( data && data.data && data.data.message ) || ''
				);
			} )
			.always( function () {
				$button.prop( 'disabled', false );
			} );
	} );
} );
