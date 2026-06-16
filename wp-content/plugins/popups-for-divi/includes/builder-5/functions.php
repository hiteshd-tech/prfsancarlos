<?php
/**
 * Enqueue Divi 5 Visual Builder Assets
 *
 * @free    include file
 * @package PopupsForDivi
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function pfd_divi5_enqueue_assets() {
	
	// Bail early if Divi 5 doesn't exist
	if ( ! function_exists( 'et_builder_d5_enabled' ) ) {
		
        return;
    }
	
    // Bail early if either Divi 5 or Visual Builder is not enabled.
    if ( ! et_builder_d5_enabled() || ! et_core_is_fb_enabled() ) {
		
        return;
    }
	
	if ( isset( $_GET['app_window'] ) && '1' === $_GET['app_window'] ) {
	
    \ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
        [
            'name'    => 'd5-popupsfordivi-conversion-outline',
            'version' => DIVI_POPUP_VERSION,
            'script'  => [
                'src'                => DIVI_POPUP_URL . 'scripts/divi5-conversion.js',
                'deps'               => [
                    'lodash',
                    'divi-vendor-wp-hooks'
                ],
                'enqueue_top_window' => true,
                'enqueue_app_window' => true,
                'args'               => [
                    'in_footer' => false,
                ],
            ],
        ]
    );
	
	}
			
	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		[
			'name'    => 'd5-popupsfordivi-extension',
			'version' => DIVI_POPUP_VERSION,
			'script'  => [
				'src'                => DIVI_POPUP_URL . 'scripts/divi5-build.js',
				'deps'               => [
					'lodash',
					'divi-module-library',
					'divi-vendor-wp-hooks',
					'wp-i18n'
				],
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
				'args'               => [
					'in_footer' => false,
				],
			],
		]
	);

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		[
			'name'    => 'd5-popupsfordivi-extension-style-bundle',
			'version' => DIVI_POPUP_VERSION,
			'style'   => [
				'src'                => DIVI_POPUP_URL . 'styles/builder-5.min.css',
				'deps'               => [],
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
			],

		]
	);
}


/**
 * Register custom sub-tabs attributes for Divi Section module.
 *
 * @param array $settings The module metadata settings.
 *
 * @return array The modified module metadata settings.
 */
