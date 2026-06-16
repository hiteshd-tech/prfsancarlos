<?php
/**
 * Library functions aid in code reusability and contain the actual business
 * logic of our plugin. They break down the plugin functionality into logical
 * units.
 *
 * Builder module: Integration into Divis Visual Builder. Because the VB is
 * available in the front-end (FB) and in the wp-admin area (BFB), this module
 * is neither admin nor front-end specific.
 *
 * @free    include file
 * @package PopupsForDivi
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Prepare the custom "Popup" tab when editing any post inside the Visual
 * Builder - only bail, if editing a Divi Area, as Areas cannot contain nested
 * Popups.
 *
 * @since 1.2.0
 * @return void
 */
function pfd_builder_add_hooks() {
	
	// Divi 5
	if ( function_exists( 'et_builder_d5_enabled' ) 
		&& et_builder_d5_enabled() ) {
			
		return;
	}

	add_filter( 'et_pb_all_fields_unprocessed_et_pb_section', 'pfd_builder_add_section_config' );

	add_filter( 'et_builder_main_tabs', 'pfd_builder_add_tab', 1 );

	add_filter( 'et_builder_get_parent_modules', 'pfd_builder_add_toggles_to_tab', 10, 2 );
}

/**
 * Returns true, if Divis Visual Builder is present on the current page.
 *
 * On Front-end calls: This function is called during the "parse_request"
 * action. At that point, we do not have a post_id yet, so the conditions can
 * only validate URL params and user permissions.
 *
 * This is accurate enough for our use case. In the worst case, a Popup is not
 * displayed for a logged in user when "et_fb" is present in the URL...
 *
 * On Admin pages (create new Divi Area): This function is called during
 * "admin_enqueue_assets", when we know the post-ID that's edited.
 *
 * @since 2.3.6
 * @return bool True, if the VB is present.
 */
function pfd_is_visual_builder() {
	static $cache_is_vb = null;

	if ( null === $cache_is_vb ) {
		$cache_is_vb = false;


		// Check if FB is enabled in current request.
		if (
			// phpcs:ignore WordPress.Security.NonceVerification -- This function does not change any state, and is therefore not susceptible to CSRF.
			empty( $_GET['et_fb'] ) // FB
			&& function_exists( 'et_builder_filter_bfb_enabled' )
			&& ! et_builder_filter_bfb_enabled() // BFB
		) {
			return $cache_is_vb;
		}

		// Check user capabilities.
		if (
			! current_user_can( 'edit_posts' )
			&& function_exists( 'et_pb_is_allowed' )
			&& ! et_pb_is_allowed( 'use_visual_builder' )
		) {
			return $cache_is_vb;
		}

		// Either in FB or BFB mode and the current user can use the VB.
		$cache_is_vb = true;
	}

	return $cache_is_vb;
}

/**
 * Register new Divi Area tab in the Visual Builder.
 *
 * @filter et_builder_main_tabs
 *
 * @since  1.2.0
 *
 * @param array $tabs List of tabs to display in the Visual Builder.
 *
 * @return array Modified list of tabs.
 */
function pfd_builder_add_tab( $tabs ) {
	$tabs['da'] = esc_html__( 'Popup', 'divi-popup' );


	return $tabs;
}

/**
 * Extends the configuration fields of a Divi SECTION.
 *
 * @filter et_pb_all_fields_unprocessed_et_pb_section
 *
 * @since  1.2.0
 *
 * @param array $fields_unprocessed Field definitions of the module.
 *
 * @return array The modified configuration fields.
 */
