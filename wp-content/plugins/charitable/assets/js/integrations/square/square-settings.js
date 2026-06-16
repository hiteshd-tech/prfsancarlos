/* global charitable_admin, CharitableAdmin, wpf */

/**
 * Charitable Square settings function.
 *
 * @since 1.8.7
 */
const CharitableSettingsSquare = window.CharitableSettingsSquare || ( function( document, window, $ ) {
	/**
	 * Elements.
	 *
	 * @since 1.8.7
	 *
	 * @type {Object}
	 */
	let $el = {};

	const iconSpinner = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>';

	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.7
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.8.7
		 */
		init() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.8.7
		 */
		ready() {
			// Wait for DOM to be fully loaded
			$(document).ready(function() {
				// Initialize all element references after DOM is loaded
				$el = {
					sandboxModeCheckbox: $( '#charitable-setting-square-sandbox-mode' ),
					sandboxConnectionStatusBlock: $( '#charitable-setting-row-square-connection-status-sandbox' ),
					productionConnectionStatusBlock: $( '#charitable-setting-row-square-connection-status-production' ),
					sandboxLocationBlock: $( '#charitable-setting-row-square-location-id-sandbox' ),
					sandboxLocationStatusBlock: $( '#charitable-setting-row-square-location-status-sandbox' ),
					productionLocationBlock: $( '#charitable-setting-row-square-location-id-production' ),
					productionLocationStatusBlock: $( '#charitable-setting-row-square-location-status-production' ),
					refreshBtn: $( '.charitable-square-refresh-btn' ),
					copyButton: $( '#charitable-setting-row-square-webhooks-endpoint-set .charitable-copy-to-clipboard' ),
					webhookEndpointUrl: $( 'input#charitable-square-webhook-endpoint-url' ),
					webhookMethod: $( 'input[name="square-webhooks-communication"]' ),
					webhookCommunicationStatusNotice: $( '#charitable-setting-row-square-webhooks-communication-status' ),
					webhookConnectBtn: $( '#charitable-setting-square-webhooks-connect, .charitable-btn-connect-webhooks' ),
				};

				// Bind events after elements are initialized
				app.events();
			});
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.8.7
		 */
		events() {

			// if ($el.webhookConnectBtn.length === 0) {
			// 	console.error('Webhook connect button not found in DOM!');
			// 	return;
			// }

			// Disabled webhook connect button functionality - now using manual webhook setup
			// $el.webhookConnectBtn.on('click', app.modals.displayWebhookConfigPopup);
			$el.sandboxModeCheckbox.on('change', app.credentialsFieldsDisplay);
			$el.refreshBtn.on('click', app.refreshTokensCallback);
			$el.webhookMethod.on('change', app.updateWebhookEndpointUrl);

			// Add copy functionality for the new webhook URL field
			$(document).on('click', '.charitable-copy-icon', function(e) {
				e.preventDefault();
				const targetId = $(this).data('clipboard-target');
				const targetElement = $(targetId);
				const $copyIcon = $(this);

				if (targetElement.length) {
					// Copy to clipboard using modern clipboard API
					const textToCopy = targetElement.val();

					if (navigator.clipboard && window.isSecureContext) {
						// Use modern clipboard API
						navigator.clipboard.writeText(textToCopy).then(function() {
							// Show "Copied" state
							$copyIcon.addClass('copied');

							// Reset after 2 seconds
							setTimeout(function() {
								$copyIcon.removeClass('copied');
							}, 2000);
						}).catch(function(err) {
							console.error('Failed to copy: ', err);
							// Fallback to older method
							fallbackCopyTextToClipboard(textToCopy, $copyIcon);
						});
					} else {
						// Fallback for older browsers
						fallbackCopyTextToClipboard(textToCopy, $copyIcon);
					}
				}
			});

			// Fallback copy function for older browsers
			function fallbackCopyTextToClipboard(text, $copyIcon) {
				const textArea = document.createElement('textarea');
				textArea.value = text;

				// Avoid scrolling to bottom
				textArea.style.top = '0';
				textArea.style.left = '0';
				textArea.style.position = 'fixed';
				textArea.style.opacity = '0';

				document.body.appendChild(textArea);
				textArea.focus();
				textArea.select();

				try {
					const successful = document.execCommand('copy');
					if (successful) {
						// Show "Copied" state
						$copyIcon.addClass('copied');

						// Reset after 2 seconds
						setTimeout(function() {
							$copyIcon.removeClass('copied');
						}, 2000);
					}
				} catch (err) {
					console.error('Fallback: Oops, unable to copy', err);
				}

				document.body.removeChild(textArea);
			}
		},

		/**
		 * Update the endpoint URL.
		 *
		 * @since 1.8.7
		 */
		updateWebhookEndpointUrl() {
			const checked = $el.webhookMethod.filter( ':checked' ).val(),
				newUrl = charitable_admin.square.webhook_urls[ checked ];

			$el.webhookEndpointUrl.val( newUrl );
			$el.webhookCommunicationStatusNotice.removeClass( 'charitable-hide' );
		},

		/**
		 * Refresh tokens.
		 *
		 * @since 1.8.7
		 */
		refreshTokensCallback() {
			const $btn = $( this );
			const buttonWidth = $btn.outerWidth();
			const buttonLabel = $btn.text();
			const settings = {
				url: charitable_admin.ajax_url,
				type: 'post',
				dataType: 'json',
				data: {
					action: 'charitable_square_refresh_connection',
					nonce: charitable_admin.nonce,
					mode: $btn.data( 'mode' ),
				},
				beforeSend() {
					$btn.css( 'width', buttonWidth ).html( iconSpinner ).prop( 'disabled', true );
				},
			};

			let errorMessage = charitable_admin.square.refresh_error;

			// Perform an Ajax request.
			$.ajax( settings )
				.done( function( response ) {
					if ( response.success ) {
						$btn
							.css( 'pointerEvents', 'none' )
							.removeClass( 'charitable-btn-light-grey' )
							.addClass( 'charitable-btn-grey' )
							.html( 'Refreshed!' );

						$btn.closest( 'form' ).css( 'cursor', 'wait' );

						window.location = $btn.data( 'url' );

						return;
					}

					if (
						Object.prototype.hasOwnProperty.call( response, 'data' ) &&
						response.data !== ''
					) {
						errorMessage = response.data;
					}

					$btn
						.css( 'width', 'auto' )
						.html( buttonLabel )
						.prop( 'disabled', false );
					app.modals.refreshTokensError( errorMessage );
				} )
				.fail( function() {
					$btn
						.css( 'width', 'auto' )
						.html( buttonLabel )
						.prop( 'disabled', false );
					app.modals.refreshTokensError( errorMessage );
				} );
		},

		/**
		 * Conditionally show Square mode switch warning.
		 *
		 * @since 1.8.7
		 */
		credentialsFieldsDisplay() {
			const sandboxModeEnabled = $el.sandboxModeCheckbox.is( ':checked' );

			if ( sandboxModeEnabled ) {
				$el.sandboxConnectionStatusBlock.show();
				$el.sandboxLocationBlock.show();
				$el.sandboxLocationStatusBlock.show();

				$el.productionConnectionStatusBlock.hide();
				$el.productionLocationBlock.hide();
				$el.productionLocationStatusBlock.hide();
			} else {
				$el.sandboxConnectionStatusBlock.hide();
				$el.sandboxLocationBlock.hide();
				$el.sandboxLocationStatusBlock.hide();

				$el.productionConnectionStatusBlock.show();
				$el.productionLocationBlock.show();
				$el.productionLocationStatusBlock.show();
			}

			if ( sandboxModeEnabled && $el.sandboxConnectionStatusBlock.find( '.charitable-square-connected' ).length ) {
				return;
			}

			if ( ! sandboxModeEnabled && $el.productionConnectionStatusBlock.find( '.charitable-square-connected' ).length ) {
				return;
			}

			app.modals.modeChangedWarning();
		},

		/**
		 * Modals.
		 *
		 * @since 1.8.7
		 */
		modals: {

			/**
			 * Show the warning modal when Square mode is changed.
			 *
			 * @since 1.8.7
			 */
			modeChangedWarning() {
				$.alert( {
					title: charitable_admin.heads_up,
					content: charitable_admin.square.mode_update,
					icon: 'fa fa-exclamation-circle',
					type: 'orange',
					buttons: {
						confirm: {
							text: charitable_admin.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
						},
					},
				} );
			},

			/**
			 * Refresh tokens error handling.
			 *
			 * @since 1.8.7
			 *
			 * @param {string} error Error message.
			 */
			refreshTokensError( error ) {
				$.alert( {
					title: false,
					content: error,
					icon: 'fa fa-exclamation-circle',
					type: 'orange',
					buttons: {
						confirm: {
							text: charitable_admin.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
						},
					},
				} );
			},

			/**
			 * Show popup with the ability to register a new webhook route or retrieve existing one.
			 *
			 * @since 1.8.7
			 */
			// eslint-disable-next-line max-lines-per-function
			displayWebhookConfigPopup() {
				$.confirm( {
					title: charitable_admin.square.webhook_create_title,
					content: charitable_admin.square.webhook_create_description +
						'<input type="text" id="charitable-square-personal-access-token" placeholder="' + charitable_admin.square.webhook_token_placeholder + '" value="">' +
						'<p class="charitable-square-webhooks-connect-error error" style="display:none;">' + charitable_admin.square.token_is_required + '</p>',
					icon: 'fa fa-info-circle',
					type: 'blue',
					buttons: {
						confirm: {
							text: charitable_admin.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
							action() {
								const modal = this;
								const tokenField = modal.$content.find( '#charitable-square-personal-access-token' );
								const errorMsg = modal.$content.find( '.error' );
								const token = tokenField.val().trim();
								const title = modal.$title;

								// Disable the button to prevent multiple clicks.
								$el.webhookConnectBtn.addClass( 'inactive' );

								// Reset error message before validation
								errorMsg.hide().text( '' );

								if ( token === '' ) {
									errorMsg.text( charitable_admin.square.token_is_required ).show();
									return false; // Prevent modal from closing.
								}

								// Show loading indicator.
								modal.buttons.confirm.setText( charitable_admin.loading );
								modal.buttons.confirm.disable();

								// Call API.
								// app.createWebhook( token )
								// 	.then( ( response ) => {
								// 		modal.setContent( '<p>' + response.data.message + '</p>' );
								// 		// Hide OK button and rename Cancel to Close.
								// 		modal.buttons.confirm.hide();
								// 		title.text( '' ).hide();
								// 		modal.buttons.cancel.setText( charitable_admin.close );

								// 		// Ensure user can manually close the modal.
								// 		modal.buttons.cancel.action = function() {
								// 			window.location.reload();
								// 		};
								// 	} )
								// 	.catch( ( responseError ) => {
								// 		errorMsg.text( responseError.data.message ).show();

								// 		// Re-enable confirm button for retrying.
								// 		modal.buttons.confirm.setText( charitable_admin.ok );
								// 		modal.buttons.confirm.enable();
								// 	} );

								return false; // Prevent modal from closing immediately.
							},
						},
						cancel: {
							text: charitable_admin.cancel,
							action() {
								// Re-enable the button.
								$el.webhookConnectBtn.removeClass( 'inactive' );

								this.close();
							},
						},
					},
				} );
			},
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

// Initialize.
CharitableSettingsSquare.init();
