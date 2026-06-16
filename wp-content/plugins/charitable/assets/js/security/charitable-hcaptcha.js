var charitable_hcaptcha_onload = function() {
	( function( $ ) {
		var $captcha = $( '.charitable-hcaptcha' ),
			input    = $captcha.parent().find( '[name=hcaptcha_token]' )[0],
			form     = input.form,
			captcha_id,
			helper;

		/**
		 * For donation form submissions, execute hcaptcha as part of the
		 * validation process, before firing off the rest of the donation
		 * form processing.
		 */
		var donation_form_handler = function() {
			var helper;

			captcha_id = hcaptcha.render( $captcha.attr( 'id' ), {
				'size': 'invisible',
				'callback': function( token ) {
					input.setAttribute( 'value', token );
					helper.remove_pending_process_by_name( 'hcaptcha' );
				}
			} );

			$( 'body' ).on( 'charitable:form:validate', function( event, target ) {
				helper = target;

				if ( helper.errors.length === 0 ) {
					helper.add_pending_process( 'hcaptcha' );
					hcaptcha.execute( captcha_id );
				}
			} );
		}

		/**
		 * For regular form submissions (not the donation form), execute hcaptcha
		 * when the form is submitted.
		 */
		var default_form_handler = function() {
			captcha_id = hcaptcha.render( $captcha.attr( 'id' ), {
				'size': 'invisible',
				'callback': function( token ) {
					input.setAttribute( 'value', token );
					form.submit();
				}
			} );

			form.onsubmit = function() {
				hcaptcha.execute( captcha_id );
				return false;
			}
		}

		if ( form.classList.contains( 'charitable-donation-form' ) ) {
			donation_form_handler();
		} else {
			default_form_handler();
		}
	})( jQuery );
}

