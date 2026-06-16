<?php
/**
 * Hooks up filters and actions of Divi 5 module.
 *
 * @free    include file
 * @package PopupsForDivi
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Load Divi 5 Module
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'pfd_divi5_enqueue_assets' );

// Register custom Attributes for Divi 5 Section module
add_filter( 'block_type_metadata_settings', 'pfd_divi5_register_custom_divisection_attributes' );

add_filter( 'divi_module_classnames_value', 'pfd_divi5_module_wrapper_classnames_value', 10, 2 );

add_filter( 'divi_module_wrapper_render', 'pfd_divi5_filter_wrapper_render', 10, 2 );

// Add a filter to modify the module conversion outline for the 'et_pb_section' module.
add_filter(
    'divi.moduleLibrary.conversion.moduleConversionOutline', 'pfd_divi5_et_pb_section_moduleConversionOutline', 10, 2 );
	