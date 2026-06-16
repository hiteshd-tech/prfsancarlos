/**
 * Charitable Admin Dashboard JavaScript
 *
 * @package Charitable/Admin/Dashboard
 * @since 1.8.1
 * @version 1.8.1
 */

(function($) {
    'use strict';

    /**
     * Dashboard functionality
     */
    var CharitableDashboard = {

        /**
         * Chart instance for updates
         */
        chartInstance: null,

        /**
         * Initialize the dashboard
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
            this.hideInitialLoadingSpinner();

            // Also hide spinners immediately if we're on the dashboard page
            this.hideSpinnersOnPageLoad();

            // Add timeout fallback to ensure spinner is always hidden after 10 seconds
            var self = this;
            setTimeout(function() {
                // Force hide spinner after 10 seconds as fallback
                $('#charitable-dashboard-v2').addClass('charitable-dashboard-loaded');
                $('body.charitable_page_charitable-dashboard').addClass('charitable-dashboard-loaded');
            }, 10000); // 10 seconds
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Collapsible section functionality
            $(document).on('click', '.charitable-dashboard-v2-toggle', this.handleToggleClick);

            // Time period dropdown functionality
            $(document).on('change', '#time-period-filter', this.handleTimePeriodChange);

            // Enhance campaign section functionality
            $(document).on('click', '.charitable-dashboard-v2-enhance-grid-button', this.handleEnhanceButtonClick);
        },

        /**
         * Initialize dashboard components
         */
        initComponents: function() {
            // Show loading state for chart while we check cookie
            this.showChartLoadingState();

            // Show "-" for all stats initially
            this.showLoadingStats();

            // Set initial dropdown value from cookie and initialize chart
            this.setInitialDropdownValue();
        },

        /**
         * Handle toggle click for collapsible sections
         * Arrow points down when expanded, right when collapsed
         */
        handleToggleClick: function(e) {
            e.preventDefault();

            var $section = $(this).closest('.charitable-dashboard-v2-collapsible');
            var $content = $section.find('.charitable-dashboard-v2-section-content');

            if ($section.hasClass('charitable-dashboard-v2-collapsed')) {
                // Expand - slide down
                $section.removeClass('charitable-dashboard-v2-collapsed');

                // Remove any inline max-height styles
                $content.css('max-height', '');

                // Let CSS handle the transition back to normal
            } else {
                // Collapse - slide up
                // Get the current height before collapsing
                var currentHeight = $content[0].scrollHeight;

                // Set max-height to current height for smooth animation
                $content.css('max-height', currentHeight + 'px');

                // Force reflow
                $content[0].offsetHeight;

                // Add collapsed class which will animate to max-height: 0
                $section.addClass('charitable-dashboard-v2-collapsed');
            }
        },

        /**
         * Handle time period dropdown change
         */
        handleTimePeriodChange: function(e) {
            e.preventDefault();

            var timePeriod = $(this).val();

            // Save selection to cookie
            CharitableDashboard.setCookie('charitable_dashboard_time_period', timePeriod, 30);

            // Update print button date range
            CharitableDashboard.updatePrintButtonDateRange(timePeriod);

            // Show loading states
            CharitableDashboard.showLoadingStates();

            // Make AJAX request
            CharitableDashboard.updateDashboardData(timePeriod);
        },

        /**
         * Show loading states for stats and chart
         */
        showLoadingStates: function() {
            // Add loading class to stats row
            $('.charitable-dashboard-v2-stats-row').addClass('charitable-dashboard-v2-loading');

            // Add loading class to chart container
            $('.charitable-dashboard-v2-header-bar-chart').addClass('charitable-dashboard-v2-loading');
        },

        /**
         * Hide loading states
         */
        hideLoadingStates: function() {
            // Remove loading class from stats row
            $('.charitable-dashboard-v2-stats-row').removeClass('charitable-dashboard-v2-loading');

            // Add loaded class to show stats text
            $('.charitable-dashboard-v2-stats-row').addClass('charitable-dashboard-stats-loaded');

            // Remove loading class from chart container
            $('.charitable-dashboard-v2-header-bar-chart').removeClass('charitable-dashboard-v2-loading');
        },

        /**
         * Update dashboard data via AJAX
         */
        updateDashboardData: function(timePeriod) {
            // Guard: reporting data is only localized on the dashboard page.
            if (typeof charitable_dashboard_reporting === 'undefined') {
                return;
            }

            var self = this;

            // Check if we should clear cache based on URL parameter
            var clearCache = window.location.search.indexOf('charitable_clear_stats_cache=1') !== -1;

            $.ajax({
                url: charitable_dashboard_reporting.ajax_url,
                type: 'POST',
                data: {
                    action: 'charitable_dashboard_data',
                    time_period: timePeriod,
                    nonce: charitable_dashboard_reporting.dashboard_nonce,
                    charitable_clear_stats_cache: clearCache ? '1' : '0'
                },
                success: function(response) {
                    if (response.success) {
                        self.updateStatsRow(response.data.stats);
                        self.updateChart(response.data.chart);
                    } else {
                        console.error('Charitable Dashboard: Error updating data:', response.data);
                        self.showError('Failed to update dashboard data: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Charitable Dashboard: AJAX error:', error);
                    self.showError('Network error: ' + error);
                },
                complete: function() {
                    self.hideLoadingStates();
                    self.hideChartLoadingState();
                }
            });
        },

        /**
         * Update stats row with new data
         */
        updateStatsRow: function(stats) {
            // Helper function to decode HTML entities
            function decodeHtmlEntities(str) {
                var textarea = document.createElement('textarea');
                textarea.innerHTML = str;
                return textarea.value;
            }

            // Update total donations
            var donationsChangeHtml = '';
            if (stats.donations_change && stats.donations_change !== '') {
                var changeValue = parseFloat(stats.donations_change.replace('%', ''));
                var isZero = changeValue === 0;
                var isPositive = stats.donations_change.indexOf('+') === 0 || (stats.donations_change.indexOf('-') !== 0 && changeValue > 0);
                var isNegative = stats.donations_change.indexOf('-') === 0 && changeValue < 0;
                var svgId = 'mask0_1904_1526_' + Date.now() + '_1';
                // Positive = green, Negative = red, Zero = black
                var svgColor = isZero ? '#000000' : (isPositive ? '#31944D' : '#DC2626');
                // Use up arrow for positive, down arrow for negative, up arrow for zero
                var svgPath = (isPositive || isZero) ?
                    'M1.82849 9.52507L1.07562 8.804L5.05505 4.96689L7.20609 7.02708L10.0024 4.37458H8.60426V3.34448H11.8308V6.43477H10.7553V5.09565L7.20609 8.49497L5.05505 6.43477L1.82849 9.52507Z' :
                    'M1.82849 3.47493L1.07562 4.196L5.05505 8.03311L7.20609 5.97292L10.0024 8.62542H8.60426V9.65552H11.8308V6.56523H10.7553V7.90435L7.20609 4.50503L5.05505 6.56523L1.82849 3.47493Z';

                // Add class based on value: positive, negative, or zero
                var changeClass = isZero ? 'charitable-dashboard-v2-stat-change-zero' : (isPositive ? 'charitable-dashboard-v2-stat-change-positive' : 'charitable-dashboard-v2-stat-change-negative');
                donationsChangeHtml = '<span class="charitable-dashboard-v2-stat-change ' + changeClass + '">' +
                    '<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                    '<mask id="' + svgId + '" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="13" height="13">' +
                    '<rect y="0.25415" width="12.9062" height="12.3612" fill="#D9D9D9"></rect>' +
                    '</mask>' +
                    '<g mask="url(#' + svgId + ')">' +
                    '<path d="' + svgPath + '" fill="' + svgColor + '"></path>' +
                    '</g>' +
                    '</svg>' +
                    stats.donations_change +
                    '</span>';
            }

            $('.charitable-dashboard-v2-stat-item').eq(0).find('.charitable-dashboard-v2-stat-amount').html(
                decodeHtmlEntities(stats.total_donations) + donationsChangeHtml
            );

            // Update average donations
            var avgChangeHtml = '';
            if (stats.avg_change && stats.avg_change !== '') {
                var changeValue = parseFloat(stats.avg_change.replace('%', ''));
                var isZero = changeValue === 0;
                var isPositive = stats.avg_change.indexOf('+') === 0 || (stats.avg_change.indexOf('-') !== 0 && changeValue > 0);
                var isNegative = stats.avg_change.indexOf('-') === 0 && changeValue < 0;
                var svgId = 'mask0_1904_1526_' + Date.now() + '_2';
                // Positive = green, Negative = red, Zero = black
                var svgColor = isZero ? '#000000' : (isPositive ? '#31944D' : '#DC2626');
                // Use up arrow for positive, down arrow for negative, up arrow for zero
                var svgPath = (isPositive || isZero) ?
                    'M1.82849 9.52507L1.07562 8.804L5.05505 4.96689L7.20609 7.02708L10.0024 4.37458H8.60426V3.34448H11.8308V6.43477H10.7553V5.09565L7.20609 8.49497L5.05505 6.43477L1.82849 9.52507Z' :
                    'M1.82849 3.47493L1.07562 4.196L5.05505 8.03311L7.20609 5.97292L10.0024 8.62542H8.60426V9.65552H11.8308V6.56523H10.7553V7.90435L7.20609 4.50503L5.05505 6.56523L1.82849 3.47493Z';

                // Add class based on value: positive, negative, or zero
                var changeClass = isZero ? 'charitable-dashboard-v2-stat-change-zero' : (isPositive ? 'charitable-dashboard-v2-stat-change-positive' : 'charitable-dashboard-v2-stat-change-negative');
                avgChangeHtml = '<span class="charitable-dashboard-v2-stat-change ' + changeClass + '">' +
                    '<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                    '<mask id="' + svgId + '" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="13" height="13">' +
                    '<rect y="0.25415" width="12.9062" height="12.3612" fill="#D9D9D9"></rect>' +
                    '</mask>' +
                    '<g mask="url(#' + svgId + ')">' +
                    '<path d="' + svgPath + '" fill="' + svgColor + '"></path>' +
                    '</g>' +
                    '</svg>' +
                    stats.avg_change +
                    '</span>';
            }

            $('.charitable-dashboard-v2-stat-item').eq(1).find('.charitable-dashboard-v2-stat-amount').html(
                decodeHtmlEntities(stats.avg_donations) + avgChangeHtml
            );

            // Update total donors
            $('.charitable-dashboard-v2-stat-item').eq(2).find('.charitable-dashboard-v2-stat-amount').text(stats.total_donors);

            // Update total refunds
            $('.charitable-dashboard-v2-stat-item').eq(3).find('.charitable-dashboard-v2-stat-amount').text(decodeHtmlEntities(stats.total_refunds));
        },

        /**
         * Update chart with new data
         */
        updateChart: function(chartData) {
            if (this.chartInstance) {
                // Update the existing chart with new data
                this.chartInstance.updateOptions({
                    series: [{
                        name: 'Donations',
                        data: chartData.donation_axis
                    }],
                    xaxis: {
                        categories: chartData.date_axis
                    }
                });
            } else {
                // Chart not initialized yet, initialize it with the new data
                this.initHeadlineChartWithData(chartData);
            }
        },

        /**
         * Show error message
         */
        showError: function(message) {
            // You can implement a notification system here
            // For now, we'll just log to console
            console.error('Charitable Dashboard Error:', message);

            // TODO: Future enhancement - show user-friendly error notification
            // This could be a toast notification or inline error message
        },

        /**
         * Show loading state for chart during initial load
         */
        showChartLoadingState: function() {
            $('.charitable-dashboard-v2-header-bar-chart').addClass('charitable-dashboard-v2-loading');
        },

        /**
         * Show loading state for stats with "-" placeholders
         */
        showLoadingStats: function() {
            // Update total donations with "-" and no percentage
            $('.charitable-dashboard-v2-stat-item').eq(0).find('.charitable-dashboard-v2-stat-amount').html('-');

            // Update average donations with "-" and no percentage
            $('.charitable-dashboard-v2-stat-item').eq(1).find('.charitable-dashboard-v2-stat-amount').html('-');

            // Update total donors with "-"
            $('.charitable-dashboard-v2-stat-item').eq(2).find('.charitable-dashboard-v2-stat-amount').text('-');

            // Update total refunds with "-"
            $('.charitable-dashboard-v2-stat-item').eq(3).find('.charitable-dashboard-v2-stat-amount').text('-');
        },

        /**
         * Hide loading state for chart
         */
        hideChartLoadingState: function() {
            $('.charitable-dashboard-v2-header-bar-chart').removeClass('charitable-dashboard-v2-loading');
        },

        /**
         * Set initial dropdown value from cookie
         */
        setInitialDropdownValue: function() {
            var savedTimePeriod = this.getCookie('charitable_dashboard_time_period');
            if (savedTimePeriod && $('#time-period-filter option[value="' + savedTimePeriod + '"]').length > 0) {
                $('#time-period-filter').val(savedTimePeriod);

                // Show loading state for stats row
                $('.charitable-dashboard-v2-stats-row').addClass('charitable-dashboard-v2-loading');

                // Add a small delay to allow the default 7-day stats to load first
                var self = this;
                setTimeout(function() {
                    // Update the dashboard data with the saved time period
                    self.updateDashboardData(savedTimePeriod);
                }, 500); // 500ms delay to let default stats load first
            } else {
                // No cookie found, load default 7-day data
                var self = this;
                setTimeout(function() {
                    // Update the dashboard data with default 7-day period
                    self.updateDashboardData('last-7-days');
                }, 500); // 500ms delay for consistency
            }
        },

        /**
         * Set a cookie with the given name, value, and expiration days
         */
        setCookie: function(name, value, days) {
            var expires = '';
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }

            // Make cookie unique to user by including user ID if available
            var userId = typeof charitable_dashboard_reporting !== 'undefined' && charitable_dashboard_reporting.user_id ?
                        charitable_dashboard_reporting.user_id : '0';

            document.cookie = name + '_user_' + userId + '=' + value + expires + '; path=/';
        },

        /**
         * Get a cookie value by name
         */
        getCookie: function(name) {
            // Make cookie unique to user by including user ID if available
            var userId = typeof charitable_dashboard_reporting !== 'undefined' && charitable_dashboard_reporting.user_id ?
                        charitable_dashboard_reporting.user_id : '0';

            var nameEQ = name + '_user_' + userId + '=';
            var ca = document.cookie.split(';');

            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1, c.length);
                }
                if (c.indexOf(nameEQ) === 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }
            return null;
        },

        /**
         * Initialize the headline chart with custom data
         * Used when loading from cookie
         */
        initHeadlineChartWithData: function(chartData) {
            this.createChart(chartData);
        },

        /**
         * Initialize the headline chart for the dashboard
         * This duplicates the functionality from the main dashboard
         *
         * @since 1.8.1
         */
        initHeadlineChart: function() {

            // Check if the chart container exists
            if ($('#charitable-dashboard-v2-headline-graph').length === 0) {
                console.warn('Charitable Dashboard: Chart container not found');
                return;
            }

            // Check if the localized data is available
            if (typeof charitable_dashboard_reporting === 'undefined') {
                console.warn('Charitable Dashboard: Chart data not available');
                return;
            }


            // Use the default chart data from the localized object
            var chartData = {
                donation_axis: charitable_dashboard_reporting.headline_chart_options.donation_axis,
                date_axis: charitable_dashboard_reporting.headline_chart_options.date_axis
            };

            this.createChart(chartData);
        },

        /**
         * Create the chart with the provided data
         */
        createChart: function(chartData) {
            // Check if the chart container exists
            if ($('#charitable-dashboard-v2-headline-graph').length === 0) {
                console.warn('Charitable Dashboard: Chart container not found');
                return;
            }

            // Check if ApexCharts is available
            if (typeof ApexCharts === 'undefined') {
                console.warn('Charitable Dashboard: ApexCharts library not loaded');
                return;
            }

            var enableToolTips = false;

            // Check if tooltips are enabled in the localized data
            if (typeof charitable_dashboard_reporting !== 'undefined' &&
                typeof charitable_dashboard_reporting.headline_chart_options !== 'undefined' &&
                charitable_dashboard_reporting.headline_chart_options.enable_tooltips !== false) {
                enableToolTips = true;
            }

            // Chart configuration - matches the main dashboard exactly
            var headlineChartOptions = {
                chart: {
                    type: 'area',
                    width: '100%',
                    height: 300,
                    foreColor: "#757781",
                    toolbar: {
                        autoSelected: "pan",
                        show: false
                    },
                    events: {
                        // TODO: Add animation end event for future growth tools notice functionality
                        // animationEnd: function ( chartContext, options ) {
                        //     // Future: Show growth tools notice if needed
                        // }
                    }
                },
                series: [
                    {
                        name: 'Donations',
                        data: chartData.donation_axis
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
                    categories: chartData.date_axis
                },
                    yaxis: {
                        labels: {
                            formatter: function(value) {
                                // Format currency values using the configured decimal count.
                                // Currency symbol is already decoded in PHP, so use it directly.
                                var decimalCount = typeof charitable_dashboard_reporting.decimal_count !== 'undefined'
                                    ? charitable_dashboard_reporting.decimal_count
                                    : 2;
                                return charitable_dashboard_reporting.currency_symbol + value.toFixed(decimalCount);
                            }
                        },
                        min: 0
                    }
            };

            // Create and render the chart
            try {
                var betaHeadlineChart = new ApexCharts(
                    document.querySelector("#charitable-dashboard-v2-headline-graph"),
                    headlineChartOptions
                );

                betaHeadlineChart.render();

                // Store reference for future updates
                CharitableDashboard.chartInstance = betaHeadlineChart;

            } catch (error) {
                console.error('Charitable Dashboard: Error initializing chart:', error);
            }
        },

        /**
         * Example method for initializing widgets
         */
        initWidgets: function() {
            // Add your widget initialization code here
        },

        /**
         * Handle enhance campaign button clicks
         */
        handleEnhanceButtonClick: function(e) {
            var $button = $(this);
            var action = $button.data('action');

            // Allow normal link navigation for elements without a data-action (e.g. Learn More)
            // or upgrade links that are already <a> tags with href.
            if (!action || action === 'upgrade') {
                return;
            }

            e.preventDefault();
            var slug = $button.data('slug');
            var basename = $button.data('basename');
            var type = $button.data('type');
            var setupUrl = $button.data('setup-url');


            // Store original state for error handling
            var originalState = {
                text: $button.text(),
                class: $button.attr('class')
            };

            // Handle different actions
            switch (action) {
                case 'install':
                    CharitableDashboard.handlePluginInstall($button, slug, type, originalState);
                    break;
                case 'activate':
                    CharitableDashboard.handlePluginActivate($button, basename || slug, type, originalState);
                    break;
                case 'install_addon':
                    CharitableDashboard.handleAddonInstall($button, slug, type, originalState);
                    break;
                case 'activate_addon':
                    CharitableDashboard.handleAddonActivate($button, slug, type, originalState);
                    break;
                case 'upgrade':
                    var upgradeUrl = $button.data('upgrade-url');
                    if (upgradeUrl) {
                        window.open(upgradeUrl, '_blank');
                    }
                    break;
                case 'setup':
                    if (setupUrl) {
                        window.location.href = setupUrl;
                    }
                    break;
            }
        },

        /**
         * Handle third-party plugin installation
         */
        handlePluginInstall: function($button, slug, type, originalState) {
            // Show loading state
            $button.text('Installing...').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'charitable_install_plugin',
                    nonce: charitable_admin.nonce,
                    slug: slug
                },
                success: function(response) {
                    if (response.success) {
                        // Update button to next state
                        $button.text('Activate').addClass('charitable-dashboard-v2-activate-button').prop('disabled', false);
                        $button.attr('data-action', 'activate').data('action', 'activate');
                    } else {
                        var errorMessage = 'Installation failed';
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        } else if (response.message) {
                            errorMessage = response.message;
                        }
                        CharitableDashboard.handleError($button, originalState, errorMessage);
                    }
                },
                error: function() {
                    CharitableDashboard.handleError($button, originalState, 'Network error. Please try again.');
                }
            });
        },

        /**
         * Handle third-party plugin activation
         */
        handlePluginActivate: function($button, basename, type, originalState) {
            // Show loading state
            $button.text('Activating...').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'charitable_activate_plugin',
                    nonce: charitable_admin.nonce,
                    basename: basename
                },
                success: function(response) {
                    if (response.success) {
                        // Update button to setup state
                        $button.text('Setup').removeClass('charitable-dashboard-v2-activate-button').addClass('charitable-dashboard-v2-setup-button').prop('disabled', false);
                        $button.attr('data-action', 'setup').data('action', 'setup');

                        // Set setup URL if provided in response
                        if (response.data && response.data.setup) {
                            $button.attr('data-setup-url', response.data.setup).data('setup-url', response.data.setup);
                        } else if (response.data && response.data.settings) {
                            // Fallback to settings URL if no setup URL
                            $button.attr('data-setup-url', response.data.settings).data('setup-url', response.data.settings);
                        }
                    } else {
                        var errorMessage = 'Activation failed';
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        } else if (response.message) {
                            errorMessage = response.message;
                        }
                        CharitableDashboard.handleError($button, originalState, errorMessage);
                    }
                },
                error: function() {
                    CharitableDashboard.handleError($button, originalState, 'Network error. Please try again.');
                }
            });
        },

        /**
         * Handle Charitable addon installation
         */
        handleAddonInstall: function($button, slug, type, originalState) {
            // Show loading state
            $button.text('Installing...').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'charitable_install_charitable_addon',
                    nonce: charitable_admin.nonce,
                    slug: slug
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            // Update button to next state
                            $button.text('Activate').addClass('charitable-dashboard-v2-activate-button').prop('disabled', false);
                            $button.attr('data-action', 'activate_addon').data('action', 'activate_addon');
                        }
                    } else {
                        var errorMessage = 'Installation failed';
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        } else if (response.message) {
                            errorMessage = response.message;
                        }
                        CharitableDashboard.handleError($button, originalState, errorMessage);
                    }
                },
                error: function() {
                    CharitableDashboard.handleError($button, originalState, 'Network error. Please try again.');
                }
            });
        },

        /**
         * Handle Charitable addon activation
         */
        handleAddonActivate: function($button, slug, type, originalState) {
            // Show loading state
            $button.text('Activating...').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'charitable_activate_charitable_addon',
                    nonce: charitable_admin.nonce,
                    slug: slug
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            // Update button based on response data
                            var buttonText = response.data.button_text || 'Installed';
                            var buttonClass = response.data.button_class || 'charitable-dashboard-v2-installed-button';
                            var buttonAction = response.data.button_action || 'installed';
                            var isDisabled = response.data.button_disabled || false;

                            $button.text(buttonText)
                                  .removeClass('charitable-dashboard-v2-activate-button')
                                  .addClass(buttonClass)
                                  .prop('disabled', isDisabled)
                                  .attr('data-action', buttonAction)
                                  .data('action', buttonAction);
                        }
                    } else {
                        var errorMessage = 'Activation failed';
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        } else if (response.message) {
                            errorMessage = response.message;
                        }
                        CharitableDashboard.handleError($button, originalState, errorMessage);
                    }
                },
                error: function() {
                    CharitableDashboard.handleError($button, originalState, 'Network error. Please try again.');
                }
            });
        },

        /**
         * Handle errors with admin styling
         */
        handleError: function($button, originalState, errorMessage) {
            // Show error message
            var $errorElement = $('<div class="charitable-dashboard-v2-error-message" style="color: #d63638; font-size: 12px; margin-top: 5px;">' + errorMessage + '</div>');

            // Insert error message after button
            $button.after($errorElement);

            // Revert button to original state
            $button.text(originalState.text).attr('class', originalState.class).prop('disabled', false);

            // Remove error message after 5 seconds
            setTimeout(function() {
                $errorElement.fadeOut(function() {
                    $errorElement.remove();
                });
            }, 5000);
        },

        /**
         * Hide the initial loading spinner
         */
        hideInitialLoadingSpinner: function() {
            var self = this;

            // Wait for DOM to be fully loaded and then hide spinner
            $(document).ready(function() {
                // Add a small delay to ensure all content is rendered
                setTimeout(function() {
                    self.hideSpinners();
                }, 100);

                // Also hide spinners when dashboard content is detected
                self.waitForDashboardContent();
            });
        },

        /**
         * Wait for dashboard content to load and hide spinners
         */
        waitForDashboardContent: function() {
            var self = this;
            var attempts = 0;
            var maxAttempts = 50; // 5 seconds max wait time

            var checkForContent = function() {
                attempts++;

                // Check if dashboard content is present
                var hasContent = $('#charitable-dashboard-v2 .charitable-dashboard-v2-content').length > 0 ||
                                $('#charitable-dashboard-report-container').length > 0 ||
                                $('.charitable-dashboard-report').length > 0;

                if (hasContent || attempts >= maxAttempts) {
                    self.hideSpinners();
                } else {
                    setTimeout(checkForContent, 100);
                }
            };

            checkForContent();
        },

        /**
         * Hide all dashboard spinners
         * Note: There's also a 10-second timeout fallback in init() to ensure spinner is always hidden
         */
        hideSpinners: function() {
            // Hide the dashboard v2 spinner
            $('#charitable-dashboard-v2').addClass('charitable-dashboard-loaded');

            // Hide the global dashboard spinner (targets body class)
            $('body.charitable_page_charitable-dashboard').addClass('charitable-dashboard-loaded');
        },

        /**
         * Hide spinners on page load - immediate fallback
         */
        hideSpinnersOnPageLoad: function() {
            var self = this;

            // Hide spinners after a short delay to ensure they're visible briefly
            setTimeout(function() {
                self.hideSpinners();
            }, 500);
        },

        /**
         * Update print button date range based on time period
         */
        updatePrintButtonDateRange: function(timePeriod) {
            var dateRange = this.getDateRangeForPeriod(timePeriod);

            // Update the print form hidden fields
            $('#charitable-dashboard-v2-print input[name="start_date"]').val(dateRange.start_date);
            $('#charitable-dashboard-v2-print input[name="end_date"]').val(dateRange.end_date);
            $('#charitable-dashboard-v2-print input[name="days"]').val(timePeriod);
        },

        /**
         * Get date range for a given time period
         */
        getDateRangeForPeriod: function(timePeriod) {
            var today = new Date();
            var startDate, endDate;

            switch(timePeriod) {
                case 'last-7-days':
                    startDate = new Date(today.getTime() - (7 * 24 * 60 * 60 * 1000));
                    break;
                case 'last-14-days':
                    startDate = new Date(today.getTime() - (14 * 24 * 60 * 60 * 1000));
                    break;
                case 'last-30-days':
                    startDate = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
                    break;
                case 'last-3-months':
                    startDate = new Date(today.getTime() - (90 * 24 * 60 * 60 * 1000));
                    break;
                case 'last-6-months':
                    startDate = new Date(today.getTime() - (180 * 24 * 60 * 60 * 1000));
                    break;
                case 'last-year':
                    startDate = new Date(today.getTime() - (365 * 24 * 60 * 60 * 1000));
                    break;
                default:
                    startDate = new Date(today.getTime() - (7 * 24 * 60 * 60 * 1000));
            }

            endDate = today;

            return {
                start_date: this.formatDateForPrint(startDate),
                end_date: this.formatDateForPrint(endDate)
            };
        },

        /**
         * Format date for print functionality (YYYY-MM-DD format)
         */
        formatDateForPrint: function(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        }

    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        CharitableDashboard.init();
        initProgressCircles();
        initTabSwitching();
    });

    /**
     * Initialize progress circles
     */
    function initProgressCircles() {
        const progressCircles = document.querySelectorAll('.progress-circle');

        progressCircles.forEach(circle => {
            const progress = parseInt(circle.getAttribute('data-progress')) || 0;
            const svg = circle.querySelector('svg');
            const progressCircle = svg.querySelector('circle:last-child');

            if (progressCircle) {
                // Calculate circle properties
                const radius = 18; // r="18" from the SVG
                const circumference = 2 * Math.PI * radius;

                // Set the stroke-dasharray to the full circumference
                progressCircle.style.strokeDasharray = circumference;

                // Calculate stroke-dashoffset to show progress
                // Start from top (0 degrees) and go clockwise
                // For 0% progress, offset should be circumference (full circle hidden)
                // For 100% progress, offset should be 0 (full circle visible)
                const offset = circumference - (progress / 100) * circumference;
                progressCircle.style.strokeDashoffset = offset;

                // Rotate the progress circle by -90 degrees to start from the top
                progressCircle.style.transform = 'rotate(-90deg)';
                // Set the transform origin to the center of the circle
                progressCircle.style.transformOrigin = '20px 20px';
            }
        });
    }

    /**
     * Initialize tab switching functionality
     */
    function initTabSwitching() {
        const tabNavItems = document.querySelectorAll('.charitable-dashboard-v2-tab-nav-item');
        const tabContents = document.querySelectorAll('.charitable-dashboard-v2-tab-content');

        tabNavItems.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();

                const targetTab = this.getAttribute('data-tab');

                // Remove active class from all tabs and contents
                tabNavItems.forEach(item => item.classList.remove('charitable-dashboard-v2-tab-nav-active'));
                tabContents.forEach(content => content.classList.remove('charitable-dashboard-v2-tab-content-active'));

                // Add active class to clicked tab
                this.classList.add('charitable-dashboard-v2-tab-nav-active');

                // Show corresponding content - specifically target tab content divs
                const targetContent = document.querySelector(`.charitable-dashboard-v2-tab-content[data-tab="${targetTab}"]`);
                if (targetContent) {
                    targetContent.classList.add('charitable-dashboard-v2-tab-content-active');
                }

                // Reinitialize progress circles if switching to campaigns tab
                if (targetTab === 'top-campaigns') {
                    setTimeout(() => {
                        initProgressCircles();
                    }, 100);
                }
            });
        });
    }

})(jQuery);