function pfd_builder_add_section_config( $fields_unprocessed ) {
	$fields = [];

	// "General" toggle.
	$fields['da_is_popup']   = [
		'label'           => esc_html__( 'This is a Popup', 'divi-popup' ),
		'type'            => 'yes_no_button',
		'option_category' => 'configuration',
		'options'         => [
			'off' => esc_html__( 'No', 'divi-popup' ),
			'on'  => esc_html__( 'Yes', 'divi-popup' ),
		],
		'default'         => 'off',
		'description'     => esc_html__( 'Turn this section into an On-Page Popup. Note, that this Popup is available on this page only. To create a global Popup, place an On-Page Popup into the theme Footer (or Header) using Divis Theme Builder.', 'divi-popup' ),
		'tab_slug'        => 'da',
		'toggle_slug'     => 'da_general',
	];
	$fields['da_popup_slug'] = [
		'label'           => esc_html__( 'Popup ID', 'divi-popup' ),
		'type'            => 'text',
		'option_category' => 'configuration',
		'description'     => esc_html__( 'Assign a unique ID to the Popup. You can display this Popup by using this name in an anchor link, like "#slug". The Popup ID is case-sensitive and we recommend to always use a lower-case ID', 'divi-popup' ),
		'tab_slug'        => 'da',
		'toggle_slug'     => 'da_general',
		'show_if'         => [
			'da_is_popup' => 'on',
		],
	];

	// "Behavior" toggle.
	$fields['da_not_modal']   = [
		'label'           => esc_html__( 'Close on Background-Click', 'divi-popup' ),
		'type'            => 'yes_no_button',
		'option_category' => 'configuration',
		'options'         => [
			'on'  => esc_html__( 'Yes', 'divi-popup' ),
			'off' => esc_html__( 'No', 'divi-popup' ),
		],
		'default'         => 'on',
		'description'     => esc_html__( 'Here you can decide whether the Popup can be closed by clicking somewhere outside the Popup. When this option is disabled, the Popup can only be closed via a Close Button or pressing the ESC key on the keyboard.', 'divi-popup' ),
		'tab_slug'        => 'da',
		'toggle_slug'     => 'da_behavior',
		'show_if'         => [
			'da_is_popup' => 'on',
		],
	];
	$fields['da_is_singular'] = [
		'label'           => esc_html__( 'Close other Popups', 'divi-popup' ),
		'type'            => 'yes_no_button',
		'option_category' => 'configuration',
		'options'         => [
			'on'  => esc_html__( 'Yes', 'divi-popup' ),
			'off' => esc_html__( 'No', 'divi-popup' ),
		],
		'default'         => 'off',
		'description'     => esc_html__( 'Here you can decide whether this Popup should automatically close all other Popups when it is opened.', 'divi-popup' ),
		'tab_slug'        => 'da',
		'toggle_slug'     => 'da_behavior',
		'show_if'         => [
			'da_is_popup' => 'on',
		],
	];
	$fields['da_exit_intent'] = [
		'label'           => esc_html__( 'Enable Exit Intent', 'divi-popup' ),
		'type'            => 'yes_no_button',
		'option_category' => 'configuration',
		'options'         => [
			'on'  => esc_html__( 'Yes', 'divi-popup' ),
			'off' => esc_html__( 'No', 'divi-popup' ),
		],
		'default'         => 'off',
		'description'     => esc_html__( 'When you enable the Exit Intent trigger, this Popup is automatically opened before the user leaves the current webpage. Note that the Exit Intent only works on desktop browsers, not on touch devices.', 'divi-popup' ),
		'tab_slug'        => 'da',
		'toggle_slug'     => 'da_behavior',
		'show_if'         => [
			'da_is_popup' => 'on',
		],
	];

	// "Close Button" toggle.
	$fields['da_has_close']  = [
		'label'           => esc_html__( 'Show Close Button', 'divi-popup' ),
		'type'            => 'yes_no_button',
		'option_category' => 'configuration',
		'options'         => [
			'on'  => esc_html__( 'Yes', 'divi-popup' ),
			'off' => esc_html__( 'No', 'divi-popup' ),
		],
		'default'         => 'on',
		'description'     => esc_html__( 'Do you want to display the default Close button in the top-right corner of the Popup?', 'divi-popup' ),
		'tab_slug'        => 'da',
		'toggle_slug'     => 'da_close',
		'show_if'         => [
			'da_is_popup' => 'on',
		],
	];
	$fields['da_dark_close'] = [
		'label'           => esc_html__( 'Button Color', 'divi-popup' ),
		'type'            => 'select',
		'option_category' => 'layout',
		'options'         => [
			'on'  => esc_html__( 'Light', 'divi-popup' ),
			'off' => esc_html__( 'Dark', 'divi-popup' ),
		],
		'default'         => 'off',
		'description'     => esc_html__( 'Here you can choose whether the Close button should be dark or light?. If the section has a light background, use a dark button. When the background is dark, use a light button.', 'divi-popup' ),
		'tab_slug'        => 'da',
		'toggle_slug'     => 'da_close',
		'show_if'         => [
			'da_is_popup'  => 'on',
			'da_has_close' => 'on',
		],
	];
	$fields['da_alt_close']  = [
		'label'           => esc_html__( 'Transparent Background', 'divi-popup' ),
		'type'            => 'yes_no_button',
		'option_category' => 'layout',
		'options'         => [
			'on'  => esc_html__( 'Yes', 'divi-popup' ),
			'off' => esc_html__( 'No', 'divi-popup' ),
		],
		'default'         => 'off',
		'description'     => esc_html__( 'Here you can choose whether the Close button has a Background color or only displays the Icon.', 'divi-popup' ),
		'tab_slug'        => 'da',
		'toggle_slug'     => 'da_close',
		'show_if'         => [
			'da_is_popup'  => 'on',
			'da_has_close' => 'on',
		],
	];

	// "Layout" toggle.
	$fields['da_has_shadow']  = [
		'label'           => esc_html__( 'Add a default Shadow', 'divi-popup' ),
		'type'            => 'yes_no_button',
		'option_category' => 'layout',
		'options'         => [
			'on'  => esc_html__( 'Yes', 'divi-popup' ),
			'off' => esc_html__( 'No', 'divi-popup' ),
		],
		'default'         => 'on',
		'description'     => esc_html__( 'Decide whether you want to add a default shadow to your Popup. You should disable this option, when you set a custom Box-Shadow for this Section.', 'divi-popup' ),
		'tab_slug'        => 'da',
		'toggle_slug'     => 'da_layout',
		'show_if'         => [
			'da_is_popup' => 'on',
		],
	];
	$fields['da_with_loader'] = [
		'label'           => esc_html__( 'Show Loader', 'divi-popup' ),
		'type'            => 'yes_no_button',
		'option_category' => 'layout',
		'options'         => [
			'on'  => esc_html__( 'Yes', 'divi-popup' ),
			'off' => esc_html__( 'No', 'divi-popup' ),
		],
		'default'         => 'off',
		'description'     => esc_html__( 'Decide whether to display a loading animation inside the Popup. This should be turned on, when the Popup contains an iframe or other content that is loaded dynamically.', 'divi-popup' ),
		'tab_slug'        => 'da',
		'toggle_slug'     => 'da_layout',
		'show_if'         => [
			'da_is_popup' => 'on',
		],
	];

	// "Visibility" toggle.
	$fields['da_disable_devices'] = [
		'label'           => esc_html__( 'Disable on', 'divi-popup' ),
		'type'            => 'multiple_checkboxes',
		'option_category' => 'configuration',
		'options'         => [
			'phone'   => esc_html__( 'Phone', 'divi-popup' ),
			'tablet'  => esc_html__( 'Tablet', 'divi-popup' ),
			'desktop' => esc_html__( 'Desktop', 'divi-popup' ),
		],
		'additional_att'  => 'disable_on',
		'description'     => esc_html__( 'This will disable the Popup on selected devices', 'divi-popup' ),
		'tab_slug'        => 'da',
		'toggle_slug'     => 'da_visibility',
		'show_if'         => [
			'da_is_popup' => 'on',
		],
	];

	return array_merge( $fields_unprocessed, $fields );
}

