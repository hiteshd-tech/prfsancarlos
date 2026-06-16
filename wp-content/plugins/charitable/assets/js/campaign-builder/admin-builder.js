/* global charitable_builder, charitable_campaign_builder_field_conditionals, wpchar, jconfirm, charitable_panel_switch, Charitable, CharitableCampaignEmbedWizard, CharitableCampaignCongratsWizard, wpCookies, tinyMCE, CharitableUtils, CharitableOnboarding */

var CharitableCampaignBuilder = window.CharitableCampaignBuilder || ( function( document, window, $ ) {

	var s,
		$builder,
		$builderForm,
		elements = {};

	/**
	 * Whether to show the close confirmation dialog or not.
	 *
	 * @since 1.8.0
	 *
	 * @type {boolean}
	 */
	var closeConfirmation = true;

	/**
	 * A field is adding.
	 *
	 * @since 1.8.0
	 *
	 * @type {boolean}
	 */
	var adding = false;

	var app = {

		settings: {
			spinner:          '<i class="charitable-loading-spinner"></i>',
			spinnerInline:    '<i class="charitable-loading-spinner charitable-loading-inline"></i>',
			tinymceDefaults:  { tinymce: { toolbar1: 'bold,italic,underline,blockquote,strikethrough,bullist,numlist,alignleft,aligncenter,alignright,undo,redo,link' }, quicktags: true },
			pagebreakTop:     false,
			pagebreakBottom:  false,
			upload_img_modal: false
		},

		/**
		 * Start the engine.
		 *
		 * @since 1.8.0
		 */
		init: function() {

			var that = this;

			charitable_panel_switch = true;
			s = this.settings;

			// Document ready.
			$( app.ready );

			// Page load.
			$( window ).on( 'load', function() {

				// In the case of jQuery 3.+, we need to wait for a ready event first.
				if ( typeof $.ready.then === 'function' ) {
					$.ready.then( app.load );
				} else {
					app.load();
				}
			} );

			$( window ).on( 'beforeunload', function() {
				if ( ! that.formIsSaved() && closeConfirmation ) {
					return charitable_builder.are_you_sure_to_close;
				}
			} );

		},

		/**
		 * Page load.
		 *
		 * @since 1.8.0
		 */
		load: function() {

			// Trigger initial save for new forms.
			if ( wpchar.getQueryString( 'newcampaign' ) ) {
				app.formSave( false );
			}

			// Allow callbacks to prevent making Campaign Builder ready...
			const event = CharitableUtils.triggerEvent( $builder, 'CharitableCampaignBuilderReady' );

			// ...by triggering `event.preventDefault()`.
			if ( event.isDefaultPrevented() ) {
				return false;
			}

			// Hide loading overlay and make the Form Builder ready to use.
			app.hideLoadingOverlay(); // eslint-disable-line

			// if a textarea for a coded field is present, init the code editor.
			app.initCodeEditor();

		},

		/**
		 * Document ready.
		 *
		 * @since 1.8.0
		 * @version 1.8.1.12 Added tooltip info for onboarding.
		 *
		 */
		ready: function() {

			if ( app.isVisitedViaBackButton() ) {
				location.reload();

				return;
			}

			app.showLoadingOverlay();

			s.version = '1.8.4.3';

			// Cache builder element.
			$builder     = $( '#charitable-builder' );
			$builderForm = $( '#charitable-builder-form' );

			// Action buttons.
			elements.$helpButton          = $( '#charitable-help' );
			elements.$previewButton       = $( '#charitable-preview-btn' );
			elements.$viewCampaignButton  = $( '#charitable-view-btn' );
			elements.$embedButton         = $( '#charitable-embed' );
			elements.$statusButton        = $( '#charitable-status-button' );
			elements.$saveButton          = $( '#charitable-save' );
			elements.$exitButton          = $( '#charitable-exit' );
			elements.$templateButton      = $( '.charitable-panel-template-button' );
			elements.$designButton        = $( '.charitable-panel-design-button' );
			elements.$settingsButton      = $( '.charitable-panel-settings-button' );
			elements.$marketingButton     = $( '.charitable-panel-marketing-button' );
			elements.$paymentButton       = $( '.charitable-panel-payment-button' );

			// Cache other elements.
			elements.$noFieldsOptions     = $( '.charitable-panel-fields .charitable-no-fields-holder .no-fields' );
			elements.$noFieldsPreview     = $( '.charitable-panel-fields .charitable-no-fields-holder .no-fields-preview' );
			elements.$formPreview         = $( '.charitable-panel-fields .charitable-preview-wrap' );
			elements.$revisionPreview     = $( '#charitable-panel-revisions .charitable-panel-content' );
			elements.$focusOutTarget      = null;

			elements.$preview             = $( '.charitable-preview' );
			elements.$panelDesign         = $( '#charitable-panel-design');

			elements.$nextFieldId         = $( '#charitable-field-id' );
			elements.$fieldOptions        = $( '#charitable-field-options' );
			elements.$fieldsPreviewWrap   = $( '.charitable-panel-fields .charitable-panel-content-wrap' ),
			elements.$sortableFieldsWrap  = $( '.charitable-panel-fields .charitable-field-wrap' );
			elements.$sortableTabsWrap    = $( '.charitable-panel-fields .charitable-tab-wrap' );
			elements.$sortableTabNav      = $( '.charitable-campaign-preview nav.charitable-campaign-preview-nav' );
			elements.$sortableTabContent  = $( '.charitable-campaign-preview .tab-content' );

			elements.$addFieldsButtons    = $( '.charitable-add-fields-button' ).not( '.not-draggable' ).not( '.warning-modal' ).not( '.charitable-not-available').not( '.charitable-not-installed' ).not( '.charitable-not-activated' ).not( '.charitable-installed-refresh' ).not( '.charitable-addon-file-missing').not( '.charitable-need-upgrade');

			elements.$templatePanel       = $( '#charitable-panel-template' );
			elements.$templatePreview     = $( '.charitable-template-preview' );

			elements.$settingsPanel       = $( '#charitable-panel-settings' );

			elements.$campaignID          = $( '#charitable-builder-form input[name="id"]' );
			elements.$templateID          = $( '#charitable-builder-form input[name="template_id"]' );
			elements.$templateLabel       = $( '#charitable-builder-form input[name="template_label"]' );
			elements.$postStatus          = $( '#charitable-builder-form input[name="post_status"]' );

			elements.$primaryThemeColorBase   = $( '#charitable-builder-form input[name="color_base_primary"]' );
			elements.$secondaryThemeColorBase = $( '#charitable-builder-form input[name="color_base_secondary"]' );
			elements.$tertiaryThemeColorBase  = $( '#charitable-builder-form input[name="color_base_tertiary"]' );
			elements.$buttonThemeColorBase    = $( '#charitable-builder-form input[name="color_base_button"]' );

			// Bind all actions.
			app.bindUIActions();

			// Setup/cache some vars not available before
			s.formID               = $builderForm.data( 'id' );
			s.templateID           = $builderForm.data( 'template-id' );
			s.templateLabel        = $builderForm.data( 'template-label' );

			s.primaryThemeColorBase   = s.primaryThemeColor = $( this ).closest('.charitable-template').data('template-primary');
			s.secondaryThemeColorBase = s.secondaryThemeColor = $( this ).closest('.charitable-template').data('template-secondary');
			s.tertiaryThemeColorBase  = s.tertiaryThemeColor = $( this ).closest('.charitable-template').data('template-tertiary');
			s.buttonThemeColorBase    = s.buttonThemeColor = $( this ).closest('.charitable-template').data('template-button');

			s.primaryThemeColorBase    = elements.$primaryThemeColorBase.val().length > 0 ? elements.$primaryThemeColorBase.val() : '';
			s.secondaryThemeColorBase  = elements.$secondaryThemeColorBase.val().length > 0 ? elements.$secondaryThemeColorBase.val() : '';
			s.tertiaryThemeColorBase   = elements.$tertiaryThemeColorBase.val().length > 0 ? elements.$tertiaryThemeColorBase.val() : '';
			s.buttonThemeColorBase     = elements.$buttonThemeColorBase.val().length > 0 ? elements.$buttonThemeColorBase.val() : '';
			s.primaryThemeColor        = '';
			s.secondaryThemeColor      = '';
			s.tertiaryThemeColor       = '';
			s.buttonThemeColor         = '';

			s.formSaved            = $builderForm.find( '#charitable-form-saved' ).val();
			s.formSavedStatus      = $builderForm.find( '#charitable-form-post-status' ).val();
			s.formSavedStatusLabel = $builderForm.find( '#charitable-form-post-status-label' ).val();
			s.formStatus           = s.formSavedStatus.length > 0 ? s.formSavedStatus : 'draft'; // this can change but draft is the default when a new campaign is created.
			s.formStatusLabel      = s.formSavedStatusLabel.length > 0 ? s.formSavedStatusLabel : 'Draft'; // this can change but draft is the default when a new campaign is created.

			// Global variables for quick reference to campaign data like titles and descriptions.
			s.campaignTitle       = $( 'input#charitable_settings_title' ).val();
			s.campaignDescription = $( 'div[data-special-type="campaign_description"] .campaign-builder-htmleditor' ).first().html();
			s.campaignOverview    = $( 'div[data-special-type="campaign_overview"] .campaign-builder-htmleditor' ).length > 0 ? $( 'div[data-special-type="campaign_overview"] .campaign-builder-htmleditor' ).first().html() : '';

			// Limit the number of tabs.
			s.maxNumberOfTabs = 4;

			s.denyList = {
				'donation-form': { 'donate-button' : 0, 'donation-form' : 0, 'donate-amount' : 0 },
				'donate-button': { 'donation-form' : 0 },
				'donate-amount': { 'donation-form' : 0 }
			};

			s.pagebreakTop    = $( '.charitable-pagebreak-top' ).length;
			s.pagebreakBottom = $( '.charitable-pagebreak-bottom' ).length;

			s.didInitHTMLEditorFields = false;
			s.quilled = [];
			s.currentModal = false;

			// Disable implicit submission for every form inside the builder.
			// All form values are managed by JS and should not be submitted by pressing Enter.
			$builder.on( 'keypress', '#charitable-builder-form :input:not(textarea)', function( e ) {
				if ( e.keyCode === 13 ) {
					e.preventDefault();
				}
			} );

			// If there is a section configured, display it.
			// Otherwise, we show the first panel by default.
			$( '.charitable-panel' ).each( function( index, el ) { // eslint-disable-line
				var $this       = $( this ),
					$configured = $this.find( '.charitable-panel-sidebar-section.configured' ).first();

				if ( $configured.length ) {
					var section = $configured.data( 'section' );
					$configured.addClass( 'active' );
					$this.find( '.charitable-panel-content-section-' + section ).show().addClass( 'active' );
					$this.find( '.charitable-panel-content-section-default' ).hide();
				} else {
					$this.find( '.charitable-panel-content-section').hide().removeClass( 'active' );
					$this.find( '.charitable-panel-content-section').first().show().addClass( 'active' );
					$this.find( '.charitable-panel-sidebar-section:first-of-type' ).addClass( 'active' );
				}

			} );

			// Secret builder hotkeys.
			app.builderHotkeys();

			if ( typeof jconfirm !== 'undefined' ) {

				// jquery-confirm defaults.
				jconfirm.defaults = {
					closeIcon: false,
					backgroundDismiss: false,
					escapeKey: true,
					animationBounce: 1,
					useBootstrap: false,
					theme: 'modern',
					boxWidth: '400px',
					animateFromElement: false,
					draggable: false,
					content: charitable_builder.something_went_wrong
				};

			}

			// $('#charitable_settings_title').attr('disabled', true );

			// Init all date pickers on the page.
			$( '.campaign-builder-datepicker' ).each( function() {
				app.initDatePicker( $( this ) );
			});

			// Init all unique UI tag fields on the page.
			$( '.campaign-tag-field' ).each( function() {
				app.initTagField( $( this ) );
			});

			// This handles the unique suggested donation area that creates dynamic rows.
			$( '.charitable-campaign-suggested-donations' ).each( function() {
				app.initSuggestedDonations( $( this ) );
			});
			$( '.charitable-campaign-suggested-donations-mini' ).each( function() {
				app.initSuggestedDonationsMini( $( this ) );
				app.updateSuggestdDonationsMiniRowsFromSettings( $( this ) );
			});

			app.initColorPicker();

			app.initTemplatePanel();

			app.initStatusButton();

			// Goal and End Date checks
			app.updateGoalRelatedItems();
			app.updateEndDateRelatedItems();

			// Refresh so that the campaign input box at the top can resize upon load of page.
			app.resizeTopCampaignTitleInputBox();
			if ( false !== app.hasTemplate() ) {
				var panel = ( null !== wpCookies.get( 'charitable_panel' ) ) ? wpCookies.get( 'charitable_panel' ).trim() : false;

				// if there is a cookie saved with a particular panel, go to that by default.
				if ( typeof panel !== 'undefined' && panel.length > 0 && panel !== 'template' ) {
					app.panelSwitch( panel );
				} else {
					app.panelSwitch( 'design' );
				}

				// If there is a template, onload check the fields present on preview screen and disable the ones that are not allowed.
				app.checkFieldAllow();

				// Cookies.
				app.redirectBasedOnCookies( panel );

			} else {

				app.forceTemplateSelect();
				// app.disableFormActions( false );

				// 1.8.1.12
				// temp: change mode to "new campaign mode".
				app.setCampaignTitleNotSet();

			}

			// Lite Modal.
			app.openModalButtonClick();

			app.confirmCampaignDeletion();

			$('.education-buttons button').click(function() {
				window.open("https://www.wpcharitable.com/pricing/");
			});

			// Misc.

			$(".campaign-builder-campaign-creator-id select").select2({
				templateResult: app.campaignCreatorFormatOptions
			});
			$(".campaign-builder-campaign-creator-id-mini select").select2({
				templateResult: app.campaignCreatorFormatOptions
			});

		},

		/**
		 * Return a "row" in the select2 HTML for creator campaign dropdown.
		 *
		 * @since 1.8.0
		 *
		 * @param {array} state The name of the panel.
		 *
		 */
		campaignCreatorFormatOptions( state ) {

			if (!state.id) { return state.text; }
				var $state = $(
				'<span class="charitable-select2-avatar"><img width="20px" height="20px" style="display: inline-block; float: left; margin-right: 10px;" src="' + state.element.dataset.avatar + '" /> ' + state.text + '</span><span class="charitable-select2-meta"> ' + state.element.dataset.meta + '</span>'
			);

			return $state;
		},

		/**
		 * If a cookie has saved where the user was last, attempt to bring them back to that screen.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} panel The name of the panel.
		 *
		 */
		redirectBasedOnCookies( panel = false ) {

			var cookieContentSection           = wpCookies.get( 'charitable_panel_content_section' ),
				cookieActiveFieldId            = '' !== wpCookies.get( 'charitable_panel_active_field_id' ) ? parseInt( wpCookies.get( 'charitable_panel_active_field_id' ) ) : '',
				cookieActiveTabId              = wpCookies.get( 'charitable_panel_tab_section_tab_id' ),
				cookieActiveLayoutOptionsGroup = wpCookies.get( 'charitable_panel_design_layout_options_group' );

			if ( $('.charitable-preview').is(':visible') && '' !== cookieActiveTabId && $('nav.charitable-campaign-preview-nav li[data-tab-id="' + cookieActiveTabId + '"] a').is(':visible') ) {

				$('.charitable-preview nav.charitable-campaign-preview-nav li[data-tab-id="' + cookieActiveTabId + '"] a').click();

			} else if ( $('.charitable-preview').is(':visible') && $('#charitable-field-' + cookieActiveFieldId + ' a').is(':visible') ) {

				app.clickFieldEdit( cookieActiveFieldId );

			} else if ( $('.charitable-panel-sidebar').is(':visible') && $('a.charitable-panel-sidebar-section[data-section="' + cookieContentSection + '"').is(':visible') ) { // settings section

				$('a.charitable-panel-sidebar-section[data-section="' + cookieContentSection + '"').click();

			} else if ( '' !== cookieActiveFieldId && $('#charitable-field-' + cookieActiveFieldId ).is(':visible') ) {

				$('#charitable-field-' + cookieActiveFieldId ).click();

			} else if ( $('.charitable-panel-sidebar').is(':visible') && cookieContentSection && cookieContentSection !== 'layout-options' && $('.charitable-tabs').is(':visible') && $('.charitable-tabs li#' + cookieContentSection + ' a').is(':visible') ) { // design tabs

				$('.charitable-tabs li#' + cookieContentSection + ' a').click();

			} else if ( false === panel && false !== app.hasTemplate() ) {

				app.panelSwitch( 'design' );

			} else {

				wpchar.debug('redirectBasedOnCookies nothing');
			}

		},

		/**
		 * Simulates a click on an edit fields as if the user did it themselves.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} fieldId Upgrade modal type (with video or not).
		 *
		 */
		clickFieldEdit( fieldId ) {

			$('#charitable-field-' + fieldId + ' a.charitable-field-edit').click();

		},

		/**
		 * Reassigns the .tab-content to the variable, making sure it holds the latest.
		 *
		 * @since 1.8.0
		 *
		 */
		refreshTabFieldsSortDrag() {

			elements.$sortableTabContent  = $( '.charitable-campaign-preview .tab-content' );

		},

		/**
		 * Attempts to determine if the campaign has a template assigned to it or not.
		 *
		 * @since 1.8.0
		 *
		 */
		hasTemplate() {

			if ( typeof s.templateID !== 'undefined' && s.templateID.length > 0 ) {
				return true;
			}

			var templateID = parseInt( elements.$templateID.val() );

			if ( templateID > 0 ) {
				return true;
			}

			return false;

		},

		/**
		 * Changes UI so user can select a template and can't select other UI.
		 *
		 * @since 1.8.0
		 *
		 */
		forceTemplateSelect() {

			$( '#charitable-panels-toggle' ).find( 'button' ).removeClass( 'active' ).addClass('disabled');
			elements.$templateButton.removeClass('disabled').addClass( 'active' );
			app.panelSwitch( 'template' );

		},

		/**
		 * Removes the disabled UI that foreced a template selection.
		 *
		 * @since 1.8.0
		 *
		 */
		unforceTemplateSelect() {

			$( '#charitable-panels-toggle' ).find( 'button' ).removeClass( 'disabled' );

		},

		/**
		 * Changes UI so user cannot visit the template tab to change a template.
		 *
		 * @since 1.8.0
		 *
		 */
		disableTemplateSelect() {

			elements.$templateButton.removeClass('active').addClass( 'disabled' );

		},

		/**
		 * When a template is seleced, certain UI and CSS classes need to be updated.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} templateID The 'slug' of the template.
		 * @param {string} templateLabel The text/label of the template.
		 *
		 */
		updateTemplateID( templateID = '', templateLabel = '' ) {

			// update the invisible value.
			elements.$templateID.val( templateID );
			elements.$templateLabel.val( templateLabel );

			// update the admin preview CSS stylesheet.
			app.updateThemeCSS( null, templateID, s.primaryThemeColor, s.secondaryThemeColor, s.tertiaryThemeColor, s.buttonThemeColor, false );

			// update some critical preview CSS.
			$('.charitable-preview').removeClass( function( index, className ) {
				return (className.match (/(^|\s)charitable-builder-template-\S+/g) || []).join(' ');
			}).addClass( 'charitable-builder-template-' + templateID );

			// update template name in preview top bar
			if ( templateLabel !== '' ) {
				$('.charitable-preview-top-bar .charitable-campaign-theme-label').text( templateLabel );
			} else if ( templateID !== '' ) {
				$('.charitable-preview-top-bar .charitable-campaign-theme-label').text( templateID );
			}

		},

		/**
		 * Update Theme CSS file for preview
		 *
		 * @since 1.8.0
		 *
		 * @param {string} whatChanged 'primary', etc. helpful for updating JUST the right CSS file.
		 * @param {string} templateID The 'slug' of the template.
		 * @param {string} primaryThemeColor Full alphanumeric hex value without hashtag.
		 * @param {string} secondaryThemeColor
		 * @param {string} tertiaryThemeColor
		 * @param {string} buttonThemeColor
		 *
		 */

		updateThemeCSS( whatChanged = null, templateID = '', primaryThemeColor = '', secondaryThemeColor = '', tertiaryThemeColor = '', buttonThemeColor = '', justColors = true ) {

			// There needs to be a templateID or we can't link to the CSS file.
			if ( '' === templateID && '' === s.templateID ) {
				return;
			}

			if ( typeof s.primaryThemeColor === 'undefined' || '' === primaryThemeColor ) {
				if ( typeof s.primaryThemeColor !== 'undefined' && '' !== s.primaryThemeColor ) {
					primaryThemeColor = s.primaryThemeColor;
				} else if ( typeof s.primaryThemeColorBase !== 'undefined' && '' !== s.primaryThemeColorBase ) {
					primaryThemeColor = s.primaryThemeColorBase;
				} else {
					primaryThemeColor = '000000'; // shouldn't get to this point.
				}
			}

			if ( typeof s.secondaryThemeColor === 'undefined' || '' === secondaryThemeColor ) {
				if ( typeof s.secondaryThemeColor !== 'undefined' && '' !== s.secondaryThemeColor ) {
					secondaryThemeColor = s.secondaryThemeColor;
				} else if ( typeof s.secondaryThemeColorBase !== 'undefined' && '' !== s.secondaryThemeColorBase ) {
					secondaryThemeColor = s.secondaryThemeColorBase;
				} else {
					secondaryThemeColor = '000000'; // shouldn't get to this point.
				}
			}

			if ( typeof s.tertiaryThemeColor === 'undefined' || '' === tertiaryThemeColor ) {
				if ( typeof s.tertiaryThemeColor !== 'undefined' && '' !== s.tertiaryThemeColor ) {
					tertiaryThemeColor = s.tertiaryThemeColor;
				} else if ( typeof s.tertiaryThemeColorBase !== 'undefined' && '' !== s.tertiaryThemeColorBase ) {
					tertiaryThemeColor = s.tertiaryThemeColorBase;
				} else {
					tertiaryThemeColor = '000000'; // shouldn't get to this point.
				}
			}

			if ( typeof s.buttonThemeColor === 'undefined' || '' === buttonThemeColor ) {
				if ( typeof s.buttonThemeColor !== 'undefined' && '' !== s.buttonThemeColor ) {
					buttonThemeColor = s.buttonThemeColor;
				} else if ( typeof s.buttonThemeColorBase !== 'undefined' && '' !== s.buttonThemeColorBase ) {
					buttonThemeColor = s.buttonThemeColorBase;
				} else {
					buttonThemeColor = '000000'; // shouldn't get to this point.
				}
			}

			// ensure colors are passed raw hex without the hashtag
			primaryThemeColor   = primaryThemeColor.replace(/[^a-zA-Z 0-9]+/g, '');
			secondaryThemeColor = secondaryThemeColor.replace(/[^a-zA-Z 0-9]+/g, '');
			tertiaryThemeColor  = tertiaryThemeColor.replace(/[^a-zA-Z 0-9]+/g, '');
			buttonThemeColor    = buttonThemeColor.replace(/[^a-zA-Z 0-9]+/g, '');

			var colorQueryString = 'p=' + primaryThemeColor + '&s=' + secondaryThemeColor + '&t=' + tertiaryThemeColor + '&b=' + buttonThemeColor + '&ver=' + s.version;

			// add class to entire preview area

			// update the admin preview CSS stylesheet.
			// $("head link[rel='stylesheet']").last().after('<link rel="stylesheet" id="charitable-builder-template-preview-theme-css-temp" href="' + charitable_builder.charitable_assets_dir + 'css/campaign-builder/themes/admin/' + templateID + '.php?' + colorQueryString + '" type="text/css" media="screen">');

			if ( justColors === false ) {
				$('link[id="charitable-builder-template-preview-theme-css"]').attr('href', charitable_builder.charitable_rest_url + 'campaign-css-admin/' + templateID + '?' + colorQueryString );
			}

			if ( whatChanged !== null ) {
				app.replaceCSSFile( whatChanged, templateID, colorQueryString );
			} else {
				app.replaceCSSFile( 'primary', templateID, colorQueryString );
				app.replaceCSSFile( 'secondary', templateID, colorQueryString );
				app.replaceCSSFile( 'tertiary', templateID, colorQueryString );
				app.replaceCSSFile( 'button', templateID, colorQueryString );
			}

		},

		/**
		 * Replace Theme CSS file for preview
		 *
		 * @since 1.8.0
		 *
		 * @param {string} whatChanged 'primary', etc. helpful for updating JUST the right CSS file.
		 * @param {string} templateID The 'slug' of the template.
		 * @param {string} colorQueryString
		 *
		 */
		replaceCSSFile: function ( whatChanged, templateID, colorQueryString ) {

			$('head').find('link#charitable-builder-template-preview-theme-colors-' + whatChanged + '-css').remove();
			$('head').append('<link rel="stylesheet" data-color-type="' + whatChanged + '" id="charitable-builder-template-preview-theme-colors-temp" href="' + charitable_builder.charitable_rest_url + 'campaign-css-admin/' + templateID + '-colors?' + colorQueryString + '" type="text/css" media="screen">');

			$('#charitable-design-wrap').addClass('loading');

			setTimeout(function() {
				$('head').find('#charitable-builder-template-preview-theme-colors-temp').attr('id', 'charitable-builder-template-preview-theme-colors-' + whatChanged + '-css');
				$('#charitable-design-wrap').removeClass('loading');
			}, 250);

		},

		/**
		 * Search template callback.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} e Event object.
		 */
		searchTemplate: function( e ) {

			e.preventDefault();

			let $active     = $('.charitable-setup-templates-categories li.active'),
				category    = $active.data( 'category' ),
				searchQuery = $( this ).val();

			app.performSearch( searchQuery, category );

		},

		/**
		 * Perform search value.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} query Value to search.
		 * @param {string} category Another value to search.
		 */
		performSearch( query = '', category = '' ) {

			let $templateList = elements.$templatePreview.find('.charitable-template-list');

			if ( query === '' && ( category === '' || category === 'all' ) ) {
				$templateList.find('.charitable-template-list-container-item').removeClass('charitable-hidden');
			} else {
				$templateList.find('.charitable-template-list-container-item').addClass('charitable-hidden');
				if ( query !== '' && category !== '' && category !== 'all'  ) {
					$templateList.find('.charitable-template[data-template-tags*="' + query + '"][data-template-categories*="' + category + '"]').parent().removeClass('charitable-hidden');
				} else if ( query !== '' && ( category === '' || category === 'all'  ) ) {
					$templateList.find('.charitable-template[data-template-tags*="' + query + '"],.charitable-template[data-template-categories*="' + query + '"]').parent().removeClass('charitable-hidden');
				} else {
					$templateList.find('.charitable-template[data-template-tags*="' + category + '"],.charitable-template[data-template-categories*="' + category + '"]').parent().removeClass('charitable-hidden');
				}
			}

			var numItems = $('.charitable-template-list-container-item').not('.hidden').length;

			$( '.charitable-templates-no-results' ).toggle( ! numItems );

			if ( ! elements.$templatePreview.find('#charitable-setup-template-search').val() ) {
				elements.$templatePreview.find('.charitable-setup-templates-search-wrap .fa-close').hide();
			} else {
				elements.$templatePreview.find('.charitable-setup-templates-search-wrap .fa-close').show();
			}

			app.showCheckTemplateList();
			app.showUpgradeBanner();

		},

		/**
		 * Actions after a user selects a category in the template screen.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} e Event object.
		 */
		selectCategory: function( e ) {

			e.preventDefault();

			let $item       = $( this ),
				$active     = $item.closest( 'ul' ).find( '.active' ),
				category    = $item.data( 'category' ),
				searchQuery = $( '#charitable-setup-template-search' ).val();

			$active.removeClass( 'active' );
			$item.addClass( 'active' );

			app.performSearch( searchQuery, category );


		},

		/**
		 * Show upgrade banner if licence type is less than Pro.
		 *
		 * @since 1.8.0
		 */
		showUpgradeBanner: function() {

			if ( ! $( '#tmpl-charitable-templates-upgrade-banner' ).length ) {
				return;
			}

			if ( $('#charitable-template-list .charitable-template-upgrade-banner').length > 0 ) {
				$('#charitable-template-list .charitable-template-upgrade-banner').remove();
			}

			if ( ! app.isFunction( wp.template ) ) { // eslint-disable-line
				wpchar.debug( 'wp.template not a function' );
				return;
			} else {
				wpchar.debug( 'wp.template is a function' );
			}

			let template = wp.template( 'charitable-templates-upgrade-banner' );  // eslint-disable-line

			if ( ! template ) {
				return;
			}

			const 	$templates   = $( '#charitable-template-list .charitable-template-list-container-item:not(.charitable-hidden)'),
					$insertPoint = $( '#charitable-template-list .charitable-template-list-container-item');

			if ( $templates.length > 5 ) {
				$ ('#charitable-template-list .charitable-template-list-container > div:last-child').after( template() );
				return;
			}

			$insertPoint.last().after( template() );

		},

		/**
		 * Called when the template list probably has changed - see if we hide/show the sections if there is/are no templates for those sections.
		 *
		 * @since 1.8.1.12
		 */
		showCheckTemplateList: function() {

			// if there are no more template items (.charitable-template-list-container-item) with the class of blank then hide the blank section (otherwise show it).
			if ( $('#charitable-template-list .charitable-template-list-container-item.blank:not(.charitable-hidden)').length === 0 ) {
				$('#charitable-template-list .charitable-template-list-section-blank').addClass('charitable-hidden');
				$('#charitable-template-list .charitable-template-list-section-prebuilt').addClass('charitable-hidden');
			} else {
				$('#charitable-template-list .charitable-template-list-section-blank').removeClass('charitable-hidden');
				$('#charitable-template-list .charitable-template-list-section-prebuilt').removeClass('charitable-hidden');
			}

			// if there are no more template items (.charitable-template-list-container-item) without the class of blank then hide the prebuuilt section (otherwise show it).
			if ( $('#charitable-template-list .charitable-template-list-container-item.prebuilt:not(.charitable-hidden)').length === 0 ) {
				$('#charitable-template-list .charitable-template-list-section-prebuilt').addClass('charitable-hidden');
			} else {
				$('#charitable-template-list .charitable-template-list-section-prebuilt').removeClass('charitable-hidden');
			}

			if ( $('#charitable-template-list .charitable-template-list-container-item.prebuilt:not(.charitable-hidden)').length > 0 && $('#charitable-template-list .charitable-template-list-container-item.blank:not(.charitable-hidden)').length === 0 ) {
				$('#charitable-template-list .charitable-template-list-section-prebuilt').addClass('charitable-hidden');
			}

		},

		/**
		 * Upon page load (or general resets) make sure the top button is showing the proper status and dropdown has the right available status choices.
		 *
		 * @since 1.8.0
		 */
		initStatusButton: function( formStatus = '', formStatusLabel = '' ) {


			var $statusDropdown = $( 'ul#charitable-status-dropdown' );

			if ( formStatus == '' && s.formStatus.length > 0 ) {
				formStatus      = s.formStatus;
			}
			if ( formStatusLabel == '' && s.formStatusLabel.length > 0 ) {
				formStatusLabel      = s.formStatusLabel;
			}

			// make sure dropdown isn't visible
			$statusDropdown.addClass('charitable-hidden');
			$( "#charitable-status-button" ).removeClass('active');

			// change status
			$( '#charitable-status-button span.text').html( formStatusLabel );
			$( '#charitable-status-button').attr('data-status', formStatus );

			$statusDropdown.find('a').removeClass('charitable-hidden');
			$statusDropdown.find('a.switch-' + formStatus).addClass('charitable-hidden');
			$statusDropdown.find('a.' + formStatus).addClass('charitable-hidden');

			if ( formStatus === 'draft' ) {
				// $statusDropdown.find('a[class*="switch-"]').addClass('charitable-hidden');
			} else if ( formStatus === 'publish' ) {
				// $statusDropdown.find('a.draft').addClass('charitable-hidden');
				// $statusDropdown.find('a.pending').addClass('charitable-hidden');
				// $statusDropdown.find('a.review').addClass('charitable-hidden');
			}

		},

		/**
		 * Init the template panel and setup the actions, etc.
		 *
		 * @since 1.8.0
		 */
		initTemplatePanel: function() {

			$( '#charitable-template-container' )
				.on( 'keyup', '#charitable-setup-template-search', app.searchTemplate )
				.on( 'click', '.charitable-setup-templates-categories li', app.selectCategory );

			if ( ! s.didInitHTMLEditorFields ) {

				$( '.campaign-builder-htmleditor' ).each( function() {
					app.initHTMLEditorFields( $( this ) );
				});
				$( '.campaign-builder-htmleditor-min' ).each( function() {
					app.initHTMLEditorFields( $( this ), true );
				});

				s.didInitHTMLEditorFields = true;

			}

			// feedback form.
			elements.$templatePreview.on( 'click', '.charitable-template-lite-to-pro a.button, .charitable-setup-templates-feedback a.send-feedback', function( e ) {
				e.preventDefault();

				// Scroll to the top of the charitable-panel-content-wrap div container.
				$('.charitable-panel-content-wrap').animate({
					scrollTop: 0
				}, 500);

				$('.charitable-template-list-container').addClass('disabled');
				$('.charitable-feedback-form-container').after('<div id="charitable-builder-underlay" class="charitable-builder-underlay"></div>');
				$('.charitable-feedback-form-container').css("opacity", "100" ).css("visibility", "visible" );

				$('.charitable-feedback-form-container').find('.charitable-feedback-form-interior').removeClass('charitable-hidden');
				$('.charitable-feedback-form-container').find('textarea').val('');
				$('.charitable-feedback-form-interior-confirmation').addClass('charitable-hidden');

			});

			// Close button on feedback form.
			$builderForm.on( 'click', '#charitable-feedback-form .charitable-templates-close-icon, #charitable-feedback-form-confirmation .charitable-templates-close-icon', function( e ) {
				e.preventDefault();

				$('.charitable-template-list-container').removeClass('disabled');
				$('.charitable-builder-underlay').remove();
				$('#charitable-panel-template .charitable-feedback-form-container').css("opacity", "0" ).css("visibility", "hidden" );

			});

			// Template Preview form.
			$builderForm.on( 'click', '.template-buttons a.preview-campaign', function( e ) {
				e.preventDefault();

				$('.charitable-template-list-container').addClass('disabled');
				$('.charitable-feedback-form-container').after('<div id="charitable-builder-underlay" class="charitable-builder-underlay"></div>');

				// Dynamically update the popup with elemnets from the selected/clicked on <li> element.
				const 	previewBox          = $( this ).closest('.charitable-template'),
						templateLabel       = typeof previewBox.data('template-label') !== 'undefined' ? previewBox.data('template-label') : '',
						templateDescription = typeof previewBox.data('template-description') !== 'undefined' ? previewBox.data('template-description') : '',
						templatePreviewURL  = typeof previewBox.data('template-preview-url') !== 'undefined' ? previewBox.data('template-preview-url') : '',
						templateCode        = typeof previewBox.data('template-code') !== 'undefined' ? previewBox.data('template-code') : '';

				$('#charitable-builder-modal-template-preview').find('h4').html( templateLabel );
				$('#charitable-builder-modal-template-preview').find('.charitable-templates-preview-description').html( '<p>' + templateDescription + '</p>' );
				$('#charitable-builder-modal-template-preview').find('img').attr('src', templatePreviewURL );
				$('#charitable-builder-modal-template-preview').find('img').attr('alt', templateLabel );
				$('#charitable-builder-modal-template-preview').find('a.button-link').attr('data-template-id', templateCode );

				$('.charitable-builder-modal.charitable-builder-modal-template-preview').addClass('active');

			});
			$builderForm.on( 'click', '#charitable-templates-preview-form .charitable-templates-close-icon', function( e ) {
				e.preventDefault();

				$('.charitable-template-list-container').removeClass('disabled');
				$('#charitable-builder-underlay').remove();
				$('.charitable-builder-modal.charitable-builder-modal-template-preview').removeClass('active');

			});

			// Tab and filtering.
			elements.$templatePreview.on( 'click', 'nav.charitable-template-tabs a', function( e ) {
				e.preventDefault();

				var $this = $( this ),
					templateTabFilter = ( $this ).data('template-tab-filter') ? ( $this ).data('template-tab-filter') : false,
					$templateList = elements.$templatePreview.find('.charitable-template-list ');

				elements.$templatePreview.find('nav.charitable-template-tabs a').removeClass('active');
				$this.addClass('active');

				$templateList.find('.charitable-template').removeClass('hidden');

				if ( templateTabFilter ) {
					$templateList.find('.charitable-template[data-template-type!="' + templateTabFilter + '"]').addClass('hidden');
				}

			});

			// Show default templates.
			elements.$templatePreview.on( 'click', 'a.charitable-trigger-blank', function( e ) {
				e.preventDefault();
				$('#charitable-template-list .charitable-template-upgrade-banner').remove();
				elements.$templatePreview.find('#charitable-setup-template-search').val('simple').trigger('keyup').addClass('highlighted').addClass('with-x');
				elements.$templatePreview.find('.charitable-setup-templates-categories').find('li[data-category=all').click();
				app.showCheckTemplateList();
				app.showUpgradeBanner();
			});

			elements.$templatePreview.on( 'click', 'i.fa-close', function( e ) { // eslint-disable-line
				elements.$templatePreview.find('#charitable-setup-template-search').val('').trigger('keyup').removeClass('highlighted');
			});


			// Create campaign.
			elements.$templatePreview.on( 'click', '.button.create-campaign', function( e ) {
				e.preventDefault();

				if ( app.hasTemplate() ) {

					// Display error in modal window.
					$.confirm( {
						title: charitable_builder.heads_up,
						content: '<p>' + charitable_builder.error_already_started_campaign + '</p>',
						icon: 'fa fa-exclamation-circle',
						type: 'orange',
						buttons: {
							confirm: {
								text: charitable_builder.ok,
								btnClass: 'btn-confirm',
								keys: [ 'enter' ],
								action: function() {
									s.templateID    = $( this ).closest('.charitable-template').data('template-code');
									s.templateLabel = $( this ).closest('.charitable-template').data('template-label');

									s.primaryThemeColorBase = s.primaryThemeColor = $( this ).closest('.charitable-template').data('template-primary');
									s.secondaryThemeColorBase = s.secondaryThemeColor = $( this ).closest('.charitable-template').data('template-secondary');
									s.tertiaryThemeColorBase = s.tertiaryThemeColor = $( this ).closest('.charitable-template').data('template-tertiary');
									s.buttonThemeColorBase = s.buttonThemeColor = $( this ).closest('.charitable-template').data('template-button');

									elements.$primaryThemeColorBase.val( s.primaryThemeColorBase );
									elements.$secondaryThemeColorBase.val( s.secondaryThemeColorBase );
									elements.$tertiaryThemeColorBase.val( s.tertiaryThemeColorBase );
									elements.$buttonThemeColorBase.val( s.buttonThemeColorBase );

									$('input[name="layout__advanced__theme_color_primary"]').val( s.primaryThemeColor );
									$('input[name="layout__advanced__theme_color_secondary"]').val( s.secondaryThemeColor );
									$('input[name="layout__advanced__theme_color_tertiary"]').val( s.tertiaryThemeColor );
									$('input[name="layout__advanced__theme_color_button"]').val( s.buttonThemeColor );

									document.querySelector('input[name="layout__advanced__theme_color_primary"]').dispatchEvent(new Event('input', { bubbles: true }));
									document.querySelector('input[name="layout__advanced__theme_color_secondary"]').dispatchEvent(new Event('input', { bubbles: true }));
									document.querySelector('input[name="layout__advanced__theme_color_tertiary"]').dispatchEvent(new Event('input', { bubbles: true }));
									document.querySelector('input[name="layout__advanced__theme_color_button"]').dispatchEvent(new Event('input', { bubbles: true }));

									app.restartForm( s.templateID, null, s.templateLabel );
									app.restartTemplateScreen( s.templateID );

								}
							},
							cancel: {
								text: charitable_builder.cancel,
								keys: [ 'esc' ]
							}
						}
					} );

				} else {

					s.templateID    = $( this ).closest('.charitable-template').data('template-code');
					s.templateLabel = $( this ).closest('.charitable-template').data('template-label');

					// Auto-populate campaign title with template name if title is blank (for new campaigns only).
					var $titleInput = $( 'input#charitable_settings_title' );
					var currentTitle = $titleInput.val() || s.campaignTitle || '';
					if ( ( ! currentTitle || $.trim( currentTitle ) === '' ) && s.templateLabel ) {
						app.updateFormUI( 'title', s.templateLabel );
					}

					s.primaryThemeColorBase = s.primaryThemeColor = $( this ).closest('.charitable-template').data('template-primary');
					s.secondaryThemeColorBase = s.secondaryThemeColor = $( this ).closest('.charitable-template').data('template-secondary');
					s.tertiaryThemeColorBase = s.tertiaryThemeColor = $( this ).closest('.charitable-template').data('template-tertiary');
					s.buttonThemeColorBase = s.buttonThemeColor = $( this ).closest('.charitable-template').data('template-button');

					elements.$primaryThemeColorBase.val( s.primaryThemeColorBase );
					elements.$secondaryThemeColorBase.val( s.secondaryThemeColorBase );
					elements.$tertiaryThemeColorBase.val( s.tertiaryThemeColorBase );
					elements.$buttonThemeColorBase.val( s.buttonThemeColorBase );

					$('input[name="layout__advanced__theme_color_primary"]').val( s.primaryThemeColor );
					$('input[name="layout__advanced__theme_color_secondary"]').val( s.secondaryThemeColor );
					$('input[name="layout__advanced__theme_color_tertiary"]').val( s.tertiaryThemeColor );
					$('input[name="layout__advanced__theme_color_button"]').val( s.buttonThemeColor );

					document.querySelector('input[name="layout__advanced__theme_color_primary"]').dispatchEvent(new Event('input', { bubbles: true }));
					document.querySelector('input[name="layout__advanced__theme_color_secondary"]').dispatchEvent(new Event('input', { bubbles: true }));
					document.querySelector('input[name="layout__advanced__theme_color_tertiary"]').dispatchEvent(new Event('input', { bubbles: true }));
					document.querySelector('input[name="layout__advanced__theme_color_button"]').dispatchEvent(new Event('input', { bubbles: true }));

					app.updateTemplateID( s.templateID, s.templateLabel );
					app.unforceTemplateSelect();
					app.enableFormActions();
					app.restartForm( s.templateID, 'design', s.templateLabel );
					app.restartTemplateScreen( s.templateID );

					if ( ! s.didInitHTMLEditorFields ) {

						$( '.campaign-builder-htmleditor' ).each( function() {
							app.initHTMLEditorFields( $( this ) );
						});
						$( '.campaign-builder-htmleditor-min' ).each( function() {
							app.initHTMLEditorFields( $( this ), true );
						});

						s.didInitHTMLEditorFields = true;

					}
					// app.disableTemplateSelect();

					CharitableUtils.triggerEvent( $builder, 'charitableCampaignFormScreen', [ s.templateID ]  );

					// Shepherd.activeTour.next();

				}

			} );

			// Create campaign via preview window. Sneaky!
			$builder.on( 'click', '.button-preview-create-campaign', function( e ) {
				e.preventDefault();
				// find the create button in the list and click that instead of trying to duplicate everything.
				var $theButton = $( this ),
					theTemplateID = $theButton.data('template-id');

				if ( theTemplateID !== '' ) {
					$('.charitable-template-list-container').removeClass('disabled');
					$('#charitable-builder-underlay').remove();
					// $('.charitable-templates-preview-container').addClass('charitable-hidden');
					$('.charitable-builder-modal.charitable-builder-modal-template-preview').removeClass('active');
					//  if the button.update-campaign exists, click that.
					if ( $('#charitable-template-list .charitable-template[data-template-code="' + theTemplateID + '"] .button.update-campaign').length > 0 ) {
						elements.$templatePreview.find('.charitable-template-list-container-item.charitable-template-' + theTemplateID + ' .button.update-campaign').click();
					} else {
						// if the button.create-campaign exists, click that.
						elements.$templatePreview.find('.charitable-template-list-container-item.charitable-template-' + theTemplateID + ' .button.create-campaign').click();
					}

				}

			} );

			$builder.on( 'click', '.button-preview-update-campaign', function( e ) {
				e.preventDefault();
				// find the create button in the list and click that instead of trying to duplicate everything.
				var $theButton = $( this ),
					theTemplateID = $theButton.data('template-id');

				if ( theTemplateID !== '' ) {
					$('.charitable-template-list-container').removeClass('disabled');
					$('#charitable-builder-underlay').remove();
					// $('.charitable-templates-preview-container').addClass('charitable-hidden');
					$('.charitable-builder-modal.charitable-builder-modal-template-preview').removeClass('active');
					//  if the button.update-campaign exists, click that.
					if ( $('#charitable-template-list .charitable-template[data-template-code="' + theTemplateID + '"] .button.update-campaign').length > 0 ) {
						elements.$templatePreview.find('.charitable-template-list-container-item.charitable-template-' + theTemplateID + ' .button.update-campaign').click();
					} else {
						// if the button.create-campaign exists, click that.
						elements.$templatePreview.find('.charitable-template-list-container-item.charitable-template-' + theTemplateID + ' .button.create-campaign').click();
					}

				}

			} );

			// Update campaign.
			elements.$templatePreview.on( 'click', '.button.update-campaign', function( e ) {
				e.preventDefault();

				var theButton = $( this );

				if ( app.hasTemplate() ) {

					// Display error in modal window.
					$.confirm( {
						title: charitable_builder.heads_up,
						content: '<p>' + charitable_builder.error_already_started_campaign + '</p>',
						icon: 'fa fa-exclamation-circle',
						type: 'orange',
						buttons: {
							confirm: {
								text: charitable_builder.ok,
								btnClass: 'btn-confirm',
								keys: [ 'enter' ],
								action: function() {

									s.templateID = theButton.closest('.charitable-template').data('template-code');
									s.templateLabel = theButton.closest('.charitable-template').data('template-label');
									app.updateTemplateID( s.templateID, s.templateLabel );

									s.primaryThemeColorBase = s.primaryThemeColor = theButton.closest('.charitable-template').data('template-primary');
									s.secondaryThemeColorBase = s.secondaryThemeColor = theButton.closest('.charitable-template').data('template-secondary');
									s.tertiaryThemeColorBase = s.tertiaryThemeColor = theButton.closest('.charitable-template').data('template-tertiary');
									s.buttonThemeColorBase = s.buttonThemeColor = theButton.closest('.charitable-template').data('template-button');

									elements.$primaryThemeColorBase.val( s.primaryThemeColorBase );
									elements.$secondaryThemeColorBase.val( s.secondaryThemeColorBase );
									elements.$tertiaryThemeColorBase.val( s.tertiaryThemeColorBase );
									elements.$buttonThemeColorBase.val( s.buttonThemeColorBase );

									$('input[name="layout__advanced__theme_color_primary"]').val( s.primaryThemeColor );
									$('input[name="layout__advanced__theme_color_secondary"]').val( s.secondaryThemeColor );
									$('input[name="layout__advanced__theme_color_tertiary"]').val( s.tertiaryThemeColor );
									$('input[name="layout__advanced__theme_color_button"]').val( s.buttonThemeColor );

									document.querySelector('input[name="layout__advanced__theme_color_primary"]').dispatchEvent(new Event('input', { bubbles: true }));
									document.querySelector('input[name="layout__advanced__theme_color_secondary"]').dispatchEvent(new Event('input', { bubbles: true }));
									document.querySelector('input[name="layout__advanced__theme_color_tertiary"]').dispatchEvent(new Event('input', { bubbles: true }));
									document.querySelector('input[name="layout__advanced__theme_color_button"]').dispatchEvent(new Event('input', { bubbles: true }));

									// update the template screen UI, first clearing any "current" labels, etc.
									$('#charitable-template-list .charitable-template').removeClass('active');
									$('#charitable-template-list .charitable-banner-container').each( function() {
										$( this ).addClass('charitable-hidden');
										$( this ).parent().find('.template-buttons').removeClass('charitable-hidden');
									});
									theButton.closest('.charitable-template').addClass('active');
									theButton.closest('.charitable-template').find('.template-buttons').addClass('charitable-hidden');
									theButton.closest('.charitable-template').find('.charitable-banner-container').removeClass('charitable-hidden');

									// Update the text.
									$('.charitable-setup-desc.secondary-text strong.template-name').html( s.templateLabel );

									wpchar.debug('chose this path');

									app.restartForm( s.templateID, 'design', s.templateLabel );
									app.restartTemplateScreen( s.templateID );

								}
							},
							cancel: {
								text: charitable_builder.cancel,
								keys: [ 'esc' ]
							}
						}
					} );

				}

			} );

			app.showUpgradeBanner();

		},

		/**
		 * Restart the template page to reflect any current template changes.
		 *
		 * @since 1.8.0
		 *
		 * @param {boolean} templateID Template slug.
		 *
		 */
		restartTemplateScreen: function( templateID = 0 ) {

			if ( templateID === '' || templateID === 0 ) {
				templateID = s.templateID;
			}

			if ( templateID === '' || templateID === 0) {
				return;
			}

			const templateList = $('.charitable-template-list-container');

			// first remove any 'preview' buttons in the thumbnails.
			templateList.find('.template-buttons').remove('.preview-campaign');
			// replace the "create campaign" buttons with the "update campaign" buttons.
			templateList.find('.button.create-campaign').removeClass('create-campaign').addClass('update-campaign').html( charitable_builder.update_campaign );
			// remove any active charitable-templates so we can reset things.
			templateList.find('.charitable-template').removeClass('active');
			templateList.find('.charitable-template .charitable-banner-container').addClass('charitable-hidden');
			// now find the charitable-template with a data attr "template code" that matches the templateID.
			templateList.find('.charitable-template[data-template-code="' + templateID + '"]').addClass('active');
			templateList.find('.charitable-template[data-template-code="' + templateID + '"] .charitable-banner-container').removeClass('charitable-hidden');


		},

		/**
		 * Restart the forms, restart key values.
		 *
		 * @since 1.8.0
		 *
		 * @param {boolean} templateID Template slug.
		 * @param {boolean} panel Panel slug.
		 * @param {boolean} templateLabel Text/label of template.
		 *
		 */
		restartForm: function( templateID = 0, panel = 'design', templateLabel = false ) {

			app.updateTemplateID( templateID, templateLabel );

			app.showLoadingOverlay();

			var data = {
				action : 'charitable_get_campaign_builder_template_data',
				id     : templateID,
				title  : s.campaignTitle,
				nonce  : charitable_builder.nonce
			};

			$.post( charitable_builder.ajax_url, data, function( response ) {

				if ( response.success ) {

					// Clear all hidden fields that represent placed field son preview form, if we are starting over ore resetting.
					$('#charitable-field-options .charitable-layout-options-group-inner').find('.charitable-panel-field').remove();
					$('#charitable-field-options .charitable-select-field-notice').show();
					s.quilled = [];
					elements.$nextFieldId.val( 0 );

					app.hideLoadingOverlay();

					var previewLayout          = typeof response.data.preview !== 'undefined' ? response.data.preview : false,
						previewFieldOptions    = typeof response.data.field_options !== 'undefined' ? response.data.field_options : false,
						previewTabOptions      = typeof response.data.tab_options !== 'undefined' ? response.data.tab_options : false,
						previewAdvancedOptions = typeof response.data.advanced !== 'undefined' ? response.data.advanced : false;

					if ( previewLayout ) {
						app.addLayoutToPreview( previewLayout );
					} else {
						wpchar.debug( 'previewLayout is false or not defined' );
					}

					if ( previewFieldOptions ) {
						app.addFieldOptions( previewFieldOptions );
					} else {
						wpchar.debug( 'previewFieldOptions is false or not defined' );
					}

					if ( previewTabOptions ) {
						app.addTabOptions( previewTabOptions );
					} else {
						wpchar.debug( 'previewTabOptions is false or not defined' );
					}

					if ( previewAdvancedOptions ) {
						app.addAdvancedOptions( previewAdvancedOptions );
					} else {
						wpchar.debug( 'previewAdvancedOptions is false or not defined' );
					}

					if ( Charitable.Admin.Builder.DragFields && typeof Charitable.Admin.Builder.DragFields.ready === 'function' ) {
						Charitable.Admin.Builder.DragFields.ready();
					}

					app.updateFormHiddenFields();
					app.updateFormHiddenFieldID();

					// if ( ! s.didInitHTMLEditorFields ) {

						$( '.campaign-builder-htmleditor' ).each( function() {
							app.initHTMLEditorFields( $( this ) );
						});
						$( '.campaign-builder-htmleditor-min' ).each( function() {
							app.initHTMLEditorFields( $( this ), true );
						});

						s.didInitHTMLEditorFields = true;

					// }

					// check sections to see if there are fields.
					elements.$preview.find('.charitable-field-section').each( function() {
						app.checkFieldTargetState( $( this ) );
					});

					// check recommended fields and check/uncheck depending on what was loaded.
					app.checkAllRecommendedFields();

					// Goal and End Date checks
					app.updateGoalRelatedItems();
					app.updateEndDateRelatedItems();

					// swiitching over to the design panel view.
					if ( panel ) {
						app.panelSwitch( 'design' );
					}

					// Handle fields that will likely appear AFTER a template select or dynamic fields added after a form reset.

						// This handles the unique suggested donation area that creates dynamic rows.
						$( '.charitable-campaign-suggested-donations-mini' ).each( function() {
							app.initSuggestedDonationsMini( $( this ) );
							app.updateSuggestdDonationsMiniRowsFromSettings( $( this ) );
						});

					// Check the field max values and set buttons in "add layout" accordingly.
					app.checkFieldAllow();
					app.checkFieldMax();


					// Was there a description in the template data?
					if ( typeof response.data.settings.general.description !== 'undefined' && response.data.settings.general.description != '' ) {
						s.campaignDescription = response.data.settings.general.description;
						elements.$settingsPanel.find( 'input[name="settings[general][description]"').val( s.campaignDescription );
					}

					// Finally, make sure to note that the campaign isn't saved.
					app.setCampaignNotSaved();

					$builder.trigger( 'charitableEditorScreenStart' );

				} else {

					app.formSaveError( response.data );
				}

			} ).fail( function( xhr, textStatus, e ) {  // eslint-disable-line

				app.formSaveError();

			} ).always( function() {

			} );

		},

		/**
		 * Check and see if it's ok to add/remove the "no fields preview" UI.
		 *
		 * @since 1.8.0
		 *
		 * @param {array} previewAdvancedOptions Advanced Options
		 *
		 */
		checkNoFieldsPreview( forceModeOff = false ) {

			// note: we ignore fields in special tab sections.
			if ( forceModeOff === false && elements.$preview.find('.charitable-field-section[data-section-type="fields"] .charitable-field').length === 0 && typeof( charitable_builder.no_field_preview ) !== 'undefined' ) {
				// Is there any fields in preview? If not add the no-fields-preview as it goes into the void.
				// $('.charitable-campaign-preview-loader').after( charitable_builder.no_field_preview );
				$('#charitable-design-wrap').addClass('no-fields-mode');
				if ( $builder.find( '.charitable-no-fields-area' ).hasClass('charitable-hidden') ) {
					$builder.find( '.charitable-no-fields-area' ).removeClass('charitable-hidden');
				}
			} else if ( forceModeOff !== false ) {
				if ( ! $builder.find( '.charitable-no-fields-area' ).hasClass('charitable-hidden') ) {
					$builder.find( '.charitable-no-fields-area' ).addClass('charitable-hidden');
				}
				$('#charitable-design-wrap').removeClass('no-fields-mode');
			} else {
				if ( ! $builder.find( '.charitable-no-fields-area' ).hasClass('charitable-hidden') ) {
					$builder.find( '.charitable-no-fields-area' ).addClass('charitable-hidden');
				}
				$('#charitable-design-wrap').removeClass('no-fields-mode');
			}

		},

		/**
		 *
		 *
		 * @since 1.8.0
		 *
		 * @param {array} previewAdvancedOptions Advanced Options
		 *
		 */
		checkFieldTargetState( $section = false ) {

			if ( false === $section ) {
				return;
			}

			if ( $section.data('section-type') !== 'fields' && $section.data('section-type') !== 'tabs' ) {
				return;
			}

			if ( $section.data('section-type') === 'fields' ) {

				if ( $section.find('.charitable-field').length === 0 ) {

					$section.removeClass('charitable-field-target-inactive').addClass('charitable-field-target');

				} else {

					$section.removeClass('charitable-field-target').addClass('charitable-field-target-inactive');

				}

			} else 	if ( $section.data('section-type') === 'tabs' ) {

				if ( $section.find('.tab_content_item.active .charitable-field').length === 0 ) {

					$section.find('.tab_content_item.active').removeClass('empty-tab').addClass('empty-tab');

				} else {

					$section.find('.tab_content_item.active').removeClass('empty-tab');
				}

			}

		},

		/**
		 * Setup values in the advanced tab area based on passed in parameters, likely coming from an ajax call when restarting the form.
		 *
		 * @since 1.8.0
		 *
		 * @param {array} previewAdvancedOptions Advanced Options
		 *
		 */
		addAdvancedOptions( previewAdvancedOptions ) {

			// update advanced options if they came with the theme
			var advancedFieldsInTab = $('.charitable-layout-options-tab-advanced').find('input, select');

			advancedFieldsInTab.each(function() {
				var $thisInput = $( this ),
					advancedFieldId = $thisInput.data('advanced-field-id');

					if ( typeof previewAdvancedOptions[advancedFieldId] !== 'undefined' && advancedFieldId != '' ) {

						if ( $thisInput.is('select') ) {
							$thisInput.val( previewAdvancedOptions[advancedFieldId] ).change();
						} else {
							$thisInput.val( previewAdvancedOptions[advancedFieldId] );
						}

					}

			});

		},

		/**
		 * Add HTML of field options to preview.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} previewFieldOptions Advanced Options
		 *
		 */
		addFieldOptions( previewFieldOptions ) {

			elements.$fieldOptions.find('.charitable-layout-options-group-inner .charitable-select-field-notice').after( previewFieldOptions );

		},

		/**
		 * Add HTML of tab options to preview.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} previewTabOptions Advanced Options
		 *
		 */
		addTabOptions( previewTabOptions ) {

			elements.$fieldOptions.find('.charitable-layout-options-tab.charitable-layout-options-tab-tabs .charitable-layout-options-group-inner').html( previewTabOptions );

		},

		/**
		 * Add HTML of the layout to preview area.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} previewLayout Advanced Options
		 *
		 */
		addLayoutToPreview( previewLayout ) {

			elements.$preview.find('.charitable-design-wrap').replaceWith( previewLayout );

		},

		/**
		 * Add align field listeners to field settings in the left column for design panel.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		alignFieldEvents: function( $builder ) {

			// Number Slider field: update hints.
			$builder.on( 'click', '.charitable-panel-field-align a', function( e ) {
				e.preventDefault();

				var clickedLink = $( this ),
					panelField  = clickedLink.closest( '.charitable-panel-field' ),
					alignValue  = '' !== clickedLink.data('align-value') ? clickedLink.data('align-value') : 'center';

				panelField.find('span').removeClass('active');
				panelField.find('input[type="hidden"').val( alignValue );
				clickedLink.parent().addClass('active');
				app.setCampaignNotSaved();

			} );

		},

		/**
		 * Add number slider events listeners to field settings in the left column for design panel.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		numberSliderEvents: function( $builder ) {

			elements.$fieldOptions.on( 'input change', 'input[type="range"]', function( e ) { // eslint-disable-line
				var minimum_value  = ( $( this ).attr('min-actual') ) ? parseInt( $( this ).attr('min-actual') ) : 0,
					scrolled_value = $( this ).val();
				if ( minimum_value > 0 ) {
					if ( scrolled_value <= minimum_value ) {
						$( this ).val( minimum_value );
						$( this ).next().addClass('min-reach');
					} else {
						$( this ).next().removeClass('min-reach');
					}
				}
				app.setCampaignNotSaved();
			} );

			// Number Slider field: update hints.
			$builder.on( 'change input', '.charitable-panel-field-number-slider input[type=range]', function( event ) {
				var hintEl = $( event.target ).siblings( '.charitable-number-slider-hint' );
				hintEl.attr( 'data-hint', event.target.value );
				hintEl.html( event.target.value + hintEl.data('symbol') + '<small>minimum</small>' );
			} );

			// Minimum update.
			$builder.on(
				'input',
				'.charitable-field-option-row-min_max .charitable-input-row .charitable-number-slider-min',
				app.fieldNumberSliderUpdateMin
			);

			// Maximum update.
			$builder.on(
				'input',
				'.charitable-field-option-row-min_max .charitable-input-row .charitable-number-slider-max',
				app.fieldNumberSliderUpdateMax
			);

			// Change default input value.
			$builder.on(
				'input',
				'.charitable-number-slider-default-value',
				_.debounce( app.changeNumberSliderDefaultValue, 500 ) // eslint-disable-line
			);

			// Change step value.
			$builder.on(
				'input',
				'.charitable-number-slider-step',
				_.debounce( app.changeNumberSliderStep, 500 ) // eslint-disable-line
			);

			// Check step value.
			$builder.on(
				'focusout',
				'.charitable-number-slider-step',
				app.checkNumberSliderStep
			);

			// Change value display.
			$builder.on(
				'input',
				'.charitable-number-slider-value-display',
				_.debounce( app.changeNumberSliderValueDisplay, 500 ) // eslint-disable-line
			);

			// Change min value.
			$builder.on(
				'input',
				'.charitable-number-slider-min',
				_.debounce( app.changeNumberSliderMin, 500 ) // eslint-disable-line
			);

			// Change max value.
			$builder.on(
				'input',
				'.charitable-number-slider-max',
				_.debounce( app.changeNumberSliderMax, 500 ) // eslint-disable-line
			);
		},

		/**
		 * Change number slider min option.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} event Input event.
		 */
		changeNumberSliderMin: function( event ) {

			var fieldID = $( event.target ).parents( '.charitable-field-option-row' ).data( 'fieldId' );
			var value   = parseFloat( event.target.value );

			if ( isNaN( value ) ) {
				return;
			}

			app.updateNumberSliderDefaultValueAttr( fieldID, event.target.value, 'min' );
		},

		/**
		 * Change number slider max option.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} event Input event.
		 */
		changeNumberSliderMax: function( event ) {

			var fieldID = $( event.target ).parents( '.charitable-field-option-row' ).data( 'fieldId' );
			var value   = parseFloat( event.target.value );

			if ( isNaN( value ) ) {
				return;
			}

			app.updateNumberSliderDefaultValueAttr( fieldID, event.target.value, 'max' )
				.updateNumberSliderStepValueMaxAttr( fieldID, event.target.value );
		},

		/**
		 * Change number slider value display option.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} event Input event.
		 */
		changeNumberSliderValueDisplay: function( event ) {

			var str = event.target.value;
			var fieldID = $( event.target ).parents( '.charitable-field-option-row' ).data( 'fieldId' );
			var defaultValue = document.getElementById( 'charitable-field-option-' + fieldID + '-default_value' );

			if ( defaultValue ) {
				app.updateNumberSliderHintStr( fieldID, str )
					.updateNumberSliderHint( fieldID, defaultValue.value );
			}
		},

		/**
		 * Change number slider step option.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} event Input event.
		 */
		changeNumberSliderStep: function( event ) {

			var value = parseFloat( event.target.value );

			if ( isNaN( value ) ) {
				return;
			}

			var max     = parseFloat( event.target.max ),
				min     = parseFloat( event.target.min ),
				fieldID = $( event.target ).parents( '.charitable-field-option-row' ).data( 'fieldId' );

			if ( value <= 0 ) {
				return;
			}

			if ( value > max ) {
				event.target.value = max;
				return;
			}

			if ( value < min ) {
				event.target.value = min;
				return;
			}

			app.updateNumberSliderAttr( fieldID, value, 'step' )
				.updateNumberSliderDefaultValueAttr( fieldID, value, 'step' );
		},

		/**
		 * Check number slider step option.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} event Focusout event object.
		 */
		checkNumberSliderStep: function( event ) {

			var value  = parseFloat( event.target.value ),
				$input = $( this );

			if ( ! isNaN( value ) && value > 0 ) {
				return;
			}

			$.confirm( {
				title   : charitable_builder.heads_up,
				content : charitable_builder.error_number_slider_increment,
				icon    : 'fa fa-exclamation-circle',
				type    : 'orange',
				buttons : {
					confirm: {
						text: charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action: function() {

							$input.val( '' ).trigger( 'focus' );
						}
					}
				}
			} );

		},

		/**
		 * Change number slider default value option.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} event Input event.
		 */
		changeNumberSliderDefaultValue: function( event ) {

			var value = parseFloat( event.target.value );

			if ( ! isNaN( value ) ) {
				var max     = parseFloat( event.target.max ),
					min     = parseFloat( event.target.min ),
					fieldID = $( event.target ).parents( '.charitable-field-option-row-default_value' ).data( 'fieldId' );

				if ( value > max ) {
					event.target.value = max;
					return;
				}

				if ( value < min ) {
					event.target.value = min;
					return;
				}

				app.updateNumberSlider( fieldID, value )
					.updateNumberSliderHint( fieldID, value );
			}

		},

		/**
		 * Update number slider default value attribute.
		 *
		 * @since 1.8.0
		 *
		 * @param {number} fieldID Field ID.
		 * @param {*} newValue Default value attribute.
		 * @param {*} attr Attribute name.
		 *
		 * @returns {object} App instance.
		 */
		updateNumberSliderDefaultValueAttr: function( fieldID, newValue, attr ) {

			var input = document.getElementById( 'charitable-field-option-' + fieldID + '-default_value' );

			if ( input ) {
				var value = parseFloat( input.value );

				input.setAttribute( attr, newValue );
				newValue = parseFloat( newValue );

				if ( 'max' === attr && value > newValue ) {
					input.value = newValue;
					$( input ).trigger( 'input' );
				}

				if ( 'min' === attr && value < newValue ) {
					input.value = newValue;
					$( input ).trigger( 'input' );
				}
			}

			return this;
		},

		/**
		 * Update number slider value.
		 *
		 * @since 1.8.0
		 *
		 * @param {number} fieldID Field ID.
		 * @param {string} value Number slider value.
		 *
		 * @returns {object} App instance.
		 */
		updateNumberSlider: function( fieldID, value ) {

			var numberSlider = document.getElementById( 'charitable-number-slider-' + fieldID );

			if ( numberSlider ) {
				numberSlider.value = value;
			}

			return this;
		},

		/**
		 * Update number slider attribute.
		 *
		 * @since 1.8.0
		 *
		 * @param {number} fieldID Field ID.
		 * @param {mixed} value Attribute value.
		 * @param {*} attr Attribute name.
		 *
		 * @returns {object} App instance.
		 */
		updateNumberSliderAttr: function( fieldID, value, attr ) {

			var numberSlider = document.getElementById( 'charitable-number-slider-' + fieldID );

			if ( numberSlider ) {
				numberSlider.setAttribute( attr, value );
			}

			return this;
		},

		/**
		 * Update number slider hint string.
		 *
		 * @since 1.8.0
		 *
		 * @param {number} fieldID Field ID.
		 * @param {string} str Hint string.
		 *
		 * @returns {object} App instance.
		 */
		updateNumberSliderHintStr: function( fieldID, str ) {

			var hint = document.getElementById( 'charitable-number-slider-hint-' + fieldID );

			if ( hint ) {
				hint.dataset.hint = str;
			}

			return this;
		},

		/**
		 * Update number slider Hint value.
		 *
		 * @since 1.8.0
		 *
		 * @param {number} fieldID Field ID.
		 * @param {string} value Hint value.
		 *
		 * @returns {object} App instance.
		 */
		updateNumberSliderHint: function( fieldID, value ) {

			var hint = document.getElementById( 'charitable-number-slider-hint-' + fieldID );

			if ( hint ) {
				hint.innerHTML = wpchar.sanitizeHTML( hint.dataset.hint ).replace( '{value}', '<b>' + value + '</b>' );
			}

			return this;
		},

		/**
		 * Update min attribute.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} event Input event.
		 */
		fieldNumberSliderUpdateMin: function( event ) {

			var $options = $( event.target ).parents( '.charitable-field-option-row-min_max' ),
				max      = parseFloat( $options.find( '.charitable-number-slider-max' ).val() ),
				current  = parseFloat( event.target.value );

			if ( isNaN( current ) ) {
				return;
			}

			if ( max <= current ) {
				event.preventDefault();
				this.value = max;

				return;
			}

			var fieldId = $options.data( 'field-id' ),
				numberSlider = $builder.find( '#charitable-field-' + fieldId + ' input[type="range"]' );

			numberSlider.attr( 'min', current );
		},

		/**
		 * Update max attribute.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} event Input event.
		 */
		fieldNumberSliderUpdateMax: function( event ) {
			var $options = $( event.target ).parents( '.charitable-field-option-row-min_max' ),
				min      = parseFloat( $options.find( '.charitable-number-slider-min' ).val() ),
				current  = parseFloat( event.target.value );

			if ( isNaN( current ) ) {
				return;
			}

			if ( min >= current ) {
				event.preventDefault();
				this.value = min;

				return;
			}

			var fieldId = $options.data( 'field-id' );
			var numberSlider = $builder.find( '#charitable-field-' + fieldId + ' input[type="range"]' );

			numberSlider.attr( 'max', current );
		},

		/**
		 * Update max attribute for step value.
		 *
		 * @since 1.8.0
		 *
		 * @param {number} fieldID Field ID.
		 * @param {*} newValue Default value attribute.
		 *
		 * @returns {object} App instance.
		 */
		updateNumberSliderStepValueMaxAttr: function( fieldID, newValue ) {

			var input = document.getElementById( 'charitable-field-option-' + fieldID + '-step' );

			if ( input ) {
				var value = parseFloat( input.value );

				input.setAttribute( 'max', newValue );
				newValue = parseFloat( newValue );

				if ( value > newValue ) {
					input.value = newValue;
					$( input ).trigger( 'input' );
				}
			}

			return this;
		},

		/**
		 * Builder was visited via back button in browser.
		 *
		 * @since 1.8.0
		 *
		 * @returns {boolean} True if the builder was visited via back button in browser.
		 */
		isVisitedViaBackButton: function() {

			if ( ! performance ) { // todo: check this.
				return false;
			}

			var isVisitedViaBackButton = false;

			performance.getEntriesByType( 'navigation' ).forEach( function( nav ) {
				if ( nav.type === 'back_forward' ) {
					isVisitedViaBackButton = true;
				}
			} );

			return isVisitedViaBackButton;
		},

		/**
		 * Remove loading overlay.
		 *
		 * @since 1.8.0
		 */
		hideLoadingOverlay: function() {

			var $overlay = $( '#charitable-builder-overlay' );

			$overlay.addClass( 'fade-out' );
			setTimeout( function() {

				$overlay.hide();

			}, 200 );
		},

		/**
		 * Attempts to load a code editor when the page loads, or at other times called.
		 *
		 * @since 1.8.4.3.
		 */
		initCodeEditor: function() {

			// if a textarea for a coded field is present, init the code editor.
			if ( $( '.campaign-builder-codeeditor' ).length > 0 ) {

				var field_id = '';

				// get the field id which should be the number after charitable-panel-field-settings-field_html_html_ in the id attr.
				// for each element that has the id 'charitable-panel-field-settings-field_html_html_' + field_id, init the editor.
				$( '.campaign-builder-codeeditor' ).each( function() {
					// does this element have 'charitable-panel-field-settings-field_html_html_' in the id?
					if ( $( this ).attr( 'id' ).indexOf( 'charitable-panel-field-settings-field_html_html_' ) !== -1 ) {
						field_id = $( this ).attr( 'id' ).replace( 'charitable-panel-field-settings-field_html_html_', '' );
						wpchar.debug( 'field_id: ' + field_id );
						$builder.trigger( 'charitableFieldAddHTML', [ field_id, 'html' ] );
					}
				});
			}

		},

		/**
		 * Show loading overlay.
		 *
		 * @since 1.8.0
		 */
		showLoadingOverlay: function() {

			var $overlay = $( '#charitable-builder-overlay' );

			$overlay.removeClass( 'fade-out' );
			$overlay.show();
		},

		/**
		 * Update some element of form UI.
		 * Element and value might be to update something speicfic, if not passed assume it's everything.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} element Template slug.
		 * @param {string} value Text/label of template.
		 *
		 */
		updateFormUI: function( element = false, value = false ) {

			var title = false;

			// Title.
			if ( element === 'title' ) {
				title = value;
			} else {
				title = $( 'input.charitable-form-name' ).val();
			}

			// Clean the string.
			title = title.replace(/[^\w\s\u00C0-\u024F\u1E00-\u1EFF\u2C60-\u2C7F\uA720-\uA7FF _.,!"()'\/$[\]:@#%-]/gi, '');

			// Should we allow a blank title even temp?
			// if ( '' === $.trim(title) ) {
				// title = s.campaignTitle;
			// }

			// Campaign Title.
			elements.$campaignNameTopBanner        = $( 'input.charitable-form-name' ); // the campaign title in the top banner
			elements.$campaignNamePreview          = $( '.charitable-preview .charitable-form-name' ); // in the "preview" window
			// elements.$campaignNameGeneralSettings  = $( '#charitable-panel-settings #charitable-panel-field-settings-campaign_title' ); // this is the name field in settings -> general
			elements.$campaignNameFieldTitleSettings = $('input.charitable-campaign-builder-title'); // this is all the campaign title block input text boxes (left panel)

			elements.$campaignNameTopBanner.val( title );
			elements.$campaignNamePreview.html( title );
			// elements.$campaignNameGeneralSettings.val( title );
			elements.$campaignNameFieldTitleSettings.val( title );

			elements.$preview.find('.charitable-field-campaign-title .charitable-campaign-builder-placeholder-preview-text').html( '<h1 class="charitable-campaign-title">' + ( title ) + '</h1>' );
			// elements.$preview.find('.charitable-field-campaign-title .charitable-campaign-builder-placeholder-preview-text').html( '<h5>' + ( title ) + '</h5>' );

			if ( element === 'title' ) {
				s.campaignTitle = title;
				app.resizeTopCampaignTitleInputBox();
			}

		},

		/**
		 * Element bindings.
		 *
		 * @since 1.8.0
		 */
		bindUIActions: function() {

			// Campaign Title.
			app.bindAutoResizeCampaignTitle();
			app.bindUIEditCampaignTitle();
			app.bindCampaignTitleBlockTextField();

			// General Panels.
			app.bindUIActionsPanels();

			// Fields Panel.
			app.bindUIActionsFields();

			// Preview.
			app.bindUIActionsPreview();

			// Save and Exit.
			app.bindUIActionsSaveExit();

			// General/ global.
			app.bindUIActionsGeneral();

			// Tab related.
			app.bindUITabs();

			// Listen to form and tag form not as saved if there is a change.
			$builder.on( 'change', 'input, textarea, select, checkbox, radio', function( e ) {  // eslint-disable-line
				app.setCampaignNotSaved();
			} );

			// Advanced Layout Options
			app.bindUILayoutOptionsAdvanced();

			// Setting "Reveals" (like Recurring Donations).
			app.bindUISettingsRevealGroups();

			// Auto-santitize numeric/money fields.
			app.bindUIMoneyTextFields();

			// Check Field Conditionals
			app.checkFieldConditionals();

			$builder.on( 'change', 'select#charitable-panel-field-settings-campaign_campaign_creator_id', function( e ) { // eslint-disable-line

				app.updateCampaignCreatorInfo();

			} );

			$builder.on( 'click', 'div#charitable-marketing-form a.button-link, div#charitable-payment-form a.button-link, div#charitable-feedback-form a.button-link', function( e ) { // eslint-disable-line

				e.preventDefault();
				app.initFeedbackForms( $(this) );

			} );

			// Handle send-feedback links in template panel description.
			$builder.on( 'click', 'a.send-feedback', function( e ) {
				e.preventDefault();

				// Scroll to the top of the charitable-panel-content-wrap div container.
				$('.charitable-panel-content-wrap').animate({
					scrollTop: 0
				}, 500);

				$('.charitable-template-list-container').addClass('disabled');
				$('.charitable-feedback-form-container').after('<div id="charitable-builder-underlay" class="charitable-builder-underlay"></div>');
				$('.charitable-feedback-form-container').css("opacity", "100" ).css("visibility", "visible" );

				$('.charitable-feedback-form-container').find('.charitable-feedback-form-interior').removeClass('charitable-hidden');
				$('.charitable-feedback-form-container').find('textarea').val('');
				$('.charitable-feedback-form-interior-confirmation').addClass('charitable-hidden');

			} );

			// Suggested Donation Amounts

			$builder.on( 'keyup', 'table.charitable-campaign-suggested-donations-mini input', function( e ) { // eslint-disable-line

				var field_id = parseInt( $(this).closest('.charitable-panel-field').data('field-id') );

				if ( field_id > 0 ) {

					var theIndex = $(this).index( '.charitable-panel-field[data-field-id="' + field_id + '"] table.charitable-campaign-suggested-donations-mini input[type="text"].campaign_suggested_donations' );

					app.updateSuggestDonationsSettings( $(this), theIndex );

				}

			} );

			$builder.on( 'change keyup blur input', 'table.charitable-campaign-suggested-donations input[type="text"].campaign_suggested_donations', function( e ) { // eslint-disable-line

				var theIndex   = $(this).index( '.charitable-campaign-suggested-donations input[type="text"].campaign_suggested_donations' ),
					field_name = $(this).attr('name');

				if ( field_name.indexOf('recurring') < 1 ) {
					app.updateSuggestDonationsSettings( $(this), theIndex );
				}


			} );

			$builder.on( 'change', '.charitable-campaign-builder-allow-custom-donations input[type="checkbox"]', function( e ) { // eslint-disable-line

				// make sure all checkboxes with the same "charitable-campaign-builder-allow-custom-donations" class are matching the same value.
				$('.charitable-campaign-builder-allow-custom-donations input[type="checkbox"]').prop('checked', $(this).is(':checked') );

				app.updateAllowCustomDonationSettings( $( this ).is(':checked') );

			} );

			$builder.on( 'change', 'input[type="radio"].campaign_suggested_donations', function( e ) { // eslint-disable-line

				var field_id = parseInt( $(this).closest('.charitable-panel-field').data('field-id') ), // eslint-disable-line
					field_name = $(this).attr('name'); // eslint-disable-line

				// do not proceed with updating other suggest amount boxes if the field_name contains the string 'recurring' (only do this for the default non-recurring).
				if ( field_name.indexOf('recurring') < 1 ) {
					app.updateSuggestedDonationAmountDefault( $( this ).val() );
				}

			} );


			// General/ global.

			app.bindUIInputRange();

			$builder.on( 'click', '.education-buttons button.update-to-pro-link', function( e ) { // eslint-disable-line

				// if there is a link right above this button element, have the window open to the url that is assigned to the href in that link.
				if ( $( this ).prev('a').length > 0 ) {
					window.open( $( this ).prev('a').attr('href'), '_blank' );
				} else {
					window.open("https://wpcharitable.com/lite-vs-pro/", "_blank");
				}

			} );


		},

		/**
		 * Element bindings for general panel tasks.
		 *
		 * @since 1.8.0
		 */
		bindUILayoutOptionsAdvanced: function() {

			// This is the area at the top of the builder where you can change the campaign title.
			$builder.on( 'change', 'select#charitable-design-layout-options-advanced-tab-style', function( e ) {
				e.preventDefault();

				var $this = $( this ),
					$preview_nav = $( 'nav.charitable-campaign-preview-nav' );


				// get all possible values so we can review any possible css that might exist?
				$( $this ).find('option').each(function() {
					$preview_nav.removeClass('tab-style-' + this.value );
				});

				$preview_nav.addClass( 'tab-style-' + $this.find(":selected").val() );

			} );

			$builder.on( 'change', 'select#charitable-design-layout-options-advanced-tab-size', function( e ) {
				e.preventDefault();

				var $this = $( this ),
					$preview_nav = $( 'nav.charitable-campaign-preview-nav' );

				// get all possible values so we can review any possible css that might exist?
				$( $this ).find('option').each(function() {
					$preview_nav.removeClass('tab-size-' + this.value );
				});

				$preview_nav.addClass( 'tab-size-' + $this.find(":selected").val() );

			} );

		},

		/**
		 * Add new group tab.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} el jQuery element.
		 * @param {object} e Event.
		 *
		 */
		tabGroupsAdd: function( el, e ) {

			e.preventDefault();

			// count the number of tabs currently created.
			var count = elements.$sortableTabContent.find('.tab_content_item').length;

			if ( count >= s.maxNumberOfTabs ) { // 4 for now is the tab limit
				app.formGenericNotice('You cannot have more than ' + s.maxNumberOfTabs + ' tabs in your campaign template.');
				return;
			}

			var $this                    = $( el ),
				$groupLast               = $this.parent().find( '.charitable-group.charitable-layout-options-tab-group.hidden' ).last(),
				$newGroup                = $groupLast.clone(),
				tab_content              = elements.$preview.find('.tab-content'),
				default_tab_content_type = 'html';

			// Get the cloned group_id and add +1.
			var dataList = $(".charitable-group.charitable-layout-options-tab-group").map(function() {
				return parseInt($(this).attr("data-group_id"));
			}).get();

			var groupID = Math.max.apply(null, dataList) + 1;

			if ( typeof groupID === 'undefined' ) {
				app.formGenericError('Error ecountered attempting to add another tab.');
				return;
			}

			$newGroup.attr( 'data-group_id', groupID );

			// update the data-tab-id.
			$newGroup.attr( 'data-tab-id', groupID );
			$newGroup.find('.charitable-group-row').attr( 'data-tab-id', groupID );

			// Replace the __xx__ in the names of the fields in the clone with the groupID, thereby making them unique.
			$newGroup.find('input, select, textarea, label').each( function(index) {  // eslint-disable-line
				var $this    = $( this ),
					forAttr  = $this.attr('for'),
					nameAttr = $this.attr('name'),
					idAttr   = $this.attr('id');

					if ( typeof idAttr !== 'undefined' && idAttr !== false ) {
						$this.attr( 'id', $this.attr('id').replace('_xxx_', '_' + groupID + '_') );
					}

					if ( typeof nameAttr !== 'undefined' && nameAttr !== false ) {
						$this.attr( 'name', $this.attr('name').replace('_xxx_', '_' + groupID + '_') );
					}

					// check if there is a "for" (label) and update that too.
					if ( typeof forAttr !== 'undefined' && forAttr !== false ) {
						$this.attr( 'for', $this.attr('for').replace('_xxx_', '_' + groupID + '_') );
					}

			});

			// Assign default values to items that have been cloned in the group.
			$newGroup.find( 'input[name="tabs__' + groupID + '__title"]' ).val('New Tab');
			$newGroup.find( 'textarea[name="tabs__' + groupID + '__desc"]' ).html('Content');

			// Whip the active class to reset the tab area on the left.
			$('.charitable-layout-options-tab-group').removeClass('active');
			$newGroup.addClass('active');

			// Make sure the clone is open (not closed) and not hidden.
			$newGroup.removeClass('charitable-closed').addClass('charitable-open').removeClass('hidden').find('.charitable-angle-right').removeClass('charitable-angle-right').addClass('charitable-angle-down');

			$this.before( $newGroup );

			app.bindUIActionPanelsTabs();
			app.checkHideTabNavigation();

			// Update now the preview area, making the latest new tab the active one (first clearing the actives).
			$('nav.charitable-campaign-preview-nav ul li').removeClass('active');
			tab_content.find('ul li').removeClass('active');

			$('nav.charitable-campaign-preview-nav ul').append('<li data-tab-type="html" data-tab-id="' + groupID + '" class="tab_title active" id="tab_' + groupID + '_title"><a href="#">' + charitable_builder.new_tab + '</a></li>');

			tab_content.find('ul').append('<li id="tab_' + groupID + '_content" class="tab_content_item empty-tab active tab_type_' + default_tab_content_type + '" data-tab-type="' + default_tab_content_type + '" data-tab-id="' + groupID + '"><div class="charitable-tab-wrap ui-sortable"><p class="empty-tab-notice">' + charitable_builder.empty_tab + '</p></div></li>');

			tab_content.removeClass('empty-tabs');
			tab_content.find('.no-tab-notice').remove();

			$builder.trigger( 'charitableAddNewTab', groupID );

		},

		//--------------------------------------------------------------------//
		// General Panels
		//--------------------------------------------------------------------//

		/**
		 * Element bindings for tabs.
		 *
		 * @since 1.8.0
		 */
		bindUITabs: function() {

			// Conditional add new group tab.
			$builder.on( 'click', 'button.charitable-tab-groups-add', function( e ) {
				app.tabGroupsAdd( this, e );
			} );

			// Moving the tabs around.
			$builder.find('.charitable-layout-options-tab-tabs .charitable-layout-options-group-inner').sortable({
				handle: '.charitable-draggable',
				update: function( event, ui ) {  // eslint-disable-line

					// get the list of tabs in the fields
					var dataList = $(".charitable-group.charitable-layout-options-tab-group").map(function() {
						if ( ! $(this).hasClass('hidden') ) {
							return parseInt($(this).attr("data-group_id"));
						}
					}).get();
					var activeList = $(".charitable-group.charitable-layout-options-tab-group").map(function() {
						if ( $(this).hasClass('active') ) {
							return parseInt($(this).attr("data-group_id"));
						}
					}).get();

					$('nav.charitable-campaign-preview-nav ul').empty();

					dataList.forEach( function( groupID ) {
						// do something with `item`
						var $tab           = $("#charitable-field-options").find('[data-group_id="' + groupID + '"]'),
							textFieldnName = groupID,
							isActive       = activeList.indexOf( groupID ) >= 0 ? 'active' : '',
							tabType        = ( groupID !== 0 && '' !== $tab.find('input[name="tabs__' + textFieldnName + '__type"').val() ) ? $tab.find('input[name="tabs__' + textFieldnName + '__type"').val() : 'html',
							tabTitle       = $tab.find('input[name="tabs__' + textFieldnName + '__title"').val();

						$('nav.charitable-campaign-preview-nav ul').append('<li data-tab-type="' + tabType + '" data-tab-id="' + groupID + '" class="tab_title ' + isActive + '" id="tab_' + groupID + '_title"><a href="#">' + tabTitle + '</a></li>');
					});

				}

			});

			$builder.on( 'click', 'input, select, textarea, .ql-editor', '.charitable-layout-options-tab-tabs .charitable-layout-options-group-inner .charitable-layout-options-tab-group', function( e ) { // eslint-disable-line

				if ( ! $( this ).closest('.charitable-layout-options-tab-group').hasClass( 'active' ) ) {
					var group_id = $( this ).closest('.charitable-layout-options-tab-group').data('group_id');
					$('.charitable-preview nav.charitable-campaign-preview-nav li[data-tab-id="' + group_id + '"] a').click();
				}

			} );

			// Tab Links that do things in the admin UI.
			$builder.on( 'click', 'a.charitable-configure-tab-settings', function( e ) { // eslint-disable-line
				// make sure to switch over to the "tabs" tab under layout options
				app.fieldTabToggle('layout-options');
				$('.charitable-layout-options-tab').removeClass('active');
				$('.charitable-layout-options-tab.charitable-layout-options-tab-tabs').addClass('active');
			} );


			// Clickable Tabs
			$builder.on( 'click', 'nav li.tab_title a', function( e ) { // eslint-disable-line

				e.preventDefault();

				var $preview = $( '.charitable-campaign-preview' ),
					tab_id = $( this ).parent().data('tab-id'),
					tab_type = $preview.find( 'li#tab_' + tab_id + '_title').attr( 'data-tab-type' ); // eslint-disable-line

				// clear the active states of the tabs and content areas in the preview area
				$('.tab-content ul li').removeClass('active');
				$('nav li.tab_title').removeClass('active');

				// make the clicked on tab and it's content area active
				$( this ).parent().addClass('active');
				$('.tab-content ul li#tab_' + tab_id + '_content').addClass('active');

				// make sure to switch over to the "tabs" tab under layout options
				$('.charitable-layout-options-tab').removeClass('active');
				$('.charitable-layout-options-tab.charitable-layout-options-tab-tabs').addClass('active');

				// Using cookies make sure the tabs are open or closed
				$( '.charitable-layout-options-tab' ).find('.charitable-layout-options-tab-group').each(function() {
					$( this ).removeClass('charitable-open');
					var cookieValue = wpCookies.get( 'charitable_panel_layout_options_tabs_tab_open_' + $( this ).data('group_id') );

					if ( cookieValue === 'true' ) {
						$( this ).addClass('charitable-open');
						$( this ).find('.charitable-general-layout-heading a.charitable-toggleable-group i').addClass('charitable-angle-down');
						$( this ).find('.charitable-general-layout-heading a.charitable-toggleable-group i').removeClass('charitable-angle-right');
						$( this ).removeClass('charitable-closed');
					} else {
						$( this ).removeClass('charitable-open');
						$( this ).find('.charitable-general-layout-heading a.charitable-toggleable-group i').removeClass('charitable-angle-down');
						$( this ).find('.charitable-general-layout-heading a.charitable-toggleable-group i').addClass('charitable-angle-right');
						$( this ).addClass('charitable-closed');
					}
				});

				// now clear any of the left option panel 'tabs' from being active... then add active to the proper one
				$('.charitable-layout-options-tab .charitable-group').removeClass('active');
				$(".charitable-layout-options-tab").find('[data-group_id=' + tab_id + ']').addClass('active').removeClass('charitable-closed');
				$(".charitable-layout-options-tab").find('[data-group_id=' + tab_id + ']').find('.charitable-group-rows').show();
				$(".charitable-layout-options-tab").find('[data-group_id=' + tab_id + ']').find('.charitable-toggleable-group i').removeClass('.charitable-angle-right').addClass('charitable-angle-down');
				$( '#layout-options a' ).click();

				$("#charitable-preview-tab-container").addClass('active');

				wpCookies.set( 'charitable_panel_tab_section_tab_id', tab_id, 2592000 ); // 1 month.
				wpCookies.set( 'charitable_panel_layout_options_tabs_tab_open_' + tab_id, true, 2592000 ); // 1 month.

				wpCookies.set( 'charitable_panel_content_section', '', 2592000 );
				wpCookies.set( 'charitable_panel_active_field_id', '', 2592000 );

			} );

			// Change the tab type when the select dropdown is changed.
			$builder.on( 'change', '.charitable-group select.tab_type', function( e ) {

				e.preventDefault();

				var $this          = $( this ),
					group_id       = $this.closest('.charitable-group').data('group_id'),
					$preview       = $( '.charitable-campaign-preview' ),
					selected_value = $this.val();

				// Update the tabs in the preview area with this new type.
				$preview.find('nav li.tab_title[data-tab-id=' + group_id + ']').attr( 'data-tab-type', selected_value );
				$preview.find('div.tab-content ul li#tab_' + group_id + '_content').attr( 'data-tab-type', selected_value );
				$preview.find('div.tab-content ul li#tab_' + group_id + '_content').removeClass (function (index, className) {
					return (className.match (/(^|\s)tab_type_\S+/g) || []).join(' ');
				});

				$preview.find('div.tab-content ul li#tab_' + group_id + '_content').addClass( 'tab_type_' + selected_value );

				var data = {
					action   : 'charitable_tab_content_preview',
					type     : selected_value,
					group_id : group_id,
					nonce    : charitable_builder.nonce
				};

				return $.post( charitable_builder.ajax_url, data, function( response ) {

					if ( response.success ) {

						$preview.find('div.tab-content ul li#tab_' + group_id + '_content').html( response.data.output );

					} else {
						app.formSaveError( response.data );
					}

				} ).fail( function( xhr, textStatus, e ) { // eslint-disable-line

					app.formSaveError();

				} ).always( function() {


				} );

			} );

			$builder.on( 'input', 'input#charitable-panel-field-settings-charitable-campaign-enable-tabs', function( e ) { // eslint-disable-line

				const isChecked   = $( this ).is(':checked');

				if ( isChecked ) {
					// fade an disable the tab area on the preview.
					$("#charitable-preview-tab-container").addClass('disabled');
					// also fade and disable the style/size tab attributes in the advanced tab.
					$("#charitable-design-layout-options-advanced-tab-style").prop('disabled', true).addClass('disabled');
					$("#charitable-design-layout-options-advanced-tab-size").prop('disabled', true).addClass('disabled');
				} else {
					// remove the fade on preview tabs.
					$("#charitable-preview-tab-container").removeClass('disabled');
					// unfade and enable the style/size tab attributes in the advanced tab.
					$("#charitable-design-layout-options-advanced-tab-style").prop('disabled', false).removeClass('disabled');
					$("#charitable-design-layout-options-advanced-tab-size").prop('disabled', false).removeClass('disabled');
				}

			} );

			$builder.on( 'input', 'input.charitable-settings-tab-visible-nav', function( e ) { // eslint-disable-line

				const 	isChecked   = $( this ).is(':checked'),
						tabID       = $( this ).closest('.charitable-tab-title-row').data('tab-id');

				if ( isChecked ) {
					elements.$preview.find('nav li#tab_' + tabID + '_title').addClass('charitable-tab-hide');
				} else {
					elements.$preview.find('nav li#tab_' + tabID + '_title').removeClass('charitable-tab-hide');
				}

			} );

			$builder.on( 'keydown', '.charitable-tab-title-row input[type="text"]', function( e ) { // eslint-disable-line
				var k = e.keyCode || e.which,
					ok = k >= 65 && k <= 90 || // A-Z
					( k >= 96 ) && ( k <= 105 ) || // a-z
					( k >= 35 ) && ( k <= 40 ) || // arrows
					( k == 32 ) || // space
					( e.ctrlKey && k == 65 ) || // Ctrl + A
					( e.ctrlKey && k == 67 ) || // Ctrl + C
					( e.ctrlKey && k == 88 ) || // Ctrl + X
					( e.ctrlKey && k == 86 ) || // Ctrl + V
					( e.metaKey && k == 65 ) || // command + a
					( e.metaKey && k == 67 ) || // command + c
					( e.metaKey && k == 88 ) || // command + x
					( e.metaKey && k == 86 ) || // command + v
					( k >= 96 && k <= 105 ) || // allow numbers entered from a number pad
					( k == 110 || k == 190 ) || // period or decimal point
					( k >= 37 && k <= 40 ) || // allow arrow keys
					( k == 46 ) || // allow delete key
					( k == 173 ) || // dash
					( k == 8 ) || // backspaces
					( k >= 48 && k <= 57 ); // only 0-9 (ok SHIFT options)

				if ( !ok || ( e.ctrlKey && e.altKey ) ) {
					e.preventDefault();
				}

			} );

			$builder.on( 'change', '.charitable-tab-title-row input[type="text"]', function( e ) { // eslint-disable-line
				// If the text input box is empty, then add a default value.
				if ( $( this ).val().trim() === '' ) {
					$( this ).val( 'New Tab' );
				}
			} );


		},

		/**
		 * Element bindings for input type range UI elements in settings, usually in left panel for 'design'.
		 *
		 * @since 1.8.0
		 */
		bindUIInputRange: function() {

			/* charitable indicator */

			$builder.on( 'mouseenter', 'input[type="range"].charitable-indicator-on-hover, .charitable-panel-field-align a', function( e ) { // eslint-disable-line

				var field_id = parseInt( $(this).closest('.charitable-panel-field').data('field-id') );

				if ( field_id > 0 ) {

					elements.$preview.find('#charitable-field-' + field_id + ' .charitable-preview-field-indicator').removeClass('charitable-hidden');

				}

			} );

			$builder.on( 'mouseleave', 'input[type="range"].charitable-indicator-on-hover, .charitable-panel-field-align a', function( e ) { // eslint-disable-line

				var field_id = parseInt( $(this).closest('.charitable-panel-field').data('field-id') );

				if ( field_id > 0 ) {

					elements.$preview.find('#charitable-field-' + field_id + ' .charitable-preview-field-indicator').addClass('charitable-hidden');

				}

			} );

		},

		/**
		 * Element bindings for campign title fields.
		 *
		 * @since 1.8.0
		 */
		bindAutoResizeCampaignTitle: function() {

			// Resize the width of the HTML input text field based on the number of characters that are being typed.
			$builder.on( 'input', 'input#charitable_settings_title', function( e ) { // eslint-disable-line

				var $this = $( this ),
					the_text = $this.val(),
					the_text_characters = the_text.length,
					the_padding = 10,
					the_input_box_width = ( the_text_characters * 12.8 ) + the_padding;

				wpchar.debug( the_text );
				wpchar.debug( the_text_characters );
				wpchar.debug( $('#charitable_settings_title').val() );

				// $this.css( 'width', the_input_box_width + 'px' );

				app.setCampaignNotSaved(); // if the title is changed, that's a trigger.

				app.updateFormUI( 'title', the_text );

				app.resizeTopCampaignTitleInputBox();

			});

			// If the field loses focus, then update the title status.
			$builder.on( 'focusout', 'input#charitable_settings_title', function( e ) { // eslint-disable-line

				// app.setCampaignTitleSet();

			});

		},

		/**
		 * Element bindings for campign title fields.
		 *
		 * @since 1.8.0
		 */
		resizeTopCampaignTitleInputBox: function() {

			// var $this = $( 'input#charitable_settings_title' ),
			// 	the_text = $this.val(),
			// 	the_text_characters = the_text.length,
			// 	the_padding = 10,
			// 	the_input_box_width = ( the_text_characters * 12.8 ) + the_padding;

			// 	$this.css( 'width', the_input_box_width + 'px' );

		},

		/**
		 * Update the title when it gets changed in the campaign title input text field.
		 *
		 * @since 1.8.0
		 */
		bindCampaignTitleBlockTextField: function () {

			$builder.on( 'keyup', 'input.charitable-campaign-builder-title', function( e ) { // eslint-disable-line
				app.updateFormUI( 'title', $( this ).val() );
			} );

		},

		/**
		 * Element bindings for campign title fields.
		 *
		 * @since 1.8.0
		 */
		bindUIEditCampaignTitle: function() {

			// This is the area at the top of the builder where you can change the campaign title.
			// $builder.on( 'click', '.charitable-edit-campaign-title-area', function( e ) {
			// 	e.preventDefault();
			// 	app.allowTitleUpdate( $( this ) );
			// } );

			// $builder.on( 'focusout', 'input#charitable_settings_title', function( e ) { // eslint-disable-line

			// 	$('.charitable-edit-campaign-title-area').removeClass( 'edit' );
			// 	if ( $('#charitable_settings_title' ).val().trim() === '' ) {
			// 		$( '#charitable_settings_title' ).val( charitable_builder.default_campaign_title ); // leave a default title, this should not be blank.
			// 	}
			// 	$('#charitable_settings_title').attr('disabled', true);

			// 	app.updateFormUI( 'title', $('#charitable_settings_title').val() );

			// 	var $this = $( 'input#charitable_settings_title' ),
			// 		the_text = $this.val(),
			// 		the_text_characters = the_text.length,
			// 		the_padding = 10,
			// 		the_input_box_width = ( the_text_characters * 11 ) + the_padding;

			// 	$this.css( 'width', the_input_box_width + 'px' );

			// } );

			// $(document).on( 'mouseup', $builder, function( e ) {
			// 	if ($(e.target).closest(".charitable-edit-campaign-title-area").length === 0) {
			// 		$('.charitable-edit-campaign-title-area').removeClass( 'edit' );
			// 		if ( $('#charitable_settings_title' ).val().trim() === '' ) {
			// 			$( '#charitable_settings_title' ).val( charitable_builder.default_campaign_title ); // leave a default title, this should not be blank.
			// 		}
			// 		$('#charitable_settings_title').attr('disabled', true);
			// 	}
			// 	app.updateFormUI( 'title', $('#charitable_settings_title').val() );
			// } );

		},

		allowTitleUpdate: function( $container ) {

			if ( $container.hasClass( 'edit' ) ) {
				$('#charitable_settings_title').attr('disabled', true);
				$container.removeClass( 'edit' );
			} else {
				$('#charitable_settings_title').removeAttr('disabled');
				$container.addClass( 'edit' );
				$('#charitable_settings_title').focus().select();
			}

		},

		/**
		 * Element bindings for general panel tasks.
		 *
		 * @since 1.8.0
		 */
		bindUIActionsPanels: function() {

			// Panel switching.
			$builder.on( 'click', '#charitable-panels-toggle button:not(.charitable-panel-help-button), .charitable-panel-switch', function( e ) {
				e.preventDefault();

				// if the button has a disabled class, then we can't switch the panel because it's disabled.
				if ( ! $( this ).hasClass('disabled') ) {
					app.panelSwitch( $( this ).data( 'panel' ) );
				}
			} );

			// Help button - allow the link to work by ensuring the anchor's href is followed.
			$builder.on( 'click', '.charitable-panel-help-button, #charitable-panels-toggle a:has(.charitable-panel-help-button)', function( e ) {
				var $anchor = $( this ).closest( 'a' );
				if ( ! $anchor.length ) {
					$anchor = $( this ).is( 'a' ) ? $( this ) : $( this ).closest( 'a' );
				}
				if ( $anchor.length && $anchor.attr( 'href' ) ) {
					// Stop event propagation to prevent other handlers from interfering
					e.stopPropagation();
					e.stopImmediatePropagation();
					// Navigate to the URL - let the anchor handle it naturally
					var href = $anchor.attr( 'href' );
					var target = $anchor.attr( 'target' ) || '_blank';
					if ( target === '_blank' ) {
						window.open( href, target );
					} else {
						window.location.href = href;
					}
					return false;
				}
			} );

			// Panel sections switching.
			$builder.on( 'click', '.charitable-panel .charitable-panel-sidebar-section:not(.charitable-need-upgrade):not(.charitable-not-available):not(.charitable-not-installed):not(.charitable-not-activated):not(.charitable-installed-refresh):not(.charitable-not-available):not(.charitable-addon-file-missing)', function( e ) {
				app.panelSectionSwitch( this, e );
			} );

			// Panel sidebar toggle.
			$builder.on( 'click', '.charitable-panels .charitable-panel-sidebar-content .charitable-panel-sidebar-toggle', function() {
				$( this ).parent().toggleClass( 'charitable-panel-sidebar-closed' );
			} );


			$builder.on( 'focusout', 'input[name="tabs__campaign__title"]', function( e ) {
				e.preventDefault();
				app.updateFormUI( 'tabs__campaign__title', $( this ).val() );
			} );

			app.bindUIActionPanelsTabs();

		},

		/**
		 * Element bindings for general panel tasks.
		 *
		 * @since 1.8.0
		 */
		bindUIActionPanelsTabs: function() {

			$builder.on( 'input', '.charitable-tab-title-row input[type="text"]', function( e ) {

				e.preventDefault();

				var $textBox         = $( this ),
					$the_element     = $textBox.closest('.charitable-group'),
					theGroupID       = $the_element.attr("data-group_id"),
					updatedText      = ( 0 === $textBox.val().length ) ? 'New Tab' : $textBox.val(),
					updatedTextClean = app.removeTags( updatedText )

				// Change the title of the current tab that the text field is in.
				$the_element.find('.charitable-general-layout-heading').find('span').html( updatedTextClean );

				// Update in preview area.
				$('nav.charitable-campaign-preview-nav ul').find('#tab_' + theGroupID + '_title a').html( updatedTextClean );

			} );

		},

		/**
		 * This specifically checks to see if we have to force on and disable thie "hide tab navigation" functionality... if there is more than on tab, the user can't hide nav for any tab.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} theString The string.
		 *
		 */
		checkHideTabNavigation: function () {

			const tab_count = $('#charitable-field-options .charitable-layout-options-tab-group').length - 1;

			if ( tab_count > 1 ) {
				// disable and force on.
				$('#charitable-field-options').find('input.charitable-settings-tab-visible-nav').each(function () {
					$( this ).prop( 'checked', false ).attr( 'disabled', true );
					$( this ).parent().find('label').addClass( 'charitable-disabled' );
					elements.$preview.find('nav.charitable-campaign-preview-nav li').removeClass( 'charitable-tab-hide' );
				});
			} else {
				$('#charitable-field-options').find('input.charitable-settings-tab-visible-nav').each(function () {
					$( this ).removeAttr( 'disabled' );
					$( this ).parent().find('label').removeClass( 'charitable-disabled' );
				});
			}

		},

		/**
		 * Remove tags from a string.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} theString The string.
		 *
		 */
		removeTags: function ( theString ) {

			return theString;

			// var rex = /(<([^>]+)>)/ig;
			// return( theString.replace( rex , "" ) );
		},


		/**
		 * Switch Panels.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} panel Panel slug.
		 *
		 * @returns {mixed} Void or false.
		 */
		panelSwitch: function( panel ) {

			var $panel     = $( '#charitable-panel-' + panel ),
				$panelBtn  = $( '.charitable-panel-' + panel + '-button' ),
				cookieName = 'charitable_panel';

			/* check and see if a template has been selected */
			if ( ! app.hasTemplate() && panel !== 'template' ) {
				return;
			}

			if ( ! $panel.hasClass( 'active' ) ) {

				const event = CharitableUtils.triggerEvent( $builder, 'charitablePanelSwitch', [ panel ]  );

				// Allow callbacks on `charitablePanelSwitch` to cancel panel switching by triggering `event.preventDefault()`.
				if ( event.isDefaultPrevented() || ! charitable_panel_switch ) {
					return false;
				}

				$( '#charitable-panels-toggle' ).find( 'button' ).removeClass( 'active' );
				$( '.charitable-panel' ).removeClass( 'active' );
				$panelBtn.addClass( 'active' );
				$panel.addClass( 'active' );

				history.replaceState( {}, null, wpchar.updateQueryString( 'view', panel ) );

				$builder.trigger( 'charitablePanelSwitched', [ panel ] );

				wpCookies.set( cookieName, panel, 2592000 ); // 1 month.
				wpCookies.set( 'charitable_panel_content_section', '', 2592000 );
				wpCookies.set( 'charitable_panel_active_field_id', '', 2592000 );
			}
		},

		/**
		 * Switch Panel section.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} el DOM element object.
		 * @param {object} e  Event object.
		 *
		 */
		panelSectionSwitch: function( el, e ) {

			if ( e ) {
				e.preventDefault();
			}

			var $this           = $( el ),
				$panel          = $this.parent().parent(),
				section         = $this.data( 'section' ),
				$sectionButtons = $panel.find( '.charitable-panel-sidebar-section' ),
				$sectionButton  = $panel.find( '.charitable-panel-sidebar-section-' + section );

			if ( $this.hasClass( 'upgrade-modal' ) || $this.hasClass( 'education-modal' )  ) {
				return;
			}

			if ( ! $sectionButton.hasClass( 'active' ) ) {
				app.panelSectionSwitchTo( section, $panel, $sectionButtons, $sectionButton );
			}
		},

		/**
		 * Switch Panel section to something specific.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} section The section slug.
		 * @param {object} $panel Panel object.
		 * @param {object} $sectionButtons Buttons object.
		 * @param {object} $sectionButton Button object.
		 *
		 */
		panelSectionSwitchTo: function ( section, $panel, $sectionButtons, $sectionButton ) {

			const event = CharitableUtils.triggerEvent( $builder, 'charitablePanelSectionSwitch', section  );

			// Allow callbacks on `charitablePanelSectionSwitch` to cancel panel section switching by triggering `event.preventDefault()`.
			if ( event.isDefaultPrevented() || ! charitable_panel_switch ) {
				return false;
			}

			$sectionButtons.removeClass( 'active' );
			$sectionButton.addClass( 'active' );
			$panel.find( '.charitable-panel-content-section' ).hide();
			$panel.find( '.charitable-panel-content-section-' + section ).show();

			var cookieName = 'charitable_panel_content_section';
			wpCookies.set( cookieName, section, 2592000 ); // 1 month

		},

		//--------------------------------------------------------------------//
		// Fields Panel
		//--------------------------------------------------------------------//

		/**
		 * Element bindings for Fields panel.
		 *
		 * @since 1.8.0
		 */
		bindUIActionsFields: function() {

			// Field sidebar tab toggle.
			$builder.on( 'click', '.charitable-tab a', function( e ) {
				e.preventDefault();
				app.fieldTabToggle( $( this ).parent().attr( 'id' ) );
				if ( 'add-layout' === $( this ).parent().attr( 'id' ) ) {
					app.resetPreviewArea();
				}
			} );

			$builder.on( 'click', '.charitable-layout-options-tab-tabs .charitable-toggleable-group', function( e ) {
				e.preventDefault();
				app.fieldGroupTogglev2( $( this ), 'click' );
			} );

			$builder.on( 'click', '.charitable-add-fields .charitable-toggleable-group', function( e ) {
				e.preventDefault();
				app.fieldGroupTogglev3( $( this ), 'click' );
			} );

			// Tab delete.
			$builder.on( 'click', '.charitable-tab-group-delete', function( e ) {

				e.preventDefault();
				e.stopPropagation();

				if ( app.isFormPreviewActionsDisabled( this ) ) {
					return;
				}

				var $the_element = $( this ).closest('.charitable-group'),
					theGroupID   = $the_element.attr("data-group_id");

				app.tabDelete( theGroupID );
			} );

			// Form field preview clicking.
			$builder.on( 'click', '.charitable-field', function( e ) {

				// Allow clicking on the dismiss button inside the field.
				if ( e.target.classList.contains( 'charitable-dismiss-button' ) ) {
					return;
				}

				e.stopPropagation();

				$( this ).find('.charitable-field-edit').click();

			} );

			// Field delete.
			$builder.on( 'click', '.charitable-field-delete', function( e ) {

				e.preventDefault();
				e.stopPropagation();

				var confirmation = $( this ).parent().hasClass('charitable-missing-addon-content' ) ? false : true,
					field_id     = parseInt( $( this ).closest('.charitable-field').data( 'field-id' ) );

				app.fieldDelete( field_id, confirmation );

			} );

			// Field duplicate.
			$builder.on( 'click', '.charitable-field-duplicate', function( e ) {

				e.preventDefault();
				e.stopPropagation();

				app.fieldDuplicate( $( this ).parent().data( 'field-id' ) );
			} );

			// Field edit
			$builder.on( 'click', '.charitable-field-edit', function( e ) {

				e.preventDefault();
				e.stopPropagation();

				app.resetPreviewArea();

				$( this ).parent().addClass('active');
				$('ul.charitable-tabs li#add-layout a').removeClass('active');
				$('ul.charitable-tabs li#layout-options a').addClass('active');

				var field_id = parseInt( $( this ).parent().data( 'field-id' ) );

				wpCookies.set( 'charitable_panel_active_field_id', field_id, 2592000 ); // 1 month.

				app.fieldEdit( $( this ).data( 'type' ), $( this ).data( 'section' ), $( this ).parent().data( 'field-id' ), field_id, $( this ).parent().data( 'field-type' ) );
			} );

			// Field add.
			$builder.on( 'click', '.charitable-add-fields-button', function( e ) {

				e.preventDefault();

				const $field = $( this );

				if ( $field.hasClass( 'ui-draggable-disabled' ) ) {
					return;
				}

				let type = $field.data( 'field-type' ),
					event = CharitableUtils.triggerEvent( $builder, 'charitableBeforeFieldAddOnClick', [ type, $field ] );

				// Allow callbacks on `charitableBeforeFieldAddOnClick` to cancel adding field
				// by triggering `event.preventDefault()`.
				if ( event.isDefaultPrevented() ) {
					return;
				}

				app.fieldAdd( type, { $sortable: 'default' } );
			} );


			// New field choices should be sortable
			$builder.on( 'charitableFieldAdd', function( event, id, type ) {

				const fieldTypes = [
					'campaign-title',
					'campaign-description',
					'campaign-overview',
					'progress-bar',
					'donation-options',
					'social-sharing',
					'social-links',
					'photo',
					'organizer',
					'html',
					'donate-button',
					'donate-amount',
					'campaign-summary',
					'donate-form',
					'donor-wall',
					'shortcode',
					'text',
					'video'
				];

				if ( $.inArray( type, fieldTypes ) !== -1 ) {
					app.fieldChoiceSortable( type, `#charitable-field-option-row-${id}-choices ul` );
				} else {
					// not found in the fieldTypes.
				}
			} );

			// Field Options group tabs.
			$builder.on( 'click', '.charitable-group-toggle', function( e ) { // was charitable-field-option-group-toggle

				const event = CharitableUtils.triggerEvent( $builder, 'charitableFieldOptionGroupToggle' );

				// Allow callbacks on `charitableFieldOptionGroupToggle` to cancel tab toggle by triggering `event.preventDefault()`.
				if ( event.isDefaultPrevented() ) {
					return false;
				}

				e.preventDefault();

				app.resetPreviewArea();

				var $group = $( this ).closest( '.charitable-layout-options-tab' );

				$group.siblings( '.charitable-layout-options-tab' ).removeClass( 'active' );
				$group.addClass( 'active' );

				if ( $( this ).parent().hasClass('charitable-layout-options-tab-tabs') ) {
					$("#charitable-preview-tab-container").toggleClass('active');
				} else {
					$("#charitable-preview-tab-container").removeClass('active');
				}

				if ( $( this ).parent().hasClass('charitable-layout-options-tab-general') ) {
					wpCookies.set( 'charitable_panel_design_layout_options_group', 'general', 2592000 ); // 1 month.
				} else if ( $( this ).parent().hasClass('charitable-layout-options-tab-tabs') ) {
					wpCookies.set( 'charitable_panel_design_layout_options_group', 'tabs', 2592000 ); // 1 month.
				} else if ( $( this ).parent().hasClass('charitable-layout-options-tab-advanced') ) {
					wpCookies.set( 'charitable_panel_design_layout_options_group', 'advanced', 2592000 ); // 1 month.
				}

				// reset cookies for anything else
				wpCookies.set( 'charitable_panel_active_field_id', '', 2592000 ); // 1 month.

				$('.charitable-layout-options-tab-general .charitable-layout-options-group-inner').html();
				$('.charitable-campaign-preview .charitable-select-field').removeClass('active');

			} );

			// Preview
			$builder.on( 'click', '.charitable-field-edit', function( e ) {

				e.preventDefault();

				$( '#layout-options a' ).addClass( 'active' );
				$( '.charitable-add-fields' ).hide();
				$( '.charitable-field-options' ).show();

				var $group = $( '.charitable-layout-options-tab-general' );

				$group.siblings( '.charitable-layout-options-tab' ).removeClass( 'active' );
				$group.addClass( 'active' );

			} );

			// On any click check if we had focusout event.
			$builder.on( 'click', function() {
				app.focusOutEvent();
			} );

			// Generalized field/block types.

				/* photo */
				app.photoFieldEvents( $builder );

				/* campaign summary */
				app.campaignSummaryEvents( $builder );

				/* social sharing */
				app.socialSharingEvents( $builder );

				/* social linking */
				app.socialLinkingEvents( $builder );

				/* donate button */
				app.donateButtonEvents( $builder );

				/* donate wall */
				app.donateWallEvents( $builder );

				/* progress bar */
				app.progressBarEvents( $builder );

				/* shortcodes */
				app.shortcodeEvents( $builder );

				/* organizer */
				app.organizerEvents( $builder );

			// Generalized form fields (in settings/left pane) types.

				/* text fields */
				app.cssTextFieldEvents( $builder );
				app.textFieldEvents( $builder );

				/* headlines */
				app.headlineEvents( $builder );

				/* number sliders */
				app.numberSliderEvents( $builder );

				/* align */
				app.alignFieldEvents( $builder );

				/ *highlights */
				app.highlightEvents( $builder );

			// Layout advanced.
			app.advancedLayoutOptionsEvents( $builder );

			// Preview related.
			app.previewHover( $builder );

			// Goal related
			app.goalEvents( $builder );

			// End date related
			app.endDateEvents( $builder );

		},

		/**
		 * Logic: checks if there is a campaign goal and depending if there is or isn't, hides and disables items in the preview field.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		endDateEvents: function( $builder ) {

			$builder.on( 'change', '#charitable-panel-field-settings-campaign_end_date', function( e ) {  // eslint-disable-line

				app.updateEndDateRelatedItems( $( this ).val() );

			} );

		},

		/**
		 * Logic: checks what the end date for the campaign is (if any) and adjusts the preview area accordingly.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		updateEndDateRelatedItems: function( endDate = '', theField = $('#charitable-panel-field-settings-campaign_end_date') ) {

			if ( endDate === '' ) {
				// if no end Date has been passed in, get the value from theField if theField exists.
				if ( theField.length ) {
					endDate = theField.val();
				}
			}

			if ( endDate.trim() === '' ) {

				wpchar.debug('updateEndDateRelatedItems - no end date');

				// if there is no value, then there is no end date... for each campaign summary field, check and see if it has a charitable-hidden class... and if it doesn't, add it.
				elements.$preview.find('.charitable-field-campaign-summary').each(function () {
					var field_id = $( this ).attr('data-field-id');

					$(this).find('.campaign-time-left.campaign-summary-item').addClass('charitable-hidden');
					// ... and also disable the checkbox field to hide the amount raised.
					wpchar.debug(  $('input[name="_fields[' + field_id + '][show_hide][campaign_hide_time_remaining]' ) );
					// uncheck checkbox
					elements.$fieldOptions.find( 'input[name="_fields[' + field_id + '][show_hide][campaign_hide_time_remaining]' ).attr( 'disabled', true ).addClass('charitable-disabled').prop( 'checked', false ).next().addClass('charitable-disabled');

				});

			} else {

				wpchar.debug('updateEndDateRelatedItems - there is an end date');

				elements.$preview.find('.charitable-field-campaign-summary').each(function () {
					var field_id = $( this ).attr('data-field-id');

					// $(this).find('.campaign-time-left.campaign-summary-item').removeClass('charitable-hidden');

					//$(this).find('.campaign-time-left.campaign-summary-item').removeClass('charitable-hidden');
					// ... and also enable the checkbox field to hide the amount raised.
					elements.$fieldOptions.find( 'input[name="_fields[' + field_id + '][show_hide][campaign_hide_time_remaining]' ).attr( 'disabled', false ).removeClass('charitable-disabled').next().removeClass('charitable-disabled');

				});

			}

		},

		/**
		 * Logic: checks if there is a campaign goal and depending if there is or isn't, hides and disables items in the preview field.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		goalEvents: function( $builder ) {

			$builder.on( 'change', '#charitable-panel-field-settings-campaign_goal', function( e ) {  // eslint-disable-line

				app.updateGoalRelatedItems( $( this ).val() );

			} );

		},

		/**
		 * Logic: checks if there is a campaign goal and depending if there is or isn't, hides and disables items in the preview field.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		updateGoalRelatedItems: function( goalAmount = '', theField = $('#charitable-panel-field-settings-campaign_goal') ) {

			// If the user adds or removes a goal in settings, then we should adjust the progress bar field in preview live.
			wpchar.debug ( goalAmount, 'goalAmount');

			if ( goalAmount === '' ) {
				// if no Goal Amount has been passed in, get the value from theField if theField exists.
				if ( theField.length ) {
					goalAmount = theField.val();
				}
			}

			if ( goalAmount === '' || goalAmount === '0' || goalAmount === '0.00' ) {

				wpchar.debug('updateGoalRelatedItems - checkpoint 0');

				// if there is no value, then there is no goal and therefore no progress bar.
				elements.$preview.find('.charitable-field-progress-bar .progress').addClass('charitable-campaign-preview-not-available').addClass('charitable-hidden');

				// for each campaign summary field, check and see if it has a charitable-hidden class... and if it doesn't, add it.
				elements.$preview.find('.charitable-field-campaign-summary').each(function () {
					var field_id = $( this ).attr('data-field-id');

					$(this).find('.campaign-raised.campaign-summary-item').addClass('charitable-hidden');
					// ... and also disable the checkbox field to hide the amount raised.
					wpchar.debug( elements.$fieldOptions.find( 'input[name="_fields[' + field_id + '][show_hide][campaign_hide_percent_raised]' ) );
					elements.$fieldOptions.find( 'input[name="_fields[' + field_id + '][show_hide][campaign_hide_percent_raised]' ).prop( 'checked', false ).attr( 'disabled', true ).addClass('charitable-disabled').next().addClass('charitable-disabled');

				});

				// update the goal field.
				elements.$preview.find('.charitable-field-progress-bar .campaign-goal').each(function () {
					var goalLabel = $( this ).find('span').html();
					$( this ).html( '<span>' + goalLabel + '</span>' + '∞' );
				});

			} else {

				// does the goalAmount have a decimal point?
				var regex_decemial       = /^\d+\.\d+$/,
					regex_comma          = /^\d+\,\d+$/; // eslint-disable-line

				if ( regex_comma.test( goalAmount ) ) {

					wpchar.debug('updateGoalRelatedItems - checkpoint 1');

					app.updateGoalisPresentRelatedUI();

					elements.$preview.find('.charitable-field-progress-bar .campaign-goal').each(function () {
						var goalLabel = $( this ).find('span').html(),
							santitizedValue = parseFloat( goalAmount ).toFixed(2); // eslint-disable-line

						wpchar.debug( charitable_builder.currency_symbol + goalAmount );

						$( this ).html( '<span>' + goalLabel + '</span>' + ' ' + charitable_builder.currency_symbol + goalAmount );

					});

				} else if ( regex_decemial.test( goalAmount ) ) {

					wpchar.debug('updateGoalRelatedItems - checkpoint 2');

					app.updateGoalisPresentRelatedUI();

					elements.$preview.find('.charitable-field-progress-bar .campaign-goal').each(function () {
						var goalLabel = $( this ).find('span').html(),
							santitizedValue = parseFloat( goalAmount ).toFixed(2);

						// Add commas to the interger part of the number.
						santitizedValue = goalAmount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");

						$( this ).html( '<span>' + goalLabel + '</span>' + ' ' + charitable_builder.currency_symbol + santitizedValue );

					});

				} else {

					wpchar.debug('updateGoalRelatedItems - checkpoint 3');

					app.updateGoalisPresentRelatedUI();

					// Remove all characters except commas, decimeals, and numbers.
					goalAmount = goalAmount.replace(/[^0-9\.\,]/g, ''); // eslint-disable-line

					elements.$preview.find('.charitable-field-progress-bar .campaign-goal').each(function () {
						var goalLabel = $( this ).find('span').html();

						$( this ).html( '<span>' + goalLabel + '</span>' + ' ' + charitable_builder.currency_symbol + goalAmount );

					});

				}

			}

		},

		/**
		 * Updates UI related to the campaign goal in settings being present.
		 *
		 * @since 1.8.0
		 */
		updateGoalisPresentRelatedUI: function () {

			elements.$preview.find('.charitable-field-campaign-summary').each(function () {
				var field_id = $( this ).attr('data-field-id'),
					$preview_summary = $( this );

				// if the checkbox to show this unchecked don't show it even though a goal has been added.
				if ( $( 'input[name="_fields[' + field_id + '][show_hide][campaign_hide_percent_raised]' ).is(':checked') ) {
					if ( ! $( this ).hasClass('charitable-hidden') ) {
						$preview_summary.find('.campaign-raised.campaign-summary-item').removeClass('charitable-hidden');
					}
				}

				// ... and also enable the checkbox field to hide the amount raised.
				elements.$fieldOptions.find( 'input[name="_fields[' + field_id + '][show_hide][campaign_hide_percent_raised]' ).attr( 'disabled', false ).removeClass('charitable-disabled').next().removeClass('charitable-disabled');

			});

			elements.$preview.find('.charitable-field-progress-bar .progress').removeClass('charitable-campaign-preview-not-available').removeClass('charitable-hidden');


		},

		/**
		 * Add handlers for "highlighting" certain areas of settings (like the recurring donations area).
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		highlightEvents: function( $builder ) {

			// Custom settings button redirects (recurring).
			$builder.on( 'click', 'a.charitable-addon-installed-charitable-lite, a.charitable-addon-installed-charitable-pro', function( e ) {  // eslint-disable-line
				e.preventDefault();

				if ( $( this ).hasClass('charitable-addon-recurring-donations') ) {
					elements.$settingsPanel.find('.charitable-panel-fields-group-recurring-donations').removeClass('charitable-highlight');
					$('a.charitable-panel-sidebar-section-donation-options').click();
					// highlight the area when the user goes to the section.
					elements.$settingsPanel.find('.charitable-panel-fields-group-recurring-donations').addClass('charitable-highlight');
				}

			} );

			$builder.on( 'webkitAnimationEnd oanimationend msAnimationEnd animationend', '.charitable-panel-fields-group-recurring-donations', function( e ) { // eslint-disable-line
				elements.$settingsPanel.find('.charitable-panel-fields-group-recurring-donations').removeClass('charitable-highlight');
			} );

		},

		/**
		 * Add handlers for changing advanced options settings.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		advancedLayoutOptionsEvents: function( $builder ) {

			$builder.on( 'change', 'select#charitable-design-layout-options-show-field-names', function( e ) {  // eslint-disable-line

				const theField = $( this );

				if ( theField.val() === 'hide' ) {
					elements.$formPreview.addClass('charitable-preview-hide-field-names');
				} else if ( theField.val() === 'show' ) {
					elements.$formPreview.removeClass('charitable-preview-hide-field-names');
				}

			} );


			$builder.on( 'change', 'select#charitable-design-layout-options-preview-mode', function( e ) {  // eslint-disable-line

				const theField = $( this );

				if ( theField.val() === 'normal' ) {
					elements.$formPreview.removeClass('charitable-preview-minimum-preview');
				} else if ( theField.val() === 'minimum' ) {
					elements.$formPreview.addClass('charitable-preview-minimum-preview');
				}

			} );

		},

		/**
		 * Add handlers for hovering over preview (live effect).
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		previewHover: function( $builder ) {

			$builder.on( 'mouseover', '.charitable-view-campaign-external-link', function( e ) {  // eslint-disable-line

				elements.$formPreview.addClass('charitable-preview-live');

			} );

			$builder.on( 'mouseleave', '.charitable-view-campaign-external-link', function( e ) {  // eslint-disable-line

				elements.$formPreview.removeClass('charitable-preview-live');

			} );

		},

		/**
		 * Add handlers for preview: updating the donate button text.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		donateButtonEvents: function( $builder ) {

			$builder.on( 'input', '.charitable-panel-field-text input[type="text"].charitable-campaign-builder-donate-button-button-label', function( e ) { // eslint-disable-line

				const 	theTextBox = $( this ),
						field_id   = theTextBox.closest('.charitable-panel-field').data('field-id');

				app.updateDonateButtonPreview( field_id, theTextBox.attr('name'), theTextBox.val() );

			} );

		},

		/**
		 * Updating the donate button text.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer} field_id Field ID.
		 * @param {string}  textFieldName Not currently used.
		 * @param {string}  $label_value Value of text to update.
		 */
		updateDonateButtonPreview: function( field_id = 0, textFieldName = '', label_value = 'Donate' ) {  // eslint-disable-line

			const preview_field = $('#charitable-field-' + field_id );

			label_value = label_value.length > 0 ? CharitableUtils.santitizeTextInput( label_value ) : 'Donate';

			preview_field.find('.placeholder').html( label_value );

		},

		/**
		 * Add handlers for preview: updating the donate wall text.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		donateWallEvents: function( $builder ) {

			$builder.on( 'input', '.charitable-panel-field.charitable-campaign-builder-donor-wall input, .charitable-panel-field.charitable-campaign-builder-donor-wall select', function( e ) {  // eslint-disable-line

				const 	theFormField = $( this ),
						field_id    = theFormField.closest('.charitable-panel-field').data('field-id');

				app.updateDonateWallPreview( field_id, theFormField );

			} );

		},

		/**
		 * Updating the donate wall text.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer} field_id Field ID.
		 * @param {string}  theFormField Not currently used.
		 */
		updateDonateWallPreview: function( field_id = 0, theFormField ) { // eslint-disable-line

			var data = {};

			$('.charitable-panel-field.charitable-campaign-builder-donor-wall[data-field-id="' + field_id + '"]').each(function () {
				var theArea = $( this );

				theArea.find('input.charitable-checkbox-for-toggle[type="checkbox"]:checked').each(function () {
					var theName = $(this).closest('.charitable-toggle-control[data-field-id="' + field_id + '"]').data('ajax-label');
					if ( $( this ).is(':checked') ) {
						data[theName] = $( this ).val();
					} else {
						data[theName] = 0;
					}
				});

				theArea.find('input[type="radio"]:checked,input[type="text"],input[type="number"],select').each(function () {
					var theName = $(this).closest('.charitable-panel-field[data-field-id="' + field_id + '"]').data('ajax-label');
					data[theName] = $( this ).val();
				});

				data.campaign_id = s.formID;
				data.field_type  = 'donation-wall';
				data.field_id    = field_id;
				data.action      = 'charitable_builder_field_content_preview',
				data.nonce       = charitable_builder.nonce

			} );

			// Disable stuff - move this into it's own function.
			app.disableFormActions();

			$('.charitable-layout-options-tab-general .charitable-panel-field[data-field-id="' + field_id + '"]').addClass('charitable-loading');
			$('#charitable-field-' + field_id + ' .charitable-preview-field-container span.placeholder').addClass('charitable-loading').parent().prepend('<div class="charitable-loading-spinner preview-ajax"></div>');

			return $.post( charitable_builder.ajax_url, data, function( res ) { // eslint-disable-line complexity

				if ( ! res.success ) {
					wpchar.debug( 'Add field AJAX call is unsuccessful:', res );
					return;
				}

				$('#charitable-field-' + field_id + ' .charitable-preview-field-container').replaceWith( res.data.output );

				var theTextBox = $( '.charitable-panel-field-text[data-field-id="' + field_id + '"] input[type="text"].charitable-campaign-builder-headline' );

				app.updateHeadlinePreview( field_id, theTextBox.attr('name'), theTextBox.val(), theTextBox );

				app.enableFormActions();

				$('.charitable-layout-options-tab-general .charitable-panel-field[data-field-id="' + field_id + '"]').removeClass('charitable-loading');
				$('#charitable-field-' + field_id + ' .charitable-preview-field-container span.placeholder').removeClass('charitable-loading').parent().remove('.charitable-loading');

			} ).fail( function( xhr, textStatus, e ) { // eslint-disable-line

				wpchar.debug( 'Add field AJAX call failed:', xhr.responseText );

				$('.charitable-layout-options-tab-general .charitable-panel-field[data-field-id="' + field_id + '"]').removeClass('charitable-loading');
				$('#charitable-field-' + field_id + ' .charitable-preview-field-container span.placeholder').removeClass('charitable-loading').parent().remove('.charitable-loading');

			} ).always( function() {

			} );

		},

		/**
		 * Add handlers for preview: progress bars.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		progressBarEvents: function( $builder ) {

			$builder.on( 'change', '.charitable-panel-field.charitable-campaign-builder-progress-bar input, .charitable-panel-field.charitable-campaign-builder-progress-bar select', function( e ) { // eslint-disable-line

				var theFormField = $( this ),
					field_id     = theFormField.attr('data-field-id');

				app.progressBarEventsPreview( field_id, theFormField );

			} );

			$builder.on( 'keyup', '.charitable-panel-field.charitable-panel-field-text input.donate_label, .charitable-panel-field.charitable-panel-field-text input.donate_goal', function( e ) { // eslint-disable-line

				var theFormField = $( this ),
					field_id     = theFormField.attr('data-field-id');

				app.progressBarEventsPreviewLabels( field_id, theFormField );

			} );

		},

		/**
		 * Updating the labels for the progress bar field.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer} field_id Field ID.
		 * @param {string}  theFormField Not currently used.
		 */
		progressBarEventsPreviewLabels: function( field_id = 0, theFormField = '' ) {

			const preview_field = $('#charitable-field-' + field_id );

			if ( theFormField.hasClass('donate_label') ) {
				preview_field.find('.campaign-percent-raised span' ).html( theFormField.val() );
			}
			if ( theFormField.hasClass('donate_goal') ) {
				preview_field.find('.campaign-goal span' ).html( theFormField.val() );
			}

		},

		/**
		 * Updating the fields for the progress bar field.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer} field_id Field ID.
		 * @param {string}  theFormField Not currently used.
		 */
		progressBarEventsPreview: function( field_id = 0, theFormField = '' ) {

			const preview_field = $('#charitable-field-' + field_id );

			if ( theFormField.val() === 'show_donated' ) {
				preview_field.find('.campaign-percent-raised' ).toggleClass('charitable-hidden');
			}
			if ( theFormField.val() === 'show_goal' ) {
				preview_field.find('.campaign-goal' ).toggleClass('charitable-hidden');
			}

		},

		/**
		 * Add handlers for preview: CSS text boxes.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		cssTextFieldEvents: function( $builder ) {

			$builder.on( 'input', '.charitable-panel-field-text input[data-ajax-label="css_class"]', function( e ) { // eslint-disable-line

				const 	theTextBox    = $( this ),
						textboxString = CharitableUtils.santitizeCSSInput( theTextBox.val() ); // Clean the string.

				$( this ).val( textboxString );

				app.setCampaignNotSaved();

			} );

		},

		/**
		 * Add handlers for preview: generic text boxes.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		textFieldEvents: function( $builder ) {

			$builder.on( 'input', '.charitable-panel-field-text input[type="text"][data-ajax-label!="css_class"]:not(.charitable-campaign-builder-headline, .charitable-campaign-builder-donate-button-button-label)', function( e ) { // eslint-disable-line

				const 	theTextBox    = $( this ),
						textboxString = CharitableUtils.santitizeTextInput( theTextBox.val() ); // Clean the string.

				$( this ).val( textboxString );

				app.setCampaignNotSaved();

			} );

		},

		/**
		 * Add handlers for preview: headlines.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		headlineEvents: function( $builder ) {

			$builder.on( 'input', '.charitable-panel-field-text input[type="text"].charitable-campaign-builder-headline', function( e ) { // eslint-disable-line

				const 	theTextBox = $( this ),
						field_id    = theTextBox.closest('.charitable-panel-field').data('field-id');

				app.updateHeadlinePreview( field_id, theTextBox.attr('name'), theTextBox.val(), theTextBox );

				app.setCampaignNotSaved();

			} );

		},

		/**
		 * Updating the fields for headlines.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer} field_id Field ID.
		 * @param {string}  textFieldName Not currently used.
		 * @param {string}  $label_value Value of text to update.
		 */
		updateHeadlinePreview: function( field_id = 0, textFieldName = '', label_value = '', theTextBox ) { // eslint-disable-line

			const 	preview_field            = $('#charitable-field-' + field_id ),
					headline                 = CharitableUtils.santitizeTitle( label_value  ), // Clean the string.
					headline_html            = label_value.length > 0 ? '<h5 class="charitable-field-preview-headline">' + headline + '</h5>': '',
					tempPlaceholderContainer = preview_field.find('.charitable-placeholder').length > 0 ? '.charitable-placeholder' : '.placeholder',
					placeholderContainer     = preview_field.find('.charitable-field-preview-social-sharing-headline-container').length > 0 ? '.charitable-field-preview-social-sharing-headline-container' : tempPlaceholderContainer;

			theTextBox.val( headline );
			preview_field.find( placeholderContainer ).find( 'h5.charitable-field-preview-headline' ).remove();
			preview_field.find( placeholderContainer ).first().prepend( headline_html );

		},

		/**
		 * Add handlers for preview: shortcodes.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		shortcodeEvents: function( $builder ) {

			$builder.on( 'input', '.charitable-panel-field-text input[type="text"].charitable-campaign-builder-shortcode', function( e ) { // eslint-disable-line

				const 	theTextBox = $( this ),
						field_id    = theTextBox.closest('.charitable-panel-field').data('field-id');

				app.updateShortcodePreview( field_id, theTextBox.attr('name'), theTextBox.val() );

			} );

		},

		/**
		 * Updating the fields for shortcodes.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer} field_id Field ID.
		 * @param {string}  textFieldName Not currently used.
		 * @param {string}  $label_value Value of text to update.
		 */
		updateShortcodePreview: function( field_id = 0, textFieldName = '', label_value = '' ) { // eslint-disable-line

			const 	preview_field = $('#charitable-field-' + field_id ),
					headline_html = label_value.length > 0 ? '<h5 class="charitable-field-preview-shortcode">' + label_value + '</h5>': '';

			preview_field.find('.placeholder.shortcode-preview').find( 'h5.charitable-field-preview-shortcode' ).remove();
			preview_field.find('.placeholder.shortcode-preview').first().prepend( headline_html );

		},

		/**
		 * Add handlers for preview: organizer.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		organizerEvents: function( $builder ) {

			$builder.on( 'change', '.charitable-panel-field.campaign-builder-campaign-creator-id-mini select', function( e ) { // eslint-disable-line

				var theFormField = $( this ),
					theAvatarURL = $( this ).find('option:selected').data('avatar'),
					field_id     = theFormField.closest('.charitable-panel-field').attr('data-field-id');

				app.organizerEventsPreview( field_id, theFormField, theAvatarURL );

			} );

		},

		/**
		 * Updating the fields for social sharing.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer} field_id Field ID.
		 * @param {object}  $theFormField Value of text to update.
		 */
		organizerEventsPreview: function( field_id = 0, theFormField = '', theAvatarURL = '' ) {

			// update the creator dropdown in settings
			$('select#charitable-panel-field-settings-campaign_campaign_creator_id').select2( "val", theFormField.val() );

			// update the name in the preview area.
			const preview_field = $('#charitable-field-' + field_id );
			preview_field.find('.charitable-organizer-name').html( theFormField.find("option:selected").text() );
			preview_field.find('.charitable-organizer-image').attr( 'style', 'background-image: url(' + theAvatarURL + ');' );

		},

		/**
		 * Add handlers for preview: campaign summary
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		campaignSummaryEvents: function( $builder ) {

			$builder.on( 'click', '.charitable-campaign-summary-checkboxes input[type="checkbox"]', function( e ) { // eslint-disable-line

				const 	theCheckbox = $( this ),
						field_id    = theCheckbox.closest('.charitable-panel-field').data('field-id');

				app.updateCampaignSummaryPreview( field_id, theCheckbox.attr('name') );

			} );

		},

		/**
		 * Updating the fields for social sharing.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer} field_id Field ID.
		 * @param {string}  $checkboxName
		 */
		updateCampaignSummaryPreview: function( field_id = 0, checkboxName = '' ) {

			const preview_field = $('#charitable-field-' + field_id );

			if ( checkboxName.indexOf( 'campaign_hide_percent_raised' ) >= 0 ) {
				preview_field.find('.campaign_hide_percent_raised' ).toggleClass('charitable-hidden');
			}
			if ( checkboxName.indexOf( 'campaign_hide_amount_donated' ) >= 0 ) {
				preview_field.find('.campaign_hide_amount_donated' ).toggleClass('charitable-hidden');
			}
			if ( checkboxName.indexOf( 'campaign_hide_number_of_donors' ) >= 0 ) {
				preview_field.find('.campaign_hide_number_of_donors' ).toggleClass('charitable-hidden');
			}
			if ( checkboxName.indexOf( 'campaign_hide_time_remaining' ) >= 0 ) {
				preview_field.find('.campaign_hide_time_remaining' ).toggleClass('charitable-hidden');
			}

		},

		/**
		 * Add handlers for preview: social sharing icons.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		socialSharingEvents: function( $builder ) {

			$builder.on( 'click', '.charitable-social-network-checkboxes input[type="checkbox"]', function( e ) { // eslint-disable-line

				const 	theCheckbox = $( this ),
						field_id    = theCheckbox.closest('.charitable-panel-field').data('field-id');

				app.updateSocialSharingPreview( field_id, theCheckbox.attr('name') );

			} );

		},

		/**
		 * Updating the fields for social sharing.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer} field_id Field ID.
		 * @param {string}  textFieldName Not currently used.
		 * @param {string}  $label_value Value of text to update.
		 */
		updateSocialSharingPreview: function( field_id = 0, checkboxName = '' ) {

			const preview_field = $('#charitable-field-' + field_id );

			if ( checkboxName.indexOf( 'twitter' ) >= 0 ) {
				preview_field.find('.charitable-social-sharing-preview-twitter' ).toggleClass('charitable-hidden');
			}
			if ( checkboxName.indexOf( 'facebook' ) >= 0 ) {
				preview_field.find('.charitable-social-sharing-preview-facebook' ).toggleClass('charitable-hidden');
			}
			if ( checkboxName.indexOf( 'linkedin' ) >= 0 ) {
				preview_field.find('.charitable-social-sharing-preview-linkedin' ).toggleClass('charitable-hidden');
			}
			if ( checkboxName.indexOf( 'instagram' ) >= 0 ) {
				preview_field.find('.charitable-social-sharing-preview-instagram' ).toggleClass('charitable-hidden');
			}
			if ( checkboxName.indexOf( 'tiktok' ) >= 0 ) {
				preview_field.find('.charitable-social-sharing-preview-tiktok' ).toggleClass('charitable-hidden');
			}
			if ( checkboxName.indexOf( 'pinterest' ) >= 0 ) {
				preview_field.find('.charitable-social-sharing-preview-pinterest' ).toggleClass('charitable-hidden');
			}
			if ( checkboxName.indexOf( 'mastodon' ) >= 0 ) {
				preview_field.find('.charitable-social-sharing-preview-mastodon' ).toggleClass('charitable-hidden');
			}
			if ( checkboxName.indexOf( 'threads' ) >= 0 ) {
				preview_field.find('.charitable-social-sharing-preview-threads' ).toggleClass('charitable-hidden');
			}
			if ( checkboxName.indexOf( 'bluesky' ) >= 0 ) {
				preview_field.find('.charitable-social-sharing-preview-bluesky' ).toggleClass('charitable-hidden');
			}

		},

		/**
		 * Add handlers for preview: social linking.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		socialLinkingEvents: function( $builder ) {

			$builder.on( 'input', '.charitable-panel-field-text input[type="url"].charitable-campaign-builder-social-links-text-field', function( e ) { // eslint-disable-line

				var theTextField = $( this ),
					field_id     = theTextField.attr('data-field-id');

				app.updateSocialLinksPreview( field_id, theTextField.attr('name'), theTextField.val() );

			} );

		},

		/**
		 * Updating the fields for social links.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer} field_id Field ID.
		 * @param {string}  textFieldName Not currently used.
		 * @param {string}  $label_value Value of text to update.
		 */
		updateSocialLinksPreview: function( field_id = 0, textFieldName = '', linkURL = '' ) {

			const 	preview_field = $('#charitable-field-' + field_id ),
					social_networks = [ 'twitter', 'facebook', 'linkedin', 'instagram', 'tiktok', 'pinterest', 'mastodon', 'youtube', 'threads', 'bluesky' ];

			var visibleNetworks = 0;

			$.each(
				social_networks
			,
				function( _index, network ) {

					if ( textFieldName.indexOf( network ) >= 0 && app.isValidURL( linkURL ) ) {
						preview_field.find('.charitable-social-linking-preview-' + network ).removeClass('charitable-hidden');
					} else if ( textFieldName.indexOf( network ) >= 0 ) {
						preview_field.find('.charitable-social-linking-preview-' + network ).addClass('charitable-hidden');
					}

					if ( preview_field.find('.charitable-social-linking-preview-' + network ).hasClass('charitable-hidden') ) {
						// Do Nothing.
					} else {
						visibleNetworks++;
					}
				}
			);

			if ( visibleNetworks > 0 ) {
				preview_field.find('.charitable-social-linking-no-links').addClass('charitable-hidden');
			} else {
				preview_field.find('.charitable-social-linking-no-links').removeClass('charitable-hidden');
			}

		},

		/**
		 * Add handlers for preview: photo field.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $builder JQuery object.
		 */
		photoFieldEvents: function( $builder ) {

			// WP 3.5+ uploader
			var file_frame;
			window.formfield = '';

			$builder.on( 'click', '.charitable-campaign-builder-upload-button', function( e ) {
				e.preventDefault();

				var 	button   = $( this ),
						field_id = $( this ).closest('.charitable-panel-field').data('field-id');

				window.formfield = $( this ).parent().prev();
				window.field_id = field_id;

				// If the media frame already exists, reopen it.
				if ( file_frame ) {
					//file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
					file_frame.open();
					return;
				}

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media( { // eslint-disable-line
					title    : button.data( 'uploader_title' ),
					library  : { type: 'image' },
					button   : { text: button.data( 'uploader_button_text' ) },
					multiple : false
				} );

				file_frame.on( 'menu:render:default', function( view ) {
					// Store our views in an object.
					const views = {};

					// Unset default menu items
					view.unset( 'library-separator' );
					view.unset( 'gallery' );
					view.unset( 'featured-image' );
					view.unset( 'embed' );

					// Initialize the views in our view object.
					view.set( views );
				} );

				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
					const selection = file_frame.state().get( 'selection' );
					selection.each( function( attachment, index ) { // eslint-disable-line
						attachment = attachment.toJSON();
						window.formfield.val( attachment.url );
						app.updateImagePhotoPreview( window.field_id, attachment.url );
						window.field_id = false;
					} );
				} );

				// Finally, open the modal
				file_frame.open();

			} );

			$builder.on( 'click', '.charitable-campaign-builder-clear-button', function( e ) {
				e.preventDefault();

				const 	button   = $( this ),
						field_id = $( this ).closest('.charitable-panel-field').data('field-id');

				button.closest('.charitable-internal').find('input[type="url"]').val('');
				app.updateImagePhotoPreview( field_id, false );

			} );

		},

		/**
		 * Updating the photo of the photo field in the preview area.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer} field_id Field ID.
		 * @param {string}  imageUrl The URL of the image file.
		 */
		updateImagePhotoPreview: function( field_id = 0, imageUrl = '' ) {

			const 	preview_field       = $('#charitable-field-' + field_id ),
					preview_field_image = preview_field.find('img.charitable-campaign-builder-preview-photo');


			if ( false === imageUrl || '' === imageUrl || ! app.isValidURL( imageUrl ) ) {
				preview_field.find('.primary-image-container .primary-image img').remove();
				preview_field.find('.primary-image-container .primary-image').append('<img src="../../images/campaign-builder/fields/photo/temp-icon.svg" class="temp-icon" alt="" />');
				preview_field.find('.primary-image-container').removeClass('has-image');

			} else {

				preview_field.find('i.temp-icon').remove();

				if ( preview_field_image.length > 0 ) {

					preview_field_image.attr('src', imageUrl );

				} else {

					preview_field.find('.primary-image-container .primary-image').append('<img src="' + imageUrl + '" class="charitable-campaign-builder-preview-photo" />');
					preview_field.find('.primary-image-container').addClass('has-image');
				}

			}

		},

		/**
		 * A utiliay funciton that checks to see if an input is a valid URL.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} str The URL of the image file.
		 */
		isValidURL: function( str ) {

			var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
			'((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
			'((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
			'(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
			'(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
			'(\\#[-a-z\\d_]*)?$','i'); // fragment locator
			return !!pattern.test(str);

		},

		/**
		 * General reset of the preview area (maybe when tabs and/or fields are added and/or removed and/or moved)
		 *
		 * @since 1.8.0
		 *
		 * @param {string} str The URL of the image file.
		 */
		resetPreviewArea: function() {

			elements.$formPreview.find('.charitable-field').each(function() {
				$( this ).removeClass( 'active' );
			});

			$('.charitable-select-field-notice').show();
			$('.charitable-layout-options-tab-general .charitable-panel-field').removeClass('active');

			$("#charitable-preview-tab-container").removeClass('active');

		},

		/**
		 * Delete a field.
		 *
		 * @param {int} id Field ID.
		 * @param {boolean} confirmation Show a confirmation popup - true or false.
		 *
		 * @since 1.8.0
		 */
		fieldDelete: function( id, confirmation = true ) {

			var $field = $( '#charitable-field-' + id ),
				type   = $field.data( 'field-type' );

			if ( $field.hasClass( 'no-delete' ) ) {
				app.youCantRemoveFieldPopup();
				return;
			}

			if ( confirmation ) {
				app.confirmFieldDeletion( id, type );
			} else {
				app.fieldDeleteById( id, type );
			}
		},

		/**
		 * Show the confirmation popup before the field deletion.
		 *
		 * @param {int} id Field ID.
		 * @param {string} type Field type.
		 *
		 * @since 1.8.0
		 */
		confirmFieldDeletion: function( id, type ) {

			var fieldData = {
				'id'      : id,
				'message' : charitable_builder.delete_confirm
			};

			var event = CharitableUtils.triggerEvent( $builder, 'charitableBeforeFieldDeleteAlert', [ fieldData, type ] );

			// Allow callbacks on `charitableBeforeFieldDeleteAlert` to prevent field deletion by triggering `event.preventDefault()`.
			if ( event.isDefaultPrevented() ) {
				return;
			}

			$.confirm( {
				title   : false,
				content : fieldData.message,
				icon    : 'fa fa-exclamation-circle',
				type    : 'orange',
				buttons: {
					confirm: {
						text     : charitable_builder.ok,
						btnClass : 'btn-confirm',
						keys     : [ 'enter' ],
						action: function() {
							app.fieldDeleteById( id, type );
						}
					},
					cancel: {
						text: charitable_builder.cancel,
						keys: [ 'esc' ]
					}
				}
			} );
		},

		/**
		 * Add handlers to create confirmation popup before the campaign deletion.
		 *
		 * @since 1.8.0
		 */
		confirmCampaignDeletion: function() {

			// Embed form.
			$builder.on( 'click', '.charitable-button.alert.delete-campaign', function( e ) {

				e.preventDefault();

				$.alert( {
					title: false,
					content : charitable_builder.campaign_delete_confirm,
					icon: 'fa fa-info-circle',
					type: 'red',
					buttons: {
						confirm: {
							text     : charitable_builder.ok,
							btnClass : 'btn-confirm',
							keys     : [ 'enter' ],
							action: function() {
								// delete the campaign.
							}
						},
						cancel: {
							text: charitable_builder.cancel,
							keys: [ 'esc' ]
						}
					}
				} );

			} );

		},

		/**
		 * Remove the field by ID.
		 *
		 * @since 1.8.0
		 *
		 * @param {int}    id       Field ID.
		 * @param {string} type     Field type/slug
		 * @param {int}    duration Duration of animation.
		 */
		fieldDeleteById: function( id = false, type = '', duration = 200 ) { // eslint-disable-line

			if ( id === false ) {
				return;
			}

			$( `#charitable-field-${id}` ).fadeOut( duration, function() {

				const 	$field  = $( this ),
						section = $field.closest('.charitable-field-section'),
						type    = $field.data( 'field-type' ),
						max     = typeof $field.data('field-max') !== 'undefined' ? parseInt( $field.data('field-max') ) : 99;

				$builder.trigger( 'charitableBeforeFieldDelete', [ id, type ] );

				$field.remove();
				$( '#charitable-field-option-' + id ).remove();
				$( '.charitable-field, .charitable-preview-top-bar' ).removeClass( 'active' );

				// Remove the settings boxes, assuming they existed.
				$('.charitable-layout-options-tab-general .charitable-panel-field[data-field-id="' + id + '"]').remove();
				$('.charitable-select-field-notice').show();

				app.checkNoFieldsPreview();
				app.checkFieldTargetState( section );

				// Possible enable the field button on the left if the field had a max and deleteing it just freed up something.
				if ( max === 0 || elements.$preview.find('.charitable-field.charitable-field-donate-amount').length < max ) {
					$('#charitable-panel-design button#charitable-add-fields-donate-amount').removeClass('charitable-disabled');
				}

				// if this was removed in a tab, check to see if the tab is now empty
				app.checkIfTabsAreEmpty();

				if ( $('.charitable-layout-options-tab.charitable-layout-options-tab-general').hasClass('active') ) {
					app.fieldTabToggle( 'add-layout' );
				}

				const 	$fieldsOptions = $( '.charitable-field-option' ),
						$submitButton = $builder.find( '.charitable-field-submit' );

				// No fields remains.
				if ( $fieldsOptions.length < 1 ) {
					elements.$sortableFieldsWrap.append( elements.$noFieldsPreview.clone() );
					elements.$fieldOptions.append( elements.$noFieldsOptions.clone() );
					$submitButton.hide();
				}

				// Only Layout fields remains.
				if ( ! $fieldsOptions.filter( ':not(.charitable-field-option-layout)' ).length ) {
					$submitButton.hide();
				}

				// Check and see if deleting this changed any enabling of add field buttons on the left.
				app.checkFieldAllow();
				app.checkFieldMax( type, max );

				// Check and see if this enables/disables a checkmark for recommended buttons on the left.
				app.checkRecommendedFields( type );

				if ( wpCookies.get( 'charitable_panel_active_field_id' ) === id ) {
					wpCookies.set( 'charitable_panel_active_field_id', '', 2592000 ); // 1 month.
				}

				// Finally, make sure to note that the campaign isn't saved.
				app.setCampaignNotSaved();

				$builder.trigger( 'charitableFieldDelete', [ id, type ] );

			} );
		},

		/**
		 * Edit the field by ID.
		 *
		 * @since 1.8.0
		 *
		 * @param {int}    id       Field ID.
		 * @param {string} type     Field type (deprecated)
		 * @param {int}    duration Duration of animation.
		 */
		fieldEdit: function ( type, section, edit_field_id, field_id, field_type ) {

			if ( section === 'general' || section === 'standard' || section === 'pro' || section === 'recommended' ) {

				app.panelSwitch( 'design' );

				$('.charitable-select-field-notice').hide();
				$('.charitable-layout-options-tab-general .charitable-panel-field').removeClass('active');
				$('.charitable-layout-options-tab-general .charitable-panel-field[data-field-id="' + field_id + '"]').addClass('active');

				// If the first setting is a form field go ahead and make that the focus.
				$('.charitable-layout-options-tab-general .charitable-panel-field[data-field-id="' + field_id + '"]').find('input[type=text],input[type=button],input[type=range],input[type=url],textarea,select').filter(':visible:first').focus();

				if ( 'html' === field_type ) {
					// CharitableCampaignBuilderFieldHTML.codemirrorInit( field_id ); // eslint-disable-line
					// trigger an event that another JS file can listen for.
					$builder.trigger( 'charitableFieldAddHTML', [ field_id, field_type ] );
				} else {
					$builder.trigger( 'charitableFieldEdit', [ type, section, edit_field_id, field_id, field_type ] );
				}

			}

		},

		/**
		 * Duplicate field.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} id Field id.
		 */
		fieldDuplicate: function( id ) {

			const $field = $( `#charitable-field-${id}` );

			if ( $field.hasClass( 'no-duplicate' ) ) {
				$.alert( {
					title: charitable_builder.field_locked,
					content: charitable_builder.field_locked_no_duplicate_msg,
					icon: 'fa fa-info-circle',
					type: 'blue',
					buttons: {
						confirm: {
							text: charitable_builder.close,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ]
						}
					}
				} );

				return;
			}

			$.confirm( {
				title   : false,
				content : charitable_builder.duplicate_confirm,
				icon    : 'fa fa-exclamation-circle',
				type    : 'orange',
				buttons : {
					confirm: {
						text: charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action: function() {

							// Disable the current button to avoid firing multiple click events.
							// By default, "jconfirm" tends to destroy any modal DOM element upon button click.
							this.$$confirm.prop( 'disabled', true );

							const beforeEvent = CharitableUtils.triggerEvent( $builder, 'charitableBeforeFieldDuplicate', [ id, $field ]  );

							// Allow callbacks on `charitableFieldBeforeDuplicate` to cancel field duplication.
							if ( beforeEvent.isDefaultPrevented() ) {
								return;
							}

							const 	newFieldId = app.fieldDuplicateRoutine( id ),
									$newField  = $( `#charitable-field-${newFieldId}` );

							CharitableUtils.triggerEvent( $builder, 'charitableFieldDuplicated', [ id, $field, newFieldId, $newField ]  );

						}
					},
					cancel: {
						text: charitable_builder.cancel,
						keys: [ 'esc' ]
					}
				}
			} );

		},

		/**
		 * Duplicate field routine.
		 *
		 * @since 1.8.0
		 *
		 * @param {integer|number|string} id Field Id.
		 *
		 * @returns {number} New field Id.
		 */
		fieldDuplicateRoutine: function( id ) { // eslint-disable-line max-lines-per-function, complexity

			const 	$field             = $( `#charitable-field-${id}` ),
					$fieldActiveFields = elements.$sortableFieldsWrap.find( '> .active' ),
					$fieldActiveTabs   = elements.$sortableFieldsWrap.find( '> .active' ),
					$newField          = $field.clone(),
					newFieldID         = parseInt( elements.$nextFieldId.val(), 10 ) + 1,
					$settingsArea      = $( '#charitable-field-options .charitable-layout-options-group-inner .charitable-panel-field[data-field-id="' + id + '"]' );

			if ( $newField ) {
				// Update next field id hidden input value.
				app.updateFormHiddenFieldID( newFieldID );
			}

			// Toggle visibility states.
			$field.after( $newField );
			$fieldActiveFields.removeClass( 'active' );
			$fieldActiveTabs.removeClass( 'active' );
			$newField.addClass( 'active' ).attr( {
				'id'           : `charitable-field-${newFieldID}`,
				'data-field-id': newFieldID
			} );

			$settingsArea.each(function(){
				var $newSettingsField = $(this).clone(),
					isContains = $newSettingsField.text().indexOf('ID:') > -1;

				$newSettingsField.attr('data-field-id', newFieldID );

				$(this).removeClass('active');
				$newSettingsField.addClass('active');

				if ( isContains ) {
					$newSettingsField.text(function(index,text){
						return text.replace( 'ID: ' + id, 'ID: ' + newFieldID );
					});
				}

				if ( typeof $newSettingsField.attr('name') !== 'undefined' && $newSettingsField.attr('name').length > 0 ) {
					$newSettingsField.attr('name', $newSettingsField.attr('name').replace( id, newFieldID ) );
				}
				if ( typeof $newSettingsField.attr('id') !== 'undefined' && $newSettingsField.attr('id').length > 0 ) {
					$newSettingsField.attr('id', $newSettingsField.attr('id').replace( id, newFieldID ) );
				}

				$newSettingsField.find('*').each(function () {

					var $subField = $( this );

					if ( typeof $subField.attr('for') !== 'undefined' && $subField.attr('for').length > 0 ) {
						$subField.attr('for', $subField.attr('for').replace( id, newFieldID ) );
					}
					if ( typeof $subField.attr('name') !== 'undefined' && $subField.attr('name').length > 0 ) {
						$subField.attr('name', $subField.attr('name').replace( id, newFieldID ) );
					}
					if ( typeof $subField.attr('id') !== 'undefined' && $subField.attr('id').length > 0 ) {
						$subField.attr('id', $subField.attr('id').replace( id, newFieldID ) );
					}
					if ( typeof $subField.data('field-id') !== 'undefined' && $subField.data('field-id').length > 0 ) {
						$newSettingsField.attr('data-field-id', newFieldID );
					}


				});

				$newSettingsField.find('.charitable-toggle-control').attr('data-field-id', newFieldID );
				$newSettingsField.find('.charitable-checkbox-for-toggle').attr('data-field-id', newFieldID );

				$('#charitable-field-options .charitable-layout-options-tab-general .charitable-layout-options-group-inner').append( $newSettingsField );

				// Is this setting a WYSIWYG field for a campaign description?
				if ( typeof $newSettingsField.data('special-type') !== 'undefined' && $newSettingsField.data('special-type') === 'campaign_description' ) {
					// replace the current field in the "each" with a field then init it for the quill.
					let contentToCopy = $newSettingsField.find('.campaign-builder-htmleditor .ql-editor').html();
					$newSettingsField.find( '.ql-toolbar' ).remove();
					$newSettingsField.find('.campaign-builder-htmleditor').html( '<div data-textarea-name="_fields[' + newFieldID + '][content]" id="charitable-panel-field-settings-field_campaign-description_html_' + newFieldID + '" class="campaign-builder-htmleditor">' + contentToCopy + '</div>' );
					app.initHTMLEditorFields( $newSettingsField.find('.campaign-builder-htmleditor'), false );
				}

				// Check and see if duplicated this changed any enabling of add field buttons on the left.
				app.checkFieldAllow();
				app.checkFieldMax();


			});

			return newFieldID;

		},

		/**
		 * Check if we had focusout event from certain fields.
		 *
		 * @since 1.8.0
		 */
		focusOutEvent: function() {
			if ( elements.$focusOutTarget === null ) {
				return;
			}

			elements.$focusOutTarget = null;
		},

		/**
		 * Determine if form wrapper has sorting locked.
		 *
		 * @since 1.8.0
		 *
		 * @param {mixed} el DOM element or jQuery object of some container on the field preview.
		 *
		 * @returns {bool} True if form preview wrapper sorting is disabled.
		 */
		isFormPreviewActionsDisabled: function( el ) {

			return $( el ).closest( '.charitable-field-wrap' ).hasClass( 'ui-sortable-disabled' );
		},

		/**
		 * Toggle field group visibility in the field sidebar.
		 *
		 * @since 1.8.0
		 *
		 * @param {mixed}  el     DOM element or jQuery object.
		 * @param {string} action Action.
		 */
		fieldGroupTogglev2: function( el, action ) {

			var $this         = $( el ),
				groupName     = $this.closest('.charitable-group').data( 'group_id' ),
				$nearestGroup = $this.closest('.charitable-group'),
				$rows         = $nearestGroup.find( '.charitable-group-rows' ),
				$group        = $rows.parent(), // eslint-disable-line
				$icon         = $this.find( 'i' ),
				cookieName    = 'charitable_panel_layout_options_tabs_tab_open_' + groupName;

			if ( action === 'click' ) {

				$icon.toggleClass( 'charitable-angle-right' );
				$rows.stop().slideToggle( '', function() {
					$nearestGroup.toggleClass( 'charitable-closed' );
					if ( $nearestGroup.hasClass( 'charitable-closed' ) ) {

						$nearestGroup.removeClass( 'charitable-open' );

						wpCookies.remove( cookieName );
					} else {
						wpCookies.set( cookieName, 'true', 2592000 ); // 1 month
					}
				} );

				return;
			}

		},

		/**
		 * Toggle field group visibility in the field sidebar.
		 *
		 * @since 1.8.0
		 *
		 * @param {mixed}  el     DOM element or jQuery object.
		 * @param {string} action Action.
		 */
		fieldGroupTogglev3: function( el, action = 'click' ) {

			var $this = $( el ),
				$nearestGroup = $this.closest('.charitable-add-fields-group'),
				$rows = $nearestGroup.find( '.charitable-group-rows' ),
				$icon = $this,
				groupName = 'test',
				cookieName = 'charitable_panel_add_layout_blocks_open_' + groupName;

			if ( action === 'click' ) {

				$icon.toggleClass( 'charitable-angle-right' );
				$rows.stop().slideToggle( '', function() {
					$nearestGroup.toggleClass( 'charitable-closed' );
					if ( $nearestGroup.hasClass( 'charitable-closed' ) ) {

						$nearestGroup.removeClass( 'charitable-open' );

						wpCookies.remove( cookieName );
					} else {

						wpCookies.set( cookieName, 'true', 2592000 ); // 1 month
					}
				} );

				return;
			}

		},

		/**
		 * Toggle field group visibility in the field sidebar.
		 *
		 * @since 1.8.0
		 *
		 * @param {mixed}  el     DOM element or jQuery object.
		 * @param {string} action Action.
		 */
		fieldGroupToggle: function( el, action ) {

			var $this = $( el ),
				$buttons = $this.next( '.charitable-add-fields-buttons' ),
				$group = $buttons.parent(),
				$icon = $this.find( 'i' ),
				groupName = $this.data( 'group' ),
				cookieName = 'charitable_field_group_' + groupName;

				if ( groupName == 'general-layout-campaign' || groupName == 'general-layout-faq' ) {
					//$buttons = $this.next('.charitable-field-option-row');
					$group = $this.parent().find('.charitable-' + groupName + '-group-inner');
					$buttons = $group;
				}

			if ( action === 'click' ) {

				if ( $group.hasClass( 'charitable-closed' ) ) {
					wpCookies.remove( cookieName );
				} else {
					wpCookies.set( cookieName, 'true', 2592000 ); // 1 month
				}
				$icon.toggleClass( 'charitable-angle-right' );
				$buttons.stop().slideToggle( '', function() {
					$group.toggleClass( 'charitable-closed' );
				} );

				return;

			} else if ( action === 'load' ) {

				$buttons = $this.find( '.charitable-add-fields-buttons' );
				$icon = $this.find( '.charitable-add-fields-heading i' );
				groupName = $this.find( '.charitable-add-fields-heading' ).data( 'group' );
				cookieName = 'charitable_field_group_' + groupName;

				if ( wpCookies.get( cookieName ) === 'true' ) {
					$icon.toggleClass( 'charitable-angle-right' );
					$buttons.hide();
					$this.toggleClass( 'charitable-closed' );
				}
			}
		},

		/**
		 * Add new field.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} type    Field type.
		 * @param {object} options Additional options.
		 *
		 */
		fieldMove: function( type, options ) { // eslint-disable-line max-lines-per-function

			var $field = options.field;

			$field.find('.charitable-field-edit').click();

			// remove disabled from add fields.
			$builder.find( '.charitable-add-fields .charitable-add-fields-button' ).prop( 'disabled', false );

			// if a field moved, check the section it landed on and adjust the field target state.
			var section = $field.closest('.charitable-field-section');
			app.checkFieldTargetState( section );

			// if a field moved, also check where it came FROM to see if it's now empty.
			if ( options.section ) {
				app.checkFieldTargetState( options.section );
			}

			// add a trigger for anything else that wants to hook.
			$builder.trigger( 'charitableFieldMove', [ options.fieldId, type ] );

		},

		/**
		 * Add new field.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} type    Field type.
		 * @param {object} options Additional options.
		 *
		 * @returns {promise|void} jQuery.post() promise interface.
		 */
		fieldAdd: function( type, options ) { // eslint-disable-line max-lines-per-function

			const $btn = $( `#charitable-add-fields-${type}` ); // eslint-disable-line

			adding = true;

			if ( Charitable.Admin.Builder.DragFields && typeof Charitable.Admin.Builder.DragFields.disableDragAndDrop === 'function' ) {
				Charitable.Admin.Builder.DragFields.disableDragAndDrop();
			}

			app.disableFormActions();

			let defaults = {
				campaign_title: s.campaignTitle,
				position: 'bottom',
				$sortable: 'base',
				placeholder: false,
				scroll: true,
				defaults: false,
				column_id : options.column_id,
				tab_id : options.tab_id,
				section_id : options.section_id,
				area: 'fields' // vs tabs
			};

			options = $.extend( {}, defaults, options );

			let data = {
				action         : 'charitable_new_field_' + type,
				id             : s.formID,
				column_id      : options.column_id,
				tab_id         : options.tab_id,
				section_id     : options.section_id,
				field_id       : parseInt( elements.$nextFieldId.val() ) + 1,
				type           : type,
				campaign_title : options.campaign_title,
				defaults       : options.defaults,
				nonce          : charitable_builder.nonce
			};

			return $.post( charitable_builder.ajax_url, data, function( res ) { // eslint-disable-line complexity

				if ( ! res.success ) {
					wpchar.debug( 'Add field AJAX call is unsuccessful:', res );
					return;
				}

				wpchar.debug ('fieldAdd return');

				app.refreshTabFieldsSortDrag();

				// define the base container.
				let $baseFieldsContainer = ( options.area === 'tabs' ) ? elements.$sortableTabContent.find( 'li[data-tab-id="' + data.tab_id + '"] .charitable-tab-wrap' ) : elements.$formPreview.find( '.charitable-field-wrap[data-section-id="' + data.section_id + '"]' );

				wpchar.debug( $baseFieldsContainer );

				const 	$newField   = $( res.data.preview ),
						maxAllowed  = typeof res.data.max !== 'undefined' ? parseInt( res.data.max ): 99,
						$newOptions = $( res.data.options );

				let	$fieldContainer = options.$sortable;

				adding = false;

				$newField.css( 'display', 'none' );

				if ( options.placeholder ) {
					options.placeholder.remove();
				}

				if ( options.$sortable === 'default' || ! options.$sortable.length ) {
					$fieldContainer = $baseFieldsContainer.find( '.charitable-fields-sortable-default' );
				}

				if ( options.$sortable === 'base' || ! $fieldContainer.length ) {
					$fieldContainer = $baseFieldsContainer;
				}

				let event = CharitableUtils.triggerEvent(
					$builder,
					'charitableBeforeFieldAddToDOM',
					[ options, $newField, $newOptions, $fieldContainer ]
				);

				// Allow callbacks on `charitableBeforeFieldAddToDOM` to cancel adding field
				// by triggering `event.preventDefault()`.
				if ( event.isDefaultPrevented() ) {
					return;
				}

				// Add field to the base level of fields.
				// Allow callbacks on `charitableBeforeFieldAddToDOM` to skip adding field to the base level
				// by setting `event.skipAddFieldToBaseLevel = true`.
				if ( ! event.skipAddFieldToBaseLevel ) {
					// wpchar.debug('adding to base level');
					app.fieldAddToBaseLevel( options, $newField, $newOptions );
				}

				// Do we now disable the field add button because of "max"?
				if ( elements.$preview.find('.charitable-field.charitable-field-donate-amount').length >= maxAllowed ) {
					$('#charitable-panel-design button#charitable-add-fields-donate-amount').addClass('charitable-disabled');
				}

				// IT IT SHOWTIME... FADE IN THE FIELD.
				$newField.fadeIn();

				app.checkFieldTargetState( $('.charitable-field-section[data-section-id="' + data.section_id + '"]') );

				$newField.find('.charitable-field-edit').click();

				if ( $( '.charitable-field-option:not(.charitable-field-option-layout)' ).length ) {
					$builder.find( '.charitable-field-submit' ).show();
				}

				// Update next field id hidden input value.
				app.updateFormHiddenFieldID( res.data.field_id );

				// add the invisible field under 'settings' tab so the user can instantly edit the settings for the field
				if ( res.data.edit_field_html !== '' ) {

					$('.charitable-layout-options-tab-general .charitable-layout-options-group-inner').append( res.data.edit_field_html ).find('.charitable-panel-field[data-field-id="' + res.data.field_id + '"]').addClass('active');

					// Does this new field have any WYSIWYG fields in the settings?
					if ( res.data.html_field != '' ) {
						// this requires a quill assignment, an HTML field.
						$('.charitable-panel-field[data-field-id="' + res.data.field_id + '"]').find( '.campaign-builder-htmleditor' ).each( function() {
							app.initHTMLEditorFields( $( this ), false );
						});
					}

					// check and see if the field default text requires an update, say maybe for campaign description.
					if ( type === 'campaign-description' && typeof s.campaignDescription !== 'undefined' ) {
						$newField.find('.charitable-campaign-builder-no-description-preview').html( '<div>' + s.campaignDescription + '</div>' );

						if ( $( '#charitable-panel-field-settings-field_campaign-description_html_' + data.field_id ).find('.ql-editor').length === 0 ) {
							app.initHTMLEditorFields( $( '#charitable-panel-field-settings-field_campaign-description_html_' + data.field_id )  );
						}

						$( '#charitable-panel-field-settings-field_campaign-description_html_' + data.field_id).find('.ql-editor').html( s.campaignDescription );
					}

				}

				// if this is a new organizer field, we need to init the dropdown.
				if ( res.data.field.type === 'organizer' ) {
					$('.campaign-builder-campaign-creator-id-mini[data-field-id="' + res.data.field_id + '"] select').select2({
						templateResult: app.campaignCreatorFormatOptions
					});
				}

				//wpchar.initTooltips();

				// Check and see if duplicated this changed any enabling of add field buttons on the left.
				app.checkFieldAllow();
				app.checkFieldMax();

				app.updateEndDateRelatedItems();
				app.updateGoalRelatedItems();

				// Check and see if this enables/disables a checkmark for recommended buttons on the left.
				app.checkRecommendedFields( type );

				// determine if we add/remove the no fields preview area
				var fieldContainerSectionType = $fieldContainer.data( 'section-type' ),
					forceDeleteNoPreview = false;

				if (typeof fieldContainerSectionType !== 'undefined' && fieldContainerSectionType === 'fields' ) { // could also be 'tabs' but we want to just check 'fields'.
					forceDeleteNoPreview = true; // eslint-disable-line
				}

				app.checkNoFieldsPreview();

				// if this is a photo, auto open the media library.
				if ( 'photo' === type ) {
					$( '.charitable-panel-field-uploader[data-field-id="' + res.data.field_id + '"] input.charitable-campaign-builder-upload-button' ).click();
				} else if ( 'donate-amount' === type ) {
					$( '.charitable-campaign-suggested-donations-mini' ).each( function() {
						app.initSuggestedDonationsMini( $( this ) );
						app.updateSuggestdDonationsMiniRowsFromSettings( $( this ) );
					});
				}

				// finally... just trigger and we're done.

				$builder.trigger( 'charitableFieldAdd', [ res.data.field.id, type ] );

			} ).fail( function( xhr, textStatus, e ) { // eslint-disable-line

				adding = false;

			} ).always( function() {

				$builder.find( '.charitable-add-fields .charitable-add-fields-button' ).prop( 'disabled', false );

				if ( ! adding ) {
					if ( Charitable.Admin.Builder.DragFields && typeof Charitable.Admin.Builder.DragFields.enableDragAndDrop === 'function' ) {
						Charitable.Admin.Builder.DragFields.enableDragAndDrop();
					}
					app.enableFormActions();
				}
			} );
		},

		/**
		 * Add new field to the base level of fields.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} options     Field add additional options.
		 * @param {jQuery} $newField   New field preview object.
		 * @param {jQuery} $newOptions New field options object.
		 */
		fieldAddToBaseLevel: function( options, $newField, $newOptions ) { // eslint-disable-line

			wpchar.debug ('options');
			wpchar.debug ( options );

			let $baseFieldsContainer = '';

			if ( options.area === 'tabs' ) {
				$baseFieldsContainer = elements.$sortableTabContent.find( 'li[data-tab-id="' + options.tab_id + '"] .charitable-tab-wrap' );
				$baseFieldsContainer.parent().removeClass('empty-tab');
			} else {
				$baseFieldsContainer = elements.$formPreview.find( '.charitable-field-wrap[data-section-id="' + options.section_id + '"]' );
			}

			wpchar.debug ('baseFieldsContainer');
			wpchar.debug ( $baseFieldsContainer );

			// $baseFieldsContainer = elements.$sortableFieldsWrap,
			const 	$baseFields = $baseFieldsContainer.find( '> :not(.charitable-field-drag-pending)' ),
					$lastBaseField = $baseFields.last(),
					totalBaseFields = $baseFields.length;

			let	$fieldInPosition = elements.$fieldOptions;

			if ( options.position === 'top' ) {
				// Add field to top of base level fields.
				$baseFieldsContainer.prepend( $newField );
				return;
			}

			if (
				options.position === 'bottom' && (
					! $lastBaseField.length ||
					! $lastBaseField.hasClass( 'charitable-field-stick' )
				)
			) {

				wpchar.debug ('adding field to the bottom!!!!');

				// Add field to the bottom of base level fields.
				$baseFieldsContainer.append( $newField );
				return;
			}

			if ( options.position === 'bottom' ) {
				options.position = totalBaseFields;
			}

			if (
				options.position === totalBaseFields &&
				$lastBaseField.length && $lastBaseField.hasClass( 'charitable-field-stick' )
			) {

				// Check to see if the last field we have is configured to
				// be stuck to the bottom, if so add the field above it.
				$lastBaseField.before( $newField );
				// $fieldOptions.find( `#charitable-field-option-${lastBaseFieldId}` ).before( $newOptions );

				return;
			}

			$fieldInPosition = $baseFieldsContainer.children( ':not(.charitable-field-drag-pending)' ).eq( options.position );

			if ( $fieldInPosition.length ) {
				// Add field to a specific location.
				$fieldInPosition.before( $newField );
				// $fieldOptions.find( `#charitable-field-option-${fieldInPositionId}` ).before( $newOptions );

				return;
			}

			// Something is wrong. Just add the field. This should never occur.
			$baseFieldsContainer.append( $newField );

		},

		/**
		 * Check if the tabs in the preview area have no fields.
		 *
		 * @since 1.8.0
		 */
		checkIfTabsAreEmpty: function () {

			elements.$preview.find('.tab-content ul li.tab_content_item').each(function(){
				var numfields = $(this).find('.charitable-field').length;
				if ( numfields === 0 ) {
					$(this).addClass('empty-tab');
				}
			});

		},

		/**
		 * Disable Preview, Embed, Save form actions and Form Builder exit button.
		 *
		 * @since 1.8.0
		 *
		 * @param {boolean} disableExit Exit button.
		 * @param {boolean} disableCampaignTitleEdit Campaign title edit.
		 * @param {boolean} disablePreviewButton Preview button.
		 *
		 */
		disableFormActions: function( disableExit = false, disableCampaignTitleEdit = true, disablePreviewButton = false ) {

			$.each(
				[
					elements.$embedButton,
					elements.$statusButton,
					elements.$saveButton
				],
				function( _index, button ) {
					button.prop( 'disabled', true ).addClass( 'charitable-disabled' );
				}
			);

			if ( disableExit ) {
				$.each(
					[
						elements.$exitButton
					],
					function( _index, button ) {
						button.prop( 'disabled', true ).addClass( 'charitable-disabled' );
					}
				);
			}

			if ( disableCampaignTitleEdit ) {
				$('.charitable-edit-campaign-title-area').addClass('charitable-disabled');
				$('.charitable-edit-campaign-title-area input').prop( 'disabled', true );
				$('.charitable-edit-campaign-title-area a').addClass('charitable-disabled');
			}

			if ( disablePreviewButton ) {
				elements.$previewButton.prop( 'disabled', true ).addClass( 'charitable-disabled' );
			}

		},

		/**
		 * Enable Preview, Embed, Save form actions and Form Builder exit button.
		 *
		 * @since 1.8.0
		 */
		enableFormActions: function() {

			$.each(
				[
					// elements.$previewButton,
					// elements.$embedButton,
					elements.$statusButton,
					elements.$saveButton,
					elements.$exitButton
				],
				function( _index, button ) {
					button.prop( 'disabled', false ).removeClass( 'charitable-disabled' );
				}
			);

			$('.charitable-edit-campaign-title-area').removeClass('charitable-disabled');
			$('.charitable-edit-campaign-title-area input').prop( 'disabled', false );
			$('.charitable-edit-campaign-title-area a').removeClass('charitable-disabled');

			if ( s.formStatus === 'publish' ) {
				elements.$embedButton.prop( 'disabled', false ).removeClass( 'charitable-disabled' );
			}

		},

		/**
		 * Field choice delete error alert.
		 *
		 * @since 1.8.0
		 */
		fieldChoiceDeleteAlert: function() {

			$.alert( {
				title   : false,
				content : charitable_builder.error_choice,
				icon    : 'fa fa-info-circle',
				type    : 'blue',
				buttons : {
					confirm: {
						text: charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ]
					}
				}
			} );

		},

		/**
		 * Make field choices sortable.
		 * Currently used for select, radio, and checkboxes field types.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} type Field type.
		 * @param {string} selector Element.
		 */
		fieldChoiceSortable: function( type, selector ) {

			selector = typeof selector !== 'undefined' ? selector : '.charitable-field-option-' + type + ' .charitable-field-option-row-choices ul';

			$( selector ).sortable( {
				items  : 'li',
				axis   : 'y',
				delay  : 100,
				opacity: 0.6,
				handle : '.move',
				stop:function( e, ui ) {
					var id = ui.item.parent().data( 'field-id' );
					app.fieldChoiceUpdate( type, id );
					$builder.trigger( 'charitableFieldChoiceMove', ui );
				},
				update: function( e, ui ) { // eslint-disable-line
				}
			} );
		},

		/**
		 * Delete a tab.
		 *
		 * @param {int} id Field ID.
		 *
		 * @since 1.8.0
		 */
		tabDelete: function( groupID ) {

			app.confirmTabDeletion( groupID );

		},

		/**
		 * Show the confirmation popup before the tab deletion.
		 *
		 * @param {int} groupID Group ID.
		 *
		 * @since 1.8.0
		 */
		confirmTabDeletion: function( groupID ) {

			var tabData = {
				'id'      : groupID,
				'message' : charitable_builder.delete_tab_confirm
			};

			var event = CharitableUtils.triggerEvent( $builder, 'charitableBeforeTabDeleteAlert', [ tabData ] );

			// Allow callbacks on `charitableBeforeFieldDeleteAlert` to prevent field deletion by triggering `event.preventDefault()`.
			if ( event.isDefaultPrevented() ) {
				return;
			}

			$.confirm( {
				title   : false,
				content : charitable_builder.delete_tab_confirm,
				icon    : 'fa fa-exclamation-circle',
				type    : 'orange',
				buttons : {
					confirm: {
						text     : charitable_builder.ok,
						btnClass : 'btn-confirm',
						keys     : [ 'enter' ],
						action   : function() {
							app.tabDeleteById( groupID );
						}
					},
					cancel: {
						text: charitable_builder.cancel,
						keys: [ 'esc' ]
					}
				}
			} );

		},

		/**
		 * Actual deletion of a tab.
		 *
		 * @param {int} groupID Group ID.
		 *
		 * @since 1.8.0
		 */
		tabDeleteById: function ( groupID ) {

			const 	tab_settings        = elements.$fieldOptions.find('[data-group_id="' + groupID + '"]'),
					tab_preview_nav     = $('nav ul li#tab_' + groupID + '_title'),
					tab_preview_content = elements.$preview.find('.tab-content ul li.tab_content_item[data-tab-id="' + groupID + '"]'),
					tab_count           = elements.$preview.find('.tab-content .tab_content_item').length - 1,
					previous_group_ID   = Math.abs( groupID - 1 ),
					tab_content         = elements.$preview.find('.tab-content');

			tab_settings.remove();
			tab_preview_nav.remove();
			tab_preview_content.remove();

			if ( elements.$preview.find('nav.charitable-campaign-preview-nav ul li[data-tab-id="' + previous_group_ID + '"] a').length > 0 ) {
				elements.$preview.find('nav.charitable-campaign-preview-nav ul li[data-tab-id="' + previous_group_ID + '"] a' ).click();
			} else if ( tab_count === 1 ) { // there's at least one tab left, we need to make some tab active
				elements.$preview.find('nav.charitable-campaign-preview-nav ul li a' ).first().click();
			}

			// adjust the CSS of the tab-content div to reflect no more tabs, if that's the case.
			if ( tab_count === 0 ) {
				tab_content.addClass('empty-tabs');
				// add the HTML message.
				tab_content.append('<p class="no-tab-notice">' + charitable_builder.no_tabs + '</p>');
			} else {
				tab_content.removeClass('empty-tabs');
				tab_content.find('.no-tab-notice').remove();
			}

			app.checkHideTabNavigation();

		},

		//--------------------------------------------------------------------//
		// Preview
		//--------------------------------------------------------------------//

		/**
		 * Element bindings for Embed and Save/Exit items.
		 *
		 * @since 1.8.0
		 */
		bindUIActionsPreview: function() {

			// Embed form.
			$('body').on( 'click', 'a#charitable-preview-btn', function( e ) {

				e.preventDefault();
				e.stopPropagation();

				if ( parseInt( elements.$campaignID.val() ) === 0 ) {

					$.alert( {
						title   : charitable_builder.no_preview_must_save,
						content : charitable_builder.no_preview_must_save_msg,
						icon    : 'fa fa-info-circle',
						type    : 'blue',
						buttons : {
							confirm: {
								text: charitable_builder.close,
								btnClass: 'btn-confirm',
								keys: [ 'enter' ]
							}
						}
					} );

					return;

				} else {

					var openNewTabURL = '';

					if ( typeof this.href !== 'undefined' ) {
						openNewTabURL = this.href.replace( 'charitable_campaign_preview=0', 'charitable_campaign_preview=' + s.campaignID );
					}

					// We are assuming that if they are able to click preview then the campaign is either in draft/not posiblished OR published but they made a change.

					// Determine if this is an already published campaign... if so, then save the campaign data TEMPORARILY so we can preview it...
					if ( typeof s.formStatus !== 'undefined' && s.formStatus === 'publish' ) {
						// Passing in "true" as the second parameter to formSave() will tell it to save the campaign data temporarily.
						app.formSave( false, true, openNewTabURL ); // false - no redirect, true - yes this is a preview.

					} else if ( typeof s.formStatus !== 'undefined' && s.formStatus === 'draft' ) {
						app.formSave( false, true, openNewTabURL ); // still previewing, so pass true.
					}



				}

			} );

		},

		//--------------------------------------------------------------------//
		// Save and Exit
		//--------------------------------------------------------------------//

		/**
		 * Element bindings for Embed and Save/Exit items.
		 *
		 * @since 1.8.0
		 */
		bindUIActionsSaveExit: function() {

			// Embed form.
			$builder.on( 'click', '#charitable-embed', function( e ) {

				e.preventDefault();
				e.stopPropagation();

				if ( $( this ).hasClass( 'charitable-disabled' ) ) {
					return;
				}

				CharitableCampaignEmbedWizard.openPopup( s.formID );

			} );

			// Change Status.
			$builder.on( 'click', '#charitable-status-button', function( e ) {

				e.preventDefault();
				e.stopPropagation();

				if ( $( this ).hasClass( 'charitable-disabled' ) ) {
					return;
				}

				if ( $( this ).hasClass( 'active' ) ) {
					$( this ).parent().find('ul#charitable-status-dropdown').addClass('charitable-hidden');
					$( this ).removeClass( 'active' );
				} else {
					$( this ).parent().find('ul#charitable-status-dropdown').removeClass('charitable-hidden');
					$( this ).addClass( 'active' );
				}

			} );

			$builder.on( 'click', 'ul#charitable-status-dropdown a', function( e ) {

				e.preventDefault();
				e.stopPropagation();

				if ( $( '#charitable-status-button' ).hasClass( 'charitable-disabled' ) ) {
					return;
				}

				var newStatus = $( this ).data('status'),
					newStatusLabel = $( this ).data('status-label'),
					$statusDropdown = $( "ul#charitable-status-dropdown" );

				$statusDropdown.addClass('charitable-hidden');
				$( "#charitable-status-button" ).removeClass('active');

				// change status
				// s.formStatus = newStatus;
				$( '#charitable-status-button span.text').html( newStatusLabel );
				$( '#charitable-status-button').attr('data-status', newStatus );

				$statusDropdown.find('a').removeClass('charitable-hidden');
				$statusDropdown.find('a.switch-' + newStatus).addClass('charitable-hidden');
				$statusDropdown.find('a.' + newStatus).addClass('charitable-hidden');

				if ( newStatus === 'draft' ) {
					// $statusDropdown.find('a[class*="switch-"]').addClass('charitable-hidden');
				} else if ( newStatus === 'publish' ) {
					// $statusDropdown.find('a.draft').addClass('charitable-hidden');
					// $statusDropdown.find('a.pending').addClass('charitable-hidden');
					// $statusDropdown.find('a.review').addClass('charitable-hidden');
				}

				s.formStatus      = newStatus;
				s.formStatusLabel = newStatusLabel;

			} );

			$builder.on("click", function(e) {
				if ( $(e.target).is("#charitable-status-dropdown") === false) {
					$( "ul#charitable-status-dropdown" ).addClass('charitable-hidden');
					$( "#charitable-status-button" ).removeClass('active');
				}
			});

			// Save form.
			$builder.on( 'click', '#charitable-save', function( e ) {
				e.preventDefault();
				app.formSave( false );
			} );

			// Exit builder.
			$builder.on( 'click', '#charitable-exit', function( e ) {
				e.preventDefault();
				app.formExit();
			} );

			// After form save.
			$builder.on( 'charitableSaved', function( e, data ) { // eslint-disable-line

				$('#charitable_settings_title').attr('disabled', true);

				/**
				 * Remove `newform` parameter, if it's in URL, otherwise we can to get a "race condition".
				 * E.g. form settings will be updated before some provider connection is loaded.
				 */
				wpchar.removeQueryParam( 'newform' );
			} );

		},

		/**
		 * Update the campaign <form> ID that gets stored in a few key places (like hidden fields) - usually happens when a new form is saved/gets it's ID.
		 *
		 * @since 1.8.0
		 */
		updateFormID: function () {

			var data = {
				action: 'charitable_get_campaign_form_id',
				id    : s.formID,
				nonce : charitable_builder.nonce
			};

			return $.post( charitable_builder.ajax_url, data, function( response ) {

				if ( response.success ) {

					var campaignID = response.data.campaign_id,
						redirect = false;

					// update the campaign ID
					$('form#charitable-builder-form').attr('data-id', campaignID );
					$('form#charitable-builder-form input[name="id"]').val( campaignID );

					// update the field ID
					if ( response.data.field_id ) {
						elements.$nextFieldId.val( parseInt( response.data.field_id ) );
					}

					wpchar.savedState  = wpchar.getFormState( '#charitable-builder-form' );
					wpchar.initialSave = false;

					$builder.trigger( 'charitableSaved', response.data );

					// send the updated form of the campaign builder form to the debug window, along with the returned campaign id.
					app.updateDebugWindow( response.data, response.data.campaign_id );

					if ( true === redirect ) {
						window.location.href = charitable_builder.exit_url;
					}

				} else {

					app.formSaveError( response.data );

				}

			} ).fail( function( xhr, textStatus, e ) { // eslint-disable-line

				app.formSaveError();

			} ).always( function() {

			} );

		},

		/**
		 * Make sure critical hidden fields with critical data have the latest.
		 *
		 * @since 1.8.0
		 */
		updateFormHiddenFields: function () {

			// fields area.

			$('#charitable-panel-design .charitable-panel-content-wrap input[type="hidden"]').remove();

			$('.charitable-field-wrap .charitable-field, .charitable-tab-wrap .charitable-field').each(function(){

				var row_type     = $( this ).closest('.row').data('row-type'),
					row_id       = $( this ).closest('.row').data('row-id'),
					row_css      = $( this ).closest('.row').data('row-css').length > 0 ? $( this ).closest('.row').data('row-css') : 'no-css',
					column_id    = $( this ).closest('.column.charitable-field-column').data('column-id'),
					section_type = $( this ).closest('.section.charitable-field-section').data('section-type'),
					section_id   = $( this ).closest('.section.charitable-field-section').data('section-id'),
					field_id     = $( this ).data('field-id'),
					tab_id       = ( 'tabs' === section_type ) ? $( this ).closest('li.tab_content_item').data('tab-id') : false;

				if ( typeof row_id !== 'undefined' ) {

					if ( 'tabs' === section_type ) {

						elements.$formPreview.append('<input type="hidden" name="layout[row][row-type-' + row_type + '][' + row_id + '][' + row_css + '][column][' + column_id + '][section][section-type-' + section_type +'][' + section_id + '][tabs][' + tab_id + '][fields][' + field_id + ']" value="' + $(this).data('field-type') + '" />');

					} else {

						elements.$formPreview.append('<input type="hidden" name="layout[row][row-type-' + row_type + '][' + row_id + '][' + row_css + '][column][' + column_id + '][section][section-type-' + section_type +'][' + section_id + '][fields][' + field_id + ']" value="' + $(this).data('field-type') + '" />');

					}

				}

			});

		},

		/**
		 * This updates the field id hidden field that tells the form what the current/next field ID is
		 * which is useful if you are loading a template from the start and immedately want to add a field with a new incredmental ID (like ID: 3).
		 *
		 * @param {int} newValue New value.
		 *
		 * @since 1.8.0
		 */
		updateFormHiddenFieldID: function ( newValue = 0 ) {

			if ( 0 === newValue ) {
				// Let's do a count ourselves.
				var field_id = 0;
				elements.$formPreview.find('.charitable-field').each(function() {
					var value = parseFloat($(this).attr('data-field-id'));
					field_id = (value > field_id) ? value : field_id;
				});
				newValue = field_id;
			}

			elements.$nextFieldId.val( parseInt( newValue ) );

		},

		/**
		 * This updates the link on the preview button in the top header, especially if this is a campaign saved for the first time.
		 *
		 * @param {int} newValue New value.
		 *
		 * @since 1.8.0
		 */
		updatePreviewLink: function( previewLink = '') {

			elements.$previewButton.attr( 'href', previewLink );

		},

		/**
		 * This updates the donation amount settings in the settings area when a user updates a value in the donation amount field settings in the preview/design area to keep insync.
		 *
		 * @param {object} $element The element on focus.
		 * @param {int} theIndex Position of text box relative to the other text boxes.
		 *
		 * @since 1.8.0
		 */
		updateSuggestDonationsSettings: function( $element, theIndex ) {

			if ( ! $element.is("input") || app.getInputType( $element ) !== 'text' ) {
				return;
			}

			// Update settings.
			$('table#campaign_donation_amounts tbody').find('input[type="text"].campaign_suggested_donations').eq( theIndex ).val( $element.val() );

			// Update any donation amount setting fields on the left.
			$('.charitable-campaign-suggested-donations-mini tbody').find('input[type="text"].campaign_suggested_donations').eq( theIndex ).val( $element.val() );

			// Update preview area on the right.
			elements.$preview.find('.charitable-field-donate-amount li.charitable-preview-donation-amount').eq( ( theIndex / 2 ) - 1 ).find('span').first().html( $element.val() );
		},

		/**
		 * This updates the allow custom donation settings in the donation amount field in the preview area depending on the checkbox in the settings area.
		 *
		 * @param {object} $element The element on focus.
		 * @param {int} theIndex Position of text box relative to the other text boxes.
		 *
		 * @since 1.8.0
		 */
		updateAllowCustomDonationSettings: function ( isChecked ) {

			// // Update settings
			// $('#charitable-panel-field-settings-campaign_allow_custom_donations').prop( "checked", isChecked );

			// // Update ANY toggle in field settings.
			// $('.charitable-panel-field.charitable-campaign-builder-allow-custom-donations input[type="checkbox"]').prop( "checked", isChecked );

			// Hide or show field in preview depending on setting
			const $custom_preview_fields = elements.$preview.find('.charitable-preview-donation-options .custom-donation-amount');

			if ( isChecked ) {
				$custom_preview_fields.removeClass('charitable-hidden');
			} else {
				$custom_preview_fields.addClass('charitable-hidden');
			}

		},

		/**
		 * This updates the suggested donation amount settings in the settings area when a user updates a value in the donation amount field settings in the preview/design area to keep insync.
		 *
		 * @since 1.8.0
		 *
		 * @param {int} selectedValue The value of the selected radio button.
		 */
		updateSuggestedDonationAmountDefault: function( selectedValue = 0 ) {

			if ( selectedValue > 0 ) {

				$('input[type="radio"].campaign_suggested_donations').prop('checked', false );
				$('input[type="radio"][value=' + selectedValue + '].campaign_suggested_donations').prop('checked', true );

				elements.$preview.find('.charitable-field.charitable-field-donate-amount li').removeClass( 'selected' );
				elements.$preview.find('.charitable-preview-donation-amounts li:nth-child(' + ( selectedValue ) + ')').addClass('selected');

				if ( elements.$preview.find('.charitable-field.charitable-field-donate-amount input[type="radio"]').length > 0 ) {
					elements.$preview.find('.charitable-field.charitable-field-donate-amount input[type="radio"]:eq(' + ( selectedValue - 1 ) + ')').prop( 'checked', true );
				}


			} else {

				$('input:radio].campaign_suggested_donations').prop('checked', false );
			}

		},

		/**
		 * Gets input type of a field, see updateSuggestDonationsSettings().
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $element The element on focus.
		 */
		getInputType: function( $element ) {

			var thistest = $element;

			return thistest[0].tagName.toString().toLowerCase() === "input" ?
			$(thistest[0]).prop("type").toLowerCase() :
			thistest[0].tagName.toLowerCase();

		},

		/**
		 * This does an ajax call to update the "campaign creator" info, usually when the user selects a different valud in the user dropdown.
		 *
		 * @since 1.8.0
		 */
		updateCampaignCreatorInfo: function () {

			// Update the campaign creator info if the select dropdown is changed upon usually a form save.
			var data = {
				action      : 'charitable_update_campaign_creator',
				creator_id  : parseInt( $( 'select#charitable-panel-field-settings-campaign_campaign_creator_id' ).val() ),
				campaign_id : s.formID,
				nonce       : charitable_builder.nonce
			};

			return $.post( charitable_builder.ajax_url, data, function( response ) {

				if ( response.success ) {

					$('#campaign-creator .charitable-campaign-creator-avatar img').attr('src', response.data.avatar_url );
					$('#campaign-creator h3.creator-name').html( response.data.creator_name );
					$('#campaign-creator p.joined-on span').html( response.data.joined_on );
					$('#campaign-creator a.public-profile-link').attr('href', response.data.public_profile_link );
					$('#campaign-creator a.edit-profile-link').attr('href', response.data.edit_profile_link );


				} else {

					app.formSaveError( response.data );

				}

			} ).fail( function( xhr, textStatus, e ) { // eslint-disable-line

				app.formSaveError();

			} ).always( function() {


			} );

		},

		/**
		 * General check for form save errors.
		 *
		 * @since 1.8.1.12
		 *
		 */
		formSaveCheck: function() {

			if ( $('#charitable_settings_title').val().length === 0 ) {
				$.alert( {
					title   : charitable_builder.error_title,
					content : charitable_builder.error_no_title,
					icon    : 'fa fa-info-circle',
					type    : 'red',
					buttons : {
						confirm: {
							text: charitable_builder.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ]
						}
					}
				} );
				return false;
			}

		},

		/**
		 * Save form.
		 *
		 * @since 1.8.0
		 *
		 * @param {boolean} redirect Whether to redirect after save.
		 */
		formSave: function( redirect, preview = false, openNewTabURL = '', refreshAfterSave = false ) {

			var $saveBtn = elements.$saveButton,
				$icon    = $saveBtn.find( 'img.topbar_icon' ),
				$spinner = $saveBtn.find( 'i.charitable-loading-spinner' ),
				$label   = $saveBtn.find( 'span' ),
				currentPostStatus = s.formSavedStatus;

			// Saving a revision directly is not allowed. We need to notify the user that it will overwrite the current version.
			if ( $builder.hasClass( 'charitable-is-revision' ) && ! $builder.hasClass( 'charitable-revision-is-saving' ) ) {
				app.confirmSaveRevision();
				return;
			}

			// Check and see if there is a name for this campaign.
			if ( app.formSaveCheck() === false ) {
				return;
			}

			if ( typeof tinyMCE !== 'undefined' ) {
				tinyMCE.triggerSave();
			}

			var event = CharitableUtils.triggerEvent( $builder, 'charitableBeforeSave' );

			// Allow callbacks on `charitableBeforeSave` to cancel form submission by triggering `event.preventDefault()`.
			if ( event.isDefaultPrevented() ) {
				return;
			}

			// Un-disable certain fields so they can be included in the data submit.
			$( '#charitable_settings_title' ).removeAttr('disabled');

			// disable the save button.
			$saveBtn.prop( 'disabled', true );

			// don't show the spinner if this is a preview save.
			if ( preview === false ) {
				$label.text( charitable_builder.saving );
				$icon.addClass( 'charitable-hidden' );
				$spinner.removeClass( 'charitable-hidden' );
			}

			app.updateFormHiddenFields();

			var data = {
				action      : 'charitable_save_campaign',
				data        : JSON.stringify( $( '#charitable-builder-form' ).serializeArray() ),
				id          : s.formID,
				status      : s.formStatus,
				statusLabel : s.formStatusLabel,
				preview     : preview,
				nonce       : charitable_builder.nonce
			};

			return $.post( charitable_builder.ajax_url, data, function( response ) {

				if ( response.success ) {

					var campaignID = parseInt( response.data.campaign_id );

					if ( refreshAfterSave ) {
						// is the current url the same as the new url? if so, reload the page.
						if ( window.location.href === app.addCampaignIDToURL( campaignID ) ) {
							window.location.href = app.addCampaignIDToURL( campaignID );
							return;
						} else {
							// otherwise, redirect to the new url.
							window.location.href = app.addCampaignIDToURL( campaignID );
							return;
						}
					}

					// update the campaign ID.
					$('form#charitable-builder-form').attr('data-id', campaignID );
					$('form#charitable-builder-form input[name="id"]').val( campaignID );

					s.formID = campaignID; // need to update this var so adding fields for new forms can work.

					// update the field ID
					if ( response.data.field_id ) {
						elements.$nextFieldId.val( parseInt( response.data.field_id ) );
					}

					wpchar.savedState = wpchar.getFormState( '#charitable-builder-form' );
					wpchar.initialSave = false;

					$builder.trigger( 'charitableSaved', response.data );

					// send the updated form of the campaign builder form to the debug window, along with the returned campaign id.
					// app.updateDebugWindow( JSON.stringify( $( '#charitable-builder-form' ).serializeArray() ), response.data.campaign_id );
					app.updateDebugWindow( response.data, response.data.campaign_id );

					// If we got back a preview url, update the preview button.
					if ( typeof response.data.preview_url !== 'undefined' && response.data.preview_url.length > 0 ) {
						app.updatePreviewLink( response.data.preview_url );
					}

					// Don't set the campaign to "saved" if this is just a save for a preview.
					if ( preview === false ) {
						app.setCampaignSaved();
					}

					if ( true === redirect ) {
						window.location.href = charitable_builder.exit_url;
					}

					if ( history.pushState ) {
						var newUrl = app.addCampaignIDToURL( campaignID );
						window.history.pushState( {path:newUrl} ,'', newUrl );
					} else {
						window.location.href = app.addCampaignIDToURL( campaignID );
					}

					s.campaignID = campaignID;

					// If this campaign was draft and now published, open the congrats popup.
					if ( ( 'draft' === currentPostStatus || '' === currentPostStatus ) && 'publish' === response.data.post_status && response.data.permalink ) {
						CharitableCampaignCongratsWizard.openPopup( s.formID, response.data.permalink );
					}

					// If this campaign is published, make sure the 'view' button is clickable and has the correct URL, otherwise disable it.
					if ( 'publish' === response.data.post_status ) {
						elements.$viewCampaignButton.removeClass('charitable-disabled');
						elements.$viewCampaignButton.attr('href', response.data.permalink );
						$('a.charitable-admin-campaign-link').attr('href', response.data.permalink );
						$('a.charitable-admin-campaign-link.show-url').html( response.data.permalink );
					} else {
						elements.$viewCampaignButton.addClass('charitable-disabled');
					}

					// Depending if the campaign is published, make the embed button disabled or not.
					if ( 'publish' === response.data.post_status ) {
						elements.$embedButton.prop( 'disabled', false ).removeClass( 'charitable-disabled' );
					} else {
						elements.$embedButton.prop( 'disabled', true ).addClass( 'charitable-disabled' );
					}

					s.formSavedStatus      = response.data.post_status;
					s.formSavedStatusLabel = response.data.post_status_label;

					if ( openNewTabURL !== '' ) {
						window.open( openNewTabURL );
					}

					// Make sure the shortcode in the embed wizard is updated.
					var campaignEmedCode = '[campaign id=&quot;' + campaignID + '&quot;]';
					$( '#charitable-admin-campaign-embed-wizard-shortcode-wrap #charitable-admin-campaign-embed-wizard-shortcode' ).remove();
					$( '#charitable-admin-campaign-embed-wizard-shortcode-wrap').prepend("<input type=\"text\" id=\"charitable-admin-campaign-embed-wizard-shortcode\" class=\"charitable-admin-popup-shortcode\" value=\"" + campaignEmedCode + "\" />");
					$( '#charitable-admin-campaign-embed-wizard-shortcode' ).prop( 'disabled', true );

					// If this is a first time save, make sure to update the label re: campaign title.
					app.setCampaignTitleSet();

					return app.addCampaignIDToURL( campaignID );

				} else {

					app.formSaveError( response.data );

				}

			} ).fail( function( xhr, textStatus, e ) { // eslint-disable-line

				app.formSaveError();

			} ).always( function() {

				$label.text( charitable_builder.saved );
				setTimeout( function() {
					$label.text( charitable_builder.save );
				}, 2500 );
				$saveBtn.prop( 'disabled', false );
				$spinner.addClass( 'charitable-hidden' );
				$icon.removeClass( 'charitable-hidden' );

			} );
		},

		/**
		 * Form save error.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} error Error message.
		 */
		formSaveError: function( error ) {

			// Default error message.
			if ( wpchar.empty( error ) ) {
				error = charitable_builder.error_save_form;
			}

			// Display error in modal window.
			$.confirm( {
				title   : charitable_builder.heads_up,
				content : '<p>' + error + '</p><p>' + charitable_builder.error_contact_support + '</p>',
				icon    : 'fa fa-exclamation-circle',
				type    : 'orange',
				buttons : {
					confirm: {
						text: charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ]
					}
				}
			} );

		},

		/**
		 * Form save error.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} error Error message.
		 */
		formGenericError: function( error ) {

			// Default error message.
			if ( wpchar.empty( error ) ) {
				error = charitable_builder.something_went_wrong;
			}

			// Display error in modal window.
			$.confirm( {
				title   : charitable_builder.heads_up,
				content : '<p>' + error + '</p><p>' + charitable_builder.error_contact_support + '</p>',
				icon    : 'fa fa-exclamation-circle',
				type    : 'orange',
				buttons : {
					confirm: {
						text: charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ]
					}
				}
			} );
		},

		/**
		 * Form save error.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} error Error message.
		 */
		formGenericNotice: function( error ) {

			// Default error message.
			if ( wpchar.empty( error ) ) {
				error = charitable_builder.something_went_wrong;
			}

			// Display error in modal window.
			$.confirm( {
				title   : charitable_builder.heads_up,
				content : '<p>' + error + '</p>',
				icon    : 'fa fa-exclamation-circle',
				type    : 'orange',
				buttons : {
					confirm: {
						text: charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ]
					}
				}
			} );

		},

		/**
		 * Appends campaign ID to the URL in the campign builder.
		 *
		 * @since 1.8.0
		 *
		 * @param {int} campaignID ID of campaign.
		 */
		addCampaignIDToURL: function( campaignID ) {

			const 	currentURL        = window.location.href,
					urlHasQueryString = currentURL.includes("?");

			let updatedURL = false;

			if ( urlHasQueryString ) {
				// URL already has a query string.

				const 	queryString      = window.location.search,
						campaignIDExists = queryString.includes("campaign_id");

				if ( campaignIDExists ) {
					// URL already has a campaign ID parameter.
					// Remove any # and text after the # from the url.
					updatedURL = currentURL.split('#')[0];
					return updatedURL;
				} else {
					// Remove any # and text after the # from the url.
					updatedURL = currentURL.split('#')[0];
					// URL does not have a campaign ID parameter, so add it.
					return updatedURL + "&campaign_id=" + campaignID;
				}

			} else {

				// URL does not have a query string, so add one with the campaign ID parameter.
				return currentURL + "?campaign_id=" + campaignID;

			}

		},

		//--------------------------------------------------------------------//
		// Fields (Design) Panel
		//--------------------------------------------------------------------//

		/**
		 * Toggle fields tabs (Add Fields, Field Options.
		 *
		 * @since 1.8.0
		 *
		 * @param {string|integer} id Field Id or `add-fields` or `field-options`.
		 */
		fieldTabToggle: function( id ) {

			const event = CharitableUtils.triggerEvent( $builder, 'charitableFieldTabToggle', [ id ] );

			// Allow callbacks on `charitableFieldTabToggle` to cancel tab toggle by triggering `event.preventDefault()`.
			if ( event.isDefaultPrevented() ) {
				return false;
			}

			$( '.charitable-tab a' ).removeClass( 'active' );
			$( '.charitable-field, .charitable-preview-top-bar' ).removeClass( 'active' );

			if ( id === 'add-layout' ) {

				$( '#add-layout a' ).addClass( 'active' );
				$( '.charitable-field-options' ).hide();
				$( '.charitable-add-fields' ).show();

			} else if ( id === 'layout-options' ) {

				$( '#layout-options a' ).addClass( 'active' );
				$( '.charitable-add-fields' ).hide();
				$( '.charitable-field-options' ).show();

			} else {

				$( '#charitable-field-' + id ).addClass( 'active' );

				$( '.charitable-field-option' ).hide();
				$( '#charitable-field-option-' + id ).show();

				$( '.charitable-add-fields' ).hide();
				$( '.charitable-field-options' ).show();

				$builder.trigger( 'charitableFieldOptionTabToggle', [ id ] );
			}

			wpCookies.set( 'charitable_panel_content_section', id, 2592000 );
			wpCookies.set( 'charitable_panel_active_field_id', '', 2592000 ); // 1 month
			wpCookies.set( 'charitable_panel_design_layout_options_group', '', 2592000 ); // 1 month
			wpCookies.set( 'charitable_panel_tab_section_tab_id', '', 2592000 ); // 1 month

		},

		/**
		 * Exit form builder.
		 *
		 * @since 1.8.0
		 */
		formExit: function() {

			if ( app.formIsSaved() ) {

				window.location.href = charitable_builder.exit_url;

			} else {

				$.confirm( {
					title     : false,
					content   : '<p>' + charitable_builder.exit_confirm + '</p>',
					icon      : 'fa fa-exclamation-circle',
					type      : 'orange',
					closeIcon : true,
					buttons   : {
						confirm: {
							text: charitable_builder.save_exit,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
							action: function() {
								app.formSave( true );
							}
						},
						cancel: {
							text: charitable_builder.exit,
							keys: [ 'esc' ],
							action: function() {
								closeConfirmation = false;
								window.location.href = charitable_builder.exit_url;
							}
						}
					}
				} );
			}

		},

		/**
		 * Check the field max values and set buttons in "add layout" accordingly.
		 * This function can check things generally... or specifically if a type is passed.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} type Field type.
		 * @param {int} max Max number of fields.
		 */
		checkFieldMax: function( type = '', max = 99 ) {

			if ( type.length === 0 ) {

				// count how many types of fields there are and if they are under the max, removeClass 'charitable-disabled' from the button.
				var fieldCount = elements.$preview.find('.charitable-field-' + type).length;

				if ( fieldCount < max ) {
					elements.$panelDesign.find('button#charitable-add-fields-' + type).removeClass('charitable-disabled');
				}

			} else {

				elements.$preview.find( ".charitable-field[data-field-max!='']" ).each( function() {
					var max        = parseInt( $( this ).data('field-max') ),
						fieldType  = $( this ).data('field-type'),
						fieldCount = ( typeof fieldType === 'undefined' || fieldType.length === 0 ) ? 0 : elements.$preview.find('.charitable-field-' + fieldType).length;

					// count how many of the fields there are in the preview area and see if we need to deactivate the button.
					if ( ! isNaN( max ) && max > 0 && fieldCount >= max ) {
						// disable button.
						elements.$panelDesign.find('button#charitable-add-fields-' + fieldType).addClass('charitable-disabled');
					}
				});

			}

		},

		/**
		 * Check the field deny list to see if any fields should be disabled.
		 *
		 * @since 1.8.0
		 */
		checkFieldAllow: function () {

			wpchar.debug( 'checkFieldAllow' );

			// Assume all fields are enabled until we go through the deny list and find one instance where they would be disabled.
			$.each( s.denyList, function ( fieldPresent, fieldsNotAllowed ) {
				$.each( fieldsNotAllowed, function ( fieldsNotAllowedName, fieldsNotAllowedAmount ) { // eslint-disable-line
					app.enableAddFieldButton( fieldsNotAllowedName );
				});
			});

			$.each( s.denyList, function ( fieldPresent, fieldsNotAllowed ) {

				wpchar.debug( 'fieldPresent: ' + fieldPresent );

				// s.denyList = {
				// 	'donation-form': { 'donate-button' : 0, 'donation-form' : 0, 'donate-amount' : 0 },
				// 	'donate-button': { 'donation-form' : 0 },
				// 	'donate-amount': { 'donation-form' : 0 },
				// };

				// check and see if the fieldPresent is... present.
				if ( app.checkFieldIsPresent( fieldPresent ) ) {
					$.each( fieldsNotAllowed, function ( fieldsNotAllowedName, fieldsNotAllowedAmount ) {
						if ( fieldsNotAllowedAmount === 0 || app.getFieldAmount( fieldsNotAllowedName ) > parseInt( fieldsNotAllowedAmount ) ) {
							wpchar.debug ( fieldsNotAllowedName + ' is not allowed');
							// fieldsNotAllowedName shouldn't be allowed at all.
							app.disableAddFieldButton( fieldsNotAllowedName );
						}
					});
				}
			});

		},

		/**
		 * Check all recommended files on the left to see if they need to be checked or unchecked base don the fields in the preview area.
		 *
		 * @since 1.8.0
		 */
		checkAllRecommendedFields: function () {

			$.each( $('.charitable-add-fields-group-recommended button'), function ( ) {

				var type = $(this).data('field-type');

				app.checkRecommendedFields( type );

			});

		},

		/**
		 * Check to see if a single recommended field (passed) needs to be checked or unchecked base don the fields in the preview area.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} type Field type.
		 *
		 */
		checkRecommendedFields: function ( type = false ) {

			if ( ! type || type.length === 0 ) {
				return;
			}

			var numberOfFieldsByType = elements.$preview.find( ".charitable-field[data-field-type='" + type + "']" ).length,
				checkForRecommended  = $builder.find( '#charitable-add-fields-' + type ).parent().find('.charitable-check');

			if ( typeof checkForRecommended === 'undefined' || checkForRecommended.length === 0 ) {
				return;
			}

			if ( numberOfFieldsByType === 0 ) {
				checkForRecommended.removeClass('checked').addClass('unchecked');
			} else {
				checkForRecommended.removeClass('unchecked').addClass('checked');
			}

		},

		/**
		 * Disable add field button.
		 *
		 * @since 1.8.0
		 */
		disableAddFieldButton: function( fieldType ) {

			elements.$panelDesign.find('button#charitable-add-fields-' + fieldType).addClass('charitable-disabled');

		},

		/**
		 * Enable add field button.
		 *
		 * @since 1.8.0
		 */
		enableAddFieldButton: function( fieldType = '' ) {

			elements.$panelDesign.find('button#charitable-add-fields-' + fieldType).removeClass('charitable-disabled');

		},

		/**
		 * Get field amount.
		 *
		 * @since 1.8.0
		 */
		getFieldAmount: function ( fieldType = '' ) {

			if ( fieldType.length === 0 ) {
				return;
			}

			return elements.$preview.find('.charitable-field-' + fieldType).length;

		},

		/**
		 * Check field is present.
		 *
		 * @since 1.8.0
		 */
		checkFieldIsPresent: function ( fieldType = '' ) {

			if ( fieldType.length === 0 ) {
				return false;
			}

			if ( app.getFieldAmount( fieldType ) > 0 ) {
				return true;
			}

			return false;

		},

		/**
		 * Close confirmation setter.
		 *
		 * @since 1.8.0
		 *
		 * @param {boolean} confirm Close confirmation flag value.
		 */
		setCloseConfirmation: function( confirm ) {

			closeConfirmation = ! ! confirm;

		},

		/**
		 * Check current form state.
		 *
		 * @since 1.8.0
		 */
		formIsSaved: function() {

			if ( $( '#charitable-builder-form input#charitable-form-saved' ).val().length === 0 && ( typeof s.formSaved !== 'undefined' ) && s.formSaved.length === 0 ) { // the hidden field exists but contains no value, both of these fields SHOULD be in sync
				return true;
			} else {
				return false;
			}

		},

		/**
		 * Sets the campaign to a not saved mode.
		 *
		 * @since 1.8.1.12
		 */
		setCampaignTitleNotSet: function () {

			$('.charitable-edit-campaign-title-label').text( 'Name Your Campaign:' );

		},

		/**
		 * Sets the campaign to a not saved mode.
		 *
		 * @since 1.8.1.12
		 */
		setCampaignTitleSet: function () {

			$('.charitable-edit-campaign-title-label').text( 'Now Editing:' );

		},

		/**
		 * Sets the campaign to a not saved mode.
		 *
		 * @since 1.8.0
		 */
		setCampaignNotSaved: function( enablePreviewButton = false ) { // eslint-disable-line

			var the_time = Date.now();

			$( '#charitable-builder-form input#charitable-form-saved' ).val( the_time );
			s.formSaved  = the_time;

			// if the user has done something to the form, then we need to update the preview button to reflect that (enable the preview button).
			elements.$previewButton.removeClass('charitable-disabled');

		},

		/**
		 * Sets the campaign to a saved mode.
		 *
		 * @since 1.8.0
		 */
		setCampaignSaved: function() {

			$( '#charitable-builder-form input#charitable-form-saved' ).val( '' );
			s.formSaved = '';

			// For published campaigns disable preview button because there's nothing to currently preview untit the user makes another change...
			// ...otherwise if it's saved in draft, user should be able to click the preview buton because there's no other way to preview it.
			if ( typeof s.formStatus !== 'undefined' && s.formStatus === 'publish' ) {
				elements.$previewButton.addClass('charitable-disabled');
			} else {
				elements.$previewButton.removeClass('charitable-disabled');
			}

		},

		/**
		 * Bindings that prevent a user from entering anything but numbers, comma, and decimal point into a text field that represents a money value.
		 *
		 * @since 1.8.0
		 */
		bindUIMoneyTextFields: function () {

			// we will be monitoring:
			// #charitable-panel-field-settings-campaign_minimum_donation_amount
			// #charitable-panel-field-settings-campaign_goal
			$builder.on( 'keydown', 'input#charitable-panel-field-settings-campaign_minimum_donation_amount, input#charitable-panel-field-settings-campaign_goal', function( e ) { // eslint-disable-line
				var k = e.keyCode || e.which,
				// show keycode for decimal point
					ok = k == 190 || // period
					k == 188 || // comma
					k == 32 || // space
					k == 9 || // tab
					// k == 173 || // dash
					k == 8 || // backspaces
					( e.ctrlKey && k == 65 ) || // Ctrl + A
					( e.ctrlKey && k == 67 ) || // Ctrl + C
					( e.ctrlKey && k == 88 ) || // Ctrl + X
					( e.ctrlKey && k == 86 ) || // Ctrl + V
					( e.metaKey && k == 65 ) || // command + a
					( e.metaKey && k == 67 ) || // command + c
					( e.metaKey && k == 88 ) || // command + x
					( e.metaKey && k == 86 ) || // command + v
					( k >= 96 && k <= 105 ) || // allow numbers entered from a number pad
					( k == 110 || k == 190 ) || // period or decimal point
					( k >= 37 && k <= 40 ) || // allow arrow keys
					( k == 46 ) || // allow delete key
					( k >= 48 && k <= 57 ); // only 0-9 (ok SHIFT options)

				if ( !ok ) {
					e.preventDefault();
				}

			} );

			$builder.on( 'focusout', 'input#charitable-panel-field-settings-campaign_minimum_donation_amount, input#charitable-panel-field-settings-campaign_maximum_donation_amount, input#charitable-panel-field-settings-campaign_goal', function( e ) { // eslint-disable-line
				// add a decimal point followed by two zeros (.00) if the user clicks out of the input box and the money value is a whole number.
				var $this = $( this ),
					val   = $this.val(),
					decimal_separator = charitable_builder.currency_decimal_separator,
					thousands_separator = charitable_builder.currency_thousands_separator;

				// if undefined or empty, return.
				if ( typeof decimal_separator === 'undefined' || decimal_separator.length === 0 ) {
					return;
				}

				if ( typeof thousands_separator === 'undefined' || thousands_separator.length === 0 ) {
					return;
				}

				if ( val.length > 0 && val.indexOf( decimal_separator ) === -1 ) {
					$this.val( val + decimal_separator + '00' );
				}

			} );

		},

		/**
		 * Element bindings for general panel tasks.
		 *
		 * @since 1.8.0
		 */
		bindUISettingsRevealGroups: function() {

			$.each( elements.$settingsPanel.find( '.charitable-panel-fields-group.unfoldable' ), function() {
				// we found a reveal / foldable, see if we need to show it... find the id and see if the cookies exists.
				var $this           = $( this ),
					dataGroup       = $this.attr('data-group'),
					dataGroupCookie = wpCookies.get( 'charitable_fold_' + dataGroup );

				if ( dataGroupCookie === 'true' && ! $this.hasClass('opened') ) {
					// open the section
					$this.addClass('opened');
					$this.find('.charitable-panel-fields-group-inner').show();
				}

			});

			elements.$settingsPanel.on( 'click', '.charitable-track-cookie', function( e ) { // eslint-disable-line

				var $this           = $( this ),
					dataGroup       = $this.closest('.unfoldable').attr('data-group');

					if ( ! $this.closest('.unfoldable').hasClass('opened') ) {

						wpCookies.set( 'charitable_fold_' + dataGroup, true, 2592000 ); // 1 month.

					} else {

						wpCookies.remove( 'charitable_fold_' + dataGroup );
					}

			} );

		},

		/**
		 * Checks fields to see if OTHER fields can be shown/hidden. Logical conditional showings.
		 *
		 * @since 1.8.0
		 */
		checkFieldConditionals: function() {

			$.each( charitable_campaign_builder_field_conditionals, function( mainUIKey, mainUIValue) {

				var theUIElement = elements.$settingsPanel.find( mainUIKey ),
					changeArray  = mainUIValue;

				if ( theUIElement.is('input[type="checkbox"]') ) {

					elements.$settingsPanel.on( 'click', mainUIKey, function( e ) { // eslint-disable-line

						var thisUIElement   = $( this ), // eslint-disable-line
							isChecked       = $(this).is(':checked'),
							checkedFields   = changeArray['checked'],
							uncheckedFields = changeArray['unchecked'];

							if ( isChecked ) {

								$.each( checkedFields, function( checkedFieldKey, checkedFieldValue ) { // checkedFieldValue is the string selector for $
									elements.$settingsPanel.find( checkedFieldValue + '' ).removeClass('charitable-hidden');
								});

							} else {

								$.each( uncheckedFields, function( checkedFieldKey, checkedFieldValue ) { // checkedFieldValue is the string selector for $
									$.each( checkedFieldValue, function( hidingfieldKey, hidingfieldValue ) {
										elements.$settingsPanel.find( hidingfieldValue + '' ).addClass('charitable-hidden');
									});
								});

							}

					} );

				}

				if ( theUIElement.is('input[type="radio"]') ) {

					elements.$settingsPanel.on( 'click', theUIElement.closest('.charitable-panel-field-radio-options').find('input[type="radio"]'), function( e ) { // eslint-disable-line

						var thisUIElement   = $( theUIElement ),
							isChecked       = thisUIElement.is(':checked'),
							checkedFields   = changeArray['checked'],
							uncheckedFields = changeArray['unchecked'],
							abort           = false;

							if ( isChecked ) {

								$.each( checkedFields, function( checkedFieldKey, checkedFieldValue ) { // checkedFieldValue is the string selector for $
									if ( checkedFieldKey === 'if' ) {
										$.each( checkedFieldValue, function( checkedIfFieldKey, checkedIfFieldValue ) { // checkedFieldValue is the string selector for $
											if ( checkedIfFieldValue === 'checked' && false === elements.$settingsPanel.find( checkedIfFieldKey + '' ).is(':checked') ) {
												abort = true;
											}
										});
									}

									if ( checkedFieldKey === 'show' && abort === false ) {
										elements.$settingsPanel.find( checkedFieldValue + '' ).removeClass('charitable-hidden');
									} else if ( checkedFieldKey === 'show' && abort === true ) {
										elements.$settingsPanel.find( checkedFieldValue + '' ).addClass('charitable-hidden');
									}

								});

							} else {

								$.each( uncheckedFields, function( checkedFieldKey, checkedFieldValue ) { // checkedFieldValue is the string selector for $
									elements.$settingsPanel.find( checkedFieldValue + '' ).addClass('charitable-hidden');
								});

							}

					} );

				}

			});

		},

		/**
		 * Check if the builder opened in the popup (iframe).
		 *
		 * @since 1.8.0
		 *
		 * @returns {boolean} True if builder opened in the popup.
		 */
		isBuilderInPopup: function() {

			return window.self !== window.parent && window.self.frameElement.id === 'charitable-builder-iframe';
		},

		//--------------------------------------------------------------------//
		// General / global
		//--------------------------------------------------------------------//

		/**
		 * Element bindings for general and global items
		 *
		 * @since 1.8.0
		 */
		bindUIActionsGeneral: function() {

			// Toggle unfoldable group of fields.
			$builder.on( 'click', '.charitable-panel-fields-group.unfoldable .charitable-panel-fields-group-title', app.toggleUnfoldableGroup );

			$builder.on( 'click', '.go-to-settings-button', function( e ) {
				e.preventDefault();
				app.panelSwitch( 'settings' );
				var $this           = $( this ),
					section         = ( $this ).data('settings-section'),
					$panel          = $( '.charitable-panel-sidebar-content' ),
					$sectionButtons = $panel.find( '.charitable-panel-sidebar-section' ),
					$sectionButton  = $panel.find( '.charitable-panel-sidebar-section-' + section ),
					cookieName      = 'charitable_panel_sidebar_section';

				if ( ! $sectionButton.hasClass( 'active' ) ) {
					app.panelSectionSwitchTo( section, $panel, $sectionButtons, $sectionButton );
					wpCookies.set( cookieName, section, 2592000 ); // 1 month.
				}

			} );

			// Mobile notice primary button / close icon click.
			$builder.on( 'click', '#charitable-builder-mobile-notice .charitable-fullscreen-notice-button-primary, #charitable-builder-mobile-notice .close', function() {
				window.location.href = charitable_builder.exit_url;
			} );

			// Mobile notice secondary button click.
			$builder.on( 'click', '#charitable-builder-mobile-notice .charitable-fullscreen-notice-button-secondary', function() {
				window.location.href = wpchar.updateQueryString( 'force_desktop_view', 1, window.location.href );
			} );

		},


		/**
		 * Element bindings for general and global items
		 *
		 * @since 1.8.0
		 */
		initFeedbackForms: function( $element ) {

			var theForm         = $element.closest('.charitable-form'),
				theConfirmation = theForm.find('.charitable-feedback-form-interior-confirmation'),
				data = {
					name     : theForm.find('.charitable-feedback-form-name').val(),
					email    : theForm.find('.charitable-feedback-form-email').val(),
					feedback : theForm.find('.charitable-feedback-form-feedback').val(),
					type     : theForm.find('.charitable-feedback-form-type').val()
				};

			// required fields
			if ( '' === data.name || '' === data.email || '' === data.feedback  ) {

				$.alert( {
					title: false,
					content : charitable_builder.feedback_form_fields_required,
					icon: 'fa fa-info-circle',
					type: 'red',
					buttons: {
						confirm: {
							text     : charitable_builder.ok,
							btnClass : 'btn-confirm',
							keys     : [ 'esc' ],
							action: function() {
								// delete the campaign.
							}
						}
					}
				} );


			} else {

				$element.addClass('charitable-disabled');
				theForm.addClass('charitable-processing');
				theForm.find('.charitable-loading-spinner').removeClass('charitable-hidden');

				var ajaxData = {
					action   : 'charitable_campaign_builder_send_feedback_ajax',
					dataType : 'json',
					data     : data,
					nonce    : charitable_builder.nonce
				};

				$.post( charitable_builder.ajax_url, ajaxData, function( response ) {

					if ( response.success ) {

						$element.removeClass('charitable-disabled');
						theForm.removeClass('charitable-processing');
						theForm.find('.charitable-feedback-form-interior').addClass('charitable-hidden');
						theConfirmation.removeClass('charitable-hidden');
						theConfirmation.find('.charitable-form-confirmation').removeClass('charitable-hidden');

					}

				} ).fail( function( xhr, textStatus, e ) { // eslint-disable-line


				} ).always( function() {


				} );

			}


		},

		/**
		 * Toggle unfoldable group of fields.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} e Event object.
		 */
		toggleUnfoldableGroup: function( e ) {

			e.preventDefault();

			var $title     = $( e.target ),
				$group     = $title.closest( '.charitable-panel-fields-group' ),
				$inner     = $group.find( '.charitable-panel-fields-group-inner' ),
				cookieName = 'charitable_fields_group_' + $group.data( 'group' );

			if ( $group.hasClass( 'opened' ) ) {
				wpCookies.remove( cookieName );
				$inner.stop().slideUp( 150, function() {

					$group.removeClass( 'opened' );
				} );
			} else {
				wpCookies.set( cookieName, 'true', 2592000 ); // 1 month.
				$group.addClass( 'opened' );
				$inner.stop().slideDown( 150 );
			}
		},

		/**
		 * Update via ajax the debug window.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} data Data.
		 * @param {int} campaignID Camapign ID.
		 */
		updateDebugWindow: function( data, campaignID = 0 ) {

			var ajaxData = {
				action   : 'charitable_update_debug_window_ajax',
				dataType : 'json',
				data     : data,
				id       : campaignID,
				nonce    : charitable_builder.nonce
			};

			$.post( charitable_builder.ajax_url, ajaxData, function( response ) {

				if ( response.success ) {
					$('.charitable-debug').html( response.data );
				}

			} ).fail( function( xhr, textStatus, e ) { // eslint-disable-line


			} ).always( function() {

				// $label.text( text );
				// $saveBtn.prop( 'disabled', false );
				// $spinner.addClass( 'charitable-hidden' );
				// $icon.removeClass( 'charitable-hidden' );

			} );
		},

		//--------------------------------------------------------------------//
		// Alerts (notices).
		//--------------------------------------------------------------------//

		/**
		 * Click on the Dismiss notice button.
		 *
		 * @since 1.8.0
		 */
		dismissNotice: function() {

			$builder.on( 'click', '.charitable-alert-field-not-available .charitable-dismiss-button', function( e ) {

				e.preventDefault();

				var $button = $( this ),
					$alert = $button.closest( '.charitable-alert' ),
					fieldId = $button.data( 'field-id' );

				$alert.addClass( 'out' );
				setTimeout( function() {
					$alert.remove();
				}, 250 );

				if ( fieldId ) {
					$( '#charitable-field-option-' + fieldId ).remove();
				}
			} );

		},

		//--------------------------------------------------------------------//
		// Other functions.
		//--------------------------------------------------------------------//

		/**
		 * Init all the HTML textarea fields so that Quill, etc. runs in them.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $element Data.
		 * @param {boolean} minmum Show or not show min interface for quill.
		 */
		initHTMLEditorFields: function( $element, minmum = false ) {

			if ( $.inArray( $element.attr('id'), s.quilled) == -1 ) {

				var $container_id = '#' + $element.attr('id'),
					div_height    = 200,
					textarea_name = $element.data('textarea-name'); // ql-editor

				if ( minmum !== false ) {
					var toolbarOptions = ['bold', 'italic', 'underline', 'strike'];

					var quill = new Quill( $container_id, { // eslint-disable-line
						debug: 'info',
						theme: 'snow',
						modules: {
							toolbar: toolbarOptions
						}
					});

					$( $container_id ).css('height', div_height+'px');
					$( $container_id ).css('min-height', div_height+'px');

					quill.focus;

					s.quilled.push ( $element.attr('id') );

				} else {

					var quill = new Quill( $container_id, { // eslint-disable-line
						// debug: 'info',
						theme: 'snow'
					});

					quill.focus;

					s.quilled.push ( $element.attr('id') );

			}

				$( $container_id ).on( 'focus', '.ql-editor', function() { // eslint-disable-line

					var contents = $element.find('.ql-editor').html();

					if ( $element.closest('.charitable-panel-field-textarea').attr('data-special-type') === 'campaign_description' ) {

						$('#charitable-panel-field-settings-campaign_description .ql-editor').html( contents );
						elements.$preview.find('.charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text').html( '<div>' + contents + '</div>' );
						s.campaignDescription = contents;
					}

				});

				quill.on( 'text-change', function( delta, oldDelta, source ) { // eslint-disable-line

					var contents = $element.find('.ql-editor').html(),
						tab_id   = $element.closest('.charitable-group-row').data('tab-id'),
						field_id = $element.closest('.charitable-panel-field').data('field-id');

					app.setCampaignNotSaved(); // this is a trigger.

					// fill the form field as the user types
					$('input[name="' + textarea_name + '"]').val( contents );

					// if this is the CAMPAIGN DESCRIPTION in a DESCRIPTION FIELD we need to update any campaign description field in the settings and other design/preview areas too.
					if ( $element.closest('.charitable-panel-field-textarea').attr('data-special-type') === 'campaign_description' ) {

						if ( $.trim( app.removeTags( contents ) ) === '' ) {
							elements.$preview.find('.charitable-campaign-builder-no-description-preview').removeClass('charitable-hidden');
							elements.$preview.find('.charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text').html( '<div></div>' );
							// elements.$fieldOptions.find('.campaign-builder-htmleditor .ql-editor').html( '<div></div>' );
							s.campaignDescription = '';
						} else {
							// elements.$preview.find('.charitable-campaign-builder-no-description-preview').addClass('charitable-hidden');
							// elements.$preview.find('.charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text').html( app.removeTags( contents ) );
							elements.$preview.find('.charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text').html( '<div>' + contents + '</div>' );
							// elements.$fieldOptions.find('.charitable-panel-field').not('[data-field-id="' + field_id + '"]').find('.campaign-builder-htmleditor').pasteHTML([{ insert: contents }]);
							s.campaignDescription = contents;
						}

						$('#charitable-panel-field-settings-campaign_description .ql-editor').html( contents );
					}

					if ( $element.closest('.charitable-panel-field-textarea').attr('data-special-type') === 'campaign_overview' ) {
						if ( $.trim( app.removeTags( contents ) ) === '' ) {
							elements.$preview.find('.charitable-campaign-builder-no-overview-preview').removeClass('charitable-hidden')
							elements.$preview.find('.charitable-field-campaign-overview .charitable-campaign-builder-placeholder-preview-text').html( '<div></div>' );
							s.campaignDescription = '';
						} else {
							elements.$preview.find('.charitable-field-campaign-overview .charitable-campaign-builder-placeholder-preview-text').html( '<div>' + contents + '</div>' );
							s.campaignDescription = contents;
						}

						$('#charitable-panel-field-settings-campaign_overview .ql-editor').html( contents );
					}

					if ( $element.closest('.charitable-panel-field-textarea').attr('data-special-type') === 'organizer_content' ) {

						field_id = parseInt( $element.closest('.charitable-panel-field-textarea').data('field-id') );

						if ( $.trim( app.removeTags( contents ) ) === '' ) {
							elements.$preview.find('#charitable-field-' + field_id + ' .charitable-organizer-description').html( '<div></div>' );
						} else {
							elements.$preview.find('#charitable-field-' + field_id + ' .charitable-organizer-description').html( '<div>' + contents + '</div>' );
						}

					}

					if ( $element.closest('.charitable-panel-field-textarea').attr('data-special-type') === 'text' ) {

						field_id = parseInt( $element.closest('.charitable-panel-field-textarea').data('field-id') );

						if ( $.trim( app.removeTags( contents ) ) === '' ) {
							elements.$preview.find('#charitable-field-' + field_id + ' .charitable-campaign-builder-placeholder-preview-text').html( '<div></div>' );
						} else {
							elements.$preview.find('#charitable-field-' + field_id + ' .charitable-campaign-builder-placeholder-preview-text').html( '<div>' + contents + '</div>' );
						}

						// $('#charitable-panel-field-settings-campaign_overview .ql-editor').html( contents );
					}

					if ( minmum !== false ) {
						var div_height = parseInt( $element.find('.ql-editor').height() / 2 );
						$('#tab_' + tab_id + '_content .placeholder.big').css('min-height', div_height+'px');
					}

				});

			} else {

				/* already on the list */

			}

		},

		/**
		 * Init the suggested donations area in campaign settings.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $element Data.
		 */
		initSuggestedDonations: function ( $element ) {

			wpchar.debug( $element, 'initSuggestedDonations' );

			var $table             = $element.closest( '.charitable-campaign-suggested-donations' ),
				$add_row_button    = $element.find('[data-charitable-add-row]'),
				donation_type      = $add_row_button.data( 'charitable-add-row' ), // eslint-disable-line
				$delete_row_button = $element.find('.charitable-delete-row');

			$add_row_button.on( 'click', function( event ) {

				event.preventDefault();
				event.stopImmediatePropagation();

				var type = $( this ).data( 'charitable-add-row' );

				if ( 'suggested-amount' === type ) {
					app.add_suggested_amount_row( $( this ) );
				} else if ( 'suggested-recurring-amount' === type ) {
					app.add_suggested_amount_row( $( this ), 'recurring' );
				}

				app.initSuggestedDonations( $element );
				return false;
			});

			$delete_row_button.on( 'click', function( event ) {

				event.preventDefault();
				event.stopImmediatePropagation();

				var donation_type = $( this ).closest( '.charitable-campaign-suggested-donations' ).find('[data-charitable-add-row]').data( 'charitable-add-row' );

				if ( 'suggested-amount' === donation_type ) {
					app.delete_suggested_amount_row( $( this ) );
				} else if ( 'suggested-recurring-amount' === donation_type ) {
					app.delete_suggested_amount_row( $( this ), 'recurring' );
				}

				app.initSuggestedDonations( $element );
				return false;
			});

			$('.charitable-campaign-suggested-donations tbody').sortable({
				items: "tr:not(.to-copy)",
				handle: ".handle",
				stop: function( event, ui ) { // eslint-disable-line
					app.reindex_rows();
					// app.redrawDonationAmountsPreview( $table );
					$( '.charitable-campaign-suggested-donations-mini' ).each( function() {
						// app.initSuggestedDonationsMini( $( this ) );
						app.updateSuggestdDonationsMiniRowsFromSettings( $( this ) );
						app.redrawDonationAmountsPreview( $( this )  );
					});
				}

			});

			$table.on( 'click', 'th.default_amount-col a, a.charitable-clear-defaults', function() {
				$table.find('.default_amount-col input').prop('checked', false);
				$( this ).blur();
				return false;
			});

			// if there is any change in the radio button update with redrawDonationAmountsPreview.
			$table.on( 'change', 'input[type="radio"]', function() {
				app.redrawDonationAmountsPreview( $table );
			});

		},

		/**
		 * Just update the mini rows of a suggested donations table.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $element Data.
		 * @param {int} field_id Field ID.
		*/
		updateSuggestdDonationsMiniRowsFromSettings: function( $element, field_id = 0 ) {

			if ( 0 === field_id ) {
				field_id = $element.closest('.charitable-panel-field').data('field-id');
			}

			// Remove all rows in this table except the table with the class 'to-copy'.
			$element.find('tbody').children('tr').not('.to-copy').remove();

			// Get the values from the donation suggestions table in settings and update them for this table.
			$('#campaign_donation_amounts tbody').children('tr').not('.no-suggested-amounts').not('.to-copy').each( function( index ) { // eslint-disable-line
				var row_to_add           = '',
					updatedIndex         = index + 1,
					updatedDonationValue = $( this ).find('.amount-col input').val(),
					isChecked            = $( this ).find('.default_amount-col input').is(':checked') ? 'checked' : '';

				row_to_add = row_to_add + '<tr class="" data-index="' + updatedIndex + '">';
				row_to_add = row_to_add + '<td class="reorder-col"><span class="charitable-icon charitable-icon-donations-grab handle ui-sortable-handle"></span></td>';
				row_to_add = row_to_add + '<td class="default_amount-col"><input ' + isChecked + ' type="radio" class="campaign_suggested_donations" name="_fields][' + field_id + '][donation_amounts][campaign_suggested_donations_default][]" value="' + updatedIndex + '"></td>';
				row_to_add = row_to_add + '<td class="amount-col"><input autocomplete="false" type="text" class="campaign_suggested_donations" name="_fields][' + field_id + '][donation_amounts][' + updatedIndex + '][amount]" value="' + updatedDonationValue + '" placeholder="Amount"></td>';
				row_to_add = row_to_add + '<td class="description-col"><input type="text" class="campaign_suggested_donations" name="_fields][' + field_id + '][donation_amounts][' + updatedIndex + '][description]" value="This is a small donation." placeholder="Optional Description">';
				row_to_add = row_to_add + '</td>';
				row_to_add = row_to_add + '<td class="remove-col"><span class="dashicons-before dashicons-dismiss charitable-delete-row"></span></td>';
				row_to_add = row_to_add + '</tr>';

				$element.find('tbody').append( row_to_add );

			});

			return $element;

		},

		/**
		 * Just update the mini rows of a suggested donations table.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $element Data.
		 * @param {int} field_id Field ID.
		*/
		updateSuggestdDonationsRowsFromSettings: function( $miniTableTBody, $element ) {

			// Provide a default value.
			if ( typeof $element === 'undefined' || $element.length === 0 ) {
				$element = $('#campaign_donation_amounts');
			}

			// Remove all rows in this table except the table with the class 'to-copy'.
			$element.find('tbody').children('tr').not('.to-copy').remove();

			// Get the values from the donation suggestions table in settings and update them for this table.
			$miniTableTBody.children('tr').not('.no-suggested-amounts').not('.to-copy').each( function( index ) { // eslint-disable-line
				var row_to_add           = '',
					updatedIndex         = index + 1,
					updatedDonationValue = $( this ).find('.amount-col input').val(),
					isChecked            = $( this ).find('.default_amount-col input').is(':checked') ? 'checked' : '';

				row_to_add = row_to_add + '<tr class="" data-index="' + updatedIndex + '">';
				row_to_add = row_to_add + '<td class="reorder-col"><span class="charitable-icon charitable-icon-donations-grab handle ui-sortable-handle"></span></td>';
				row_to_add = row_to_add + '<td class="default_amount-col"><input ' + isChecked + ' type="radio" class="campaign_suggested_donations" name="settings][donation-options][donation_amounts][campaign_suggested_donations_default][]" value="' + updatedIndex + '">';
				row_to_add = row_to_add + '<td class="amount-col"><input autocomplete="off" type="text" class="campaign_suggested_donations" name="settings][donation-options][donation_amounts][' + updatedIndex + '][amount]" value="' + updatedDonationValue + '" placeholder="Amount">';
				row_to_add = row_to_add + '<td class="description-col"><input type="text" class="campaign_suggested_donations" name="settings][donation-options][donation_amounts][' + updatedIndex + '][description]" value="This is a small donation." placeholder="Optional Description">';
				row_to_add = row_to_add + '</td>';
				row_to_add = row_to_add + '<td class="remove-col"><span class="dashicons-before dashicons-dismiss charitable-delete-row"></span></td>';
				row_to_add = row_to_add + '</tr>';

				$element.find('tbody').append( row_to_add );

			});

			return $element;

		},

		/**
		 * Init the suggested donations area in campaign settings.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $element Data.
		 */
		initSuggestedDonationsMini: function ( $element ) {

			wpchar.debug( $element, 'initSuggestedDonationsMini' );
			wpchar.debug( 'initSuggestedDonationsMini' );

			var field_id = $element.closest('.charitable-panel-field').data('field-id');

			wpchar.debug( $element, 'initSuggestedDonationsMini' );

			app.updateSuggestdDonationsMiniRowsFromSettings( $element, field_id );

			wpchar.debug( $element, 'initSuggestedDonationsMini2' );

			app.redrawDonationAmountsPreview( $element );

			wpchar.debug( $element, 'initSuggestedDonationsMini3' );

			var $table = $element.closest( '.charitable-campaign-suggested-donations-mini' ),
				$add_row_button = $element.find('[data-charitable-add-row]');

			$add_row_button.on( 'click', function() {
				var type = $( this ).data( 'charitable-add-row' );

				if ( 'suggested-amount' === type ) {
					wpchar.debug ('type is suggesetd amount');
					wpchar.debug( $ ( this ) );
					app.add_suggested_amount_row( $( this ), 'mini' );
				}
				return false;
			});

			$('.charitable-campaign-suggested-donations-mini tbody').sortable({
				items: "tr:not(.to-copy)",
				handle: ".handle",
				stop: function( event, ui ) { // eslint-disable-line
					wpchar.debug( 'sortable test mini ');
					app.reindex_rows( 'mini' );
					app.updateSuggestdDonationsRowsFromSettings( $( this ) );
					app.redrawDonationAmountsPreview( $( this ).parent()  );
				}

			});

			$table.on( 'click', '.charitable-delete-row', function() {
				app.delete_suggested_amount_row( $( this ), 'mini' );
				return false;
			});

			$table.on( 'click', 'th.default_amount-col a', function() {
				$table.find('.default_amount-col input').prop('checked', false);
				$( this ).blur();
				return false;
			});

			// if there is any change in the radio button update with redrawDonationAmountsPreview.
			$table.on( 'change', 'input[type="radio"]', function() {
				app.redrawDonationAmountsPreview( $table );
				// If there is a change to the radio buttons, make sure to sync this change so that everyone ahs the same default donation.
				// Get the value of this radio button.
				var radioButtonValue = $(this).val();
				$builder.find( '.charitable-campaign-suggested-donations-table' ).find('input[type="radio"]').prop('checked', false );
				$builder.find( '.charitable-campaign-suggested-donations-table' ).each( function() {
					$(this).find('input[type="radio"][value="' + radioButtonValue + '"]').prop('checked', true );
				});
			});

		},

		/**
		 * Adds a row in the suggestion dations area.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $button Button, clicked.
		 * @param {string} type The type of table to reindex.
		 */
		add_suggested_amount_row: function( $button, type = '' ) { // eslint-disable-line

			wpchar.debug('add_suggested_amount_row');
			wpchar.debug('.charitable-campaign-suggested-donations' + type );

			const $donations_table = type === 'recurring' ? $builder.find( '.charitable-campaign-suggested-recurring-donations-table' ) : $builder.find( '.charitable-campaign-suggested-donations-table' );

			// Go through the donations table instances, in settings or not.
			$donations_table.each( function() {

				wpchar.debug( $( this ) );

				var $table = $(this).closest('table').find('tbody'),
					$clone = $table.find('tr.to-copy').clone().removeClass('to-copy hidden');

					// find the data-index in the tr in the clone and update it to the number of rows that already exist in the table.
					var newIndex = $table.find('tr:not(.to-copy)').length + 1;
					$clone.attr('data-index', newIndex );

					// Replace the [0] in the input name with the data-index number.
					var inputFieldName = $clone.find('.amount-col input[type="text"].campaign_suggested_donations').attr('name'),
						newInputFieldName = inputFieldName.replace('[0]', '[' + newIndex + ']'),
						inputFieldNameDescription = $clone.find('.description-col input[type="text"].campaign_suggested_donations').attr('name'),
						newInputFieldNameDescription = inputFieldNameDescription.replace('[0]', '[' + newIndex + ']');
					wpchar.debug( $clone.find('.amount-col input[type="text"].campaign_suggested_donations') );
					wpchar.debug( $clone.find('.amount-col input[type="text"].campaign_suggested_donations').attr('name') );
					$clone.find('.amount-col input[type="text"].campaign_suggested_donations').attr('name', newInputFieldName );
					$clone.find('.description-col input[type="text"].campaign_suggested_donations').attr('name', newInputFieldNameDescription );
					$clone.find('.default_amount-col input[type="radio"]').val( newIndex );

					wpchar.debug( $table, 'table' );
					wpchar.debug( $clone, 'clone' );

					$table.find( '.no-suggested-amounts' ).hide();
					$table.append( $clone );
					$clone.on( 'click', '.charitable-delete-row', function() {
						app.delete_suggested_amount_row( $(this), type );
						return false;
					});

			});

			app.redrawDonationAmountsPreview( $builder.find( '.charitable-campaign-suggested-donations-table' ).first() );

			app.reindex_rows( type );
			if ( type === '' ) {
				app.toggle_custom_donations_checkbox();
			}

		},

		/**
		 * Removes a row in the suggestion dontions area.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $button Button, clicked.
		 * @param {string} type The type of table to reindex.
		 */
		delete_suggested_amount_row: function( $button, type = '' ) {

			wpchar.debug('delete_suggested_amount_row');
			wpchar.debug( $button );
			wpchar.debug( type );

			var $row_to_delete      = $button.closest( 'tr '),
				row_to_delete_index = parseInt( $row_to_delete.attr('data-index') );

			wpchar.debug( $row_to_delete );
			wpchar.debug( row_to_delete_index, 'row_to_delete_index' );

			const $donations_table      = type === 'recurring' ? $builder.find( '.charitable-campaign-suggested-recurring-donations-table' ) : $builder.find( '.charitable-campaign-suggested-donations-table' ),
				  donations_table_class = type === 'recurring' ? '.charitable-campaign-suggested-recurring-donations-table' : '.charitable-campaign-suggested-donations-table';

			if ( row_to_delete_index > 0 ) {

				wpchar.debug( $donations_table.find(' tbody tr[data-index="' + row_to_delete_index + '"]') );
				wpchar.debug( donations_table_class + ' tbody tr[data-index="' + row_to_delete_index + '"]' );

				// remove the row in all instances of the donations table
				$donations_table.find(' tbody tr[data-index="' + row_to_delete_index + '"]').remove();

			}

			app.redrawDonationAmountsPreview( $builder.find( '.charitable-campaign-suggested-donations-table' ).first() );

			var $table = $button.closest('table').find('tbody');

			if ( $table.find( 'tr:not(.to-copy)' ).length == 1 ){
				$table.find( '.no-suggested-amounts' ).removeClass('hidden').show();
			}

			// Reindex the rows.
			app.reindex_rows( type );
			if ( type === '' ) {
				app.reindex_rows( 'mini' );
			} else if ( type === 'mini' ) {
				app.reindex_rows();
			}
			if ( type === '' ) {
				app.toggle_custom_donations_checkbox();
			}

		},

		/**
		 * Reindexes the rows in the suggestion dontions area.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} type The type of table to reindex.
		 *
		 */
		reindex_rows: function( type = '' ) {

			wpchar.debug('reindex rows with type: ' + type );

			if ( type !== '' ) {
				type = '-' + type;
			}

			$('.charitable-campaign-suggested-donations' + type + ' tbody').each(function(){
				$(this).children('tr').not('.no-suggested-amounts .to-copy').each( function( index ) {
					$(this).find('input[type="radio"]').val( index );
					$(this).attr('data-index', index );
					$(this).find('input').each(function(i) { // eslint-disable-line
						this.name = this.name.replace(/(\[\d\])/, '[' + index + ']');
					});
				});
			});

		},

		/**
		 * Redraws the donation amounts preview based on the values in the suggested donations table.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} targetTable The table to redraw the preview for.
		 *
		 */
		redrawDonationAmountsPreview: function( targetTable ) {

			// for each ul element called 'charitable-preview-donation-amounts'...
			elements.$preview.find('ul.charitable-preview-donation-amounts').each( function( index ) { // eslint-disable-line
				var $theDontionAmountsList = $( this );

				// remove all the li.
				$theDontionAmountsList.find('li').remove();
				// look at targetTable - a suggested donations table - and read each row and repopulate the amounts unordered list.
				$( targetTable ).find('tbody').children('tr').not('.no-suggested-amounts').not('.to-copy').not('hidden').each( function( index ) { // eslint-disable-line
					var theInputTextValue = $( this ).find('input[type="text"].campaign_suggested_donations').first().val();

					// If the radio button is checked, add the class 'selected' to the li.
					var selected = '';
					wpchar.debug($( this ).find('input[type="radio"]'));
					if ( $( this ).find('input[type="radio"]').is(':checked') ) {
						selected = ' selected';
					}
					$theDontionAmountsList.append( '<li class="charitable-preview-donation-amount suggested-donation-amount' + selected + '"><label><input type="radio" name="donation_amount" value="' + index + '"><span class="amount">' + theInputTextValue + '</span></label></li>' );
				});

				// Add the custom donation HTML if the checkbox is checked.
				if ( $( '#charitable-panel-field-settings-campaign_allow_custom_donations').is(':checked') ) {
					$theDontionAmountsList.append( '<li class="charitable-preview-donation-amount custom-donation-amount "><span class="custom-donation-amount-wrapper"><label><input type="radio" name="donation_amount" value="custom"><span class="description">Custom amount</span></label><input type="text" disabled="&quot;true&quot;" class="custom-donation-input" name="custom_donation_amount" placeholder="Custom Donation Amount" value=""></span></li>' );
				}

			});

		},

		/**
		 * Add handler for the toggle that allows/disallows custom donations.
		 *
		 * @since 1.8.0
		 *
		 */
		toggle_custom_donations_checkbox: function() {
			var $custom = $('#campaign_allow_custom_donations'),
				$suggestions = $('.charitable-campaign-suggested-donations tbody tr:not(.to-copy)'),
				has_suggestions = $suggestions.length > 1 || false === $suggestions.first().hasClass('no-suggested-amounts');

			$custom.prop( 'disabled', ! has_suggestions );

			if ( ! has_suggestions ) {
				$custom.prop( 'checked', true );
			}
		},

		/**
		 * Init the tag field in campaign settings.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $element Data.
		 */
		initTagField: function( $element ) {

			$element.select2();

		},

		/**
		 * Init color picker fields in campaign settings.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $element Data.
		 */
		initColorPicker: function() {

			Coloris({ // eslint-disable-line
				el: '.coloris'
			});

			Coloris.setInstance('.instance2.primary', { // eslint-disable-line
				defaultColor: s.primaryThemeColor,
				onChange : function ( color ) {
					if ( '' === color ) {
						$('input[name="layout__advanced__theme_color_primary"]').val( s.primaryThemeColorBase );
						s.primaryThemeColor = s.primaryThemeColorBase;
						document.querySelector('input[name="layout__advanced__theme_color_primary"]').dispatchEvent(new Event('input', { bubbles: true }));
					} else {
						s.primaryThemeColor = color;
					}
					app.updateThemeCSS( 'primary', s.templateID, s.primaryThemeColor, s.secondaryThemeColor, s.tertiaryThemeColor, s.buttonThemeColor, true );
				},
				theme: 'polaroid',
				themeMode: 'light',
				alpha: false,
				formatToggle: true,
				closeButton: true,
				clearButton: true,
				clearLabel: 'Reset',
				swatches: [
				'#067bc2',
				'#84bcda',
				'#80e377',
				'#ecc30b',
				'#f37748',
				'#d56062'
				]
			});

			Coloris.setInstance('.instance2.secondary', { // eslint-disable-line
				defaultColor: s.secondaryThemeColor,
				onChange : function ( color ) {
					if ( '' === color ) {
						$('input[name="layout__advanced__theme_color_secondary"]').val( s.secondaryThemeColorBase );
						s.secondaryThemeColor = s.secondaryThemeColorBase;
						app.updateThemeCSS( 'secondary', s.templateID, s.primaryThemeColor, s.secondaryThemeColor, s.tertiaryThemeColor, s.buttonThemeColor, true );
						document.querySelector('input[name="layout__advanced__theme_color_secondary"]').dispatchEvent(new Event('input', { bubbles: true }));
					} else {
						s.secondaryThemeColor = color;
						app.updateThemeCSS( 'secondary', s.templateID, s.primaryThemeColor, s.secondaryThemeColor, s.tertiaryThemeColor, s.buttonThemeColor, true );
					}
				},
				theme: 'polaroid',
				themeMode: 'light',
				alpha: false,
				formatToggle: true,
				closeButton: true,
				clearButton: true,
				clearLabel: 'Reset',
				swatches: [
				'#067bc2',
				'#84bcda',
				'#80e377',
				'#ecc30b',
				'#f37748',
				'#d56062'
				]
			});

			Coloris.setInstance('.instance2.tertiary', { // eslint-disable-line
				defaultColor: s.tertiaryThemeColor,
				onChange : function ( color ) {
					if ( '' === color ) {
						$('input[name="layout__advanced__theme_color_tertiary"]').val( s.tertiaryThemeColorBase );
						s.tertiaryThemeColor = s.tertiaryThemeColorBase;
						app.updateThemeCSS( 'teritary', s.templateID, s.primaryThemeColor, s.secondaryThemeColor, s.tertiaryThemeColor, s.buttonThemeColor, true );
						document.querySelector('input[name="layout__advanced__theme_color_tertiary"]').dispatchEvent(new Event('input', { bubbles: true }));
					} else {
						s.tertiaryThemeColor = color;
						app.updateThemeCSS( 'teritary', s.templateID, s.primaryThemeColor, s.secondaryThemeColor, s.tertiaryThemeColor, s.buttonThemeColor, true );
					}
				},
				theme: 'polaroid',
				themeMode: 'light',
				alpha: false,
				formatToggle: true,
				closeButton: true,
				clearButton: true,
				clearLabel: 'Reset',
				swatches: [
				'#067bc2',
				'#84bcda',
				'#80e377',
				'#ecc30b',
				'#f37748',
				'#d56062'
				]
			});

			Coloris.setInstance('.instance2.button-color', { // eslint-disable-line
				defaultColor: s.buttonThemeColor,
				onChange : function ( color ) {
					if ( '' === color ) {
						$('input[name="layout__advanced__theme_color_button"]').val( s.buttonThemeColorBase );
						s.buttonThemeColor = s.buttonThemeColorBase;
						app.updateThemeCSS( 'button', s.templateID, s.primaryThemeColor, s.secondaryThemeColor, s.tertiaryThemeColor, s.buttonThemeColor, true );
						document.querySelector('input[name="layout__advanced__theme_color_button"]').dispatchEvent(new Event('input', { bubbles: true }));
					} else {
						s.buttonThemeColor = color;
						app.updateThemeCSS( 'button', s.templateID, s.primaryThemeColor, s.secondaryThemeColor, s.tertiaryThemeColor, s.buttonThemeColor, true );
					}
				},
				theme: 'polaroid',
				themeMode: 'light',
				alpha: false,
				formatToggle: true,
				closeButton: true,
				clearButton: true,
				clearLabel: 'Reset',
				swatches: [
				'#067bc2',
				'#84bcda',
				'#80e377',
				'#ecc30b',
				'#f37748',
				'#d56062'
				]
			});

			/* make it so clicking the badge triggers the popup */
			elements.$fieldOptions.on( 'click', '.clr-field button', function( e ) {
				e.preventDefault();
				$( this ).parent().find('input[type="text"]').trigger('click');
			});

		},

		/**
		 * Init the date pickers in campaign settings.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} $element Data.
		 */
		initDatePicker: function( $element ) {

			var $the_element = $element,
				options = {
					dateFormat 	: $the_element.data('format') || 'MM d, yy',
					minDate 	: $the_element.data('min-date') || '',
					beforeShow	: function( input, inst ) { // eslint-disable-line
						setTimeout( function() {
							$('.ui-datepicker').css('z-index', 99999999999999);
						}, 0);
					}
				};

			if ( $.isFunction( $the_element.datepicker ) ) {

			$the_element.datepicker( options );

				if ( $the_element.data('date') ) {
					$the_element.datepicker( 'setDate', this.$el.data('date') );
				}

				if ( $the_element.data('min-date') ) {
					$the_element.datepicker( 'option', 'minDate', this.$el.data('min-date') );
				}

			}
		},

		/**
		 * Trim long form titles.
		 *
		 * @since 1.8.0
		 */
		trimFormTitle: function() {

			var $title = $( '.charitable-center-form-name' );

			if ( $title.text().length > 38 ) {
				var shortTitle = $title.text().trim().substring( 0, 38 ).split( ' ' ).slice( 0, -1 ).join( ' ' ) + '...';
				$title.text( shortTitle );
			}

		},

		/**
		 * Hotkeys:
		 * Ctrl+t - Templates.
		 *
		 * @since 1.8.0
		 * @since 1.8.0.4 Added ctrl+x to exit builder.
		 */
		builderHotkeys: function() {

			hotkeys('esc,ctrl+1,ctrl+2,ctrl+3,ctrl+4,ctrl+5,ctrl+s,ctrl+p,ctrl+x,ctrl+v', function ( event, handler ){ // eslint-disable-line
				switch (handler.key) {
					case 'esc':
						// this is a non-standard modal popup, so we want to close it manually with the esc key.
						if ( $('.charitable-builder-modal.charitable-builder-modal-template-preview').hasClass('active') ) {
							$('.charitable-template-list-container').removeClass('disabled');
							$('#charitable-builder-underlay').remove();
							$('.charitable-builder-modal.charitable-builder-modal-template-preview').removeClass('active');
						}
					break;
					case 'ctrl+1':
						$( elements.$templateButton, $builder ).trigger( 'click' );
					break;
					case 'ctrl+2':
						$( elements.$designButton, $builder ).trigger( 'click' );
					break;
					case 'ctrl+3':
						$( elements.$settingsButton, $builder ).trigger( 'click' );
					break;
					case 'ctrl+4':
						$( elements.$marketingButton, $builder ).trigger( 'click' );
					break;
					case 'ctrl+5':
						$( elements.$paymentButton, $builder ).trigger( 'click' );
					break;
					case 'ctrl+s': // Save Campaign
						$( elements.$saveButton, $builder ).trigger( 'click' );
					break;
					case 'ctrl+p': // Preview
						$( elements.$previewButton, $builder ).trigger( 'click' );
					break;
					case 'ctrl+x':
						$( elements.$exitButton, $builder ).trigger( 'click' );
					break;
					case 'ctrl+v':
						$( elements.$viewCampaignButton, $builder ).trigger( 'click' );
					break;
				}
			});

		},

		/* LITE OR UPDATE/INSTALL SPECIFIC */

		/**
		 * Registers click events that should open upgrade modal.
		 *
		 * @since 1.8.0
		 */
		openModalButtonClick: function() {

			$( document )
				.on( 'click', '.charitable-not-available:not(.charitable-add-fields-button)', app.openModalButtonHandler )
				.on( 'mousedown', '.charitable-not-available.charitable-add-fields-button', app.openModalButtonHandler );

			$( document )
				.on( 'click', '.charitable-disabled-modal:not(.charitable-add-fields-button)', app.openModalWarningModal )
				.on( 'mousedown', '.charitable-add-fields-button.charitable-disabled-modal', app.openModalWarningModal );

			$( document )
				.on( 'click', '.charitable-disabled-same_page:not(.charitable-add-fields-button)', app.openModalWarningModal )
				.on( 'mousedown', '.charitable-add-fields-button.charitable-disabled-same_page', app.openModalWarningModal );

			$( document )
				.on( 'click', '.charitable-not-installed:not(.charitable-add-fields-button)', app.openModalButtonHandlerInstall )
				.on( 'mousedown', '.charitable-not-installed.charitable-add-fields-button', app.openModalButtonHandlerInstall );

			$( document )
				.on( 'click', '.charitable-not-activated:not(.charitable-add-fields-button)', app.openModalButtonHandlerActivate )
				.on( 'mousedown', '.charitable-not-activated.charitable-add-fields-button', app.openModalButtonHandlerActivate );

			$( document )
				.on( 'click', '.charitable-addon-file-missing:not(.charitable-add-fields-button)', app.openModalButtonHandlerInstall )
				.on( 'mousedown', '.charitable-addon-file-missing.charitable-add-fields-button', app.openModalButtonHandlerInstall );

			$( document )
				.on( 'click', '.charitable-not-available:not(.charitable-setting-panel-upgrade-to-pro)', app.openModalButtonHandler )
				.on( 'mousedown', '.charitable-not-available.charitable-setting-panel-upgrade-to-pro', app.openModalButtonHandler );

			$( document )
				.on( 'click', '.charitable-need-upgrade:not(.charitable-setting-panel-upgrade-to-pro)', app.openModalButtonHandler )
				.on( 'mousedown', '.charitable-need-upgrade.charitable-setting-panel-upgrade-to-pro', app.openModalButtonHandler );

			$( document )
				.on( 'click', '.charitable-installed-refresh:not(.charitable-add-fields-button)', app.openModalButtonHandlerActivatedRefresh )
				.on( 'mousedown', '.charitable-installed-refresh.charitable-add-fields-button', app.openModalButtonHandlerActivatedRefresh );


			/* the below represent buttons inside the preview area in the design tab */

			$( document )
				.on( 'click', 'a.button-link.charitable-not-activated', app.openModalButtonHandlerActivate )
				.on( 'mousedown', 'a.button-link.charitable-not-activated', app.openModalButtonHandlerActivate );

			$( document )
				.on( 'click', 'a.button-link.charitable-not-installed', app.openModalButtonHandlerInstall )
				.on( 'mousedown', 'a.button-link.charitable-not-installed', app.openModalButtonHandlerInstall );

			/* the below represent buttons inside the marketing area */

			$( document )
				.on( 'click', 'a.button-link.charitable-not-activated-button', app.activateFromButton )
				.on( 'mousedown', 'a.button-link.charitable-not-activated-button', app.activateFromButton );

			$( document )
				.on( 'click', 'a.button-link.charitable-not-installed-button', app.installFromButton )
				.on( 'mousedown', 'a.button-link.charitable-not-installed-button', app.installFromButton );

		},

		/**
		 * Open the upgrade modal to activate an extension/plugin from a sepcific button click.
		 *
		 * @since 1.8.1.12
		 *
		 * @param {object} e Event.
		 *
		 * @return {void}
		 *
		*/
		activateFromButton: function( e ) {
			e.preventDefault();

			const 	$button      = $( this ),
					plugin_url   = $( this ).data( 'plugin-url' ),
					plugin_name  = $( this ).data( 'name' ),
					settings_url = $( this ).data( 'settings-url' ),
					plugin_slug  = $( this ).data( 'plugin-slug' ),
					enable_url   = $( this ).data( 'enable-url' ).length > 0 ? $( this ).data( 'enable-url' ) : '';

			// check to makes sure all the variables above are set.
			if ( ! plugin_url || ! plugin_name ) {
				return;
			}

			app.installFromButtonAjax( plugin_url, plugin_name, settings_url, plugin_slug, enable_url, 'activate', 'addon', $button );
		},

		/**
		 * Open the upgrade modal to install an extension/plugin from a sepcific button click.
		 *
		 * @since 1.8.1.12
		 *
		 * @param {object} e Event.
		 *
		 * @return {void}
		 *
		*/
		installFromButton: function( e ) {
			e.preventDefault();

			const 	$button      = $( this ),
					plugin_url   = $( this ).data( 'plugin-url' ),
					plugin_name  = $( this ).data( 'name' ),
					settings_url = $( this ).data( 'settings-url' ),
					plugin_slug  = $( this ).data( 'plugin-slug' ),
					enable_url   = $( this ).data( 'enable-url' ).length > 0 ? $( this ).data( 'enable-url' ) : '';

			// check to makes sure all the variables above are set.
			if ( ! plugin_url || ! plugin_name ) {
				return;
			}

			app.installFromButtonAjax( plugin_url, plugin_name, settings_url, plugin_slug, enable_url, 'install', 'addon', $button );
		},


		/**
		 * Change plugin/addon state.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} plugin_url Plugin URL.
		 * @param {string} plugin_name Plugin name.
		 */
		installFromButtonAjax: function( plugin_url, plugin_name, settings_url, plugin_slug, enable_url, state, pluginType, $button ) {

			wpchar.debug ( 'setAddonState' );
			wpchar.debug ( plugin_url );
			wpchar.debug ( plugin_name );
			wpchar.debug ( state );

			var actions = {
					'activate': 'charitable_activate_addon',
					'install': 'charitable_install_addon',
					'deactivate': 'charitable_deactivate_addon'
				},
				action = actions[ state ];

			if ( ! action ) {
				return;
			}

			var plugin_ajax = plugin_url;

			// update the text of the button.
			if ( 'install' === state ) {
				if ( enable_url.length > 0 ) { // this is a payment extension
					$button.text( 'Installing and activating gateway...' );
				} else {
					$button.text( 'Installing and activating...' );
				}
				$button.removeClass( 'charitable-not-installed-button' );
				$button.addClass( 'charitable-view-settings-button' );
			} else if ( 'activate' === state ) {
				if ( enable_url.length > 0 ) { // this is a payment extension
					$button.text( 'Activating gateway...' );
				} else {
					$button.text( 'Activating...' );
				}
				$button.removeClass( 'charitable-not-activated-button' );
				$button.addClass( 'charitable-view-settings-button' );
				plugin_ajax = plugin_slug;
			}

			// Override the plugin var in the ajax if this is activation.
			var data = {
				action: action,
				nonce: charitable_admin.nonce, // eslint-disable-line
				plugin: plugin_ajax,
				type: pluginType
			};

			wpchar.debug ( data );

			$.post( charitable_admin.ajax_url, data, function( res ) { // eslint-disable-line

				// update the text of the button.
				if ( 'install' === state ) {
					// all buttons that have a data attribute of data-plugin-slug that matches "test".
					$( 'a.button-link[data-plugin-slug="' + plugin_slug + '"]' ).each( function() {
						$( this ).text( 'View Settings' );
						$( this ).removeClass( 'charitable-not-installed-button' );
						$( this ).addClass( 'charitable-view-settings-button' );
						if ( enable_url.length > 0 ) { // this is a payment extension
							$( this ).attr( 'href', enable_url );
						} else {
							$( this ).attr( 'href', settings_url );
						}
						$( this ).attr( 'target', '_blank' );
					});

					$( 'section.header-content h2' ).addClass('charitable-hidden');
					$( 'section.header-content h2.charitable-header-content-activated' ).removeClass('charitable-hidden');


				} else if ( 'activate' === state ) {
					// all buttons that have a data attribute of data-plugin-slug that matches "test".
					$( 'a.button-link[data-plugin-slug="' + plugin_slug + '"]' ).each( function() {
						$( this ).text( 'View Settings' );
						$( this ).removeClass( 'charitable-not-installed-button' );
						$( this ).addClass( 'charitable-view-settings-button' );
						if ( enable_url.length > 0 ) { // this is a payment extension
							$( this ).attr( 'href', enable_url );
						} else {
							$( this ).attr( 'href', settings_url );
						}
						$( this ).attr( 'target', '_blank' );
					});

					$( 'section.header-content h2' ).addClass('charitable-hidden');
					$( 'section.header-content h2.charitable-header-content-activated' ).removeClass('charitable-hidden');
				}

			} ).fail( function( xhr ) {

				wpchar.debug( xhr.responseText );

			} );
		},

		/**
		 * Open "you're using modal" modal handler.
		 *
		 * @since 1.8.0
		 *
		 * @param {Event} event Event.
		 */
		openModalWarningModal: function( event ) {

			const $this = $( this );

			event.preventDefault();
			event.stopImmediatePropagation();

			let name   = $this.data( 'name' ),
				icon   = '',
				reason = '';

			if ( $this.hasClass( 'charitable-add-fields-button' ) ) {
				name  = $this.text();
				name += name.indexOf( charitable_builder.field ) < 0 ? ' ' + charitable_builder.field : '';
				// if the button has an attribute of data-icon, we want to use that icon instead of the default one.
				if ( $this.data('field-icon') ) {
					icon = $this.data('field-icon');
				}
			}

			if ( $this.hasClass( 'charitable-disabled-modal' ) ) {
				reason = 'modal';
			} else if ( $this.hasClass( 'charitable-disabled-same_page' ) ) {
				reason = 'same_page';
			}

			app.modalWarningModal( name, reason, icon );
		},

		/**
		 * Open education modal handler.
		 *
		 * @since 1.8.0
		 *
		 * @param {Event} event Event.
		 */
		openModalButtonHandlerInstall: function( event ) {

			const $this = $( this );

			var icon        = '',
				plugin_url  = '',
				video       = '',
				license     = '',
				elementType = false;

			if ( $this.data( 'action' ) && [ 'activate', 'install' ].includes( $this.data( 'action' ) ) ) {
				return;
			}

			event.preventDefault();
			event.stopImmediatePropagation();

			let name = $this.data( 'name' );

			if ( $this.hasClass( 'charitable-add-fields-button' ) ) {
				name  = $this.text();
				name += name.indexOf( charitable_builder.field ) < 0 ? ' ' + charitable_builder.field : '';
				// if the button has an attribute of data-icon, we want to use that icon instead of the default one.
				if ( $this.data('field-icon') ) {
					icon = $this.data('field-icon');
				}
			}

			if ( $this.data( 'install' ) ) {
				plugin_url = $this.data( 'install' );
			} else if ( $this.data( 'plugin-url' ) ) {
				plugin_url = $this.data('plugin-url');
			}

			if ( $this.data( 'video' ) ) {
				video = $this.data('video');
			}

			if ( $this.data( 'license' ) ) {
				license = $this.data('license');
			}

			app.installModal( name, plugin_url, license, video, $this, elementType, icon );
		},

		/**
		 * Open education modal handler.
		 *
		 * @since 1.8.0
		 *
		 * @param {Event} event Event.
		 */
		openModalButtonHandlerActivatedRefresh: function( event ) {

			const $this = $( this );

			event.preventDefault();
			event.stopImmediatePropagation();

			let name = $this.data( 'name' );

			app.activateRefreshModal( name, $this.data( 'video' ) );
		},

		/**
		 * Open education modal handler.
		 *
		 * @since 1.8.0
		 *
		 * @param {Event} event Event.
		 */
		openModalButtonHandlerActivate: function( event ) {

			const $this = $( this );

			var icon        = '',
				plugin_url  = '',
				video       = '',
				license     = '',
				elementType = false;

			if ( $this.data( 'action' ) && [ 'activate', 'install' ].includes( $this.data( 'action' ) ) ) {
				return;
			}

			event.preventDefault();
			event.stopImmediatePropagation();

			let name = $this.data( 'name' );

			if ( $this.data('plugin-url') ) {
				plugin_url = $this.data('plugin-url');
			}

			if ( $this.data('video') ) {
				video = $this.data('video');
			}

			if ( $this.data('license') ) {
				license = $this.data('license');
			}

			app.activateModal( name, plugin_url, license, video, $this, elementType, icon );
		},


		/**
		 * Open education modal handler.
		 *
		 * @since 1.8.0
		 *
		 * @param {Event} event Event.
		 */
		openModalButtonHandler: function( event ) {

			const $this = $( this );

			if ( $this.data( 'action' ) && [ 'activate', 'install' ].includes( $this.data( 'action' ) ) ) {
				return;
			}

			event.preventDefault();
			event.stopImmediatePropagation();

			let icon = '',
				name = $this.data( 'name' ),
				elementType = $this.data( 'type' );

			if ( $this.hasClass( 'charitable-add-fields-button' ) ) {
				name  = $this.text();
				name += name.indexOf( charitable_builder.field ) < 0 ? ' ' + charitable_builder.field : '';
				// if the button has an attribute of data-icon, we want to use that icon instead of the default one.
				if ( $this.data('field-icon') ) {
					icon = $this.data('field-icon');
				}
			}

			const utmContent = 'utmValue'; // charitableEducation.core.getUTMContentValue( $this );

			app.upgradeModal( name, utmContent, $this.data( 'license' ), $this.data( 'video' ), elementType, icon );
		},

		/**
		 * Upgrade modal.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} feature Field name or feature.
		 * @param {string} reason  slug for the why reason.
		 * @param {string} icon    icon slug.
		 */
		modalWarningModal: function( feature, reason = '', icon = '' ) {

			var	message       = '',
				modalWidth    = app.getUpgradeModalWidth( false ),
				title         = '';

			if ( reason === 'modal' ) {
				message = charitable_builder.field_disabled_due_to_modal.replace( /%name%/g, feature )
			} else if ( reason === 'same_page' ) {
				message = charitable_builder.field_disabled_due_to_same_page.replace( /%name%/g, feature )
			}

			var modal = $.alert( {  // eslint-disable-line
				backgroundDismiss: true,
				title            : title,
				icon             : icon !== '' ? 'fa ' + icon : 'fa fa-thumbs-up',
				content          : message,
				boxWidth         : modalWidth,
				theme            : 'modern,charitable-install-form',
				closeIcon        : true,
				buttons : {
					confirm: {
						text    : charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
						action: function() {

						}
					},
					cancel: {
						text    : charitable_builder.go_to_settings,
						btnClass: 'btn-confirm',
						action: function() {
							window.open( charitable_builder.settings_page_url );
						}
					}
				}
			} );

		},

		/**
		 * Change plugin/addon state.
		 *
		 * @since 1.8.0
		 *
		 * @param {string}   plugin     Plugin slug or URL for download (example: charitable-geolocation/charitable-geolocation.php)
		 * @param {string}   state      State status activate|deactivate|install.
		 * @param {string}   pluginType Plugin type addon or plugin.
		 * @param {Function} callback   Callback for get result from AJAX.
		 */
		setAddonState: function( plugin_url, plugin_name, state, pluginType, modal, clickedObject, callback ) {

			wpchar.debug ( 'setAddonState' );
			wpchar.debug ( plugin_url );
			wpchar.debug ( plugin_name );
			wpchar.debug ( state );

			var actions = {
					'activate': 'charitable_activate_addon',
					'install': 'charitable_install_addon',
					'deactivate': 'charitable_deactivate_addon'
				},
				action = actions[ state ];

			if ( ! action ) {
				return;
			}

			var data = {
				action: action,
				nonce: charitable_admin.nonce, // eslint-disable-line
				plugin: plugin_url,
				type: pluginType
			};

			wpchar.debug ( data );

			$.post( charitable_admin.ajax_url, data, function( res ) { // eslint-disable-line

				callback( res, plugin_url, plugin_name, state, modal, clickedObject );

			} ).fail( function( xhr ) {

				wpchar.debug( xhr.responseText );

			} );
		},

		/**
		 * Respond for the plugin/addon state.
		 *
		 * @since 1.8.0
		 *
		 */
		callBackAddonState: function( res, plugin_url = '', plugin_name = '', state = '', modal = false, clickedObject ) {

			wpchar.debug ( 'callBackAddonState' );
			wpchar.debug ( res );
			wpchar.debug ( state );
			wpchar.debug ( modal );

			var successText = '';

			if ( res.success ) {

				if ( 'install' === state ) {

					wpchar.debug('installed');

					// close the current modal window, if you can.
					if ( s.currentModal !== false &&  'object' === typeof s.currentModal ) {
						s.currentModal.close();
					}

					if ( res.data.is_activated ) {
						successText = res.data.msg;
						app.upgradeModalAddonInstalledAndActivated( plugin_url, plugin_name, successText );
					} else {
						app.upgradeModalAddonInstalledAndActivatedFailed( plugin_url, plugin_name, successText );
					}

				} else if ( 'activate' === state ) {

					wpchar.debug('activated');

					successText = res.data;

					// close the current modal window, if you can.
					if ( s.currentModal !== false &&  'object' === typeof s.currentModal ) {
						s.currentModal.close();
					}

					app.upgradeModalAddonActivated( plugin_url, plugin_name, successText );

					// Add class class to clickedObject, which should be the button they clicked.
					clickedObject.addClass( 'charitable-installed-refresh' ).removeClass( 'charitable-not-activated' );
					app.openModalButtonClick();

				} else {

					wpchar.debug('some other success');

					successText = res.data;

					// close the current modal window, if you can.
					if ( s.currentModal !== false &&  'object' === typeof s.currentModal ) {
						s.currentModal.close();
					}

					app.upgradeModalAddonActivated( plugin_url, plugin_name, successText );

					// Add class class to clickedObject, which should be the button they clicked.
					clickedObject.addClass( 'charitable-installed-refresh' ).removeClass( 'charitable-not-activated' );
					app.openModalButtonClick();

				}

			} else {

				// close the current modal window, if you can.
				if ( s.currentModal !== false &&  'object' === typeof s.currentModal ) {
					s.currentModal.close();
				}

				app.upgradeModalAddonActivatedFailed( plugin_url, plugin_name, successText );

			}

		},

		/**
		 * Refresh notice modal.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} feature     Feature name.
		 * @param {string} type        Feature license type: 'pro', 'basic', 'plus', 'agency', 'elite'.
		 * @param {string} video       Feature video URL.
		 * @param {string} elementType Element type.
		 */
		activateRefreshModal: function( name, video, icon = '' ) {

			wpchar.debug('activateRefreshModal');
			wpchar.debug( name );
			wpchar.debug( video );

			var	message       = charitable_builder.activated_refresh.replace( /%addon%/g, name ),
				isVideoModal  = ! _.isEmpty( video ), // eslint-disable-line
				modalWidth    = app.getUpgradeModalWidth( isVideoModal ),
				title         = '<span class="charitable-upgrade-pro-title">' + name + ' ' + charitable_builder.activated_refresh_title + '</span>';

			var modal = $.alert( {
				backgroundDismiss: true,
				title            : title,
				icon             : icon !== '' ? 'fa ' + icon : 'fa fa-thumbs-up',
				content          : message,
				boxWidth         : modalWidth,
				theme            : 'modern,charitable-activate-refresh',
				closeIcon        : true,
				buttons : {
					confirm: {
						text    : charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ]
					}
				}
			} );

			$( window ).on( 'resize', function() {

				modalWidth = app.getUpgradeModalWidth( isVideoModal );

				if ( modal.isOpen() ) {
					modal.setBoxWidth( modalWidth );
				}
			} );
		},

		/**
		 * Activate addon modal.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} feature       Feature name.
		 * @param {string} plugin_url    Likely the url that allows a download.
		 * @param {string} type          Feature license type: 'pro', 'basic', 'plus', 'agency', 'elite'.
		 * @param {string} video         Feature video URL.
		 * @param {string} clickedObject What was clicked on.
		 * @param {string} elementType   Element type.
		 * @param {string} icon          Icon slug.
		 */
		activateModal: function( feature, plugin_url, type, video, clickedObject, elementType = false, icon = '' ) {

			wpchar.debug('activatelModal');
			wpchar.debug( feature );
			wpchar.debug( plugin_url );
			wpchar.debug( type );
			wpchar.debug( video );
			wpchar.debug( elementType );

			// Provide a default value.
			if ( typeof type === 'undefined' || type.length === 0 ) {
				type = 'pro';
			}

			var	message       = charitable_builder.activate.message.replace( /%addon%/g, feature ),
				isVideoModal  = ! _.isEmpty( video ), // eslint-disable-line
				modalWidth    = app.getUpgradeModalWidth( isVideoModal ),
				title         = '<span class="charitable-upgrade-pro-title">' + feature + ' ' + charitable_builder.activate.title + '</span>';

			var modal = $.alert( {
				backgroundDismiss: true,
				title            : title,
				icon             : icon !== '' ? 'fa ' + icon : 'fa fa-thumbs-up',
				content          : message,
				boxWidth         : modalWidth,
				theme            : 'modern,charitable-activate-addon',
				closeIcon        : true,
				buttons : {
					cancel: {
						btnClass   : 'btn-confirm',
						keys       : [ 'esc' ],
						isHidden   : false, // initially not hidden
						isDisabled : false, // initially not disabled
						action: function() {
						}
					},
					install: {
						text    : charitable_builder.activate.button,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
						isHidden   : false, // initially not hidden
						isDisabled : false, // initially not disabled
						action: function( activateButton ) {
							// change the name of the button to let the user know it's installing, or at least trying.
							activateButton.setText( charitable_builder.activating );
							activateButton.disable();
							this.$$cancel.prop('disabled', true);
							this.$$cancel.hide();
							app.setAddonState( plugin_url, feature, 'activate', 'addon', $( this ), clickedObject, app.callBackAddonState );
							return false;
						}
					}
				}
			} );

			s.currentModal = modal;

			$( window ).on( 'resize', function() {

				modalWidth = app.getUpgradeModalWidth( isVideoModal );

				if ( modal.isOpen() ) {
					modal.setBoxWidth( modalWidth );
				}
			} );
		},

		/**
		 * Install modal.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} feature     Feature name.
		 * @param {string} plugin_url  Likely the url that allows a download.
		 * @param {string} type        Feature license type: 'pro', 'basic', 'plus', 'agency', 'elite'.
		 * @param {string} video       Feature video URL.
		 * @param {object} clickedObject What was clicked on.
		 * @param {string} elementType Element type.
		 * @param {string} icon        Icon slug.
		 */
		installModal: function( feature, plugin_url, type, video, clickedObject, elementType = false, icon = '' ) {

			wpchar.debug('installModal');
			wpchar.debug( feature );
			wpchar.debug( plugin_url );
			wpchar.debug( type );
			wpchar.debug( video );
			wpchar.debug( clickedObject );
			wpchar.debug( elementType );

			// Provide a default value.
			if ( typeof type === 'undefined' || type.length === 0 ) {
				type = 'pro';
			}

			// Make sure we received only supported types.
			if ( $.inArray( type, [ 'pro', 'basic', 'plus', 'agency', 'elite' ] ) < 0 ) {
				return;
			}

			var	license_label = charitable_builder.charitable_license_label,
				message       = charitable_builder.install[ type ].message.replace( /%name%/g, license_label ).replace( /%addon%/g, feature ),
				isVideoModal  = ! _.isEmpty( video ), // eslint-disable-line
				modalWidth    = app.getUpgradeModalWidth( isVideoModal ),
				title         = '';

				if ( elementType ) {
					title = '<span class="charitable-upgrade-pro-title">' + feature + ' ' + charitable_builder.install['pro-panel'].title + '</span>';
				} else {
					title = '<span class="charitable-upgrade-pro-title">' + feature + ' ' + charitable_builder.install[type].title + '</span>';
				}

			var modal = $.alert( {
				backgroundDismiss : false,
				title             : title,
				icon              : icon !== '' ? 'fa ' + icon : 'fa fa-thumbs-up',
				content           : message,
				boxWidth          : modalWidth,
				theme             : 'modern,charitable-install-form',
				closeIcon         : false,
				buttons : {
					cancel: {
						btnClass   : 'btn-confirm',
						keys       : [ 'esc' ],
						isHidden   : false, // initially not hidden
						isDisabled : false, // initially not disabled
						action: function() {
						}
					},
					confirmInstall: {
						text       : charitable_builder.install[type].button,
						btnClass   : 'btn-confirm',
						keys       : [ 'enter' ],
						isHidden   : false, // initially not hidden
						isDisabled : false, // initially not disabled
						action: function( confirmInstallButton ) {
							// change the name of the button to let the user know it's installing, or at least trying.
							confirmInstallButton.setText( charitable_builder.installing );
							confirmInstallButton.disable();
							this.$$cancel.prop('disabled', true);
							this.$$cancel.hide();

							app.setAddonState( plugin_url, feature, 'install', 'addon', $( this ), clickedObject, app.callBackAddonState );
							return false;
						}
					}
				}
			} );

			s.currentModal = modal;

			$( window ).on( 'resize', function() {

				modalWidth = app.getUpgradeModalWidth( isVideoModal );

				if ( modal.isOpen() ) {
					modal.setBoxWidth( modalWidth );
				}
			} );
		},

		/**
		 * Upgrade modal.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} feature    Feature name.
		 * @param {string} utmContent UTM content.
		 * @param {string} type       Feature license type: pro or elite.
		 * @param {string} video      Feature video URL.
		 * @param {string} elementType Element type.
		 * @param {string} icon        Icon slug.
		 */
		upgradeModal: function( feature, utmContent, type, video, elementType = false, icon = '' ) {

			// Provide a default value.
			if ( typeof type === 'undefined' || type.length === 0 ) {
				type = 'pro';
			}

			// Make sure we received only supported types.
			if ( $.inArray( type, [ 'pro', 'basic', 'plus', 'agency', 'elite' ] ) < 0 ) {
				return;
			}

			var isVideoModal   = ! _.isEmpty( video ), // eslint-disable-line
				modalWidth     = app.getUpgradeModalWidth( isVideoModal ),
				title          = '',
				message        = '',
				button         = '',
				typeCapitlized = 'pro' !== type.toLowerCase() ? type.charAt(0).toUpperCase() + type.slice(1) : 'PRO';

			if ( elementType ) {
				title   = feature + ' ' + charitable_builder.upgrade['pro-panel'].title.replace( /%plan%/g, typeCapitlized ),
				message = charitable_builder.upgrade[ 'pro-panel' ].message.replace( /%name%/g, feature ),
				message = message.replace( /%plan%/g, typeCapitlized ),
				button  = charitable_builder.upgrade[ 'pro-panel' ].button.replace( /%name%/g, feature ).replace( 'addon', '' ),
				button  = button.replace( /%plan%/g, typeCapitlized );

			} else {
				title   = feature + ' ' + charitable_builder.upgrade[type].title.replace( /%plan%/g, typeCapitlized ),
				message = charitable_builder.upgrade[ type ].message.replace( /%name%/g, feature ),
				message = message.replace( /%plan%/g, typeCapitlized ),
				button  = charitable_builder.upgrade[ type ].button.replace( /%name%/g, feature ).replace( 'addon', '' ),
				button  = button.replace( /%plan%/g, typeCapitlized );
			}

			var modal = $.alert( {
				backgroundDismiss: true,
				title            : title,
				icon             : icon !== '' ? 'fa ' + icon : 'fa fa-lock',
				content          : message,
				boxWidth         : modalWidth,
				theme            : 'modern,charitable-upgrade-form-lite',
				closeIcon        : true,
				onOpenBefore: function() {

					if ( isVideoModal ) {
						this.$el.addClass( 'has-video' );
					}

					var videoHtml = isVideoModal ? '<iframe src="' + video + '" class="feature-video" frameborder="0" allowfullscreen="" width="475" height="267"></iframe>' : '';

					// this.$btnc.after( '<div class="discount-note">' + charitable_builder.upgrade_bonus_modal + '</div>' );
					this.$btnc.after( charitable_builder.upgrade[type].doc.replace( /%25name%25/g, feature ) );
					this.$btnc.after( videoHtml );

					this.$body.find( '.jconfirm-content' ).addClass( 'lite-upgrade' );
				},
				buttons : {
					confirm: {
						text    : button,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
						action: function() {
							window.open( app.getUpgradeURL( utmContent, type ), '_blank' );
							app.upgradeModalThankYou( type );
						}
					}
				}
			} );

			$( window ).on( 'resize', function() {

				modalWidth = app.getUpgradeModalWidth( isVideoModal );

				if ( modal.isOpen() ) {
					modal.setBoxWidth( modalWidth );
				}

			} );

		},

		/**
		 * Get install URL according to the UTM content and license type.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} utmContent UTM content.
		 * @param {string} type       Feature license type: pro or elite.
		 *
		 * @returns {string} Upgrade URL.
		 */
		getInstallURL: function( utmContent, type, searchKeyword = false ) { // eslint-disable-line

			var returnUrl = charitable_builder.charitable_addons_page;

			if ( searchKeyword ) {
				returnUrl = returnUrl + '&search=' + searchKeyword.replace(/[^0-9a-z+ ]/gi, '').replace('Field', '').trim();
			}

			return returnUrl;

		},

		/**
		 * Get upgrade URL according to the UTM content and license type.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} utmContent UTM content.
		 * @param {string} type       Feature license type: pro or elite.
		 *
		 * @returns {string} Upgrade URL.
		 */
		getUpgradeURL: function( utmContent, type ) {

			var	baseURL = charitable_builder.upgrade[ type ].url;

			if ( utmContent.toLowerCase().indexOf( 'template' ) > -1 ) {
				baseURL = charitable_builder.upgrade[ type ].url_template;
			}

			// Test if the base URL already contains `?`.
			var appendChar = /(\?)/.test( baseURL ) ? '&' : '?';

			// If the upgrade link is changed by partners, appendChar has to be encoded.
			if ( baseURL.indexOf( 'https://wpcharitable.com' ) === -1 ) {
				appendChar = encodeURIComponent( appendChar );
			}

			return baseURL + appendChar + 'utm_content=' + encodeURIComponent( utmContent.trim() );
		},

		/**
		 * Upgrade modal thank you.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} type Feature license type: pro or elite.
		 */
		upgradeModalThankYou: function( type ) {

			$.alert( {
				title    : charitable_builder.thanks_for_interest,
				content  : charitable_builder.upgrade[type].modal,
				icon     : 'fa fa-info-circle',
				type     : 'blue',
				boxWidth : '565px',
				buttons  : {
					confirm: {
						text    : charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ]
					}
				}
			} );

		},

		/**
		 * Activation failed message.
		 *
		 * @since 1.8.0.x
		 *
		 * @param {string} feature Addon.
		 */
		upgradeModalAddonInstalledAndActivated: function( plugin_url = '', plugin_name = '', successText = '' ) { // eslint-disable-line

			$.alert( {
				title    : '<span class="charitable-upgrade-pro-title">' + plugin_name + ' ' + charitable_builder.installed_activated_title + '</span>',
				content  : charitable_builder.installed_activated_reboot,
				icon     : 'fa fa-info-circle',
				type     : 'blue',
				boxWidth : '565px',
				buttons  : {
					confirm: {
						text    : charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys    : [ 'esc' ]
					},
					saveRefresh: {
						text    : charitable_builder.save_refresh,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
						action: function( saveRefreshButton ) {

							// Check for campaign title first
							if ( $('#charitable_settings_title').val().length === 0 ) {
								// Close this modal
								this.close();
								// Show the title error modal
								app.formSaveCheck();
								return false;
							}

							saveRefreshButton.setText( charitable_builder.standby );
							saveRefreshButton.disable();
							this.$$confirm.prop('disabled', true);
							this.$$confirm.hide();

							// Refresh the current page after saving the form.
							app.formSave( false, false, false, true );

							return false;

						}
					}
				}
			} );

		},

		/**
		 * Activation confirmation.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} plugin_url Plugin activation url.
		 * @param {string} plugin_name Plugin name.
		 * @param {string} successText Success text.
		 *
		 */
		upgradeModalAddonActivated: function( plugin_url = '', plugin_name = '', successText = '' ) { // eslint-disable-line

			$.alert( {
				title    : '<span class="charitable-upgrade-pro-title">' + plugin_name + ' ' + charitable_builder.activated_title + '</span>',
				content  : charitable_builder.activated_reboot,
				icon     : 'fa fa-info-circle',
				type     : 'blue',
				boxWidth : '565px',
				buttons  : {
					confirm: {
						text    : charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys    : [ 'esc' ]
					},
					saveRefresh: {
						text    : charitable_builder.save_refresh,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
						action: function( saveRefreshButton ) {

							// Check for campaign title first
							if ( $('#charitable_settings_title').val().length === 0 ) {
								// Close this modal
								this.close();
								// Show the title error modal
								app.formSaveCheck();
								return false;
							}

							saveRefreshButton.setText( charitable_builder.standby );
							saveRefreshButton.disable();
							this.$$confirm.prop('disabled', true);
							this.$$confirm.hide();

							// Refresh the current page after saving the form.
							app.formSave( false, false, false, true );

							return false;

						}
					}
				}
			} );

		},

		/**
		 * Activation confirmation.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} plugin_url Plugin activation url.
		 * @param {string} plugin_name Plugin name.
		 * @param {string} successText Success text.
		 */
		upgradeModalAddonInstalledAndActivatedFailed: function( plugin_url = '', plugin_name = '', successText = '' ) { // eslint-disable-line

			$.alert( {
				title    : '<span class="charitable-upgrade-pro-title">' + plugin_name + ' ' + charitable_builder.installed_activated_failed_title + '</span>',
				content  : charitable_builder.installed_activated_failed_reboot,
				icon     : 'fa fa-info-circle',
				type     : 'blue',
				boxWidth : '565px',
				buttons  : {
					confirm: {
						text    : charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ]
					}
				}
			} );

		},

		/**
		 * Activation failed message.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} plugin_url Plugin activation url.
		 * @param {string} plugin_name Plugin name.
		 * @param {string} successText Success text.
		 */
		upgradeModalAddonActivatedFailed: function( plugin_url = '', plugin_name = '', successText = '' ) { // eslint-disable-line

			$.alert( {
				title    : '<span class="charitable-upgrade-pro-title">' + plugin_name + ' ' + charitable_builder.activated_failed_title + '</span>',
				content  : charitable_builder.activated_failed_reboot,
				icon     : 'fa fa-info-circle',
				type     : 'blue',
				boxWidth : '565px',
				buttons  : {
					confirm: {
						text    : charitable_builder.ok,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ]
					}
				}
			} );

		},

		/**
		 * Get upgrade modal width.
		 *
		 * @since 1.8.0
		 *
		 * @param {boolean} isVideoModal Upgrade modal type (with video or not).
		 *
		 * @returns {string} Modal width in pixels.
		 */
		getUpgradeModalWidth: function( isVideoModal ) {

			var windowWidth = $( window ).width();

			if ( windowWidth <= 300 ) {
				return '250px';
			}

			if ( windowWidth <= 750 ) {
				return '350px';
			}

			if ( ! isVideoModal || windowWidth <= 1024 ) {
				return '560px';
			}

			return windowWidth > 1070 ? '1040px' : '994px';
		},


		/**
		 * Util for checking/confirming function is a function.
		 *
		 * @since 1.8.0
		 */
		isFunction: function( functionToCheck ) {

			return functionToCheck && {}.toString.call(functionToCheck) === '[object Function]';

		}

	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) ); // eslint-disable-line

CharitableCampaignBuilder.init();
