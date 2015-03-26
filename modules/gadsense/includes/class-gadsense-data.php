<?php

if (!defined('WPINC')) {
    die;
}

if (!class_exists('Gadsense_Data')) {

    class Gadsense_Data {

        private static $instance = null;
		
        private $options;

		private $resizing;
		
        private function __construct() {
            $options = get_option(GADSENSE_OPT_NAME, array());
            $update = false;

            // adSense publisher id
            if (!isset($options['adsense_id'])) {
                $options['adsense_id'] = '';
                $update = true;
            }
			// Limit of 3 ads per page
			if (!isset($options['limit_ads_per_page'])) {
				$options['limit_ads_per_page'] = true;
                $update = true;
			}

            if ($update) {
                update_option(GADSENSE_OPT_NAME, $options);
            }
            $this->options = $options;
			
			// Resizing method for responsive ads
			$this->resizing = array(
				'auto' => __('Auto', ADVADS_SLUG),
			);
        }

        /**
         * SETTERS
         */
        public function set_adsense_id($id) {
            $old_id = $this->options['adsense_id'];
            $this->options['adsense_id'] = $id;
            update_option(GADSENSE_OPT_NAME, $this->options);
            do_action('advanced-ads-gadsense-after-id-changed', $id, $old_id);
        }
		
		public function set_limit_per_page($value = true) {
			$this->options['limit_ads_per_page'] = $value;
            update_option(GADSENSE_OPT_NAME, $this->options);
		}

        /**
         * GETTERS
         */
        public function get_adsense_id() {
            return $this->options['adsense_id'];
        }
		
		public function get_limit_per_page() {
			return $this->options['limit_ads_per_page'];
		}
		
		public function get_responsive_sizing() {
			$resizing = $this->resizing;
			$this->resizing = apply_filters('advanced-ads-gadsense-responsive-sizing', $resizing);
			return $this->resizing;
		}

        public static function get_instance() {
            if (null == self::$instance) {
                self::$instance = new self;
            }
            return self::$instance;
        }

    }

}