/**
 * Add a custom POPUP toggle to the SECTION module.
 *
 * @filter et_builder_get_parent_modules
 *
 * @since  1.2.0
 *
 * @param array  $parent_modules List of all parent elements.
 * @param string $post_type      The post type in editor.
 *
 * @return array Modified parent element definition.
 */
function pfd_builder_add_toggles_to_tab( $parent_modules, $post_type ) {

	if ( isset( $parent_modules['et_pb_section'] ) ) {
		$section = $parent_modules['et_pb_section'];

		$section->settings_modal_toggles['da'] = [
			'toggles' => [
				'da_general'    => [
					'title'    => __( 'General', 'divi-popup' ),
					'priority' => 10,
				],
				'da_behavior'   => [
					'title'    => __( 'Behavior', 'divi-popup' ),
					'priority' => 15,
				],
				'da_close'      => [
					'title'    => __( 'Close Button', 'divi-popup' ),
					'priority' => 20,
				],
				'da_layout'     => [
					'title'    => __( 'Layout', 'divi-popup' ),
					'priority' => 25,
				],
				'da_visibility' => [
					'title'    => __( 'Visibility', 'divi-popup' ),
					'priority' => 30,
				],
			],
		];

		/*
		This custom field actually supports the Visual Builder:
		VB support is provided in builder.js by observing the React state object.
		*/
		unset( $section->fields_unprocessed['da_is_popup']['vb_support'] );
		unset( $section->fields_unprocessed['da_popup_slug']['vb_support'] );
		unset( $section->fields_unprocessed['da_not_modal']['vb_support'] );
		unset( $section->fields_unprocessed['da_is_singular']['vb_support'] );
		unset( $section->fields_unprocessed['da_with_loader']['vb_support'] );
		unset( $section->fields_unprocessed['da_exit_intent']['vb_support'] );
		unset( $section->fields_unprocessed['da_has_close']['vb_support'] );
		unset( $section->fields_unprocessed['da_dark_close']['vb_support'] );
		unset( $section->fields_unprocessed['da_alt_close']['vb_support'] );
		unset( $section->fields_unprocessed['da_has_shadow']['vb_support'] );
		unset( $section->fields_unprocessed['da_disable_devices']['vb_support'] );
	}

	return $parent_modules;
}

