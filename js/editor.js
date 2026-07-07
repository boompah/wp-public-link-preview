/**
 * Block editor UI: a "Public preview link" toggle in the post Status &
 * Visibility panel, next to the publish controls.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.element || ! wp.components ) {
		return;
	}

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var registerPlugin = wp.plugins.registerPlugin;
	var ToggleControl = wp.components.ToggleControl;
	var Button = wp.components.Button;
	var __ = wp.i18n.__;

	// Moved from wp.editPost to wp.editor in WP 6.6.
	var PluginPostStatusInfo =
		( wp.editor && wp.editor.PluginPostStatusInfo ) ||
		( wp.editPost && wp.editPost.PluginPostStatusInfo );

	var data = window.plpEditorData;

	if ( ! PluginPostStatusInfo || ! data ) {
		return;
	}

	function copyToClipboard( text ) {
		if ( window.navigator.clipboard && window.isSecureContext ) {
			return window.navigator.clipboard.writeText( text );
		}

		return new Promise( function ( resolve, reject ) {
			var input = document.createElement( 'textarea' );
			input.value = text;
			input.style.position = 'fixed';
			input.style.opacity = '0';
			document.body.appendChild( input );
			input.select();
			try {
				document.execCommand( 'copy' );
				resolve();
			} catch ( err ) {
				reject( err );
			} finally {
				document.body.removeChild( input );
			}
		} );
	}

	function PublicLinkPreviewRow() {
		var enabledState = useState( !! data.enabled );
		var enabled = enabledState[ 0 ];
		var setEnabled = enabledState[ 1 ];

		var urlState = useState( data.url || '' );
		var url = urlState[ 0 ];
		var setUrl = urlState[ 1 ];

		var busyState = useState( false );
		var busy = busyState[ 0 ];
		var setBusy = busyState[ 1 ];

		var copiedState = useState( false );
		var copied = copiedState[ 0 ];
		var setCopied = copiedState[ 1 ];

		function toggle( next ) {
			setBusy( true );

			var body = new window.FormData();
			body.append( 'action', 'plp_toggle' );
			body.append( '_ajax_nonce', data.nonce );
			body.append( 'post_id', data.postId );
			body.append( 'enabled', next ? '1' : '0' );

			window
				.fetch( data.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: body,
				} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( result ) {
					if ( result && result.success ) {
						setEnabled( !! result.data.enabled );
						setUrl( result.data.url || '' );
						setCopied( false );
					} else {
						window.alert(
							( result && result.data && result.data.message ) ||
								__( 'Could not update the preview link.', 'public-link-preview' )
						);
					}
					setBusy( false );
				} )
				.catch( function () {
					window.alert( __( 'Could not update the preview link.', 'public-link-preview' ) );
					setBusy( false );
				} );
		}

		function copy() {
			copyToClipboard( url ).then( function () {
				setCopied( true );
				window.setTimeout( function () {
					setCopied( false );
				}, 2000 );
			} );
		}

		return el(
			PluginPostStatusInfo,
			{ className: 'plp-status-info' },
			el(
				'div',
				{ style: { width: '100%' } },
				el( ToggleControl, {
					label: __( 'Public preview link', 'public-link-preview' ),
					checked: enabled,
					disabled: busy,
					onChange: toggle,
					__nextHasNoMarginBottom: true,
					help: enabled
						? __(
								'Anyone with the link below can view this post until you turn this off.',
								'public-link-preview'
						  )
						: __( 'Share this post publicly before it is published.', 'public-link-preview' ),
				} ),
				enabled && url
					? el(
							'div',
							{
								style: {
									display: 'flex',
									gap: '4px',
									alignItems: 'center',
									marginTop: '8px',
								},
							},
							el( 'input', {
								type: 'text',
								readOnly: true,
								value: url,
								style: { flex: '1 1 auto', minWidth: 0, fontSize: '12px' },
								onFocus: function ( event ) {
									event.target.select();
								},
								'aria-label': __( 'Public preview URL', 'public-link-preview' ),
							} ),
							el(
								Button,
								{ variant: 'secondary', isSmall: true, onClick: copy },
								copied
									? __( 'Copied!', 'public-link-preview' )
									: __( 'Copy', 'public-link-preview' )
							)
					  )
					: null
			)
		);
	}

	registerPlugin( 'public-link-preview', {
		render: PublicLinkPreviewRow,
	} );
} )( window.wp );
