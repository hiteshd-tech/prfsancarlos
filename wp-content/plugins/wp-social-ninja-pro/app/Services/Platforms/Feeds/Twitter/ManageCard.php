<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Feeds\Twitter;

/**
 * Class TwitterCard
 *
 */

if (!defined('ABSPATH')) {
    die('-1');
}

class ManageCard
{

    public function maybeStoredCardData($key)
    {
        $existing_twitter_card_data = get_option('wpsr_twitter_cards_data', array());
        if (isset($existing_twitter_card_data[$key])) {
            return $existing_twitter_card_data[$key];
        }

        return false;
    }

    public function getKeyForCard($id_or_url)
    {
        $key = $id_or_url;
        if (strpos($id_or_url, '//') !== false) {
            $key = preg_replace('~[^a-zA-Z0-9]+~', '', $id_or_url);
        }

        return $key;
    }

    public function storeCard($key, $data)
    {
        $existing_twitter_card_data = get_option('wpsr_twitter_cards_data', array());
        if (is_array($existing_twitter_card_data) && count($existing_twitter_card_data) > 100 - 1) {
            array_pop($existing_twitter_card_data);
        }
        $existing_twitter_card_data[$key] = $data;
        update_option('wpsr_twitter_cards_data', $existing_twitter_card_data, false);
    }

    public function processUrl($card_items, $max = 5)
    {
        $newCards = 0;
        $card     = array();
        foreach ($card_items as $item) {
            if (is_array($item)) {
                $id  = $item['id'];
                $url = $item['url'];
            } else {
                $id  = $this->getKeyForCard($item);
                $url = $item;
            }
            $maybe_stored_card = $this->maybeStoredCardData($id);
            if ($maybe_stored_card !== false) {
                $card[] = array(
                    'id'           => $id,
                    'url'          => $url,
                    'twitter_card' => $maybe_stored_card,
                    'is_new'       => false
                );
            }
            if (!$maybe_stored_card && $newCards < $max) {
                $newCards++;
                $twitter_card = new TwitterCard($url);
                $twitter_card->setExternalTwitterCardMetaFromUrl();
                $newCardData = $twitter_card->getTwitterCardData();
                $this->storeCard($id, $newCardData);
                $card[] = array(
                    'id'           => $id,
                    'url'          => $url,
                    'twitter_card' => $newCardData,
                    'is_new'       => true
                );
            }
        }

        return $card;
    }
}

