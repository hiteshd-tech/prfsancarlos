<?php

namespace WPSocialReviewsPro\App\Hooks\Handlers;


class ChatHandler
{

    public function renderChatCss($settings)
    {
        if ($settings['platform'] === 'messenger' && isset($settings['additional_settings']['chat_bubble_scroll_position'])) {
            ?>
            <style type="text/css">

                <?php if( $settings['additional_settings']['chat_bubble_position'] === 'top-left' || $settings['additional_settings']['chat_bubble_position'] === 'top-right') { ?>
                .wpsr-fm-chat-wrapper.wpsr-multiplatform-chat.wpsr-chat-messenger {
                    top: <?php echo $settings['additional_settings']['chat_bubble_scroll_position'] .'px !important;'; ?>
                }

                .wpsr-fm-chat-wrapper.wpsr-multiplatform-chat.wpsr-chat-messenger .wpsr-fm-chat-box-display {
                    top: <?php echo 100 + $settings['additional_settings']['chat_bubble_scroll_position'] .'px !important;'; ?>
                }

                <?php } ?>

                <?php if( $settings['additional_settings']['chat_bubble_position'] === 'bottom-left' || $settings['additional_settings']['chat_bubble_position'] === 'bottom-right') { ?>
                .wpsr-fm-chat-wrapper.wpsr-chat-messenger {
                    bottom: <?php echo 20 + $settings['additional_settings']['chat_bubble_scroll_position'] .'px;'; ?>

                }

                .wpsr-fm-chat-wrapper.wpsr-chat-messenger .wpsr-fm-chat-box-display {
                    margin-bottom: <?php echo 100 + $settings['additional_settings']['chat_bubble_scroll_position'] .'px;'; ?>
                }

                <?php } ?>
            </style>
        <?php }

    }

    public function updateDisplayUserOnlineStatus($settings)
    {
        $days = array(
            __('Saturday', 'wp-social-ninja-pro'),
            __('Sunday', 'wp-social-ninja-pro'),
            __('Monday', 'wp-social-ninja-pro'),
            __('Tuesday', 'wp-social-ninja-pro'),
            __('Wednesday', 'wp-social-ninja-pro'),
            __('Thursday', 'wp-social-ninja-pro'),
            __('Friday', 'wp-social-ninja-pro')
        );

        //day params
        $dataParams                    = array();
        $dataParams['dayTimeSchedule'] = isset($settings['day_time_schedule']) ? $settings['day_time_schedule'] : 'false';
        $dataParams['dayLists']        = isset($settings['day_list']) ? $settings['day_list'] : $days;

        //time params
        $dataParams['timeSchedule'] = isset($settings['time_schedule']) ? $settings['time_schedule'] : 'false';
        $dataParams['startTime']    = isset($settings['start_chat_time']) ? $settings['start_chat_time'] : $settings['start_time'];
        $dataParams['endTime']      = isset($settings['end_chat_time']) ? $settings['end_chat_time'] : $settings['end_time'];
        $dataParams['serverTimezone'] = wp_timezone_string();

        return $dataParams;
    }
}