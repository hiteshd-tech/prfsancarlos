<?php
/**
 * Campaign model.
 *
 * @package   Charitable/Classes/Charitable_Campaign
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.54
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign' ) ) :

	/**
	 * Campaign Model
	 *
	 * @since 1.0.0
	 *
	 * @property int    $ID
	 * @property int    $post_author
	 * @property string $post_date
	 * @property string $post_date_gmt
	 * @property string $post_content
	 * @property string $post_title
	 * @property string $post_excerpt
	 * @property string $post_status
	 * @property string $comment_status
	 * @property string $ping_status
	 * @property string $post_password
	 * @property string $post_name
	 * @property string $to_ping
	 * @property string $pinged
	 * @property string $post_modified
	 * @property string $post_modified_gmt
	 * @property string $post_content_filtered
	 * @property int    $post_parent
	 * @property string $guid
	 * @property int    $menu_order
	 * @property string $post_type
	 * @property string $post_mime_type
	 * @property int    $comment_count
	 * @property string $filter
	 */
	class Charitable_Campaign {

		/**
		 * The WP_Post object associated with this campaign.
		 *
		 * @var WP_Post
		 */
		private $post;

		/**
		 * The timestamp for the expiry for this campaign.
		 *
		 * @var int
		 */
		private $end_time;

		/**
		 * The fundraising goal for the campaign.
		 *
		 * @var string|false
		 */
		private $goal;

		/**
		 * The fundraising goal for the campaign.
		 *
		 * @var string|false
		 */
		private $min_donation_amount;

		/**
		 * The donations made to this campaign.
		 *
		 * @var WP_Query|false
		 */
		private $donations;

		/**
		 * The amount donated to the campaign.
		 *
		 * @var int|false
		 */
		private $donated_amount;

		/**
		 * The form object for this campaign.
		 *
		 * @var Charitable_Donation_Form
		 */
		private $donation_form;

		/**
		 * The Charitable_Object_Fields object for this campaign.
		 *
		 * @since 1.6.0
		 *
		 * @var   Charitable_Object_Fields
		 */
		protected $fields;

		/**
		 * Class constructor.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $post The post ID or WP_Post object for this this campaign.
		 */
		public function __construct( $post ) {
			if ( ! $post instanceof WP_Post ) {
				$post = get_post( $post );
			}

			add_filter( 'charitable_campaign_description_template_content', array( $this, 'description_content' ), 10, 2 );

			$this->post = $post;
		}

		/**
		 * Return description content.
		 *
		 * @since  1.7.0.4
		 *
		 * @param  string $content  The content.
		 * @param  object $campaign The campaign object.
		 * @return mixed
		 */
		public function description_content( $content = false, $campaign = false ) {

			// search for YouTube and Vimeo video urls by default and attempt to convert them to oembed.

			preg_match_all( '#(?<=v=|v\/|vi=|vi\/|youtu.be\/)[a-zA-Z0-9_-]{11}#', $content, $matches );

			if ( $matches ) :
				foreach ( $matches as $video_id ) {
					if ( ! empty( $video_id ) ) :
						$video_embed_args = apply_filters( 'charitable_campaign_description_template_video', array(), 'youtube', $video_id, $content );
						$embed            = wp_oembed_get( 'https://youtube.com/watch?v=' . $video_id[0], $video_embed_args );

						$content = str_replace( trim( 'https://youtube.com/watch?v=' . $video_id[0] ), $embed, $content );
						$content = str_replace( trim( 'http://youtube.com/watch?v=' . $video_id[0] ), $embed, $content );
						$content = str_replace( trim( 'https://www.youtube.com/watch?v=' . $video_id[0] ), $embed, $content );
						$content = str_replace( trim( 'http://www.youtube.com/watch?v=' . $video_id[0] ), $embed, $content );
					endif;
				}
			endif;

			preg_match( '/https?:\/\/(?:www\.)?vimeo\.com\/\d{8}/', $content, $matches );

			if ( $matches ) :
				foreach ( $matches as $video_url ) {
					if ( ! empty( $video_id ) ) :
						$video_embed_args = apply_filters( 'charitable_campaign_description_template_video', array(), 'vimeo', $video_id, $content, $campaign );
						$embed            = wp_oembed_get( $video_url, $video_embed_args );

						$content = str_replace( trim( $video_url ), $embed, $content );
					endif;
				}
			endif;

			// allow developers and plugins to hook into content.
			$content = apply_filters( 'charitable_campaign_description_content', $content, $campaign );

			// apply WP's the_content filter, which other plugins can hook into.
			$content = apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'the_content' is a WordPress core filter hook.

			return $content;
		}

		/**
		 * Magic getter.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key The key of the field to get.
		 * @return mixed
		 */
		public function __get( $key ) {
			if ( null !== $this->post && property_exists( $this->post, $key ) ) {
				return $this->post->$key;
			}

			return $this->get( $key );
		}

		/**
		 * Limit properties to be serialized.
		 *
		 * @since  1.6.18
		 *
		 * @return array
		 */
		public function __sleep() {
			return array(
				'post',
				'end_time',
				'goal',
				'donations',
				'donated_amount',
				'donation_form',
			);
		}

		/**
		 * Returns the campaign's post_meta values. _campaign_ is automatically prepended to the meta key.
		 *
		 * @see    get_post_meta
		 *
		 * @since  1.0.0
		 *
		 * @param  string  $key    The field key.
		 * @param  boolean $single Whether to return a single value or an array.
		 * @return mixed This will return an array if single is false. If it's true, the value of the
		 *               meta_value field will be returned.
		 */
		public function get( $key, $single = true ) {
			if ( $this->fields()->has_value_callback( $key ) ) {
				return $this->fields()->get( $key );
			}

			/* Look for a local method. */
			if ( method_exists( $this, 'get_' . $key ) ) {
				$method = 'get_' . $key;
				return $this->$method();
			}

			return $this->get_meta( '_campaign_' . $key, $single );
		}

		/**
		 * Return the campaign's post_meta values.
		 *
		 * @since  since
		 *
		 * @param  string  $meta_name The meta key.
		 * @param  boolean $single    Whether to return a single value or an array.
		 * @return mixed This will return an array if single is false. If it's true, the value of the
		 *               meta_value field will be returned.
		 */
		public function get_meta( $meta_name, $single = true ) {

			if ( empty( $this->post->ID ) ) {
				return;
			}

			/**
			 * Filter the meta value of a particular campaign field.
			 *
			 * @since  1.0.0
			 *
			 * @param  mixed               $value     The meta value.
			 * @param  string              $meta_name The name of the meta field.
			 * @param  boolean             $single    Whether we're returning a single result.
			 * @param  Charitable_Campaign $campaign  This instance of `Charitable_Campaign`.
			 */
			return apply_filters( 'charitable_campaign_get_meta_value', get_post_meta( $this->post->ID, $meta_name, $single ), $meta_name, $single, $this );
		}

		/**
		 * Return the Charitable_Object_Fields instance.
		 *
		 * @since  1.6.0
		 *
		 * @return Charitable_Object_Fields
		 */
		public function fields() {
			if ( ! isset( $this->fields ) ) {
				$this->fields = new Charitable_Object_Fields( charitable()->campaign_fields(), $this );
			}

			return $this->fields;
		}

		/**
		 * Returns the id of the campaign (post).
		 *
		 * @since   1.7.0.9
		 * @version 1.8.0
		 *
		 * @return integer
		 */
		public function get_campaign_id() {
			return ( isset( $this->post->ID ) ) ? intval( $this->post->ID ) : false;
		}

		/**
		 * Returns whether the campaign is endless (i.e. no end date has been set).
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function is_endless() {
			return 0 == $this->get_meta( '_campaign_end_date' );
		}

		/**
		 * Return whether the campaign supports custom donation amounts.
		 *
		 * @since  1.6.53
		 *
		 * @return boolean
		 */
		public function get_allow_custom_donations() {
			return (int) get_post_meta( $this->post->ID, '_campaign_allow_custom_donations', true );
		}

		/**
		 * Return the suggested amounts, or an empty array if there are none.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_suggested_donations() {
			$value = get_post_meta( $this->post->ID, '_campaign_suggested_donations', true );

			if ( ! is_array( $value ) ) {
				$value = array();
			}

			return apply_filters( 'charitable_campaign_suggested_donations', $value, $this );
		}

		/**
		 * Return the suggested amounts, or an empty array if there are none.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_suggested_donations_default() {
			$value = get_post_meta( $this->post->ID, '_campaign_suggested_donations_default', true );

			if ( is_array( $value ) && isset( $value[0] ) ) {
				$value = $value[0];
			}

			return apply_filters( 'charitable_campaign_suggested_donations_default', $value, $this );
		}

		/**
		 * Returns the end date in your preferred format.
		 *
		 * If a format is not provided, the user-defined date_format in WordPress settings is used.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $date_format A date format accepted by PHP's date() function.
		 * @return string|false String if an end date is set. False if campaign has no end date.
		 */
		public function get_end_date( $date_format = '' ) {
			if ( $this->is_endless() ) {
				return false;
			}

			if ( ! strlen( $date_format ) ) {
				$date_format = get_option( 'date_format', 'd/m/Y' );
			}

			/* Filter the end date format using the charitable_campaign_end_date_format hook. */
			$date_format = apply_filters( 'charitable_campaign_end_date_format', $date_format, $this );

			/* This is how the end date is stored in the database, so just return that directly. */
			if ( 'Y-m-d H:i:s' == $date_format ) {
				return $this->get_meta( '_campaign_end_date' );
			}

			return date_i18n( $date_format, $this->get_end_time() );
		}

		/**
		 * Returns the timetamp of the end date.
		 *
		 * @since  1.0.0
		 * @since  1.8.1.10
		 *
		 * @return int|false Int if campaign has an end date. False if campaign has no end date.
		 */
		public function get_end_time() {
			if ( ! isset( $this->end_time ) ) {
				$end_date = $this->get_meta( '_campaign_end_date' );

				if ( ! $end_date ) {
					return false;
				}

				/* The date is stored in the format of Y-m-d H:i:s. */
				$date_time = explode( ' ', $end_date );
				$date      = explode( '-', $date_time[0] );
				$time      = explode( ':', $date_time[1] );

				// Check for undefined array keys. If any of these are not defined, return false.
				if ( ! isset( $date[0] ) || ! isset( $date[1] ) || ! isset( $date[2] ) || ! isset( $time[0] ) || ! isset( $time[1] ) || ! isset( $time[2] ) ) {
					return false;
				}

				$this->end_time = mktime( $time[0], $time[1], $time[2], $date[1], $date[2], $date[0] );
			}

			return $this->end_time;
		}

		/**
		 * Returns the amount of time left in the campaign in seconds.
		 *
		 * @since  1.0.0
		 *
		 * @return int $time_left Int if campaign has an end date. False if campaign has no end date.
		 */
		public function get_seconds_left() {
			if ( $this->is_endless() ) {
				return false;
			}

			$time_left = $this->get_end_time() - current_time( 'timestamp' );

			return $time_left < 0 ? 0 : $time_left;
		}

		/**
		 * Returns the amount of time left in the campaign as a descriptive string.
		 *
		 * @uses charitable_campaign_ended          Change the text displayed when there is no time left.
		 * @uses charitabile_campaign_minutes_left  Change the text displayed when there is less than an hour left.
		 * @uses charitabile_campaign_hours_left    Change the text displayed when there is less than a day left.
		 * @uses charitabile_campaign_days_left     Change the text displayed when there is more than a day left.
		 * @uses charitable_campaign_time_left      Change the text displayed when there is time left.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_time_left() {
			if ( $this->is_endless() ) {
				return '';
			}

			$hour = 3600;
			$day  = 86400;

			$seconds_left = $this->get_seconds_left();

			if ( 0 === $seconds_left ) {

				/* Condition 1: The campaign has finished. */

				$time_left = apply_filters( 'charitable_campaign_ended', __( 'Campaign has ended', 'charitable' ), $this );

			} elseif ( $seconds_left <= $hour ) {

				/* Condition 2: There is less than an hour left. */

				$minutes_remaining = ceil( $seconds_left / 60 );
				$time_left         = apply_filters(
					'charitabile_campaign_minutes_left', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a legacy hook used by other Charitable code. Changing it would break existing functionality.
					// translators: %s is the number of minutes left.
					sprintf( _n( '%s Minute Left', '%s Minutes Left', $minutes_remaining, 'charitable' ), '<span class="amount time-left minutes-left">' . $minutes_remaining . '</span>' ),
					$this
				);

			} elseif ( $seconds_left <= $day ) {

				/* Condition 3: There is less than a day left. */

				$hours_remaining = floor( $seconds_left / 3600 );
				$time_left       = apply_filters(
					'charitabile_campaign_hours_left', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a legacy hook used by other Charitable code. Changing it would break existing functionality.
					// translators: %s is the number of hours left.
					sprintf( _n( '%s Hour Left', '%s Hours Left', $hours_remaining, 'charitable' ), '<span class="amount time-left hours-left">' . $hours_remaining . '</span>' ),
					$this
				);

			} else {

				/* Condition 4: There is more than a day left. */

				$days_remaining = floor( $seconds_left / 86400 );
				$time_left      = apply_filters(
					'charitabile_campaign_days_left', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a legacy hook used by other Charitable code. Changing it would break existing functionality.
					// translators: %s is the number of days left.
					sprintf( _n( '%s Day Left', '%s Days Left', $days_remaining, 'charitable' ), '<span class="amount time-left days-left">' . $days_remaining . '</span>' ),
					$this
				);

			} //end if

			return apply_filters( 'charitable_campaign_time_left', $time_left, $this );
		}

		/**
		 * Returns whether the campaign has ended.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function has_ended() {
			return ! $this->is_endless() && 0 == $this->get_seconds_left();
		}

		/**
		 * Returns whether a campaign is currently able to receive donations.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		public function can_receive_donations() {
			$can = 'active' == $this->get_status();

			/**
			 * Filter whether the campaign can receive donations.
			 *
			 * @since 1.5.0
			 *
			 * @param boolean             $can      Whether the campaign can receive donations.
			 * @param Charitable_Campaign $campaign This instance of `Charitable_Campaign`.
			 */
			return apply_filters( 'charitable_campaign_can_receive_donations', $can, $this );
		}

		/**
		 * Return a text notice to say that a campaign has finished.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_finished_notice() {
			if ( ! $this->has_ended() ) {
				return '';
			}

			if ( ! $this->has_goal() ) {
				// translators: %s is the time since the campaign ended.
				$message = __( 'This campaign ended %s ago', 'charitable' );
			} elseif ( $this->has_achieved_goal() ) {
				// translators: %s is the time since the campaign ended.
				$message = __( 'This campaign successfully reached its funding goal and ended %s ago', 'charitable' );
			} else {
				// translators: %s is the time since the campaign ended.
				$message = __( 'This campaign failed to reach its funding goal %s ago', 'charitable' );
			}

			return apply_filters( 'charitable_campaign_finished_notice', sprintf( $message, '<span class="time-ago">' . human_time_diff( $this->get_end_time() ) . '</span>' ), $this );
		}

		/**
		 * Return the time since the campaign finished, or zero if it's still going.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_time_since_ended() {
			if ( 0 !== $this->get_seconds_left() ) {
				return 0;
			}

			return current_time( 'U' ) - $this->get_end_time();
		}

		/**
		 * Returns the fundraising goal of the campaign.
		 *
		 * @since  1.0.0
		 *
		 * @return string|false  Amount if goal is set. False otherwise.
		 */
		public function get_goal() {
			if ( ! isset( $this->goal ) ) {
				$this->goal = $this->has_goal() ? $this->get_meta( '_campaign_goal' ) : false;
			}

			return $this->goal;
		}

		/**
		 * Returns the minimum donation amount of the campaign.
		 *
		 * @since  1.0.0
		 *
		 * @return string|false  Amount if goal is set. False otherwise.
		 */
		public function get_min_donation_amount() {
			if ( ! isset( $this->min_donation_amount ) ) {
				$this->min_donation_amount = $this->get_meta( '_campaign_min_donation_amount' ) ? $this->get_meta( '_campaign_min_donation_amount' ) : false;
			}

			return $this->min_donation_amount;
		}


		/**
		 * Returns whether a goal has been set (anything greater than $0 is a goal).
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function has_goal() {
			return 0 < (float) $this->get_meta( '_campaign_goal' );
		}

		/**
		 * Returns the fundraising goal formatted as a monetary amount.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_monetary_goal() {
			if ( ! $this->has_goal() ) {
				return '';
			}

			return charitable_format_money( (string) $this->get_meta( '_campaign_goal' ) );
		}

		/**
		 * Returns whether the goal has been achieved.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function has_achieved_goal() {
			return $this->get_donated_amount( true ) >= $this->get_goal();
		}

		/**
		 * Return a message to say whether the campaign has achieved its goal.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $success Optional. The success message.
		 * @param  string $failure Optional. The failure message.
		 * @return string
		 */
		public function get_goal_achieved_message( $success = '', $failure = '' ) {
			if ( $this->has_achieved_goal() ) {

				if ( strlen( $success ) ) {
					return $success;
				}

				/**
				 * Filter the default message to show when a campaign has achieved its goal.
				 *
				 * @since 1.6.0
				 *
				 * @param string              $message  The success message.
				 * @param Charitable_Campaign $campaign This instance of `Charitable_Campaign`.
				 */
				return apply_filters( 'charitable_campaign_goal_achievement_success_message', __( 'The campaign achieved its fundraising goal.', 'charitable' ), $this );
			}

			if ( strlen( $failure ) ) {
				return $failure;
			}

			/**
			 * Filter the default message to show when a campaign has not achieved its goal.
			 *
			 * @since 1.6.0
			 *
			 * @param string              $message  The failure message.
			 * @param Charitable_Campaign $campaign This instance of `Charitable_Campaign`.
			 */
			return apply_filters( 'charitable_campaign_goal_achievement_failure_message', __( 'The campaign did not reach its fundraising goal.', 'charitable' ), $this );
		}

		/**
		 * Return the campaign status.
		 *
		 * If the campaign is published, this will return whether either 'active' or 'finished'.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_status() {

			$status = $post_status = isset( $this->post ) && ! empty( $this->post->post_status ) ? $this->post->post_status : false; // phpcs:ignore

			if ( 'publish' == $status ) {
				$status = $this->has_ended() ? 'finished' : 'active';
			}

			/**
			 * Filter the returned campaign status.
			 *
			 * @since 1.0.0
			 *
			 * @param string              $status      The campaign status.
			 * @param string              $post_status The post_status of the campaign.
			 * @param Charitable_Campaign $campaign    This instance of `Charitable_Campaign`.
			 */
			return apply_filters( 'charitable_campaign_status', $status, $post_status, $this );
		}

		/**
		 * Return a status key for the campaign.
		 *
		 * This will return one of the following:
		 *
		 * inactive : A campaign that is not published.
		 * ended : A campaign without a goal has finished
		 * successful : A campaign with a goal has finished & achieved its goal
		 * unsucessful : A campaign with a goal has finished without achieving its goal
		 * ending : A campaign is ending soon.
		 * active : A campaign that is active and not ending soon.
		 *
		 * @since  1.3.7
		 *
		 * @return string
		 */
		public function get_status_key() {
			/**
			 * Set the threshold in seconds for defining whether a campaign is ending soon.
			 *
			 * @since 1.0.0
			 *
			 * @param int $seconds By default, this equals 604,800.
			 */
			$ending_soon_threshold = apply_filters( 'charitable_campaign_ending_soon_threshold', WEEK_IN_SECONDS );
			$ended                 = $this->has_ended();

			if ( 'publish' != $this->post->post_status ) {
				return 'inactive';
			}

			if ( $ended && ! $this->has_goal() ) {
				return 'ended';
			}

			if ( $ended && $this->has_achieved_goal() ) {
				return 'successful';
			}

			if ( $ended ) {
				return 'unsucessful';
			}

			if ( ! $this->is_endless() && $this->get_seconds_left() < $ending_soon_threshold ) {
				return 'ending';
			}

			return 'active';
		}

		/**
		 * Return the campaign status tag.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean $show_achievement Whether to show the achievement status tag.
		 * @param  boolean $show_active_tag  Whether to show the active status tag.
		 *
		 * @return string
		 */
		public function get_status_tag( $show_achievement = null, $show_active_tag = null ) {
			$key = $this->get_status_key();

			if ( is_null( $show_achievement ) ) {
				$show_achievement = apply_filters( 'charitable_campaign_show_achievement_status_tag', true );
			}

			if ( is_null( $show_active_tag ) ) {
				$show_active_tag = apply_filters( 'charitable_campaign_show_active_status_tag', false );
			}

			switch ( $key ) {
				case 'ended':
					$tag = __( 'Ended', 'charitable' );
					break;

				case 'successful':
					$tag = $show_achievement ? __( 'Successful', 'charitable' ) : __( 'Ended', 'charitable' );
					break;

				case 'unsucessful':
					$tag = $show_achievement ? __( 'Unsuccessful', 'charitable' ) : __( 'Ended', 'charitable' );
					break;

				case 'ending':
					$tag = __( 'Ending Soon', 'charitable' );
					break;

				case 'active':
				case 'publish':
					$tag = $show_active_tag ? __( 'Active', 'charitable' ) : '';
					break;

				case 'inactive':
					switch ( $this->post->post_status ) {
						case 'pending':
							$tag = __( 'Pending', 'charitable' );
							break;

						case 'draft':
							$tag = __( 'Draft', 'charitable' );
							break;

						case 'future':
							$tag = __( 'Scheduled', 'charitable' );
							break;

						default:
							$tag = '';
					}
					break;

				default:
					$tag = '';

			}//end switch

			/**
			 * Filter the campaign status tag.
			 *
			 * @since 1.0.0
			 *
			 * @param string              $tag  The tag for the status.
			 * @param string              $key  The key of the current status.
			 * @param Charitable_Campaign $this This campaign object.
			 */
			return apply_filters( 'charitable_campaign_status_tag', $tag, $key, $this );
		}

		/**
		 * Return the permalink for this campaign.
		 *
		 * @since  1.6.0
		 *
		 * @return string|false The permalink URL or false if post does not exist.
		 */
		public function get_permalink() {
			return get_permalink( $this->ID );
		}

		/**
		 * Return the admin edit link for this campaign.
		 *
		 * @since  1.6.0
		 *
		 * @return string
		 */
		public function get_admin_edit_link() {
			$post_type_object = get_post_type_object( Charitable::CAMPAIGN_POST_TYPE );

			if ( $post_type_object->_edit_link ) {
				$link = admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=edit', $this->ID ) );
			} else {
				$link = '';
			}

			return $link;
		}

		/**
		 * Returns the donations made to this campaign.
		 *
		 * @since  1.0.0
		 *
		 * @return WP_Query
		 */
		public function get_donations() {
			$this->donations = get_transient( self::get_donations_cache_key( $this->ID ) );

			if ( false === $this->donations ) {

				$this->donations = charitable_get_table( 'campaign_donations' )->get_donations_on_campaign( $this->ID );

				set_transient( self::get_donations_cache_key( $this->ID ), $this->donations, 0 );
			}

			return $this->donations;
		}

		/**
		 * Return the current amount of donations.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean $sanitize Whether to sanitize the amount. False by default.
		 * @return string String if $sanitize is false. If $sanitize is true, return a float or WP_Error if the amount is not a string.
		 */
		public function get_donated_amount( $sanitize = false ) {
			$this->donated_amount = get_transient( self::get_donation_amount_cache_key( $this->ID ) );

			if ( false === $this->donated_amount ) {
				$this->donated_amount = charitable_get_table( 'campaign_donations' )->get_campaign_donated_amount( $this->ID, false, false );

				set_transient( self::get_donation_amount_cache_key( $this->ID ), $this->donated_amount, 0 );
			}

			$amount = $this->donated_amount;

			/* If returning a string, return it in the site number format. */
			if ( ! $sanitize ) {
				$amount = Charitable_Currency::get_instance()->sanitize_database_amount( $amount );
			}

			/**
			 * Filter the campaign donated amount.
			 *
			 * @since 1.2.0
			 *
			 * @param string|float|WP_Error $amount   The donated amount.
			 * @param Charitable_Campaign   $this     This campaign object.
			 * @param boolean               $sanitize Whether the amount should be sanitized.
			 */
			return apply_filters( 'charitable_campaign_donated_amount', $amount, $this, $sanitize );
		}

		/**
		 * Return the currency formatted donation amount.
		 *
		 * @since  1.6.0
		 *
		 * @return string
		 */
		public function get_donated_amount_formatted() {
			return charitable_format_money( $this->get_donated_amount() );
		}

		/**
		 * Return a string describing the campaign's donation summary.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_donation_summary() {
			$currency_helper = charitable_get_currency_helper();
			$amount          = $this->get_donated_amount();
			$goal            = $this->get_meta( '_campaign_goal' );

			if ( $goal ) {
				$ret = sprintf(
					// translators: %1$s is the amount donated, %2$s is the goal amount.
					_x( '%1$s donated of %2$s goal', 'amount donated of goal', 'charitable' ),
					'<span class="amount">' . $currency_helper->get_monetary_amount( $amount ) . '</span>',
					'<span class="goal-amount">' . $currency_helper->get_monetary_amount( $goal ) . '</span>'
				);
			} else {
				$ret = sprintf(
					// translators: %s is the amount donated.
					_x( '%s donated', 'amount donated', 'charitable' ),
					'<span class="amount">' . $currency_helper->get_monetary_amount( $amount ) . '</span>'
				);
			}

			/**
			 * Filter the returned string if you want to display the donation summary in a different way.
			 *
			 * @since 1.0.0
			 * @since 1.5.0 Added the $amount argument.
			 * @since 1.5.0 Added the $goal argument.
			 *
			 * @param string              $ret    The summary.
			 * @param Charitable_Campaign $this   This campaign object.
			 * @param float               $amount The amount donated.
			 * @param int                 $goal   The campaign goal.
			 */
			return apply_filters( 'charitable_donation_summary', $ret, $this, $amount, $goal );
		}

		/**
		 * Return a string especially for the campaign builder describing the campaign's donation summary.
		 *
		 * @since  1.8.0
		 *
		 * @return string
		 */
		public function get_builder_donation_summary() {
			$currency_helper = charitable_get_currency_helper();
			$amount          = $this->get_donated_amount();
			$goal            = $this->get_meta( '_campaign_goal' );

			$ret = sprintf(
				// translators: %s is the amount donated.
				_x( '%s donated', 'amount donated', 'charitable' ),
				'<span class="amount">' . $currency_helper->get_monetary_amount( $amount ) . '</span>'
			);

			/**
			 * Filter the returned string if you want to display the donation summary in a different way.
			 *
			 * @since 1.8.0
			 *
			 * @param string              $ret    The summary.
			 * @param Charitable_Campaign $this   This campaign object.
			 * @param float               $amount The amount donated.
			 * @param int                 $goal   The campaign goal.
			 */
			return apply_filters( 'charitable_builder_donation_summary', $ret, $this, $amount, $goal );
		}

		/**
		 * Return the percentage donated. Use this if you want a formatted string.
		 *
		 * @since  1.0.0
		 *
		 * @return string|false String if campaign has a goal. False if no goal is set.
		 */
		public function get_percent_donated() {
			$percent = $this->get_percent_donated_raw();

			if ( false === $percent ) {
				return $percent;
			}

			$percent = number_format( $percent, 2 );

			return apply_filters( 'charitable_percent_donated', $percent . '%', $percent, $this );
		}

		/**
		 * Returns the percentage donated as a number.
		 *
		 * @since   1.0.0
		 * @version 1.8.7.1
		 *
		 * @return int
		 */
		public function get_percent_donated_raw() {
			if ( ! $this->has_goal() ) {
				return false;
			}

			$donated = (float) $this->get_donated_amount( true );
			$goal    = (float) $this->get_goal();
			if ( $goal <= 0 ) {
				return 0;
			}
			return ( $donated / $goal ) * 100;
		}

		/**
		 * Return the number of people who have donated to the campaign.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_donor_count() {
			/**
			 * Set the number of donors who have donated to this campaign.
			 *
			 * @since 1.4.7
			 *
			 * @param int                 $count Number of donors.
			 * @param Charitable_Campaign $this  This campaign object.
			 */
			return apply_filters(
				'charitable_campaign_donor_count',
				(int) charitable_get_table( 'campaign_donations' )->count_campaign_donors( $this->ID ),
				$this
			);
		}

		/**
		 * Returns the donation form object.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Donation_Form_Interface
		 */
		public function get_donation_form() {
			if ( ! isset( $this->donation_form ) ) {
				/**
				 * Return the class name of the donation form for this campaign.
				 *
				 * @since 1.0.0
				 *
				 * @param string              $class The form class.
				 * @param Charitable_Campaign $this  This campaign object.
				 */
				$form_class = apply_filters( 'charitable_donation_form_class', 'Charitable_Donation_Form', $this );

				$this->donation_form = new $form_class( $this );
			}

			return $this->donation_form;
		}

		/**
		 * Returns the amount to be donated to the campaign as it is currently set in the session.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_donation_amount_in_session() {
			$donation = charitable_get_session()->get_donation_by_campaign( $this->ID );
			$amount   = is_array( $donation ) ? $donation['amount'] : 0;

			/**
			 * The donation amount for this campaign stored in the session.
			 *
			 * @since 1.0.0
			 *
			 * @param float|int           $amount The amount.
			 * @param Charitable_Campaign $this   This campaign object.
			 */
			return apply_filters( 'charitable_session_donation_amount', $amount, $this );
		}

		/**
		 * Returns the donation period for the amount in session.
		 *
		 * @since  1.6.25
		 *
		 * @return false|string
		 */
		public function get_donation_period_in_session() {
			$donation = charitable_get_session()->get_donation_by_campaign( $this->ID );

			if ( ! $donation ) {
				return false;
			}

			if ( ! is_array( $donation ) || ! array_key_exists( 'donation_period', $donation ) ) {
				return 'once';
			}

			return $donation['donation_period'];
		}

		/**
		 * Return the initial/default donation period when
		 *
		 * @since  1.6.32
		 *
		 * @return string
		 */
		public function get_initial_donation_period() {
			$period = $this->get_donation_period_in_session();

			if ( false !== $period ) {
				return $period;
			}

			if ( ! class_exists( 'Charitable_Recurring' ) ) {
				return 'once';
			}

			switch ( $this->get( 'recurring_donation_mode' ) ) {
				case 'advanced':
					$period = 'one-time' === $this->get( 'recurring_default_tab' ) ? 'once' : 'recurring';
					break;

				default:
					$period = 'once';
			}

			return $period;
		}

		/**
		 * Get the default donation amount for this campaign.
		 *
		 * @since  1.6.32
		 *
		 * @return int
		 */
		public function get_default_donation_amount() {
			/**
			 * Filter the default donation amount.
			 *
			 * Note, the donation amount should be expressed in a format where
			 * a period (.) is used as the decimal separator. i.e. 2.50 is
			 * two dollars and fifty cents.
			 *
			 * @since 1.5.6
			 *
			 * @param float|int           $amount   The amount to be filtered. $0 by default.
			 * @param Charitable_Campaign $campaign The instance of `Charitable_Campaign`.
			 */
			return apply_filters( 'charitable_default_donation_amount', 0, $this );
		}

		/**
		 * Renders the donate button template.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function donate_button_template() {
			if ( ! $this->can_receive_donations() ) {
				return;
			}

			$display_option = charitable_get_option( 'donation_form_display', 'separate_page' );

			switch ( $display_option ) {
				case 'separate_page':
					$template_name = 'campaign/donate-button.php';
					break;

				case 'same_page':
					$template_name = 'campaign/donate-link.php';
					break;

				case 'modal':
					$template_name = 'campaign/donate-modal.php';
					break;

				default:
					$template_name = apply_filters( 'charitable_donate_button_template', 'campaign/donate-button.php', $this );
			}

			charitable_template( $template_name, array( 'campaign' => $this ) );
		}

		/**
		 * Renders the donate button template.
		 *
		 * @since  1.2.3
		 *
		 * @return void
		 */
		public function donate_button_loop_template() {
			if ( ! $this->can_receive_donations() ) {
				return;
			}

			$display_option = charitable_get_option( 'donation_form_display', 'separate_page' );

			switch ( $display_option ) {
				case 'modal':
					$template_name = 'campaign-loop/donate-modal.php';
					break;

				default:
					$template_name = apply_filters( 'charitable_donate_button_loop_template', 'campaign-loop/donate-link.php', $this );
			}

			charitable_template( $template_name, array( 'campaign' => $this ) );
		}

		/**
		 * Returns the campaign creator.
		 *
		 * By default, this just returns the user from the post_author field, but
		 * it can be overridden by plugins.
		 *
		 * @since  1.0.0
		 *
		 * @return int $user_id
		 */
		public function get_campaign_creator() {
			return apply_filters( 'charitable_campaign_creator', $this->post->post_author, $this );
		}

		/**
		 * Return the campaign creator's name.
		 *
		 * @since  1.6.0
		 *
		 * @return string
		 */
		public function get_campaign_creator_name() {
			return get_the_author_meta( 'display_name', $this->get_campaign_creator() );
		}

		/**
		 * Return the campaign creator's email address.
		 *
		 * @since  1.6.0
		 *
		 * @return string
		 */
		public function get_campaign_creator_email() {
			return get_the_author_meta( 'user_email', $this->get_campaign_creator() );
		}

		/**
		 * Sanitize the campaign goal.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $value Current value of goal.
		 * @return string|int
		 */
		public static function sanitize_campaign_goal( $value ) {
			if ( empty( $value ) || ! $value ) {
				return 0;
			}

			return charitable_get_currency_helper()->sanitize_monetary_amount( $value );
		}

		/**
		 * Sanitize the campaign minimum amount.
		 *
		 * @since  1.7.0.3
		 *
		 * @param  string $value Current value of min amount.
		 * @return string|int
		 */
		public static function sanitize_min_donation_amount( $value ) {
			if ( empty( $value ) || ! $value ) {
				return 0;
			}

			return charitable_get_currency_helper()->sanitize_monetary_amount( $value );
		}

		/**
		 * Sanitize the campaign end date.
		 *
		 * We use WP_Locale to parse the month that the user has set.
		 *
		 * @global  WP_Locale $wp_locale
		 *
		 * @since  1.0.0
		 *
		 * @param  string $value     Current end date value.
		 * @param  array  $submitted The submitted data.
		 * @return string|int
		 */
		public static function sanitize_campaign_end_date( $value, $submitted = array() ) {
			$end_time = array_key_exists( '_campaign_end_time', $submitted ) ? $submitted['_campaign_end_time'] : '23:59:59';

			if ( charitable()->registry()->get( 'i18n' )->decline_months() ) {
				$end_date = $value . ' ' . $end_time;
			} else {
				$end_date = charitable_sanitize_date( $value, 'Y-m-d ' . $end_time );
			}

			if ( ! $end_date ) {
				$end_date = 0;
			}

			return $end_date;
		}

		/**
		 * Sanitize the campaign suggested donations.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $value Current suggested donations value.
		 * @return array
		 */
		public static function sanitize_campaign_suggested_donations_default( $value ) {

			return $value; // this is likely an array.
		}

		/**
		 * Sanitize the campaign suggested donations.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $value Current suggested donations value.
		 * @param  bool  $submitted Whether the value was submitted.
		 * @param  int   $campaign_id The campaign ID.
		 * @return array
		 */
		public static function sanitize_campaign_suggested_donations( $value, $submitted = false, $campaign_id = false ) { // phpcs:ignore
			if ( ! is_array( $value ) ) {
				return array();
			}

			$value = array_filter( $value, array( 'Charitable_Campaign', 'filter_suggested_donation' ) );

			if ( empty( $value ) ) {
				return $value;
			}

			foreach ( $value as $key => $suggestion ) {
				$value[ $key ]['amount'] = charitable_sanitize_amount( (string) $suggestion['amount'] );

				/**
				 * Sanitize the description field.
				 *
				 * @since 1.6.51
				 *
				 * @param boolean $sanitize Whether to sanitize the suggested amount description.
				 *                          Returning false will allow HTML to be used, though it will
				 *                          still be filtered using wp_kses_post.
				 */
				if ( apply_filters( 'charitable_sanitize_suggested_amount_description', true ) ) {
					$value[ $key ]['description'] = sanitize_text_field( $suggestion['description'] );
				} else {
					$value[ $key ]['description'] = wp_kses_post( $suggestion['description'] );
				}
			}

			return $value;
		}

		/**
		 * Filter out any suggested donations that do not have an amount set.
		 *
		 * @since  1.0.0
		 *
		 * @param  array|string $donation Suggested donation or an array of suggested donations.
		 * @return boolean
		 */
		public static function filter_suggested_donation( $donation ) {
			if ( is_array( $donation ) ) {
				return isset( $donation['amount'] ) && ! empty( $donation['amount'] );
			}

			return ! empty( $donation['amount'] );
		}

		/**
		 * Sanitize any checkbox value.
		 *
		 * @since  1.0.0
		 *
		 * @param  mixed $value Current checkbox value.
		 * @return boolean
		 */
		public static function sanitize_checkbox( $value ) {
			return intval( true == $value || 'on' === $value );
		}

		/**
		 * Sanitize the campaign description.
		 *
		 * @since   1.0.0
		 * @updated 1.7.0.4
		 *
		 * @param  string $value Current description value.
		 * @return string
		 */
		public static function sanitize_campaign_description( $value ) {

			/**
			 * Allowed HTML in description.
			 */
			$wp_kses_allowed_html = array(
				'a'      => array(
					'href'   => array(),
					'target' => array(),
					'class'  => array(),
					'title'  => array(),
				),
				'br'     => array(),
				'img'    => array(
					'src'   => array(),
					'class' => array(),
					'alt'   => array(),
				),
				'h1'     => array(
					'class' => array(),
				),
				'h2'     => array(
					'class' => array(),
				),
				'h3'     => array(
					'class' => array(),
				),
				'h4'     => array(
					'class' => array(),
				),
				'h5'     => array(
					'class' => array(),
				),
				'h6'     => array(
					'class' => array(),
				),
				'div'    => array(
					'class' => array(),
				),
				'p'      => array(
					'style' => array(),
					'class' => array(),
				),
				'ul'     => array(
					'class' => array(),
				),
				'li'     => array(
					'id'    => array(),
					'class' => array(),
				),
				'em'     => array(),
				'span'   => array(
					'class' => array(),
				),
				'strong' => array(),
			);

			if ( apply_filters( 'charitable_sanitize_campaign_description', false ) ) {
				return sanitize_text_field( $value );
			} else {
				return wp_kses( apply_filters( 'charitable_campaign_description', $value ), apply_filters( 'charitable_sanitize_campaign_description_allowed_html', $wp_kses_allowed_html ) );
			}
		}

		/**
		 * Sanitize the campaign donation button text..
		 *
		 * @since   1.7.0.9
		 *
		 * @param  string $value Current text value.
		 * @return string
		 */
		public static function sanitize_campaign_donate_button_text( $value = false ) {

			return wp_strip_all_tags( $value );
		}

		/**
		 * Sanitize the value provided for custom donations.
		 *
		 * @since   1.3.6
		 * @version 1.8.0
		 *
		 * @param  mixed $value     Current custom donations value.
		 * @param  array $submitted All posted values.
		 * @return boolean
		 */
		public static function sanitize_custom_donations( $value, $submitted ) {
			$checked = self::sanitize_checkbox( $value );

			if ( $checked ) {
				return $checked;
			}

			/**
			 * Filter whether a campaign can be saved with custom donations disabled
			 * and no suggested donations.
			 *
			 * @since 1.6.14
			 *
			 * @param boolean $permitted Whether a campaign is permitted to be saved without
			 *                           suggested donations or custom donations.
			 * @param array   $submitted The submitted values.
			 */
			if ( apply_filters( 'charitable_campaign_permitted_without_custom_or_suggested', false, $submitted ) ) {
				return $checked;
			}

			// see if literally "0" was passed.
			if ( is_string( $value ) && '0' === $value ) {
				return 0;
			}

			/* If suggested donations are not set, custom donations needs to be enabled. */
			if ( ! isset( $submitted['_campaign_suggested_donations'] ) ) {
				return 1;
			}

			$suggested_donations = self::sanitize_campaign_suggested_donations( $submitted['_campaign_suggested_donations'] );

			if ( empty( $suggested_donations ) ) {
				return 1;
			}

			return $checked;
		}

		/**
		 * Flush donations cache.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $campaign_id The campaign ID.
		 * @return void
		 */
		public static function flush_donations_cache( $campaign_id ) {
			delete_transient( self::get_donations_cache_key( $campaign_id ) );
			delete_transient( self::get_donation_amount_cache_key( $campaign_id ) );

			do_action( 'charitable_flush_campaign_cache', $campaign_id );
		}

		/**
		 * Returns the key used for caching all donations made to this campaign.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $campaign_id The ID of the campaign.
		 * @return string
		 */
		private static function get_donations_cache_key( $campaign_id ) {
			return 'charitable_campaign_' . $campaign_id . '_donations';
		}

		/**
		 * Returns the key used for caching the donation amount for this campaign.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $campaign_id The ID of the campaign.
		 * @return string
		 */
		private static function get_donation_amount_cache_key( $campaign_id ) {
			return 'charitable_campaign_' . $campaign_id . '_donation_amount';
		}

		/**
		 * Deprecated method used to sanitize meta.
		 *
		 * @deprecated 1.7.0
		 *
		 * @since  1.4.12 Deprecated.
		 *
		 * @param  mixed  $value     Value of meta field.
		 * @param  string $key       Key of meta field.
		 * @param  array  $submitted Posted values.
		 */
		public static function sanitize_meta( $value, $key, $submitted ) {
			charitable_get_deprecated()->deprecated_function( __METHOD__, '1.4.2' );
			return apply_filters( 'charitable_sanitize_campaign_meta' . $key, $value, $submitted );
		}
	}

endif;
