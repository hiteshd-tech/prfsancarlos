<?php
/**
 * Library functions aid in code reusability and contain the actual business
 * logic of our plugin. They break down the plugin functionality into logical
 * units.
 *
 * Assets module: Handles all assets (.js and .css files, inline scripts/styles)
 *
 * @free include file
 * @package PopupsForDivi
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Add the CSS/JS support to the front-end to make the popups work.
 *
 * @since 0.2.0
 */
function pfd_assets_enqueue_js_library() {
	global $wp_query;

	if (
		dm_get_const( 'DOING_CRON' )
		|| dm_get_const( 'DOING_AJAX' )
	) {
		return;
	}

	/*
	 * Logic found in Divi/includes/builder/functions.php
	 * @see function et_pb_register_preview_page()
	 */
	if (
		'true' === $wp_query->get( 'et_pb_preview' )
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		&& isset( $_GET['et_pb_preview_nonce'] ) // input var okay.
	) {
		return;
	}

	// Divi 5
	$et_builder_d5_enabled = false;
	if ( function_exists( 'et_builder_d5_enabled' ) 
		&& et_builder_d5_enabled()
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		&& isset( $_GET['et_vb_preview_id'] ) ) {
			
		$et_builder_d5_enabled = true;
	}
	
	if ( pfd_is_visual_builder() ) {
		
		$base_name = 'builder';
		
		
	} elseif ( pfd_assets_need_js_api() ) {
		
		$base_name = 'front';
		
	} else {
		// Not in builder mode, but also no front-end document: Do not load API.
		return;
	}

	if ( dm_get_const( 'SCRIPT_DEBUG' ) ) {
		$cache_version = time();
	} else {
		$cache_version = DIVI_POPUP_VERSION;
	}

	if ( function_exists( 'et_fb_is_enabled' ) ) {
		$is_divi_v3 = true;
	} else {
		// Divi 2.x or non-Divi theme. Limited support.
		$is_divi_v3 = false;
	}
	
	/**
	* Check for 3rd party services fetch to show/hide popup by default
	*
	*/
	// phpcs:disable WordPress.Security.NonceVerification
	$nowprocket = isset( $_GET['nowprocket'] ) && '1' === $_GET['nowprocket'];
	$no_optimize = isset( $_GET['no_optimize'] ) && '1' === $_GET['no_optimize'];
	

	$js_data = [];

	/**
	 * The base z-index. This z-index is used for the overlay, every
	 * popup has a z-index increased by 1.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['zIndex'] = 1000000;

	/**
	 * Speed of the fade-in/out animations. Set this to 0 to disable fade-in/out.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['animateSpeed'] = 400;

	/**
	 * A class-name prefix that can be used in *any* element to trigger
	 * the given popup. Default prefix is 'show-popup-', so we could
	 * add the class 'show-popup-demo' to an image. When this image is
	 * clicked, the popup "#demo" is opened.
	 * The prefix must have 3 characters or more.
	 *
	 * Example:
	 * <span class="show-popup-demo">Click here to show #demo</span>
	 *
	 * @since JS 1.0.0
	 */
	$js_data['triggerClassPrefix'] = 'show-popup-';

	/**
	 * Alternate popup trigger via data-popup attribute.
	 *
	 * Example:
	 * <span data-popup="demo">Click here to show #demo</span>
	 *
	 * @since JS 1.0.0
	 */
	$js_data['idAttrib'] = 'data-popup';

	/**
	 * Class that indicates a modal popup. A modal popup can only
	 * be closed via a close button, not by clicking on the overlay.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['modalIndicatorClass'] = 'is-modal';

	/**
	 * Class that indicates a blocking popup, which has the purpose to block the
	 * page behind it from access. As a result, the blocking Popup ignores the ESC
	 * key. When combined with "is-modal" and "no-close", the Popup is practically
	 * un-closeable.
	 *
	 * @since JS 2.0.0
	 */
	$js_data['blockingIndicatorClass'] = 'is-blocking';

	/**
	 * This changes the default close-button state when a popup does
	 * not specify noCloseClass or withCloseClass
	 *
	 * @since  1.1.0
	 */
	$js_data['defaultShowCloseButton'] = true;

	/**
	 * Add this class to the popup section to show the close button
	 * in the top right corner.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['withCloseClass'] = 'with-close';

	/**
	 * Add this class to the popup section to hide the close button
	 * in the top right corner.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['noCloseClass'] = 'no-close';

	/**
	 * Name of the class that closes the currently open popup. By default
	 * this is "close".
	 *
	 * @since JS 1.0.0
	 */
	$js_data['triggerCloseClass'] = 'close';

	/**
	 * Name of the class that marks a popup as "singleton". A "singleton" popup
	 * will close all other popups when it is opened/focused. By default this
	 * is "single".
	 *
	 * @since JS 1.0.0
	 */
	$js_data['singletonClass'] = 'single';

	/**
	 * Name of the class that activates the dark mode (dark close button) of the
	 * popup.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['darkModeClass'] = 'dark';

	/**
	 * Name of the class that removes the box-shadow from the popup.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['noShadowClass'] = 'no-shadow';

	/**
	 * Name of the class that changes the popups close button layout.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['altCloseClass'] = 'close-alt';

	/**
	 * CSS selector used to identify popups.
	 * Each popup must also have a unique ID attribute that
	 * identifies the individual popups.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['popupSelector'] = '.et_pb_section.popup';

	/**
	 * Whether to wait for an JS event-trigger before initializing
	 * the popup module in front end. This is automatically set
	 * for the Divi theme.
	 *
	 * If set to false, the popups will be initialized instantly when the JS
	 * library is loaded.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['initializeOnEvent'] = (
	$is_divi_v3
		? 'et_pb_after_init_modules' // Divi 3.0+ detected.
		: false // Older Divi or other themes.
	);

	/**
	 * All popups are wrapped in a new div element. This is the
	 * class name of this wrapper div.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['popupWrapperClass'] = 'area-outer-wrap';

	/**
	 * CSS class that is added to the popup when it enters
	 * full-height mode (i.e. on small screens)
	 *
	 * @since JS 1.0.0
	 */
	$js_data['fullHeightClass'] = 'full-height';

	/**
	 * CSS class that is added to the website body when the background overlay
	 * is visible.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['openPopupClass'] = 'da-overlay-visible';

	/**
	 * CSS class that is added to the modal overlay that is
	 * displayed while at least one popup is visible.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['overlayClass'] = 'da-overlay';

	/**
	 * Class that adds an exit-intent trigger to the popup.
	 * The exit intent popup is additionally triggered, when the
	 * mouse pointer leaves the screen towards the top.
	 * It's only triggered once.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['exitIndicatorClass'] = 'on-exit';

	/**
	 * Class that can be added to any trigger element (e.g., to a link) to
	 * instruct the JS API to trigger the Area on mouse contact. Default trigger
	 * is only click, not hover.
	 *
	 * @since JS 1.2.3
	 */
	$js_data['hoverTriggerClass'] = 'on-hover';

	/**
	 * Class that can be added to an trigger (e.g., to a link or button) to
	 * instruct the JS API to trigger the Area on click. That is the default
	 * behavior already, so this class only needs to be added if you want to
	 * enable on-hover AND on-click triggers for the same element.
	 *
	 * @since JS 1.2.3
	 */
	$js_data['clickTriggerClass'] = 'on-click';

	/**
	 * Defines the delay for reacting to exit-intents.
	 * Default is 2000, which means that an exit intent during the first two
	 * seconds after page load is ignored.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['onExitDelay'] = 2000;

	/**
	 * Class to hide a popup on mobile devices.
	 * Used for non-Divi themes or when creating popups via DiviPopup.register().
	 *
	 * @since JS 1.0.0
	 */
	$js_data['notMobileClass'] = 'not-mobile';

	/**
	 * Class to hide a popup on tablet devices.
	 * Used for non-Divi themes or when creating popups via DiviPopup.register().
	 *
	 * @since JS 1.0.0
	 */
	$js_data['notTabletClass'] = 'not-tablet';

	/**
	 * Class to hide a popup on desktop devices.
	 * Used for non-Divi themes or when creating popups via DiviPopup.register().
	 *
	 * @since JS 1.0.0
	 */
	$js_data['notDesktopClass'] = 'not-desktop';

	/**
	 * The parent container which holds all popups. For most Divi sites
	 * this could be "#page-container", but some child themes do not
	 * adhere to this convention.
	 * When a valid Divi theme is detected by the JS library, it will switch
	 * from 'body' to '#page-container'.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['baseContext'] = 'body';

	/**
	 * This class is added to the foremost popup; this is useful to
	 * hide/fade popups in the background.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['activePopupClass'] = 'is-open';

	/**
	 * This is the class-name of the close button that is
	 * automatically added to the popup. Only change this, if you
	 * want to use existing CSS or when the default class causes a
	 * conflict with your existing code.
	 *
	 * Note: The button is wrapped in a span which gets the class-
	 *       name `closeButtonClass + "-wrap"` e.g. "da-close-wrap"
	 *
	 * @since JS 1.0.0
	 */
	$js_data['closeButtonClass'] = 'da-close';

	/**
	 * Apply this class to a popup to add a loading animation in the background.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['withLoaderClass'] = 'with-loader';

	/**
	 * Display debug output in the JS console.
	 *
	 * @since JS 1.0.0
	 */
	$js_data['debug'] = pfd_flag_debug_mode();

	/**
	 * PRO ONLY: URL to the Ajax handler. This URL is used by the following
	 * modules:
	 *
	 * - Schedule conditions: Verify if an Area is currently active.
	 * - Tracking: Send usage details about Area events.
	 *
	 * @since 2.2.0
	 */
	$js_data['ajaxUrl'] = admin_url( 'admin-ajax.php' );

	/**
	 * PRO ONLY: Passes the default Area prefix to the JS API.
	 *
	 * @since 3.0.0
	 */
	if ( defined( 'DIVI_AREAS_ID_PREFIX' ) ) {
		$js_data['areaPrefix'] = DIVI_AREAS_ID_PREFIX;
	}

	/* -- End of default configuration -- */

	// Divi Areas Pro filter.
	$js_data = apply_filters( 'divi_areas_js_data', $js_data );

	/**
	 * Additional debugging details to generate JS error reports.
	 *
	 * @since 1.2.2
	 * @var array $infos Details about the current environment.
	 */
	$js_data['sys'] = apply_filters( 'divimode_debug_infos', [] );
	
	$load_loader_module = true;
	if ( $et_builder_d5_enabled ) {
		
		$load_loader_module = false;
	}
	
	if ( $et_builder_d5_enabled && is_preview() ) {
		
		$load_loader_module = false;
	}
	
	if ( $load_loader_module ) {
	
	// Inject the loader module and the configuration object into the header.
	pfd_assets_inject_loader( $js_data );

	wp_register_script(
		'js-divi-area',
		pfd_url( 'scripts/' . $base_name . '.min.js' ),
		[ 'jquery' ],
		$cache_version,
		true
	);

	wp_register_style(
		'css-divi-area',
		pfd_url( 'styles/' . $base_name . '.min.css' ),
		[],
		$cache_version,
		'all'
	);
	
	if ( !$nowprocket && !$no_optimize ) {
		
		wp_register_style(
			'css-divi-area-popuphidden',
			pfd_url( 'styles/front-popuphidden.min.css' ),
			[],
			$cache_version,
			'all'
		);
	}
	
	}

	wp_enqueue_script( 'js-divi-area' );
	wp_enqueue_style( 'css-divi-area' );
	
	if ( !$nowprocket && !$no_optimize ) {
		
		wp_enqueue_style( 'css-divi-area-popuphidden' );
	}

	if ( 'front' === $base_name ) {
		$inline_css = sprintf(
			'%s{display:none}',
			$js_data['popupSelector']
		);

		wp_add_inline_style( 'css-divi-area', $inline_css );
	} else {
	}

	/**
	 * Fires after the Divi Area JS API was enqueued.
	 *
	 * @since 2.3.2
	 *
	 * @param string $library Which library was enqueued - either 'front'
	 *                        when the public JS API was loaded, or
	 *                        'builder' when the Popup-tab in the Visual
	 *                        Builder was enqueued.
	 * @param string $version The version-string for cache management.
	 */
	do_action( 'divi_areas_enqueue_library', $base_name, $cache_version );
}