/**
 * Ajax handler that is called BEFORE the actual `et_fb_ajax_save` function in
 * Divi. This function does not save anything but it sanitizes section
 * attributes and sets popup classes.
 *
 * @action wp_ajax_et_fb_ajax_save
 *
 * @since  1.2.0
 */
function pfd_builder_et_fb_ajax_save() {
	/**
	 * We disable phpcs for the following block, so we can use the identical code
	 * that is used inside the Divi theme.
	 *
	 * @see et_fb_ajax_save() in themes/Divi/includes/builder/functions.php
	 */
	if (
		! isset( $_POST['et_fb_save_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['et_fb_save_nonce'] ), 'et_fb_save_nonce' )
	) {
		return;
	}

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	// phpcs:disable ET.Sniffs.ValidatedSanitizedInput.InputNotSanitized
	if (
		! isset( $_POST['options'] )
		|| ! isset( $_POST['options']['status'] )
		|| ! isset( $_POST['modules'] )
		|| ! et_fb_current_user_can_save( $post_id, $_POST['options']['status'] )
	) {
		return;
	}

	// Fetch the builder attributes and sanitize them.
	$shortcode_data = json_decode( stripslashes( $_POST['modules'] ), true );
	// phpcs:enable

	// Popup defaults.
	$da_default = [
		'da_is_popup'        => 'off',
		'da_popup_slug'      => '',
		'da_exit_intent'     => 'off',
		'da_has_close'       => 'on',
		'da_alt_close'       => 'off',
		'da_dark_close'      => 'off',
		'da_not_modal'       => 'on',
		'da_is_singular'     => 'off',
		'da_with_loader'     => 'off',
		'da_has_shadow'      => 'on',
		'da_disable_devices' => [ 'off', 'off', 'off' ],
	];

	// Remove all functional classes from the section.
	$special_classes = [
		'popup',
		'on-exit',
		'no-close',
		'close-alt',
		'dark',
		'is-modal',
		'single',
		'with-loader',
		'no-shadow',
		'not-mobile',
		'not-tablet',
		'not-desktop',
	];

	foreach ( $shortcode_data as $id => $item ) {
		$type = sanitize_text_field( $item['type'] );
		if ( 'et_pb_section' !== $type ) {
			continue;
		}
		$attrs   = $item['attrs'];
		$conf    = $da_default;
		$classes = [];


		if ( ! empty( $attrs['module_class'] ) ) {
			$classes = explode( ' ', $attrs['module_class'] );

			if ( in_array( 'popup', $classes, true ) ) {
				$conf['da_is_popup'] = 'on';

				if ( ! empty( $attrs['module_id'] ) ) {
					$conf['da_popup_slug'] = $attrs['module_id'];
				}
				if ( in_array( 'on-exit', $classes, true ) ) {
					$conf['da_exit_intent'] = 'on';
				}
				if ( in_array( 'no-close', $classes, true ) ) {
					$conf['da_has_close'] = 'off';
				}
				if ( in_array( 'close-alt', $classes, true ) ) {
					$conf['da_alt_close'] = 'on';
				}
				if ( in_array( 'dark', $classes, true ) ) {
					$conf['da_dark_close'] = 'on';
				}
				if ( in_array( 'is-modal', $classes, true ) ) {
					$conf['da_not_modal'] = 'off';
				}
				if ( in_array( 'single', $classes, true ) ) {
					$conf['da_is_singular'] = 'on';
				}
				if ( in_array( 'with-loader', $classes, true ) ) {
					$conf['da_with_loader'] = 'on';
				}
				if ( in_array( 'no-shadow', $classes, true ) ) {
					$conf['da_has_shadow'] = 'off';
				}
				if ( in_array( 'not-mobile', $classes, true ) ) {
					$conf['da_disable_devices'][0] = 'on';
				}
				if ( in_array( 'not-tablet', $classes, true ) ) {
					$conf['da_disable_devices'][1] = 'on';
				}
				if ( in_array( 'not-desktop', $classes, true ) ) {
					$conf['da_disable_devices'][2] = 'on';
				}
			}
		}

		// Set all missing Divi Area attributes with a default value.
		foreach ( $conf as $key => $def_value ) {
			if ( ! isset( $attrs[ $key ] ) ) {
				if ( 'da_disable_devices' === $key ) {
					$def_value = implode( '|', $def_value );
				}
				$attrs[ $key ] = $def_value;
			}
		}

		$classes = array_diff( $classes, $special_classes );

		// Finally set the class to match all attributes.
		if ( 'on' === $attrs['da_is_popup'] ) {
			$classes[] = 'popup';

			if ( 'on' === $attrs['da_exit_intent'] ) {
				$classes[] = 'on-exit';
			}
			if ( 'on' !== $attrs['da_has_close'] ) {
				$classes[] = 'no-close';
			}
			if ( 'on' === $attrs['da_alt_close'] ) {
				$classes[] = 'close-alt';
			}
			if ( 'on' === $attrs['da_dark_close'] ) {
				$classes[] = 'dark';
			}
			if ( 'on' !== $attrs['da_not_modal'] ) {
				$classes[] = 'is-modal';
			}
			if ( 'on' === $attrs['da_is_singular'] ) {
				$classes[] = 'single';
			}
			if ( 'on' === $attrs['da_with_loader'] ) {
				$classes[] = 'with-loader';
			}
			if ( 'on' !== $attrs['da_has_shadow'] ) {
				$classes[] = 'no-shadow';
			}
			if ( 'on' === $attrs['da_disable_devices'][0] ) {
				$classes[] = 'not-mobile';
			}
			if ( 'on' === $attrs['da_disable_devices'][1] ) {
				$classes[] = 'not-tablet';
			}
			if ( 'on' === $attrs['da_disable_devices'][2] ) {
				$classes[] = 'not-desktop';
			}
			if ( $attrs['da_popup_slug'] ) {
				$attrs['module_id'] = $attrs['da_popup_slug'];
			}
		}
		if ( $classes ) {
			$attrs['module_class'] = implode( ' ', $classes );
		} else {
			unset( $attrs['module_class'] );
		}

		if ( 'on' === $attrs['da_is_popup'] ) {
			if (
				empty( $attrs['admin_label'] )
				|| 0 === strpos( $attrs['admin_label'], 'Popup - ' )
			) {
				if ( $attrs['module_id'] ) {
					$attrs['admin_label'] = 'Popup - #' . $attrs['module_id'];
				} else {
					$attrs['admin_label'] = 'Popup - (unnamed)';
				}
			}
		} elseif (
			empty( $attrs['admin_label'] )
			|| 0 === strpos( $attrs['admin_label'], 'Popup - ' )
		) {
			$attrs['admin_label'] = '';
		}

		$shortcode_data[ $id ]['attrs'] = $attrs;
	}

	$_POST['modules'] = addslashes( wp_json_encode( $shortcode_data ) );
}

