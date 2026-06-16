<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Feeds\Twitter;

/**
 * Class TwitterCard
 *
 */

if (!defined('ABSPATH')) {
    die('-1');
}

class TwitterCard
{
    private $twitter_card_data;
    private $url;
    private $open_graph_meta;

    /**
     * TwitterCard constructor.
     *
     * @param $url string  search twitter card data from the URL
     *
     */
    public function __construct($url)
    {
        $this->url               = $url;
        $this->twitter_card_data = array();
        $this->open_graph_meta   = array();
    }

    /**
     * the url associated with this twitter card
     *
     * @return string  url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * connects with an external url and saves relevant meta data
     *
     */
    public function setExternalTwitterCardMetaFromUrl()
    {
        $url    = $this->url;
        $values = array();

        if (is_callable('curl_init')) {
            $meta = $this->get_meta_tags_curl($url);
        } else {
            $meta = @get_meta_tags($url);
        }

        if (!empty($meta)) {
            $values['twitter:card']        = isset($meta['twitter:card']) ? sanitize_text_field($meta['twitter:card']) : '';
            $values['twitter:site']        = isset($meta['twitter:site']) ? sanitize_text_field($meta['twitter:site']) : '';
            $values['twitter:site:id']     = isset($meta['twitter:site:id']) ? sanitize_text_field($meta['twitter:site:id']) : '';
            $values['twitter:creator']     = isset($meta['twitter:creator']) ? sanitize_text_field($meta['twitter:creator']) : '';
            $values['twitter:creator:id']  = isset($meta['twitter:creator:id']) ? sanitize_text_field($meta['twitter:creator:id']) : '';
            $values['twitter:title']       = isset($meta['twitter:title']) ? $this->encodeHelper($meta['twitter:title']) : '';
            $values['twitter:description'] = isset($meta['twitter:description']) ? $this->encodeHelper($meta['twitter:description']) : '';
            $values['twitter:image']       = isset($meta['twitter:image']) ? esc_url($meta['twitter:image']) : '';

            if ($values['twitter:image'] === '' && isset($meta['twitter:image:src'])) {
                $values['twitter:image'] = esc_url($meta['twitter:image:src']);
            }

            if ($values['twitter:title'] === '' && isset($meta['og:title'])) {
                $values['twitter:title'] = $this->encodeHelper($meta['og:title']);
            }

            if ($values['twitter:description'] === '' && isset($meta['og:description'])) {
                $values['twitter:description'] = $this->encodeHelper($meta['og:description']);
            }

            if ($values['twitter:image'] === '' && isset($meta['og:image'])) {
                $values['twitter:image'] = $meta['og:image'];
            }

            $values['twitter:image:alt'] = isset($meta['twitter:image:alt']) ? sanitize_text_field($meta['twitter:image:alt']) : '';

            $parsed_main = wp_parse_url($url);
            if ($values['twitter:image'] !== '') {
                if (strpos($values['twitter:image'], 'http') === false) {
                    $start                   = !empty($parsed_main['scheme']) ? $parsed_main['scheme'] : 'http';
                    $host                    = !empty($parsed_main['host']) ? $parsed_main['host'] : '';
                    $values['twitter:image'] = $start . '://' . trailingslashit($host) . $values['twitter:image'];
                }
            }

            if ($values['twitter:card'] === 'player') {
                $values['twitter:player'] = isset($meta['twitter:player']) ? sanitize_text_field($meta['twitter:player']) : '';
            }

            if ($values['twitter:card'] == '' && $values['twitter:description'] !== '') {
                $values['twitter:card'] = 'summary';
            }
        }

        $this->twitter_card_data = $values;
        if ($this->openGraphDataNeeded()) {
            $this->setExternalOpenGraphMetaFromUrl();
        }
        $this->setTwitterCardData();
    }

    /**
     * return the complete twitter card
     *
     * @return array
     */
    public function getTwitterCardData()
    {
        return $this->twitter_card_data;
    }

