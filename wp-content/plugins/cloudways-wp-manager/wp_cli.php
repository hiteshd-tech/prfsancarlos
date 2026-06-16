<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('CWMWPCli')) :

	class CWMWPCli {
		public $settings;
		public $siteinfo;
		public $bvinfo;
		public $bvapi;

		public function __construct($settings, $bvinfo, $bvsiteinfo, $bvapi) {
			$this->settings = $settings;
			$this->siteinfo = $bvsiteinfo;
			$this->bvinfo = $bvinfo;
			$this->bvapi = $bvapi;
		}

		public function setkey($args, $params) {
			// Support for encoded key
			if (isset($params['key'])) {
				$decoded = base64_decode($params['key']);
				$parts = explode(':', $decoded, 3);
				if (count($parts) === 3) {
					if ($parts[0] !== 'v1') {
						WP_CLI::error('Key version incompatible or invalid key format.');
					}
					if ($parts[1] !== '' && $parts[2] !== '') {
						$pubkey = $parts[1];
						$secret = $parts[2];

						if (strlen($pubkey) < 32 || strlen($secret) < 32) {
							WP_CLI::error('Please enter valid key.');
						}
						CWMAccount::addAccount($this->settings, $pubkey, $secret);
						CWMAccount::updateApiPublicKey($this->settings, $pubkey);
						if (CWMAccount::exists($this->settings, $pubkey)) {
							WP_CLI::success('Key Setup Successfully.');
						} else {
							WP_CLI::error('Key Setup Failed.');
						}
					} else {
						WP_CLI::error('Invalid key format.');
					}
				} else {
					WP_CLI::error('Invalid key format.');
				}
			}
		}
	}
endif;