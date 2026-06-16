<?php

!defined('WPINC') && die;

define('WPSOCIALREVIEWS_PRO_VERSION', '4.0.0');
define('WPSOCIALREVIEWS_PRO', true);
define('WPSOCIALREVIEWS_PRO_URL', plugin_dir_url(__FILE__));
define('WPSOCIALREVIEWS_PRO_DIR', plugin_dir_path(__FILE__));

spl_autoload_register(function ($class){
    $match = 'WPSocialReviewsPro';
    if ( ! preg_match("/\b{$match}\b/", $class)) {
        return;
    }

    $path = plugin_dir_path(__FILE__);

    $file = str_replace(
        ['WPSocialReviewsPro', '\\', '/App/'],
        ['', DIRECTORY_SEPARATOR, 'app/'],
        $class
    );

    $filePath = (trailingslashit($path) . trim($file, '/') . '.php');

    if (file_exists($filePath)) {
        require $filePath;
    }
});

class WPSocialReviewsProDependency
{
    public function init()
    {
        if( !defined('WPSOCIALREVIEWS_VERSION') ){
            $this->injectDependency();
        }

        if( defined('WPSOCIALREVIEWS_VERSION') && version_compare(WPSOCIALREVIEWS_VERSION, '3.10.1', '<=') ){
            add_action('admin_notices', function () {
                $class = 'notice notice-error';
                $message = 'You are using old version of WP Social Ninja. Please update to latest from your plugins list.';
                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
            });

            add_filter('wpsocialreviews/render_twitter_template_header', function(){
                return '';
            });
        }
    }

    /**
     * Notify the user about the WP Social Ninja dependency and instructs to install it.
     */
    protected function injectDependency()
    {
        add_action('admin_notices', function () {
            $pluginInfo = $this->getBasePluginInstallationDetails();

            $class = 'notice notice-error';

            $install_url_text = __('Click Here to Install the Plugin', 'wp-social-ninja-pro');

            if ($pluginInfo->action == 'activate') {
                $install_url_text = __('Click Here to Activate the Plugin', 'wp-social-ninja-pro');
            }

            $message = 'WP Social Ninja PRO Requires WP Social Ninja Base Plugin, <b><a href="' . $pluginInfo->url
                . '">' . $install_url_text . '</a></b>';

            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
        });
    }

    /**
     * Get the WP Social Ninja plugin installation information e.g. the URL to install.
     *
     * @return \stdClass $activation
     */
    protected function getBasePluginInstallationDetails()
    {
        $activation = (object)[
            'action' => 'install',
            'url'    => ''
        ];

        $allPlugins = get_plugins();

        $plugin_path = 'wp-social-reviews/wp-social-reviews.php';

        if (isset($allPlugins[$plugin_path])) {
            $url = wp_nonce_url(
                self_admin_url('plugins.php?action=activate&plugin=' . $plugin_path . ''),
                'activate-plugin_' . $plugin_path . ''
            );

            $activation->action = 'activate';
        } else {
            $api = (object)[
                'slug' => 'wp-social-reviews'
            ];

            $url = wp_nonce_url(
                self_admin_url('update.php?action=install-plugin&plugin=' . $api->slug),
                'install-plugin_' . $api->slug
            );
        }
        $activation->url = $url;

        return $activation;
    }
}

add_action('init', function ($app) {
    (new WPSocialReviewsProDependency())->init();
});

