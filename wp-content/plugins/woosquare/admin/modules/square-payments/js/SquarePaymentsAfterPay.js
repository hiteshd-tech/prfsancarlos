(function ( $ ) {
	'use strict';

	const afterpay_appId = square_afterpay_params.application_id;
	const afterpay_location_id = square_afterpay_params.lid;
	
	function buildPaymentRequest(payments) {
		
		/*if(jQuery('form.wc-block-checkout__form').length > 0){
			var id_of_div = jQuery('.wc-block-components-totals-footer-item-tax-value').html(); 
			var total_price = id_of_div.split(square_afterpay_params.currency_sym)[1];
			var total_price = total_price.replace(" ", "");
			// var total = total.substring(1, total.length);
			// var total_price = total.toString();
		}else{
			var id_of_div = jQuery('div#order_review tr.order-total span.woocommerce-Price-amount bdi').html();
			var total = id_of_div.split("span")[2];
			var total = total.substring(1, total.length);
			var total_price = total.toString();
		}*/
		
		var total_price = square_afterpay_params.order_total; 
		const req = payments.paymentRequest({
			countryCode: square_afterpay_params.country_code,
			currencyCode: square_afterpay_params.currency_code,
			total: {
			amount: total_price,
			label: 'Total',
			},
			requestShippingContact: true,
		});

		// Note how afterpay has its own listeners
		req.addEventListener('afterpay_shippingaddresschanged', function (_address) {
			return {
				shippingOptions: [{
					amount: '0.00',
					id: 'shipping-option-1',
					label: 'Flat rate',
					taxLineItems: [],
					total: {
						amount: total_price,
						label: 'total',
					}
				}]
			};
		});
		req.addEventListener('afterpay_shippingoptionchanged', function (_option) {
			// This event listener is for information purposes only.
			// Changes here (or values returned) will not affect the Afterpay/Clearpay PaymentRequest.
		});

		return req;
	}

	let afterpay;

	async function initializeAfterpay(payments) {

		if(jQuery('#afterpay-button').html().length > 1 && afterpay && typeof afterpay.destroy === 'function'){
			try {
				afterpay.destroy();
			} catch (destroyError) {
				console.error('Error destroying afterpay:', destroyError);
			}
		}

		const paymentRequest = buildPaymentRequest(payments)
		
		try {
			afterpay = await payments.afterpayClearpay(paymentRequest);
		} catch (error) {
			// Handle PaymentMethodUnsupportedError or other initialization errors
			console.error('After Pay initialization error:', error);
			
			// Always show short, user-friendly message - never show full API error to customer
			// Update the initialization message with short message
			let $initDiv = jQuery('#afterpay-initialization');
			
			// If element doesn't exist, create it dynamically
			if ($initDiv.length === 0) {
				const $afterpayButton = jQuery('#afterpay-button');
				if ($afterpayButton.length > 0) {
					// Create the initialization div before the button
					$initDiv = jQuery('<div id="afterpay-initialization" class="method-initialization"></div>');
					$afterpayButton.before($initDiv);
				} else {
					// If button also doesn't exist, try to find payment-form container
					const $paymentForm = jQuery('#payment-form');
					if ($paymentForm.length > 0) {
						$initDiv = jQuery('<div id="afterpay-initialization" class="method-initialization"></div>');
						$paymentForm.prepend($initDiv);
					}
				}
			}
			
			if ($initDiv.length > 0) {
				$initDiv.html('After Pay unavailable due to account issue. Please select another payment method.').show();
			} else {
				// Fallback: try to show message in afterpay-button or create a visible error
				const $afterpayButton = jQuery('#afterpay-button');
				if ($afterpayButton.length > 0) {
					$afterpayButton.html('<div style="color: red; padding: 10px;">After Pay unavailable due to account issue. Please select another payment method.</div>');
				}
			}
			jQuery('#afterpay-button').html('');
			
			// Don't throw error, just return so it doesn't break the checkout
			return null;
		}
		
		setTimeout(function(){ 	 
			// Check if afterpay was successfully initialized
			if (!afterpay || afterpay === null) {
				// Already handled in the catch block above, just return
				return;
			}
			
			try {
				afterpay.attach('#afterpay-button');
				jQuery('#afterpay-initialization').hide();
			} catch (attachError) {
				console.error('After Pay attach error:', attachError);
				jQuery('#afterpay-initialization').html('After Pay unavailable due to account issue. Please select another payment method.').show();
				return;
			}
			
			const afterpayButton = document.getElementById('afterpay-button');
			
			async function handlePaymentMethodSubmissionafterpay(event, paymentMethod) {
				event.preventDefault();
				try {
					// disable the submit button as we await tokenization and make a
					// payment request.
					// cardButton.disabled = true;
					jQuery('.woocommerce-error').remove();
					const token =  tokenize(paymentMethod);
				} catch (e) {
					// cardButton.disabled = false;
					console.error(e.message);
				}
			}
			
			// Only add event listener if afterpay button exists and afterpay is initialized
			if (afterpayButton && afterpay) {
				if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_after_pay'+square_afterpay_params.sandbox
					|| jQuery("input[name=radio-control-wc-payment-method-options]:checked").val() == 'square_after_pay'+square_afterpay_params.sandbox
				){
					afterpayButton.addEventListener('click', async function (event) {
						await handlePaymentMethodSubmissionafterpay(event, afterpay);
					});
				}
			}
			
		}, 10);
		
	}
	
	async function tokenize(paymentMethod) {

		const tokenResult = await
		paymentMethod.tokenize();
		if (tokenResult.status === 'OK') {
			
			var $form = jQuery('form.woocommerce-checkout, form.wc-block-checkout__form, form#order_review');
			// inject nonce to a hidden field to be submitted
			/*$form.append( '<input type="hidden" class="errors" name="errors" value="' + errors + '" />' );
			 $form.append( '<input type="hidden" class="noncedatatype" name="noncedatatype" value="' + noncedatatype + '" />' );
			 $form.append( '<input type="hidden" class="cardData" name="cardData" value="' + cardData + '" />' );
			 */
			$form.append('<input type="hidden" class="square-nonce" name="square_nonce" value="' + tokenResult.token + '" />');


			if( jQuery('form.wc-block-checkout__form').length > 0 ){
				if(jQuery("input[name=radio-control-wc-payment-method-options]:checked").val() == 'square_after_pay'+square_afterpay_params.sandbox){
					jQuery(".wc-block-components-checkout-place-order-button").trigger("click");
				}
			}else{
				$form.submit();
			}
		} else {
			let errorMessage = tokenResult.status;
			if (tokenResult.errors) {
				errorMessage += tokenResult.errors;
			}
			throw new Error(errorMessage);
		}
	}

// Helper method for displaying the Payment Status on the screen.
// status is either SUCCESS or FAILURE;
	function displayPaymentResults(status) {
		const statusContainer = document.getElementById(
				'payment-status-container'
		);
		if (status === 'SUCCESS') {
			statusContainer.classList.remove('is-failure');
			statusContainer.classList.add('is-success');
		} else {
			statusContainer.classList.remove('is-success');
			statusContainer.classList.add('is-failure');
		}

		statusContainer.style.visibility = 'visible';
	}
	async function initp(afterpay,payments){
		try {
			jQuery('#afterpay-initialization').show().html('Initializing...');
			afterpay = await initializeAfterpay(payments);
			// If initialization returned null (error occurred), keep showing the message
			if (afterpay === null) {
				// Error message already displayed in initializeAfterpay function
			}
		} catch (e) {
			// Show error message instead of hiding
			jQuery('#afterpay-initialization').html('After Pay unavailable due to account issue. Please select another payment method.').show();
			console.error('Initializing After Pay failed', e);
			return;
		}
	}
	jQuery( window  ).on("load", function() {
		if (!window.Square) {
			throw new Error('Square.js failed to load properly');
		}
		const payments = window.Square.payments(afterpay_appId, afterpay_location_id);
		// 🕒 Keep checking for selected payment method
		const pollInterval = setInterval(async () => {
			const selectedValue = jQuery("input[name=radio-control-wc-payment-method-options]:checked").val();

			// ✅ Check if Square After Pay is selected
			if (selectedValue === 'square_after_pay' + square_afterpay_params.sandbox) {
				clearInterval(pollInterval); // ✅ Stop checking

				if (jQuery('#afterpay-button').html().length > 1) {
					afterpay?.destroy();
				}

				try {
					jQuery('#afterpay-initialization').show().html('Initializing...');
					afterpay = await initializeAfterpay(payments);
					// If initialization returned null (error occurred), keep showing the message
					if (afterpay === null) {
						// Error message already displayed in initializeAfterpay function
					}
				} catch (e) {
					// Show error message instead of hiding
					jQuery('#afterpay-initialization').html('After Pay unavailable due to account issue. Please select another payment method.').show();
				}
			}
		}, 500); // 🔁 Check every 500ms

		if (jQuery('.payment_method_square_ach_payment' + square_afterpay_params.sandbox).length === 0) {

			let attempts = 0;
			const maxAttempts = 10;

			const intervalId = setInterval(async function () {
				attempts++;
				const $afterpayBtn = jQuery('#afterpay-button');


				if ($afterpayBtn.length > 0) {

					try {
						jQuery('#afterpay-initialization').show().html('Initializing...');
						afterpay = await initializeAfterpay(payments);
						// If initialization returned null (error occurred), keep showing the message
						if (afterpay === null) {
							// Error message already displayed in initializeAfterpay function
						}
					} catch (e) {
						// Show error message instead of hiding
						jQuery('#afterpay-initialization').html('After Pay unavailable due to account issue. Please select another payment method.').show();
						console.error('Initializing After Pay failed', e);
					}

					clearInterval(intervalId);
				}

				if (attempts >= maxAttempts) {
					clearInterval(intervalId);
				}
			}, 500); // Checks every 500ms
		}
		jQuery(document).on('change', 'body.woocommerce-checkout', function() {
			setTimeout(async () => {
				if(jQuery("input[name=radio-control-wc-payment-method-options]:checked").val() == 'square_after_pay'+square_afterpay_params.sandbox ){
					if(jQuery('#afterpay-button').html().length > 1 && afterpay && typeof afterpay.destroy === 'function'){
						try {
							afterpay.destroy();
						} catch (destroyError) {
							console.error('Error destroying afterpay:', destroyError);
						}
					}
					try {
						if(jQuery("input[name=radio-control-wc-payment-method-options]:checked").val() == 'square_after_pay'+square_afterpay_params.sandbox ){
							jQuery('#afterpay-initialization').show().html('Initializing...');
							afterpay = await initializeAfterpay(payments);
							// If initialization returned null (error occurred), keep showing the message
							if (afterpay === null) {
								// Error message already displayed in initializeAfterpay function
							}
						}
						// return afterpay;
					} catch (e) {
						// Show error message instead of hiding
						jQuery('#afterpay-initialization').html('After Pay unavailable due to account issue. Please select another payment method.').show();
						console.error('Initializing After Pay failed', e);
						return;
					}
				}
			}, 1000);
		})
		$('form.checkout').on('change', '.woocommerce-checkout-payment input', async function(){
			const selectedPayment = jQuery('.woocommerce-checkout-payment .input-radio:checked').val();
			
			if(selectedPayment == 'square_after_pay'+square_afterpay_params.sandbox ){
				if(jQuery('#afterpay-button').html().length > 1 && afterpay && typeof afterpay.destroy === 'function'){
					try {
						afterpay.destroy();
					} catch (destroyError) {
						console.error('Error destroying afterpay:', destroyError);
					}
				}
				try {
					if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_after_pay'+square_afterpay_params.sandbox ){
						jQuery('#afterpay-initialization').show().html('Initializing...');
						afterpay = await initializeAfterpay(payments);
						// If initialization returned null (error occurred), keep showing the message
						if (afterpay === null) {
							// Error message already displayed in initializeAfterpay function
						}
					}
					// return afterpay;
				} catch (e) {
					// Show error message instead of hiding
					jQuery('#afterpay-initialization').html('After Pay unavailable due to account issue. Please select another payment method.').show();
					console.error('Initializing After Pay failed', e);
					return;
				}
				/* init_afterpay(afterpay,payments);
				jQuery('input[type=radio][name=payment_method]').change(function() {
					if(jQuery("input[name='payment_method'][value='square_after_pay']").prop("checked")){
						init_afterpay(afterpay,payments);
					}
				});	 */	
			}
		});
		jQuery('form.wc-block-checkout__form').on('change', "input[name=radio-control-wc-payment-method-options]", async function(){
			if(jQuery("input[name=radio-control-wc-payment-method-options]:checked").val() == 'square_after_pay'+square_afterpay_params.sandbox ){
				if(jQuery('#afterpay-button').html().length > 1 && afterpay && typeof afterpay.destroy === 'function'){
					try {
						afterpay.destroy();
					} catch (destroyError) {
						console.error('Error destroying afterpay:', destroyError);
					}
				}
				try {
					if(jQuery("input[name=radio-control-wc-payment-method-options]:checked").val() == 'square_after_pay'+square_afterpay_params.sandbox ){
						jQuery('#afterpay-initialization').show().html('Initializing...');
						afterpay = await initializeAfterpay(payments);
						// If initialization returned null (error occurred), keep showing the message
						if (afterpay === null) {
							// Error message already displayed in initializeAfterpay function
						}
					}
					// return afterpay;
				} catch (e) {
					// Show error message instead of hiding
					jQuery('#afterpay-initialization').html('After Pay unavailable due to account issue. Please select another payment method.').show();
					console.error('Initializing After Pay failed', e);
					return;
				}
			}
		})
		
		// Legacy checkout - Handle updated_checkout event for AJAX updates
		// Always setup updated_checkout handler for legacy checkout (not dependent on ACH payment method)
		
		// Also check on initial page load if After Pay is already selected
		setTimeout(function() {
			const isLegacyCheckout = jQuery('form.woocommerce-checkout').length > 0 && jQuery('.wc-block-checkout').length === 0;
			
			if (isLegacyCheckout) {
				const selectedPayment = jQuery('.woocommerce-checkout-payment .input-radio:checked').val();
				
				if (selectedPayment === 'square_after_pay' + square_afterpay_params.sandbox) {
					const $afterpayBtn = jQuery('#afterpay-button');
					const $initialization = jQuery('#afterpay-initialization');
					
					if ($afterpayBtn.length > 0 && $afterpayBtn.html().length <= 1) {
						$initialization.show().html('Initializing...');
						initializeAfterpay(payments).then(function(result) {
							// Initialization complete
						}).catch(function(error) {
							console.error('Error initializing AfterPay on page load:', error);
						});
					}
				}
			}
		}, 1000);
		
		jQuery(document.body).on('updated_checkout', async function() {
			// Only handle legacy checkout, not block checkout
			// Legacy checkout has form.woocommerce-checkout and no .wc-block-checkout
			const isLegacyCheckout = jQuery('form.woocommerce-checkout').length > 0 && jQuery('.wc-block-checkout').length === 0;
			
			if (isLegacyCheckout) {
				const selectedPayment = jQuery('.woocommerce-checkout-payment .input-radio:checked').val();
				
				if (selectedPayment === 'square_after_pay' + square_afterpay_params.sandbox) {
					const $afterpayBtn = jQuery('#afterpay-button');
					const $initialization = jQuery('#afterpay-initialization');
					
					// Check if button exists and After Pay is not already initialized
					if ($afterpayBtn.length > 0 && $afterpayBtn.html().length <= 1) {
						try {
							$initialization.show().html('Initializing...');
							afterpay = await initializeAfterpay(payments);
							// If initialization returned null (error occurred), keep showing the message
							if (afterpay === null) {
								// Error message already displayed in initializeAfterpay function
							}
						} catch (e) {
							// Show error message instead of hiding
							$initialization.html('After Pay unavailable due to account issue. Please select another payment method.').show();
							console.error('Initializing After Pay failed in updated_checkout:', e);
						}
					}
				}
			}
		});

	});


}( jQuery ) );


