<?php
/**
 * Sets up the donation meta boxes.
 *
 * @package   Charitable/Classes/Charitable_Donation_Meta_Boxes
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.53
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Meta_Boxes' ) ) :

	/**
	 * Charitable_Donation_Meta_Boxes class.
	 *
	 * @final
	 * @since 1.5.0
	 */
	final class Charitable_Donation_Meta_Boxes {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Donation_Meta_Boxes|null
		 */
		private static $instance = null;

		/**
		 * @var Charitable_Meta_Box_Helper $meta_box_helper
		 */
		private $meta_box_helper;

		/**
		 * Create object instance.
		 *
		 * @since 1.5.0
		 *
		 * @param Charitable_Meta_Box_Helper $helper The meta box helper class.
		 */
		public function __construct( Charitable_Meta_Box_Helper $helper ) {
			$this->meta_box_helper = $helper;
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Donation_Meta_Boxes
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self(
					new Charitable_Meta_Box_Helper( 'charitable-donation' )
				);
			}

			return self::$instance;
		}

		/**
		 * Add needed css classes to body HTML tag.
		 *
		 * @since  1.8.1
		 * @version 1.8.9.1
		 *
		 * @param  string $classes The body classes.
		 * @return string
		 */
		public function add_body_class( $classes = '' ) {

			if ( ! function_exists( 'get_current_screen' ) ) {
				return $classes;
			}

			$screen = get_current_screen();

			if ( 'donation' !== $screen->post_type ) {
				return $classes;
			}
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $_GET['action'] ) ) {
				$classes .= ' charitable-admin-donation-' . esc_attr( sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) . ' ';
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			return $classes;

		}

		/**
		 * Sets up the meta boxes to display on the donation admin page.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function add_meta_boxes() {
			foreach ( $this->get_meta_boxes() as $meta_box_id => $meta_box ) {
				add_meta_box(
					$meta_box_id,
					$meta_box['title'],
					array( $this->meta_box_helper, 'metabox_display' ),
					Charitable::DONATION_POST_TYPE,
					$meta_box['context'],
					$meta_box['priority'],
					$meta_box
				);
			}
		}

		/**
		 * Remove default meta boxes.
		 *
		 * @since  1.5.0
		 *
		 * @global array $wp_meta_boxes Registered meta boxes in WP.
		 * @return void
		 */
		public function remove_meta_boxes() {
			global $wp_meta_boxes;

			$charitable_meta_boxes = $this->get_meta_boxes();

			foreach ( $wp_meta_boxes[ Charitable::DONATION_POST_TYPE ] as $context => $priorities ) {
				foreach ( $priorities as $priority => $meta_boxes ) {
					foreach ( $meta_boxes as $meta_box_id => $meta_box ) {
						if ( ! isset( $charitable_meta_boxes[ $meta_box_id ] ) ) {
							remove_meta_box( $meta_box_id, Charitable::DONATION_POST_TYPE, $context );
						}
					}
				}
			}
		}

		/**
		 * Returns an array of all meta boxes added to the donation post type screen.
		 *
		 * @since  1.5.0
		 * @version 1.8.9.1
		 *
		 * @return array
		 */
		private function get_meta_boxes() {
			$screen = get_current_screen();

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$show_form = isset( $_GET['show_form'] );
			if ( 'donation' == $screen->post_type && ( 'add' == $screen->action || $show_form ) ) {
				$meta_boxes = $this->get_form_meta_box();
			} else {
				$meta_boxes = $this->get_view_meta_boxes();
			}

			/**
			 * Filter the meta boxes to be displayed on a donation overview page.
			 *
			 * @since 1.0.0
			 *
			 * @param array $meta_boxes The array of meta boxes and their details.
			 */
			return apply_filters( 'charitable_donation_meta_boxes', $meta_boxes );
		}

		/**
		 * Return the form meta box.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_form_meta_box() {
			global $post;

			$form       = new Charitable_Admin_Donation_Form( charitable_get_donation( $post->ID ) );
			$meta_boxes = array(
				'donation-form' => array(
					'title'    => __( 'Donation Form', 'charitable' ),
					'context'  => 'normal',
					'priority' => 'high',
					'view'     => 'metaboxes/donation/donation-form',
					'form'     => $form,
				),
				'donation-form-meta' => array(
					'title'    => __( 'Additional Details', 'charitable' ),
					'context'  => 'side',
					'priority' => 'high',
					'view'     => 'metaboxes/donation/donation-form-meta',
					'form'     => $form,
				),
			);

			/**
			 * Filter the meta boxes to be displayed on a donation add/edit page.
			 *
			 * @since 1.0.0
			 *
			 * @param array $meta_boxes The array of meta boxes and their details.
			 */
			return apply_filters( 'charitable_donation_form_meta_boxes', $meta_boxes );
		}

		/**
		 * Return the view meta boxes.
		 *
		 * @since  1.5.0
		 * @since  1.7.0.8
		 *
		 * @return array
		 */
		public function get_view_meta_boxes() {
			global $post;

			$meta_boxes = array(
				'donation-overview' => array(
					'title'    => __( 'Donation Overview', 'charitable' ),
					'context'  => 'normal',
					'priority' => 'high',
					'view'     => 'metaboxes/donation/donation-overview',
				),
				'donation-actions'  => array(
					'title'    => __( 'Donation Actions', 'charitable' ),
					'context'  => 'side',
					'priority' => 'high',
					'view'     => 'metaboxes/actions',
					'actions'  => charitable_get_donation_actions(),
				),
				'donation-details'  => array(
					'title'    => __( 'Donation Details', 'charitable' ),
					'context'  => 'side',
					'priority' => 'high',
					'view'     => 'metaboxes/donation/donation-details',
				),
				'donation-log'      => array(
					'title'    => __( 'Donation Log', 'charitable' ),
					'context'  => 'normal',
					'priority' => 'low',
					'view'     => 'metaboxes/donation/donation-log',
				),
				'donation-donor-history'  => array(
					'title'    => __( 'Related Donations <span class="badge beta">Beta</span>', 'charitable' ),
					'context'  => 'side',
					'priority' => 'low',
					'view'     => 'metaboxes/donation/donation-donor-history',
					'help'     => ''
				),
			);

			/* Get rid of the donation actions meta box if it doesn't apply to this donation. */
			if ( ! charitable_get_donation_actions()->has_available_actions( $post->ID ) ) {
				unset( $meta_boxes['donation-actions'] );
			}

			/**
			 * Filter the meta boxes to be displayed on a donation overview page.
			 *
			 * @since 1.5.0
			 *
			 * @param array $meta_boxes The array of meta boxes and their details.
			 */
			return apply_filters( 'charitable_donation_view_meta_boxes', $meta_boxes );
		}

		/**
		 * Create donation actions instance, with some initial defaults.
		 *
		 * @since  1.6.0
		 *
		 * @return void
		 */
		public function register_donation_actions() {
			$donation_actions = charitable_get_donation_actions();

			foreach ( charitable_get_valid_donation_statuses() as $status => $label ) {
				$donation_actions->register(
					'change_status_to_' . $status,
					array(
						'label'           => $label,
						'callback'        => array( $this, 'change_donation_status' ),
						'button_text'     => __( 'Update Status', 'charitable' ),
						'active_callback' => array( $this, 'can_change_donation_status' ),
						'success_message' => 13,
						'failed_message'  => 14,
						'fields'          => array( $this, 'get_donation_status_change_fields' ),
					),
					__( 'Change Status', 'charitable' )
				);
			}
		}

		/**
		 * Check whether a particular status change works for the given donation.
		 *
		 * @since  1.6.0
		 *
		 * @param  int   $object_id The donation's ID.
		 * @param  array $args      Mixed action arguments.
		 * @return boolean
		 */
		public function can_change_donation_status( $object_id, $args = array() ) {
			$donation = charitable_get_donation( $object_id );

			return $donation && $args['action_args']['label'] !== $donation->get_status_label();
		}

		/**
		 * Change a donation's status.
		 *
		 * @since  1.6.0
		 *
		 * @param  boolean $success   Whether the action has been run.
		 * @param  int     $object_id The donation's ID.
		 * @param  array   $args      Mixed action arguments.
		 * @param  string  $action    The action id.
		 * @return boolean Whether the status was changed.
		 */
		public function change_donation_status( $success, $object_id, $args, $action ) {
			$donation = charitable_get_donation( $object_id );

			if ( ! $donation ) {
				return false;
			}

			$status  = str_replace( 'change_status_to_', '', $action );
			$success = $donation->update_status( $status );

			if ( array_key_exists( 'gateway_refund', $_POST ) && $_POST['gateway_refund'] ) { // phpcs:ignore
				$gateway = $donation->get_gateway();

				/**
				 * Perform a refund in the donation's gateway.
				 *
				 * @since 1.6.0
				 *
				 * @param int $donation_id The donation's ID.
				 */
				do_action( 'charitable_process_refund_' . $gateway, $object_id );
			}

			return 0 !== $success;
		}

		/**
		 * Additional fields to display for a status change.
		 *
		 * @since  1.6.0
		 *
		 * @param  int   $object_id The donation's ID.
		 * @param  array $action    Mixed action arguments.
		 * @return void
		 */
		public function get_donation_status_change_fields( $object_id, $action ) {
			$statuses = charitable_get_valid_donation_statuses();

			if ( $statuses['charitable-refunded'] != $action['label'] ) {
				return;
			}

			$donation = charitable_get_donation( $object_id );

			if ( ! $donation || ! $donation->is_refundable_in_gateway() ) {
				return;
			}

			printf(
				/* translators: %s: gateway name */
				'%s' . esc_html__( 'Refund in %s automatically.', 'charitable' ),
				'<input type="checkbox" name="gateway_refund" value="1" />',
				esc_html( $donation->get_gateway_object()->get_name() )
			);
		}

		/**
		 * Save meta for the donation.
		 *
		 * @since  1.5.0
		 *
		 * @param  int     $donation_id
		 * @param  WP_Post $post
		 * @return void
		 */
		public function save_donation( $donation_id, WP_Post $post ) {
			if ( ! $this->meta_box_helper->user_can_save( $donation_id ) ) {
				return;
			}

			$this->maybe_save_form_submission( $donation_id );

			/* Handle any fired actions */
			if ( ! empty( $_POST['charitable_donation_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				charitable_get_donation_actions()->do_action( sanitize_text_field( $_POST['charitable_donation_action'] ), $donation_id ); // phpcs:ignore
			}

			/**
			 * Hook for plugins to do something else with the posted data.
			 *
			 * @since 1.0.0
			 *
			 * @param int     $donation_id The donation ID.
			 * @param WP_Post $post        Instance of `WP_Post`.
			 */
			do_action( 'charitable_donation_save', $donation_id, $post );
		}

		/**
		 * Save a donation after the admin donation form has been submitted.
		 *
		 * @since  1.5.0
		 *
		 * @param  int $donation_id The donation ID.
		 * @return boolean True if this was a form submission. False otherwise.
		 */
		public function maybe_save_form_submission( $donation_id ) {
			if ( ! $this->is_admin_donation_save() || did_action( 'charitable_before_save_donation' ) ) {
				return false;
			}

			$form = new Charitable_Admin_Donation_Form( charitable_get_donation( $donation_id ) );

			if ( ! $form->validate_submission() ) {
				wp_safe_redirect( admin_url( 'post-new.php?post_type=donation&show_form=1' ) );
				exit();
			}

			charitable_create_donation( $form->get_donation_values() );

			update_post_meta( $donation_id, '_donation_manually_edited', true );

			return true;
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @since  1.5.0
		 * @version 1.8.1.15
		 *
		 * @param  array $messages The post messages.
		 * @return array
		 */
		public function post_messages( $messages ) {
			global $post, $post_ID;

			$messages[ Charitable::DONATION_POST_TYPE ] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf(
					/* translators: %s: link */
					__( 'Donation updated. <a href="%s">View Donation</a>', 'charitable' ),
					esc_url( get_permalink( $post_ID ) )
				),
				2  => __( 'Custom field updated.', 'charitable' ),
				3  => __( 'Custom field deleted.', 'charitable' ),
				4  => __( 'Donation updated.', 'charitable' ),
				5  => // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only, no action
					isset( $_GET['revision'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					? sprintf(
						/* translators: %s: revision title */
						__( 'Donation restored to revision from %s', 'charitable' ),
						wp_post_revision_title( (int) sanitize_text_field( wp_unslash( $_GET['revision'] ) ), false ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					)
					: false,
				6  => sprintf(
					/* translators: %s: link */
					__( 'Donation published. <a href="%s">View Donation</a>', 'charitable' ),
					esc_url( get_permalink( $post_ID ) )
				),
				7  => __( 'Donation saved.', 'charitable' ),
				8  => sprintf(
					/* translators: %s: link */
					__( 'Donation submitted. <a target="_blank" href="%s">Preview Donation</a>', 'charitable' ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
				9  => sprintf(
					/* translators: %1$s: date and time; %2$s: link */
					__( 'Donation scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Donation</a>', 'charitable' ),
					date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ),
					esc_url( get_permalink( $post_ID ) )
				),
				10 => sprintf(
					/* translators: %s: link */
					__( 'Donation draft updated. <a target="_blank" href="%s">Preview Donation</a>', 'charitable' ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
				11 => __( 'Email resent.', 'charitable' ),
				12 => __( 'Email could not be resent.', 'charitable' ),
				13 => __( 'Donation status changed.', 'charitable' ),
				14 => __( 'Donation status could not be changed.', 'charitable' ),
			);

			return $messages;
		}

		/**
		 * Prevent email from sending if we are saving the donation via the admin.
		 *
		 * @since  1.6.15
		 *
		 * @param  boolean             $send_email Whether to send the email.
		 * @param  Charitable_Donation $donation   The donation object.
		 * @return boolean
		 */
		public function maybe_block_new_donation_email( $send_email, Charitable_Donation $donation ) {
			if ( ! $send_email ) {
				return $send_email;
			}

			/* Don't block sending it from donation actions. */
			if ( $this->is_donation_action() ) {
				return $send_email;
			}

			/* If we're not saving the donation, send the email. */
			if ( ! $this->is_admin_donation_save() ) {
				return $send_email;
			}

			/* If this isn't the `charitable_after_save_donation` hook, don't send the email. */
			if ( ! doing_action( 'charitable_after_save_donation' ) ) {
				return false;
			}

			/* If this isn't a manually created donation, send the email. */
			if ( 'manual' !== $donation->get_gateway() ) {
				return $send_email;
			}

			/* Do not send admin notifications for manually created donations. */
			return false;
		}

		/**
		 * Prevent email from sending if we are saving the donation via the admin.
		 *
		 * @since  1.6.15
		 *
		 * @param  boolean             $send_email Whether to send the email.
		 * @param  Charitable_Donation $donation   The donation object.
		 * @return boolean
		 */
		public function maybe_block_donation_receipt_email( $send_email, Charitable_Donation $donation ) {
			if ( ! $send_email ) {
				return $send_email;
			}

			/* Don't block sending it from donation actions. */
			if ( $this->is_donation_action() ) {
				return $send_email;
			}

			/* If we're not saving the donation, send the email. */
			if ( ! $this->is_admin_donation_save() ) {
				return $send_email;
			}

			/* If this isn't the `charitable_after_save_donation` hook, don't send the email. */
			if ( ! doing_action( 'charitable_after_save_donation' ) ) {
				return false;
			}

			/* If this isn't a manually created donation, send the email. */
			if ( 'manual' !== $donation->get_gateway() ) {
				return $send_email;
			}

			/**
			 * Finally, if we're saving a manually created donation, only
			 * send the email if that option was checked in the admin.
			 */
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			return array_key_exists( 'send_donation_receipt', $_POST ) && ! empty( sanitize_text_field( wp_unslash( $_POST['send_donation_receipt'] ) ) );
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Checks whether we are saving the admin donation form.
		 *
		 * @since  1.6.15
		 * @version 1.8.9.1
		 *
		 * @return boolean
		 */
		public function is_admin_donation_save() {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			return array_key_exists( 'charitable_action', $_POST );
		}

		/**
		 * Checks whether we are doing a donation action.
		 *
		 * @since  1.6.15
		 * @version 1.8.9.1
		 *
		 * @return boolean
		 */
		public function is_donation_action() {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			return array_key_exists( 'charitable_donation_action', $_POST );
		}
	}

endif;
