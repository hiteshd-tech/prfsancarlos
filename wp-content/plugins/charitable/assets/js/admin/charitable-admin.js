CHARITABLE_ADMIN = window.CHARITABLE_ADMIN || {};

/**
 * Datepicker
 */
( function( exports, $ ){

	if ( ! $.fn.datepicker ) {
		return;
	}

	var Datepicker = function( $el ) {
		this.$el = $el;
		var options = {
			dateFormat 	: this.$el.data('format') || 'yy/mm/dd',
			minDate 	: this.$el.data('min-date') || '',
			beforeShow	: function( input, inst ) {
				$('#ui-datepicker-div').addClass('charitable-datepicker-table');
			}
		}

		this.$el.datepicker( options );

		if ( this.$el.data('date') ) {
			this.$el.datepicker( 'setDate', this.$el.data('date') );
		}

		if ( this.$el.data('min-date') ) {
			this.$el.datepicker( 'option', 'minDate', this.$el.data('min-date') );
		}
	}

	exports.Datepicker = Datepicker;

})( CHARITABLE_ADMIN, jQuery );

/**
 * Conditional settings.
 */
( function( exports, $ ){

	var Settings = function( $el ) {
		var triggers = [];

		show_setting = function(value, $trigger) {
			var not = '!' === value[0],
				compare = not ? value.slice(1) : value,
				show;

			if ('checked' === compare) {
				show = $trigger.is(':checked');
			} else if ('selected' === compare) {
				show = $trigger.selected();
			} else {
				show = $trigger.val() === compare;
			}

			return not ? !show : show;
		};

		toggle_setting = function($setting, $trigger) {
			var $el = (function($setting){
					var $tr = $setting.parents('tr');
					return $tr.length ? $tr.first() : $setting;
				})($setting),
				value = get_setting_value( $setting ),
				show = show_setting(value, $trigger);

			$el.toggle(show);
		};

		get_setting_value = function($setting) {
			var value = $setting.data( 'trigger-value' );

			/* Backwards compatibility for pre 1.5 */
			if ( 'undefined' === typeof value ) {
				value = $setting.data( 'show-only-if-value' );
			}

			return value;
		};

		toggle_options = function($setting, $trigger) {
			var value = $trigger.val(),
				options = $setting.data( 'trigger-value' ),
				available;

			/* If it's a radio input that isn't checked, ignore the event. */
			if ( 'radio' === $trigger.attr( 'type' ) && ! $trigger.prop( 'checked' ) ) {
				return;
			}

			if ( ! options.hasOwnProperty( value ) ) {
				return;
			}

			available = options[value];

			$setting.find( 'input' ).each( function(){
				var $option = $(this),
					disabled = ! ( $option.val() in available );

				if ( disabled ) {
					$option.prop( 'checked', false );
				}

				$option.prop( 'disabled', disabled ).trigger( 'change' );
			});
		};

		get_trigger_id = function($setting) {
			var id = $setting.data( 'trigger-key' );

			/* Backwards compatibility for pre 1.5 */
			if ( 'undefined' === typeof id ) {
				id = '#' + $setting.data( 'show-only-if-key' );
			}

			return id;
		};

		get_trigger = function(id) {
			if ( '#' === id[0] ) {
				return $( id );
			}

			return $( '[name="' + id + '"]' );
		};

		get_change_type = function($setting) {
			var type = $setting.data( 'trigger-change-type' );

			if ( 'undefined' === typeof type ) {
				type = 'visibility';
			}

			return type;
		};

		on_change = function() {
			var $trigger = $( this ),
				trigger_idx = $trigger.data( 'trigger_idx' ),
				settings;

			trigger_idx.forEach( function( id ) {

				settings = triggers[id]['settings'];

				settings.forEach( function( $setting ) {
					var change = get_change_type( $setting );

					if ( 'visibility' === change ) {
						toggle_setting( $setting, $trigger );
					} else if ( 'options' === change ) {
						toggle_options( $setting, $trigger );
					}
				} );
			} );
		};

		this.$el = $el;

		var i = 0;

		this.$el.find( '[data-trigger-key],[data-show-only-if-key]' ).each( function(){
			var $this      = $(this),
				trigger_id = get_trigger_id( $this ),
				element    = triggers[trigger_id];

			if ( 'undefined' === typeof triggers[trigger_id] ) {
				triggers[i] = {
					trigger_id : trigger_id,
					settings : []
				};
			}

			triggers[i].settings.push( $this );

			i += 1;
		});

		triggers.forEach( function( trigger, i ) {
			var $trigger = get_trigger( trigger['trigger_id'] ),
				trigger_idx = $trigger.data( 'trigger_idx' );

			if ( 'undefined' === typeof( trigger_idx ) ) {
				trigger_idx = [];
			}

			trigger_idx.push( i );

			$trigger.data( 'trigger_idx', trigger_idx );

			$trigger.on( 'change', on_change );

			$trigger.trigger( 'change' );
		} );
	};

	exports.Settings = Settings;

})( CHARITABLE_ADMIN, jQuery );

