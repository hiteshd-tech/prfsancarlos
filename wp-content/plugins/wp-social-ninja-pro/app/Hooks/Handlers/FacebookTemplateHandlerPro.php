<?php

namespace WPSocialReviewsPro\App\Hooks\Handlers;

use WPSocialReviews\App\Services\GlobalSettings;
use WPSocialReviews\App\Hooks\Handlers\ShortcodeHandler;
use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\App\Services\Platforms\Feeds\Facebook\FacebookFeed;
use WPSocialReviews\App\Services\Platforms\Feeds\Facebook\Helper as FacebookHelper;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviewsPro\App\Traits\LoadView;

class FacebookTemplateHandlerPro
{
    use LoadView;

    public $image_settings = [];

    public function __construct()
    {
        $this->getGdprSettings();
    }

    public function getGdprSettings()
    {
        $global_settings = get_option('wpsr_facebook_feed_global_settings');
        $advanceSettings = (new GlobalSettings())->getGlobalSettings('advance_settings');

        $this->image_settings = [
            'optimized_images' => Arr::get($global_settings, 'global_settings.optimized_images', 'false'),
            'has_gdpr' => Arr::get($advanceSettings, 'has_gdpr', "false")
        ];
    }

    public function facebookTimelineFeedApiFields($fields)
    {
        $reactions = ',reactions.type(LIKE).limit(0).summary(total_count).as(like),reactions.type(LOVE).limit(0).summary(total_count).as(love),reactions.type(WOW).limit(0).summary(total_count).as(wow),reactions.type(HAHA).limit(0).summary(total_count).as(haha),reactions.type(SAD).limit(0).summary(total_count).as(sad),reactions.type(ANGRY).limit(0).summary(total_count).as(angry),reactions.type(THANKFUL).limit(0).summary(total_count).as(thankful)';
        return $fields . ',comments.summary(true){id,from{id,name,picture{url},link},message,message_tags,created_time,like_count,comment_count,attachment{media}}'.$reactions;
    }

    public function facebookVideoFeedApiDetails($remoteFetchUrl, $pageId, $totalFeed, $accessToken)
    {
        $fetchUrl = $remoteFetchUrl . $pageId . '/videos?fields=id,created_time,updated_time,description,from{name,id,picture{url},link},source,length,permalink_url,privacy,format{height,width,filter,picture}&limit='.$totalFeed.'&access_token=' . $accessToken;
        return $fetchUrl;
    }

    public function facebookVideoPlaylistFeedApiDetails($remoteFetchUrl, $playListId, $totalFeed, $accessToken)
    {
        $fetchUrl = $remoteFetchUrl . $playListId . '/videos?fields=id,created_time,updated_time,description,from{name,id,picture{url},link},source,length,permalink_url,privacy,format{height,width,filter,picture}&limit='.$totalFeed.'&access_token=' . $accessToken;
        return $fetchUrl;
    }

    public function facebookPhotoFeedApiDetails($remoteFetchUrl, $pageId, $totalFeed, $accessToken)
    {
        $fetchUrl = $remoteFetchUrl . $pageId . '/photos?fields=id,created_time,updated_time,caption,link,images,name,privacy,from{name,id,picture{url},link}&type=uploaded&limit='.$totalFeed.'&access_token=' . $accessToken;
        return $fetchUrl;
    }

    public function facebookEventFeedApiDetails($remoteFetchUrl, $pageId, $totalFeed, $accessToken)
    {
        $fetchUrl = $remoteFetchUrl . $pageId . '/events?fields=id,name,description,created_time,category,type,start_time,end_time,place,cover,attending_count,interested_count,is_online&limit='.$totalFeed.'&access_token=' . $accessToken;
        return $fetchUrl;
    }

    public function facebookAlbumsFeedApiDetails($remoteFetchUrl, $pageId, $totalFeed, $accessToken)
    {
        $fetchUrl = $remoteFetchUrl . $pageId . '/albums?fields=id,created_time,updated_time,cover_photo{source},photos{name,picture,source,link},name,privacy,from{name,id,picture{url},link}&type=uploaded&limit='.$totalFeed.'&access_token=' . $accessToken;
        return $fetchUrl;
    }


    public function facebookSingleAlbumFeedApiDetails($remoteFetchUrl, $albumId, $totalFeed, $accessToken)
    {
        $fetchUrl = $remoteFetchUrl . $albumId . '/photos?fields=id,created_time,updated_time,caption,link,images,name,privacy,from{name,id,picture{url},link}&type=uploaded&limit='.$totalFeed.'&access_token=' . $accessToken;
        return $fetchUrl;
    }

