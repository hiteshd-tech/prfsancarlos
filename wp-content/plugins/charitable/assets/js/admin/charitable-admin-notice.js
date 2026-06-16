( function( $ ){

    /**
     * Binds navigation buttons/links in the "Five Star Rating" admin notice.
     */
    function fiveStarRatingNotice() {
        const steps = document.querySelectorAll(
            '.charitable-admin-notice-five-star-rating'
        );

        steps.forEach( ( stepEl ) => {
            const navigationEls = stepEl.querySelectorAll( '[data-navigate]' );

            if ( ! navigationEls ) {
                return;
            }

            navigationEls.forEach( ( navigationEl ) => {
                navigationEl.addEventListener( 'click', ( { target } ) => {
                    const step = target.dataset.navigate;
                    const stepToShow = document.querySelector(
                        `.charitable-admin-notice-five-star-rating[data-step="${ step }"]`
                    );
                    const stepsToHide = document.querySelectorAll(
                        `.charitable-admin-notice-five-star-rating:not([data-step="${ step }"])`
                    );

                    if ( stepToShow ) {
                        stepToShow.style.display = 'block';
                    }

                    if ( stepsToHide.length > 0 ) {
                        stepsToHide.forEach( ( stepToHide ) => {
                            stepToHide.style.display = 'none';
                        } );
                    }
                } );
            } );
        } );
    }

    $( document ).ready( function() {

        fiveStarRatingNotice();

        $( '.charitable-notice.is-dismissible' ).each( function(){
            var $el = $( this ), $button = $el.find( '.notice-dismiss' );

            $button.on( 'click', function( event ) {
                event.preventDefault();

                $.ajax({
                    type: "POST",
                    data: {
                        action : 'charitable_dismiss_notice',
                        notice : $el.data( 'notice' )
                    },
                    dataType: "json",
                    url: ajaxurl,
                    xhrFields: {
                        withCredentials: true
                    },
                    success: function ( response ) {
                        if ( window.console && window.console.log ) {
                            console.log( response );
                        }
                    },
                    error: function( error ) {
                        if ( window.console && window.console.log ) {
                            console.log( error );
                        }
                    }
                }).fail(function ( response ) {
                    if ( window.console && window.console.log ) {
                        console.log( response );
                    }
                });
            });

            $el.css( 'position', 'relative' );
        });

        $( '.charitable-banner' ).each( function () {
            const banner   = jQuery( this );
            const bannerId = banner.data( 'id' );
            const nonce    = banner.data( 'nonce' );
            const lifespan = banner.data( 'lifespan' );

            banner.on( 'click', '.banner-dismiss, .charitable-banner-dismiss', () => {
                event.preventDefault();
                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: {
                        action: 'charitable_dismiss_admin_banner',
                        banner_id: bannerId,
                        nonce,
                        lifespan,
                    },
                    dataType: "json",
                    success() {
                        banner.slideUp( 'fast' );

                        // Remove previously set "seen" local storage.
                        const uid = 0;
                        const seenKey = `charitable-banner-${ bannerId }-seen-${ uid }`;
                        window.localStorage.removeItem( seenKey );
                    },
                } );
            } );
        } );

        $( '.charitable-campaign-list-banner' ).each( function () {
            const banner = jQuery( this );
            const containerRow = banner.closest('tr.child');
            const bannerId = banner.data( 'id' );
            const nonce = banner.data( 'nonce' );
            const lifespan = banner.data( 'lifespan' );

            banner.on( 'click', '.banner-dismiss, .notice-dismiss, .charitable-close', () => {
                event.preventDefault();
                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: {
                        action: 'charitable_dismiss_admin_list_banner',
                        banner_id: bannerId,
                        nonce,
                        lifespan,
                    },
                    dataType: "json",
                    success() {
                        containerRow.fadeOut( 'fast' );

                        // Remove previously set "seen" local storage.
                        const uid = 0;
                        const seenKey = `charitable-banner-${ bannerId }-seen-${ uid }`;
                        window.localStorage.removeItem( seenKey );
                    },
                } );
            } );
        } );

        // Move "Top of Page" promos to the top of content (before Help/Screen Options).
        const topOfPageNotice = jQuery( '.charitable-admin-banner-top-of-page' );

        if ( topOfPageNotice.length > 0 ) {
            const topOfPageNoticeEl = topOfPageNotice.detach();

            if ( jQuery( '#charitable-admin-header' ).length > 0 ) {
                jQuery( '#charitable-admin-header' ).before( topOfPageNoticeEl );
            } else {
                jQuery( '#wpbody-content' ).prepend( topOfPageNoticeEl );
            }

            const uid = 0;
            const noticeId = topOfPageNoticeEl.data( 'id' );
            const seenKey = `charitable-banner-${ noticeId }-seen-${ uid }`;

            if ( window.localStorage.getItem( seenKey ) ) {
                topOfPageNoticeEl.show();
            } else {
                setTimeout( () => {
                    window.localStorage.setItem( seenKey, true );
                    topOfPageNotice.slideDown();
                }, 1500 );
            }
        }

        $( '.charitable-admin-notice-five-star-rating' ).each( function () {
            const notice = jQuery( this );
            const bannerId = notice.data( 'id' );

            notice.on( 'click', '.charitable-notice-dismiss', () => {
                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: {
                        action: 'charitable_dismiss_admin_five_star_rating',
                        banner_id: bannerId,
                    },
                    success() {
                        $('.notice-five-star-review').remove();

                    }
                } );
            } );
        } );

        // Move "Top of Page" promos to the top of content (before Help/Screen Options).
        const fiveStarReviewNotice = jQuery( '.notice-five-star-review' );

        if ( fiveStarReviewNotice.length > 0 ) {
            const fiveStarReviewNoticeEl = fiveStarReviewNotice.detach(),
                  adminHeader = jQuery( '#charitable-admin-header' );

            if ( jQuery('#screen-meta-links').length > 0 ) {
                jQuery( '#screen-meta-links' ).after( fiveStarReviewNoticeEl );
                fiveStarReviewNoticeEl.addClass('screen-meta-space').show();
            } else if ( adminHeader.length > 0 ) {
                adminHeader.after( fiveStarReviewNoticeEl );
                fiveStarReviewNoticeEl.show();
            } else if ( topOfPageNotice.length > 0 ) {
                topOfPageNotice.after( fiveStarReviewNoticeEl );
                fiveStarReviewNoticeEl.show();
            } else {
                jQuery( '#wpbody-content' ).prepend( fiveStarReviewNoticeEl );
                fiveStarReviewNoticeEl.show();
            }

        }

        /* #version 1.8.1.5 */
        const genericCharitableNotice = jQuery( '.notice.charitable-notice' );

        if ( genericCharitableNotice.length > 0 ) {
            const genericCharitableNoticeEl = genericCharitableNotice.detach(),
                  adminHeader = jQuery( '#charitable-admin-header' );

            if ( jQuery('#screen-meta-links').length > 0 ) {
                jQuery( '#screen-meta-links' ).after( genericCharitableNoticeEl );
                genericCharitableNoticeEl.addClass('screen-meta-space').show();
            } else if ( adminHeader.length > 0 ) {
                adminHeader.after( genericCharitableNoticeEl );
                genericCharitableNoticeEl.show();
            } else if ( topOfPageNotice.length > 0 ) {
                topOfPageNotice.after( genericCharitableNoticeEl );
                genericCharitableNoticeEl.show();
            } else {
                jQuery( '#wpbody-content' ).prepend( genericCharitableNoticeEl );
                genericCharitableNoticeEl.show();
            }

        }

        /* #version 1.8.1.6 */

        /* important messages, like the one about the new dashboard */
        const dashboardCharitableNotice = jQuery( '.charitable-remove-dashboard-notice, .charitable-remove-dashboard-notice-link' );

        if ( dashboardCharitableNotice.length > 0 ) {
            const dashboardCharitableNoticeEl = dashboardCharitableNotice.closest('.charitable-important');

            // update via AJAX to add the removed notice to the user meta.
            dashboardCharitableNotice.on( 'click', function( event ) {
                event.preventDefault();

                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: {
                        action: 'charitable_dismiss_dashboard_notice',
                        notice_ids: dashboardCharitableNoticeEl.data( 'notice-ids' ),
                    },
                    success() {
                        // remove element from page completely.
                        dashboardCharitableNoticeEl.remove();
                    },
                } );
            } );

        }

        $('#charitable-growth-tools-notice').on('click', '.charitable-remove-growth-tools', function( event ) {
            event.preventDefault();

            const dashboardRemovegrowthToolsNotice       = $( this ),
                  dashboardRemovegrowthToolsNoticeEl     = dashboardRemovegrowthToolsNotice.closest('.charitable-growth-tools-notice'),
                  dashboardRemovegrowthToolsNoticeAction = dashboardRemovegrowthToolsNoticeEl.hasClass('charitable-growth-tools-dashboard') ? 'charitable_dismiss_growth_tools_dashboard_notice' : 'charitable_dismiss_growth_tools_notice',
                  dashboardRemovegrowthToolsNoticeNonce  = dashboardRemovegrowthToolsNoticeEl.data( 'nonce' );

                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: {
                        action:      dashboardRemovegrowthToolsNoticeAction,
                        nonce:       dashboardRemovegrowthToolsNoticeNonce,
                        notice_type: dashboardRemovegrowthToolsNoticeEl.data( 'notice-type' ),
                    },
                    success() {
                        // fade and remove element.
                        dashboardRemovegrowthToolsNoticeEl.fadeOut( 'fast', function() {
                            dashboardRemovegrowthToolsNoticeEl.remove();
                        });
                    },
                } );
        } );

        $('#charitable-growth-tools-notice').on('click', '.charitable-dashboard-notice-another-suggestion a', function( event ) {
            event.preventDefault();

            const dashboardRemovegrowthToolsNoticeEl = $( this ).closest('.charitable-growth-tools-notice-interior');

            $.ajax({
                type : "POST",
                url  : ajaxurl,
                data : {
                    action:     'ajax_get_dashboard_notice_html',
                    nonce:      charitable_admin.nonce, // eslint-disable-line
                },
                success( response ) {
                    // if the response was successfull.
                    if ( response.success ) {
                        // fade and remove element.
                        if ( response.data ) {
                            // quickly fade out the current notice and when the fade is complete, update the HTML and fade back in.
                            dashboardRemovegrowthToolsNoticeEl.fadeOut( 'fast', function() {
                                dashboardRemovegrowthToolsNoticeEl.html( response.data );
                                dashboardRemovegrowthToolsNoticeEl.fadeIn( 'fast' );
                            });
                        }
                    }
                },

            } );
        } );


    });

} )( jQuery );