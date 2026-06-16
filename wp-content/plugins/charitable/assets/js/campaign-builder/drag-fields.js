/* global charitable_builder, wpchar, CharitableCampaignBuilder, CharitableUtils */

/**
 * Campaign Builder Builder Fields Drag-n-Drop module.
 *
 * @since 1.8.0
 */

'use strict';

var Charitable = window.Charitable || {};

Charitable.Admin = Charitable.Admin || {};
Charitable.Admin.Builder = Charitable.Admin.Builder || {};

Charitable.Admin.Builder.DragFields = Charitable.Admin.Builder.DragFields || ( function( document, window, $ ) {

	/**
	 * Elements holder.
	 *
	 * @since 1.8.0
	 *
	 * @type {object}
	 */
	let el = {};

	/**
	 * Runtime variables.
	 *
	 * @since 1.8.0
	 *
	 * @type {object}
	 */
	let vars = {};

	/**
	 * Layout field functions wrapper.
	 *
	 * @since 1.8.0
	 *
	 * @type {object}
	 */
	let fieldLayout;

	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.0
	 *
	 * @type {object}
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.8.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 1.8.0
		 */
		ready: function() {

			app.setup();
			app.initSortableFields();

			app.events();
		},

		/**
		 * Setup. Prepare some variables.
		 *
		 * @since 1.8.0
		 */
		setup: function() {

			// Cache DOM elements.
			el = {
				$builder:            $( '#charitable-builder' ),
				$sortableFieldsWrap: $( '.charitable-panel-fields .charitable-field-wrap' ),
				$sortableTabsWrap:   $( '.charitable-panel-fields .charitable-tab-wrap' ),
				$addFieldsButtons:   $( '.charitable-add-fields-button' ).not( '.not-draggable' ).not( '.warning-modal' ).not( '.charitable-not-available' ).not( '.charitable-not-installed' ),
				$preview:            $( '.charitable-preview' )
			};

			wpchar.debug('setup drag-fields'), 'drag-fields';
			wpchar.debug( el , 'drag-fields' );
		},

		/**
		 * Bind events.
		 *
		 * @since 1.8.0
		 */
		events: function() {

			el.$builder
				.on( 'charitableFieldDragToggle', app.fieldDragToggleEvent );

			el.$builder
				.on( 'charitableAddNewTab', app.initAfterNewTab );

			// el.$builder
			// 	.on( 'charitableFieldPreviewSwitchTabs', app.charitableFieldPreviewSwitchTabs );
		},

		initAfterNewTab: function( $field, groupID = false ) {

			wpchar.debug('groupID'), 'drag-fields';
			wpchar.debug( $field , 'drag-fields' );
			wpchar.debug( groupID , 'drag-fields' );
			wpchar.debug( el.$sortableTabsWrap , 'drag-fields' );

			el.$sortableTabsWrap = $( '.charitable-panel-fields .charitable-tab-wrap' );

			wpchar.debug( el.$sortableTabsWrap , 'drag-fields' );

			app.initSortableFields();

			CharitableCampaignBuilder.setCampaignNotSaved();

		},

		/**
		 * Disable drag & drop.
		 *
		 * @since 1.7.1
		 * @since 1.8.0 Moved from admin-builder.js.
		 */
		disableDragAndDrop: function() {
			wpchar.debug('disableDragAndDrop'), 'drag-fields';
			el.$addFieldsButtons.filter( '.ui-draggable' ).draggable( 'disable' );
			el.$sortableFieldsWrap.sortable( 'disable' );
			el.$sortableFieldsWrap.find( '.charitable-layout-section.ui-sortable' ).sortable( 'disable' ); // was layout-column
		},

		/**
		 * Enable drag & drop.
		 *
		 * @since 1.7.1
		 * @since 1.8.0 Moved from admin-builder.js.
		 */
		enableDragAndDrop: function() {
			wpchar.debug('enableDragAndDrop'), 'drag-fields';
			el.$addFieldsButtons.filter( '.ui-draggable' ).draggable( 'enable' );
			el.$sortableFieldsWrap.sortable( 'enable' );
			el.$sortableFieldsWrap.find( '.charitable-layout-section.ui-sortable' ).sortable( 'enable' ); // was layout-column
		},

		/**
		 * Show popup in case if field is not draggable, and cancel moving.
		 *
		 * @since 1.7.5
		 * @since 1.8.0 Moved from admin-builder.js.
		 *
		 * @param {jQuery}  $field    A field or list of fields.
		 * @param {boolean} showPopUp Whether the pop-up should be displayed on dragging attempt.
		 */
		fieldDragDisable: function( $field, showPopUp = true ) {

			if ( $field.hasClass( 'ui-draggable-disabled' ) ) {
				$field.draggable( 'enable' );

				return;
			}

			let startTopPosition;

			$field.draggable( {
				revert: true,
				axis: 'y',
				delay: 100,
				opacity: 0.75,
				cursor: 'move',
				start: function( event, ui ) {

					startTopPosition = ui.position.top;
				},
				drag: function( event, ui ) {

					if ( Math.abs( ui.position.top ) - Math.abs( startTopPosition ) > 15 ) {

						if ( showPopUp ) {
							app.youCantReorderFieldPopup();
						}

						return false;
					}
				}
			} );
		},

		/**
		 * Allow field dragging.
		 *
		 * @since 1.7.5
		 * @since 1.8.0 Moved from admin-builder.js.
		 *
		 * @param {jQuery} $field A field or list of fields.
		 */
		fieldDragEnable: function( $field ) {

			if ( $field.hasClass( 'ui-draggable' ) ) {
				return;
			}

			wpchar.debug('fieldDragEnable'), 'drag-fields';

			$field.draggable( 'disable' );
		},

		/**
		 * Show the error message in the popup that you cannot reorder the field.
		 *
		 * @since 1.7.1
		 * @since 1.8.0 Moved from admin-builder.js.
		 */
		youCantReorderFieldPopup: function() {

			$.confirm( {
				title: charitable_builder.heads_up,
				content: charitable_builder.field_cannot_be_reordered,
				icon: 'fa fa-exclamation-circle',
				type: 'red',
				buttons: {
					confirm: {
						text: charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ]
					},
				},
			} );
		},

		/**
		 * Event handler for `charitableFieldDragToggle` event.
		 *
		 * @since 1.8.0
		 *
		 * @param {object}  e  Event object.
		 * @param {numeric} id Field ID.
		 */
		fieldDragToggleEvent: function( e, id ) {

			const $field = $( `#charitable-field-${id}` );

			if (
				$field.hasClass( 'charitable-field-not-draggable' ) ||
				$field.hasClass( 'charitable-field-stick' )
			) {
				app.fieldDragDisable( $field );
				wpchar.debug('fieldDragToggleEvent fieldDragDisable'), 'drag-fields';
				return;
			}

			app.fieldDragEnable( $field );

			CharitableCampaignBuilder.setCampaignNotSaved();

		},

		/**
		 * Initialize sortable fields in the builder form preview area.
		 *
		 * @since 1.8.0
		 */
		initSortableFields: function() {

			wpchar.debug( 'initSortableFields' , 'drag-fields' );
			wpchar.debug( el.$sortableFieldsWrap , 'drag-fields' );
			wpchar.debug( el.$sortableTabsWrap , 'drag-fields' );

			// app.initSortableContainer( el.$sortableFieldsWrap );

			app.initSortableContainer( el.$sortableTabsWrap );

			el.$builder.find( '.charitable-panel-fields .charitable-field-wrap' ).each( function() {
				app.initSortableContainer( $( this ) );
			} );

			//app.fieldDragDisable( $( '.charitable-field-not-draggable, .charitable-field-stick' ) );
			app.initDraggableFields();
		},

		/**
		 * Initialize sortable container with fields.
		 *
		 * @since 1.8.0
		 *
		 * @param {jQuery} $sortable Container to make sortable.
		 */
		initSortableContainer: function( $sortable ) { // eslint-disable-line max-lines-per-function

			wpchar.debug('initSortableContainer'), 'drag-fields';

			let fieldId,
				fieldType,
				isNewField;

			$sortable.sortable( {
				items: '> .charitable-field:not(.charitable-field-stick):not(.no-fields-preview)',
				connectWith: '.charitable-field-wrap, .charitable-tab-wrap', // , .charitable-layout-column was in here
				delay: 100,
				opacity: 0.75,
				cursor: 'move',
				cancel: '.charitable-field-not-draggable',
				placeholder: 'charitable-field-drag-placeholder',
				appendTo: '.charitable-design-wrap',
				zindex: 10000,
				tolerance: 'pointer',
				distance: 1,
				start: function( e, ui ) {

					wpchar.debug( 'vars' , 'drag-fields' );
					wpchar.debug( vars , 'drag-fields' );
					wpchar.debug('start sortable via drag-fields.js'), 'drag-fields';
					wpchar.debug( 'ui' , 'drag-fields' );
					wpchar.debug( ui , 'drag-fields' );
					wpchar.debug( 'ui.item' , 'drag-fields' );
					wpchar.debug( ui.item , 'drag-fields' );
					fieldId    = ui.item.data( 'field-id' );
					fieldType  = ui.item.data( 'field-type' ) || vars.fieldType;
					// fieldType = $field.data( 'field-type' )

					if ( ! isNewField ) {
						vars.section = ui.item.closest('.charitable-field-section');
					} else {
						vars.section = false;
					}

					isNewField = typeof fieldId === 'undefined';
					wpchar.debug(' fieldId: ' + fieldId + ' - fieldType: ' + fieldType + ' - newField: ' + isNewField + ' - section: ' + vars.section), 'drag-fields';
					// $fieldOption = $( '#charitable-field-option-' + fieldId );

					vars.fieldReceived = false;
					vars.fieldRejected = false;
					vars.$sortableStart = $sortable;
					vars.fieldType = fieldType;
					vars.startPosition = ui.item.first().index();

					$(this).attr('data-previndex', ui.item.index());


				},
				beforeStop: function( e, ui ) { // eslint-disable-line

					wpchar.debug('beforeStop ==== sortable via drag-fields.js'), 'drag-fields';

				},
				stop: function( e, ui ) {

					wpchar.debug('stop ==== sortable via drag-fields.js'), 'drag-fields';

					wpchar.debug ( ui , 'drag-fields' );

					const $field = ui.item.first();

					ui.placeholder.removeClass( 'charitable-field-drag-not-allowed' );
					$field.removeClass( 'charitable-field-drag-not-allowed' );

					$field
					.removeClass( 'charitable-field-dragging' )
					.removeClass( 'charitable-field-drag-over' )
					.attr( 'style', '' );

					el.$builder.trigger( 'charitableFieldMove', ui );


				},
				over: function( e, ui ) { // eslint-disable-line complexity

					wpchar.debug('over ==== sortable via drag-fields.js'), 'drag-fields';

					const $field = ui.item.first(),
						$target = $(e.target),
						$placeholder = $target.find('.charitable-field-drag-placeholder'),
						isColumn = $target.hasClass('charitable-layout-column'),
						targetClass = isColumn ? ' charitable-field-drag-to-column' : '',
						helper = {
							width: $target.outerWidth(),
							height: $field.outerHeight()
						};


					fieldId = $field.data( 'field-id' );
					fieldType = $field.data( 'field-type' ) || vars.fieldType;
					isNewField = typeof fieldId === 'undefined';

					// Adjust helper size according to the placeholder size.
					$field
						.addClass( 'charitable-field-dragging' + targetClass )
						.css( {
							'width': isColumn ? helper.width - 5 : helper.width,
							'height': 'auto',
						} );

					// Adjust placeholder height according to the height of the helper.
					$placeholder
						.removeClass( 'charitable-field-drag-not-allowed' )
						.css( {
							'height': isNewField ? helper.height + 18 : helper.height,
						} );

					// Drop to this place is not allowed.
					if (
						! fieldLayout.isFieldAllowedInColum( fieldType ) &&
						isColumn
					) {
						$placeholder.addClass( 'charitable-field-drag-not-allowed' );
						$field.addClass( 'charitable-field-drag-not-allowed' );
					}

					// Skip if it is the existing field.
					if ( ! isNewField ) {
						return;
					}

					$field
						.addClass( 'charitable-field-drag-over' )
						.removeClass( 'charitable-field-drag-out' );


				},
				out: function( e, ui ) { // eslint-disable-line

					wpchar.debug('out ==== sortable via drag-fields.js'), 'drag-fields';


				},
				receive: function( e, ui ) { // eslint-disable-line complexity

					wpchar.debug('receive ==== sortable via drag-fields.js'), 'drag-fields';

					wpchar.debug ( ui , 'drag-fields' );
					wpchar.debug ( ui.helper , 'drag-fields' );
					wpchar.debug ( ui.item , 'drag-fields' );
					wpchar.debug ( ui.position , 'drag-fields' );
					wpchar.debug ( ui.originalPosition , 'drag-fields' );
					wpchar.debug ( ui.sender , 'drag-fields' );
					wpchar.debug ( ui.placeholder , 'drag-fields' );



				},
				update: function( e, ui ) { // eslint-disable-line

					wpchar.debug('update ==== sortable via drag-fields.js'), 'drag-fields';

					var newIndex = ui.item.index();
					var oldIndex = $(this).attr('data-previndex');
					var element_id = ui.item.attr('class');

					$(this).attr('data-current-index', ui.item.index());
					wpchar.debug ( $( this ) );


					$(this).removeAttr('data-previndex');

					// decalare a bunch of variables.

					const $field = $( ui.helper || ui.item );

					var area       = $sortable.hasClass('charitable-field-wrap') ? 'fields' : 'tabs',
						column_id  = 'fields' === area ? parseInt($field.closest('.charitable-field-column').data('column-id')) : false,
						section_id = 'fields' === area ? parseInt($field.closest('.charitable-field-section').data('section-id')) : false,
						tab_id     = 'tabs' === area ? parseInt($field.closest('.tab_content_item').data('tab-id')) : false,
						fieldId    = $field.data('field-id'),
						fieldType  = $field.data('field-type') || vars.fieldType;

					const 	isNewField            = typeof fieldId === 'undefined',
							isColumn              = $sortable.hasClass('charitable-layout-column'),
							numfieldsDonateButton = el.$preview.find('.charitable-field.charitable-field-donate-button').length,
							numfieldsDonationForm = el.$preview.find('.charitable-field.charitable-field-donation-form').length;

					wpchar.debug( $sortable );
					wpchar.debug(  $sortable.data( 'ui-sortable' ) );
					wpchar.debug(  $sortable.data( 'ui-sortable' ).currentItem );

					var currentIndex = $(this).attr('data-current-index');

					wpchar.debug ( currentIndex, 'currentIndex' );
					wpchar.debug ( $( this ) );
					let position = typeof currentIndex === 'undefined' ? -1 : currentIndex;
					// let position = typeof $sortable.data( 'ui-sortable' ).currentItem === 'undefined' ? -1 : $sortable.data( 'ui-sortable' ).currentItem.index();

					// disable any add field buttons until this is finished.
					el.$builder.find( '.charitable-add-fields .charitable-add-fields-button' ).prop( 'disabled', true );

					// you shouldn't try to add a donation form if a donation button is already added.
					if ( fieldType === 'donation-form' ) {

						if ( numfieldsDonateButton > 0 ) {

							$('.ui-draggable-dragging').css('opacity', '0');

							$.confirm( {
								title: charitable_builder.heads_up,
								content: charitable_builder.donation_form_donation_button,
								icon: 'fa fa-exclamation-circle',
								type: 'red',
								buttons: {
									confirm: {
										text: charitable_builder.remove_donation_button,
										btnClass: 'btn-confirm',
										keys: [ 'enter' ],
										action: function() {
											el.$preview.find('.charitable-field.charitable-field-donate-button').each( function() {
												var fieldDeleteId = $( this ).data('field-id');
												if ( parseInt( fieldDeleteId ) > 0 ) {
													CharitableCampaignBuilder.fieldDelete( fieldDeleteId, false );
												}
											} );
											$('.ui-draggable-dragging').css('opacity', '1.0');
											app.addFieldAfterDrag( $field, isNewField, isColumn, position, area, column_id, tab_id, section_id, fieldId, fieldType, $sortable )
										},
									},
									cancel: {
										text: charitable_builder.cancel,
										keys: [ 'esc' ],
										action: function() {
											el.$builder.find( '.charitable-add-fields .charitable-add-fields-button' ).prop( 'disabled', false );
											$field.remove();
											return;
										},
									},
								},
							} );

						} else if ( numfieldsDonationForm > 1 ) {

							$('.ui-draggable-dragging').css('opacity', '0');

							$.confirm( {
								title: charitable_builder.heads_up,
								content: charitable_builder.only_one_donation_form,
								icon: 'fa fa-exclamation-circle',
								type: 'red',
								buttons: {
									confirm: {
										text: charitable_builder.remove_donation_form,
										btnClass: 'btn-confirm',
										keys: [ 'enter' ],
										action: function() {
											el.$preview.find('.charitable-field.charitable-field-donation-form').each( function() {
												var fieldDeleteId = $( this ).data('field-id');
												if ( parseInt( fieldDeleteId ) > 0 ) {
													CharitableCampaignBuilder.fieldDelete( fieldDeleteId, false );
												}
											} );
											$('.ui-draggable-dragging').css('opacity', '1.0');
											app.addFieldAfterDrag( $field, isNewField, isColumn, position, area, column_id, tab_id, section_id, fieldId, fieldType, $sortable )
										},
									},
									cancel: {
										text: charitable_builder.cancel,
										keys: [ 'esc' ],
										action: function() {
											el.$builder.find( '.charitable-add-fields .charitable-add-fields-button' ).prop( 'disabled', false );
											$field.remove();
											return;
										},
									},
								},
							} );

						} else {

							app.addFieldAfterDrag( $field, isNewField, isColumn, position, area, column_id, tab_id, section_id, fieldId, fieldType, $sortable, vars.section )

						}

					} else if ( fieldType === 'donate-button' && numfieldsDonationForm > 0 ) {

						$('.ui-draggable-dragging').css('opacity', '0');

						$.confirm( {
							title: charitable_builder.heads_up,
							content: charitable_builder.donation_button_donation_form,
							icon: 'fa fa-exclamation-circle',
							type: 'red',
							buttons: {
								confirm: {
									text: charitable_builder.remove_donation_form,
									btnClass: 'btn-confirm',
									keys: [ 'enter' ],
									action: function() {
										el.$preview.find('.charitable-field.charitable-field-donation-form').each( function() {
											var fieldDeleteId = $( this ).data('field-id');
											if ( parseInt( fieldDeleteId ) > 0 ) {
												CharitableCampaignBuilder.fieldDelete( fieldDeleteId, false );
											}
										} );
										$('.ui-draggable-dragging').css('opacity', '1.0');
										app.addFieldAfterDrag( $field, isNewField, isColumn, position, area, column_id, tab_id, section_id, fieldId, fieldType, $sortable, vars.section )
									},
								},
								cancel: {
									text: charitable_builder.cancel,
									keys: [ 'esc' ],
									action: function() {
										el.$builder.find( '.charitable-add-fields .charitable-add-fields-button' ).prop( 'disabled', false );
										$field.remove();
										return;
									},
								},
							},
						} );

					} else {

						app.addFieldAfterDrag( $field, isNewField, isColumn, position, area, column_id, tab_id, section_id, fieldId, fieldType, $sortable, vars.section );

					}


				},
				change: function( e, ui ) { // eslint-disable-line

					wpchar.debug('change ==== sortable via drag-fields.js'), 'drag-fields';


				},
				sort: function( e, ui ) { // eslint-disable-line

					wpchar.debug('sort ==== sortable via drag-fields.js'), 'drag-fields';
					CharitableCampaignBuilder.setCampaignNotSaved();


				},
			} );
		},

		addFieldAfterDrag( $field, isNewField, isColumn, position, area, column_id, tab_id, section_id, fieldId, fieldType, $sortable, $section ) {

			if ( isNewField ) {

				// add the spinning loader icon to the field (because this requires an ajax call and there might be a delay)
				$field
					.addClass( 'charitable-field-drag-over charitable-field-drag-pending' )
					.removeClass( 'charitable-field-drag-out' )
					.append( CharitableCampaignBuilder.settings.spinnerInline )
					.css( 'width', '100%' );

					wpchar.debug( 'addFieldAfterDrag' );
					wpchar.debug( isColumn );
					wpchar.debug( position );

					CharitableCampaignBuilder.fieldAdd(
						vars.fieldType,
						{
							position:    isColumn ? position - 1 : position,
							placeholder: $field,
							$sortable:   $sortable,
							column_id:   column_id,
							tab_id:      tab_id,
							section_id:  section_id,
							area:        area,
							section:     false,
						}
					);

			} else {

					CharitableCampaignBuilder.fieldMove(
						vars.fieldType,
						{
							// position: isColumn ? position - 1 : position,
							field:     $field,
							fieldId:   fieldId,
							$sortable: $sortable,
							column_id: column_id,
							tab_id:    tab_id,
							section_id:  section_id,
							area:      area,
							section:   $section,
						}
					);

			}

			CharitableCampaignBuilder.setCampaignNotSaved();

		},

		getChildIndex(childDiv) {
			// Find the parent div of the given child div
			var parentDiv = childDiv.parent();

			// Find all the child divs within the parent div
			var childDivs = parentDiv.children();

			// Loop through the child divs to find the index of the given child div
			for (var i = 0; i < childDivs.length; i++) {
				if (childDivs[i] === childDiv[0]) {
					// Return the index (1-based) if found
					return i + 1;
				}
			}

			// If the given child div is not found, return -1
			return -1;
		},

		/**
		 * Initialize draggable fields buttons.
		 *
		 * @since 1.8.0
		 */
		initDraggableFields: function() {

			el.$addFieldsButtons.draggable( {
				connectToSortable: '.charitable-field-wrap, .charitable-tab-wrap', // .charitable-layout-column was in there
				delay: 200,
				cancel: false,
				scroll: false,
				opacity: 0.75,
				appendTo: '.charitable-design-wrap',
				zindex: 10000,
				helper: function() {

					let $this = $( this ),
						$el = $( '<div class="charitable-field-drag-out charitable-field-drag">' );

					vars.fieldType = $this.data( 'field-type' );



					wpchar.debug ('initDraggableFields start '), 'drag-fields';
					wpchar.debug( vars , 'drag-fields' );
					wpchar.debug( $el.html( $this.html(), 'drag-fields' ) );
					wpchar.debug ('initDraggableFields end'), 'drag-fields';

					return $el.html( $this.html() );
				},

				start: function( e, ui ) {

					let event = CharitableUtils.triggerEvent(
						el.$builder,
						'charitableFieldAddDragStart',
						[ vars.fieldType, ui ]
					);

					// wpchar.debug('event'), 'drag-fields';
					// wpchar.debug(event), 'drag-fields';
					// wpchar.debug(event.isDefaultPrevented), 'drag-fields';

					// Allow callbacks on `charitableFieldAddDragStart` to cancel dragging the field
					// by triggering `event.preventDefault()`.
					if ( event.isDefaultPrevented() ) {
						return false;
					}
				},
			} );
		},

		/**
		 * Revert moving the field to the column.
		 *
		 * @since 1.8.0
		 *
		 * @param {jQuery} $field Field object.
		 */
		revertMoveFieldToColumn: function( $field ) {

			const isNewField = $field.data( 'field-id' ) === undefined;

			if ( isNewField ) {

				// Remove the field.
				$field.remove();

				return;
			}

			// Restore existing field on the previous position.
			$field = $field.detach();

			const $fieldInStartPosition = vars.$sortableStart
				.find( '> .charitable-field' )
				.eq( vars.startPosition );

			$field
				.removeClass( 'charitable-field-dragging' )
				.removeClass( 'charitable-field-drag-over' )
				.attr( 'style', '' );

			if ( $fieldInStartPosition.length ) {
				$fieldInStartPosition.before( $field );

				return;
			}

			vars.$sortableStart.append( $field );
		},
	};

	/**
	 * Layout field functions holder.
	 *
	 * @since 1.8.0
	 *
	 * @type {object}
	 */
	fieldLayout = {

		/**
		 * Position field in the column inside the Layout Field.
		 *
		 * @since 1.8.0
		 *
		 * @param {number} fieldId   Field Id.
		 * @param {number} position  The new position of the field inside the column.
		 * @param {jQuery} $sortable Sortable column container.
		 **/
		positionFieldInColumn: function( fieldId, position, $sortable ) {

			if ( ! Charitable.Admin.Builder.FieldLayout ) {
				return;
			}

			Charitable.Admin.Builder.FieldLayout.positionFieldInColumn( fieldId, position, $sortable );
		},

		/**
		 * Receive field to column inside the Layout Field.
		 *
		 * @since 1.8.0
		 *
		 * @param {number} fieldId   Field Id.
		 * @param {number} position  Field position inside the column.
		 * @param {jQuery} $sortable Sortable column container.
		 **/
		receiveFieldToColumn: function( fieldId, position, $sortable ) {

			if ( ! Charitable.Admin.Builder.FieldLayout ) {
				return;
			}

			Charitable.Admin.Builder.FieldLayout.receiveFieldToColumn( fieldId, position, $sortable );
		},

		/**
		 * Update field options according to the position of the field.
		 * Event `charitableFieldOptionTabToggle` handler.
		 *
		 * @since 1.8.0
		 *
		 * @param {Event}  e       Event.
		 * @param {int}    fieldId Field id.
		 */
		fieldOptionsUpdate: function( e, fieldId ) {

			if ( ! Charitable.Admin.Builder.FieldLayout ) {
				return;
			}

			Charitable.Admin.Builder.FieldLayout.fieldOptionsUpdate( e, fieldId );
		},

		/**
		 * Reorder fields options of the fields in columns.
		 * It is not critical, but it's better to keep some order in the `fields` data array.
		 *
		 * @since 1.8.0
		 *
		 * @param {jQuery} $layoutField Layout field object.
		 */
		reorderLayoutFieldsOptions: function( $layoutField ) {

			if ( ! Charitable.Admin.Builder.FieldLayout ) {
				return;
			}

			Charitable.Admin.Builder.FieldLayout.reorderLayoutFieldsOptions( $layoutField );
		},

		/**
		 * Whether the field type is allowed to be in column.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} fieldType Field ty to check.
		 *
		 * @returns {boolean} True if allowed.
		 */
		isFieldAllowedInColum: function( fieldType ) {

			if ( ! Charitable.Admin.Builder.FieldLayout ) {
				return true;
			}

			return Charitable.Admin.Builder.FieldLayout.isFieldAllowedInColum( fieldType );
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) ); // eslint-disable-line

// Initialize.
Charitable.Admin.Builder.DragFields.init();
