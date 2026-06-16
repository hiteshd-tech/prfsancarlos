/* eslint-disable no-undef */

// if wpchar is not defined, define it.
if ( typeof wpchar === 'undefined' ) {

    var wpchar = {

        // This file contains a collection of utility functions.

        /**
         * Start the engine.
         *
         * @since 1.0.1
         */
        init: function() {
            jQuery( wpchar.ready );
        },

        /**
         * Document ready.
         *
         * @since 1.0.1
         */
        ready: function() {
        },

        /**
         * Debug output helper.
         *
         * @since 1.3.8
         *
         * @param {string|integer|boolean|Array|object} msg Debug message (any data).
         */
        debug: function( msg, type = '' ) {

            if ( ! wpchar.isDebug() ) {
                return;
            }

            if ( type ) {
                type = '(' + type + ')';
            }

            console.log( '%cCharitable Debug: ' + type, 'color: #cd6622;', msg );
        },

        /**
         * Is debug mode.
         *
         * @since 1.3.8
         */
        isDebug: function() {
            return ( ( window.location.hash && '#charitabledebug' === window.location.hash ) );
        },

    }

wpchar.init();

}
