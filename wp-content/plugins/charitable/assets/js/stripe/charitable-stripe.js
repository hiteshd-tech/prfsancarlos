( function( $ ) {

	var $body = $( 'body' );
	var stripe;

	/**
	 * Return the connect charge owner.
	 */
	var get_stripe_connect_charge_owner = function( helper ) {
		var owner = helper.get_input( 'connect_charge_owner' );
		return owner.length ? owner.val() : null;
	};

	/**
	 * Return the connected account.
	 */
	var get_stripe_connected_account = function( helper ) {
		var account = helper.get_input( 'connected_account' );
		return account.length ? account.val() : null;
	};

	/**
	 * Whether to process the payment intent on the connected account.
	 */
	var should_process_payment_on_connected_account = function( helper ) {
		return 'direct' === get_stripe_connect_charge_owner( helper )
			|| ( 'platform' === get_stripe_connect_charge_owner( helper ) && helper.is_recurring_donation() )
	};

	/**
	 * Get the Stripe options.
	 */
	var get_stripe_options = function( helper, force ) {
		return 'direct' === get_stripe_connect_charge_owner( helper ) || force ? { 'stripeAccount': get_stripe_connected_account( helper ) } : null;
	};

	/**
	 * Get Stripe destination accout.
	 */
	var get_stripe_destination = function( helper ) {
		return 'platform' === get_stripe_connect_charge_owner( helper ) ? get_stripe_connected_account( helper ) : null;
	};

	/**
	 * Handle Stripe Checkout donations.
	 */
	var Checkout_Handler = function() {
		// Init Stripe object.
		stripe = Stripe( CHARITABLE_STRIPE_VARS.key );

		$body.on(
			'charitable:form:processed',
			function( event, response, helper ) {

				// Donation failed.
				if ( ! response.success ) {
					return;
				}

				// This is not the main donation form, so skip processing.
				if ( 'make_donation' !== helper.get_input( 'charitable_action' ).val() ) {
					return;
				}

				// If we're not using Stripe, do not process any further
				if ( 'stripe' !== helper.get_payment_method() ) {
					return;
				}

				event.preventDefault();

				helper.add_pending_process( 'stripe' );

				// The payment intent should be processed on the connected account.
				if ( should_process_payment_on_connected_account( helper ) ) {
					new_stripe = Stripe( CHARITABLE_STRIPE_VARS.key, get_stripe_options( helper, true ) );
				} else {
					new_stripe = stripe;
				}

				new_stripe.redirectToCheckout( {
					sessionId: response.session_id
				  } ).then( function ( result ) {
						helper.add_error( result.error.message );
						helper.remove_pending_process_by_name( 'stripe' );
				  } );
			}
		);
	}

	/**
	 * Handle Stripe Payment Intents donations.
	 */
	function Payment_Intents_Handler() {
		// Init Stripe object.
		stripe = Stripe( CHARITABLE_STRIPE_VARS.key );

		var elements     = stripe.elements();
		var card_element = elements.create( 'card' );
		var $card        = $( '#charitable_stripe_card_field' );
		var $errors      = $( '#charitable_stripe_card_errors' );
		var $card_name   = $( '.charitable-donation-form [data-input=cc_name]' );
		var $postcode    = $( '.charitable-donation-form [name=postcode]' );

		card_element.mount( '#charitable_stripe_card_field' );

		/**
		 * Display card errors.
		 */
		card_element.on( 'change', function( event ) {
			$errors.text( event.error ? event.error.message : '' );
		} );

		/**
		 * Set the zip code field in the card element to the same as
		 * the postal code field used above.
		 */
		if ( $postcode.length ) {
			$postcode.on( 'change', function( event ) {
				card_element.update( { value : { postalCode: event.target.value } } );
			} ).trigger( 'change' );
		}

		/**
		 * Set up styling for the Stripe card field.
		 */
		var setup_adaptive_styling = ( function() {
			var current_height = $card.height(),
				new_height = $card_name.innerHeight(),
				v_padding = ( new_height - current_height ) / 2;

			$card.css( {
				paddingTop: v_padding,
				paddingRight: $card_name.css( 'paddingRight' ),
				paddingBottom: v_padding,
				paddingLeft: $card_name.css( 'paddingLeft' ),
				border: $card_name.css( 'border' ),
				borderRadius: $card_name.css( 'borderRadius' ),
				background: $card_name.css( 'background' )
			} );
		} )();

		/**
		 * On validation, add the donor's payment method to Stripe.
		 */
		$body.on(
			'charitable:form:validate',
			function( event, helper ) {
				// This is not the main donation form, so skip processing.
				if ( 'make_donation' !== helper.get_input( 'charitable_action' ).val() ) {
					return;
				}

				// If we're not using Stripe, do not process any further
				if ( 'stripe' !== helper.get_payment_method() ) {
					return;
				}

				event.preventDefault();

				// Check if our card errors field has errors in it.
				if ( '' !== $errors.text() ) {
					if ( helper.hasOwnProperty( 'prevent_scroll_to_top' ) ) {
						helper.prevent_scroll_to_top = true;
					}

					helper.add_error( $errors.text() );
					helper.hide_processing();
					return;
				}

				// If we have found no errors, handle card payment with Stripe.
				if ( helper.errors.length === 0 ) {

					// Pause further processing until we've handled Stripe response.
					helper.add_pending_process( 'stripe' );

					var email    = helper.get_email(),
						phone    = helper.get_input( 'phone' ),
						address  = ( function() {
							var address = {},
								address_fields = [
									{ field: 'city', key: 'city' },
									{ field: 'country', key: 'country' },
									{ field: 'address', key: 'line1' },
									{ field: 'address_2', key: 'line2' },
									{ field: 'postcode', key: 'postal_code' },
									{ field: 'state', key: 'state' },
								];

							address_fields.forEach( function( field ) {
								var input = helper.get_input( field.field );

								if ( input.length && input.val().length ) {
									address[field.key] = input.val();
								}
							} );

							return address;
						} )(),
						data     = {
							billing_details: ( function() {
								var billing = {
									name: $card_name.val(),
									email: email,
									address: address
								};

								if ( phone.length && phone.val().length ) {
									billing.phone = phone.val();
								}

								return billing;
							} )()
						};

					stripe.createPaymentMethod(
						'card',
						card_element,
						data
					).then( function( result ) {
						if ( result.error ) {
							if ( helper.hasOwnProperty( 'prevent_scroll_to_top' ) ) {
								helper.prevent_scroll_to_top = true;
							} else {
								helper.add_error( result.error.message );
							}
						} else {
							helper.get_input( 'stripe_payment_method' ).val( result.paymentMethod.id );
						}

						helper.remove_pending_process_by_name( 'stripe' );
					} );
				}
			}
		);

		/**
		 * After the donation form has been processed, handle card payment.
		 */
		$body.on(
			'charitable:form:processed',
			function( event, response, helper ) {
				if ( ! response.success ) {
					return;
				}

				// If this is a recurring donation and no further action is required, return true now.
				if ( helper.is_recurring_donation() && ! response.requires_action ) {
					return;
				}

				// This is not the main donation form, so skip processing.
				if ( 'make_donation' !== helper.get_input( 'charitable_action' ).val() ) {
					return;
				}

				// If we're not using Stripe, do not process any further
				if ( 'stripe' !== helper.get_payment_method() ) {
					return;
				}

				event.preventDefault();

				// If we have found no errors, handle card payment with Stripe.
				if ( helper.errors.length === 0 ) {

					var new_stripe;

					// Pause further processing until we've handled Stripe response.
					helper.add_pending_process( 'stripe' );

					// The payment intent should be processed on the connected account.
					if ( should_process_payment_on_connected_account( helper ) ) {
						new_stripe = Stripe( CHARITABLE_STRIPE_VARS.key, get_stripe_options( helper, true ) );
					} else {
						new_stripe = stripe;
					}

					new_stripe.handleCardPayment(
						response.secret
					).then( function( result ) {
						if ( result.error ) {
							/* allow third party plugin to potentially hook into this, starting with the Charitable Spam Blocker */
							$( document ).trigger( "Charitable_Custom_Event__Handle_Card_Payment_Fail", [ event, result, response, CHARITABLE_STRIPE_VARS, helper.get_payment_method() ] );
							helper.add_error( result.error.message );
						}

						helper.remove_pending_process_by_name( 'stripe' );
					} );
				}
			}
		);
	}

	/**
	 * Handle Stripe Payment Intents donations w/ multiple fields.
	 */
	function Payment_Intents_Handler_Multiple() {
		// Init Stripe object.
		stripe = Stripe( CHARITABLE_STRIPE_VARS.key );

		var elements     = stripe.elements();
		var $card        = $( '#charitable_stripe_card_field' );
		var $errors      = $( '#charitable_stripe_card_errors' );
		var $card_name   = $( '.charitable-donation-form [data-input=cc_name]' );
		var $postcode    = $( '.charitable-donation-form [name=postcode]' );

		var $card_field_name = $('#charitable_stripe_card_name_field');
		var $card_filed_number = $('#charitable_stripe_card_number_field');
		var $card_field_expiry = $('#charitable_stripe_card_expiration_field');
		var $card_field_cvc = $('#charitable_stripe_card_cvc_field');

		// Create an instance of the card Element for card number
		var cardNumber = elements.create('cardNumber');
		cardNumber.mount('#charitable_stripe_card_number_field');

		// Create an instance of the card expiry Element
		var cardExpiry = elements.create('cardExpiry');
		cardExpiry.mount('#charitable_stripe_card_expiration_field');

		// Create an instance of the card CVC Element
		var cardCvc = elements.create('cardCvc');
		cardCvc.mount('#charitable_stripe_card_cvc_field');

		/**
		 * Display card errors.
		 */
		cardNumber.on( 'change', function( event ) {
			$errors.text( event.error ? event.error.message : '' );
		} );
		cardExpiry.on( 'change', function( event ) {
			$errors.text( event.error ? event.error.message : '' );
		} );
		cardCvc.on( 'change', function( event ) {
			$errors.text( event.error ? event.error.message : '' );
		} );

		/**
		 * Set the zip code field in the card element to the same as
		 * the postal code field used above.
		 */
		// if ( $postcode.length ) {
		// 	$postcode.on( 'change', function( event ) {
		// 		card_element.update( { value : { postalCode: event.target.value } } );
		// 	} ).trigger( 'change' );
		// }

		/**
		 * Set up styling for the Stripe card field.
		 */
		var setup_adaptive_styling = ( function() {
			var current_height = $card.height(),
				new_height = $card_name.innerHeight(),
				v_padding = ( new_height - current_height ) / 2;

			$card.css( {
				paddingTop: v_padding,
				paddingRight: $card_name.css( 'paddingRight' ),
				paddingBottom: v_padding,
				paddingLeft: $card_name.css( 'paddingLeft' ),
				border: $card_name.css( 'border' ),
				borderRadius: $card_name.css( 'borderRadius' ),
				background: $card_name.css( 'background' )
			} );
		} )();

		/**
		 * On validation, add the donor's payment method to Stripe.
		 */
		$body.on(
			'charitable:form:validate',
			function( event, helper ) {
				// This is not the main donation form, so skip processing.
				if ( 'make_donation' !== helper.get_input( 'charitable_action' ).val() ) {
					return;
				}

				// If we're not using Stripe, do not process any further
				if ( 'stripe' !== helper.get_payment_method() ) {
					return;
				}

				event.preventDefault();

				// Check if our card errors field has errors in it.
				if ( '' !== $errors.text() ) {
					if ( helper.hasOwnProperty( 'prevent_scroll_to_top' ) ) {
						helper.prevent_scroll_to_top = true;
					}

					helper.add_error( $errors.text() );
					helper.hide_processing();
					return;
				}

				// If we have found no errors, handle card payment with Stripe.
				if ( helper.errors.length === 0 ) {

					// Pause further processing until we've handled Stripe response.
					helper.add_pending_process( 'stripe' );

					event.preventDefault();

					var email = helper.get_email(),
						phone = helper.get_input('phone'),
						address = {
							city: helper.get_input('city').val(),
							country: helper.get_input('country').val(),
							line1: helper.get_input('address').val(),
							line2: helper.get_input('address_2').val(),
							postal_code: helper.get_input('postcode').val(),
							state: helper.get_input('state').val(),
						},
						billingDetails = {
							name: $card_name.val(),
							email: email,
							address: address,
						};

					if (phone.length && phone.val().length) {
						billingDetails.phone = phone.val();
					}

					stripe.createPaymentMethod({
						type: 'card',
						card: cardNumber, // Reference your main card element or whichever is representative.
						billing_details: billingDetails,
					}).then(function(result) {
						if (result.error) {
							// Handle error
							helper.add_error(result.error.message);
						} else {
							// Process result
							helper.get_input('stripe_payment_method').val(result.paymentMethod.id);
						}

						helper.remove_pending_process_by_name('stripe');
					});
				}
			}
		);

		/**
		 * After the donation form has been processed, handle card payment.
		 */
		$body.on(
			'charitable:form:processed',
			function(event, response, helper) {
				if (!response.success) {
					return;
				}

				// If this is a recurring donation and no further action is required, return true now.
				if (helper.is_recurring_donation() && !response.requires_action) {
					return;
				}

				// This is not the main donation form, so skip processing.
				if ('make_donation' !== helper.get_input('charitable_action').val()) {
					return;
				}

				// If we're not using Stripe, do not process any further
				if ('stripe' !== helper.get_payment_method()) {
					return;
				}

				event.preventDefault();

				// If we have found no errors, handle card payment with Stripe.
				if (helper.errors.length === 0) {
					var new_stripe;

					// Pause further processing until we've handled Stripe response.
					helper.add_pending_process('stripe');

					// The payment intent should be processed on the connected account.
					if (should_process_payment_on_connected_account(helper)) {
						new_stripe = Stripe(CHARITABLE_STRIPE_VARS.key, get_stripe_options(helper, true));
					} else {
						new_stripe = stripe;
					}

					// Confirm the card payment
					new_stripe.confirmCardPayment(response.secret, {
						payment_method: {
							card: cardNumber, // Referencing the cardNumber element
							billing_details: {
								name: $card_name.val(),
								email: helper.get_email(),
								address: {
									city: helper.get_input('city').val(),
									country: helper.get_input('country').val(),
									line1: helper.get_input('address').val(),
									line2: helper.get_input('address_2').val(),
									postal_code: helper.get_input('postcode').val(),
									state: helper.get_input('state').val()
								}
							}
						}
					}).then(function(result) {
						if (result.error) {
							// Allow third-party plugin to potentially hook into this, starting with the Charitable Spam Blocker
							$(document).trigger("Charitable_Custom_Event__Handle_Card_Payment_Fail", [event, result, response, CHARITABLE_STRIPE_VARS, helper.get_payment_method()]);
							helper.add_error(result.error.message);
						} else {
							// Payment successful, handle post-payment logic here
						}

						helper.remove_pending_process_by_name('stripe');
					});
				}
			}
		);
	}

	/**
	 * Initialize the Stripe handlers.
	 *
	 * The 'charitable:form:initialize' event is only triggered once.
	 */
	$body.on( 'charitable:form:initialize', function( event ) {
		/* CHARITABLE_STRIPE_VARS.mode is set to either 'checkout' or 'payment-intents'.*/
		if ( 'checkout' === CHARITABLE_STRIPE_VARS.mode ) {
			new Checkout_Handler();
		} else {
			/* CHARITABLE_STRIPE_VARS.cc_fields_format is set to '' or 'multiple'. */
			if ( typeof CHARITABLE_STRIPE_VARS !== 'undefined' &&
				typeof CHARITABLE_STRIPE_VARS.cc_fields_format !== 'undefined' &&
				'multiple' === CHARITABLE_STRIPE_VARS.cc_fields_format ) {
				new Payment_Intents_Handler_Multiple();
			} else {
				new Payment_Intents_Handler();
			}
		}
	} );

} )( jQuery );