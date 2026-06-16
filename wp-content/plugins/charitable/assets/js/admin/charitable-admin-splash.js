/* global charitable_admin_splash_data, ajaxurl */
/**
 * Charitable Admin Splash.
 *
 * @since 1.8.6
 */
const CharitableAdminSplash = window.CharitableAdminSplash || ( function( document, window, $ ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.6
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Initialize.
		 *
		 * @since 1.8.6
		 */
		init() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.8.6
		 */
		ready() {
			app.events();

			if ( charitable_admin_splash_data.triggerForceOpen ) {
				app.openModal();
			}
		},

		/**
		 * Events.
		 *
		 * @since 1.8.6
		 */
		events() {
			$( document )
				.on( 'click', '.charitable-splash-modal-open', function( e ) {
					e.preventDefault();
					app.openModal();
				} );
		},

		/**
		 * Open the modal.
		 *
		 * @since 1.8.6
		 */
		openModal() {
			$.alert( {
				title: false,
				content: wp.template( 'charitable-splash-modal-content' )(),
				icon: false,
				closeIcon: true,
				boxWidth: '1000px',
				theme: 'modern',
				useBootstrap: false,
				scrollToPreviousElement: false,
				buttons: false,
				backgroundDismiss: true,
				offsetTop: 50,
				offsetBottom: 50,
				animation: 'opacity',
				closeAnimation: 'opacity',
				animateFromElement: false,
				onOpenBefore() {
					const scrollbarWidth = ( window.innerWidth - document.body.clientWidth ) + 'px';

					$( 'body' )
						.addClass( 'charitable-splash-modal' )
						.css( '--charitable-body-scrollbar-width', scrollbarWidth );

					$( '.charitable-challenge-popup-container' ).addClass( 'charitable-invisible' );

					setTimeout( () => {
						if ( navigator.userAgent.includes( 'Safari' ) && ! navigator.userAgent.includes( 'Chrome' ) ) {
							$( 'html, body' ).animate( { scrollTop: 0 }, 0 );
						}

						$( '.jconfirm-box-container' )
							.css( 'padding-top', '50px' )
							.animate( { opacity: 1 }, 30 );
					}, 0 );
				},
				onOpen() {
					$( '.jconfirm' ).css( 'bottom', 0 );
					$( '.charitable-dash-widget-welcome-block' ).remove();
				},
				onDestroy() {
					$( 'body' )
						.removeClass( 'charitable-splash-modal' )
						.css( '--charitable-body-scrollbar-width', null );
				},
			} );
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

CharitableAdminSplash.init();
