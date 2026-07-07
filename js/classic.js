/**
 * Classic editor UI: wires up the checkbox rendered in the Publish box.
 */
( function ( $ ) {
	'use strict';

	var data = window.plpEditorData;

	if ( ! data ) {
		return;
	}

	$( function () {
		var $toggle = $( '#plp-toggle' );
		var $row = $( '#plp-link-row' );
		var $url = $( '#plp-url' );
		var $copy = $( '#plp-copy' );
		var copyLabel = $copy.text();

		$toggle.on( 'change', function () {
			var enable = $toggle.prop( 'checked' );

			$toggle.prop( 'disabled', true );

			$.post( data.ajaxUrl, {
				action: 'plp_toggle',
				_ajax_nonce: data.nonce,
				post_id: data.postId,
				enabled: enable ? '1' : '0',
			} )
				.done( function ( result ) {
					if ( result && result.success ) {
						$url.val( result.data.url || '' );
						$row.toggle( !! result.data.enabled );
					} else {
						// Revert the checkbox on failure.
						$toggle.prop( 'checked', ! enable );
						window.alert(
							( result && result.data && result.data.message ) || data.i18n.error
						);
					}
				} )
				.fail( function () {
					$toggle.prop( 'checked', ! enable );
					window.alert( data.i18n.error );
				} )
				.always( function () {
					$toggle.prop( 'disabled', false );
				} );
		} );

		$copy.on( 'click', function () {
			$url.trigger( 'focus' ).trigger( 'select' );

			var done = function () {
				$copy.text( data.i18n.copied );
				window.setTimeout( function () {
					$copy.text( copyLabel );
				}, 2000 );
			};

			if ( window.navigator.clipboard && window.isSecureContext ) {
				window.navigator.clipboard.writeText( $url.val() ).then( done );
			} else {
				$url[ 0 ].select();
				document.execCommand( 'copy' );
				done();
			}
		} );
	} );
} )( window.jQuery );
