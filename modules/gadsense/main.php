<?php
if (!defined('WPINC')) {
	die;
}

define('GADSENSE_BASE_PATH', plugin_dir_path(__FILE__));
define('GADSENSE_BASE_URL', plugins_url(basename(ADVADS_BASE_PATH) . '/modules/' . basename(GADSENSE_BASE_PATH) . '/'));
define('GADSENSE_OPT_NAME', 'gadsense_options');

if ('' == session_id()) {
	session_start();
}

function gadsense_date_time($time) {
    return date_i18n( get_option( 'date_format' ), $time) . __(' at ', ADVADS_SLUG) . date_i18n( get_option( 'time_format' ), $time);
}

require_once(GADSENSE_BASE_PATH . 'includes/class-gadsense-data.php');
require_once(GADSENSE_BASE_PATH . 'includes/class-ad-type-adsense.php');

Gadsense_Data::get_instance();

if (!defined('DOING_AJAX') || !DOING_AJAX) {
	if (is_admin()) {
		require_once(GADSENSE_BASE_PATH . '/admin/class-gadsense-admin.php');
	}
}