    private function get_meta_tags_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // must be false to connect without signed certificate
        //curl_setopt( $ch, CURLOPT_ENCODING, "" );
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        $html = curl_exec($ch);
        if (empty($html)) {
            return;
        }
        curl_close($ch);
        $doc = new \DOMDocument('1.0', 'utf-8');
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $metas              = $doc->getElementsByTagName('meta');
        $twitter_card_names = array(
            'twitter:card',
            'twitter:site',
            'twitter:site:id',
            'twitter:title',
            'twitter:description',
            'twitter:image',
            'twitter:image:src',
            'twitter:image:alt',
            'twitter:card',
            'twitter:player',
            //     'twitter:amplify:teaser_segments_stream',
            'twitter:image:src',
//            'twitter:amplify:vmap',
//            'twitter:amplify:media:wpsr_src',
            'og:title',
            'og:image',
            'og:description'
        );

        $twitter_card_meta = array();
        for ($i = 0; $i < $metas->length; $i++) {
            $meta = $metas->item($i);
            if (in_array($meta->getAttribute('name'), $twitter_card_names, true)) {
                if ($meta->getAttribute('content') !== '') {
                    $twitter_card_meta[$meta->getAttribute('name')] = $meta->getAttribute('content');
                } elseif ($meta->getAttribute('value') !== '') {
                    $twitter_card_meta[$meta->getAttribute('name')] = $meta->getAttribute('content');
                }
            } elseif (in_array($meta->getAttribute('property'), $twitter_card_names, true)) {
                if ($meta->getAttribute('content') !== '') {
                    $twitter_card_meta[$meta->getAttribute('property')] = $meta->getAttribute('content');
                } elseif ($meta->getAttribute('value') !== '') {
                    $twitter_card_meta[$meta->getAttribute('property')] = $meta->getAttribute('content');
                }
            }
        }

        return $twitter_card_meta;
    }


    public function encodeHelper($string)
    {
        return wp_strip_all_tags(str_replace(array(
            'â',
            'â',
            'â',
            '“',
            '”',
            '’',
            '‘',
            'â',
            'Ã¼',
            'â',
            'â',
            'Ã',
            'Ã¤',
            'Ã¶'
        ), array(
            '&#8220;',
            '&#8221;',
            '&#8221;',
            '&#8220;',
            '&#8221;',
            '&#8217;',
            '&#8216;',
            '&#8216;',
            '&#252;',
            '&#8220;',
            '&#8220;',
            '&#223;',
            '&#228;',
            '&#246;'
        ), $string));
    }


    /**
     * checks to see if any critical data for twitter cards is missing after first request
     *
     * @return bool whether or not more data is needed
     */
    public function openGraphDataNeeded()
    {
        if (!empty($this->twitter_card_data['twitter:card'])) {
            if (empty($this->twitter_card_data['twitter:title']) || empty($this->twitter_card_data['twitter:site']) || empty($this->twitter_card_data['twitter:description']) || empty($this->twitter_card_data['twitter:image'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * connect to external website and retrieve other open graph meta info
     *
     */
    public function setExternalOpenGraphMetaFromUrl()
    {
        $url                           = $this->url;
        $values                        = array();
        $graph                         = OpenGraph::fetch($url);
        $values['twitter:title']       = isset($graph->title) ? sanitize_text_field($graph->title) : '';
        $values['twitter:description'] = isset($graph->description) ? sanitize_text_field($graph->description) : '';
        $values['twitter:image']       = isset($graph->image) ? sanitize_text_field($graph->image) : '';
        $this->open_graph_meta         = $values;
    }

    /**
     * set the Twitter Card data
     */
    public function setTwitterCardData()
    {
        $tc_data = array();
        $tc_meta = $this->twitter_card_data;
        $og_meta = $this->open_graph_meta;
        foreach ($tc_meta as $key => $value) {
            $tc_data[$key] = !empty($tc_meta[$key]) ? $tc_meta[$key] : (isset($og_meta[$key]) ? $og_meta[$key] : '');
        }
        // if card is not one of the 4 accepted types but might still work
        if (isset($tc_data['twitter:card'])) {
            if ($tc_data['twitter:card'] !== ''
                && $tc_data['twitter:card'] !== 'summary_large_image'
                && $tc_data['twitter:card'] !== 'summary'
                && $tc_data['twitter:card'] !== 'player') {
                $tc_data['twitter:card'] = 'summary_large_image';
            }
        }
        $this->twitter_card_data = $tc_data;
    }
}