<?php
/**
 * Create and retrieve Stripe products for campaigns.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Product
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.4.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Product' ) ) :

	/**
	 * Charitable_Stripe_Product
	 *
	 * @since 1.4.0
	 */
	class Charitable_Stripe_Product {

		/**
		 * Campaign ID.
		 *
		 * @since 1.4.0
		 *
		 * @var   int
		 */
		private $campaign_id;

		/**
		 * Options.
		 *
		 * @since 1.4.0
		 *
		 * @var   array|null
		 */
		private $options;

		/**
		 * Product object.
		 *
		 * @since 1.4.0
		 *
		 * @var   \Stripe\Product
		 */
		private $product;

		/**
		 * Create class object.
		 *
		 * @since 1.4.0
		 *
		 * @param int        $campaign_id The campaign id.
		 * @param array|null $options     Mixed set of options to pass to API request.
		 */
		public function __construct( $campaign_id, $options = null ) {
			$this->campaign_id = $campaign_id;
			$this->options     = $options;
		}

		/**
		 * Return a class property if set.
		 *
		 * @since  1.4.3
		 *
		 * @param  string $prop The class property to return.
		 * @return mixed Returns null if the class property is not set.
		 */
		public function __get( $prop ) {
			return isset( $this->$prop ) ? $this->$prop : null;
		}

		/**
		 * Return a particular property of the Product.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $property The property to get.
		 * @return mixed
		 */
		public function get( $property ) {
			$product = $this->get_product();

			return is_null( $product ) ? null : $product->$property;
		}

		/**
		 * Get the product.
		 *
		 * @since  1.4.0
		 *
		 * @return \Stripe\Product|null
		 */
		public function get_product() {
			if ( ! isset( $this->product ) ) {
				$product_id = get_post_meta( $this->campaign_id, 'stripe_product_id', true );

				if ( ! $product_id ) {
					return null;
				}

				/* The product may have been deleted within Stripe, so make sure we can retrieve it. */
				try {
					$this->product = \Stripe\Product::retrieve( $product_id, $this->options );
				} catch( Exception $e ) {
					return null;
				}
			}

			return $this->product;
		}

		/**
		 * Create a product in Stripe for the campaign.
		 *
		 * @since  1.4.0
		 *
		 * @return string|false The product id if successful. False otherwise.
		 */
		public function create_product() {
			/**
			 * Filter product args.
			 *
			 * @since 1.4.3
			 *
			 * @param array                     $product_args The product args to be sent to Stripe.
			 * @param Charitable_Stripe_Product $product      This product object.
			 */
			$product_args = apply_filters(
				'charitable_stripe_product_args',
				[
					'name'                 => get_the_title( $this->campaign_id ),
					'type'                 => 'service',
					'statement_descriptor' => $this->get_statement_descriptor(),
					'metadata'             => [
						'campaign_id' => $this->campaign_id,
					],
				],
				$this
			);

			try {
				$this->product = \Stripe\Product::create( $product_args, $this->options );

				update_post_meta( $this->campaign_id, 'stripe_product_id', $this->product->id );

				return $this->product->id;

			} catch ( Exception $e ) {
				/* Log the error message and return false. */
				error_log( 'STRIPE - Error creating product: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

				return false;
			}
		}

		/**
		 * Return the statement_descriptor value.
		 *
		 * @since  1.3.0
		 *
		 * @return string
		 */
		public function get_statement_descriptor() {
			$format     = charitable_get_option( [ 'gateways_stripe', 'statement_descriptor' ], 'auto' );
			$descriptor = substr( charitable_get_option( [ 'gateways_stripe', 'statement_descriptor_custom' ], '' ), 0, 22 );

			if ( 'auto' == $format || empty( $descriptor ) ) {
				/**
				 * Filter the automatically formatted statement_descriptor for products.
				 *
				 * @since 1.4.0
				 *
				 * @param string                    $descriptor The default descriptor.
				 * @param Charitable_Stripe_Product $product    The Stripe product instance.
				 */
				$descriptor = apply_filters( 'charitable_stripe_product_statement_descriptor', substr( get_the_title( $this->campaign_id ), 0, 22 ), $this );
			}

			return $descriptor;
		}
	}

endif;
