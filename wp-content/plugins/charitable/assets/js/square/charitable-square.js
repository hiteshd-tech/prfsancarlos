CHARITABLE = window.CHARITABLE || {};

(function ($) {

  var $body = $('body');
  const clientId = CHARITABLE_SQUARE_VARS.client_id;
  const locationId = CHARITABLE_SQUARE_VARS.location_id;
  const loadingSVG = `<!-- By Sam Herbert (@sherb), for everyone. More @ http://goo.gl/7AJzbL -->
  <svg width="40" height="10" viewBox="0 0 120 30" xmlns="http://www.w3.org/2000/svg" fill="#f89d35">
      <circle cx="15" cy="15" r="15">
          <animate attributeName="r" from="15" to="15"
                   begin="0s" dur="0.8s"
                   values="15;9;15" calcMode="linear"
                   repeatCount="indefinite" />
          <animate attributeName="fill-opacity" from="1" to="1"
                   begin="0s" dur="0.8s"
                   values="1;.5;1" calcMode="linear"
                   repeatCount="indefinite" />
      </circle>
      <circle cx="60" cy="15" r="9" fill-opacity="0.3">
          <animate attributeName="r" from="9" to="9"
                   begin="0s" dur="0.8s"
                   values="9;15;9" calcMode="linear"
                   repeatCount="indefinite" />
          <animate attributeName="fill-opacity" from="0.5" to="0.5"
                   begin="0s" dur="0.8s"
                   values=".5;1;.5" calcMode="linear"
                   repeatCount="indefinite" />
      </circle>
      <circle cx="105" cy="15" r="15">
          <animate attributeName="r" from="15" to="15"
                   begin="0s" dur="0.8s"
                   values="15;9;15" calcMode="linear"
                   repeatCount="indefinite" />
          <animate attributeName="fill-opacity" from="1" to="1"
                   begin="0s" dur="0.8s"
                   values="1;.5;1" calcMode="linear"
                   repeatCount="indefinite" />
      </circle>
  </svg>
  `;

  $('#square-card-container').html(loadingSVG);

  // Browser detection for Apple Pay compatibility
  function isSafari() {
    const userAgent = navigator.userAgent;
    return /Safari/.test(userAgent) && !/Chrome/.test(userAgent);
  }

  // Temporarily disable Google Pay and Apple Pay until further testing
  // These options are now permanently disabled but code is preserved for potential future use
  var google_pay_enabled = false, // CHARITABLE_SQUARE_VARS.enable_google_pay,
      apple_pay_enabled = false, // CHARITABLE_SQUARE_VARS.enable_apple_pay && isSafari(), // Only enable if Safari
      google_pay_button = $('#google-pay-button'),
      apple_pay_button = $('#apple-pay-button'),
      show_google_pay_button = google_pay_enabled && google_pay_button.length > 0,
      show_apple_pay_button = apple_pay_enabled && apple_pay_button.length > 0;

  const wallets = {};
  let isRecurring = false;
  let amountBeingInitialized = 1;

  async function initializeCard(payments) {

    const card = await payments.card();

    $('#square-card-container').html('');

    await card.attach('#square-card-container');

    return card;
  }

  function showWalletProcessing(helper) {
    $('#charitable-gateway-fields').addClass('square-wallet-processing');
    helper.show_processing();
  }

  function cancelWalletProcessing(helper) {
    helper.cancel_processing();
    $('#charitable-gateway-fields').removeClass('square-wallet-processing');
  }

  function setRecurringSupportClass(helper) {
    // Dynamically find the Square gateway fields container
    var squareGatewayFields = $('[id^="charitable-gateway-fields-square"]');
    if (helper.is_recurring_donation()) {
      squareGatewayFields.addClass('needs-recurring-support');
    } else {
      squareGatewayFields.removeClass('needs-recurring-support');
    }
  }

  // Handle Wallet Pay payments
  async function handleWalletPayEvent(event) {
    event.preventDefault();
    const helper = event.currentTarget.helper;
    const type = event.currentTarget.type;
    showWalletProcessing(helper)
    helper.clear_errors();
    helper.validate_amount();
    helper.validate_required_fields();

    if ( helper.is_recurring_donation() ) {
      helper.add_error( CHARITABLE_SQUARE_VARS.wallet_not_supported )
    }

    if (helper.errors.length > 0) {
      cancelWalletProcessing(helper);
      return false;
    }

    try {
      const result = await wallets[type].tokenize();
      if (result.status === 'OK') {
        helper.get_input('square_token').val(result.token);
        helper.remove_pending_process_by_name('square');

        /* Continue on to process the donation. */
        $body.trigger( 'charitable:form:process', helper );
      }
    } catch (e) {
      helper.add_error(e.message);
      helper.remove_pending_process_by_name('square');
      cancelWalletProcessing(helper);
    }

  };

  async function initializeWallets(payments, helper) {
    if (helper.is_recurring_donation()) {
      return;
    }
    try {
      // Use a default amount of 1.00 if no amount is set
      const amount = helper.get_amount() ? helper.get_amount().toString() : '1.00';
      amountBeingInitialized = amount;

      const paymentRequest = payments.paymentRequest({
        countryCode: CHARITABLE_VARS.country,
        currencyCode: CHARITABLE_VARS.currency,
        total: {
          amount: amount,
          label: 'Total',
        },
      });

      // Initialize both Google Pay and Apple Pay
      const walletTypes = [];
      if (show_google_pay_button) {
        walletTypes.push('googlePay');
      }
      if (show_apple_pay_button) {
        walletTypes.push('applePay');

      }
      if (walletTypes.length === 0) {
        return;
      }
      const promises = [];
      for (let walletType of walletTypes) {
        promises.push(initializeWallet(walletType, paymentRequest, payments, helper));
      }
      await Promise.all(promises).catch(e => {
        if (e.name === 'PaymentMethodUnsupportedError') {
          console.log(e.message);
        } else {
          console.error(e.message);
        }
      });

      // Show the wallet buttons container
      $('#charitable-square-wallet-buttons').show();
    }
    catch (e) {
      console.error('Error initializing wallets:', e);
    }
  }

  /*
  * Initialize a wallet with the passed in paymentRequests
  */
  async function initializeWallet(walletType, paymentRequest, payments, helper) {
    try {
      // Only destroy and recreate if the wallet doesn't exist or if it's a different amount
      if (!wallets[walletType] || (walletType === 'applePay' && wallets[walletType]._paymentRequest?.total?.amount !== paymentRequest.total.amount)) {
        if (wallets[walletType]) {
          await wallets[walletType].destroy();
        }

        wallets[walletType] = await payments[walletType](paymentRequest);
      }

      const id = walletType.split("P")[0] + '-pay-button';

      // For Apple Pay, we need to handle it differently
      if (walletType === 'applePay') {
        const applePay = wallets[walletType];
        if (!applePay) {
          throw new Error('Apple Pay is not available');
        }

        let buttonElement = document.getElementById(id);

        // Create the button element if it doesn't exist
        if (!buttonElement) {
          buttonElement = document.createElement('button');
          buttonElement.id = id;
          buttonElement.className = 'apple-pay-button';
          document.getElementById('charitable-square-wallet-buttons').appendChild(buttonElement);
        }

        // Apply Apple Pay button styles
        buttonElement.style.cssText = `
          -apple-pay-button-style: black;
          -apple-pay-button-type: plain;
          appearance: -apple-pay-button;
          display: inline-block;
          width: 200px;
          height: 40px;
          border-radius: 5px;
          padding: 0;
          margin: 10px auto;
          background: #000;
          cursor: pointer;
        `;

        // Remove any existing click handlers to prevent duplicates
        buttonElement.replaceWith(buttonElement.cloneNode(true));
        buttonElement = document.getElementById(id);

        // Add click handler
        buttonElement.addEventListener('click', async (event) => {
          event.preventDefault();
          try {
            if (!applePay || applePay._destroyed) {
              throw new Error('Apple Pay session has expired. Please try again.');
            }
            const result = await applePay.tokenize();
            if (result.status === 'OK') {
              helper.get_input('square_token').val(result.token);
              helper.remove_pending_process_by_name('square');
              $body.trigger('charitable:form:process', helper);
            }
          } catch (error) {
            console.error('Apple Pay tokenization error:', error);
            helper.add_error(error.message);
            // Reinitialize Apple Pay on error
            await initializeWallet(walletType, paymentRequest, payments, helper);
          }
        });

        buttonElement.helper = helper;
        buttonElement.type = walletType;
        buttonElement.style.display = 'block';
      } else {
        // For Google Pay and other wallet types
        const buttonElement = document.getElementById(id);
        if (buttonElement) {
          // Check if the wallet is already attached
          if (!buttonElement.hasAttribute('data-wallet-attached')) {
            try {
              await wallets[walletType].attach('#' + id);
              buttonElement.setAttribute('data-wallet-attached', 'true');

              // Remove any existing click handlers
              buttonElement.replaceWith(buttonElement.cloneNode(true));
              const newButtonElement = document.getElementById(id);

              // Add new click handler
              newButtonElement.addEventListener('click', handleWalletPayEvent);
              newButtonElement.helper = helper;
              newButtonElement.type = walletType;
              newButtonElement.style.display = 'block';
            } catch (error) {
              console.error(`Error attaching ${walletType}:`, error);
              // If attachment fails, try to reinitialize
              await wallets[walletType].destroy();
              wallets[walletType] = await payments[walletType](paymentRequest);
              await wallets[walletType].attach('#' + id);
              buttonElement.setAttribute('data-wallet-attached', 'true');
            }
          }
        }
      }

      $('#charitable-square-wallet-buttons').addClass('wallet-enabled').show();
      $('#charitable-square-card-payment-fields').addClass('wallet-enabled');

    } catch (e) {
      console.error(`Error initializing ${walletType}:`, e);
      console.error('Wallet object:', wallets[walletType]);
      console.error('PaymentRequest:', paymentRequest);
      console.error('Payments object:', payments);

      // Hide the button if initialization failed
      const id = walletType.split("P")[0] + '-pay-button';
      const buttonElement = document.getElementById(id);
      if (buttonElement) {
        buttonElement.style.display = 'none';
      }
    }
  }

  // This function tokenizes a payment method.
  // The 'error' thrown from this async function denotes a failed tokenization,
  // which is due to buyer error (such as an expired card). It is up to the
  // developer to handle the error and provide the buyer the chance to fix
  // their mistakes.
  async function tokenize(paymentMethod) {

    const tokenResult = await paymentMethod.tokenize();

    if (tokenResult.status === 'OK') {
      return tokenResult.token;
    } else {
      let errorMessage = 'Unable to validate card.Please check your details.';
      if (tokenResult.errors) {
        errors = tokenResult.errors.map(function (item) { return item.message });
        errorMessage = errors.join('. ');
      }
      throw new Error(errorMessage);
    }
  }

  /**
	 * Initialize the Square handler.
	 *
	 * The 'charitable:form:initialize' event is only triggered once.
	 */
	$body.on( 'charitable:form:initialize', async function( event, helper ) {

    if (!window.Square) {
      throw new Error('Square.js failed to load properly');
    }

    let payments;

    try {
      payments = window.Square.payments(clientId, locationId);
    } catch (e) {
      const statusContainer = document.getElementById(
        'square-payment-status-container'
      );
      document.getElementById('square-card-container').innerHTML = '';
      statusContainer.className = 'charitable-notice';
      statusContainer.style.visibility = 'visible';
      statusContainer.innerHTML = 'Square: ' + e.message;
      return;
    }

    let card;

    try {
      card = await initializeCard(payments);
      await initializeWallets(payments, helper);
    } catch (e) {
      console.error('Initializing Card failed', e);
      return;
    }

    // Handle Credit card payments
    async function handlePaymentMethodSubmission(event, paymentMethod, helper) {

      event.preventDefault();

      // This is not the main donation form, so skip processing.
      if ( 'make_donation' !== helper.get_input( 'charitable_action' ).val() ) {
        return;
      }

      // If we're not using Square, do not process any further
      if ( !helper.get_payment_method().startsWith('square') ) {
        return;
      }

      if (helper.errors.length > 0) {
        helper.cancel_processing();
        return;
      }

      // Pause further processing until we've handled Square response.
      helper.add_pending_process('square');

      try {
        const token = await tokenize(paymentMethod);
        helper.get_input('square_token').val(token);

        const verificationToken = await verifyBuyer(payments, token, helper); // 3DS card verification.
        helper.get_input('square_verification_token').val(verificationToken);

        helper.remove_pending_process_by_name('square');

      } catch (e) {
        helper.add_error(e.message);
        helper.remove_pending_process_by_name('square');
      }
    }

    function getBillingContactFromForm() {
      const billingContact = {};
      const addressLines = [];
      if (helper.get_input('address').val()) {
        addressLines.push(helper.get_input('address').val());
      }
      if (helper.get_input('address_2').val()) {
        addressLines.push(helper.get_input('address_2').val());
      }
      const fields = {
        'givenName': helper.get_input('first_name').val(),
        'familyName': helper.get_input('last_name').val(),
        'emailAddress': helper.get_input('email').val(),
        'addressLines': addressLines,
        'phone': helper.get_input('phone').val(),
        'country': helper.get_input('country').val(),
        'city': helper.get_input('city').val(),
        'postalCode': helper.get_input('postcode').val(),
        'region': helper.get_input('state').val()
      }
      for (let key in fields) {
        if (fields[key] && fields[key].length) {
          billingContact[key] = fields[key];
        }
      }
      return billingContact;
    }

    /* This function exists to handle SCA payments. */
    async function verifyBuyer(payments, token, helper) {

      const intent = helper.is_recurring_donation() ? 'STORE' : 'CHARGE';

      const verificationDetails = {
        amount: helper.get_amount().toString(),
        billingContact: getBillingContactFromForm(),
        currencyCode: CHARITABLE_VARS.currency,
        intent: intent,
      };

      const verificationResults = await payments.verifyBuyer(
        token,
        verificationDetails
      );
      return verificationResults.token;

    }

    $body.on(
      'charitable:form:validate',
      async function (event, helper) {

        await handlePaymentMethodSubmission(event, card, helper);
      }
    );

    $body.on('charitable:form:amount:changed', async function (event, helper) {
      const amount = helper.get_amount().toString();
      if (amount !== amountBeingInitialized) {
        amountBeingInitialized = amount;
        try {
          const paymentRequest = payments.paymentRequest({
            countryCode: CHARITABLE_VARS.country,
            currencyCode: CHARITABLE_VARS.currency,
            total: {
              amount: amount,
              label: 'Total',
            },
          });

          // Reinitialize wallets with new amount, respecting enabled settings
          const walletTypes = [];
          if (show_google_pay_button) {
            walletTypes.push('googlePay');
          }
          if (show_apple_pay_button) {
            walletTypes.push('applePay');
          }

          const promises = [];
          for (let walletType of walletTypes) {
            promises.push(initializeWallet(walletType, paymentRequest, payments, helper));
          }
          await Promise.all(promises);
        } catch (e) {
          console.error('Error updating wallet amount:', e);
        }
      }
    });

    setRecurringSupportClass(helper);
    $('#charitable-donation-form').on('change', 'input[name=recurring_donation]', async () => {
      setRecurringSupportClass(helper);
      await initializeWallets(payments, helper);
    });

	} );

  /**
   * Handle Square iframe height recalculation for modal forms.
   * This ensures proper sizing when donation forms are loaded in modals.
   */
  function triggerSquareResize() {
    // Check if Square is the active gateway (either by selection or as the only option)
    const $squareGateway = $('input[name="gateway"][value="square"], input[name="gateway"][value="square_core"]');
    const $squareFields = $('#charitable-gateway-fields-square, #charitable-gateway-fields-square_core');

    // If Square gateway is selected or Square fields are visible (single gateway scenario)
    if ($squareGateway.is(':checked') || $squareFields.is(':visible')) {
      setTimeout(function() {
        window.dispatchEvent(new Event('resize'));
      }, 150);
    }
  }

  // Listen for modal open events
  $body.on('charitable:modal:open', function() {
    triggerSquareResize();
  });

  // Listen for form loaded events (especially important for AJAX-loaded forms in modals)
  $body.on('charitable:form:loaded', function(event, helper) {
    triggerSquareResize();
  });

  // Listen for modal resize events (in case modal content changes)
  $body.on('charitable:modal:resize', function() {
    triggerSquareResize();
  });


} )( jQuery );