( function($){

	var setup_license_check = function() {

		$('#charitable-settings-connect-btn').on( 'click', function( e ){
			var $btn = $( this );
			var data = {
					action 			    : 'charitable_license_check',
					'license' 	        : $('input[name="license-key"]').val(), // 022effc6d03bdda601503a1dc13672fd
					'nonce'			    : $('input[name="_wpnonce"]').val(),
					'charitable_action' : $btn.data('action'),
				};

			// If the button contains the 'data-pro-connect' attribute and it's true, don't show the button.
			if ( $('#charitable-settings-connect-btn').data( 'pro-connect' ) && $('#charitable-settings-connect-btn').data( 'pro-connect' ) == '1' ) {
				return;
			}

			var originalHtml = $btn.html();
			var spinnerHtml = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> ';
			if ( data.charitable_action === 'verify' ) {
				$btn.html( spinnerHtml + charitable_admin.verifying );
			} else {
				$btn.html( spinnerHtml + charitable_admin.deactivating );
			}

			$btn.prop('disabled', true);

			$.ajax({
				type: 'POST',
				data: data,
				dataType: 'json',
				url: ajaxurl,
				xhrFields: {
					withCredentials: true
				},
				success: function (response) {
					if ( response.success ) {

						// Redirect to upgrade.wpcharitable.com when backend returns upgrade_url (e.g. Pro installed but not activated).
						if ( response.data.upgrade_url ) {
							$btn.data( 'charitable-skip-restore', true );
							window.location.href = response.data.upgrade_url;
							return;
						}
						if ( response.data.show_manual_upgrade && response.data.url ) {
							$.alert( {
								title: charitable_admin.license_validated || charitable_admin.success,
								content: response.data.message,
								icon: 'fa fa-check-circle',
								type: 'green',
								boxWidth: '800px',
								buttons: {
									confirm: {
										text: charitable_admin.ok,
										btnClass: 'btn-confirm',
										keys: [ 'enter' ],
										action: function() {
											window.location.reload();
										},
									},
									manual: {
										text: charitable_admin.manual_install,
										btnClass: 'btn-confirm',
										action: function() {
											window.open( response.data.url, '_blank' );
											window.location.reload();
										},
									},
								},
							} );
							return;
						}

						// Pro installed but inactive: show popup to activate (no redirect to upgrade site or valid=valid).
						if ( response.data.show_activate_pro_popup ) {
							if ( typeof CharitableConnect !== 'undefined' && CharitableConnect.showActivateProPopup ) {
								CharitableConnect.showActivateProPopup( response.data );
							} else {
								$.alert( {
									title: charitable_admin.license_validated || charitable_admin.success,
									content: response.data.message,
									icon: 'fa fa-check-circle',
									type: 'green',
									boxWidth: '800px',
									buttons: {
										activate: {
											text: charitable_admin.activate_now,
											btnClass: 'btn-confirm',
											keys: [ 'enter' ],
											action: function() {
												window.location.href = ( response.data.redirect_url || ( charitable_admin.admin_url + 'admin.php?page=charitable-settings&tab=general' ) ) + '&valid=valid&pro=activate';
											},
										},
										cancel: {
											text: charitable_admin.cancel,
											keys: [ 'esc' ],
											action: function() {
												window.location.reload();
											},
										},
									},
								} );
							}
							return;
						}

						if ( response.data.valid === true ) {

							var is_pro_plugin_installed = 'undefined' !== typeof charitable_admin.is_pro_installed && charitable_admin.is_pro_installed === '1' ? true : false;

							$('#charitable-license-message').html( response.data.message );

							// If the pro plugin is installed, redirect to a page that will activate it.
							if ( is_pro_plugin_installed ) {

								// Generate an alert modal to confirm we are about to activate the pro plugin.

								$.alert( {
									title: charitable_admin.activated_title,
									content: charitable_admin.activated_content,
									icon: 'fa fa-check-circle',
									type: 'green',
									boxWidth: '800px',
									buttons: {
										confirmActivate: {
											text    : charitable_admin.plugin_activate_btn,
											btnClass: 'btn-confirm',
											keys    : [ 'enter' ],
											action: function( confirmActivateButton ) {
												window.location.href = charitable_admin.admin_url + 'admin.php?page=charitable-settings&tab=general&valid=valid&pro=activate';
												return false;
											}
										}
									},

								} );


							} else {
								$btn.data( 'charitable-skip-restore', true );
								if ( 'undefined' !== typeof charitable_admin.admin_url ) {
									window.location.href = charitable_admin.admin_url + 'admin.php?page=charitable-settings&tab=general&valid=valid';
								} else {
									location.reload();
								}
							}

						} else {
							$('#charitable-license-message').html( response.data.message );

							if ( 'undefined' !== typeof charitable_admin.admin_url ) {

								$btn.data( 'charitable-skip-restore', true );
								if ( response.data.license_limit === true ) {

									window.location.href = charitable_admin.admin_url + 'admin.php?page=charitable-settings&tab=general&valid=invalid&license_limit=true';
								} else if ( response.data.comm_success === false ) {
									window.location.href = charitable_admin.admin_url + 'admin.php?page=charitable-settings&tab=general&valid=invalid&comm_success=false';
								} else if ( data.charitable_action === 'verify' ) {
									window.location.href = charitable_admin.admin_url + 'admin.php?page=charitable-settings&tab=general&valid=invalid';
								} else {
									window.location.href = charitable_admin.admin_url + 'admin.php?page=charitable-settings&tab=general&valid=success';
								}
							} else {

								$btn.data( 'charitable-skip-restore', true );
								location.reload();
							}
						}
					}
					// $user_fields.removeClass( 'loading-data' );
				}
			}).fail(function (data) {
				if ( window.console && window.console.log ) {
					// console.log( data );
					// $user_fields.removeClass( 'loading-data' );
				}
			}).always(function () {
				var $restoreBtn = $( '#charitable-settings-connect-btn' );
				if ( $restoreBtn.length && $restoreBtn.data( 'charitable-skip-restore' ) ) {
					$restoreBtn.removeData( 'charitable-skip-restore' ).removeData( 'charitable-license-original-html' );
					return;
				}
				if ( $restoreBtn.length && $restoreBtn.data( 'charitable-license-original-html' ) ) {
					$restoreBtn.html( $restoreBtn.data( 'charitable-license-original-html' ) ).prop( 'disabled', false ).removeData( 'charitable-license-original-html' );
				}
			});

			$btn.data( 'charitable-license-original-html', originalHtml );

			return false;

		} );
	};

	var setup_campaign_end_date_field = function() {
		$( '#campaign_end_date' ).on( 'change', function() {
			var $field = $( this ),
				date   = $field.val(),
				$span  = $field.siblings( '.charitable-end-time' ),
				$input = $field.siblings( '#campaign_end_time' );

			if ( '' === date || ! date ) {
				$span.hide();
				$input.val( 0 );
			} else {
				$span.text( '@ 23:59 PM' ).show();
				$input.val( '23:59:59' );
			}
		});
	};

	var setup_charitable_ajax = function() {
		$('[data-charitable-action]').on( 'click', function( e ){
			var data 	= $(this).data( 'charitable-args' ) || {},
				action 	= 'charitable-' + $(this).data( 'charitable-action' );

			$.post( ajaxurl,
				{
					'action'	: action,
					'data'		: data
				},
				function( response ) {
					// console.log( "Response: " + response );
				}
			);

			return false;
		} );
	};

	var setup_charitable_toggle = function() {
		$( '[data-charitable-toggle]' ).on( 'click', function( e ){
			var toggle_id = $(this).data( 'charitable-toggle' ),
				toggle_text = $(this).attr( 'data-charitable-toggle-text' );

			if ( toggle_text && toggle_text.length ) {
				$(this).attr( 'data-charitable-toggle-text', $(this).text() );
				$(this).text( toggle_text );
			}

			$('#' + toggle_id).toggle();

			return false;
		} );
	};

	var setup_advanced_meta_box = function() {
		var $meta_box = $('#charitable-campaign-advanced-metabox');

		if ( ! $meta_box.length ) {
			return;
		}

		$meta_box.tabs();

		var min_height = $meta_box.find('.charitable-tabs').height();

		$meta_box.find('.ui-tabs-panel').each( function(){
			$(this).css( 'min-height', min_height );
		});
	};

	var toggle_custom_donations_checkbox = function() {
		var $custom = $('#campaign_allow_custom_donations'),
			$suggestions = $('.charitable-campaign-suggested-donations tbody tr:not(.to-copy)'),
			has_suggestions = $suggestions.length > 1 || false === $suggestions.first().hasClass('no-suggested-amounts');

		// check to see if $custom exists.
		if ( ! $custom.length ) {
			return;
		}

		$custom.prop( 'disabled', ! has_suggestions );

		if ( ! has_suggestions ) {
			$custom.prop( 'checked', true );
		}
	};

	// Suggested Donations (in settings)
	var setup_sortable_suggested_donations = function(){
		if ( 'undefined' !== typeof $.fn.sortable ) {
			$('.charitable-campaign-suggested-donations tbody').sortable({
				items: "tr:not(.to-copy)",
				handle: ".handle",
				stop: function( event, ui ) {
					reindex_rows();
				}

			});
		}
	};

	var add_suggested_amount_row = function( $button ) {
		var $table = $button.closest( '.charitable-campaign-suggested-donations' ).find('tbody');
		var $clone = $table.find('tr.to-copy').clone().removeClass('to-copy hidden');

		// check to see if $table or $clone exists.
		if ( ! $table.length || ! $clone.length ) {
			return;
		}

		$table.find( '.no-suggested-amounts' ).hide();
		$table.append( $clone );
		reindex_rows();
		toggle_custom_donations_checkbox();
	};

	var delete_suggested_amount_row = function($button) {

		$button.closest( 'tr' ).remove();
		var $table = $button.closest('.charitable-campaign-suggested-donations').find('tbody');
		if( $table.find( 'tr:not(.to-copy)' ).length == 1 ){
			$table.find( '.no-suggested-amounts' ).removeClass('hidden').show();
		}
		reindex_rows();
		toggle_custom_donations_checkbox();
	};

	var reindex_rows = function(){
		$('.charitable-campaign-suggested-donations tbody').each(function(){
			$(this).children('tr').not('.no-suggested-amounts .to-copy').each(function(index) {

				$(this).find('input[name="_campaign_suggested_donations_default[]"').val( index );
				$(this).data('index', index );
				$(this).find('input').each(function(i) {
					this.name = this.name.replace(/(\[\d\])/, '[' + index + ']');
				});
			});
		});
	};

	// Suggested Donations (in left sidebar in preview)
	var setup_sortable_suggested_donations_mini = function(){
		if ( 'undefined' !== typeof $.fn.sortable ) {
			$('.charitable-campaign-suggested-donations-mini tbody').sortable({
				items: "tr:not(.to-copy)",
				handle: ".handle",
				stop: function( event, ui ) {
					reindex_rows_mini();
				}

			});
		}
	};

	var reindex_rows_mini = function(){
		$('.charitable-campaign-suggested-donations-mini tbody').each(function(){
			$(this).children('tr').not('.no-suggested-amounts .to-copy').each(function(index) {

				$(this).find('input[name="_campaign_suggested_donations_default[]"').val( index );
				$(this).data('index', index );
				$(this).find('input').each(function(i) {
					this.name = this.name.replace(/(\[\d\])/, '[' + index + ']');
				});
			});
		});
	};

	// var setup_dashboard_widgets = function() {
	// 	var $widget = $( '#charitable_dashboard_donations' );

	// 	if ( $widget.length ) {
	// 		$.ajax({
	// 			type: "GET",
	// 			data: {
	// 				action: 'charitable_load_dashboard_donations_widget'
	// 			},
	// 			url: ajaxurl,
	// 			success: function (response) {
	// 				$widget.find( '.inside' ).html( response );
	// 			}
	// 		});
	// 	}
	// };

	var setup_actions_form = function() {
		var $form = $( '.charitable-actions-form-wrapper' ),
			$select,
			$submit,
			$button;

		if ( ! $form.length ) {
			return;
		}

		$select = $form.find( '.charitable-action-select' );
		$submit = $form.find( '.charitable-actions-submit' );
		$button = $submit.find( 'button' );

		$submit.hide();

		$select.on( 'change', function() {
			var action = $select.val(),
				text = $select.find( 'option:selected' ).data( 'button-text');

			if ( '' === action ) {
				$submit.hide();
				return;
			}

			$form.find( '.charitable-action-fields' ).each( function() {
				var $el = $( this );
				$el.toggle( action === $el.data( 'type' ) );
			});

			if ( text ) {
				$button.text( text );
			} else {
				$button.text( $button.prop( 'title' ) );
			}

			$submit.show();
		});
	}

	var setup_select2 = function() {
		if ( ! $.fn.select2 ) {
			return;
		}

		$( '.select2 select, select.select2' ).select2();
	}

	var setup_donor_select = function() {
		var user_fields;

		var get_user_fields = function() {
			if ( 'object' === typeof user_fields ) {
				return user_fields;
			}

			user_fields = [];

			$( '#charitable-user-fields-wrap' ).find( 'select,input,textarea' ).each( function() {
				user_fields.push(this.name);
			});

			return user_fields;
		};

		$( '.charitable-admin-donation-form #donor-id' ).on( 'change', function() {
			var donor_id = $(this).val(),
				$user_fields,
				field;

			if ( '' === donor_id || 'new' === donor_id ) {
				return;
			}

			$user_fields = $( '#charitable-user-fields-wrap' );
			$user_fields.addClass( 'loading-data' );

			data = {
				action   : 'charitable_get_donor_data',
				donor_id : donor_id,
				fields   : get_user_fields(),
				nonce    : $(this).data( 'nonce' )
			};

			$.ajax({
				type: 'POST',
				data: data,
				dataType: 'json',
				url: ajaxurl,
				xhrFields: {
					withCredentials: true
				},
				success: function (response) {
					if ( response.success ) {
						for ( field in response.data ) {
							$user_fields.find( "[name=" + field + "]" ).val( response.data[field] );
						}
					}
					$user_fields.removeClass( 'loading-data' );
				}
			}).fail(function (data) {
				if ( window.console && window.console.log ) {
					// console.log( data );
					$user_fields.removeClass( 'loading-data' );
				}
			});
		});
	}

	var setup_currency_inputs = function() {
		if ( 'undefined' === typeof accounting ) {
			return;
		}

		if ( 'undefined' === typeof CHARITABLE || CHARITABLE === null ) {
			return;
		}

		var unformat = function( amount ) {
			return Math.abs( parseFloat( accounting.unformat( amount, CHARITABLE.currency_format_decimal_sep ) ) );
		}

		var format = function( amount ) {
			return accounting.formatMoney( amount, {
				symbol : '',
				decimal : CHARITABLE.currency_format_decimal_sep,
				thousand : CHARITABLE.currency_format_thousand_sep,
				precision : CHARITABLE.currency_format_num_decimals,
				format : CHARITABLE.currency_format
			}).trim();
		}

		$( 'body' ).on( 'change', '.currency-input', function() {
			var amount = unformat( $( this ).val() );

			if ( $.trim( amount ) > 0 ) {
				$( this ).val( format( amount ) );
			}
		} );

		$( '.currency-input' ).trigger( 'change' );
	};


	$(document).ready( function(){

		$('body').on( 'click', 'th.default_amount-col a', function( e ) {

			e.stopPropagation();

			$('.default_amount-col input').prop('checked', false);

			$(this).blur();

		});

		// if there are stripe keys already entered, allow fields to be added via ajax to allow user to view/edit them.

		$( 'a#charitable-modify-keys' ).on( 'click', function( e ) {

			e.stopPropagation();

			var link = $(this);

			link.css('pointer-events', 'none');
			link.css('opacity', '0.25');

			var data = {
				action  : 'charitable-' + $(this).data( 'charitable-action' ),
				args 	: $(this).data( 'charitable-args' ),
				nonce   : $(this).data( 'nonce' )
			};

			$.ajax({
				type: "POST",
				data: data,
				dataType: "json",
				url: ajaxurl,
				xhrFields: {
					withCredentials: true
				},
				success: function (response) {

					link.closest('tr').after( response.data );
					link.closest('tr').hide();
				}
			}).fail(function (data) {
				if ( window.console && window.console.log ) {
					link.css('pointer-events', 'all');
					link.css('opacity', '1.0');
				}
			});

		});


		if ( CHARITABLE_ADMIN.Datepicker ) {
			$( '.charitable-datepicker' ).each( function() {
				CHARITABLE_ADMIN.Datepicker( $(this ) );
			});
		}

		/* check for valid dates and valid input on export and filter forms */

		$(document).on( 'click', '#charitable-campaigns-export-modal form button', function( e ) {
			e.preventDefault();
			var form_invalid = false;
			// check all text fields to ensure dates are proper.
			$('#charitable-campaigns-export-modal form input[type=text]').each(function(){
				if ( ( $(this).val() ).trim() !== '' ) {
					isValidDate = dateIsValid( $(this).val() );
					if ( isValidDate === false ) {
						form_invalid = true;
						$(this).val('');
					}
				}
			})
			if ( form_invalid === true ) {
				var today = new Date(),
					dd = ( '0' + today.getDate() ).slice(-2),
					mm = ( '0' + ( today.getMonth()+1 ) ).slice(-2),
					yyyy = today.getFullYear();
				alert ('You have entered an invalid date format. Please use the following format: yyyy/mm/dd. For example: [' + yyyy + '/' + mm + '/' + dd + ']' );
			} else {
				$('#charitable-campaigns-export-modal form').submit();
			}
		});

		$(document).on( 'click', '#charitable-campaigns-filter-modal form button', function( e ) {
			e.preventDefault();
			var form_invalid = false;
			// check all text fields to ensure dates are proper.
			$('#charitable-campaigns-filter-modal form input[type=text]').each(function(){
				if ( ( $(this).val() ).trim() !== '' ) {
					isValidDate = dateIsValid( $(this).val() );
					if ( isValidDate === false ) {
						form_invalid = true;
						$(this).val('');
					}
				}
			})
			if ( form_invalid === true ) {
				var today = new Date(),
				dd = ( '0' + today.getDate() ).slice(-2),
				mm = ( '0' + ( today.getMonth()+1 ) ).slice(-2),
					yyyy = today.getFullYear();
				alert ('You have entered an invalid date format. Please use the following format: yyyy/mm/dd. For example: [' + yyyy + '/' + mm + '/' + dd + ']' );
			} else {
				$('#charitable-campaigns-filter-modal form').submit();
			}
		});

		$(document).on( 'click', '#charitable-donations-export-modal form button', function( e ) {
			e.preventDefault();
			var form_invalid = false;
			// check all text fields to ensure dates are proper.
			$('#charitable-donations-export-modal form input[type=text]').each(function(){
				// ignore recurring donation text field - added in version 1.8.1.8.
				if ( $(this).attr('id') === 'charitable-filter-part-of-recurring' ) {
					return;
				}
				if ( ( $(this).val() ).trim() !== '' ) {
					isValidDate = dateIsValid( $(this).val() );
					if ( isValidDate === false ) {
						form_invalid = true;
						$(this).val('');
					}
				}
			})
			if ( form_invalid === true ) {
				var today = new Date(),
				dd = ( '0' + today.getDate() ).slice(-2),
				mm = ( '0' + ( today.getMonth()+1 ) ).slice(-2),
					yyyy = today.getFullYear();
				alert ('You have entered an invalid date format. Please use the following format: yyyy/mm/dd. For example: [' + yyyy + '/' + mm + '/' + dd + ']' );
			} else {
				$('#charitable-donations-export-modal form').submit();
			}
		});

		$(document).on( 'click', '#charitable-donations-filter-modal form button', function( e ) {
			e.preventDefault();
			var form_invalid = false;
			// check all text fields to ensure dates are proper.
			$('#charitable-donations-filter-modal form input[type=text]').each(function(){
				if ( ( $(this).val() ).trim() !== '' ) {
					isValidDate = dateIsValid( $(this).val() );
					if ( isValidDate === false ) {
						form_invalid = true;
						$(this).val('');
					}
				}
			})
			if ( form_invalid === true ) {
				var today = new Date(),
				dd = ( '0' + today.getDate() ).slice(-2),
				mm = ( '0' + ( today.getMonth()+1 ) ).slice(-2),
					yyyy = today.getFullYear();
				alert ('You have entered an invalid date format. Please use the following format: yyyy/mm/dd. For example: [' + yyyy + '/' + mm + '/' + dd + ']' );
			} else {
				$('#charitable-donations-filter-modal form').submit();
			}
		});

		function dateIsValid( date_string ) {

			const regex = /^(\d{4})(\/|-)(\d{1,2})(\/|-)(\d{1,2})$/;

			if (date_string.match(regex) === null) {
			  return false;
			}

			const converted_date_string = date_string.replace(/\//g, '-');
			const date = new Date(converted_date_string);
			const timestamp = date.getTime();
			const iso_date_string = date.toISOString();

			if (typeof timestamp !== 'number' || Number.isNaN(timestamp)) {
			  return false;
			}

			return iso_date_string.startsWith(converted_date_string);
		}

		$( '#charitable-settings, body.post-type-campaign form#post, body.post-type-donation form#post' ).each( function(){
			CHARITABLE_ADMIN.Settings( $(this) );
		});

		$('body.post-type-campaign .handlediv, body.post-type-donation .handlediv').remove();
		$('body.post-type-campaign .hndle, body.post-type-donation .hndle').removeClass( 'hndle ui-sortable-handle' ).addClass( 'postbox-title' );

		setup_advanced_meta_box();
		setup_sortable_suggested_donations();
		setup_sortable_suggested_donations_mini(); // might need to run this again when fields are added/removed
		toggle_custom_donations_checkbox();
		setup_charitable_ajax();
		setup_charitable_toggle();
		// setup_dashboard_widgets();
		setup_campaign_end_date_field();
		setup_actions_form();
		setup_select2();
		setup_donor_select();
		setup_currency_inputs();
		setup_license_check();

		/* view donor history on donation post type pages */

		$(document).on( 'click', 'a.donor-list-view-donations', function( e ) {
			e.preventDefault();
			$( 'table.charitable-donor-history-table' ).toggle();
			$(this).text($(this).text() == 'Hide Donations' ? 'Show Donations' : 'Hide Donations');
		});

		/* activate color picker on settings pages that need it */
		if ( $( 'body.charitable_page_charitable-settings input.charitable-color-field' ).length ) {
			$( 'input.charitable-color-field' ).wpColorPicker();
		}

		$('[data-charitable-add-row]').on( 'click', function() {
			var type = $( this ).data( 'charitable-add-row' );

			if ( 'suggested-amount' === type ) {
				add_suggested_amount_row($(this));
			}

			return false;
		});

		$('.charitable-campaign-suggested-donations').on( 'click', '.charitable-delete-row', function() {
			delete_suggested_amount_row( $(this) );
			return false;
		});

		$('body').on( 'click', '[data-campaign-benefactor-delete]', function() {
			var $block = $( this ).parents( '.charitable-benefactor' ),
				data = {
					action 			: 'charitable_delete_benefactor',
					benefactor_id 	: $(this).data( 'campaign-benefactor-delete' ),
					nonce 			: $(this).data( 'nonce' )
				};

			$.ajax({
				type: "POST",
				data: data,
				dataType: "json",
				url: ajaxurl,
				xhrFields: {
					withCredentials: true
				},
				success: function (response) {
					if ( response.deleted ) {
						$block.remove();
					}
				}
			}).fail(function (data) {
				if ( window.console && window.console.log ) {
					// console.log( 'failed' );
					// console.log( data );
				}
			});

			return false;
		});

		$('#change-donation-status').on( 'change', function() {
			$(this).parents( 'form' ).submit();
		});

		if ( $('table.wp-list-table.posts').length > 0 && 'undefined' !== typeof CHARITABLE && null !== CHARITABLE && 'undefined' !== typeof CHARITABLE.banner ) {
			var banner_html = CHARITABLE.banner,
				col_count = 0;

			if ( banner_html === false || '' === banner_html) {
				return;
			}

			$('table.wp-list-table.posts thead tr:nth-child(1) td').each(function () {
				if ($(this).attr('colspan')) {
					col_count += +$(this).attr('colspan');
				} else {
					col_count++;
				}
			});


			$('table.wp-list-table.posts thead tr:nth-child(1) th').each(function () {
				if ($(this).attr('colspan')) {
					col_count += +$(this).attr('colspan');
				} else {
					col_count++;
				}
			});

			$('table.wp-list-table.posts').append('<tr class="child"><td colspan="' + ( col_count ) + '">' + CHARITABLE.banner + '</td></tr>');
		}

		// Handle Square webhooks checkbox visibility
		// Removed webhook checkbox functionality - now using conditional display in PHP
		var $legacyCheckbox = $('#charitable_settings_gateways_square_square_legacy_settings');
		var $legacyNonLegacyContent = $('tr.square-non-legacy');
		var $legacyLegacyContent = $('tr.square-legacy');

		if ($legacyCheckbox.is(':checked')) {
			$legacyNonLegacyContent.hide();
			$legacyLegacyContent.show();
		} else {
			$legacyNonLegacyContent.show();
			$legacyLegacyContent.hide();
		}
		$legacyCheckbox.on('change', function() {
			if (!$(this).is(':checked')) {
				$legacyNonLegacyContent.show();
				$legacyLegacyContent.hide();
			} else {
				$legacyNonLegacyContent.hide();
				$legacyLegacyContent.show();
			}
		});

																				// Test Mode Flash Effect
		// Check if we should highlight the test mode checkbox
		// Only trigger when sessionStorage indicates user came from admin bar badge
		if (sessionStorage.getItem('charitable_from_admin_bar') === 'true') {
			// Clear the sessionStorage flag immediately
			sessionStorage.removeItem('charitable_from_admin_bar');

			// Wait for the page to fully load
			$(window).on('load', function() {
				// Find the test mode checkbox row
				var $testModeRow = $('tr.charitable-settings-field.charitable-test-mode-checkbox');

				if ($testModeRow.length) {
					// Add flash animation class
					$testModeRow.addClass('charitable-test-mode-flash');

					// Remove the class after animation completes
					setTimeout(function() {
						$testModeRow.removeClass('charitable-test-mode-flash');
					}, 2000);

					// Scroll to the row
					$('html, body').animate({
						scrollTop: $testModeRow.offset().top - 100
					}, 800);
				}
			});
		}


	});

})( jQuery );