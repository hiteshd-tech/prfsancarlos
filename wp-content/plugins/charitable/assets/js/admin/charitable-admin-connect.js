/* global charitable_admin */
/**
 * Connect functionality.
 *
 * @since 1.5.4
 */

'use strict';

var CharitableConnect = window.CharitableConnect || ( function( document, window, $ ) {

	/**
	 * Elements reference.
	 *
	 * @since 1.5.5
	 *
	 * @type {object}
	 */
	var el = {
		$connectBtn: $( '#charitable-settings-connect-btn' ),
		$connectKey: $( '#charitable-settings-upgrade-license-key' ),
	};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.5.5
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.5.5
		 */
		init: function() {

			$( app.ready );

	    },

		/**
		 * Document ready.
		 *
		 * @since 1.5.5
		 */
		ready: function() {

			app.events();
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.5.5
		 */
		events: function() {

			app.connectBtnClick();
		},

		/**
		 * Register connect button event.
		 *
		 * @since 1.5.5
		 */
		connectBtnClick: function() {

			// If the button contains the 'data-pro-connect' attribute and it's false, don't show the button.
			// If there is no data-pro-connect attribute, don't show the button.
			if ( ! el.$connectBtn.data( 'pro-connect' ) || el.$connectBtn.data( 'pro-connect' ) === false ) {
				return;
			}

			el.$connectBtn.on( 'click', function( event ) {
                // Stop the form from submitting.
				event.preventDefault();
				app.gotoUpgradeUrl();
			} );
		},

		/**
		 * Get the alert arguments in case of Pro already installed.
		 *
		 * @since 1.5.5
		 *
		 * @param {object} res Ajax query result object.
		 *
		 * @returns {object} Alert arguments.
		 */
		proAlreadyInstalled: function( res ) {

			var buttons = {
				confirm: {
					text: charitable_admin.plugin_activate_btn,
					btnClass: 'btn-confirm',
					keys: [ 'enter' ],
					action: function() {
						window.location.reload();
					},
				},
			};

			return {
				title: charitable_admin.almost_done,
				content: res.data.message,
				icon: 'fa fa-check-circle',
				type: 'green',
				buttons: buttons,
			};
		},

		/**
		 * Pro installed but inactive: show popup "Activate now?" then call AJAX to activate; redirect to dashboard on success.
		 *
		 * @since 1.8.9.6
		 * @param {object} data Response data with message, redirect_url, plugin_basename.
		 */
		showActivateProPopup: function( data ) {
			var self = this;
			$.alert( {
				title: charitable_admin.license_validated || charitable_admin.success,
				content: data.message,
				icon: 'fa fa-check-circle',
				type: 'green',
				boxWidth: '800px',
				buttons: {
					activate: {
						text: charitable_admin.activate_now,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action: function() {
							self.activateProThenRedirect( data );
						},
					},
					cancel: {
						text: charitable_admin.cancel,
						keys: [ 'esc' ],
						action: function() {
							window.location.reload();
						},
					},
				},
			} );
		},

		/**
		 * Call AJAX to activate Charitable Pro, then redirect to dashboard on success; show error and keep Lite on failure.
		 *
		 * @since 1.8.9.6
		 * @param {object} data Data with redirect_url, plugin_basename.
		 */
		activateProThenRedirect: function( data ) {
			$.post( charitable_admin.ajax_url, {
				action: 'charitable_activate_pro',
				nonce: charitable_admin.nonce,
				plugin_basename: data.plugin_basename || 'charitable-pro/charitable.php',
			} )
				.done( function( res ) {
					if ( res.success && res.data.redirect_url ) {
						window.location.href = res.data.redirect_url;
					} else if ( res.data && res.data.message ) {
						$.alert( {
							title: charitable_admin.oops,
							content: res.data.message,
							icon: 'fa fa-exclamation-circle',
							type: 'orange',
							buttons: { confirm: { text: charitable_admin.ok, btnClass: 'btn-confirm' } },
						} );
					}
				} )
				.fail( function( xhr ) {
					app.failAlert( xhr );
				} );
		},

		/**
		 * Pro already installed: ask to activate. Yes = redirect to activate, No = back to general settings.
		 *
		 * @since 1.8.9.6
		 * @param {object} data Response data with activate_url, general_url, message.
		 */
		showProAlreadyInstalledConfirm: function( data ) {

			$.alert( {
				title: charitable_admin.success,
				content: data.message,
				icon: 'fa fa-check-circle',
				type: 'green',
				boxWidth: '800px',
				buttons: {
					activate: {
						text: charitable_admin.plugin_activate_btn,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action: function() {
							window.location.href = data.activate_url;
						},
					},
					no: {
						text: charitable_admin.cancel,
						keys: [ 'esc' ],
						action: function() {
							window.location.href = data.general_url;
						},
					},
				},
			} );
		},

		/**
		 * Go to upgrade url.
		 *
		 * @since 1.5.5
		 * @version 1.8.9.6
		 */
		gotoUpgradeUrl: function( event ) {

			var data = {
				action: 'charitable_connect_url',
				key:  el.$connectKey.val(),
				nonce: charitable_admin.nonce,
			};

			// Test arg: ?charitable_connect_test=1 on settings URL simulates non-localhost (install/activate modals).
			if ( window.location.search.indexOf( 'charitable_connect_test=1' ) !== -1 ) {
				data.charitable_connect_test = '1';
			}

			// if there is key empty, then alert.
			if ( ! el.$connectKey.val() ) {
				$.alert( {
					title: charitable_admin.oops,
					icon: 'fa fa-exclamation-circle',
					type: 'orange',
					boxWidth: '800px',
					content: charitable_admin.please_enter_key,
					buttons: {
						confirm: {
							text: charitable_admin.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
						},
					},
				} );
				// prevent the form from submitting.
				event.preventDefault();
				return false;
			}

			var $btn = el.$connectBtn;
			var originalHtml = $btn.html();
			var spinnerHtml = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> ';
			$btn.prop( 'disabled', true ).html( spinnerHtml + charitable_admin.verifying );

			$.post( charitable_admin.ajax_url, data )
				.done( function( res ) {

					if ( res.success ) {
						// Pro installed but inactive: show popup to activate (no redirect to upgrade.wpcharitable.com).
						if ( res.data.show_activate_pro_popup ) {
							app.showActivateProPopup( res.data );
							return;
						}
						// Redirect to upgrade.wpcharitable.com (Pro not installed — need to download).
						if ( res.data.upgrade_url ) {
							el.$connectBtn.data( 'charitable-skip-restore', true );
							window.location.href = res.data.upgrade_url;
							return;
						}
						if ( res.data.reload ) {
							$.alert( app.proAlreadyInstalled( res ) );
							return;
						}
						if ( res.data.action === 'show_pro_download_confirmation' ) {
							app.showProDownloadConfirmation( res.data );
							return;
						}
						// Pro already installed: ask to activate. Yes = go to activate, No = back to general settings.
						if ( res.data.pro_already_installed_confirm ) {
							app.showProAlreadyInstalledConfirm( res.data );
							return;
						}
						// Localhost: show message and Manual Install button (opens docs in new tab only).
						// Refresh the page on either button so the settings UI shows the saved license.
						if ( res.data.show_manual_upgrade && res.data.url ) {
							$.alert( {
								title: charitable_admin.license_validated || charitable_admin.success,
								content: res.data.message,
								icon: 'fa fa-check-circle',
								type: 'green',
								boxWidth: '800px',
								buttons: {
									confirm: {
										text: charitable_admin.ok,
										btnClass: 'btn-confirm',
										keys: [ 'enter' ],
										action: function() {
											window.location.reload();
										},
									},
									manual: {
										text: charitable_admin.manual_install,
										btnClass: 'btn-confirm',
										action: function() {
											window.open( res.data.url, '_blank' );
											window.location.reload();
										},
									},
								},
							} );
							return;
						}
					}

					// Error or legacy response: show message (with optional manual upgrade for errors).
					if ( res.data && res.data.show_manual_upgrade && res.data.url ) {
						$.alert( {
							title: charitable_admin.oops,
							content: res.data.message,
							icon: 'fa fa-exclamation-circle',
							type: 'orange',
							boxWidth: '800px',
							buttons: {
								confirm: {
									text: charitable_admin.ok,
									btnClass: 'btn-confirm',
									keys: [ 'enter' ],
								},
								url: {
									text: charitable_admin.manual_install,
									btnClass: 'btn-confirm',
									action: function() {
										window.open( res.data.url, '_blank' );
									},
								},
							},
						} );
					} else if ( res.data && res.data.message ) {
						$.alert( {
							title: charitable_admin.oops,
							content: res.data.message,
							icon: 'fa fa-exclamation-circle',
							type: 'orange',
							boxWidth: '800px',
							buttons: {
								confirm: {
									text: charitable_admin.ok,
									btnClass: 'btn-confirm',
									keys: [ 'enter' ],
								},
							},
						} );
					}
				} )
				.always( function() {
					var $b = el.$connectBtn;
					// Only restore if we're not redirecting (e.g. user saw popup and stayed on page).
					if ( $b.data( 'charitable-skip-restore' ) ) {
						$b.removeData( 'charitable-skip-restore' );
						return;
					}
					if ( originalHtml ) {
						$b.prop( 'disabled', false ).html( originalHtml );
					} else {
						$b.prop( 'disabled', false );
					}
				} )
				.fail( function( xhr ) {

					app.failAlert( xhr );
				} );
		},

		/**
		 * Show Pro download confirmation modal.
		 *
		 * @since 1.8.9.6
		 * @version 1.8.9.6
		 *
		 * @param {object} data Response data from server.
		 */
		showProDownloadConfirmation: function( data ) {

			$.alert( {
				title: data.confirmation_title || charitable_admin.license_validated,
				content: data.confirmation_text,
				icon: 'fa fa-check-circle',
				type: 'green',
				boxWidth: '800px',
				buttons: {
					download: {
						text: charitable_admin.download_pro,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action: function() {
							app.downloadProPlugin( data.pro_download_url );
						},
					},
					cancel: {
						text: charitable_admin.cancel,
						keys: [ 'esc' ],
						action: function() {
							window.location.reload();
						},
					},
				},
			} );
		},

		/**
		 * Download Charitable Pro plugin.
		 *
		 * @since 1.8.9.6
		 * @version 1.8.9.6
		 *
		 * @param {string} downloadUrl The download URL for Pro plugin.
		 */
		downloadProPlugin: function( downloadUrl ) {

			var data = {
				action: 'charitable_download_pro',
				download_url: downloadUrl,
				nonce: charitable_admin.nonce,
			};

			// Show loading message.
			$.alert( {
				title: charitable_admin.downloading,
				content: charitable_admin.downloading_pro_message,
				icon: 'fa fa-spinner fa-spin',
				type: 'blue',
				boxWidth: '600px',
				buttons: false,
				closeIcon: false,
			} );

			$.post( charitable_admin.ajax_url, data )
				.done( function( res ) {
					// Close the loading dialog.
					if ( typeof jconfirm !== 'undefined' && jconfirm.instances && jconfirm.instances.length > 0 ) {
						jconfirm.instances[ jconfirm.instances.length - 1 ].close();
					}

					if ( res.success ) {
						if ( res.data.action === 'show_activation_confirmation' ) {
							app.showActivationConfirmation( res.data );
							return;
						}
					}

					// Handle download errors.
					var errorMessage = res.data && res.data.message ? res.data.message : charitable_admin.download_failed;
					var addonsUrl = res.data && res.data.addons_url ? res.data.addons_url : null;

					var buttons = {
						confirm: {
							text: charitable_admin.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
						},
					};

					if ( addonsUrl ) {
						buttons.addons = {
							text: charitable_admin.visit_addons_page,
							btnClass: 'btn-confirm',
							action: function() {
								window.location.href = addonsUrl;
							},
						};
					}

					$.alert( {
						title: charitable_admin.oops,
						content: errorMessage,
						icon: 'fa fa-exclamation-circle',
						type: 'orange',
						boxWidth: '800px',
						buttons: buttons,
					} );
				} )
				.fail( function( xhr ) {
					// Close the loading dialog.
					if ( typeof jconfirm !== 'undefined' && jconfirm.instances && jconfirm.instances.length > 0 ) {
						jconfirm.instances[ jconfirm.instances.length - 1 ].close();
					}
					app.failAlert( xhr );
				} );
		},

		/**
		 * Show Pro activation confirmation modal.
		 *
		 * @since 1.8.9.6
		 * @version 1.8.9.6
		 *
		 * @param {object} data Response data from server.
		 */
		showActivationConfirmation: function( data ) {

			$.alert( {
				title: data.activation_title || charitable_admin.success,
				content: data.activation_text,
				icon: 'fa fa-check-circle',
				type: 'green',
				boxWidth: '800px',
				buttons: {
					activate: {
						text: charitable_admin.activate_now,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action: function() {
							app.activateProPlugin( data.plugin_basename );
						},
					},
					later: {
						text: charitable_admin.activate_later,
						keys: [ 'esc' ],
						action: function() {
							// Just close the modal - Pro is downloaded and ready for manual activation.
							$.alert( {
								title: charitable_admin.ready_to_use,
								content: charitable_admin.pro_ready_to_activate,
								icon: 'fa fa-info-circle',
								type: 'blue',
								boxWidth: '600px',
								buttons: {
									confirm: {
										text: charitable_admin.ok,
										btnClass: 'btn-confirm',
										keys: [ 'enter' ],
									},
								},
							} );
						},
					},
				},
			} );
		},

		/**
		 * Activate Charitable Pro plugin.
		 *
		 * @since 1.8.9.6
		 * @version 1.8.9.6
		 *
		 * @param {string} pluginBasename The plugin basename to activate.
		 */
		activateProPlugin: function( pluginBasename ) {

			var data = {
				action: 'charitable_activate_pro',
				plugin_basename: pluginBasename,
				nonce: charitable_admin.nonce,
			};

			// Show loading message.
			$.alert( {
				title: charitable_admin.activating,
				content: charitable_admin.activating_pro_message,
				icon: 'fa fa-spinner fa-spin',
				type: 'blue',
				boxWidth: '600px',
				buttons: false,
				closeIcon: false,
			} );

			$.post( charitable_admin.ajax_url, data )
				.done( function( res ) {
					// Close the loading dialog.
					if ( typeof jconfirm !== 'undefined' && jconfirm.instances && jconfirm.instances.length > 0 ) {
						jconfirm.instances[ jconfirm.instances.length - 1 ].close();
					}

					if ( res.success && res.data.reload ) {
						$.alert( {
							title: charitable_admin.success,
							content: res.data.message,
							icon: 'fa fa-check-circle',
							type: 'green',
							boxWidth: '600px',
							buttons: {
								confirm: {
									text: charitable_admin.ok,
									btnClass: 'btn-confirm',
									keys: [ 'enter' ],
									action: function() {
										window.location.reload();
									},
								},
							},
						} );
						return;
					}

					// Handle activation errors.
					var errorMessage = res.data && res.data.message ? res.data.message : charitable_admin.activation_failed;

					$.alert( {
						title: charitable_admin.oops,
						content: errorMessage,
						icon: 'fa fa-exclamation-circle',
						type: 'orange',
						boxWidth: '800px',
						buttons: {
							confirm: {
								text: charitable_admin.ok,
								btnClass: 'btn-confirm',
								keys: [ 'enter' ],
							},
						},
					} );
				} )
				.fail( function( xhr ) {
					// Close the loading dialog.
					if ( typeof jconfirm !== 'undefined' && jconfirm.instances && jconfirm.instances.length > 0 ) {
						jconfirm.instances[ jconfirm.instances.length - 1 ].close();
					}
					app.failAlert( xhr );
				} );
		},

		/**
		 * Alert in case of server error.
		 *
		 * @since 1.5.5
		 *
		 * @param {object} xhr XHR object.
		 */
		failAlert: function( xhr ) {

			$.alert( {
				title: charitable_admin.oops,
				content: charitable_admin.server_error + '<br>' + xhr.status + ' ' + xhr.statusText + ' ' + xhr.responseText,
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
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
CharitableConnect.init();
