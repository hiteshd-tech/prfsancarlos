<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\Framework\Support\Arr;
$createdAt = Arr::get($feed, 'created_at', '');
?>
<span class="wpsr-tiktok-feed-time">
    <?php
    $created_at = $createdAt;
    // translators: %s is the time difference (e.g., "2 hours", "3 days")
    echo sprintf(__('%s ago', 'wp-social-ninja-pro'), human_time_diff($created_at));
    ?>
</span>