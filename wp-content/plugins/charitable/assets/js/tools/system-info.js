/* global wpchar */

/**
 * System Info script.
 *
 * @since 1.8.1.6
 */
const CharitableSystemInfo = window.CharitableSystemInfo || ( function( document, window, $ ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.1.6
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.8.1.6
		 */
		init() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.8.1.6
		 */
		ready() {
			app.events();
		},

		/**
		 * Events.
		 *
		 * @since 1.8.1.6
		 */
		events() {

			// Copy system information to clipboard.
			$( document ).on( 'click', '#charitable-system-information-copy', function( event ) {
				event.preventDefault();
				app.copySystemInformation();
			} );

			// Run SSL test.
			$( document ).on( 'click', '#charitable-ssl-verify', function( event ) {
				event.preventDefault();
				app.verifySSLConnection();
			} );

			// Run email diagnostics.
			$( document ).on( 'click', '#charitable-email-diagnostics', function( event ) {
				event.preventDefault();
				app.runEmailDiagnostics();
			} );

			// Send test email.
			$( document ).on( 'click', '#charitable-send-test-email', function( event ) {
				event.preventDefault();
				app.sendTestEmail();
			} );

			// Copy email diagnostics results.
			$( document ).on( 'click', '#charitable-email-diagnostics-copy', function( event ) {
				event.preventDefault();
				app.copyEmailDiagnostics();
			} );

			// Toggle error logs visibility.
			$( document ).on( 'click', '#charitable-toggle-error-logs', function( event ) {
				event.preventDefault();
				app.toggleErrorLogs();
			} );

			// Clear error logs.
			$( document ).on( 'click', '#charitable-clear-error-logs', function( event ) {
				event.preventDefault();
				app.clearErrorLogs();
			} );

			// Export error logs.
			$( document ).on( 'click', '#charitable-export-error-logs', function( event ) {
				event.preventDefault();
				app.exportErrorLogs();
			} );

			// Run debug log scan.
			$( document ).on( 'click', '#charitable-debug-log-scan', function( event ) {
				event.preventDefault();
				app.runDebugLogScan();
			} );

			// Handle debug format tab switching.
			$( document ).on( 'click', '.charitable-format-tab', function( event ) {
				event.preventDefault();
				app.switchDebugFormat( $( this ) );
			} );

			// Copy debug log results.
			$( document ).on( 'click', '#charitable-debug-copy', function( event ) {
				event.preventDefault();
				app.copyDebugResults();
			} );
		},

		/**
		 * Copy system information to clipboard.
		 *
		 * @since 1.8.1.6
		 */
		copySystemInformation() {
            $( '#charitable-system-information' ).select();
            document.execCommand( 'copy' );
		},

		/**
		 * Verify SSL connection.
		 *
		 * @since 1.8.1.6
		 *
		 * @return {void}
		*/
		verifySSLConnection() {
			const $btn      = $( '#charitable-ssl-verify' );
			const btnLabel  = $btn.text();
			const btnWidth  = $btn.outerWidth();
			const $settings = $btn.parent();

			if ( typeof charitable_admin_tools !== "undefined" && typeof charitable_admin_tools.ajax_url !== "undefined" && typeof charitable_admin_tools.nonce !== "undefined" && typeof charitable_admin_tools.testing !== "undefined" ) {

				$btn.css( 'width', btnWidth ).prop( 'disabled', true ).text( charitable_admin_tools.testing );

				const data = {
					action: 'charitable_verify_ssl',
					nonce:   charitable_admin_tools.nonce
				};

				// Trigger AJAX to test connection
				$.post( charitable_admin_tools.ajax_url, data, function( res ) {

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( res );
					}

					// Remove any previous alerts.
					$settings.find( '.charitable-notice' ).remove();

					if ( res.success ) {
						$btn.before( '<div class="notice charitable-notice notice-success">' + res.data.msg + '</div>' );
					}

					if ( ! res.success && res.data.msg ) {
						$btn.before( '<div class="notice charitable-notice notice-error">' + res.data.msg + '</div>' );
					}

					if ( ! res.success && res.data.debug ) {
						$btn.before( '<div class="charitable-ssl-error pre-error">' + res.data.debug + '</div>' );
					}

					$btn.css( 'width', btnWidth ).prop( 'disabled', false ).text( btnLabel );
				} );

			}
		},

		/**
		 * Run email diagnostics.
		 *
		 * @since 1.8.9.2
		 *
		 * @return {void}
		 */
		runEmailDiagnostics() {
			const $btn = $( '#charitable-email-diagnostics' );
			const $results = $( '#charitable-email-diagnostics-results' );
			const $textarea = $results.find( 'textarea' );
			const $copyBtn = $( '#charitable-email-diagnostics-copy' );
			const btnLabel = $btn.text();
			const btnWidth = $btn.outerWidth();

			if ( typeof charitable_email_diagnostics !== "undefined" ) {

				// Show loading state
				$btn.css( 'width', btnWidth ).prop( 'disabled', true ).text( charitable_email_diagnostics.running_text );
				$results.show();
				$textarea.val( 'Running diagnostics, please wait...' );
				$copyBtn.hide();

				// Remove any previous notices
				$btn.parent().find( '.charitable-notice' ).remove();

				const data = {
					action: 'charitable_email_diagnostics',
					nonce: charitable_email_diagnostics.nonce
				};

				// Run diagnostics via AJAX
				$.post( charitable_email_diagnostics.ajax_url, data, function( response ) {

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( response );
					}

					if ( response.success && response.data.diagnostics ) {
						// Show successful results
						$textarea.val( response.data.diagnostics );
						$copyBtn.show();

						// Show success notice
						$btn.before( '<div class="notice charitable-notice notice-success"><p>Email diagnostics completed successfully.</p></div>' );

					} else {
						// Show error
						let errorMsg = charitable_email_diagnostics.error_text;
						if ( response.data && response.data.message ) {
							errorMsg = response.data.message;
						}

						$textarea.val( '### DIAGNOSTIC ERROR ###\n\n' + errorMsg );
						$btn.before( '<div class="notice charitable-notice notice-error"><p>' + errorMsg + '</p></div>' );
					}

					// Restore button
					$btn.css( 'width', btnWidth ).prop( 'disabled', false ).text( btnLabel );

				} ).fail( function( xhr, status, error ) {
					// Handle AJAX failure
					const errorMsg = 'AJAX request failed: ' + status + ' - ' + error;
					$textarea.val( '### DIAGNOSTIC ERROR ###\n\n' + errorMsg );
					$btn.before( '<div class="notice charitable-notice notice-error"><p>Diagnostics request failed. Please try again.</p></div>' );
					$btn.css( 'width', btnWidth ).prop( 'disabled', false ).text( btnLabel );

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( { xhr: xhr, status: status, error: error } );
					}
				} );

			} else {
				// Show error if localized script not available
				$btn.before( '<div class="notice charitable-notice notice-error"><p>Email diagnostics not properly loaded. Please refresh the page and try again.</p></div>' );
			}
		},

		/**
		 * Send test email.
		 *
		 * @since 1.8.9.2
		 *
		 * @return {void}
		 */
		sendTestEmail() {
			const $btn = $( '#charitable-send-test-email' );
			const $messageDiv = $( '#charitable-test-email-message' );
			const $messageText = $( '#charitable-test-email-message-text' );
			const btnLabel = $btn.text();

			if ( typeof charitable_email_diagnostics !== "undefined" ) {

				// Show loading state
				$btn.prop( 'disabled', true ).text( charitable_email_diagnostics.sending_text || 'Sending...' );
				$messageDiv.hide();

				// Remove any previous notices
				$btn.parent().find( '.charitable-notice' ).not( '#charitable-test-email-message .charitable-notice' ).remove();

				const data = {
					action: 'charitable_send_test_email',
					nonce: charitable_email_diagnostics.test_email_nonce
				};

				// Send test email via AJAX
				$.post( charitable_email_diagnostics.ajax_url, data, function( response ) {

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( response );
					}

					if ( response.success && response.data.success ) {
						// Show success message
						$messageText.text( response.data.message );
						$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-error' ).addClass( 'notice-success' );
						$messageDiv.show();

					} else {
						// Show error message
						let errorMsg = 'Test email failed to send.';
						if ( response.data && response.data.message ) {
							errorMsg = response.data.message;
						}

						$messageText.text( errorMsg );
						$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-success' ).addClass( 'notice-error' );
						$messageDiv.show();
					}

					// Restore button
					$btn.prop( 'disabled', false ).text( btnLabel );

				} ).fail( function( xhr, status, error ) {
					// Handle AJAX failure
					const errorMsg = 'Test email request failed. Please try again. If problems persist, contact support.';

					$messageText.text( errorMsg );
					$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-success' ).addClass( 'notice-error' );
					$messageDiv.show();

					$btn.prop( 'disabled', false ).text( btnLabel );

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( { xhr: xhr, status: status, error: error } );
					}
				} );

			} else {
				// Show error if localized script not available
				$messageText.text( 'Test email functionality not properly loaded. Please refresh the page and try again.' );
				$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-success' ).addClass( 'notice-error' );
				$messageDiv.show();
			}
		},

		/**
		 * Copy email diagnostics to clipboard.
		 *
		 * @since 1.8.9.2
		 */
		copyEmailDiagnostics() {
			const $textarea = $( '#charitable-email-diagnostics-results textarea' );
			if ( $textarea.length ) {
				$textarea.select();
				document.execCommand( 'copy' );

				// Show temporary feedback
				const $copyBtn = $( '#charitable-email-diagnostics-copy' );
				const originalText = $copyBtn.text();
				$copyBtn.text( 'Copied!' );
				setTimeout( function() {
					$copyBtn.text( originalText );
				}, 2000 );
			}
		},

		/**
		 * Toggle error logs visibility.
		 *
		 * @since 1.8.9.2
		 */
		toggleErrorLogs() {
			const $btn = $( '#charitable-toggle-error-logs' );
			const $container = $( '#charitable-error-logs-container' );

			if ( $container.is( ':visible' ) ) {
				$container.hide();
				$btn.text( 'Show Error Logs' );
			} else {
				$container.show();
				$btn.text( 'Hide Error Logs' );
			}
		},

		/**
		 * Clear error logs.
		 *
		 * @since 1.8.9.2
		 */
		clearErrorLogs() {
			const $btn = $( '#charitable-clear-error-logs' );
			const $messageDiv = $( '#charitable-error-log-message' );
			const $messageText = $( '#charitable-error-log-message-text' );
			const btnLabel = $btn.text();

			if ( ! confirm( 'Are you sure you want to clear all error logs? This action cannot be undone.' ) ) {
				return;
			}

			if ( typeof charitable_admin_tools !== "undefined" && typeof charitable_admin_tools.ajax_url !== "undefined" && typeof charitable_admin_tools.nonce !== "undefined" ) {

				// Show loading state
				$btn.prop( 'disabled', true ).text( 'Clearing...' );
				$messageDiv.hide();

				const data = {
					action: 'charitable_clear_error_logs',
					nonce: charitable_admin_tools.nonce
				};

				// Clear logs via AJAX
				$.post( charitable_admin_tools.ajax_url, data, function( response ) {

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( response );
					}

					if ( response.success ) {
						// Show success message and reload page
						$messageText.text( response.data.message );
						$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-error' ).addClass( 'notice-success' );
						$messageDiv.show();

						// Reload page after 2 seconds to show updated logs
						setTimeout( function() {
							location.reload();
						}, 2000 );

					} else {
						// Show error message
						let errorMsg = 'Failed to clear error logs.';
						if ( response.data && response.data.message ) {
							errorMsg = response.data.message;
						}

						$messageText.text( errorMsg );
						$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-success' ).addClass( 'notice-error' );
						$messageDiv.show();
					}

					// Restore button
					$btn.prop( 'disabled', false ).text( btnLabel );

				} ).fail( function( xhr, status, error ) {
					// Handle AJAX failure
					const errorMsg = 'Clear request failed. Please try again.';

					$messageText.text( errorMsg );
					$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-success' ).addClass( 'notice-error' );
					$messageDiv.show();

					$btn.prop( 'disabled', false ).text( btnLabel );

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( { xhr: xhr, status: status, error: error } );
					}
				} );

			} else {
				alert( 'Error log clearing not properly loaded. Please refresh the page and try again.' );
			}
		},

		/**
		 * Export error logs.
		 *
		 * @since 1.8.9.2
		 */
		exportErrorLogs() {
			const $btn = $( '#charitable-export-error-logs' );
			const $messageDiv = $( '#charitable-error-log-message' );
			const $messageText = $( '#charitable-error-log-message-text' );
			const btnLabel = $btn.text();

			if ( typeof charitable_admin_tools !== "undefined" && typeof charitable_admin_tools.ajax_url !== "undefined" && typeof charitable_admin_tools.nonce !== "undefined" ) {

				// Show loading state
				$btn.prop( 'disabled', true ).text( 'Exporting...' );
				$messageDiv.hide();

				const data = {
					action: 'charitable_export_error_logs',
					nonce: charitable_admin_tools.nonce
				};

				// Export logs via AJAX
				$.post( charitable_admin_tools.ajax_url, data, function( response ) {

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( response );
					}

					if ( response.success && response.data.content ) {
						// Create and download CSV file
						const csvContent = atob( response.data.content );
						const blob = new Blob( [csvContent], { type: 'text/csv;charset=utf-8;' } );
						const link = document.createElement( 'a' );

						if ( link.download !== undefined ) {
							const url = URL.createObjectURL( blob );
							link.setAttribute( 'href', url );
							link.setAttribute( 'download', response.data.filename || 'error-logs.csv' );
							link.style.visibility = 'hidden';
							document.body.appendChild( link );
							link.click();
							document.body.removeChild( link );
						}

						// Show success message
						$messageText.text( response.data.message );
						$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-error' ).addClass( 'notice-success' );
						$messageDiv.show();

					} else {
						// Show error message
						let errorMsg = 'Failed to export error logs.';
						if ( response.data && response.data.message ) {
							errorMsg = response.data.message;
						}

						$messageText.text( errorMsg );
						$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-success' ).addClass( 'notice-error' );
						$messageDiv.show();
					}

					// Restore button
					$btn.prop( 'disabled', false ).text( btnLabel );

				} ).fail( function( xhr, status, error ) {
					// Handle AJAX failure
					const errorMsg = 'Export request failed. Please try again.';

					$messageText.text( errorMsg );
					$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-success' ).addClass( 'notice-error' );
					$messageDiv.show();

					$btn.prop( 'disabled', false ).text( btnLabel );

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( { xhr: xhr, status: status, error: error } );
					}
				} );

			} else {
				alert( 'Error log export not properly loaded. Please refresh the page and try again.' );
			}
		},

		/**
		 * Run debug log scan.
		 *
		 * @since 1.8.9.3
		 */
		runDebugLogScan() {
			const $btn = $( '#charitable-debug-log-scan' );
			const $results = $( '#charitable-debug-log-results' );
			const $messageDiv = $( '#charitable-debug-log-message' );
			const $messageText = $( '#charitable-debug-log-message-text' );
			const btnLabel = $btn.text();
			const btnWidth = $btn.outerWidth();

			if ( typeof charitable_admin_tools !== "undefined" && typeof charitable_admin_tools.ajax_url !== "undefined" && typeof charitable_admin_tools.nonce !== "undefined" ) {

				// Show loading state
				$btn.css( 'width', btnWidth ).prop( 'disabled', true ).text( 'Scanning logs...' );
				$results.show();
				$( '.charitable-format-content' ).val( 'Scanning debug logs, please wait...' );
				$messageDiv.hide();

				// Remove any previous notices
				$btn.parent().find( '.charitable-notice' ).remove();

				const data = {
					action: 'charitable_debug_log_scan',
					nonce: charitable_admin_tools.nonce
				};

				// Run scan via AJAX
				$.post( charitable_admin_tools.ajax_url, data, function( response ) {

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( response );
					}

					if ( response.success && response.data.scan_results ) {
						// Populate different format views
						const results = response.data.scan_results;
						$( '#charitable-debug-summary' ).val( results.summary || 'No summary available.' );
						$( '#charitable-debug-technical' ).val( results.technical || 'No technical details available.' );
						$( '#charitable-debug-forum' ).val( results.forum_ready || 'No forum format available.' );

						// Show success notice
						$messageText.text( 'Debug log scan completed successfully.' );
						$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-error' ).addClass( 'notice-success' );
						$messageDiv.show();

						// Initialize tab system
						app.switchDebugFormat( $( '.charitable-format-tab[data-format="summary"]' ) );

					} else {
						// Show error
						let errorMsg = 'Debug log scan failed.';
						let detailedMsg = '';

						if ( response.data && response.data.message ) {
							errorMsg = response.data.message;
						}

						if ( response.data && response.data.detailed_message ) {
							detailedMsg = response.data.detailed_message;
						}

						// Show detailed error message in text areas
						const displayMsg = detailedMsg ? detailedMsg : ('### DEBUG LOG SCAN ERROR ###\n\n' + errorMsg);
						$( '.charitable-format-content' ).val( displayMsg );

						// Show basic error in notice
						$messageText.text( errorMsg );
						$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-success' ).addClass( 'notice-error' );
						$messageDiv.show();
					}

					// Restore button
					$btn.css( 'width', btnWidth ).prop( 'disabled', false ).text( btnLabel );

				} ).fail( function( xhr, status, error ) {
					// Handle AJAX failure
					const errorMsg = 'Debug log scan request failed: ' + status + ' - ' + error;
					$( '.charitable-format-content' ).val( '### DEBUG LOG SCAN ERROR ###\n\n' + errorMsg );

					$messageText.text( 'Scan request failed. Please try again.' );
					$messageDiv.find( '.charitable-notice' ).removeClass( 'notice-success' ).addClass( 'notice-error' );
					$messageDiv.show();

					$btn.css( 'width', btnWidth ).prop( 'disabled', false ).text( btnLabel );

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( { xhr: xhr, status: status, error: error } );
					}
				} );

			} else {
				alert( 'Debug log scanner not properly loaded. Please refresh the page and try again.' );
			}
		},

		/**
		 * Switch debug log format display.
		 *
		 * @since 1.8.9.3
		 * @param {jQuery} $clickedTab The clicked tab element.
		 */
		switchDebugFormat( $clickedTab ) {
			const format = $clickedTab.data( 'format' );

			// Update tab states
			$( '.charitable-format-tab' ).removeClass( 'active' );
			$clickedTab.addClass( 'active' );

			// Show/hide content areas
			$( '.charitable-format-content' ).hide();
			$( '#charitable-debug-' + format ).show();
		},

		/**
		 * Copy debug log results to clipboard.
		 *
		 * @since 1.8.9.3
		 */
		copyDebugResults() {
			const $activeContent = $( '.charitable-format-content:visible' );
			const $copyMessage = $( '#charitable-debug-copy-message' );

			if ( $activeContent.length > 0 ) {
				$activeContent.select();
				document.execCommand( 'copy' );

				// Show copy confirmation
				$copyMessage.fadeIn( 200 ).delay( 1500 ).fadeOut( 200 );
			}
		}
    };

	return app;
}( document, window, jQuery ) );

// Initialize.
CharitableSystemInfo.init();
