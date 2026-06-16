<?php
/**
 * Object fit style declaration.
 *
 * @package Divi
 * @since ??
 */

namespace ET\Builder\Packages\StyleLibrary\Declarations\ObjectFit;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\StyleLibrary\Utils\StyleDeclarations;

/**
 * ObjectFit helper class.
 *
 * @since ??
 */
class ObjectFit {
	/**
	 * Generate object-fit and object-position style declarations.
	 *
	 * @since ??
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type array      $attrValue  The value (breakpoint > state > value) of module attribute.
	 *     @type bool|array $important  Optional. Whether to add `!important` tag. Default `false`.
	 *     @type string     $returnType Optional. This is the type of value that the function will return.
	 *                                  Can be either `string` or `key_value_pair`. Default `string`.
	 * }
	 *
	 * @return string|array
	 */
	public static function style_declaration( array $args ) {
		$style_declarations = new StyleDeclarations( $args );
		$fit_attr           = $args['attrValue'] ?? [];

		if ( isset( $fit_attr['objectFit'] ) ) {
			$style_declarations->add( 'object-fit', $fit_attr['objectFit'] );
		}

		if ( isset( $fit_attr['objectPosition'] ) ) {
			$style_declarations->add( 'object-position', $fit_attr['objectPosition'] );
		}

		return $style_declarations->value();
	}
}
