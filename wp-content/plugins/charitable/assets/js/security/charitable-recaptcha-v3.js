/**
 * Google reCAPTCHA v3 Handler for Charitable
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WPCharitable
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.9
 * @version   1.8.9
 */

( function( $ ) {
	'use strict';

	/**
	 * Initialize reCAPTCHA v3 when Google's script is ready.
	 */
	if ( typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready !== 'undefined' ) {
		grecaptcha.ready( function() {
			initializeRecaptchaV3();
		} );
	} else {
		// Fallback if grecaptcha.ready is not available.
		$( document ).ready( function() {
			if ( typeof grecaptcha !== 'undefined' ) {
				initializeRecaptchaV3();
			}
		} );
	}

	/**
	 * Initialize reCAPTCHA v3 for all forms.
	 */
	function initializeRecaptchaV3() {
		if ( typeof CHARITABLE_RECAPTCHA_V3 === 'undefined' ) {
			return;
		}

		var siteKey = CHARITABLE_RECAPTCHA_V3.site_key;
		var action  = CHARITABLE_RECAPTCHA_V3.action || 'charitable_donation';
		var errorMessage = CHARITABLE_RECAPTCHA_V3.error_message || 'Your form submission failed because the captcha failed to be validated.';

		/**
		 * Handle donation form submissions.
		 */
		$( 'body' ).on( 'charitable:form:validate', function( event, helper ) {
			if ( ! helper || helper.errors.length > 0 ) {
				return;
			}

			// Generate token with action.
			grecaptcha.execute( siteKey, { action: action } ).then( function( token ) {
				// Add token to form.
				var tokenInput = $( '<input>' )
					.attr( 'type', 'hidden' )
					.attr( 'name', 'charitable_recaptcha_v3_token' )
					.val( token );

				// Remove existing token input if present.
				$( '[name="charitable_recaptcha_v3_token"]' ).remove();

				// Add token to form.
				$( helper.form ).append( tokenInput );

				// Remove pending process.
				helper.remove_pending_process_by_name( 'recaptcha_v3' );
			} ).catch( function( error ) {
				// Handle error.
				helper.add_error( errorMessage );
				helper.remove_pending_process_by_name( 'recaptcha_v3' );
			} );

			// Add pending process.
			helper.add_pending_process( 'recaptcha_v3' );
		} );

		/**
		 * Handle regular form submissions (non-donation forms).
		 */
		$( 'form' ).not( '.charitable-donation-form' ).on( 'submit', function( e ) {
			var $form = $( this );
			var formKey = getFormKey( $form );

			// Check if this form should have reCAPTCHA v3.
			if ( ! shouldHaveRecaptcha( formKey ) ) {
				return;
			}

			// Prevent default submission.
			e.preventDefault();

			// Generate token.
			grecaptcha.execute( siteKey, { action: action } ).then( function( token ) {
				// Add token to form.
				var tokenInput = $( '<input>' )
					.attr( 'type', 'hidden' )
					.attr( 'name', 'charitable_recaptcha_v3_token' )
					.val( token );

				// Remove existing token input if present.
				$form.find( '[name="charitable_recaptcha_v3_token"]' ).remove();

				// Add token to form.
				$form.append( tokenInput );

				// Submit form.
				$form.off( 'submit' ).submit();
			} ).catch( function( error ) {
				// Handle error.
				alert( errorMessage );
			} );
		} );
	}

	/**
	 * Get form key based on form class or ID.
	 */
	function getFormKey( $form ) {
		if ( $form.hasClass( 'charitable-registration-form' ) ) {
			return 'registration_form';
		}
		if ( $form.hasClass( 'charitable-profile-form' ) ) {
			return 'profile_form';
		}
		if ( $form.hasClass( 'charitable-forgot-password-form' ) ) {
			return 'password_retrieval_form';
		}
		if ( $form.hasClass( 'charitable-reset-password-form' ) ) {
			return 'password_reset_form';
		}
		if ( $form.hasClass( 'charitable-campaign-form' ) ) {
			return 'campaign_form';
		}
		return null;
	}

	/**
	 * Check if form should have reCAPTCHA v3.
	 * This is a simplified check - the server-side validation is the authoritative check.
	 */
	function shouldHaveRecaptcha( formKey ) {
		// Default enabled forms.
		var enabledForms = {
			'donation_form': true,
			'registration_form': true,
			'password_reset_form': true,
			'password_retrieval_form': true,
			'campaign_form': true,
		};

		return formKey && enabledForms[ formKey ];
	}

} )( jQuery );

