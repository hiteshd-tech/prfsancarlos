<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles loading all the field types (default and others possibly from optional addons) into the builder.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 */
class Charitable_Builder_Fields {

	/**
	 * Current field data.
	 *
	 * @since 1.8.0
	 *
	 * @var array
	 */
	public $pro_fields = array();

	/**
	 * Primary class constructor.
	 *
	 * @since 1.8.0
	 */
	public function __construct() {

		$this->init();
	}

	/**
	 * Load and init the base field class.
	 *
	 * @since 1.8.0
	 */
	public function init() {

		// Parent class template.
		require_once Charitable()->get_path( 'includes' ) . 'admin/campaign-builder/fields/class-base.php';

		// WordPress hooks.
		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.0
	 */
	private function hooks() {

		// Load default fields on WP init.
		add_action( 'init', [ $this, 'load' ] );

		add_filter( 'charitable_campaign_builder_load_fields', [ $this, 'load_pro_fields' ], 100, 2 );

		add_filter( 'charitable_builder_field_button_attributes', [ $this, 'fields_attributes' ], 100, 2 );

		add_filter( 'charitable_builder_design_buttons', [ $this, 'pro_field_buttons' ], 15 );

		add_action( 'init', array( $this, 'setup_pro_addons' ), 999 ); // 1.8.3.5
	}

	/**
	 * Adds pro fields to the button list.
	 *
	 * @since 1.8.0
	 *
	 * @param array $fields Field types.
	 *
	 * @return array Fields array.
	 */
	public function pro_field_buttons( $fields ) {

		if ( false === $fields || ! is_array( $fields ) ) {
			return $fields;
		}

		$fields['pro']['fields'] = (array) $this->pro_fields;

		return $fields;
	}

	/**
	 * Load default field types.
	 *
	 * @since 1.8.0
	 */
	public function load() {

		$fields = [
			'campaign-title',
			'campaign-description',
			'donate-button',
			'donate-amount',
			'progress-bar',
			// 'donation-options',
			'photo',
			'organizer',
			'html',
			'social-sharing',
			'social-links',
			'campaign-summary',
			'donate-form',
			'donor-wall',
			'shortcode',
			'text',
		];

		/**
		 * Filters array of fields to be loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param array $fields Field types.
		 */
		$fields = (array) apply_filters(
			'charitable_campaign_builder_load_fields',
			$fields
		);

		if ( ! empty( $fields ) ) :

			foreach ( $fields as $key => $field_type ) {

				// reset the value.
				$file_path = false;

				if ( is_array( $field_type ) ) {

					// if this is an array, it's likely "fnacy" and added by a plugin or addon so look for the key in the array for the field type.
					$field_type_slug = key( $field_type );
					$field_type      = $field_type[ $field_type_slug ];

					// where do we load the class, otherwise pick the "default" path inside core Charitable.
					$file = isset( $field_type['file'] ) && ! empty( $field_type['file'] ) ? esc_html( $field_type['file'] ) : false;

					if ( $file ) {
						$file = wp_slash( charitable()->get_path( 'plugin-directory' ) ) . $file; // /wp-content/plugins/charitable-videos/includes/campaign-builder/fields/class-video.php
					}

				} else {

					$file = charitable()->get_path( 'includes' ) . 'admin/campaign-builder/fields/class-' . $field_type . '.php';

				}

				if ( false !== $file && file_exists( $file ) ) {
					require_once $file;
				}

			}

		endif;

	}

	/**
	 * Filters array of "pro" fields form addons to be loaded.
	 *
	 * @since 1.8.0
	 */
	public function setup_pro_addons() {

		$this->pro_fields = (array) apply_filters( // phpcs:ignore Charitable.PHP.ValidateHooks.InvalidHookName
			'charitable_load_campaign_builder_pro_fields',
			array(
				'donor-comments'   => array(
					'order' => '250',
					'name'  => esc_html__( 'Donor Comments', 'charitable' ),
					'type'  => 'donor-comments',
					'icon'  => 'fa-comment',
					'class' => 'pro',
					'addon' => 'donor-comments',
				),
				'ambassadors-team' => array(
					'order' => '500',
					'name'  => esc_html__( 'Team', 'charitable' ),
					'type'  => 'team',
					'icon'  => 'fa-users',
					'class' => 'pro',
					'addon' => 'ambassador',
				),
				'simple-updates'   => array(
					'order' => '750',
					'name'  => esc_html__( 'Updates', 'charitable' ),
					'type'  => 'simple-updates',
					'icon'  => 'fa-bullhorn',
					'class' => 'pro',
					'addon' => 'simple-updates',
				),
				'video'            => array(
					'order' => '1000',
					'name'  => esc_html__( 'Video', 'charitable' ),
					'type'  => 'video',
					'icon'  => 'fa-film',
					'class' => 'pro',
					'addon' => 'video',
				),
			)
		);
	}

	/**
	 * Filters array of fields to be loaded.
	 *
	 * @since 1.8.0
	 *
	 * @param array $fields Field types.
	 */
	public function load_pro_fields( $fields = false ) {

		if ( false === $fields ) {
			$fields = array();
		}

		foreach ( $this->pro_fields as $pro_field ) {
			if ( ! key_exists( $pro_field['type'], $fields ) ) {
				$fields[] = array( $pro_field['type'] => $pro_field );
			}
		}

		return $fields;

	}

	/**
	 * Adjust attributes on field buttons.
	 *
	 * @since 1.8.0
	 *
	 * @param array $atts  Button attributes.
	 * @param array $field Button properties.
	 *
	 * @return array Attributes array.
	 */
	public function fields_attributes( $atts, $field ) {

		if ( ! charitable_is_pro() && ! empty( $field['class'] ) && $field['class'] === 'pro' ) {
			$atts['class'][] = 'charitable-not-available';
		} elseif ( charitable_is_pro() && ! empty( $field['class'] ) && $field['class'] === 'pro' ) {
			$atts['class'][] = 'charitable-not-installed';
		}

		return $atts;
	}


}

new Charitable_Builder_Fields();
