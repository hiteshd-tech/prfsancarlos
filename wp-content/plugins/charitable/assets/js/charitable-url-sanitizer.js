CHARITABLE = window.CHARITABLE || {};

/**
 * Add sanitize_url to the Helpers.
 */
( function( exports, $ ) {
	exports.Helpers = exports.Helpers || {};
	exports.Helpers.sanitize_url = function( input ) {
		var url = input.value.toLowerCase();

		if ( !/^https?:\/\//i.test( url ) && url.length > 0 ) {
			url = 'http://' + url;
			input.value = url;
		}
	};
} )( CHARITABLE, jQuery );

/**
 * URL sanitization.
 *
 * This is provided for backwards compatibility.
 */
CHARITABLE.SanitizeURL = function( input ) {
    CHARITABLE.Helpers.sanitize_url( input );
};