    public function facebookFeedExtendApiEndpoints($dateRangeStart, $dateRangeEnd)
    {
        return '&since=' . $dateRangeStart . '&until=' . $dateRangeEnd;
    }


    public function getFacebookLikesCount($feed)
    {
        $sum = 0;

        if(isset($feed['like']) && isset($feed['love']) && isset($feed['wow']) && isset($feed['haha']) && isset($feed['sad']) && isset($feed['angry'])) {
            $sum += $feed['like']['summary']['total_count'];
            $sum += $feed['love']['summary']['total_count'];
            $sum += $feed['wow']['summary']['total_count'];
            $sum += $feed['haha']['summary']['total_count'];
            $sum += $feed['sad']['summary']['total_count'];
            $sum += $feed['angry']['summary']['total_count'];
        }

        return $sum;
    }

    public function getFacebookCommentsCount($feed)
    {
        return isset($feed['comments']['summary']['total_count']) ? $feed['comments']['summary']['total_count'] : 0;
    }

    public function facebookFeedsByPopularity($feeds, $popularity_type)
    {
        $multiply = ($popularity_type === 'most_popular') ? -1 : 1;
        usort($feeds, function ($m1, $m2) use ($multiply) {
            $sum1 = $this->getFacebookLikesCount($m1) + $this->getFacebookCommentsCount($m1);
            $sum2 = $this->getFacebookLikesCount($m2) + $this->getFacebookCommentsCount($m2);

            if($sum1 == $sum2) {
                return 0;
            }

            if($sum1 < $sum2) {
                return -1 * $multiply;
            }
            return 1 * $multiply;
        });

        return $feeds;
    }

    public function renderFacebookFeedLikeButtonHtml($settings = [], $header = [])
    {
        $display_like_button = Arr::get($settings, 'like_button_settings.display_like_button');
        if($display_like_button === 'true') {
            $like_button_text = Arr::get($settings, 'like_button_settings.like_button_text');
            $page_url = esc_url(Arr::get($header, 'link'));
            ?>
            <div class="wpsr-fb-feed-btn">
                <a href="<?php echo esc_url($page_url); ?>" role="button" target="_blank" rel="nofollow" aria-label="<?php echo esc_attr($like_button_text); ?>">
                            <span class="wpsr-fb-feed-btn-icon">
                              <svg class="wpsr-svg-fb-icon" width="12px" height="12px" viewBox="0 0 12 12" style="enable-background:new 0 0 12 12;">
                                  <path style="fill:#4672D2;" d="M10.4,0H1.6C0.7,0,0,0.7,0,1.6v8.8C0,11.3,0.7,12,1.6,12h4.3l0-4.3H4.8c-0.1,0-0.3-0.1-0.3-0.3l0-1.4
                                      c0-0.1,0.1-0.3,0.3-0.3h1.1V4.5c0-1.5,0.9-2.4,2.3-2.4h1.1c0.1,0,0.3,0.1,0.3,0.3v1.2c0,0.1-0.1,0.3-0.3,0.3l-0.7,0
                                      C8,3.8,7.8,4.1,7.8,4.6v1.2h1.7c0.2,0,0.3,0.1,0.3,0.3L9.6,7.5c0,0.1-0.1,0.2-0.3,0.2H7.8l0,4.3h2.6c0.9,0,1.6-0.7,1.6-1.6V1.6
                                      C12,0.7,11.3,0,10.4,0z"></path>
                              </svg>
                            </span>
                    <span class="wpsr-fb-feed-btn-text"><?php echo esc_html($like_button_text); ?></span>
                </a>
            </div>
        <?php }
    }

    public function renderFacebookFeedShareButtonHtml($settings = [], $header = [])
    {
        $display_share_button = Arr::get($settings, 'share_button_settings.display_share_button');
        if($display_share_button === 'true') {
            $share_button_text = Arr::get($settings, 'share_button_settings.share_button_text');
            $page_url = Arr::get($header, 'link');
            ?>
            <div class="wpsr-fb-feed-btn wpsr-ml-15">
                <a class="wpsr-fb-feed-btn-share" href="<?php echo esc_url($page_url); ?>" target="_blank" rel="nofollow" role="button" aria-label="<?php echo esc_attr($share_button_text); ?>">
                     <span class="wpsr-fb-feed-btn-icon">
                      <svg class="wpsr-svg-share-icon" width="12px" height="12px" viewBox="0 0 24 24">
                        <path id="XMLID_31_" d="M12.7,15.3c-4.5,0.1-8.4,2.5-10.7,6c-0.2,0.3-0.6,0.5-0.9,0.5c-0.1,0-0.2,0-0.3,0c-0.5-0.1-0.8-0.6-0.8-1c0,0,0-0.1,0-0.1c0-7.1,5.7-12.9,12.7-13V5.5c0-0.5,0.3-0.9,0.7-1.1c0.2-0.1,0.4-0.1,0.6-0.1c0.3,0,0.5,0.1,0.7,0.2l8.8,6c0.3,0.2,0.5,0.6,0.5,0.9c0,0.4-0.2,0.7-0.5,1l-8.8,6.1c-0.2,0.2-0.5,0.2-0.7,0.2c-0.2,0-0.4,0-0.6-0.1c-0.4-0.2-0.7-0.7-0.7-1.1V15.3z"></path>
                      </svg>
                    </span>
                    <span class="wpsr-fb-feed-btn-text"><?php echo esc_html($share_button_text); ?></span>
                </a>
            </div>
        <?php }
    }

