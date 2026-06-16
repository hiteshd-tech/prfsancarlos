/**
 * Charitable External Banner
 *
 * Handles positioning, display, and dismissal of Charitable banners
 * on non-Charitable admin screens.
 *
 * @since 1.8.10
 */
( function( $ ) {

	$( document ).ready( function() {

		var $banner = $( '.charitable-external-banner' );

		if ( ! $banner.length ) {
			return;
		}

		// Reposition the banner before the main content area.
		// GiveWP React pages use root divs like #give-admin-campaigns-root.
		// Traditional pages use .wrap containers.
		var $target = $( '#wpbody-content' ).children( '[id^="give-admin-"], [id="reports-app"], .wrap' ).first();

		if ( $target.length ) {
			$banner.detach().insertBefore( $target );
		} else {
			// Fallback: prepend to #wpbody-content.
			$banner.detach().prependTo( '#wpbody-content' );
		}

		// Slide the banner down.
		var bannerId = $banner.data( 'banner-id' );
		var seenKey  = 'charitable-ext-banner-' + bannerId + '-seen';

		if ( window.localStorage.getItem( seenKey ) ) {
			$banner.show();
		} else {
			setTimeout( function() {
				window.localStorage.setItem( seenKey, true );
				$banner.slideDown( 300 );
			}, 800 );
		}

		// Dismiss handler.
		$banner.on( 'click', '.charitable-external-banner-dismiss', function( e ) {
			e.preventDefault();

			var nonce = $banner.data( 'nonce' );

			$.ajax( {
				type: 'POST',
				url: charitable_external_banner.ajaxurl,
				data: {
					action: 'charitable_dismiss_external_banner',
					banner_id: bannerId,
					nonce: nonce,
				},
				dataType: 'json',
				success: function() {
					$banner.slideUp( 'fast', function() {
						$banner.remove();
					} );
					window.localStorage.removeItem( seenKey );
				},
			} );
		} );
	} );

} )( jQuery );
