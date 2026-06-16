/* global charitable_reporting, wpchar, wpCookies */
// eslint-disable-line no-unused-vars

var CharitableAdminReporting = window.CharitableAdminReporting || (function (document, window, $) {

    var s = {},
        $reports,
        showGrowthToolsNotice = false,
        elements = {},
        charts = {};

    var app = {

        settings: {
            clickWatch: false
        },

		/**
		 * Start the engine.
		 *
		 * @since 1.8.1
		 */
        init: function () {

            s = this.settings;

            // Document ready.
            $(app.ready);

        },

		/**
		 * Document ready.
		 *
		 * @since 1.8.1
		 *
		 */
        ready: function () { // check to see if javascript has been defined.

            // check to see if charitable_reporting has been defined.
            if ( typeof charitable_reporting === 'undefined' ) {
                return;
            }

            wpchar.debug('charitable_reporting');
            wpchar.debug(charitable_reporting);

            $reports = $('#charitable-reports');

            if ( $reports.length === 0 ) {
                return;
            }

            // UI elements.
            elements.$start_datepicker       = $( '#charitable-reports-start_date' );
            elements.$end_datepicker         = $( '#charitable-reports-end_date' );
            elements.$filter_button          = $( '#charitable-reports-filter-button');
            elements.$report_campaign_filter = $('#report-campaign-filter');
            elements.$report_category_filter = $('#report-category-filter');

            // Data containers.
            elements.$top_donation_amount                = $('#charitable-top-donation-total-amount');
            elements.$top_donation_count                 = $('#charitable-top-donation-total-count');
            elements.$top_donation_average               = $('#charitable-top-donation-average');
            elements.$top_donation_donors_count          = $('#charitable-top-donor-count');
            elements.$top_charitable_refund_total_amount = $('#charitable-top-refund-total-amount');
            elements.$top_charitable_refund_count        = $('#charitable-top-refund-count');
            elements.$top_donors_list                    = $('#charitable-top-donors-list');
            elements.$donations_breakdown_list           = $('#donations-breakdown-list');
            elements.$payment_methods_list               = $('ul#charitable-payment-methods-list');
            elements.$activity_list                      = $('#charitable-activity-list');
            elements.$top_campaigns_list                 = $('#charitable-top-campaigns-list');
            elements.$report_date_range_filter           = $('#report-date-range-filter');
            elements.$topnav_datepicker                  = $('#charitable-reports-topnav-datepicker');
            elements.$topnav_datepicker_comparefrom      = $('#charitable-reports-topnav-datepicker-comparefrom-lybunt');
            elements.$topnav_datepicker_compareto        = $('#charitable-reports-topnav-datepicker-compareto-lybunt');

            s.datePickerStartDate = '';
            s.datePickerEndDate   = '';

            s.datePickerCompareFromStartDate = '';
            s.datePickerCompareFromEndDate   = '';
            s.datePickerCompareToStartDate   = '';
            s.datePickerCompareToEndDate     = '';


            if ( app.isAdvancedPage() ) {
                app.initDatePickerRanges( 'ranged' );
            } else {
                app.initDatePickerRanges( '' );
            }

            app.initDatePicker();

            // Bind all actions.
            app.bindUIActions();

            app.initCharts();
            app.renderCharts();

            app.checkCategoryDropdown();

            app.updateDatePickerVars();

            app.initPagination();

        },

		/**
		 * Updates the datepicker vars.
		 *
		 * @since 1.8.1
		 *
		 */
        updateDatePickerVars: function () {

          if ( $reports.find('input.charitable-datepicker-ranged').length === 0 ) {
            return;
          }

          var drp = $reports.find('input.charitable-datepicker-ranged').data('daterangepicker');

          s.datePickerStartDate   = ( drp.startDate._i  ) ? drp.startDate._i : '';
          s.datePickerEndDate     = ( drp.endDate._i    ) ? drp.endDate._i : '';

        },

        /**
         * Checks to see if the category dropdown should be enabled on the page (overview).
         *
         * @since 1.8.1
         *
         */
        checkCategoryDropdown: function () {

          if ( elements.$report_campaign_filter.val() === '-1' ) {
            elements.$report_category_filter.removeClass('charitable-disabled');
          } else {
            elements.$report_category_filter.val('-1').addClass('charitable-disabled');
          }

        },

        /**
         * Inits the date picker ranges, for when that UI needs to be initialized.
         *
         * @since 1.8.1
         *
         */
        initDatePickerRanges: function ( pickerType = 'ranged' ) {

            // Determine args for datepickers on various report pages.
            if ( pickerType === 'ranged' ) {

                // if moment is defined, use it to set the datepicker ranges.
                if ( typeof moment !== 'undefined' ) {

                  s.ranges = {
                    'This Year': [
                        moment().startOf('year'), moment().endOf('year') // eslint-disable-line
                    ],
                    'Last Year': [
                        moment().subtract(1, 'year').startOf('year'), // eslint-disable-line
                        moment().subtract(1, 'year').endOf('year') // eslint-disable-line
                    ],
                    'This Month': [
                        moment().startOf('month'), moment().endOf('month') // eslint-disable-line
                    ],
                    'Last Month': [
                        moment().subtract(1, 'month').startOf('month'), // eslint-disable-line
                        moment().subtract(1, 'month').endOf('month') // eslint-disable-line
                    ]
                  }

                }

              } else { // default to overview page.

                if ( typeof moment !== 'undefined' ) {

                  s.ranges = {
                    'Today': [
                        moment(), moment() // eslint-disable-line
                    ],
                    'Yesterday': [
                        moment().subtract(1, 'days'), // eslint-disable-line
                        moment().subtract(1, 'days') // eslint-disable-line
                    ],
                    'Last 7 Days': [
                        moment().subtract(6, 'days'), // eslint-disable-line
                        moment() // eslint-disable-line
                    ],
                    'Last 14 Days': [
                      moment().subtract(13, 'days'), // eslint-disable-line
                      moment() // eslint-disable-line
                    ],
                    'Last 30 Days': [
                        moment().subtract(29, 'days'), // eslint-disable-line
                        moment() // eslint-disable-line
                    ],
                    'This Month': [
                        moment().startOf('month'), moment().endOf('month') // eslint-disable-line
                    ],
                    'Last Month': [
                        moment().subtract(1, 'month').startOf('month'), // eslint-disable-line
                        moment().subtract(1, 'month').endOf('month') // eslint-disable-line
                    ]
                  };

                }

              }

        },

        /**
         * Inits the date picker for most standard datepicker UIs.
         *
         * @since 1.8.1
         *
         */
        initDatePicker: function ( datepickerClass = 'ranged', datepickerObject = false ) {

            if ( datepickerObject ) {

                var $the_datepicker = $reports.find( datepickerObject );

                $the_datepicker.daterangepicker({
                    opens: 'left',
                    locale: {
                        format: 'Y/M/DD'
                    },
                    linkedCalendars: true,
                    showCustomRangeLabel: true,
                    alwaysShowCalendars: true,
                    ranges: s.ranges

                }, function  ( start, end, label ) { // eslint-disable-line

                    wpchar.debug('first');
                    wpchar.debug( $the_datepicker );

                    $the_datepicker.attr('data-start-date', start.format('Y/M/DD') );
                    $the_datepicker.attr('data-end-date', end.format('Y/M/DD') );

                    wpchar.debug( $the_datepicker );

                    s.datePickerStartDate = start.format('Y/M/DD');
                    s.datePickerEndDate = end.format('Y/M/DD');

                    wpchar.debug( app.getStartDateFromDatePicker() );
                    wpchar.debug( app.getEndDateFromDatePicker() );
                    wpchar.debug( elements.$topnav_datepicker );

                    wpchar.debug("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
                });

            } else if ( datepickerClass === 'ranged' ) {

                $reports.find('input.charitable-datepicker-' + datepickerClass).daterangepicker({
                    opens: 'left',
                    locale: {
                        format: 'Y/M/DD'
                    },
                    linkedCalendars: true,
                    showCustomRangeLabel: true,
                    alwaysShowCalendars: true,
                    ranges: s.ranges

                }, function  ( start, end, label ) { // eslint-disable-line
                    elements.$topnav_datepicker.attr('data', 'start-date').val( start.format('Y/M/DD') );
                    elements.$topnav_datepicker.attr('data', 'end-date').val( end.format('Y/M/DD') );

                    s.datePickerStartDate = start.format('Y/M/DD');
                    s.datePickerEndDate = end.format('Y/M/DD');

                    wpchar.debug( app.getStartDateFromDatePicker() );
                    wpchar.debug( app.getEndDateFromDatePicker() );
                    wpchar.debug( elements.$topnav_datepicker );

                    wpchar.debug("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
                });

                $reports.find('input#charitable-reports-topnav-datepicker-comparefrom-lybunt.charitable-datepicker-' + datepickerClass).daterangepicker({
                    opens: 'left',
                    locale: {
                        format: 'Y/M/DD'
                    },
                    linkedCalendars: true,
                    showCustomRangeLabel: true,
                    alwaysShowCalendars: true,
                    ranges: s.ranges

                }, function  ( start, end, label ) { // eslint-disable-line
                    wpchar.debug('second');
                    wpchar.debug( start.format('Y/M/DD') );
                    wpchar.debug( end.format('Y/M/DD') );
                    elements.$topnav_datepicker_comparefrom.attr('data-start-date', start.format('Y/M/DD') );
                    elements.$topnav_datepicker_comparefrom.attr('data-end-date', end.format('Y/M/DD') );

                    s.datePickerCompareFromStartDate = start.format('Y/M/DD');
                    s.datePickerCompareFromEndDate = end.format('Y/M/DD');

                    wpchar.debug( app.getStartDateFromDatePicker() );
                    wpchar.debug( app.getEndDateFromDatePicker() );
                    wpchar.debug( elements.$topnav_datepicker_comparefrom );
                    wpchar.debug( this );
                    wpchar.debug( label );

                    wpchar.debug("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
                });

                $reports.find('input#charitable-reports-topnav-datepicker-compareto-lybunt.charitable-datepicker-' + datepickerClass).daterangepicker({
                    opens: 'left',
                    locale: {
                        format: 'Y/M/DD'
                    },
                    linkedCalendars: true,
                    showCustomRangeLabel: true,
                    alwaysShowCalendars: true,
                    ranges: s.ranges

                }, function  ( start, end, label ) { // eslint-disable-line
                    wpchar.debug('second');
                    wpchar.debug( start.format('Y/M/DD') );
                    wpchar.debug( end.format('Y/M/DD') );
                    elements.$topnav_datepicker_compareto.attr('data-start-date', start.format('Y/M/DD') );
                    elements.$topnav_datepicker_compareto.attr('data-end-date', end.format('Y/M/DD') );

                    s.datePickerCompareToStartDate = start.format('Y/M/DD');
                    s.datePickerCompareToEndDate = end.format('Y/M/DD');

                    wpchar.debug( app.getStartDateFromDatePicker() );
                    wpchar.debug( app.getEndDateFromDatePicker() );
                    wpchar.debug( elements.$topnav_datepicker_comparefrom );
                    wpchar.debug( this );
                    wpchar.debug( label );

                    wpchar.debug("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
                });

            } else {

                wpchar.debug('using datepicker class: ' + $( 'input.' + datepickerClass ).val() );

                var $the_datepicker = $reports.find( 'input.' + datepickerClass ); // eslint-disable-line

                $the_datepicker.daterangepicker({
                    opens: 'left',
                    locale: {
                        format: 'Y/M/DD'
                    },
                    linkedCalendars: true,
                    showCustomRangeLabel: true,
                    alwaysShowCalendars: true,
                    ranges: s.ranges

                }, function  ( start, end, label ) { // eslint-disable-line

                    wpchar.debug('third');
                    wpchar.debug( $the_datepicker );

                    $the_datepicker.attr('data', 'start-date').val( start.format('Y/M/DD') );
                    $the_datepicker.attr('data', 'end-date').val( end.format('Y/M/DD') );

                    s.datePickerStartDate = start.format('Y/M/DD');
                    s.datePickerEndDate = end.format('Y/M/DD');

                    wpchar.debug( app.getStartDateFromDatePicker() );
                    wpchar.debug( app.getEndDateFromDatePicker() );
                    wpchar.debug( elements.$topnav_datepicker );

                    wpchar.debug("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
                });

            }

        },

		/**
		 * Element bindings.
		 *
		 * @since 1.8.1
		 */
        bindUIActions: function () {

            $reports.on('click', '.charitable-toggle', function (e) {
                e.preventDefault();
                // remove the focus from the button.
                $(this).blur();
                app.fieldSectionToggle($(this), 'click');
            });

            // Init all date pickers on the page.
            $reports.find('.charitable-report-download-items').each(function () {
                app.initDownloadButton($(this));
            });

            $reports.on('click', '#charitable-reports-filter-button[data-filter-type="overview"]', function (e) { // eslint-disable-line
                e.preventDefault();

                wpchar.debug('overview filter button clicked');

                // Update the "print" button hidden field with the new value of the dropdown.
                var campaign_id = parseInt( $('#report-campaign-filter').val() ) || -1;
                $reports.find('form#charitable-overview-print input[name="campaign_id"]').val( campaign_id );
                var category_id = parseInt( $('#report-category-filter').val() ) || 0;
                $reports.find('form#charitable-overview-print input[name="category_id"]').val( category_id );

                var start_date = app.getStartDateFromDatePicker();
                var end_date = app.getEndDateFromDatePicker();

                // if charitable-reports-topnav-datepicker exists, update the data attributes with the new dates.
                if ( elements.$topnav_datepicker.length > 0 ) {
                    elements.$topnav_datepicker.attr('data-start-date', start_date );
                    elements.$topnav_datepicker.attr('data-end-date', end_date );
                }
                // Update the "print" button hidden field with the new value of the dropdown.
                $reports.find('form#charitable-overview-print input[name="start_date"]').val( start_date );
                $reports.find('form#charitable-overview-print input[name="end_date"]').val( end_date );

                wpchar.debug( 'start_date: ' + start_date );
                wpchar.debug( 'end_date: ' + end_date );

                app.getOverviewReports();
            });

            /* advanced reports */

            // selecting a value in the advanced report type dropdown.
            $reports.on('change', '#report-advanced-type-filter', function (e) { // eslint-disable-line
                e.preventDefault();
                app.loadAdvancedReportUI( $(this).val() );
              });

            // clicking of the filter button.
            $reports.on('click', '.charitable-advanced-filter-button a.button', function (e) { // eslint-disable-line
              e.preventDefault();
              app.getAdvancedReports( $( this ) );
            });

            $reports.on('click', '#charitable-reports-filter-button[data-filter-type="activity"]', function (e) { // eslint-disable-line
              e.preventDefault();
              app.getActivityReports();
            });

            $reports.on('click', '#charitable-reports-filter-button[data-filter-type="donor"]', function (e) { // eslint-disable-line
              e.preventDefault();
              app.getDonorReports();
            });

            $reports.on('change', '#report-campaign-filter', function (e) { // eslint-disable-line
              // e.preventDefault();
              app.checkCategoryDropdown();
            });

            /* dhashboard */

            if ( app.isDashboardPage() ) {

              $reports.on('change', '#report-date-range-filter', function (e) { // eslint-disable-line
                e.preventDefault();

                // Update the "print" button hidden field with the new value of the dropdown.
                $reports.find('form#charitable-dashboard-print input[name="days"]').val( $(this).val() );

                app.getDashboardReports();
              });

            }

        },

        /**
         * Toggle field group visibility in the field sidebar.
         *
         * @since 1.8.1
         *
         * @param {mixed}  el     DOM element or jQuery object.
         * @param {string} action Action.
         */
        fieldSectionToggle: function (el, action) {

            var $this = $(el),
                $nearestContainer = $this.closest('.charitable-container'),
                $toggleGroup = $nearestContainer.find('.charitable-toggle-container'),
                sectionName = $nearestContainer.data('section-name'),
                $icon = $this.find('i'),
                cookieName = 'charitable_reports_section_' + sectionName;

            if (action === 'click') {

                $icon.toggleClass('charitable-angle-right');

                $toggleGroup.stop().slideToggle('', function () {
                    $nearestContainer.toggleClass('charitable-closed');
                    if ($nearestContainer.hasClass('charitable-closed')) {
                        $nearestContainer.removeClass('charitable-open');
                        wpCookies.remove(cookieName);
                    } else {
                        wpCookies.set(cookieName, 'true', 2592000); // 1 month
                    }
                });

                return;
            }

        },

        /* dynamic UI updates */

		/**
		 * This loads the UI/data for advanced reports, usually when a filter has been applied on the page.
		 *
		 * @since 1.8.1
         *
         * @param {string} slug The slug of the advanced report.
         *
		 */
        loadAdvancedReportUI: function ( slug = '' ) {

            wpchar.debug( charitable_reporting );
            wpchar.debug( charitable_reporting.advanced_reports[slug].title );
            wpchar.debug( slug );

            // show the appropriate advanced report UI (date pickers and filter buttons).
            $reports.find('.charitable-advanced-date-picker, .charitable-advanced-filter-button').addClass('charitable-hidden');
            $reports.find('.charitable-advanced-date-picker[data-report-type="' + slug + '"]').removeClass('charitable-hidden');
            $reports.find('.charitable-advanced-filter-button[data-report-type="' + slug + '"]').removeClass('charitable-hidden');

            app.disableUI();
            app.reportUILoadingOn();

            var data = {
                action:                  'charitable_report_advanced_ui',
                nonce:                   charitable_reporting.nonce,
                report_type:             slug,
                report_title:            charitable_reporting.advanced_reports[slug].title,
                report_description:      charitable_reporting.advanced_reports[slug].description,
                start_date:              s.datePickerStartDate, // this would be a default date?
                end_date:                s.datePickerEndDate // this would be a default date?
            };

            $.post( charitable_reporting.ajax_url, data, function (response) {

                if (response.success) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                      wpchar.debug('response success');

                      // use slug as a dynamic variable to access the charitable_reporting object.
                      if ( typeof charitable_reporting.advanced_reports[slug] !== 'undefined' ) {
                          // update the title and description of the title cards, if the content exists in the localized strings JS in the footer.
                          $reports.find('.charitable-title-card-content h2').html( charitable_reporting.advanced_reports[slug].title );
                          $reports.find('.charitable-title-card-content div.charitable-report-description').html( charitable_reporting.advanced_reports[slug].description );
                      }

                      if ( response.data.html ) {
                          $reports.find('#charitable-report-advanced-container').html( response.data.html );
                      } else {
                          $reports.find('#charitable-report-advanced-container').html( '' );
                      }

                      app.enableUI();
                      app.reportUILoadingOff();

                }

            });

            // init the date pickers for the advanced report UI.
            app.initDatePicker( false, '.charitable-advanced-date-picker[data-report-type="' + slug + '"] input.charitable-reports-datepicker' );

            // populate the start/end date vars.
            if ( slug !== 'lybunt' ) {
                s.datePickerStartDate = $reports.find('.charitable-advanced-date-picker[data-report-type="' + slug + '"]').find('input.charitable-reports-datepicker').data('start-date');
                s.datePickerEndDate   = $reports.find('.charitable-advanced-date-picker[data-report-type="' + slug + '"]').find('input.charitable-reports-datepicker').data('end-date');
            } else {
                s.datePickerCompareFromStartDate = $reports.find('.charitable-advanced-date-picker[data-report-type="' + slug + '"]').find('input#charitable-reports-topnav-datepicker-comparefrom-lybunt').data('start-date');
                s.datePickerCompareFromEndDate   = $reports.find('.charitable-advanced-date-picker[data-report-type="' + slug + '"]').find('input#charitable-reports-topnav-datepicker-comparefrom-lybunt').data('end-date');
                s.datePickerCompareToStartDate   = $reports.find('.charitable-advanced-date-picker[data-report-type="' + slug + '"]').find('input#charitable-reports-topnav-datepicker-compareto-lybunt').data('start-date');
                s.datePickerCompareToEndDate     = $reports.find('.charitable-advanced-date-picker[data-report-type="' + slug + '"]').find('input#charitable-reports-topnav-datepicker-compareto-lybunt').data('end-date');
            }

        },

		/**
		 * This laods the UI/data for dashboard reports, usually when a filter has been applied on the page.
		 *
		 * @since 1.8.1
         *
		 */
        getDashboardReports: function () {

          var data = {
              action:                  'charitable_report_dashboard_data',
              nonce:                   charitable_reporting.nonce,
              start_date:              app.getDashboardStartDate(),
              end_date:                app.getDashboardEndDate(),
              days:                    app.getDashboardDays()
          };

          app.disableUI();
          app.reportUILoadingOn();

          $.post( charitable_reporting.ajax_url, data, function (response) {

              if (response.success) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                    wpchar.debug('dashboard response success');

                    showGrowthToolsNotice = false;

                    if ( response.data.html.charitable_cards ) {
                        wpchar.debug( 'updating dashboard with: ' + response.data.html.charitable_cards );
                        $reports.find('#charitable-dashboard-report-cards').html( response.data.html.charitable_cards );
                    }

                    if ( response.data.html.charitable_reports ) {
                        wpchar.debug( 'updating dashboard with: ' + response.data.html.charitable_reports );
                        $reports.find('#charitable-dashboard-report-sections').html( response.data.html.charitable_reports );
                    }

                    // if form#charitable-dashboard-print exists, update the hidden fields with the new dates.
                    if ( $reports.find('form#charitable-dashboard-print').length > 0 ) {
                        $reports.find('form#charitable-dashboard-print input[name="start_date"]').val( response.data.start_date );
                        $reports.find('form#charitable-dashboard-print input[name="end_date"]').val( response.data.end_date );
                        $reports.find('form#charitable-dashboard-print input[name="days"]').val( response.data.days );
                    }

                    wpchar.debug( charts.headlineChart );
                    wpchar.debug( response.data.headline_chart_options.donation_axis );
                    wpchar.debug( response.data.headline_chart_options.date_axis );
                    // check and see if axis data is returned and if so, update the headline chart.
                    if ( charts.headlineChart && response.data.headline_chart_options.donation_axis && response.data.headline_chart_options.date_axis ) {
                        charts.headlineChart.updateOptions({
                            series: [
                                {
                                    name: 'Donations',
                                    data: response.data.headline_chart_options.donation_axis
                                }
                            ],
                            xaxis: {
                                categories: response.data.headline_chart_options.date_axis
                            }
                        });

                        // determine if the donation axis is all zeros.
                        var isAllZero = response.data.headline_chart_options.donation_axis.every( function ( val ) {
                            return val === 0;
                        });

                        // check to see if the donation axis is empty or has all zeros.
                        if ( response.data.headline_chart_options.donation_axis.length === 0 || isAllZero === false ) {
                            showGrowthToolsNotice = false;
                        } else{
                            showGrowthToolsNotice = true;
                        }
                        if ( showGrowthToolsNotice && $( '.charitable-growth-tools-notice' ).length ) {
                            $( '.charitable-growth-tools-notice' ).removeClass( 'charitable-hidden' );
                        } else {
                            $( '.charitable-growth-tools-notice' ).addClass( 'charitable-hidden' );
                        }
                    }

                  app.enableUI();
                  app.reportUILoadingOff();

              }

          });

        },

		/**
		 * This laods the UI/data for report:overview reports, usually when a filter has been applied on the page.
		 *
		 * @since 1.8.1
         *
		 */
        getOverviewReports: function () {

            var data = {
                action:               'charitable_report_overview_data',
                nonce:                charitable_reporting.nonce,
                start_date:           app.getStartDateFromDatePicker(),
                end_date:             app.getEndDateFromDatePicker(),
                campaign_id:          parseInt( $('#report-campaign-filter').val() ) || 0,
                campaign_category_id: parseInt( $('#report-category-filter').val() ) || 0,
                filter:               $('#report-date-range-filter').val()
            };

            app.disableUI();
            app.reportUILoadingOn();

            $.post(charitable_reporting.ajax_url, data, function (response) {

                if (response.success) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                    wpchar.debug('response success');

                    if ( response.data.html.donation_breakdown ) {
                        elements.$donations_breakdown_list.html(response.data.html.donation_breakdown);
                    }

                    // check and see if axis data is returned and if so, update the headline chart.
                    if ( charts.headlineChart && response.data.headline_chart_options.donation_axis && response.data.headline_chart_options.date_axis ) {
                        charts.headlineChart.updateOptions({
                            series: [
                                {
                                    name: 'Donations',
                                    data: response.data.headline_chart_options.donation_axis
                                }
                            ],
                            xaxis: {
                                categories: response.data.headline_chart_options.date_axis
                            }
                        });
                    }

                    if (response.data.donation_amount) {
                        elements.$top_donation_amount.html(response.data.donation_amount);
                    }

                    // if donation_count was returned, update the donation count.
                    if ( response.data.donation_count ) {
                        elements.$top_donation_count.html(response.data.donation_count);
                    } else {
                        elements.$top_donation_count.html('0');
                    }

                    if ( response.data.donation_average ) {
                        elements.$top_donation_average.html(response.data.donation_average);
                    } else {
                        elements.$top_donation_average.html('0');
                    }

                    // if donor_count was returned, update the donor count.
                    if ( response.data.donors_count ) {
                        elements.$top_donation_donors_count.html( response.data.donors_count );
                    } else {
                        elements.$top_donation_donors_count.html('0');
                    }

                    if ( response.data.refund_total_amount ) {
                        elements.$top_charitable_refund_total_amount.html(response.data.refund_total_amount);
                    }

                    if ( response.data.refund_count ) {
                        elements.$top_charitable_refund_count.html(response.data.refund_count);
                    } else {
                        elements.$top_charitable_refund_count.html('0');
                    }

                    if ( response.data.html.payment_methods_list ) {
                        elements.$payment_methods_list.html( response.data.html.payment_methods_list );
                    }

                    if ( charts.paymentMethodsChart && response.data.payment_methods_chart.payment_percentages && response.data.payment_methods_chart.payment_labels ) {
                        charts.paymentMethodsChart.updateOptions( { series: response.data.payment_methods_chart.payment_percentages, labels: response.data.payment_methods_chart.payment_labels } );
                    }

                    if ( response.data.html.activities ) {
                        wpchar.debug( 'updating activities with: ' + response.data.html.activities );
                        $reports.find('.charitable-activity-report .charitable-report-ui .the-list').html(response.data.html.activities);
                    }

                    if ( response.data.html.top_donors_list ) {
                        wpchar.debug( 'updating top donors with: ' + response.data.html.top_donors_list );
                        $reports.find('.charitable-top-donors-report .charitable-report-ui').html(response.data.html.top_donors_list);
                    }

                    if ( response.data.html.top_campaigns_list ) {
                        elements.$top_campaigns_list.html(response.data.html.top_campaigns_list);
                    }

                    // update the "download csv" form.
                    $reports.find('#charitable-donations-breakdown-download-form input[name="start_date"]').val( app.getStartDateFromDatePicker() );
                    $reports.find('#charitable-donations-breakdown-download-form input[name="end_date"]').val( app.getEndDateFromDatePicker() );
                    $reports.find('#charitable-donations-breakdown-download-form input[name="campaign_id"]').val( parseInt( $('#report-campaign-filter').val() ) );
                    $reports.find('#charitable-donations-breakdown-download-form input[name="category_id"]').val( parseInt( $('#report-category-filter').val() ) );

                    app.enableUI();
                    app.reportUILoadingOff();
                    app.checkCategoryDropdown();

                }

            });


        },

		/**
		 * This laods the UI/data for advanced reports, usually when a filter has been applied on the page.
		 *
		 * @since 1.8.1
         *
         * @param {string} button Button element clicked, which we use to get the report type.
         *
		 */
        getAdvancedReports: function ( $button = false ) {

            var report_type = $button.parent().data('report-type'),
                data = {};

            if ( 'lybunt' === report_type ) {

                var datePickerCompareFromStartDate  = $reports.find('.charitable-advanced-date-picker[data-report-type="' + report_type + '"]').find('input#charitable-reports-topnav-datepicker-comparefrom-lybunt').attr('data-start-date'),
                    datePickerCompareFromEndDate    = $reports.find('.charitable-advanced-date-picker[data-report-type="' + report_type + '"]').find('input#charitable-reports-topnav-datepicker-comparefrom-lybunt').attr('data-end-date'),
                    datePickerCompareToStartDate    = $reports.find('.charitable-advanced-date-picker[data-report-type="' + report_type + '"]').find('input#charitable-reports-topnav-datepicker-compareto-lybunt').attr('data-start-date'),
                    datePickerCompareToEndDate      = $reports.find('.charitable-advanced-date-picker[data-report-type="' + report_type + '"]').find('input#charitable-reports-topnav-datepicker-compareto-lybunt').attr('data-end-date');

                data = {
                        action:                  'charitable_report_advanced_data',
                        nonce:                   charitable_reporting.nonce,
                        report_type:             report_type,
                        compare_from_start_date: datePickerCompareFromStartDate,
                        compare_from_end_date:   datePickerCompareFromEndDate,
                        compare_to_start_date:   datePickerCompareToStartDate,
                        compare_to_end_date:     datePickerCompareToEndDate
                };

            } else {

                var start_date = s.datePickerStartDate, // $reports.find('.charitable-advanced-date-picker[data-report-type="' + report_type + '"]').find('input.charitable-reports-datepicker').data('start-date'),
                    end_date   = s.datePickerEndDate; // $reports.find('.charitable-advanced-date-picker[data-report-type="' + report_type + '"]').find('input.charitable-reports-datepicker').data('end-date');

                data = {
                        action:                  'charitable_report_advanced_data',
                        nonce:                   charitable_reporting.nonce,
                        report_type:             report_type,
                        start_date:              start_date,
                        end_date:                end_date
                };

            }

          app.disableUI();
          app.reportUILoadingOn();

          $.post( charitable_reporting.ajax_url, data, function (response) {

              if (response.success) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                    wpchar.debug('response success');

                    // use slug as a dynamic variable to access the charitable_reporting object.
                    if ( typeof charitable_reporting.advanced_reports[report_type] !== 'undefined' ) {
                        // update the title and description of the title cards, if the content exists in the localized strings JS in the footer.
                        $reports.find('.charitable-title-card-content h2').html( charitable_reporting.advanced_reports[report_type].title );
                        $reports.find('.charitable-title-card-content div.charitable-report-description').html( charitable_reporting.advanced_reports[report_type].description );
                    }

                    if ( response.data.html ) {
                        $reports.find('#charitable-report-advanced-container').html( response.data.html );
                    }

                    app.enableUI();
                    app.reportUILoadingOff();

              }

          });

        },

		/**
		 * This laods the UI/data for activity reports.
		 *
		 * @since 1.8.1
         *
		 */
        getActivityReports: function () {

          var data = {
              action:                  'charitable_report_activity_data',
              nonce:                   charitable_reporting.nonce,
              start_date:              app.getStartDateFromDatePicker(),
              end_date:                app.getEndDateFromDatePicker(),
              campaign_id:             parseInt( $('#report-campaign-filter').val() ),
              activity_type:           $('#report-activity-type-filter').val()
          };

          app.disableUI();
          app.reportUILoadingOn();

          $.post(charitable_reporting.ajax_url, data, function (response) {

              if (response.success) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                  wpchar.debug('response success');

                  if (response.data.html.activities) {
                    wpchar.debug( 'updating activities with: ' + response.data.html.activities );
                    $reports.find('.charitable-the-list-container').html(response.data.html.activities);
                  }

                  // update the "download csv" form.
                  $reports.find('#charitable-activity-download-form input[name="start_date"]').val( app.getStartDateFromDatePicker() );
                  $reports.find('#charitable-activity-download-form input[name="end_date"]').val( app.getEndDateFromDatePicker() );
                  $reports.find('#charitable-activity-download-form input[name="campaign_id"]').val( parseInt( $('#report-campaign-filter').val() ) );
                  $reports.find('#charitable-activity-download-form input[name="activity_type"]').val( $('#report-activity-type-filter').val() );

                  app.enableUI();
                  app.reportUILoadingOff();

              }

          });

        },

		/**
		 * This laods the UI/data for donor reports.
		 *
		 * @since 1.8.1
         *
		 */
        getDonorReports: function () {

          var data = {
              action:            'charitable_report_donor_data',
              nonce:             charitable_reporting.nonce,
              report_type:       $('#report-donor-type-filter').val(),
              limit:             $('#report-donor-limit').val(),
              offset:            $('#report-donor-offset').val(),
              ppage:             $('#report-donor-ppage').val()
          };

          app.disableUI();
          app.reportUILoadingOn();

          $.post(charitable_reporting.ajax_url, data, function (response) {

              if (response.success) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                  if ( response.data.html.donors ) {
                    wpchar.debug( 'updating donors with: ' + response.data.html.donors );
                    $reports.find('#charitable-report-donor-container').html( response.data.html.donors );
                  }

                  if ( response.data.html.title_card ) {
                    $reports.find('#charitable-report-donor-title-card').html( response.data.html.title_card );
                  }

                  app.enableUI();
                  app.reportUILoadingOff();

              }

          });

        },

        initPagination: function () {

            if ( $reports.find('.charitable-reports-pagination-nav').length === 0 ) {
                return;
            }

            $reports.on('click', '.charitable-reports-pagination-nav .page-item a.page-link', function (e) { // eslint-disable-line
                e.preventDefault();

                var $this = $(this),
                    limit = $this.data('limit'),
                    offset = $this.data('offset'),
                    ppage = $this.data('ppage'),
                    total_pages = $this.data('total-pages'),
                    current_page = $this.data('current-page'),
                    report_type = $this.data('report-type'),
                    page = $this.data('page'),
                    tab = $this.data('tab');

                var data = {
                    action:       'charitable_report_donor_data_pagination',
                    nonce:        charitable_reporting.nonce,
                    limit:        limit,
                    offset:       offset,
                    ppage:        ppage,
                    total_pages:  total_pages,
                    current_page: current_page,
                    report_type:  report_type,
                    page:         page,
                    tab:          tab
                };

                $.post(charitable_reporting.ajax_url, data, function (response) {

                    if (response.success) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                        if ( response.data.html.donors ) {
                            $reports.find('#charitable-report-donor-container').html( response.data.html.donors );
                        }

                    }

                });

            });

        },

		/**
		 * This inits the "headline" chart for a page.
		 *
		 * @since 1.8.1
         * @since 1.8.1.6 - Added check for empty donation axis and animationEnd event.
         *
		 */
        initHeadlineChart: function () {

            if ( $('#charitable-headline-graph').length === 0 ) {
                this.return;
            }

            var enableToolTips = false;

            if ( typeof charitable_reporting.headline_chart_options.enable_tooltips !== 'undefined' && false !== charitable_reporting.headline_chart_options.enable_tooltips ) {
                enableToolTips = true;
            }

            var headlineChartOptions = {
                chart: {
                    type: 'area',
                    width: '100%',
                    height: 400,
                    foreColor: "#757781",
                    toolbar: {
                        autoSelected: "pan",
                        show: false
                    },
                    events: {
                        animationEnd: function ( chartContext, options ) {
                            // if the goal tools notice is not visible, show it.
                            if ( showGrowthToolsNotice && $( '.charitable-growth-tools-notice' ).length ) {
                                $( '.charitable-growth-tools-notice' ).removeClass( 'charitable-hidden' );
                            }
                        }
                    }
                },
                series: [
                    {
                        name: 'Donations',
                        data: charitable_reporting.headline_chart_options.donation_axis
                    }
                ],
                colors: ["#5AA15226"],
                grid: {
                    borderColor: "#C9D4CA",
                    clipMarkers: false,
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                stroke: {
                    width: 3,
                    colors: ["#5AA152"]
                },
                fill: {
                    type: "solid"
                },
                tooltip: {
                    enabled: enableToolTips,
                    theme: "dark"
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 5,
                    colors: ["#FFFFFF"],
                    strokeColor: "#5AA152",
                    strokeWidth: 4
                },
                xaxis: {
                    categories: charitable_reporting.headline_chart_options.date_axis
                },
                yaxis: {
                    labels: {
                        formatter: (value) => {
                            // Format currency values using the configured decimal count.
                            // Currency symbol is already decoded in PHP, so use it directly.
                            var decimalCount = typeof charitable_reporting.decimal_count !== 'undefined'
                                ? charitable_reporting.decimal_count
                                : 2;
                            return charitable_reporting.currency_symbol + value.toFixed(decimalCount);
                        }
                    },
                    min: 0
                }
            }

            charts.headlineChart = new ApexCharts(document.querySelector("#charitable-headline-graph"), headlineChartOptions); // eslint-disable-line

            // check if array/value exists.
            if ( typeof charitable_reporting.headline_chart_options.donation_axis !== 'undefined' ) {
                // check if array has all zeros.
                if ( charitable_reporting.headline_chart_options.donation_axis.every( function (i) { return i === 0; } ) ) {
                    showGrowthToolsNotice = true;
                } else {
                    showGrowthToolsNotice = false;
                }
            }

        },

		/**
		 * This inits the "payment" chart for a page, usually located on the dashboard.
		 *
		 * @since 1.8.1
         *
		 */
        initPaymentChart: function () {

            if ( ! app.isDashboardPage() ) {
              this.return;
            }

            if ( $('#charitable-payment-methods-graph').length === 0 )  {
                this.return;
            }

            if ( typeof charitable_reporting.payment_methods_chart_options === 'undefined' ) {
                this.return;
            }

            // Color mapping for payment methods by gateway key.
            // This ensures consistent colors regardless of order.
            // Colors match the CSS legend dot colors in charitable-admin.scss.
            var paymentMethodColors = {
                'stripe': '#5AA152',      // Green (matches CSS)
                'offline': '#9e36f9',     // Purple (matches CSS)
                'manual': '#F99E36',      // Orange (matches CSS)
                'paypal': '#2B66D1',      // Blue (matches CSS)
                'square_core': '#d21561', // Pink/Red (unique color for Square)
                'square': '#d21561'       // Legacy Square support
            };

            // Default color palette for unknown payment methods.
            var defaultColors = [
                '#d21561', '#9e36f9', '#F99E36', '#2B66D1', '#5AA152'
            ];

            // Build colors array based on payment method keys.
            var paymentKeys = typeof charitable_reporting.payment_methods_chart_options.payment_keys !== 'undefined'
                ? charitable_reporting.payment_methods_chart_options.payment_keys
                : [];
            var colors = [];
            var colorIndex = 0;

            for ( var i = 0; i < paymentKeys.length; i++ ) {
                var key = paymentKeys[i];
                if ( key && paymentMethodColors[key] ) {
                    colors.push( paymentMethodColors[key] );
                } else if ( key ) {
                    // For unknown payment methods, use a color from the default palette.
                    colors.push( defaultColors[colorIndex % defaultColors.length] );
                    colorIndex++;
                } else {
                    // Empty key (usually the trailing empty entry), use first default color.
                    colors.push( defaultColors[0] );
                }
            }

            // If no colors were generated (fallback), use the default array.
            if ( colors.length === 0 ) {
                colors = defaultColors;
            }

            var paymentMethodsChartOptions = {
                series: charitable_reporting.payment_methods_chart_options.payment_percentages,
                labels: charitable_reporting.payment_methods_chart_options.payment_labels,
                colors: colors,
                chart: {
                    type: 'donut',
                    width: '75%',
                    toolbar: {
                        autoSelected: "pan",
                        show: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                yaxis: {
                    labels: {
                        formatter: (value) => {
                            return value + '%'
                        }
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '80%'
                        }
                    }
                },
                total: {
                    show: true,
                    showAlways: true,
                    label: 'Total',
                    fontSize: '22px',
                    fontFamily: 'Helvetica, Arial, sans-serif',
                    fontWeight: 600,
                    color: '#373d3f',
                    formatter: function (w) {
                        return w.globals.seriesTotals.reduce((a, b) => {
                            return a + b
                        }, 0)
                    }
                },
                legend: {
                    show: false,
                    showForSingleSeries: true,
                    showForNullSeries: true,
                    showForZeroSeries: true,
                    position: 'bottom',
                    horizontalAlign: 'center',
                    floating: false,
                    fontSize: '14px',
                    fontFamily: 'Helvetica, Arial',
                    fontWeight: 400,
                    inverseOrder: true,
                    customLegendItems: [],
                    offsetX: 0,
                    offsetY: 0,
                    labels: {
                        useSeriesColors: false
                    },
                    markers: {
                        width: 12,
                        height: 12,
                        strokeWidth: 0,
                        strokeColor: '#fff',
                        radius: 12,
                        offsetX: 0,
                        offsetY: 0,
                        customHTML: function () {
                            return '<span class="custom-marker">test</span>'
                        }
                    },
                    itemMargin: {
                        horizontal: 5,
                        vertical: 0
                    }
                }
            }

            charts.paymentMethodsChart = new ApexCharts(document.querySelector("#charitable-payment-methods-graph"), paymentMethodsChartOptions); // eslint-disable-line

        },

		/**
		 * Init the charts on the page.
		 *
		 * @since 1.8.1
         *
		 */
        initCharts: function () {

            app.initHeadlineChart();
            app.initPaymentChart();

        },

		/**
		 * Render the headline chart on the page.
		 *
		 * @since 1.8.1
         *
		 */
        renderHeadlineChart: function () {

            if ($('#charitable-headline-graph').length === 0) {
                return;
            }

            charts.headlineChart.render();

        },

		/**
		 * Render the payment chart on the page.
		 *
		 * @since 1.8.1
         *
		 */
        renderPaymentChart: function () {

            if ($('#charitable-payment-methods-graph').length === 0) {
                return;
            }

            charts.paymentMethodsChart.render();

        },

		/**
		 * Render the charts on the page.
		 *
		 * @since 1.8.1
         *
		 */
        renderCharts: function () {

            app.renderHeadlineChart();
            app.renderPaymentChart();

        },

        /* UI and Misc */

		/**
		 * Disables the UI on the page.
		 *
		 * @since 1.8.1
         *
		 */
        disableUI: function () {

            $reports.find('a, button, input, select').attr('disabled', 'disabled').addClass('charitable-disabled');

        },

		/**
		 * Enables the UI on the page.
		 *
		 * @since 1.8.1
         *
		 */
        enableUI: function () {

            $reports.find('a, button, input, select').removeAttr('disabled').removeClass('charitable-disabled');

        },

		/**
		 * Shows the loading indicator on the page.
		 *
		 * @since 1.8.1
         *
		 */
        reportUILoadingOn: function () {

            $reports.find('.charitable-report-ui, .charitable-activity-list-container').addClass('charitable-section-loading');

        },

		/**
		 * Hides the loading indicator on the page.
		 *
		 * @since 1.8.1
         *
		 */
        reportUILoadingOff: function () {

            $reports.find('.charitable-report-ui, .charitable-activity-list-container').removeClass('charitable-section-loading');

        },

		/**
		 * Util function that gets the start dates from the date picker.
		 *
		 * @since 1.8.1
         *
		 */
        getStartDateFromDatePicker: function () {

            return s.datePickerStartDate; // elements.$topnav_datepicker.data('start-date');

        },

		/**
		 * Util function that gets the end dates from the date picker.
		 *
		 * @since 1.8.1
         *
		 */
        getEndDateFromDatePicker: function () {

            return s.datePickerEndDate; // elements.$topnav_datepicker.data('end-date');

        },

		/**
		 * Util function that gets the start date "compare" from the date picker.
		 *
		 * @since 1.8.1
         *
		 */
        getStartDateFromCompareFromDatePicker: function () {

            return s.datePickerCompareFromStartDate;

        },

		/**
		 * Util function that gets the end date "compare" from the date picker.
		 *
		 * @since 1.8.1
         *
		 */
        getEndDateFromCompareFromDatePicker: function () {

            return s.datePickerCompareFromEndDate;

        },

		/**
		 * Util function that gets the start date "compare" from the date picker.
		 *
		 * @since 1.8.1
         *
		 */
        getStartDateFromCompareToDatePicker: function () {

            return s.datePickerCompareToStartDate;

        },

		/**
		 * Util function that gets the end date "compare" from the date picker.
		 *
		 * @since 1.8.1
         *
		 */
        getEndDateFromCompareToDatePicker: function () {

            return s.datePickerCompareToEndDate;

        },

		/**
		 * Util function that gets dashboard start date.
		 *
		 * @since 1.8.1
         *
		 */
        getDashboardStartDate: function () {

          return $reports.find('#charitable-dashboard-report-start-date').val();

        },

		/**
		 * Util function that gets dashboard end date.
		 *
		 * @since 1.8.1
         *
		 */
        getDashboardEndDate: function () {

          return $reports.find('#charitable-dashboard-report-end-date').val();

        },

		/**
		 * Util function that gets dashboard "days" (last 7, 14, 30, etc.)
		 *
		 * @since 1.8.1
         *
		 */
        getDashboardDays: function () {

          return $reports.find('#report-date-range-filter').val();

        },

		/**
		 * Util funcion that checks if this is an advanced report page.
		 *
		 * @since 1.8.1
         *
		 */
        isAdvancedPage: function() {

          return ( $('.charitable-advanced-report').length > 0 );

        },

		/**
		 * Util funcion that checks if this is a dashboard page.
		 *
		 * @since 1.8.1
         *
		 */
        isDashboardPage: function() {

          return ( $('#charitable-dashboard-report-container').length > 0 );

        },

        /* utils */

		/**
		 * Util function that decodes HTML entities.
		 *
		 * @since 1.8.1
         *
		 */
        decodeHtml: function (html) {
            var txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        }


    };

    return app;

}(document, window, jQuery)); // eslint-disable-line no-undef

CharitableAdminReporting.init();
