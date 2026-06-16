<?php
/**
 * The class that defines how campaigns are managed on the admin side.
 *
 * @package   Charitable/Admin/Charitable_Campaign_Meta_Boxes
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.45
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Meta_Boxes' ) ) :

	/**
	 * Charitable_Campaign_Meta_Boxes class.
	 *
	 * @final
	 * @since 1.0.0
	 */
	final class Charitable_Campaign_Meta_Boxes {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Campaign_Meta_Boxes|null
		 */
		private static $instance = null;

		/**
		 * Meta Box Helper class instance.
		 *
		 * @var     Charitable_Meta_Box_Helper
		 */
		private $meta_box_helper;

		/**
		 * Create object instance.
		 *
		 * @since  1.0.0
		 */
		private function __construct() {
			$this->meta_box_helper = new Charitable_Meta_Box_Helper( 'charitable-campaign' );

			add_filter( 'default_title', array( $this, 'default_campaign_name' ) );
		}


	/**
	 * Set default campaign name from URL parameters.
	 *
	 * @since  1.0.0
	 * @version 1.8.9.1
	 *
	 * @param  string $title The default title.
	 * @return string
	 */
	public function default_campaign_name( $title ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['post_type'] ) || ! isset( $_GET['campaign_name'] ) ) {
			return $title;
		}

		$post_type     = sanitize_key( wp_unslash( $_GET['post_type'] ) );
		$campaign_name = sanitize_text_field( wp_unslash( $_GET['campaign_name'] ) );

		if ( 'campaign' !== $post_type || '' === trim( $campaign_name ) ) {
			return $title;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return urldecode( esc_html( $campaign_name ) );
	}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Campaign_Meta_Boxes
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Return the instance of the meta box helper.
		 *
		 * @since  1.6.0
		 *
		 * @return Charitable_Meta_Box_Helper
		 */
		public function get_meta_box_helper() {
			return $this->meta_box_helper;
		}

		/**
		 * Add meta boxes.
		 *
		 * @see add_meta_boxes hook
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function add_meta_boxes() {
			$meta_boxes = array_merge(
				$this->get_campaign_top_meta_boxes(),
				array(
					array(
						'id'       => 'campaign-settings',
						'title'    => __( 'Campaign Settings', 'charitable' ),
						'context'  => 'normal',
						'priority' => 'high',
						'view'     => 'metaboxes/campaign-settings',
					),
				)
			);

			/**
			 * Filter campaign meta boxes.
			 *
			 * @since 1.0.0
			 *
			 * @param array $meta_boxes The meta boxes.
			 */
			$meta_boxes = apply_filters( 'charitable_campaign_meta_boxes', $meta_boxes );

			foreach ( $meta_boxes as $meta_box ) {
				add_meta_box(
					$meta_box['id'],
					$meta_box['title'],
					array( $this->meta_box_helper, 'metabox_display' ),
					Charitable::CAMPAIGN_POST_TYPE,
					$meta_box['context'],
					$meta_box['priority'],
					$meta_box
				);
			}
		}

		/**
		 * Display fields at the very top of the page.
		 *
		 * @since  1.0.0
		 *
		 * @param  WP_Post $post Current post.
		 * @return void
		 */
		public function campaign_form_top( $post ) {
			if ( Charitable::CAMPAIGN_POST_TYPE === $post->post_type ) {
				do_meta_boxes( Charitable::CAMPAIGN_POST_TYPE, 'campaign-top', $post );
			}
		}

		/**
		 * Return campaign settings panels.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		public function get_campaign_settings_panels() {
			$panels = charitable()->campaign_fields()->get_sections( 'admin' );

			foreach ( $panels as $section => $label ) {
				$panels[ $section ] = array(
					'title'  => $label,
					'fields' => $this->get_section_fields( $section ),
				);

				if ( 'campaign-donation-options' === $section ) {
					/**
					 * Filter the fields in the Donation Options panel.
					 *
					 * @since 1.0.0
					 *
					 * @param array $fields The array of fields.
					 */
					$panels[ $section ]['fields'] = apply_filters( 'charitable_campaign_donation_options_fields', $panels[ $section ]['fields'] );
				}
			}

			$panels = $this->add_legacy_meta_boxes( $panels );

			/**
			 * Filter out empty panels.
			 */
			$panels = array_filter( $panels, array( $this, 'panel_has_fields' ) );

			/**
			 * Filter the array of settings panels.
			 *
			 * @since 1.6.0
			 *
			 * @param $panels array Set of panels.
			 */
			return apply_filters( 'charitable_campaign_settings_panels', $panels );
		}

		/**
		 * Save meta for the campaign.
		 *
		 * @since  1.0.0
		 * @version 1.8.9.1
		 *
		 * @param  int     $campaign_id The campaign ID.
		 * @param  WP_Post $post        Current Post object.
		 * @return void
		 */
		public function save_campaign( $campaign_id, WP_Post $post ) {
			if ( ! $this->meta_box_helper->user_can_save( $campaign_id ) ) {
				return;
			}

			$meta_keys = $this->get_campaign_meta_keys();
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array data, sanitized in loop
			$submitted = $_POST;
			// phpcs:enable WordPress.Security.NonceVerification.Missing
			$data      = array(
				'ID' => $campaign_id,
			);

			foreach ( $meta_keys as $key ) {
				$value = isset( $submitted[ $key ] ) ? $submitted[ $key ] : false;

				/**
				 * This filter is deprecated. Use charitable_sanitize_campaign_meta{$key} instead.
				 *
				 * @deprecated 1.7.0
				 *
				 * @since 1.4.12 Deprecated.
				 */
				$value = apply_filters( 'charitable_sanitize_campaign_meta', $value, $key, $submitted, $campaign_id );

				if ( $key === '_campaign_suggested_donations_default' && isset( $value[0] ) && isset( $submitted['_campaign_suggested_donations'][ $value[0] ]['amount'] ) ) {
					$value = ( false !== $submitted['_campaign_suggested_donations'][ $value[0] ]['amount'] ) ? array( $submitted['_campaign_suggested_donations'][ $value[0] ]['amount'] ) : false;
				}

				/**
				 * Filter this meta value. The filter hook is
				 * charitable_sanitize_campaign_meta{$key}. For example,
				 * for _campaign_end_date the filter hook will be:
				 * charitable_sanitize_campaign_meta_campaign_end_date
				 *
				 * @since 1.4.12
				 *
				 * @param mixed $value       The submitted value, or false.
				 * @param array $submitted   All of the submitted values.
				 * @param int   $campaign_id The campaign ID.
				 */
				$data[ $key ] = apply_filters( 'charitable_sanitize_campaign_meta' . $key, $value, $submitted, $campaign_id );
			}

			$processor = new Charitable_Campaign_Processor( $data, $meta_keys );
			$processor->save_meta();

			/**
			 * Do something with the posted data.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_Post $post An instance of `WP_Post`.
			 */
			do_action( 'charitable_campaign_save', $post );
		}

		/**
		 * Set default post content when the extended description is left empty.
		 *
		 * @since  1.4.0
		 *
		 * @param  array $data Submitted data.
		 * @return array
		 */
		public function set_default_post_content( $data ) {
			if ( Charitable::CAMPAIGN_POST_TYPE !== $data['post_type'] ) {
				return $data;
			}

			if ( 0 === strlen( $data['post_content'] ) ) {
				$data['post_content'] = '<!-- Code is poetry -->';
			}

			return $data;
		}

		/**
		 * Sets the placeholder text of the campaign title field.
		 *
		 * @since  1.0.0
		 * @version 1.8.1.15
		 *
		 * @param  string  $placeholder Placeholder text.
		 * @param  WP_Post $post        Current Post object.
		 * @return string
		 */
		public function campaign_enter_title( $placeholder, WP_Post $post ) {
			if ( 'campaign' == $post->post_type ) {
				$placeholder = __( 'Enter campaign title', 'charitable' );
			}

			return $placeholder;
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @global WP_Post $post
		 * @global int     $post_ID
		 * @param  array $messages Messages to display.
		 * @return array
		 */
		public function post_messages( $messages ) {
			global $post, $post_ID;

			$messages[ Charitable::CAMPAIGN_POST_TYPE ] = array(
				// Unused. Messages start at index 1.
				0  => '',
				1  => sprintf(
					// Translators: %s is the permalink to the campaign.
					__( 'Campaign updated. <a href="%s">View Campaign</a>', 'charitable' ),
					esc_url( get_permalink( $post_ID ) )
				),
				2  => __( 'Custom field updated.', 'charitable' ),
				3  => __( 'Custom field deleted.', 'charitable' ),
				4  => __( 'Campaign updated.', 'charitable' ),
				5  => // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only, no action
					isset( $_GET['revision'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					? sprintf(
						// Translators: %s is the revision date.
						__( 'Campaign restored to revision from %s', 'charitable' ),
						wp_post_revision_title( (int) sanitize_text_field( wp_unslash( $_GET['revision'] ) ), false ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					)
					: false,
				6  => sprintf(
					// Translators: %s is the permalink to the campaign.
					__( 'Campaign published. <a href="%s">View Campaign</a>', 'charitable' ),
					esc_url( get_permalink( $post_ID ) )
				),
				7  => __( 'Campaign saved.', 'charitable' ),
				8  => sprintf(
					// Translators: %s is the preview link to the campaign.
					__( 'Campaign submitted. <a target="_blank" href="%s">Preview Campaign</a>', 'charitable' ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
				9  => sprintf(
					// Translators: %1$s is the date the campaign is scheduled for, %2$s is the preview link to the campaign.
					__( 'Campaign scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Campaign</a>', 'charitable' ),
					date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ),
					esc_url( get_permalink( $post_ID ) )
				),
				10 => sprintf(
					// Translators: %s is the preview link to the campaign.
					__( 'Campaign draft updated. <a target="_blank" href="%s">Preview Campaign</a>', 'charitable' ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
			);

			return $messages;
		}

		/**
		 * Return the set of meta boxes to be shown in the "campaign-top" section of the campaign editor.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		private function get_campaign_top_meta_boxes() {
			$boxes = $this->get_section_fields( 'campaign-top' );

			foreach ( $boxes as $key => $box ) {
				$box['id']       = str_replace( '_', '-', substr( $key, 1 ) );
				$box['title']    = $box['label'];
				$box['context']  = 'campaign-top';
				$box['priority'] = 'high';
				$boxes[ $key ]   = $box;
			}

			return $boxes;
		}

		/**
		 * Add panels for any meta boxes previously added to the campaign-advanced section
		 * using the `charitable_campaign_meta_boxes` filter.
		 *
		 * @since  1.6.0
		 *
		 * @param  array $panels Core registered panels.
		 * @return array
		 */
		private function add_legacy_meta_boxes( $panels ) {
			foreach ( apply_filters( 'charitable_campaign_meta_boxes', array() ) as $panel ) {
				if ( 'campaign-advanced' != $panel['context'] ) {
					continue;
				}

				$id = $panel['id'];

				unset( $panel['id'] );

				$panels[ $id ] = $panel;
			}

			return $panels;
		}

		/**
		 * Return the campaign meta keys to save.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		public function get_campaign_meta_keys() {
			$registry = charitable()->campaign_fields();
			$keys     = $registry->get_sanitized_keys( $registry->get_admin_form_fields() );

			$keys[] = '_campaign_suggested_donations_default';
			$keys[] = '_campaign_hide_amount_donated';
			$keys[] = '_campaign_hide_number_of_donors';
			$keys[] = '_campaign_hide_percent_raised';
			$keys[] = '_campaign_hide_time_remaining';
			$keys[] = '_campaign_donate_button_text';

			/**
			 * Filter the set of meta keys to be saved.
			 *
			 * @since 1.0.0
			 *
			 * @param string[] $keys An array of strings representing meta keys.
			 */
			return apply_filters( 'charitable_campaign_meta_keys', $keys );
		}

		/**
		 * Set a field's initial value.
		 *
		 * @since  1.6.0
		 *
		 * @param  array  $field Field definition.
		 * @param  string $key   The key of the field.
		 * @return array
		 */
		private function sanitize_campaign_field( $field, $key ) {
			$field_name = preg_replace( '/^_campaign_/', '', $key );

			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( array_key_exists( $key, $_POST ) ) {
				$field['value'] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				return $field;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			/* Checkboxes don't need a value set. */
			if ( 'checkbox' != $field['type'] ) {
				$field['value'] = array_key_exists( 'default', $field ) ? $field['default'] : '';
			}

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( ! array_key_exists( 'post', $_GET ) ) {
				return $field;
			}

			$campaign = charitable_get_campaign( intval( wp_unslash( $_GET['post'] ) ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			if ( in_array( $campaign->post_status, array( 'auto-draft' ) ) ) {
				return $field;
			}

			if ( array_key_exists( 'value_callback', $field ) && is_callable( $field['value_callback'] ) ) {
				$field['value'] = call_user_func( $field['value_callback'], $campaign, $key );
				return $field;
			}

			$value = $campaign->get( $field_name );

			if ( ! $value ) {
				return $field;
			}

			if ( 'checkbox' == $field['type'] ) {
				$field['checked'] = $value;
			} else {
				$field['value'] = $value;
			}

			return $field;
		}

		/**
		 * Return the fields in a particular section.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $section The section.
		 * @return array
		 */
		private function get_section_fields( $section ) {
			$registry = charitable()->campaign_fields();
			$fields   = $registry->get_admin_form_fields( $section );

			if ( empty( $fields ) ) {
				return array();
			}

			$keys = $registry->get_sanitized_keys( $fields, false );

			return array_combine(
				$keys,
				array_map( array( $this, 'sanitize_campaign_field' ), wp_list_pluck( $fields, 'admin_form' ), $keys )
			);
		}

		/**
		 * Returns whether a particular panel has fields.
		 *
		 * @since  1.6.0
		 *
		 * @param  array $panel The panel definition.
		 * @return boolean
		 */
		private function panel_has_fields( $panel ) {
			return array_key_exists( 'view', $panel ) || count( $panel['fields'] );
		}
	}

endif;
