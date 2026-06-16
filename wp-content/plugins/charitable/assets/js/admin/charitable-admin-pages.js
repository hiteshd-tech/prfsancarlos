

jQuery(document).ready( function(){

    jQuery('ul.wp-submenu li:contains("Upgrade to Pro") a').attr( 'target', '_blank' ); // makes pro link open in a new tab
    jQuery('tr[data-plugin="charitable/charitable.php"]').addClass('wpcharitable-plugin-row').find('.proupgrade a').attr( 'target', '_blank' );

    // check and see if the admin menu for Charitable exists at all.
    // added in 1.8.0.5.
    if( jQuery('li#toplevel_page_charitable ul.wp-submenu').length ) {
        // go through Charitable admin menu and based on the text, add a css class to the parent li.
        jQuery('li#toplevel_page_charitable ul.wp-submenu li').each( function(){
            var text = jQuery(this).text(),
                cssClass = text.toLowerCase().replace(/\s/g, '-');

            // cleanup class a bit.
            cssClass = cssClass.replace('new!', '');
            cssClass = cssClass.replace('--', '-');
            cssClass = cssClass.replace(/-$/, '');

            jQuery(this).addClass( cssClass );
        });
    }

    var setup_dashboard_widgets = function() {
        var $widget = jQuery( '#charitable_dashboard_donations' );

        if ( $widget.length ) {
            jQuery.ajax({
                type: "GET",
                data: {
                    action: 'charitable_load_dashboard_donations_widget'
                },
                url: ajaxurl,
                success: function (response) {
                    $widget.find( '.inside' ).html( response );
                }
            });
        }
    };

    setup_dashboard_widgets();

});