function pfd_divi5_register_custom_divisection_attributes( $settings ) {
	
    $module_name       = $settings['name'] ?? '';
    $module_attributes = $settings['attributes'] ?? false;

    if ( 'divi/section' !== $module_name ) {
		
        return $settings;
    }

    if ( ! $module_attributes ) {
		
        return $settings;
    }

    $settings['attributes']['da_is_popup'] = [
        'type'              => 'object',
        'settings'          => [
            'innerContent' => [
                'item' => [
                    'attrName'    => 'da_is_popup.innerContent',
					'subName'     => 'isPopup',
                    'label'       => esc_html__( 'This is a Popup', 'divi-popup' ),
                    'description' => esc_html__( 'Turn this section into an On-Page Popup. Note, that this Popup is available on this page only. To create a global Popup, place an On-Page Popup into the theme Footer (or Header) using Divis Theme Builder.', 'divi-popup' ),
                    'groupSlug'   => 'popupsForDivi',
                    'priority'    => 40,
                    'render'      => true,
                    'features'    => [
						'hover'      => false,
                        'sticky'     => false,
						'responsive' => false,
                        'preset'     => 'content',
                    ],
                    'component'   => [
                        'name' => 'divi/toggle',
                        'type' => 'field',
                    ],
                ],
            ],
        ],
        'attributes'        => [
            'class' => 'popup',
        ]
    ];
	
    $settings['attributes']['da_popup_slug'] = [
        'type'              => 'object',
        'settings'          => [
            'innerContent' => [
                'item' => [
                    'attrName'    => 'da_popup_slug.innerContent',
                    'label'       => esc_html__( 'Popup ID', 'divi-popup' ),
                    'description' => esc_html__( 'Assign a unique ID to the Popup. You can display this Popup by using this name in an anchor link, like "#slug". The Popup ID is case-sensitive and we recommend to always use a lower-case ID.', 'divi-popup' ),
                    'groupSlug'   => 'popupsForDivi',
                    'priority'    => 40,
                    'render'      => true,
                    'features'    => [
                        'sticky'     => false,
						'responsive' => false,
						'dynamicContent' => [
							'type' => 'text',
						],
                        'preset'     => 'content',
                    ],
                    'component'   => [
                        'name' => 'divi/text',
                        'type' => 'field',
                    ],
                ],
            ],
        ],
        'childrenSanitizer' => 'et_core_esc_previously',
        'inlineEditor'      => 'plainText',
        'tagName'           => 'span'
    ];
	
    $settings['attributes']['da_not_modal'] = [
        'type'              => 'object',
		'selector'          => '{{selector}} .popup.is-modal',
        'settings'          => [
            'innerContent' => [
                'item' => [
                    'attrName'    => 'da_not_modal.innerContent',
					'subName'     => 'notModal',
                    'label'       => esc_html__( 'Close on Background-Click', 'divi-popup' ),
                    'description' => esc_html__( 'Here you can decide whether the Popup can be closed by clicking somewhere outside the Popup. When this option is disabled, the Popup can only be closed via a Close Button or pressing the ESC key on the keyboard.', 'divi-popup' ),
                    'groupSlug'   => 'popupsForDivi',
                    'priority'    => 40,
                    'render'      => true,
                    'features'    => [
						'hover'      => false,
                        'sticky'     => false,
						'responsive' => false,
                        'preset'     => 'content',
                    ],
                    'component'   => [
                        'name' => 'divi/toggle',
                        'type' => 'field',
                    ],
                ],
            ],
        ],
        'attributes'        => [
            'class' => 'is-modal',
        ],
        'childrenSanitizer' => 'et_core_esc_previously',
        'inlineEditor'      => 'plainText',
        'tagName'           => 'span'
    ];
	
    $settings['attributes']['da_is_singular'] = [
        'type'              => 'object',
		'selector'          => '{{selector}} .popup.single',
        'settings'          => [
            'innerContent' => [
                'item' => [
                    'attrName'    => 'da_is_singular.innerContent',
                    'label'       => esc_html__( 'Close other Popups', 'divi-popup' ),
                    'description' => esc_html__( 'Here you can decide whether this Popup should automatically close all other Popups when it is opened.', 'divi-popup' ),
                    'groupSlug'   => 'popupsForDivi',
                    'priority'    => 40,
                    'render'      => true,
                    'features'    => [
						'hover'     => false,
                        'sticky'     => false,
						'responsive' => false,
                        'preset'     => 'content',
                    ],
                    'component'   => [
                        'name' => 'divi/toggle',
                        'type' => 'field',
                    ],
                ],
            ],
        ],
        'attributes'        => [
            'class' => 'single',
        ],
        'childrenSanitizer' => 'et_core_esc_previously',
        'tagName'           => 'span'
    ];
	
    $settings['attributes']['da_exit_intent'] = [
        'type'              => 'object',
		'selector'          => '{{selector}} .popup.on-exit',
        'settings'          => [
            'innerContent' => [
                'item' => [
                    'attrName'    => 'da_exit_intent.innerContent',
                    'label'       => esc_html__( 'Enable Exit Intent', 'divi-popup' ),
                    'description' => esc_html__( 'When you enable the Exit Intent trigger, this Popup is automatically opened before the user leaves the current webpage. Note that the Exit Intent only works on desktop browsers, not on touch devices.', 'divi-popup' ),
                    'groupSlug'   => 'popupsForDivi',
                    'priority'    => 40,
                    'render'      => true,
                    'features'    => [
						'hover'     => false,
                        'sticky'     => false,
						'responsive' => false,
                        'preset'     => 'content',
                    ],
                    'component'   => [
                        'name' => 'divi/toggle',
                        'type' => 'field',
                    ],
                ],
            ],
        ],
        'attributes'        => [
            'class' => 'on-exit',
        ],
        'childrenSanitizer' => 'et_core_esc_previously',
        'tagName'           => 'span'
    ];
	
    $settings['attributes']['da_has_close'] = [
        'type'              => 'object',
		'selector'          => '{{selector}} .popup.no-close',
        'settings'          => [
            'innerContent' => [
                'item' => [
                    'attrName'    => 'da_has_close.innerContent',
					'subName'     => 'showCloseButton',
                    'label'       => esc_html__( 'Show Close Button', 'divi-popup' ),
                    'description' => esc_html__( 'Do you want to display the default Close button in the top-right corner of the Popup.', 'divi-popup' ),
                    'groupSlug'   => 'popupsForDivi',
                    'priority'    => 40,
                    'render'      => true,
                    'features'    => [
						'hover'     => false,
                        'sticky'     => false,
						'responsive' => false,
                        'preset'     => 'content',
                    ],
                    'component'   => [
                        'name' => 'divi/toggle',
                        'type' => 'field',
                    ],
                ],
            ],
        ],
        'attributes'        => [
            'class' => 'no-close',
        ],
        'childrenSanitizer' => 'et_core_esc_previously',
        'tagName'           => 'span'
    ];
	
    $settings['attributes']['da_dark_close'] = [
        'type'              => 'object',
		'selector'          => '{{selector}} .popup.dark',
        'settings'          => [
            'innerContent' => [
                'item' => [
                    'attrName'    => 'da_dark_close.innerContent',
					'subName'     => 'darkClose',
                    'label'       => esc_html__( 'Close Button Color', 'divi-popup' ),
                    'description' => esc_html__( 'Here you can choose whether the Close button should be dark or light?. If the section has a light background, use a dark button. When the background is dark, use a light button.', 'divi-popup' ),
                    'groupSlug'   => 'popupsForDivi',
                    'priority'    => 40,
                    'render'      => true,
                    'features'    => [
						'hover'     => false,
                        'sticky'     => false,
						'responsive' => false,
                        'preset'     => 'content',
                    ],
                    'component'   => [
                        'name' => 'divi/select',
                        'type' => 'field',
						'props' => [
							'options' => [
								'on' => [
									'label' => 'Light'
								],
								'off' => [
									'label' => 'Dark'
								]
							]
						]
                    ],
                ],
            ],
        ],
        'attributes'        => [
            'class' => 'dark',
        ],
        'childrenSanitizer' => 'et_core_esc_previously',
        'tagName'           => 'span'
    ];
	
    $settings['attributes']['da_alt_close'] = [
        'type'              => 'object',
		'selector'          => '{{selector}} .popup.close-alt',
        'settings'          => [
            'innerContent' => [
                'item' => [
                    'attrName'    => 'da_alt_close.innerContent',
					'subName'     => 'altClose',
                    'label'       => esc_html__( 'Transparent Background', 'divi-popup' ),
                    'description' => esc_html__( 'Here you can choose whether the Close button has a Background color or only displays the Icon.', 'divi-popup' ),
                    'groupSlug'   => 'popupsForDivi',
                    'priority'    => 40,
                    'render'      => true,
                    'features'    => [
						'hover'     => false,
                        'sticky'     => false,
						'responsive' => false,
                        'preset'     => 'content',
                    ],
                    'component'   => [
                        'name' => 'divi/toggle',
                        'type' => 'field',
                    ],
                ],
            ],
        ],
        'attributes'        => [
            'class' => 'close-alt',
        ],
        'childrenSanitizer' => 'et_core_esc_previously',
        'tagName'           => 'span'
    ];
	
    $settings['attributes']['da_has_shadow'] = [
        'type'              => 'object',
		'selector'          => '{{selector}} .popup.no-shadow',
        'settings'          => [
            'innerContent' => [
                'item' => [
                    'attrName'    => 'da_has_shadow.innerContent',
					'subName'     => 'hasShadow',
                    'label'       => esc_html__( 'Add a default Shadow', 'divi-popup' ),
                    'description' => esc_html__( 'Decide whether you want to add a default shadow to your Popup. You should disable this option, when you set a custom Box-Shadow for this Section.', 'divi-popup' ),
                    'groupSlug'   => 'popupsForDivi',
                    'priority'    => 40,
                    'render'      => true,
                    'features'    => [
						'hover'     => false,
                        'sticky'     => false,
						'responsive' => false,
                        'preset'     => 'content',
                    ],
                    'component'   => [
                        'name' => 'divi/toggle',
                        'type' => 'field',
                    ],
                ],
            ],
        ],
        'attributes'        => [
            'class' => 'no-shadow',
        ],
        'childrenSanitizer' => 'et_core_esc_previously',
        'tagName'           => 'span'
    ];
	
    $settings['attributes']['da_with_loader'] = [
        'type'              => 'object',
		'selector'          => '{{selector}} .popup.with-loader',
        'settings'          => [
            'innerContent' => [
                'item' => [
                    'attrName'    => 'da_with_loader.innerContent',
					'subName'     => 'hasShadow',
                    'label'       => esc_html__( 'Show Loader', 'divi-popup' ),
                    'description' => esc_html__( 'Decide whether to display a loading animation inside the Popup. This should be turned on, when the Popup contains an iframe or other content that is loaded dynamically.', 'divi-popup' ),
                    'groupSlug'   => 'popupsForDivi',
                    'priority'    => 40,
                    'render'      => true,
                    'features'    => [
						'hover'     => false,
                        'sticky'     => false,
						'responsive' => false,
                        'preset'     => 'content',
                    ],
                    'component'   => [
                        'name' => 'divi/toggle',
                        'type' => 'field',
                    ],
                ],
            ],
        ],
        'attributes'        => [
            'class' => 'with-loader',
        ],
        'childrenSanitizer' => 'et_core_esc_previously',
        'tagName'           => 'span'
    ];
	
    $settings['attributes']['da_disable_devices'] = [
        'type'              => 'object',
		'selector'          => '{{selector}} .popup.with-loader',
        'settings'          => [
            'innerContent' => [
                'item' => [
                    'attrName'    => 'da_disable_devices.innerContent',
					'subName'     => 'disabledOn',
                    'label'       => esc_html__( 'Disable on', 'divi-popup' ),
                    'description' => esc_html__( 'This will disable the Popup on selected devices.', 'divi-popup' ),
                    'groupSlug'   => 'popupsForDivi',
                    'priority'    => 40,
                    'render'      => true,
                    'features'    => [
						'hover'     => false,
                        'sticky'     => false,
						'responsive' => false,
                        'preset'     => ["style", "html"],
                    ],
                    'component'   => [
                        'name' => 'divi/checkboxes',
                        'type' => 'field',
						'props' => [
							'options' => [
								[
									'id' => 'phone',
									'value' => 'Phone',
									'label' => esc_html__( 'Phone', 'divi-popup' )
								],
								[
									'id' => 'tablet',
									'value' => 'Tablet',
									'label' => esc_html__( 'Tablet', 'divi-popup' )
								],
								[
									'id' => 'desktop',
									'value' => 'Desktop',
									'label' => esc_html__( 'Desktop', 'divi-popup' )
								]
							]
						]
                    ],
                ],
            ],
        ],
        'childrenSanitizer' => 'et_core_esc_previously',
        'tagName'           => 'span'
    ];

    return $settings;
}


