var charitable_reCAPTCHA_onload = function() {

	( function( $ ) {
		var $body      = $( 'body' );
		var $recaptcha = $( '.charitable-recaptcha' );
		var input      = $recaptcha.parent().find( '[name=charitable_grecaptcha_token]')[0];
		var form       = input.form;
		var recaptcha_id;

		/**
		 * For donation form submissions, execute reCAPTCHA as part of the
		 * validation process, before firing off the rest of the donation
		 * form processing.
		 */
		var donation_form_handler = function() {
			var helper,
				executed = false;

			recaptcha_id = grecaptcha.render( $recaptcha[0], {
				'sitekey' : CHARITABLE_RECAPTCHA.site_key,
				'callback' : function( token ) {
					input.setAttribute( 'value', token );
					helper.remove_pending_process_by_name( 'recaptcha' );
				},
				'error-callback' : function() {
					helper.add_error( CHARITABLE_RECAPTCHA.error_message );
					helper.remove_pending_process_by_name( 'recaptcha' );
				},
				'expired-callback' : function() {
					input.setAttribute( 'value', null );
					helper.remove_pending_process_by_name( 'recaptcha' );
				},
				'size' : 'invisible',
				'isolated' : true,
			} );

			$body.on( 'charitable:form:validate', function( event, target ) {
				helper = target;

				if ( helper.errors.length === 0 ) {
					if ( executed ) {
						grecaptcha.reset( recaptcha_id );
					}

					helper.add_pending_process( 'recaptcha' );

					grecaptcha.execute( recaptcha_id );

					executed = true;
				}
			} );
		}

		/**
		 * For regular form submissions (not the donation form), execute reCAPTCHA
		 * when the form is submitted.
		 * */
		var default_form_handler = function() {
			var submitting = false;

			recaptcha_id = grecaptcha.render( $recaptcha[0], {
				'sitekey' : CHARITABLE_RECAPTCHA.site_key,
				'callback' : function( token ) {
					input.setAttribute( 'value', token );
					form.submit();
				},
				'error-callback' : function() {
					if ( submitting ) {
						alert( CHARITABLE_RECAPTCHA.error_message );
						submitting = false;
					}
				},
				'size' : 'invisible',
				'isolated' : true,
			} );

			form.onsubmit = function() {
				submitting = true;
				grecaptcha.execute( recaptcha_id );
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

