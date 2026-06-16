( function( $, document ) {
    var $body,
        $overlay,
        options;

    var setup = (function() {
        var done = false;

        return function( $trigger, params ) {
            if ( done ) {
                return;
            }

            options = $.extend({
                verticalOffset: 100,
                overlay: 0.5,
                closeButton: null
            }, params);
            options.verticalOffset = parseInt(options.verticalOffset);

            $body = $( 'body' );
            $body.append( $( '<div id="lean_overlay"></div>' ) );

            $overlay = $( '#lean_overlay' );

            $trigger.on( 'click', function(e) {
                var modal_id = methods.get_target($(this));

                // If no valid modal target, don't proceed
                if ( ! modal_id ) {
                    return;
                }

                var $modal = $(modal_id);

                methods.open( $modal, e );

                $modal.on( 'click', options.closeButton, function(e){
                    methods.close( $modal );
                    e.preventDefault();
                });

                $overlay.on( 'click', function() {
                    methods.close( $modal );
                });

                $(document).on( 'keyup', function(e) {
                    return 27 === e.keyCode && methods.close( $modal );
                });

                e.preventDefault();
            });

            done = true;
        }
    })();

    /**
     * General API methods.
     */
    var methods = {

        /**
         * Main fn method
         */
        init : function( params ) {
            setup(this, params);
        },

        /**
         * Open modal.
         */
        open : function( $modal ) {
            var resize = function() {
                methods.resize($modal);
            };

            $overlay.css({
                'display': 'block',
                'opacity': 0,
            }).fadeTo(200, options.overlay);

            $modal.css({
                'display': 'block',
                'position': 'fixed',
                'opacity': 0,
                'z-index': 100000,
            }).fadeTo(200, 1);

            resize();
            $modal.resize( resize );
            $(window).resize( resize );

            $modal.trigger( 'focus' );

            /* Provide an event hook for other scripts to use. */
            $body.trigger( 'charitable:modal:open' );
        },

        /**
         * Return the ID of the modal to open.
         */
        get_target : function( $trigger ) {
            var id = $trigger.data( 'trigger-modal' );

            // If data-trigger-modal is empty or undefined, don't use href as fallback
            if ( ! id || id.length === 0 ) {
                return null;
            }

            // Ensure it starts with # for proper selector
            if ( id.charAt( 0 ) !== '#' ) {
                id = '#' + id;
            }

            return id;
        },

        /**
         * Close modal
         */
        close : function( $modal ) {
            $overlay.fadeOut( 200 );
            $modal.hide();
            methods.reset( $modal );

            /* Provide an event hook for other scripts to use. */
            $body.trigger( 'charitable:modal:close', $modal );
        },

        /**
         * Reset modal CSS
         */
        reset : function( $modal ) {
            $modal.css({
                'bottom' : 'auto',
                'overflowY' : 'auto'
            });
        },

        /**
         * Resize modal
         */
        resize : function( $modal ) {
            var window_height = $(window).height(),
                modal_width = $modal.outerWidth(),
                modal_height = $modal.outerHeight()
                available_offset = window_height - modal_height,
                modal_is_too_tall = ( function() {
                    var modal_calc_height = modal_height + ( 2 * options.verticalOffset );
                    return window_height < modal_calc_height;
                })(),
                modal_css = {
                    'left' : 50 + '%',
                    'margin-left' : -( modal_width / 2 ) + "px",
                    'top' : options.verticalOffset + "px"
                };

            if ( modal_is_too_tall ) {
                if ( available_offset > 0 ) {
                    var v_offset = available_offset / 2;

                    modal_css.top = v_offset + 'px';
                    modal_css.bottom = v_offset + 'px';
                }

                if ( available_offset <= 0 ) {
                    modal_css.top = '10px';
                    modal_css.bottom = '10px';
                    modal_css.overflowY = 'scroll';
                }
            }

            $modal.css( modal_css );

            /* Provide an event hook for other scripts to use. */
            $body.trigger( 'charitable:modal:resize' );
        }
    };

    /**
     * Register this as a jQuery function.
     */
    $.fn.extend({
        leanModal : function( method_or_options ) {
            if ( methods[ method_or_options ] ) {
                return methods[  method_or_options  ].apply( this, Array.prototype.slice.call( arguments, 1 ));
            } else if ( typeof  method_or_options  === 'object' || ! method_or_options  ) {
                // Default to "init"
                return methods.init.apply( this, arguments );
            } else {
                $.error( 'Method ' +   method_or_options  + ' does not exist on jQuery.leanModal' );
            }
        }
    });

    /**
     * Init function.
     */
    $(document).ready( function() {
        $( '[data-trigger-modal]' ).leanModal({
            closeButton : ".modal-close"
        });
    });

})(jQuery, document);