/**
 * Modify the Section module wrapper to add Popup classnames.
 *
 * @param string $module_wrapper The module wrapper output.
 * @param array  $args           The filter arguments.
 *
 * @return string The modified module wrapper output.
 */
function pfd_divi5_module_wrapper_classnames_value( $wrapper_classnames_value, $args ) {
	
    $module_name     = $args['name'] ?? '';
    $module_attrs    = $args['attrs'] ?? '';
    $module_elements = $args['elements'] ?? '';
	
    if ( 'divi/section' !== $module_name ) {
		
        return et_core_esc_previously( $wrapper_classnames_value );
    }
	
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
		'da_disable_devices' => [ '', '', '' ],
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
	
	$attrs = $da_default;
	$classes = [];
	
	if ( $wrapper_classnames_value !== '' ) {
		
		$classes = explode( ' ', $wrapper_classnames_value );

		$classes = array_diff( $classes, $special_classes );
	}
	
	if ( isset( $module_attrs['da_is_popup'] ) 
			&& 'on' === $module_attrs['da_is_popup']['innerContent']['desktop']['value']['isPopup'] ) {
				
		$attrs['da_is_popup'] = 'on';	
	}
	
	if ( isset( $module_attrs['da_exit_intent'] ) 
			&& 'on' === $module_attrs['da_exit_intent']['innerContent']['desktop']['value'] ) {
				
		$attrs['da_exit_intent'] = 'on';	
	}
	
	if ( isset( $module_attrs['da_has_close'] ) 
			&& 'off' === $module_attrs['da_has_close']['innerContent']['desktop']['value']['showCloseButton'] ) {
				
		$attrs['da_has_close'] = 'off';	
	}
	
	if ( isset( $module_attrs['da_alt_close'] ) 
			&& 'on' === $module_attrs['da_alt_close']['innerContent']['desktop']['value']['altClose'] ) {
				
		$attrs['da_alt_close'] = 'on';	
	}
	
	if ( isset( $module_attrs['da_dark_close'] ) 
			&& 'on' === $module_attrs['da_dark_close']['innerContent']['desktop']['value']['darkClose'] ) {
				
		$attrs['da_dark_close'] = 'on';	
	}
	
	if ( isset( $module_attrs['da_not_modal'] ) 
			&& 'off' === $module_attrs['da_not_modal']['innerContent']['desktop']['value']['notModal'] ) {
				
		$attrs['da_not_modal'] = 'off';	
	}
	
	if ( isset( $module_attrs['da_is_singular'] ) 
			&& 'on' === $module_attrs['da_is_singular']['innerContent']['desktop']['value'] ) {
				
		$attrs['da_is_singular'] = 'on';	
	}
	
	if ( isset( $module_attrs['da_with_loader'] ) 
			&& 'on' === $module_attrs['da_with_loader']['innerContent']['desktop']['value'] ) {
				
		$attrs['da_with_loader'] = 'on';	
	}
	
	if ( isset( $module_attrs['da_has_shadow'] ) 
			&& 'off' === $module_attrs['da_has_shadow']['innerContent']['desktop']['value']['hasShadow'] ) {
				
		$attrs['da_has_shadow'] = 'off';	
	}
	
	$attrs['da_disable_devices'][0] = 'off';
	$attrs['da_disable_devices'][1] = 'off';
	$attrs['da_disable_devices'][2] = 'off';
	if ( isset( $module_attrs['da_disable_devices'] ) ) {
		
		$disabledOn = $module_attrs['da_disable_devices']['innerContent']['desktop']['value']['disabledOn'];
		
		// Convert Divi 4 values to Divi 5
		if ( ! is_array( $disabledOn )
			&& false !== strpos( $disabledOn, '|' ) ) {
			
			$d4Values = explode( '|', $disabledOn );
			
			$disabledOn = [];
			
			$disabledOn[0] = "on" === $d4Values[0] ? 'Phone' : '';
			$disabledOn[1] = "on" === $d4Values[1] ? 'Tablet' : '';
			$disabledOn[2] = "on" === $d4Values[2] ? 'Desktop' : '';
		}
		
		if ( false !== array_search( 'Phone', $disabledOn ) ) {
				
			$attrs['da_disable_devices'][0] = 'on';	
		}
		
		if ( false !== array_search( 'Tablet', $disabledOn ) ) {
				
			$attrs['da_disable_devices'][1] = 'on';	
		}
		
		if ( false !== array_search( 'Desktop', $disabledOn ) ) {
				
			$attrs['da_disable_devices'][2] = 'on';	
		}
	}
	
	// Set the class to match all attributes.
	if ( isset( $attrs['da_is_popup'] ) ) {
		
		if ( 'on' === $attrs['da_is_popup'] ) {
			
			$classes[] = 'popup';
		}
		
		if ( 'on' === $attrs['da_exit_intent'] ) {
				
			$classes[] = 'on-exit';
		}
		if ( 'off' === $attrs['da_has_close'] ) {
				
			$classes[] = 'no-close';
		}
		if ( 'on' === $attrs['da_alt_close'] ) {
				
			$classes[] = 'close-alt';
		}
		if ( 'on' === $attrs['da_dark_close'] ) {
				
			$classes[] = 'dark';
		}
		if ( 'off' === $attrs['da_not_modal'] ) {
				
			$classes[] = 'is-modal';
		}
		if ( 'on' === $attrs['da_is_singular'] ) {
				
			$classes[] = 'single';
		}
		if ( 'on' === $attrs['da_with_loader'] ) {
				
			$classes[] = 'with-loader';
		}
		if ( 'off' === $attrs['da_has_shadow'] ) {
				
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
	}
	
	if ( $classes ) {
		$classes = implode( ' ', $classes );
	}
	
	return et_core_esc_previously( $classes );
}


/**
 * Modify the Section module wrapper to add Popup ID attribute
 *
 * @param string $module_wrapper The module wrapper output.
 * @param array  $args           The filter arguments.
 *
 * @return string The modified module wrapper output.
 */
function pfd_divi5_filter_wrapper_render( $module_wrapper, $args ): string {
	
    $module_name     = $args['name'] ?? '';
    $module_attrs    = $args['attrs'] ?? '';
    $module_elements = $args['elements'] ?? '';
	
    if ( 'divi/section' !== $module_name ) {
		
        return et_core_esc_previously( $module_wrapper );
    }
	
    $html = $module_wrapper;
	
    // Set meta charset as UTF-8
    $metacharset = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
	
    // Prepend meta charset declaration to treat string as UTF-8
    $html = $metacharset . $html;
	
    // Create a new DOMDocument instance and load the HTML content of the module wrapper.
    $dom = new \DOMDocument();
    libxml_use_internal_errors( true ); // Suppress warnings for invalid HTML.
    $dom->preserveWhiteSpace = true;
    $dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    libxml_clear_errors();

    // Bail early if the Section module content element doesn't exist.
    $xpath           = new \DOMXPath( $dom );
    $content_class   = 'et_pb_section';
    $content_element = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' $content_class ')]" );
	
    if ( is_null( $content_element ) 
		|| ! $content_element
		|| $content_element->length === 0
		|| ! isset( $module_attrs['da_popup_slug'] ) ) {
		
        return et_core_esc_previously( $module_wrapper );
    }
	
	// Bail early if section module Popup ID doesn't exist.
	$popup_id = $module_attrs['da_popup_slug']['innerContent']['desktop']['value'] ?? false;
	
	if ( ! $popup_id ) {
		
		return et_core_esc_previously( $module_wrapper );
	}
	
	foreach( $content_element as $node ) {
		
		$node->setAttribute( 'id', $popup_id );
	}
	
    $popup = $dom->getElementById( $popup_id );
	
    // Save the updated HTML content of the module wrapper.
    $htmlUpdated = $dom->saveHTML( $popup );
	
	return et_core_esc_previously( $htmlUpdated );
}


