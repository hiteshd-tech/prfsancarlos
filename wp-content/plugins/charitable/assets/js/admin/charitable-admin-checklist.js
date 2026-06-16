/* global Charitable, CharitableCampaignBuilder, wpchar */
/**
 * Charitable Checklist function.
 *
 * @since 1.8.1.15
 */
"use strict";

var CharitableChecklist = window.CharitableChecklist || (function (document, window, $) { /**
     * Public functions and properties.
     *
     * @since 1.8.1.15
     *
     * @type {object}
     */
	var app = {
		/**
       * Start the engine.
       *
       * @since 1.8.1.15
       */
		init: function () {
			$(app.ready);
			$(window).on("load", function () { // in case of jQuery 3.+ we need to wait for an `ready` event first.
				if (typeof $.ready.then === "function") {
					$.ready.then(app.load);
				} else {
					app.load();
				}
			});
		},

		/**
       * Document ready.
       *
       * @since 1.8.1.15
       */
		ready: function () {},

		/**
       * Window load.
       *
       * @since 1.8.1.15
       */
		load: function () {
			app.setup();
		},

		/**
       * Initial setup.
       *
       * @since 1.8.1.15
       */
		setup: function () { // test and see if Shepherd is available, etc. - if not, return.
			if (typeof Shepherd === "undefined" || typeof charitable_admin_checklist_onboarding === "undefined" || typeof charitable_admin_checklist_onboarding.option === "undefined" || typeof charitable_admin_checklist_onboarding.option.tour === "undefined" || typeof charitable_admin_checklist_onboarding.option.tour.status === "undefined") { // eslint-disable-line
				return;
			}

			// If we are on the actual check list page, load the shepherd if there's a # in the URL.
			if (window.location.search.includes("checklist") && window.location.hash) {
				app.setupChecklistPage();
			}

			// If the url includes 'page=charitable-setup-checklist' then load the checklist.
			if (window.location.search.includes("page=charitable-setup-checklist")) {
				app.setupChecklistPage();
			}

			// Detect if there's a change in the hash and load the appropriate step.
			$(window).on("hashchange", function () {
				if (window.location.search.includes("checklist") && window.location.hash) {
					app.setupChecklistPage();
				}
			});

			app.events();

			// possible values: 'init', 'started', 'completed', 'skipped'.
			if (charitable_admin_checklist_onboarding.option.tour.status === "init" || charitable_admin_checklist_onboarding.option.tour.status === "started" || charitable_admin_checklist_onboarding.option.tour.status === "") {
				app.setupChecklistGeneralSettings();
				app.setupChecklistEmailSettings();
				app.setupChecklistGatewaySettings();
			} else if (charitable_admin_checklist_onboarding.option.tour.status === "started") {}

			// Opt-in to anlaytics/tracking.
			app.setupAnalyticsTrackingSignInOut();
		},

		/**
       * Setup the analytics tracking for sign in and sign out.
       * This is for the checklist page.
       *
       * @since 1.8.4
       *
       * @return {void}
      */
		setupAnalyticsTrackingSignInOut: function () { // since this is the checklist page, we need to check if the opt-in section is present.
			if ($('.charitable-step-opt-in').length === 0 || $('.charitable-step-opt-in a.charitable-button').length === 0) {
				wpchar.debug('not found the charitable-step-opt-in section or the button.');
				return;
			}

			$('.charitable-step-opt-in').on('click', 'a.charitable-button', function (e) {
				e.preventDefault();

				wpchar.debug('clicked on the opt-in button');

				// <i class="fa fa-check"></i>

				// <i class="fa fa-spinner fa-spin"></i>

				var $this = $(this);

				// does this link have an attribute of data-optin-tracking? If not, bail.
				if ('undefined' === typeof $(this).attr('data-optin-tracking-status')) {
					wpchar.debug('no data-optin-tracking attribute found.');
					return;
				}

				// Replace the fa-arrow-right with a spinner.
				$this.find('i').removeClass('fa-arrow-right').addClass('fa-spinner fa-spin');

				var data = {
					action: 'charitable_setup_process_tracking',
					tracking_action: $(this).attr('data-optin-tracking-status') === 'joined' ? 'opt-out' : 'opt-in',
					nonce: charitable_admin.nonce
				};

				$.post(charitable_admin.ajax_url, data, function (response) {

					if (response.success) {

						var tracking = false;

						if (response.data.opt_in_tracking === true) {

							tracking = true;

							wpchar.debug('going with joined');
							// remove all css classes.
							$this.attr('data-optin-tracking-status', 'joined');
							$this.attr('class', 'charitable-button charitable-button-primary');
							// alter the checklist item to show that it has been completed.
							$('.chartiable-opt-in-tracking').addClass('charitable-hidden');
							$('.chartiable-opt-in-tracking-1').removeClass('charitable-hidden');
							wpchar.debug($this.closest('.charitable-step').find('.charitable-checklist-checkbox'));
							wpchar.debug($this);
							$this.closest('.charitable-step').find('.charitable-checklist-checkbox').addClass('charitable-checklist-completed');
							$this.closest('.charitable-step').find('.charitable-checklist-checkbox').addClass('charitable-checklist-checked');

							// replace the spinner icon with a checkmark.
							$this.find('i').removeClass('fa-spinner fa-spin').addClass('fa-check');

						} else {

							wpchar.debug('going with not-joined');
							$this.attr('data-optin-tracking-status', 'not-joined');
							$this.attr('class', 'charitable-button charitable-button-primary alt');
							$('.chartiable-opt-in-tracking-1').addClass('charitable-hidden');
							$('.chartiable-opt-in-tracking').removeClass('charitable-hidden');
							wpchar.debug($this.closest('.charitable-step').find('.charitable-checklist-checkbox'));
							wpchar.debug($this);
							$this.closest('.charitable-step').find('.charitable-checklist-checkbox').removeClass('charitable-checklist-completed');
							$this.closest('.charitable-step').find('.charitable-checklist-checkbox').removeClass('charitable-checklist-checked');

							// replace the spinner icon with a checkmark.
							$this.find('i').removeClass('fa-spinner fa-spin').addClass('fa-check');

						}

						if (response.data.text.length > 0) {
							$this.html(response.data.text + ' <i class="fa fa-check"></i>');
						}

						// wait one second and then remove the checkmark.
						setTimeout(function () { // fade removing the checkmark.
							$this.find('i').fadeOut(1000);
							// remove the checkmark.
							$this.find('i').remove();

							if (true === tracking) {
								$this.closest('.charitable-step').find('a.charitable-toggle').click();
							}
						}, 1000);

					} else { // there is has been an error.

					}

				});

			});


		},

		/**
       * Setup the tour steps for the checklist page itself.
       *
       * @since 1.8.1.15
       */
		setupChecklistPage: function () {
			var $step_element = null;

			// first, add a on click event to the buttons in the fundrasing box - if the user clicks on the button, then we (via ajax) mark the step as completed.
			$(".charitable-step.charitable-step-fundraising-next-level").on("click", ".charitable-button", function () { // disable the button and reduce opacity.
				$(this).attr("disabled", "disabled").css("opacity", "0.5");
				var stepData = {
					stepStatus: 'completed',
					step: 'next-level'
				};
				var updateStatus = app.saveChecklistOption(stepData);
				// refresh the page so items can be updated.
				updateStatus.done(function () { // reload the page without any hash.
					window.location.href = window.location.href.split('#')[0];
				});
			},);

			// determine the # and load the appropriate step.
			if (window.location.hash === "#general-settings") {
				$step_element = $(".charitable-step.charitable-step-plugin-config");
			} else if (window.location.hash === "#email-settings") {
				$step_element = $(".charitable-step.charitable-step-email-settings");
			} else if (window.location.hash === "#connect-gateway") {
				$step_element = $(".charitable-step.charitable-step-connect-payment");
			} else if (window.location.hash === "#first-campaign") {
				$step_element = $(".charitable-step.charitable-step-create-first-campaign",);
			} else if (window.location.hash === "#first-donation") {
				$step_element = $(".charitable-step.charitable-step-create-first-donation",);
			} else if (window.location.hash === "#next-level") {
				$step_element = $(".charitable-step.charitable-step-fundraising-next-level",);
			} else {
				return;
			}

			if (! $step_element.length) {
				return;
			}

			// animate and scroll the screen to the 'charitable-step.charitable-step-plugin-config' div.
			$("html, body").animate({
				scrollTop: $step_element.offset().top - 50
			});

			// make sure the section step is visible.
			// remove any charitable-angle-right class from the step.
			$step_element.find(".charitable-angle-right").removeClass("charitable-angle-right");
			// make sure the charitable-toggle-container is visible.
			$step_element.find(".charitable-toggle-container").show();

			// Add a style to the class to make background color yellow then fade it to transparent over the course of 5 seconds.
			$step_element.addClass("charitable-step-highlight");
			setTimeout(function () {
				$step_element.removeClass("charitable-step-highlight");
			}, 1000);
		},

		/**
       * Setup the tour steps.
       *
       * @since 1.8.1.15
       */
		setupChecklistGeneralSettings: function () { // if the url contains the query name of 'checklist' and the value of 'general-settings', then start the tour.
			if (! window.location.search.includes("checklist=general-settings")) {
				return;
			}

			// if the step has already been completed, then return. check the array charitable_admin_checklist_onboarding.option.tour.steps_completed.
			if (charitable_admin_checklist_onboarding.option.tour.steps_completed.includes("general-settings",)) {
				return;
			}

			const checklistTour = new Shepherd.Tour({
				defaultStepOptions: {
					cancelIcon: {
						enabled: true
					},
					classes: "",
					scrollTo: {
						behavior: "smooth",
						block: "center"
					},
					when: {
						show() { // remove .shshepherd-target class from the target element.
							$(".shepherd-target").removeClass("shepherd-target");
						}
					}
				},
				tourName: "wpchar-checklist-general-settings",
				useModalOverlay: true
			});

			checklistTour.addStep({
				id: "wpchar-checklist-step-general-settings-0",
				title: charitable_admin_checklist_onboarding.strings.general_settings_step_0_title,
				text: charitable_admin_checklist_onboarding.strings.general_settings_step_0_text,
				arrow: false,
				classes: "wpchar-checklist-step-general-settings-0",
				buttons: [
					{
						text: charitable_admin_checklist_onboarding.strings.ok,
						classes: "charitable-tour-btn-primary",
						action: checklistTour.next
					},
				]
			});

			checklistTour.addStep({
				id: "wpchar-checklist-step-general-settings-1",
				text: charitable_admin_checklist_onboarding.strings.general_settings_step_1_text,
				arrow: true,
				modalOverlayOpeningPadding: 8,
				cancelIcon: {
					enabled: false
				},
				attachTo: {
					element: "p.submit .button.button-primary",
					on: "bottom"
				},
				classes: "wpchar-visual-campaign-builder-step-2",
				buttons: [
					{
						text: charitable_admin_checklist_onboarding.strings.ok,
						classes: "charitable-tour-btn-primary",
						action: function () {
							app.gotoTop();
							checklistTour.next();
						}
					},
				]
			});

			checklistTour.start();
		},

		/**
       * Setup the tour steps.
       *
       * @since 1.8.1.15
       */
		setupChecklistEmailSettings: function () { // if the url contains the query name of 'checklist' and the value of 'email-settings', then start the tour.

			if (! window.location.search.includes("checklist=email-settings")) {
				return;
			}

			// if the step has already been completed, then return. check the array charitable_admin_checklist_onboarding.option.tour.steps_completed.
			if (charitable_admin_checklist_onboarding.option.tour.steps_completed.includes("email-settings",)) {
				return;
			}

			const checklistTour = new Shepherd.Tour({
				defaultStepOptions: {
					cancelIcon: {
						enabled: true
					},
					classes: "",
					scrollTo: {
						behavior: "smooth",
						block: "center"
					},
					when: {
						show() { // remove .shshepherd-target class from the target element.
							$(".shepherd-target").removeClass("shepherd-target");
						}
					}
				},
				tourName: "wpchar-checklist-email-settings",
				useModalOverlay: true
			});

			checklistTour.addStep({
				id: "wpchar-checklist-step-email-settings-0",
				title: charitable_admin_checklist_onboarding.strings.email_settings_step_0_title,
				text: charitable_admin_checklist_onboarding.strings.email_settings_step_0_text,
				arrow: false,
				classes: "wpchar-checklist-step-email-settings-0",
				buttons: [
					{
						text: charitable_admin_checklist_onboarding.strings.ok,
						classes: "charitable-tour-btn-primary",
						action: checklistTour.next
					},
				]
			});

			checklistTour.addStep({
				id: "wpchar-checklist-step-email-settings-1",
				text: charitable_admin_checklist_onboarding.strings.email_settings_step_1_text,
				arrow: true,
				modalOverlayOpeningPadding: 8,
				cancelIcon: {
					enabled: false
				},
				attachTo: {
					element: "td.charitable-fullwidth div.cf:nth-child(1)",
					on: "bottom"
				},
				classes: "wpchar-checklist-step-email-settings-1",
				buttons: [
					{
						text: charitable_admin_checklist_onboarding.strings.ok,
						classes: "charitable-tour-btn-primary",
						action: function () {
							checklistTour.next();
						}
					},
				]
			});

			checklistTour.addStep({
				id: "wpchar-checklist-step-email-settings-2",
				text: charitable_admin_checklist_onboarding.strings.email_settings_step_2_text,
				arrow: true,
				modalOverlayOpeningPadding: 8,
				cancelIcon: {
					enabled: false
				},
				attachTo: {
					element: "td.charitable-fullwidth div.cf:nth-child(3)",
					on: "bottom"
				},
				classes: "wpchar-checklist-step-email-settings-2",
				buttons: [
					{
						text: charitable_admin_checklist_onboarding.strings.ok,
						classes: "charitable-tour-btn-primary",
						action: function () {
							checklistTour.next();
						}
					},
				]
			});

			checklistTour.addStep({
				id: "wpchar-checklist-step-email-settings-3",
				text: charitable_admin_checklist_onboarding.strings.email_settings_step_3_text,
				arrow: true,
				modalOverlayOpeningPadding: 8,
				cancelIcon: {
					enabled: false
				},
				attachTo: {
					element: "p.submit .button.button-primary",
					on: "bottom"
				},
				classes: "wpchar-checklist-step-email-settings-3",
				buttons: [
					{
						text: charitable_admin_checklist_onboarding.strings.ok,
						classes: "charitable-tour-btn-primary",
						action: function () {
							app.gotoTop();
							checklistTour.next();
						}
					},
				]
			});

			checklistTour.start();
		},

		/**
       * Setup the tour steps.
       *
       * @since 1.8.1.15
       */
		setupChecklistGatewaySettings: function () { // if the url contains the query name of 'checklist' and the value of 'connect-gateway', then start the tour.
			if (! window.location.search.includes("checklist=connect-gateway")) {
				return;
			}

			// if the step has already been completed, then return. check the array charitable_admin_checklist_onboarding.option.tour.steps_completed.
			if (charitable_admin_checklist_onboarding.option.tour.steps_completed.includes("connect-gateway",)) {
				return;
			}

			const checklistTour = new Shepherd.Tour({
				defaultStepOptions: {
					cancelIcon: {
						enabled: true
					},
					classes: "",
					scrollTo: {
						behavior: "smooth",
						block: "center"
					},
					when: {
						show() { // remove .shshepherd-target class from the target element.
							$(".shepherd-target").removeClass("shepherd-target");
						}
					}
				},
				tourName: "wpchar-checklist-connect-gateway",
				useModalOverlay: true
			});

			checklistTour.addStep({
				id: "wpchar-checklist-step-connect-gateway-0",
				text: charitable_admin_checklist_onboarding.strings.gateway_settings_step_0_text,
				arrow: false,
				classes: "wpchar-checklist-step-connect-gateway-0",
				buttons: [
					{
						text: charitable_admin_checklist_onboarding.strings.ok,
						classes: "charitable-tour-btn-primary",
						action: checklistTour.next
					},
				]
			});

			checklistTour.addStep({
				id: "wpchar-checklist-step-connect-gateway-1",
				text: charitable_admin_checklist_onboarding.strings.gateway_settings_step_1_text,
				arrow: true,
				modalOverlayOpeningPadding: 8,
				cancelIcon: {
					enabled: false
				},
				attachTo: {
					element: "td.charitable-fullwidth div.cf:nth-child(1)",
					on: "right"
				},
				classes: "wpchar-checklist-step-connect-gateway-1",
				buttons: [
					{
						text: charitable_admin_checklist_onboarding.strings.ok,
						classes: "charitable-tour-btn-primary",
						action: function () {
							checklistTour.next();
						}
					},
				]
			});

			checklistTour.addStep({
				id: "wpchar-checklist-step-connect-gateway-2",
				text: charitable_admin_checklist_onboarding.strings.gateway_settings_step_2_text,
				arrow: true,
				modalOverlayOpeningPadding: 8,
				cancelIcon: {
					enabled: false
				},
				attachTo: {
					element: "table.form-table tbody tr:nth-last-child(2) input",
					on: "right"
				},
				classes: "wpchar-checklist-step-connect-gateway-2",
				buttons: [
					{
						text: charitable_admin_checklist_onboarding.strings.ok,
						classes: "charitable-tour-btn-primary",
						action: function () {
							checklistTour.next();
						}
					},
				]
			});

			checklistTour.addStep({
				id: "wpchar-checklist-step-connect-gateway-3",
				text: charitable_admin_checklist_onboarding.strings.gateway_settings_step_3_text,
				arrow: true,
				modalOverlayOpeningPadding: 8,
				cancelIcon: {
					enabled: false
				},
				attachTo: {
					element: "p.submit .button.button-primary",
					on: "right"
				},
				classes: "wpchar-checklist-step-connect-gateway-3",
				buttons: [
					{
						text: charitable_admin_checklist_onboarding.strings.ok,
						classes: "charitable-tour-btn-primary",
						action: function () {
							app.gotoTop();
							checklistTour.next();
						}
					},
				]
			});

			checklistTour.start();
		},

		/**
       * Scroll to the top of the page.
       *
       * @since 1.8.1.15
       *
       * @return {void}
       */
		gotoTop: function () {
			$("html, body").animate({
				scrollTop: 0
			}, "slow", function () { // do something here?
			});
		},

		/**
       * Setup the tour steps.
       *
       * @since 1.8.1.15
       */
		setupChecklist: function () {
			const tour = new Shepherd.Tour({
				defaultStepOptions: {
					cancelIcon: {
						enabled: true
					},
					classes: "",
					scrollTo: {
						behavior: "smooth",
						block: "center"
					},
					when: {
						show() {
							const footer = $(".shepherd-footer"),
								currentStep = tour ?. getCurrentStep(),
								currentStepNumber = tour ?. steps.indexOf(currentStep) + 1,
								totalSteps = tour ?. steps.length;

							if (currentStepNumber === 1 || currentStepNumber === totalSteps) {
								return;
							}

							var progressPercentage = (currentStepNumber / totalSteps) * 100;

							if (progressPercentage > 100) {
								progressPercentage = 100;
							}

							// insert HTML into footer that is a progress bar showing % of current/completed steps.
							footer.after('<span class="charitable-tour-progress-bar"><span class="charitable-tour-progress" style="width: ' + progressPercentage + '%"></span></span>',);

							if (currentStepNumber > 2) { // remove .shshepherd-target class from the target element.
								$(".shepherd-target").removeClass("shepherd-target");
							}
						}
					}
				},
				tourName: "wpchar-visual-campaign-builder",
				useModalOverlay: true
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-0",
				text: charitable_builder.onboarding_tour.step_0_text,
				arrow: false,
				classes: "wpchar-visual-campaign-builder-step-0",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.start_tour,
						classes: "charitable-tour-btn-primary",
						action: tour.next
					}, {
						text: charitable_builder.onboarding_tour.watch_video,
						classes: "charitable-tour-btn-primary",
						action: function () {
							app.openVideo();
						}
					},
				]
			});

			const name_step = tour.addStep({
				id: "wpchar-visual-campaign-builder-step-1",
				title: charitable_builder.onboarding_tour.step_1_title,
				text: charitable_builder.onboarding_tour.step_1_text,
				attachTo: {
					element: "#charitable_settings_title",
					on: "bottom"
				},
				classes: "wpchar-visual-campaign-builder-step-1",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.next,
						classes: "charitable-tour-btn-primary charitable-tour-btn-disabled",
						action: tour.next
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-2",
				title: charitable_builder.onboarding_tour.step_2_title,
				text: charitable_builder.onboarding_tour.step_2_text,
				arrow: true,
				modalOverlayOpeningPadding: 8,
				attachTo: {
					element: ".charitable-template-animal-sanctuary div.charitable-template",
					on: "right"
				},
				classes: "wpchar-visual-campaign-builder-step-2",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.choose_a_template,
						classes: "charitable-tour-btn-primary",
						action: tour.hide
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-3",
				title: charitable_builder.onboarding_tour.step_3_title,
				text: charitable_builder.onboarding_tour.step_3_text,
				arrow: true,
				modalOverlayOpeningPadding: 5,

				attachTo: {
					element: "#add-layout",
					on: "right-end"
				},
				classes: "wpchar-visual-campaign-builder-step-3",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.next,
						classes: "charitable-tour-btn-primary",
						action: tour.next
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-4",
				title: charitable_builder.onboarding_tour.step_4_title,
				text: charitable_builder.onboarding_tour.step_4_text,
				arrow: true,
				modalOverlayOpeningPadding: 5,
				attachTo: {
					element: "#charitable-tour-block-1",
					on: "left"
				},
				classes: "wpchar-visual-campaign-builder-step-4",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.next,
						classes: "charitable-tour-btn-primary",
						action: tour.next
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-5",
				title: charitable_builder.onboarding_tour.step_5_title,
				text: charitable_builder.onboarding_tour.step_5_text,
				arrow: true,
				modalOverlayOpeningPadding: 5,
				attachTo: {
					element: "#charitable-tour-block-2",
					on: "left"
				},
				classes: "wpchar-visual-campaign-builder-step-5",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.next,
						classes: "charitable-tour-btn-primary",
						action: tour.next
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-6",
				title: charitable_builder.onboarding_tour.step_6_title,
				text: charitable_builder.onboarding_tour.step_6_text,
				arrow: true,
				modalOverlayOpeningPadding: 5,
				attachTo: {
					element: "#charitable-tour-block-3",
					on: "left"
				},
				classes: "wpchar-visual-campaign-builder-step-6",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.next,
						classes: "charitable-tour-btn-primary",
						action: tour.next
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-7",
				title: charitable_builder.onboarding_tour.step_7_title,
				text: charitable_builder.onboarding_tour.step_7_text,
				arrow: true,
				modalOverlayOpeningPadding: 5,
				attachTo: {
					element: "#charitable-save",
					on: "top-end"
				},
				classes: "wpchar-visual-campaign-builder-step-7",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.next,
						classes: "charitable-tour-btn-primary",
						action: function () {
							app.gotoDraftPublishStep();
							tour.next();
						}
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-8",
				title: charitable_builder.onboarding_tour.step_8_title,
				text: charitable_builder.onboarding_tour.step_8_text,
				arrow: true,
				modalOverlayOpeningPadding: 10,

				attachTo: {
					element: "#charitable-tour-block-4",
					on: "left-start"
				},
				classes: "wpchar-visual-campaign-builder-step-8",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.next,
						classes: "charitable-tour-btn-primary",
						action: function () {
							app.undoDraftPublishStep();
							tour.next();
						}
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-9",
				title: charitable_builder.onboarding_tour.step_9_title,
				text: charitable_builder.onboarding_tour.step_9_text,
				arrow: true,
				modalOverlayOpeningPadding: 3,

				attachTo: {
					element: "#charitable-preview-btn",
					on: "top"
				},
				classes: "wpchar-visual-campaign-builder-step-9",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.next,
						classes: "charitable-tour-btn-primary",
						action: function () {
							tour.next();
						}
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-10",
				title: charitable_builder.onboarding_tour.step_10_title,
				text: charitable_builder.onboarding_tour.step_10_text,
				arrow: true,
				modalOverlayOpeningPadding: 3,

				attachTo: {
					element: "#charitable-view-btn",
					on: "top"
				},
				classes: "wpchar-visual-campaign-builder-step-10",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.next,
						classes: "charitable-tour-btn-primary",
						action: function () {
							tour.next();
						}
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-11",
				title: charitable_builder.onboarding_tour.step_11_title,
				text: charitable_builder.onboarding_tour.step_11_text,
				arrow: true,
				modalOverlayOpeningPadding: 3,

				attachTo: {
					element: "#charitable-embed",
					on: "top"
				},
				classes: "wpchar-visual-campaign-builder-step-11",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.next,
						classes: "charitable-tour-btn-primary",
						action: function () {
							app.gotoSettingsStep();
							tour.next();
						}
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-12",
				title: charitable_builder.onboarding_tour.step_12_title,
				text: charitable_builder.onboarding_tour.step_12_text,
				arrow: true,
				modalOverlayOpeningPadding: 0,

				attachTo: {
					element: "#charitable-panel-settings .charitable-panel-sidebar",
					on: "right"
				},
				classes: "wpchar-visual-campaign-builder-step-12",
				buttons: [
					{
						text: charitable_builder.onboarding_tour.next,
						classes: "charitable-tour-btn-primary",
						action: function () {
							CharitableCampaignBuilder.panelSwitch("design");
							tour.next();
						}
					},
				]
			});

			tour.addStep({
				id: "wpchar-visual-campaign-builder-step-13",
				title: charitable_builder.onboarding_tour.step_13_title,
				text: charitable_builder.onboarding_tour.step_13_text,
				arrow: false,
				classes: "wpchar-visual-campaign-builder-step-13",
				cancelIcon: {
					enabled: false
				},
				buttons: [
					{
						text: charitable_builder.onboarding_tour.lets_get_started,
						classes: "charitable-tour-btn-primary",
						action: tour.next
					},
				]
			});

			tour.start();
		},

		/**
       * Register JS events.
       *
       * @since 1.8.1.15
       */
		events: function () {

			$(".charitable-checklist-list-block").on("click", ".checklist-skip", function (e) {
				app.skipChecklist(e);
			},);
			$(".charitable-checklist-list-block").on("click", ".checklist-toggle", function (e) {
				app.toggleChecklist(e);
			},);

		},

		/**
		 * Toggle checklist icon click.
		 *
		 * @since 1.8.2
		 *
		 * @param {object} e Event object.
		 */
		toggleChecklist: function (e) {

			var $icon = $(e.target),
				$listBlock = $('.charitable-checklist-list-block');

			if (! $listBlock.length || ! $icon.length) {
				return;
			}

			if ($listBlock.hasClass('closed')) {
				charitable_admin_checklist_onboarding.option.window_closed = '0';
				$listBlock.removeClass('closed');

				setTimeout(function () {
					$listBlock.removeClass('transition-back');
				}, 600);
			} else {
				charitable_admin_checklist_onboarding.option.window_closed = '1';
				$listBlock.addClass('closed');

				// Add `transition-back` class when the forward transition is completed.
				// It is needed to properly implement transitions order for some elements.
				setTimeout(function () {
					$listBlock.addClass('transition-back');
				}, 600);
			}


			var optionData = {
				status: "",
				window_closed: charitable_admin_checklist_onboarding.option.window_closed
			};

			app.saveChecklistOption(optionData);

		},

		/**
       * Skip the Checklist without starting it.
       *
       * @since 1.8.2
       */
		skipChecklist: function () {
			var optionData = {
				status: "skipped",
				seconds_spent: 0,
				seconds_left: 0
			};

			// remove the widget in the lower right hand corner
			$(".charitable-checklist").remove();

			// this removes the menu option in the WordPress admin.
			$('#adminmenu a[href="admin.php?page=charitable-setup-checklist"]').parent().remove();

			app.saveChecklistOption(optionData);
		},

		/**
       * Set Challenge parameter(s) to Challenge option.
       *
       * @since 1.8.2
       *
       * @param {object} optionData Query using option schema keys.
       *
       * @returns {promise} jQuery.post() promise interface.
       */
		saveChecklistOption: function (optionData) {
			var data = {
				action: "charitable_onboarding_checklist_save_option",
				option_data: optionData,
				_wpnonce: charitable_admin_checklist_onboarding.nonce
			};

			return $.post(ajaxurl, data, function (response) {
				if (! response.success) {
					console.error("Error saving Charitable Checklist option.");
				}
			});
		}
	};

	// Provide access to public functions/properties.
	return app;
})(document, window, jQuery);
// eslint-disable-line

// Initialize.
CharitableChecklist.init();
