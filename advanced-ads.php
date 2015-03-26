<?php
/**
 * Advanced Ads.
 *
 * @package   Advanced_Ads_Admin
 * @author    Thomas Maier <thomas.maier@webgilde.com>
 * @license   GPL-2.0+
 * @link      http://webgilde.com
 * @copyright 2013-2014 Thomas Maier, webgilde GmbH
 *
 * @wordpress-plugin
 * Plugin Name:       Advanced Ads
 * Plugin URI:        http://wpadvancedads.com
 * Description:       Manage and optimize your ads in WordPress
 * Version:           1.4.1
 * Author:            Thomas Maier
 * Author URI:        http://webgilde.com
 * Text Domain:       advanced-ads
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// only load if not already existing (maybe included from another plugin)
if( defined('ADVADS_BASE_PATH') ) {
    return ;
}

// load basic path to the plugin
define('ADVADS_BASE_PATH', plugin_dir_path(__FILE__));
define('ADVADS_BASE_URL', plugin_dir_url(__FILE__));
define('ADVADS_BASE_DIR', dirname( plugin_basename( __FILE__ ) )); // directory of the plugin without any paths
// general and global slug, e.g. to store options in WP, textdomain
define('ADVADS_SLUG', 'advanced-ads');

/*----------------------------------------------------------------------------*
 * Autoloading Objects
 *----------------------------------------------------------------------------*/
if (!class_exists('Advanced_Ads', true)) {
    require_once( plugin_dir_path( __FILE__ ) . 'includes/autoloader.php' );
    require_once( plugin_dir_path( __FILE__ ) . 'public/class-advanced-ads.php' );
    spl_autoload_register(array('Advads_Autoloader', 'load'));
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */
register_activation_hook( __FILE__, array( 'Advanced_Ads', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Advanced_Ads', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Advanced_Ads', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if( defined('DOING_AJAX') ) {
      new Advads_Ad_Ajax_Callbacks;
}
// load ad conditions array
require_once( plugin_dir_path( __FILE__ ) . 'includes/array_ad_conditions.php' );

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-advanced-ads-admin.php' );
	add_action( 'plugins_loaded', array( 'Advanced_Ads_Admin', 'get_instance' ) );
}

// load public functions
require_once( plugin_dir_path( __FILE__ ) . 'public/functions.php' );

// load widget
require_once( plugin_dir_path( __FILE__ ) . 'classes/widget.php' );
function advads_widget_init() {
    register_widget('Advads_Widget');
}

add_action('widgets_init', 'advads_widget_init');

// Load advads extensions
require_once(ADVADS_BASE_PATH . 'modules/gadsense/main.php');

// load modules, if they exist
if(file_exists(ADVADS_BASE_PATH . 'modules/pro/advads_pro.php')){
    require_once(ADVADS_BASE_PATH . 'modules/pro/advads_pro.php');
}
