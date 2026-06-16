<?php
/**
 * Returns an array with all compat styles, ordered by the stylesheet they should be added to.
 *
 * @package   Charitable/Compat
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.29
 * @version   1.6.55
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This is a local variable, not a global variable.
$highlight_colour = charitable_get_option( 'highlight_colour', apply_filters( 'charitable_default_highlight_colour', '#f89d35' ) );

return [
	'twentytwenty-style'      => '.mce-btn button{background: transparent;}'
							. '.supports-drag-drop .charitable-drag-drop-dropzone,.campaign-summary,.campaign-loop .campaign,.charitable-donation-form .donation-amounts .donation-amount{background-color:#fff;color:#000;}'
							. '.charitable-form-fields .charitable-fieldset{border:none;padding:0;margin-bottom:2em;}'
							. '#charitable-donor-fields .charitable-form-header,#charitable-user-fields,#charitable-meta-fields{padding-left:0;padding-right:0;}'
							. '.campaign-loop.campaign-grid{margin:0 auto 1em;}'
							. '.charitable-form-field.charitable-form-field-checkbox input[type="checkbox"] {height:1.5rem;width:1.5rem;display:inline-block;}',
	'hello-elementor'         => '.donate-button{color: #fff;}',
	'divi-style'              => '.donate-button.button{color:' . $highlight_colour . ';background:#fff;border-color:' . $highlight_colour . ';}'
							. '#left-area .donation-amounts{padding: 0;}'
							. '.charitable-submit-field .button{font-size:20px;}'
							. '.et_pb_widget .charitable-submit-field .button{font-size:1em;}'
							. '.et_pb_widget .charitable-submit-field .et_pb_button:after{font-size:1.6em;}',
	'solopine_style'          => '.charitable-button{background-color:#161616;color:#fff;font:700 10px/10px "Montserrat", sans-serif;border:none;text-transform:uppercase;padding:14px 15px 14px 16px;letter-spacing:1.5px;}'
							. '.charitable-button.donate-button{background-color:' . $highlight_colour . ';}',
	'twenty-twenty-one-style' => '.charitable-form-field.charitable-form-field-checkbox input[type="checkbox"],.charitable-form-field.charitable-form-field-radio input[type="radio"],.charitable-radio-list input[type=radio] {height:1.5rem;width:1.5rem;display:inline-block;}'
							. '.supports-drag-drop .charitable-drag-drop-dropzone,.campaign-summary,.campaign-loop .campaign,.charitable-donation-form .donation-amounts .donation-amount{background-color:#fff;color:#000;}'
							. '.charitable-form-fields .charitable-fieldset{border:none;padding:0;margin-bottom:2em;}'
							. '#charitable-donor-fields .charitable-form-header,#charitable-user-fields,#charitable-meta-fields{padding-left:0;padding-right:0;}'
							. '.campaign-loop.campaign-grid{margin:0 auto 1em;}',
	'tove-styles-front-end'  => '.charitable-form-fields .charitable-fieldset{background-color:#fff;}',
];
