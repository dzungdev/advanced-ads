<?php

if (!defined('WPINC')) {
    die;
}

if (!class_exists('Gadsense_Admin')) {

    class Gadsense_Admin {

        private $data;
        private $nonce;
        private static $instance = null;

        private function __construct() {
            $this->data = Gadsense_Data::get_instance();
            add_action('advanced-ads-additional-settings-form', array($this, 'settings_form'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
			add_action('admin_print_scripts', array($this, 'print_scripts'));
            add_action('admin_init', array($this, 'init'));
			add_filter('advanced-ads-list-ad-size', array($this, 'ad_details_column'), 10, 2);
        }

		public function ad_details_column($size, $the_ad) {
			if ('adsense' == $the_ad->type) {
				$content = json_decode($the_ad->content);
				if ('responsive' == $content->unitType) $size = __('Responsive', ADVADS_SLUG);
			}
			return $size;
		}

		public function print_scripts() {
            global $pagenow, $post_type;
            if (
                    ('post-new.php' == $pagenow && Advanced_Ads::POST_TYPE_SLUG == $post_type) ||
                    ('post.php' == $pagenow && Advanced_Ads::POST_TYPE_SLUG == $post_type && isset($_GET['action']) && 'edit' == $_GET['action'])
            ) {
				$db = Gadsense_Data::get_instance();
				$pub_id = $db->get_adsense_id();
				?>
				<script type="text/javascript">
					var gadsenseData = {
						pubId : '<?php echo $pub_id; ?>',
						msg : {
							unknownAd : '<?php esc_attr_e("The ad details couldn't be retrieved from the ad code", ADVADS_SLUG); ?>',
							pubIdMismatch : '<?php _e('Warning : The AdSense account from this code does not match the one set with the Advanced Ads Plugin. This ad might cause troubles when used in the front end.', ADVADS_SLUG); ?>',
							missingPubId : '<?php _e('Warning : You have not yet entered an AdSense account ID. The plugin won’t work without that', ADVADS_SLUG); ?>',
						}
					};
				</script>
				<?php
			}
		}

        public function init() {
            if (isset($_POST['gadsense-nonce'])) {
                $_SESSION['gadsense']['post'] = $_POST;
                $this->refresh_page();
            }
            if (isset($_SESSION['gadsense']['post'])) {
                $_POST = $_SESSION['gadsense']['post'];
                unset($_SESSION['gadsense']['post']);
                $this->form_treatment();
            }
            $this->nonce = wp_create_nonce('gadsense_nonce');
        }

        private function refresh_page($go = null) {
            if (!empty($go)) {
                wp_redirect($go);
                die();
            }
            $running = $_SERVER['PHP_SELF'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $running .= '?' . $_SERVER['QUERY_STRING'];
            }
            wp_redirect($running);
            die();
        }

        public function form_treatment() {
            if (1 === wp_verify_nonce($_POST['gadsense-nonce'], 'gadsense_nonce')) {
                switch ($_POST['gadsense-form-name']) {
                    case 'cred-form' :
                        $id = strtolower(trim(wp_unslash($_POST['adsense-id'])));
						$limit = (isset($_POST['limit-per-page']))? true : false;
						$msg = __('Data updated', ADVADS_SLUG);
						$css = 'updated';
                        if (0 === strpos($id, 'pub-')) {
                            $this->data->set_adsense_id($id);
                            $this->data->set_limit_per_page($limit);
                        } else {
							$msg = __('The Publisher ID has an incorrect format. (must start with "pub-")', ADVADS_SLUG);
							$css = 'error';
                        }
						$_SESSION['gadsense']['admin_notice'] = array(
							'msg' => $msg,
							'class' => $css,
						);
                        break;
                    default :
                }
            }
        }

        public function enqueue_scripts() {
            global $gadsense_globals, $pagenow, $post_type;
            $screen = get_current_screen();
            $plugin = Advanced_Ads_Admin::get_instance();
            if (
                    ('post-new.php' == $pagenow && Advanced_Ads::POST_TYPE_SLUG == $post_type) ||
                    ('post.php' == $pagenow && Advanced_Ads::POST_TYPE_SLUG == $post_type && isset($_GET['action']) && 'edit' == $_GET['action'])
            ) {
                $default_script = array(
                    'path' => GADSENSE_BASE_URL . 'admin/assets/js/new-ad.js',
                    'dep' => array('jquery'),
                    'version' => null,
                );

                $scripts = array(
					'gadsense-new-ad' => $default_script,
				);

                // Allow modifications of script files to enqueue
                $scripts = apply_filters('advanced-ads-gadsense-ad-param-script', $scripts);

                foreach ($scripts as $handle => $value) {
                    if (empty($handle)) {
                        continue;
                    }
					if (!empty($handle) && empty($value)) {
						// Allow inclusion of WordPress's built-in script like jQuery
						wp_enqueue_script($handle);
					} else {
						if (!isset($value['version'])) $value['version'] = null;
						wp_enqueue_script($handle, $value['path'], $value['dep'], $value['version']);
					}
                }

                $styles = array();

                // Allow modifications of default style files to enqueue
                $styles = apply_filters('advanced-ads-gadsense-ad-param-style', $styles);

                foreach ($styles as $handle => $value) {
                    if (!isset($value['path']) ||
                            !isset($value['dep']) ||
                            empty($handle)
                    ) {
                        continue;
                    }
                    if (!isset($value['version']))
                        $value['version'] = null;
                    wp_enqueue_style($handle, $value['path'], $value['dep'], $value['version']);
                }
            }
        }

        public static function get_instance() {
            if (null == self::$instance) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        public function settings_form() {
            require_once(GADSENSE_BASE_PATH . 'admin/views/admin-page.php');
        }

    }

    Gadsense_Admin::get_instance();
}
