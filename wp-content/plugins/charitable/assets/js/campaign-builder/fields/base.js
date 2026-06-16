( function( $ ) {
	// /**

	$( document ).ready( function() {

        /* control WIDTH of item in design->preview */

        $('#charitable-field-options').on( 'input', '.charitable-number-slider', function( e ) {
            var $this = $( this ),
                width = parseInt( $this.val() ) > 0 ? parseInt( $this.val() ) : 0,
                field_id = $( this ).closest('.charitable-panel-field').data('field-id');

            if ( width < 10 ) {
                width = 10;
            } else if ( width > 95 ) {
                width = 100;
            }

            $('.charitable-field[data-field-id="' + field_id + '"]').find('.charitable-preview-field-container').css('width', width + '%' );

        } );

        /* control ALIGNMENT of item in design->preview */

        $('#charitable-field-options').on( 'click', 'span a', function( e ) {
            var $this = $( this ),
                alignValue = $this.data('align-value'),
                field_id = $( this ).closest('.charitable-panel-field').data('field-id');

            $('.charitable-field[data-field-id="' + field_id + '"]').find('.charitable-preview-field-container').removeClass(function (index, className) {
                return (className.match (/(^|\s)charitable-preview-align-\S+/g) || []).join(' ');
            }).addClass('charitable-preview-align-' + alignValue);


        } );

        /* control COLUMNS of item in design->preview */

        $('#charitable-field-options').on( 'change', '.charitable-campaign-builder-preview-columns select', function( e ) {

            var $this = $( this ),
                selectedValue = $this.val(),
                field_id = $( this ).closest('.charitable-panel-field').data('field-id');

            $('.charitable-field[data-field-id="' + field_id + '"]').find('.charitable-preview-field-container').removeClass(function (index, className) {
                return (className.match (/(^|\s)charitable-preview-columns-\S+/g) || []).join(' ');
            }).addClass('charitable-preview-columns-' + selectedValue);


        } );


	} );

})( jQuery );