    public function renderFacebookFeedStatistics($feed = [], $template_meta = [], $translations = [])
    {
        if($template_meta['post_settings']['display_likes_count'] === 'true' || $template_meta['post_settings']['display_comments_count'] === 'true'){
            $totalReactions = FacebookHelper::getTotalFeedReactions($feed);
            ?>
            <div class="wpsr-fb-feed-statistics" aria-label="<?php echo esc_attr__('Post statistics', 'wp-social-ninja-pro'); ?>">
                <?php if($totalReactions && $template_meta['post_settings']['display_likes_count'] === 'true') { ?>
                    <div class="wpsr-fb-feed-reactions" role="group" aria-label="<?php echo esc_attr__('Reactions', 'wp-social-ninja-pro'); ?>">

                        <?php if(Arr::get($feed, 'like.summary.total_count')){ ?>
                            <div class="wpsr-fb-feed-reactions-icon-like wpsr-fb-feed-reactions-icon"></div>
                        <?php } ?>

                        <?php if(Arr::get($feed, 'love.summary.total_count')){ ?>
                            <div class="wpsr-fb-feed-reactions-icon-love wpsr-fb-feed-reactions-icon"></div>
                        <?php } ?>

                        <?php if(Arr::get($feed, 'wow.summary.total_count')){ ?>
                            <div class="wpsr-fb-feed-reactions-icon-wow wpsr-fb-feed-reactions-icon"></div>
                        <?php } ?>

                        <?php if(Arr::get($feed, 'sad.summary.total_count')){ ?>
                            <div class="wpsr-fb-feed-reactions-icon-sad wpsr-fb-feed-reactions-icon"></div>
                        <?php } ?>

                        <?php if(Arr::get($feed, 'angry.summary.total_count')){ ?>
                            <div class="wpsr-fb-feed-reactions-icon-angry wpsr-fb-feed-reactions-icon"></div>
                        <?php } ?>

                        <div class="wpsr-fb-feed-reaction-count">
                            <?php echo GlobalHelper::shortNumberFormat($totalReactions); ?>
                        </div>
                    </div>
                <?php } ?>

                <?php if( Arr::get($feed, 'comments') && Arr::get($feed, 'comments.summary.total_count') && $template_meta['post_settings']['display_comments_count'] === 'true'){ ?>
                    <div class="wpsr-fb-feed-comments-count" aria-live="polite">
                        <?php
                            $comments_text = Arr::get($translations, 'comments') ?: __('Comments', 'wp-social-ninja-pro');
                            echo GlobalHelper::shortNumberFormat($feed['comments']['summary']['total_count']) .' '. $comments_text;
                        ?>
                    </div>
                <?php } ?>
            </div>
            <?php
        }
    }

