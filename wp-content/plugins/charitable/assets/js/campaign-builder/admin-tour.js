/* global Charitable, CharitableCampaignBuilder, wpchar */
/**
 * Charitable Tour function.
 *
 * @since 1.8.1.15
 */
"use strict";

var CharitableTour =
  window.CharitableTour ||
  (function (document, window, $) {
    /**
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
        $(window).on("load", function () {
          // in case of jQuery 3.+ we need to wait for an `ready` event first.
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
      setup: function () {
        // test and see if Shepherd is available, etc. - if not, return.
        if (
          typeof Shepherd === "undefined" ||
          typeof charitable_admin_builder_onboarding === "undefined" ||
          typeof charitable_admin_builder_onboarding.option === "undefined" ||
          typeof charitable_admin_builder_onboarding.option.tour ===
            "undefined" ||
          typeof charitable_admin_builder_onboarding.option.tour.status ===
            "undefined"
        ) {
          // output error message to console.
          console.error(
            "Charitable Error: Shepherd or charitable_admin_builder_onboarding is not available.",
          );
          // eslint-disable-line
          return;
        }

        // possible values: 'init', 'started', 'completed', 'skipped'.
        if (
          charitable_admin_builder_onboarding.option.tour.status === "init" ||
          charitable_admin_builder_onboarding.option.tour.status === ""
        ) {
          // eslint-disable-line
          app.events();
          if ( app.isCampaignView() ) {
            app.setupTourOnboarding();
          } else {
            app.setupTour();
          }
        } else if (
          charitable_admin_builder_onboarding.option.tour.status === "started"
        ) {

        }

        // remove ANY cookies that would hold campaign status.
        wpCookies.remove("charitable_panel");
        wpCookies.remove("charitable_panel_tab_section_tab_id");
        wpCookies.remove("charitable_panel_layout_options_tabs_tab_open_template");
        wpCookies.remove("charitable_panel_layout_options_tabs_tab_open_design");
        wpCookies.remove("charitable_panel_layout_options_tabs_tab_open_settings");
        wpCookies.remove("charitable_panel_layout_options_tabs_tab_open_marketing");
        wpCookies.remove("charitable_panel_layout_options_tabs_tab_open_payment");
        wpCookies.remove("charitable_panel_content_section");
        wpCookies.remove("charitable_panel_active_field_id");
        wpCookies.remove("charitable_panel_design_layout_options_group");

      },

      isCampaignView: function () {

        if ( window.location.search.includes("view=design") ) {
          return true;
        }

        return false;

      },

      /**
       * Setup the tour steps.
       *
       * @since 1.8.1.15
       */
      setupTour: function () {

        const tour = new Shepherd.Tour({
          defaultStepOptions: {
            cancelIcon: {
              enabled: true,
            },
            exitOnEsc: true,
            classes: "",
            classPrefix: "wpchar",
            scrollTo: { behavior: "smooth", block: "center" },
            when: {
              show() {
                const footer = $(".shepherd-footer"),
                  currentStep = tour?.getCurrentStep(),
                  currentStepNumber = tour?.steps.indexOf(currentStep) + 1,
                  totalSteps = tour?.steps.length;

                if (
                  currentStepNumber === 1 ||
                  currentStepNumber === totalSteps
                ) {
                  return;
                }

                var progressPercentage = (currentStepNumber / totalSteps) * 100;

                if (progressPercentage > 100) {
                  progressPercentage = 100;
                }

                // insert HTML into footer that is a progress bar showing % of current/completed steps.
                footer.after(
                  '<span class="charitable-tour-progress-bar"><span class="charitable-tour-progress" style="width: ' +
                    progressPercentage +
                    '%"></span></span>',
                );

                if ( currentStepNumber > 2 ) {
                  // remove .shshepherd-target class from the target element.
                  $(".shepherd-target").removeClass("shepherd-target");
                }

              },
            },
          },
          tourName: "wpchar-visual-campaign-builder",
          useModalOverlay: true,
          modalContainer: document.getElementById("charitable-builder"),
          stepContainer: document.getElementById("charitable-builder"),
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-0",
          text: charitable_builder.onboarding_tour.step_0_text,
          arrow: false,
          cancelIcon: {
            enabled: true,
          },
          classes: "wpchar-visual-campaign-builder-step-0",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.start_tour,
              classes: "charitable-tour-btn-primary",
              action: tour.next,
            },
            // {
            //   text: charitable_builder.onboarding_tour.watch_video,
            //   classes: "charitable-tour-btn-primary",
            //   action: function () {
            //     app.openVideo();
            //   },
            // },
          ],
        });

        const name_step = tour.addStep({
          id: "wpchar-visual-campaign-builder-step-1",
          title: charitable_builder.onboarding_tour.step_1_title,
          text: charitable_builder.onboarding_tour.step_1_text,
          attachTo: {
            element: "#charitable_settings_title",
            on: "bottom",
          },
          classes: "wpchar-visual-campaign-builder-step-1",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes:
                "charitable-tour-btn-primary charitable-tour-btn-disabled",
              action: tour.next,
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-2",
          title: charitable_builder.onboarding_tour.step_2_title,
          text: charitable_builder.onboarding_tour.step_2_text,
          arrow: true,
          modalOverlayOpeningPadding: 8,
          attachTo: {
            element:
              ".charitable-template-animal-sanctuary div.charitable-template",
            on: "right",
          },
          classes: "wpchar-visual-campaign-builder-step-2",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.choose_a_template,
              classes: "charitable-tour-btn-primary",
              action: tour.hide,
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-3",
          title: charitable_builder.onboarding_tour.step_3_title,
          text: charitable_builder.onboarding_tour.step_3_text,
          arrow: true,
          modalOverlayOpeningPadding: 5,

          attachTo: {
            element: "#add-layout",
            on: "right-end",
          },
          classes: "wpchar-visual-campaign-builder-step-3",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: tour.next,
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-4",
          title: charitable_builder.onboarding_tour.step_4_title,
          text: charitable_builder.onboarding_tour.step_4_text,
          arrow: true,
          modalOverlayOpeningPadding: 5,
          attachTo: {
            element: "#charitable-tour-block-1",
            on: "left",
          },
          classes: "wpchar-visual-campaign-builder-step-4",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: tour.next,
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-5",
          title: charitable_builder.onboarding_tour.step_5_title,
          text: charitable_builder.onboarding_tour.step_5_text,
          arrow: true,
          modalOverlayOpeningPadding: 5,
          attachTo: {
            element: "#charitable-tour-block-2",
            on: "left",
          },
          classes: "wpchar-visual-campaign-builder-step-5",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: tour.next,
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-6",
          title: charitable_builder.onboarding_tour.step_6_title,
          text: charitable_builder.onboarding_tour.step_6_text,
          arrow: true,
          modalOverlayOpeningPadding: 5,
          attachTo: {
            element: "#charitable-tour-block-3",
            on: "left",
          },
          classes: "wpchar-visual-campaign-builder-step-6",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: tour.next,
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-7",
          title: charitable_builder.onboarding_tour.step_7_title,
          text: charitable_builder.onboarding_tour.step_7_text,
          arrow: true,
          modalOverlayOpeningPadding: 5,
          attachTo: {
            element: "#charitable-save",
            on: "top-end",
          },
          classes: "wpchar-visual-campaign-builder-step-7",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                app.gotoDraftPublishStep();
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-8",
          title: charitable_builder.onboarding_tour.step_8_title,
          text: charitable_builder.onboarding_tour.step_8_text,
          arrow: true,
          modalOverlayOpeningPadding: 10,

          attachTo: {
            element: "#charitable-tour-block-4",
            on: "left-start",
          },
          classes: "wpchar-visual-campaign-builder-step-8",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                app.undoDraftPublishStep();
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-9",
          title: charitable_builder.onboarding_tour.step_9_title,
          text: charitable_builder.onboarding_tour.step_9_text,
          arrow: true,
          modalOverlayOpeningPadding: 3,

          attachTo: {
            element: "#charitable-preview-btn",
            on: "top",
          },
          classes: "wpchar-visual-campaign-builder-step-9",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-10",
          title: charitable_builder.onboarding_tour.step_10_title,
          text: charitable_builder.onboarding_tour.step_10_text,
          arrow: true,
          modalOverlayOpeningPadding: 3,

          attachTo: {
            element: "#charitable-view-btn",
            on: "top",
          },
          classes: "wpchar-visual-campaign-builder-step-10",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-11",
          title: charitable_builder.onboarding_tour.step_11_title,
          text: charitable_builder.onboarding_tour.step_11_text,
          arrow: true,
          modalOverlayOpeningPadding: 3,

          attachTo: {
            element: "#charitable-embed",
            on: "top",
          },
          classes: "wpchar-visual-campaign-builder-step-11",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                app.gotoSettingsStep();
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-12",
          title: charitable_builder.onboarding_tour.step_12_title,
          text: charitable_builder.onboarding_tour.step_12_text,
          arrow: true,
          modalOverlayOpeningPadding: 0,

          attachTo: {
            element: "#charitable-panel-settings .charitable-panel-sidebar",
            on: "right",
          },
          classes: "wpchar-visual-campaign-builder-step-12",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                CharitableCampaignBuilder.panelSwitch("design");
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-13",
          title: charitable_builder.onboarding_tour.step_13_title,
          text: charitable_builder.onboarding_tour.step_13_text,
          arrow: false,
          classes: "wpchar-visual-campaign-builder-step-13",
          cancelIcon: {
            enabled: false,
          },
          buttons: [
            {
              text: charitable_builder.onboarding_tour.lets_get_started,
              classes: "charitable-tour-btn-primary",
              action: tour.next,
            },
          ],
        });

        tour.start();
      },

      setupTourOnboarding: function () {

        const tour = new Shepherd.Tour({
          defaultStepOptions: {
            cancelIcon: {
              enabled: true,
            },
            exitOnEsc: true,
            classes: "",
            classPrefix: "wpchar",
            scrollTo: { behavior: "smooth", block: "center" },
            when: {
              show() {
                const footer = $(".shepherd-footer"),
                  currentStep = tour?.getCurrentStep(),
                  currentStepNumber = tour?.steps.indexOf(currentStep) + 1,
                  totalSteps = tour?.steps.length;

                if (
                  currentStepNumber === 1 ||
                  currentStepNumber === totalSteps
                ) {
                  return;
                }

                var progressPercentage = (currentStepNumber / totalSteps) * 100;

                if (progressPercentage > 100) {
                  progressPercentage = 100;
                }

                // insert HTML into footer that is a progress bar showing % of current/completed steps.
                footer.after(
                  '<span class="charitable-tour-progress-bar"><span class="charitable-tour-progress" style="width: ' +
                    progressPercentage +
                    '%"></span></span>',
                );

                if ( currentStepNumber > 2 ) {
                  // remove .shshepherd-target class from the target element.
                  $(".shepherd-target").removeClass("shepherd-target");
                }

              },
            },
          },
          tourName: "wpchar-visual-campaign-builder",
          useModalOverlay: true,
          modalContainer: document.getElementById("charitable-builder"),
          stepContainer: document.getElementById("charitable-builder"),
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-0",
          text: charitable_builder.onboarding_tour.step_0_text,
          arrow: false,
          cancelIcon: {
            enabled: true,
          },
          classes: "wpchar-visual-campaign-builder-step-0",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.start_tour,
              classes: "charitable-tour-btn-primary",
              action: tour.next,
            },
            // {
            //   text: charitable_builder.onboarding_tour.watch_video,
            //   classes: "charitable-tour-btn-primary",
            //   action: function () {
            //     app.openVideo();
            //   },
            // },
          ],
        });

        // assign a variable true if "view" in the querystring is 'design'.
        var isDesignView = window.location.search.includes("view=design");

        const name_step = tour.addStep({
          id: "wpchar-visual-campaign-builder-step-1",
          title: charitable_builder.onboarding_tour.step_1_title_onboarding,
          text: charitable_builder.onboarding_tour.step_1_text_onboarding,
          attachTo: {
            element: "#charitable_settings_title",
            on: "bottom",
          },
          classes: "wpchar-visual-campaign-builder-step-1",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: isDesignView ? "charitable-tour-btn-primary" : "charitable-tour-btn-primary charitable-tour-btn-disabled",
              action: tour.next,
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-4",
          title: charitable_builder.onboarding_tour.step_4_title,
          text: charitable_builder.onboarding_tour.step_4_text,
          arrow: true,
          modalOverlayOpeningPadding: 5,
          attachTo: {
            element: "#charitable-tour-block-1",
            on: "left",
          },
          classes: "wpchar-visual-campaign-builder-step-4",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: tour.next,
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-5",
          title: charitable_builder.onboarding_tour.step_5_title,
          text: charitable_builder.onboarding_tour.step_5_text,
          arrow: true,
          modalOverlayOpeningPadding: 5,
          attachTo: {
            element: "#charitable-tour-block-2",
            on: "left",
          },
          classes: "wpchar-visual-campaign-builder-step-5",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: tour.next,
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-6",
          title: charitable_builder.onboarding_tour.step_6_title,
          text: charitable_builder.onboarding_tour.step_6_text,
          arrow: true,
          modalOverlayOpeningPadding: 5,
          attachTo: {
            element: "#charitable-tour-block-3",
            on: "left",
          },
          classes: "wpchar-visual-campaign-builder-step-6",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: tour.next,
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-7",
          title: charitable_builder.onboarding_tour.step_7_title,
          text: charitable_builder.onboarding_tour.step_7_text,
          arrow: true,
          modalOverlayOpeningPadding: 5,
          attachTo: {
            element: "#charitable-save",
            on: "top-end",
          },
          classes: "wpchar-visual-campaign-builder-step-7",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                app.gotoDraftPublishStep();
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-8",
          title: charitable_builder.onboarding_tour.step_8_title,
          text: charitable_builder.onboarding_tour.step_8_text,
          arrow: true,
          modalOverlayOpeningPadding: 10,

          attachTo: {
            element: "#charitable-tour-block-4",
            on: "left-start",
          },
          classes: "wpchar-visual-campaign-builder-step-8",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                app.undoDraftPublishStep();
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-9",
          title: charitable_builder.onboarding_tour.step_9_title,
          text: charitable_builder.onboarding_tour.step_9_text,
          arrow: true,
          modalOverlayOpeningPadding: 3,

          attachTo: {
            element: "#charitable-preview-btn",
            on: "top",
          },
          classes: "wpchar-visual-campaign-builder-step-9",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-10",
          title: charitable_builder.onboarding_tour.step_10_title,
          text: charitable_builder.onboarding_tour.step_10_text,
          arrow: true,
          modalOverlayOpeningPadding: 3,

          attachTo: {
            element: "#charitable-view-btn",
            on: "top",
          },
          classes: "wpchar-visual-campaign-builder-step-10",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-11",
          title: charitable_builder.onboarding_tour.step_11_title,
          text: charitable_builder.onboarding_tour.step_11_text,
          arrow: true,
          modalOverlayOpeningPadding: 3,

          attachTo: {
            element: "#charitable-embed",
            on: "top",
          },
          classes: "wpchar-visual-campaign-builder-step-11",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                app.gotoSettingsStep();
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-12",
          title: charitable_builder.onboarding_tour.step_12_title,
          text: charitable_builder.onboarding_tour.step_12_text,
          arrow: true,
          modalOverlayOpeningPadding: 0,

          attachTo: {
            element: "#charitable-panel-settings .charitable-panel-sidebar",
            on: "right",
          },
          classes: "wpchar-visual-campaign-builder-step-12",
          buttons: [
            {
              text: charitable_builder.onboarding_tour.next,
              classes: "charitable-tour-btn-primary",
              action: function () {
                CharitableCampaignBuilder.panelSwitch("design");
                tour.next();
              },
            },
          ],
        });

        tour.addStep({
          id: "wpchar-visual-campaign-builder-step-13",
          title: charitable_builder.onboarding_tour.step_13_title,
          text: charitable_builder.onboarding_tour.step_13_text,
          arrow: false,
          classes: "wpchar-visual-campaign-builder-step-13",
          cancelIcon: {
            enabled: false,
          },
          buttons: [
            {
              text: charitable_builder.onboarding_tour.lets_get_started,
              classes: "charitable-tour-btn-primary",
              action: tour.next,
            },
          ],
        });

        tour.start();
      },

      /**
       * Register JS events.
       *
       * @since 1.8.1.15
       * @version 1.8.4.3 resolved TypeError, added checks.
       */
      events: function () {
        $("a.create-campaign").on("blur", function () {
          $(document).trigger("select-template");
        });

        $(document).on("charitableEditorScreenStart", function () {
          // wait 2 seconds for the fields to load
          setTimeout(function () {
            if (Shepherd.activeTour && typeof Shepherd.activeTour.next === 'function') {
              // Call the next() function if it exists and is a function
              Shepherd.activeTour.next();
            } else {
              // Shepherd.activeTour or Shepherd.activeTour.next is not available.
            }
          }, 500);
        });

        $(document).on("enter-campaign-name", () => {
          if ( app.isCampaignView() ) {
            return;
          }
          if (name_step.isOpen()) {
            // Shepherd.activeTour.next();
            $(".charitable-tour-btn-primary").removeClass(
              "charitable-tour-btn-disabled",
            );
          }
        });

        $("#charitable_settings_title").on("input", function () {
          if ( app.isCampaignView() ) {
            return;
          }
          if ($(this).val().length >= 5) {
            // $( document ).trigger( 'enter-campaign-name' );
            $(".charitable-tour-btn-primary").removeClass(
              "charitable-tour-btn-disabled",
            );
          } else {
            $(".charitable-tour-btn-primary").addClass(
              "charitable-tour-btn-disabled",
            );
          }
        });

        ["complete", "cancel"].forEach((event) =>
          Shepherd.on(event, () => {

            // Remove any 'charitable-tour-block' elements from the page so the user can click through them.
            $(".charitable-tour-block").remove();

            // if the tour is completed or cancelled, update the option in the database.
            var typeData = {
              status: event === "complete" ? "completed" : "skipped",
            };

            var data = {
              type: "tour",
              optionData: typeData,
            };

            var ajaxData = {
              action: "charitable_onboarding_tour_save_option",
              dataType: "json",
              data: data,
              nonce: charitable_admin_builder_onboarding.nonce,
            };

            $.post(charitable_builder.ajax_url, ajaxData, function (response) {
              if (response.success) {
              }
            })
              .fail(function (xhr, textStatus, e) {
                // eslint-disable-line


              })
              .always(function () {});
          }),
        );
      },

      /**
       * Open the video iframe on the first step.
       *
       * @since 1.8.1.15
       *
       * @return {void}
       */
      openVideo: function () {
        $("#charitable-tour-video").html(
          '<iframe width="560" height="315" src="https://www.youtube.com/embed/834h3huzzk8?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
        );
      },

      /**
       * Simulate the dropdown, etc.
       *
       * @since 1.8.1.15
       *
       * @param {object} e Event object.
       */
      gotoDraftPublishStep: function (e) {
        // eslint-disable-line

        var $dropdown = $("#charitable-status-button");

        $dropdown
          .parent()
          .find("ul#charitable-status-dropdown")
          .removeClass("charitable-hidden");
        $dropdown.addClass("active");
      },

      /**
       * Simulate the dropdown, etc.
       *
       * @since 1.8.1.15
       *
       * @param {object} e Event object.
       */
      undoDraftPublishStep: function (e) {
        // eslint-disable-line

        var $dropdown = $("#charitable-status-button");

        $dropdown
          .parent()
          .find("ul#charitable-status-dropdown")
          .addClass("charitable-hidden");
        $dropdown.removeClass("active");
      },

      /**
       * Switch panel to settings.
       *
       * @since 1.8.1.15
       *
       * @param {object} e Event object.
       */
      gotoSettingsStep: function (e) {
        // eslint-disable-line

        CharitableCampaignBuilder.panelSwitch("settings");
      },
    };

    // Provide access to public functions/properties.
    return app;
  })(document, window, jQuery); // eslint-disable-line

// Initialize.
CharitableTour.init();
