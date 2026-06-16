/* global jQuery, userSettings */

/**
 * WordPress dependencies
 */
 import domReady from '@wordpress/dom-ready';

 /**
  * Opens the "Upgrade to Pro" menu item in a new tab.
  */
 function upgradeToProLink() {
     const upgradeLinkEl = document.querySelector(
         '#adminmenu a[href^="https://wpcharitable.com/lite-vs-pro"]'
     );

     if ( ! upgradeLinkEl ) {
         return;
     }

     upgradeLinkEl.setAttribute( 'target', '_blank' );
 }

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

 /**
  * Handle AJAX dismissal of notices.
  *
  * Uses jQuery because the `.notice-dismiss` button is added to the DOM
  * via jQuery when the notice loads.
  */
 domReady( () => {
     fiveStarRatingNotice();
     upgradeToProLink();

     jQuery( '.charitable-notice' ).each( function () {
         const notice = jQuery( this );
         const noticeId = notice.data( 'id' );
         const nonce = notice.data( 'nonce' );
         const lifespan = notice.data( 'lifespan' );

         notice.on( 'click', '.notice-dismiss, .charitable-notice-dismiss', () => {
             wp.ajax.send( 'charitable_dismiss_admin_notice', {
                 data: {
                     notice_id: noticeId,
                     nonce,
                     lifespan,
                 },
                 success() {
                     notice.slideUp( 'fast' );

                     // Remove previously set "seen" local storage.
                     const { uid = 0 } = userSettings;
                     const seenKey = `charitable-notice-${ noticeId }-seen-${ uid }`;
                     window.localStorage.removeItem( seenKey );
                 },
             } );
         } );
     } );

     // Move "Top of Page" promos to the top of content (before Help/Screen Options).
     const topOfPageNotice = jQuery( '.charitable-admin-notice-top-of-page' );

     if ( topOfPageNotice.length > 0 ) {
         const topOfPageNoticeEl = topOfPageNotice.detach();

         jQuery( '#wpbody-content' ).prepend( topOfPageNoticeEl );

         const { uid = 0 } = userSettings;
         const noticeId = topOfPageNoticeEl.data( 'id' );
         const seenKey = `charitable-notice-${ noticeId }-seen-${ uid }`;

         if ( window.localStorage.getItem( seenKey ) ) {
             topOfPageNoticeEl.show();
         } else {
             setTimeout( () => {
                 window.localStorage.setItem( seenKey, true );
                 topOfPageNotice.slideDown();
             }, 1500 );
         }
     }
 } );