    public function renderFacebookFeedVideos($feed = [], $template_meta = [])
    {
        $feed_type = Arr::get($template_meta, 'source_settings.feed_type');
        ?>
        <div>
            <?php if(($feed_type === 'video_feed' || $feed_type === 'video_playlist_feed') && Arr::get($feed, 'format')){
                $media_url = Arr::get($feed, 'media_url');
                $large_media_url = $media_url ? $media_url : Arr::get($feed, 'format.1.picture');
                $medium_media_url = $media_url ? $media_url : Arr::get($feed, 'format.0.picture');
                $imgClass = '';
                if ($media_url) {
                    $imgClass = str_contains($media_url ?? '', 'placeholder') ? 'wpsr-fb-post-img wpsr-hide' : 'wpsr-fb-post-img wpsr-show';
                }
                $display_mode = Arr::get($template_meta, 'post_settings.display_mode');
                $permalink_url = $display_mode !== 'none' ? esc_url('https://www.facebook.com'.Arr::get($feed, 'permalink_url')) : '';
                $description  = Arr::get($feed, 'description');
                $animatedClass =  ($this->image_settings['optimized_images'] && $media_url && str_contains($media_url ?? '', 'placeholder')) ? 'wpsr-animated-background' : '';
                $attrs = [
                    'class'  => 'class="wpsr-fb-feed-video-preview wpsr-fb-feed-video-playmode wpsr-feed-link ' . esc_attr($animatedClass) . '"',
                    'target' => $display_mode !== 'none' ? 'target="_blank"' : '',
                    'rel'    => 'rel="nofollow"',
                    'href'   =>  $display_mode !== 'none' ? 'href="'.$permalink_url.'"' : '',
                    'alt'    => 'alt="'.esc_attr($description).'"'
                ];
                ?>
                <a <?php echo implode(' ', $attrs); ?>>
                    <img class="<?php echo esc_attr($imgClass) ?>" src="<?php echo esc_url($large_media_url ? $large_media_url : $medium_media_url); ?>" alt="<?php esc_attr(Arr::get($feed, 'description')); ?>"/>

                    <?php if(Arr::get($feed, 'length') && $template_meta['post_settings']['display_duration'] === 'true') { ?>
                        <span class="wpsr-fb-feed-video-duration">
                            <?php echo FacebookHelper::secondsToMinutes(Arr::get($feed, 'length')); ?>
                        </span>
                    <?php } ?>

                    <?php if($template_meta['post_settings']['display_play_icon'] === 'true') { ?>
                        <div class="wpsr-fb-feed-video-play">
                            <div class="wpsr-fb-feed-video-play-icon"></div>
                        </div>
                    <?php } ?>
                </a>
            <?php } ?>

            <div class="wpsr-fb-feed-video-info">
                <?php if(Arr::get($feed, 'description') && $template_meta['post_settings']['display_description'] === 'true'){ ?>
                    <h3>
                        <a href="<?php echo esc_url('https://www.facebook.com'.Arr::get($feed, 'permalink_url')); ?>" class="wpsr-fb-feed-video-playmode wpsr-feed-link" target="_blank" rel="nofollow">
                            <?php echo wp_kses_post(wp_trim_words($feed['description'], 10)); ?>
                        </a>
                    </h3>
                <?php } ?>
                <?php if($template_meta['post_settings']['display_date'] === 'true'){ ?>
                    <div class="wpsr-fb-feed-video-statistics">
                        <div class="wpsr-fb-feed-video-statistic-item">
                            <?php
                            /**
                             * facebook_feed_date hook.
                             *
                             * @hooked FacebookFeedTemplateHandler::renderFeedDate 10
                             * */
                            do_action('wpsocialreviews/facebook_feed_date', $feed);
                            ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php
    }

    public function renderFacebookFeedEvents($feed = [], $template_meta = [], $translations=[])
    {
        if (Arr::get($feed, 'status_type') === 'shared_story') {
            return;
        }

        $html = $this->loadView('feeds-templates/facebook/elements/event', array(
            'feed'          => $feed,
            'template_meta' => $template_meta,
            'translations'  => $translations,
            'image_settings' => $this->image_settings
        ));

        echo $html;
    }

    public function renderFacebookFeedSummaryCardImage($feed = [], $attachment = [], $template_meta = [])
    {
        if(Arr::get($template_meta, 'post_settings.display_media') === 'false'){
            return;
        }
        $media_url = $this->image_settings['optimized_images'] == 'true' ? Arr::get($feed, 'media_url') : '';
        $full_picture = Arr::get($feed, 'full_picture');
        $media_src = Arr::get($attachment, 'media.image.src');
        $picture = $media_url ? $media_url : ( $full_picture ? $full_picture : $media_src) ;
        ?>
        
        <img class="wpsr-fb-feed-url-summary-card-img wpsr-fb-post-img" src="<?php echo esc_url($picture); ?>" alt="<?php echo esc_attr(Arr::get($feed, 'from.name')); ?>">
        <?php
    }

    public function renderFacebookFeedImage($feed = [], $template_meta = [])
    {
        if(Arr::get($template_meta, 'post_settings.display_media') === 'false'){
            return;
        }
        $message = Arr::get($feed, 'message');
        $media_url = Arr::get($feed, 'media_url');
        $image = $media_url ? $media_url : (Arr::get($feed, 'attachments.data.0.media.image.src') ? Arr::get($feed, 'attachments.data.0.media.image.src') : Arr::get($feed, 'full_picture'));
        
        $type = Arr::get($feed, 'attachments.data.0.type');

        $status_type = Arr::get($feed, 'status_type');
        $optimized_images =  $this->image_settings['optimized_images'];
        $display_mode = Arr::get($template_meta, 'post_settings.display_mode');

        if($optimized_images != 'false') {
            $subAttachments = null;
        } else {
            $subAttachments = Arr::get($feed, 'attachments.data.0.subattachments.data');
        }

        $countSubAttachmentsDisplay = null;
        if(!empty($subAttachments) && is_array($subAttachments)){
            $countSubAttachments = count($subAttachments);
            if($countSubAttachments > 1){
                unset($subAttachments[0]);
            }


            if($countSubAttachments > 3){
                $countSubAttachmentsDisplay = ($countSubAttachments - 1) - 3;
                $subAttachments = array_splice($subAttachments, 0, 3);
            }
        }

        $imgClass = (!str_contains($image ?? '', 'placeholder') ? 'wpsr-fb-feed-image-render wpsr-fb-post-img wpsr-show' : 'wpsr-fb-feed-image-render wpsr-fb-post-img wpsr-hide');
        
        ?>
        
        <div class="<?php echo ($type === 'album' && $optimized_images == 'false' && !empty($subAttachments)) ? 'wpsr-fb-feed-image-album-layout-'.esc_attr($countSubAttachments) : ''; ?>">
            <?php if($type !== 'native_templates' && !empty($image)){ ?>
                <?php if($type === 'video' && $display_mode === 'popup'){ ?>
                    <a class="wpsr-fb-feed-video-thumbnail wpsr-fb-feed-video-playmode" data-video-url="<?php echo esc_attr($this->getVideoUrl($feed)); ?>" data-display-mode="<?php echo esc_attr($display_mode); ?>" onclick="wpsrHandleVideoClick(this, event)">
                        <img class="<?php echo esc_attr($imgClass) ?>" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($message); ?>"/>
                        <div class="wpsr-fb-feed-video-play">
                            <div class="wpsr-fb-feed-video-play-icon"></div>
                        </div>
                    </a>
                <?php } else { ?>
                    <img class="fb-post-media-primary <?php echo esc_attr($imgClass) ?>" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($message); ?>"/>
                <?php } ?>
            <?php } ?>
            <?php if($optimized_images == 'false' && $type === 'album' && !empty($subAttachments)) { ?>
                <div class="wpsr-fb-feed-image-album">
                    <?php foreach ($subAttachments as $index => $attachment): 
                        $attachment_type = Arr::get($attachment, 'type');
                        $is_video = $attachment_type === 'video';
                        $thumbnail_url = Arr::get($attachment, 'media.image.src');
                        $video_url = $is_video ? $this->getVideoUrlFromAttachment($attachment) : '';
                        $image_classes = 'wpsr-feed-image-render wpsr-fb-post-img';
                        $image_classes .= $is_video ? ' wpsr-fb-feed-video-thumbnail' : '';
                    ?>
                        <span class="wpsr-fb-feed-image-album-item" 
                              data-attachment-type="<?php echo esc_attr($attachment_type); ?>"
                              data-video-url="<?php echo esc_attr($video_url); ?>"
                              data-thumbnail-url="<?php echo esc_attr($thumbnail_url); ?>">
                            
                            <?php if ($index === 2 && $countSubAttachmentsDisplay): ?>
                                <span class="wpsr-fb-feed-image-more">
                                    <span>+<?php echo esc_html($countSubAttachmentsDisplay); ?></span>
                                </span>
                            <?php endif; ?>
                            
                            <img class="<?php echo esc_attr($image_classes); ?>" 
                                 src="<?php echo esc_url($thumbnail_url); ?>" 
                                 alt="<?php echo esc_attr($message); ?>"/>
                            
                            <?php if ($is_video): ?>    
                                <div class="wpsr-fb-feed-video-play wpsr-fb-feed-video-play-album">
                                    <div class="wpsr-fb-feed-video-play-icon"></div>
                                </div>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php } ?>
        </div>
        

        <?php if($template_meta['post_settings']['display_play_icon'] === 'true' && ($status_type === 'added_video' || Arr::get($feed, 'attachments.data.0.type') === 'video_inline')) { ?>
            <div class="wpsr-fb-feed-video-play">
                <div class="wpsr-fb-feed-video-play-icon"></div>
            </div>
        <?php }
    }

    /**
     * Get video URL from main feed attachment
     */
    private function getVideoUrl($feed)
    {
        $videoPaths = [
            'attachments.data.0.media.source',
            'attachments.data.0.target.url',
            'attachments.data.0.url',
            'attachments.data.0.media.video.source',
            'attachments.data.0.media.video.hd_quality',
            'attachments.data.0.media.video.sd_quality'
        ];
        
        foreach ($videoPaths as $path) {
            $videoUrl = Arr::get($feed, $path);
            if ($videoUrl) {
                return $videoUrl;
            }
        }
        
        return '';
    }

    /**
     * Get video URL from sub-attachment
     */
    private function getVideoUrlFromAttachment($attachment)
    {
        $videoPaths = [
            'media.source',
            'target.url',
            'url',
            'media.video.source',
            'media.video.hd_quality',
            'media.video.sd_quality'
        ];
        
        foreach ($videoPaths as $path) {
            $videoUrl = Arr::get($attachment, $path);
            if ($videoUrl) {
                return $videoUrl;
            }
        }
        
        return '';
    }

    public function renderFacebookFeedAlbum($feed = [], $template_meta = [] , $templateId = null , $pagination_settings = null)
    {
        if (Arr::get($feed, 'status_type') === 'shared_story') {
            return;
        }

        $sinceId = Arr::get($pagination_settings, 'sinceId');
        $maxId = Arr::get($pagination_settings, 'maxId');
        $photos = Arr::get($feed, 'photos.data') ? Arr::get($feed, 'photos.data') : [];
        $paginate = Arr::get($pagination_settings, 'paginate');
        $pagination_type = Arr::get($pagination_settings, 'pagination_type');
        $photo = Arr::get($feed, 'media_url', '');
        $page_id = Arr::get($feed, 'page_id');

        $imgClass = str_contains($photo ?? '', 'placeholder') ? 'wpsr-fb-feed-image wpsr-album-cover-photo wpsr-animated-background' : 'wpsr-fb-feed-image wpsr-album-cover-photo';

        $html = $this->loadView('feeds-templates/facebook/elements/album', array(
            'feed'          => $feed,
            'template_meta' => $template_meta,
            'templateId'    => $templateId,
            'sinceId'       => $sinceId,
            'maxId'         => $maxId,
            'paginate'      => $paginate,
            'total'         => count($photos),
            'photos'        => $photos,
            'pagination_type' => $pagination_type,
            'image_settings' => $this->image_settings,
            'img_class'     => $imgClass
        ));

        echo $html;
    }

    public function renderFacebookFeedSingleAlbum($feed = [], $template_meta = [] , $templateId = null , $pagination_settings = null, $index = null){
        if (Arr::get($feed, 'status_type') === 'shared_story') {
            return;
        }


        $sinceId = Arr::get($pagination_settings, 'sinceId');
        $maxId = Arr::get($pagination_settings, 'maxId');
        $photos = Arr::get($feed, 'photos.data') ? Arr::get($feed, 'photos.data') : [];
        $paginate = Arr::get($pagination_settings, 'paginate');
        $pagination_type = Arr::get($pagination_settings, 'pagination_type');
        $photo = Arr::get($feed, 'media_url', '');
        $page_id = Arr::get($feed, 'page_id');

        $imgClass = str_contains($photo ?? '', 'placeholder') ? 'wpsr-fb-feed-image wpsr-album-cover-photo wpsr-animated-background' : 'wpsr-fb-feed-image wpsr-album-cover-photo';

        $html = $this->loadView('feeds-templates/facebook/elements/album_single_photo', array(
            'feed'          => $feed,
            'template_meta' => $template_meta,
            'templateId'    => $templateId,
            'sinceId'       => $sinceId,
            'maxId'         => $maxId,
            'paginate'      => $paginate,
            'total'         => count($photos),
            'photos'        => $photos,
            'pagination_type' => $pagination_type,
            'image_settings' => $this->image_settings,
            'img_class'     => $imgClass,
            'index'         => $index
        ));

        echo $html;
    }


    public function renderFacebookFeedPhotoFeedImage($feed = [], $template_meta = [], $attrs = [], $image_settings = [])
    {
        $feed_type = Arr::get($template_meta, 'source_settings.feed_type');
        $large_media_url = '';
        $medium_media_url = '';
        if($feed_type === 'photo_feed' && Arr::get($feed, 'images')) {
            $media_url = Arr::get($feed, 'media_url');
            $large_media_url = $media_url ? $media_url : Arr::get($feed, 'images.2.source');
            $medium_media_url = $media_url ? $media_url : Arr::get($feed, 'images.0.source');
        }
        if($feed_type === 'event_feed') {
            $media_url = Arr::get($feed, 'cover.source');
            $imageUrl =  $media_url ? $media_url : Arr::get($feed, 'source');
            $large_media_url = $imageUrl;
        }
        if($feed_type === 'album_feed') {
            $media_url = Arr::get($feed, 'media_url');
            if($image_settings['optimized_images'] == 'true') {
                $medium_media_url = $media_url;
            } else {
                $imageUrl = $media_url ? $media_url : (Arr::get($feed, 'cover_photo.source') ? Arr::get($feed, 'cover_photo.source') : Arr::get($feed, 'source'));
                $large_media_url = $imageUrl;
            }
        }

        if($feed_type == 'single_album_feed') {
            $media_url = Arr::get($feed, 'media_url');
            if($image_settings['optimized_images'] == 'true') {
                $medium_media_url = $media_url;
            } else {
                $imageUrl = $media_url ? $media_url : Arr::get($feed, 'images.0.source');
                $large_media_url = $imageUrl;
            }
        }

        if (!empty($large_media_url) || !empty($medium_media_url)) {
            $image = $large_media_url ? $large_media_url : $medium_media_url;
            $imgClass = (!str_contains($image ?? '', 'placeholder') ? 'wpsr-feed-link-img wpsr-fb-post-img wpsr-show' : 'wpsr-feed-link-img wpsr-fb-post-img wpsr-hide');
            ?>
            
            <a <?php echo implode(' ', $attrs); ?>>
                <img class="<?php echo esc_attr($imgClass) ?>" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(Arr::get($feed, 'name')); ?>"/>
            </a>
            <?php
        }
    }

    public function renderFacebookFeedInfo($feed = [] , $identifier = null)
    {
        $name = Arr::get($feed, 'name');
        $photos = Arr::get($feed, 'photos.data') ? Arr::get($feed, 'photos.data') : [];
        $photo_count = count($photos);
        if ($identifier === 'info') {
            ?>
            <div class="wpsr-fb-albums-feed-info">
                <?php if(!empty($name)) { ?>
                    <h4 class="wpsr-fb-feed-album-name wpsr-fb-feed-album-name-outer" >
                        <?php echo esc_html($name); ?>
                    </h4>
                <?php } ?>
                <?php if($photo_count > 0) { ?>
                    <span class="wpsr-fb-feed-album-count">
                        <?php echo GlobalHelper::shortNumberFormat(esc_html($photo_count)) . __(' Photos', 'wp-social-ninja-pro'); ?>
                    </span>
                <?php } ?>
            </div>
            <?php
        }
        if ($identifier === 'header') {
            ?>
            <p class="wpsr-fb-feed-album-name" >
                <?php echo esc_html($name); ?>
            </p>
            <div class="wpsr-fb-feed-album-info">
                <p class="wpsr-fb-feed-album-photo-count">
                    <?php echo GlobalHelper::shortNumberFormat(esc_html($photo_count)) . __(' Photos', 'wp-social-ninja-pro'); ?>
                </p>
                <p class="wpsr-fb-feed-photo-album-divider"> - </p>
                <p class="wpsr-fb-feed-album-update-count">
                    <?php
                    $created_at = strtotime($feed['created_time']);
                    // translators: %s is the time difference (e.g., "2 hours", "3 days")
                    echo sprintf(__('%s ago', 'wp-social-ninja-pro'), human_time_diff($created_at));
                    ?>
                </p>
            </div>
            <?php
        }
    }

    public function handleAlbumPhoto()
    {
        $templateId = absint(Arr::get($_REQUEST, 'template_id'));
        $feed_id = sanitize_text_field(Arr::get($_REQUEST, 'feed_id' , null));

        $shortcodeHandler = new ShortcodeHandler();
        $facebookFeed = new FacebookFeed();
        $template_meta = $shortcodeHandler->templateMeta($templateId, 'facebook_feed');
        $feed = $facebookFeed->getTemplateMeta($template_meta, $templateId);
        $settings = $shortcodeHandler->formatFeedSettings($feed);
        $pagination_settings = $shortcodeHandler->formatPaginationSettings($feed);
        $feeds = Arr::get($settings, 'feeds', []);
        $feedInfo = $facebookFeed->getFeedData($feeds, $feed_id);


        if (Arr::get($feed, 'status_type') === 'shared_story') {
            return;
        }

        $sinceId = Arr::get($pagination_settings, 'sinceId');
        $maxId = Arr::get($pagination_settings, 'maxId');
        $paginate = Arr::get($pagination_settings, 'paginate');
        $pagination_type = Arr::get($pagination_settings, 'pagination_type');

        $imageResolution = Arr::get($template_meta, 'feed_settings.post_settings.resolution');
        $display_mode = Arr::get($template_meta, 'feed_settings.post_settings.display_mode');
        $display_optimized_image = Arr::get($this->image_settings, 'optimized_images', 'false');
        $has_gdpr = Arr::get($this->image_settings, 'has_gdpr', 'false');
        $feed_type = Arr::get($template_meta, 'feed_settings.source_settings.feed_type');

        // Get feed and photos data
        $feedData = Arr::get($feedInfo, 'feed');
        $photos = Arr::get($feedInfo, 'feedImages');

        $html = $this->loadView('feeds-templates/facebook/elements/photos', array(
            'feed'          => $feedData,
            'photos'        => $photos,
            'template_meta' => Arr::get($template_meta , 'feed_settings'),
            'templateId'    => $templateId,
            'sinceId'       => $sinceId,
            'maxId'         => $maxId,
            'paginate'      => $paginate,
            'pagination_type' => $pagination_type,
            'image_settings' => $this->image_settings,
            'imageResolution' => $imageResolution,
            'display_mode'    => $display_mode,
            'display_optimized_image' => $display_optimized_image,
            'has_gdpr' => $has_gdpr,
            'feed_type' => $feed_type
        ));

        echo $html;
    }

    public function getAlbumPaginatedFeedHtml($templateId = null, $page = null , $feed_id = null , $feed_type = '')
    {
        $shortcodeHandler = new ShortcodeHandler();
        $facebookFeed = new FacebookFeed();

        $template_meta = $shortcodeHandler->templateMeta($templateId, 'facebook_feed');
        $feed = $facebookFeed->getTemplateMeta($template_meta, $templateId);
        $settings = $shortcodeHandler->formatFeedSettings($feed);
        $pagination_settings = $shortcodeHandler->formatPaginationSettings($feed);
        $sinceId = (($page - 1) * $pagination_settings['paginate']);
        $maxId = ($sinceId + $pagination_settings['paginate']) - 1;

        $pagination_type = Arr::get($pagination_settings, 'pagination_type');
        $feeds = Arr::get($settings, 'feeds', []);
        $feedInfo = $facebookFeed->getFeedData($feeds, $feed_id);

        $imageResolution = Arr::get($template_meta, 'post_settings.resolution');
        $display_mode = Arr::get($template_meta, 'post_settings.display_mode');
        $display_optimized_image = Arr::get($this->image_settings, 'optimized_images', 'false');
        $has_gdpr = Arr::get($this->image_settings, 'has_gdpr', 'false');
        $feed_type = Arr::get($template_meta, 'source_settings.feed_type');

        // Get feed and photos data
        $feedData = Arr::get($feedInfo, 'feed');
        $photos = Arr::get($feedInfo, 'feedImages');

        return $this->loadView('feeds-templates/facebook/elements/photos', array(
            'template_meta'   => Arr::get($template_meta,'feed_settings'),
            'templateId'      => $templateId,
            'photos'          => $photos,
            'sinceId'         => $sinceId,
            'maxId'           => $maxId,
            'pagination_type' => $pagination_type,
            'paginate'        => Arr::get($pagination_settings, 'paginate'),
            'feed'            => $feedData,
            'image_settings'  => $this->image_settings,
            'imageResolution' => $imageResolution,
            'display_mode'    => $display_mode,
            'display_optimized_image' => $display_optimized_image,
            'has_gdpr' => $has_gdpr,
            'feed_type' => $feed_type
        ));
    }

    public function addTemplate($data = [])
    {
        return $this->loadView('feeds-templates/facebook/template2', $data);
    }

    /**
     * Fetch more attachments for each Facebook post if a custom limit is set via the 'wpsr_facebook_per_post_attachment_limit' filter.
     *
     * @param array $feeds The array of Facebook post feeds.
     * @return array Modified feeds with more attachments if needed.
     */
    public function facebookFeedFetchPostMoreAttachments($feeds, $attachment_limit, $access_token) {
        foreach ($feeds as &$feed) {
            if (isset($feed['id'])) {
                $post_id = $feed['id'];
                if ($attachment_limit > 12 && !empty($access_token)) {
                    $url = 'https://graph.facebook.com/' . $post_id . '?fields=attachments{subattachments.limit(' . $attachment_limit . ')}&access_token=' . $access_token;
                
                    $response = wp_remote_get($url);
                    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                        $body = json_decode(wp_remote_retrieve_body($response), true);
                        $subattachments = Arr::get($body, 'attachments.data.0.subattachments.data', []);
                        if (!empty($subattachments)) {
                            $feed['attachments']['data'][0]['subattachments']['data'] = $subattachments;
                        }
                    }
                }
            }
        }
        return $feeds;
    }
}