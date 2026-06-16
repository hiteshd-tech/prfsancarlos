( function( $ ) {

    // Clickable Tabs
    $('.charitable-campaign-nav').on( 'click', 'li.tab_title a', function( e ) {

        e.preventDefault();

        var $preview = $( '.charitable-campaign-preview' ),
            tab_id = $( this ).parent().data('tab-id'),
            tab_type = $preview.find( 'li#tab_' + tab_id + '_title').attr( 'data-tab-type' ); // eslint-disable-line

        // clear the active states of the tabs and content areas in the preview area
        $('.tab-content ul li').removeClass('active');
        $('nav li.tab_title').removeClass('active');

        // make the clicked on tab and it's content area active
        $( this ).parent().addClass('active');
        $('.tab-content ul li#tab_' + tab_id + '_content').addClass('active');

        // now clear any of the left option panel 'tabs' from being active... then add active to the proper one
        $('.charitable-layout-options-tab .charitable-group').removeClass('active');
        $(".charitable-layout-options-tab").find('[data-group_id=' + tab_id + ']').addClass('active').removeClass('charitable-closed');
        $(".charitable-layout-options-tab").find('[data-group_id=' + tab_id + ']').find('.charitable-group-rows').show();
        $(".charitable-layout-options-tab").find('[data-group_id=' + tab_id + ']').find('.charitable-toggleable-group i').removeClass('.charitable-angle-right').addClass('charitable-angle-down');
        $( '#layout-options a' ).click();

    } );

    // Clickable Donate Amount
    $('.charitable-campaign-field').on( 'click', 'ul.charitable-template-donation-amounts li', function( e ) { // eslint-disable-line

        // do the UI inside the donate field itself on the frontend.
        $( this ).parent().find('li').removeClass('selected');
        $( this ).addClass('selected');
        $( this ).find('input[type="radio"]').prop("checked", true);

        if ( $( this ).hasClass('custom-donation-amount' ) ) { // eslint-disable-line no-undef

        } else {

            const donationAmountSelected = $( this ).find( 'input[type="radio"]' ).val(); // example: 10.00

            var donation_amount_radio = null,
                selected_amount_string = '';

            // update any donate button in the <header> of this campaign form to reflect the selected amount
            $( this ).closest('.charitable-campaign-container').find('form.campaign-donation input[name="charitable_donation_amount"]').val( donationAmountSelected );

            // check if the donation form exists on this page, or even if it's hidden (modal).
            if( $('form#charitable-donation-form').length > 0 && $('.charitable-donation-options').length > 0 ) { // eslint-disable-line
                // find the radio inside of charitable-donation-options that has a value of donationAmountSelected and select that radio button.
                donation_amount_radio = $('.charitable-donation-options').find('input[type="radio"][value="' + donationAmountSelected + '"]'); // eslint-disable-line
                donation_amount_radio.prop("checked", true);

                // remove all selected classes from the suggested donation amounts
                $('form#charitable-donation-form').find('.suggested-donation-amount').removeClass('selected');
                donation_amount_radio.closest('.suggested-donation-amount').addClass('selected');

                // is the form showing a "your donation amount" field due to session?
                if( $('form#charitable-donation-form').find('.charitable-your-donation-amount').length > 0 ) { // eslint-disable-line
                    // first let's get the currency value of the amount that was just selected.
                    selected_amount_string = donation_amount_radio.closest('.suggested-donation-amount').find('.amount').text();
                    if ( selected_amount_string.length > 0 ) {
                        $('form#charitable-donation-form').find('.charitable-your-donation-amount').find('p strong').text( selected_amount_string );
                    }
                }
            }

        }
    } );

    $('.charitable-campaign-field').on( 'keyup', 'ul.charitable-template-donation-amounts input[name="custom_donation_amount"]', function( e ) { // eslint-disable-line

        $( this ).closest('.charitable-campaign-container').find('form.campaign-donation input[name="charitable_donation_amount"]').val( $( this ).val() );

        var donation_amount_radio = null,
            custom_donation_textbox = null,
            custom_donation_amount = '',
            selected_amount_string = '';

        const donationAmountSelected = $( this ).val(); // example: 10.00

        if( $('form#charitable-donation-form').length > 0
            && $('form#charitable-donation-form').find('input[name="custom_donation_amount"]').length > 0
            && $('.charitable-template-donation-amounts').find('input[name="custom_donation_amount"]').length > 0
        ) { // eslint-disable-line
            custom_donation_textbox = $('.charitable-template-donation-amounts').find('input[name="custom_donation_amount"]')
            custom_donation_amount = custom_donation_textbox.val();
            // if there is an amount populate the field in the main charitable-donation-form.
            if ( custom_donation_amount.length > 0 ) {

                donation_amount_radio = $('.charitable-donation-options').find('input[type="radio"][value="' + donationAmountSelected + '"]'); // eslint-disable-line
                donation_amount_radio.prop("checked", true);

                $('form#charitable-donation-form').find('input[name="custom_donation_amount"]').val( custom_donation_amount );
                // remove all selected classes from the suggested donation amounts
                $('form#charitable-donation-form').find('.suggested-donation-amount').removeClass('selected');
                donation_amount_radio.closest('.suggested-donation-amount').addClass('selected');
                // uncheck all the radio buttons.
                $('form#charitable-donation-form').find('input[type="radio"]').prop("checked", false);
            }
        }
        // is the form showing a "your donation amount" field due to session?
        if( $('form#charitable-donation-form').find('.charitable-your-donation-amount').length > 0 ) { // eslint-disable-line
            // first let's get the currency value of the amount that was just selected.
            custom_donation_textbox = $('.charitable-template-donation-amounts').find('input[name="custom_donation_amount"]')
            custom_donation_amount = custom_donation_textbox.val();
            if ( custom_donation_amount.length > 0 ) {
                $('form#charitable-donation-form').find('.charitable-your-donation-amount').find('p strong').text( custom_donation_amount );
            }
        }

    } );

    // Sharing that requires JS.

        // Grab link from the DOM
        const mastodonShareButton = document.querySelector('.charitable-mastodon-share');

        if ( mastodonShareButton !== null ) {

            // When a user clicks the link
            mastodonShareButton.addEventListener('click', (e) => {

                // If the user has already entered their instance and it is in localstorage
                // write out the link href with the instance and the current page title and URL
                if(localStorage.getItem('mastodon-instance')) {
                    mastodonShareButton.href = `
                    https://${localStorage.getItem('mastodon-instance')}/share?text=${encodeURIComponent(document.title)}%0A${encodeURIComponent(location.href)}`;
                // otherwise, prompt the user for their instance and save it to localstorage
                } else {
                    e.preventDefault();
                    let instance = window.prompt(
                    'Please tell me your Mastodon instance'
                    );
                    localStorage.setItem('mastodon-instance', instance);
                }

            });

        }

})( jQuery ); // eslint-disable-line

