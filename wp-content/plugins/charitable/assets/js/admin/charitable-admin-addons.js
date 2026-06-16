/**
 * Charitable Admin Addons functionality.
 *
 * @package   Charitable/Admin Scripts
 * @author    David Bisset
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7.6
 * @version   1.8.7.6
 */

( function( $ ) {
	'use strict';

	// Global settings access.
	var s;

	// Charitable Admin Addons object.
	var CharitableAdminAddons = {

		// Settings.
		settings: {
			iconActivate: '<i class="fa fa-toggle-on fa-flip-horizontal" aria-hidden="true"></i>',
			iconDeactivate: '<i class="fa fa-toggle-on" aria-hidden="true"></i>',
			iconInstall: '<i class="fa fa-cloud-download" aria-hidden="true"></i>',
			iconSpinner: '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>',
		},

		/**
		 * Start the engine.
		 *
		 * @since 1.8.7.6
		 */
		init: function() {

			// Settings shortcut.
			s = this.settings;

			// Document ready.
			$( CharitableAdminAddons.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.8.7.6
		 */
		ready: function() {

			// Only run on the addons page.
			if ( ! $( '#charitable-admin-addons' ).length ) {
				return;
			}

			// Addon button clicks.
			$( document ).on( 'click', '#charitable-admin-addons .action-button button', CharitableAdminAddons.handleAddonAction );
		},

		/**
		 * Handle addon action button clicks.
		 *
		 * @since 1.8.7.6
		 */
		handleAddonAction: function( e ) {
			e.preventDefault();

			var $btn = $( this );
			var $footer = $btn.parents( '.addon-item' );
			var classes = {
				active: 'status-active',
				activating: 'status-activating',
				incompatible: 'status-incompatible',
				installed: 'status-installed',
				missing: 'status-missing',
				goToUrl: 'status-go-to-url',
				withError: 'status-with-error',
			};

			// Open url in new tab.
			if ( $footer.hasClass( classes.goToUrl ) ) {
				window.open( $btn.attr( 'data-plugin' ), '_blank' );
				return;
			}

			$btn.prop( 'disabled', true );

			var checked = $btn.is( ':checked' );
			var cssClass;
			var plugin = $btn.attr( 'data-plugin' );
			var pluginType = $btn.attr( 'data-type' );
			var $addon = $btn.parents( '.addon-item' );
			var state = CharitableAdminAddons.getAddonState( $footer, classes, $btn );

			// Update button state.
			CharitableAdminAddons.updateAddonButton( $btn, state, true );

			// Make AJAX call.
			CharitableAdminAddons.setAddonState( plugin, state, pluginType, function( res ) {
				CharitableAdminAddons.handleAddonStateResponse( res, $addon, $btn, state, pluginType, cssClass );
			}, function( xhr ) {
				CharitableAdminAddons.handleAddonStateError( xhr, $addon, $btn, state );
			} );
		},

		/**
		 * Get addon state based on current classes and button state.
		 *
		 * @since 1.8.7.6
		 */
		getAddonState: function( $footer, classes, $btn ) {
			// Prefer footer-level state when present.
			if ( $footer.hasClass( classes.missing ) ) {
				return 'install';
			}
			if ( $footer.hasClass( classes.installed ) ) {
				return 'activate';
			}
			if ( $footer.hasClass( classes.active ) ) {
				return 'deactivate';
			}
			if ( $footer.hasClass( classes.incompatible ) ) {
				return 'incompatible';
			}

			// Fallback to button classes when footer has no state classes.
			if ( $btn.hasClass( 'status-missing' ) ) {
				return 'install';
			}
			if ( $btn.hasClass( 'status-installed' ) ) {
				return 'activate';
			}
			if ( $btn.hasClass( 'status-active' ) ) {
				return 'deactivate';
			}

			return 'install';
		},

		/**
		 * Update addon button state.
		 *
		 * @since 1.8.7.6
		 */
		updateAddonButton: function( $btn, state, loading ) {
			var text = '';
			var cssClass = '';

			if ( loading ) {
				if ( state === 'install' ) {
					text = s.iconSpinner + ' Installing...';
				} else if ( state === 'deactivate' ) {
					text = s.iconSpinner + ' Deactivating...';
				} else {
					text = s.iconSpinner + ' Activating...';
				}
				cssClass = 'button button-secondary loading';
			} else {
				switch ( state ) {
					case 'install':
						text = s.iconInstall + ' Install Plugin';
						cssClass = 'status-missing button button-primary';
						break;
					case 'activate':
						text = s.iconActivate + ' Activate';
						cssClass = 'status-installed button button-secondary';
						break;
					case 'deactivate':
						text = s.iconDeactivate + ' Deactivate';
						cssClass = 'status-active button button-secondary';
						break;
					case 'incompatible':
						text = 'Incompatible';
						cssClass = 'status-incompatible button button-secondary disabled';
						break;
				}
			}

			$btn.removeClass().addClass( cssClass ).html( text );
		},

		/**
		 * Set addon state via AJAX.
		 *
		 * @since 1.8.7.6
		 */
		setAddonState: function( plugin, state, pluginType, callback, errorCallback ) {
			var actions = {
				activate: 'charitable_addons_activate',
				install: 'charitable_addons_install_wporg',
				deactivate: 'charitable_addons_deactivate',
				incompatible: 'charitable_addons_activate',
			};
			var action = actions[ state ];

			if ( ! action ) {
				return;
			}

			var data = {
				action: action,
				nonce: charitable_admin_addons.nonce,
				plugin: plugin,
				type: pluginType,
			};

			$.post( charitable_admin_addons.ajax_url, data, function( res ) {
				callback( res );
			} ).fail( function( xhr ) {
				errorCallback( xhr );
			} );
		},

		/**
		 * Handle successful addon state response.
		 *
		 * @since 1.8.7.6
		 */
		handleAddonStateResponse: function( res, $addon, $btn, state, pluginType, cssClass ) {
			$btn.prop( 'disabled', false );
			$btn.removeClass( 'loading' );

			if ( res.success ) {
				var successText = '';
				var stateText = '';
				var buttonText = '';

				if ( 'install' === state ) {
					$btn.attr( 'data-plugin', res.data.basename );
					successText = res.data.msg;
					if ( ! res.data.is_activated ) {
						stateText = 'Inactive';
						buttonText = 'plugin' === pluginType ? 'Activate' : s.iconActivate + ' Activate';
						cssClass = 'plugin' === pluginType ? 'status-installed button button-secondary' : 'status-installed';
					}
				} else {
					successText = res.data;
					// Reflect new state in UI.
					if ( state === 'activate' ) {
						stateText = 'Active';
						buttonText = pluginType === 'plugin' ? 'Deactivate' : s.iconDeactivate + ' Deactivate';
						cssClass = pluginType === 'plugin' ? 'status-active button button-secondary' : 'status-active';
					} else if ( state === 'deactivate' ) {
						stateText = 'Inactive';
						buttonText = pluginType === 'plugin' ? 'Activate' : s.iconActivate + ' Activate';
						cssClass = pluginType === 'plugin' ? 'status-installed button button-secondary' : 'status-installed';
					}
				}

				$addon.find( '.actions' ).append( '<div class="msg success">' + successText + '</div>' );
				$addon.find( 'span.status-label' )
					.removeClass( 'status-active status-installed status-missing' )
					.addClass( cssClass )
					.removeClass( 'button button-primary button-secondary disabled' )
					.text( stateText );
				$btn
					.removeClass( 'status-active status-installed status-missing' )
					.removeClass( 'button button-primary button-secondary disabled loading' )
					.addClass( cssClass ).html( buttonText );
			} else {
				if ( 'object' === typeof res.data ) {
					if ( pluginType === 'addon' ) {
						$addon.find( '.actions' ).append( '<div class="msg error"><p>Error installing addon</p></div>' );
					} else {
						$addon.find( '.actions' ).append( '<div class="msg error"><p>' + res.data + '</p></div>' );
					}
				} else {
					$addon.find( '.actions' ).append( '<div class="msg error"><p>' + res.data + '</p></div>' );
				}
				$btn.removeClass( 'loading' );
				CharitableAdminAddons.updateAddonButton( $btn, state, false );
			}

			// Remove success/error messages after 3 seconds.
			setTimeout( function() {
				$addon.find( '.msg' ).fadeOut( 300, function() {
					$( this ).remove();
				} );
			}, 3000 );
		},

		/**
		 * Handle addon state error.
		 *
		 * @since 1.8.7.6
		 */
		handleAddonStateError: function( xhr, $addon, $btn, state ) {
			$btn.prop( 'disabled', false );
			$addon.find( '.actions' ).append( '<div class="msg error"><p>Network error. Please try again.</p></div>' );
			CharitableAdminAddons.updateAddonButton( $btn, state, false );

			// Remove error message after 3 seconds.
			setTimeout( function() {
				$addon.find( '.msg' ).fadeOut( 300, function() {
					$( this ).remove();
				} );
			}, 3000 );
		},
	};

	// Initialize when document is ready.
	$( document ).ready( function() {
		CharitableAdminAddons.init();
	} );

} )( jQuery );
