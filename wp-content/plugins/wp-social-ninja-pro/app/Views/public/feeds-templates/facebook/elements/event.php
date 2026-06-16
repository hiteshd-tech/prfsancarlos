<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\Helper as GlobalHelper;

$feed_type = Arr::get($template_meta, 'source_settings.feed_type');
$display_mode = Arr::get($template_meta, 'post_settings.display_mode');
$permalink_url = $display_mode !== 'none' && $feed_type === 'event_feed' ? "https://www.facebook.com/events/".esc_attr(Arr::get($feed, 'id')) : Arr::get($feed, 'permalink_url');

$attrs = [
	'class'  => 'class="wpsr-feed-link"',
	'target' => $display_mode !== 'none' ? 'target="_blank"' : '',
	'rel'    => 'rel="nofollow"',
	'href'   =>  $display_mode !== 'none' ? 'href="'.esc_url($permalink_url).'"' : '',
];

$dateFormat = 'M j, Y \a\t g:i A';
$eventStartTime = Arr::get($feed, 'start_time');
$eventEndTime = Arr::get($feed, 'end_time');
$start_time = $eventStartTime ? get_date_from_gmt($eventStartTime, $dateFormat) : '';
$end_time = $eventEndTime ? get_date_from_gmt($eventEndTime, $dateFormat) : '';

?>

<div class="wpsr-fb-event_feed-wrapper">
	<?php if(Arr::get($template_meta, 'post_settings.display_event_photo') === 'true') { ?>
        <div class="wpsr-fb-feed-image">
            <?php do_action('wpsocialreviews/facebook_feed_photo_feed_image', $feed, $template_meta, $attrs, $image_settings); ?>
        </div>
    <?php } ?>
    <a href="https://www.facebook.com/events/<?php echo esc_attr(Arr::get($feed, 'id')); ?>" class="wpsr-feed-link" target="_blank" rel="nofollow">
    <div class="wpsr-fb-events-feed-info" >
        <?php if(Arr::get($template_meta, 'post_settings.display_date') === 'true') { ?>
            <p class="wpsr-fb-feed-time" >
              <?php
              echo esc_html($start_time);
              if($end_time) { ?>
                <span> -
                    <?php echo esc_html($end_time); ?>
                </span>
              <?php } ?>
            </p>
        <?php } ?>

        <?php if(Arr::get($feed, 'name') && Arr::get($template_meta, 'post_settings.display_event_name') === 'true') { ?>
            <div class="wpsr-fb-feed-event-name" >
                <h4>
                    <?php echo esc_html(Arr::get($feed, 'name')); ?>
                </h4>
            </div>
        <?php } ?>

        <?php if(! Arr::get($feed, 'is_online') && Arr::get($template_meta, 'post_settings.display_event_location') === 'true' ) { ?>
        <span class="wpsr-fb-feed-event-place" >
                <?php echo esc_html(Arr::get($feed, 'place.name')); ?>
                <?php echo esc_html(Arr::get($feed, 'place.location.country')); ?>
            </span>
        <?php } ?>

        <?php if(Arr::get($feed, 'is_online')) { ?>
            <span class="wpsr-fb-feed-event-place" >
                <?php echo Arr::get($translations, 'online_event') ?: __( 'Online Event', 'wp-social-ninja-pro' ); ?>
            </span>
        <?php } ?>

        <?php if(Arr::get($template_meta, 'post_settings.display_event_interest') === 'true') { ?>
          <span class="wpsr-fb-feed-event-count" >
              <?php
              $interested_text = Arr::get($translations, 'interested') ?: __( 'interested', 'wp-social-ninja-pro' );
              echo GlobalHelper::shortNumberFormat(Arr::get($feed, 'interested_count')) .' '.$interested_text
              ?> .
              <?php
                  echo GlobalHelper::shortNumberFormat(Arr::get($feed, 'attending_count'));
                  $going_text = Arr::get($translations, 'going') ?: __( 'going', 'wp-social-ninja-pro' );
                  $went_text = Arr::get($translations, 'went') ?: __( 'went', 'wp-social-ninja-pro' );
                  echo Arr::get($feed, 'end_time') ? ' '.$going_text : ' '.$went_text;
              ?>
          </span>
        <?php } ?>
    </div>
      </a>
  </div>

