CHARITABLE = window.CHARITABLE || {};

( function( $ ) {

    /**
     * Closure variable for xxxx.
     *
     * @access  private
     */
    var sample_xxx = '';

    /**
     * Input event handler for credit card number. Prevents invalid characters,
     * invalid length, and automatically inserts spaces for readability.
     *
     * @access  private
     */
    var on_tab_click = function(e, tab) {

        var $container = $( '.charitable-campaign-container' ),
            tab_id = $( tab ).parent().data('tab-id'),
            tab_type = $container.find( 'li#tab_' + tab_id + '_title').attr( 'data-tab-type' );

        // clear the active states of the tabs and content areas in the preview area
        $('.tab-content ul li').removeClass('active');
        $('nav li.tab_title').removeClass('active');

        // make the clicked on tab and it's content area active
        $( tab ).parent().addClass('active');
        $('.tab-content ul li#tab_' + tab_id + '_content').addClass('active');

    };

    var $body = $( 'body' );

    $body.on( 'click', 'nav li.tab_title a', function( e ) {
        e.stopPropagation();
        e.preventDefault();
        on_tab_click(e, this);
    });


})( jQuery );