/**
 * Outputs a small inline script on the page to initialize the JS API.
 *
 * This script is output as inline script, because it needs to be usable at the
 * beginning of the html body. Due to its size, a separate HTTP request is most
 * likely costing more than an inline output.
 *
 * Also, many caching plugins will defer or combine the loader.js and effectively
 * breaking the purpose of enqueueing it this early.
 *
 * @internal Used by `pfd_assets_enqueue_js_library()`
 * @since    1.4.3
 *
 * @param array $js_config The DiviAreaConfig object details.
 */
function pfd_assets_inject_loader( array $js_config ) {
	/*
	Note that this function ignores WP Coding Standards: We read the contents
	of a .js file and output the script code (instead of enqueueing the file).

	This is safe, because the javascript file is read from disk and output
	unaltered. The security effects on the page are identical to including the
	code as a regular wp_enqueue_script() handle.
	*/

	$loader = [];

	/**
	 * Output the JS configuration before the loader.js contents.
	 *
	 * This line is used by the compatibility module!
	 *
	 * @see divi_areas_exclude_inline_content() in plugin-compatibility.php
	 */
	$loader[] = sprintf(
		'window.DiviPopupData=window.DiviAreaConfig=%s',
		wp_json_encode( $js_config )
	);

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$loader[] = file_get_contents( pfd_path( 'scripts/loader.min.js' ) );

	printf(
		'<script id="diviarea-loader">%s</script>',
		implode( ';', $loader ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Determines, whether the current request needs the JS API library.
 *
 * @since 2.3.1
 * @return bool True, when the JS API should be loaded.
 */
function pfd_assets_need_js_api() {


	return ! pfd_assets_is_missing_file();
}

/**
 * Determines whether the current request tries to load a missing page resource,
 * such as a .js or .map file.
 * When such a request is detected, the JS API is not initialized, so we do not
 * return JS code for the resource. So far we know that this fixes issues where
 * .svg files are used that do not exist: WP will return the 404 page result which
 * is parsed by the parent document. During that process the JS API can spill into
 * the parent document and interfere with the Visual Builder.
 *
 * @since 2.2.0
 * @return bool True, when we suspect that the 404 request is accessing a missing
 *              page resource.
 */
function pfd_assets_is_missing_file() {
	static $is_missing_file = null;

	if ( null === $is_missing_file ) {
		$is_missing_file = false;

		if ( is_404() ) {
			if ( isset( $_SERVER['HTTP_SEC_FETCH_DEST'] ) ) {
				/*
				 * When a Sec-Fetch-Dest header is present, we use that
				 * information to determine how this resource should be used. Only
				 * documents and embeds should load JS sources.
				 */
				$is_missing_file = ! in_array(
					$_SERVER['HTTP_SEC_FETCH_DEST'],
					[ 'document', 'embed', 'nested-document' ],
					true
				);
			} elseif ( ! empty( $_SERVER['REQUEST_URI'] ) && ! wp_get_raw_referer() ) {
				/*
				 * If no Sec-Fetch-Dest header is present, we evaluate the
				 * requested URI to determine, if it looks like a page resource.
				 * Of course, we only do this when no referer is present, as a
				 * referer always indicates a top-level document.
				 */
				$requested_url = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
				$question_mark = strpos( $requested_url, '?' );

				if ( $question_mark > 1 ) {
					$requested_url = substr( $requested_url, 0, $question_mark );
				}

				/**
				 * If the requested URL starts with "/wp-content/", or if it ends
				 * with a dot followed by 2-4 letters (like .js, .map, .json,
				 * .svg) then we're pretty sure that the request tries to find a
				 * missing page resource.
				 */
				if ( preg_match( '!(^/wp-content/|\.[a-z]{2,4}$)!i', $requested_url ) ) {
					$is_missing_file = true;
				}
			}
		}
	}

	return $is_missing_file;
}

