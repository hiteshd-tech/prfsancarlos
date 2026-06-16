<?php
if (! defined('ABSPATH')) exit;

if (!empty($header) && is_array($header)) {
    if (isset($template_meta['header_settings']) && $template_meta['header_settings']['show_header'] === 'false') {
        return;
    }
    ?>
    <div class="wpsr-twitter-feed-header">
        <?php
        /**
         * tweeter_profile_banner hook.
         *
         * @hooked wpsr_render_tweeter_profile_banner_html 10
         * */
        do_action('wpsocialreviews/tweeter_profile_banner', $header, $template_meta);
        ?>
        <div class="wpsr-twitter-user-info-wrapper">
            <div class="wpsr-twitter-user-info-head">
                <?php
                /**
                 * tweeter_user_profile_picture hook.
                 *
                 * @hooked wpsr_render_tweeter_user_profile_picture_html 10
                 * */
                do_action('wpsocialreviews/tweeter_user_profile_picture', $header, $template_meta);

                /**
                 * tweeter_user_profile_follow_btn hook.
                 *
                 * @hooked wpsr_render_tweeter_user_profile_follow_btn_html 10
                 * */
                if (isset($template_meta['follow_button_settings']) && $template_meta['follow_button_settings']['display_follow_button'] === 'true' && $template_meta['follow_button_settings']['follow_button_position'] !== 'footer') {
                    do_action('wpsocialreviews/tweeter_user_profile_follow_btn', $header,
                        $template_meta['follow_button_settings']);
                }
                ?>
            </div>
            <div class="wpsr-twitter-user-info">
                <div class="wpsr-twitter-user-info-name-wrapper">
                    <?php
                    /**
                     * tweeter_user_profile_info hook.
                     *
                     * @hooked wpsr_render_tweeter_user_profile_info_username_html 10
                     * @hooked wpsr_render_tweeter_user_profile_info_name_html 5
                     * */
                    do_action('wpsocialreviews/tweeter_user_profile_info', $header, $template_meta);
                    ?>
                </div>
                <?php
                /**
                 * tweeter_user_profile_description hook.
                 *
                 * @hooked wpsr_render_tweeter_user_profile_description_html 10
                 * */
                do_action('wpsocialreviews/tweeter_user_profile_description', $header, $template_meta);

                /**
                 * tweeter_user_address hook.
                 *
                 * @hooked wpsr_render_tweeter_tweeter_user_address_html 10
                 * */
                do_action('wpsocialreviews/tweeter_user_address', $header, $template_meta);

                /**
                 * tweeter_user_profile_statistics hook.
                 *
                 * @hooked wpsr_render_tweeter_user_profile_statistics_html 10
                 * */
                do_action('wpsocialreviews/tweeter_user_profile_statistics', $header, $template_meta, $translations);
                ?>
            </div>
        </div>
    </div>
<?php }