// Add a filter to modify the module conversion outline for the 'et_pb_section' module.
function pfd_divi5_et_pb_section_moduleConversionOutline ( $conversion_outline, $module_name ) {
		
	// Check if the module name is 'et_pb_section'.
	if ( 'et_pb_section' !== $module_name ) {
		
		// Return the original conversion outline if the module is not 'et_pb_section'.
		return $conversion_outline;
	}
	
	$conversion_outline['module']['da_is_popup'] = 'da_is_popup.innerContent.*.isPopup';
	$conversion_outline['module']['da_popup_slug'] = 'da_popup_slug.innerContent.*';
	$conversion_outline['module']['da_exit_intent'] = 'da_exit_intent.innerContent.*';
	$conversion_outline['module']['da_has_close'] = 'da_has_close.innerContent.*.showCloseButton';
	$conversion_outline['module']['da_dark_close'] = 'da_dark_close.innerContent.*.darkClose';
	$conversion_outline['module']['da_not_modal'] = 'da_not_modal.innerContent.*.notModal';
	$conversion_outline['module']['da_is_singular'] = 'da_is_singular.innerContent.*';
	$conversion_outline['module']['da_alt_close'] = 'da_alt_close.innerContent.*.altClose';
	$conversion_outline['module']['da_has_shadow'] = 'da_has_shadow.innerContent.*.hasShadow';
	$conversion_outline['module']['da_with_loader'] = 'da_with_loader.innerContent.*';
	$conversion_outline['module']['da_disable_devices'] = 'da_disable_devices.innerContent.*.disabledOn';

	// Return the modified conversion outline.
	return $conversion_outline;
}
