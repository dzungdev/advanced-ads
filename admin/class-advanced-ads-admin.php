<?php

/**
 * Advanced Ads.
 *
 * @package   Advanced_Ads_Admin
 * @author    Thomas Maier <thomas.maier@webgilde.com>
 * @license   GPL-2.0+
 * @link      http://webgilde.com
 * @copyright 2013 Thomas Maier, webgilde GmbH
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * *
 * @package Advanced_Ads_Admin
 * @author  Thomas Maier <thomas.maier@webgilde.com>
 */
class Advanced_Ads_Admin {

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     * @var      object
     */
    protected static $instance = null;

    /**
     * Slug of the settings page
     *
     * @since    1.0.0
     * @var      string
     */
    public $plugin_screen_hook_suffix = null;

    /**
     * Slug of the ad group page
     *
     * @since    1.0.0
     * @var      string
     */
    protected $ad_group_hook_suffix = null;

    /**
     * general plugin slug
     *
     * @since   1.0.0
     * @var     string
     */
    protected $plugin_slug = '';

    /**
     * post type slug
     *
     * @since   1.0.0
     * @var     string
     */
    protected $post_type = '';

    /**
     * Initialize the plugin by loading admin scripts & styles and adding a
     * settings page and menu.
     *
     * @since     1.0.0
     */
    private function __construct() {

        /*
         * Call $plugin_slug from public plugin class.
         *
         */
        $plugin = Advanced_Ads::get_instance();
        $this->plugin_slug = $plugin->get_plugin_slug();
        $this->post_type = constant("Advanced_Ads::POST_TYPE_SLUG");

        // Load admin style sheet and JavaScript.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add menu items
        add_action('admin_menu', array($this, 'add_ad_group_menu'));
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

        add_action('admin_init', array($this, 'add_meta_boxes'));

        // save ads post type
        add_action('save_post', array($this, 'save_ad'));

        // settings handling
        add_action('admin_init', array($this, 'settings_init'));

        // Add an action link pointing to the options page.
        $plugin_basename = plugin_basename(plugin_dir_path('__DIR__') . $this->plugin_slug . '.php');
        add_filter('plugin_action_links_' . $plugin_basename, array($this, 'add_action_links'));
    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Register and enqueue admin-specific style sheet.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_styles() {

        global $post;
        if (!isset($this->plugin_screen_hook_suffix) && Advanced_Ads::POST_TYPE_SLUG != $post->type) {
            return;
        }

        wp_enqueue_style($this->plugin_slug . '-admin-styles', plugins_url('assets/css/admin.css', __FILE__), array(), Advanced_Ads::VERSION);
    }

    /**
     * Register and enqueue admin-specific JavaScript.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_scripts() {

        global $post;
        if (!isset($this->plugin_screen_hook_suffix) && Advanced_Ads::POST_TYPE_SLUG != $post->type) {
            return;
        }

        wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('assets/js/admin.js', __FILE__), array('jquery'), Advanced_Ads::VERSION);

        // just register this script for later inclusion on ad group list page
        wp_register_script('inline-edit-group-ads', plugins_url('assets/js/inline-edit-group-ads.js', __FILE__), array('jquery'), Advanced_Ads::VERSION);

    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {

        // add settings page
        $this->plugin_screen_hook_suffix = add_submenu_page(
                'edit.php?post_type=' . Advanced_Ads::POST_TYPE_SLUG, __('Advanced Ads Settings', $this->plugin_slug), __('Settings', $this->plugin_slug), 'manage_options', $this->plugin_slug . '-settings', array($this, 'display_plugin_settings_page')
        );
        add_submenu_page(
                null, __('Advanced Ads Debugging', $this->plugin_slug), __('Debug', $this->plugin_slug), 'manage_options', $this->plugin_slug . '-debug', array($this, 'display_plugin_debug_page')
        );
    }

    /**
     * Render the settings page
     *
     * @since    1.0.0
     */
    public function display_plugin_settings_page() {
        include_once( 'views/settings.php' );
    }

    /**
     * Render the debug page
     *
     * @since    1.0.1
     */
    public function display_plugin_debug_page() {
        // load array with ads by condition
        $plugin = Advanced_Ads::get_instance();
        $ads_by_conditions = $plugin->get_ads_by_conditions_array();

        include_once( 'views/debug.php' );
    }

    /**
     * Register ad group taxonomy page
     *
     * @since    1.0.0
     */
    public function add_ad_group_menu() {

        $this->ad_group_hook_suffix = add_submenu_page(
                'edit.php?post_type=' . Advanced_Ads::POST_TYPE_SLUG, __('Ad Groups', $this->plugin_slug), __('Ad Groups', $this->plugin_slug), 'manage_options', $this->plugin_slug . '-groups', array($this, 'ad_group_admin_page')
        );
    }

    /**
     * Render the ad group page
     *
     * @since    1.0.0
     */
    public function ad_group_admin_page() {

        $taxonomy = Advanced_Ads::AD_GROUP_TAXONOMY;
        $post_type = Advanced_Ads::POST_TYPE_SLUG;
        $tax = get_taxonomy($taxonomy);

        $action = $this->current_action();

        // handle new and updated groups
        if ($action == 'editedgroup') {
            $group_id = (int) $_POST['group_id'];
            check_admin_referer('update-group_' . $group_id);

            if (!current_user_can($tax->cap->edit_terms))
                wp_die(__('Cheatin&#8217; uh?'));

            // handle new groups
            if ($group_id == 0) {
                $ret = wp_insert_term($_POST['name'], $taxonomy, $_POST);
                if ($ret && !is_wp_error($ret))
                    $forced_message = 1;
                else
                    $forced_message = 4;
                // handle group updates
            } else {
                $tag = get_term($group_id, $taxonomy);
                if (!$tag)
                    wp_die(__('You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?'));

                $ret = wp_update_term($group_id, $taxonomy, $_POST);
                if ($ret && !is_wp_error($ret))
                    $forced_message = 3;
                else
                    $forced_message = 5;
            }
        // deleting items
        } elseif($action == 'delete'){
            $group_id = (int) $_REQUEST['group_id'];
            check_admin_referer('delete-tag_' . $group_id);

            if (!current_user_can($tax->cap->delete_terms))
                wp_die(__('Cheatin&#8217; uh?'));

            wp_delete_term($group_id, $taxonomy);

            $forced_message = 2;
        }

        // handly views
        switch ($action) {
            case 'edit' :
                $title = $tax->labels->edit_item;
                if (isset($_REQUEST['group_id'])) {
                    $group_id = absint($_REQUEST['group_id']);
                    $tag = get_term($group_id, $taxonomy, OBJECT, 'edit');
                } else {
                    $group_id = 0;
                    $tag = false;
                }

                require_once( 'views/ad-group-edit.php' );
                break;

            default :
                $title = $tax->labels->name;
                // load needed classes
                include_once( 'includes/class-list-table.php' );
                include_once( 'includes/class-ad-groups-list-table.php' );
                // load template
                include_once( 'views/ad-group.php' );
        }
    }

    /**
     * returns a link to the ad group list page
     *
     * @since 1.0.0
     * @param arr $args additional arguments, e.g. action or group_id
     * @return string admin url
     */
    static function group_page_url($args = array()) {
        $plugin = Advanced_Ads::get_instance();

        $defaultargs = array(
            'post_type' => constant("Advanced_Ads::POST_TYPE_SLUG"),
            'page' => 'advanced-ads-groups',
        );
        $args = $args + $defaultargs;

        return add_query_arg($args, admin_url('edit.php'));
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */
    public function add_action_links($links) {

        return array_merge(
                array(
            'settings' => '<a href="' . admin_url('edit.php?post_type=advanced_ads&page=advanced-ads-settings') . '">' . __('Settings', $this->plugin_slug) . '</a>'
                ), $links
        );
    }

    /**
     * Add meta boxes
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        global $_wp_post_type_features;

        add_meta_box(
                'ad-main-box', __('Ad Main', $this->plugin_slug), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
        );
        add_meta_box(
                'ad-parameters-box', __('Fine tune your ad', $this->plugin_slug), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
        );
        add_meta_box(
                'ad-display-box', __('Where to display your ads', $this->plugin_slug), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
        );
    }

    /**
     * load templates for all meta boxes
     *
     * @since 1.0.0
     * @param obj $post
     * @param array $box
     * @todo move ad initialization to main function and just global it
     */
    public function markup_meta_boxes($post, $box) {
        $ad = new Advads_Ad($post->ID);

        switch ($box['id']) {
            case 'ad-main-box':
                $view = 'ad-main-metabox.php';
                break;
            case 'ad-parameters-box':
                $view = 'ad-parameters-metabox.php';
                break;
            case 'ad-display-box':
                $view = 'ad-display-metabox.php';
                break;
        }

        if (empty($view))
            return;
        $view = plugin_dir_path(__FILE__) . 'views/' . $view;
        if (is_file($view)) {
            require_once( $view );
        }
    }

    /**
     * prepare the ad post type to be saved
     *
     * @since 1.0.0
     * @param int $post_id id of the post
     * @todo handling this more dynamic based on ad type
     */
    public function save_ad($post_id) {

        // only use for ads, no other post type
        if (!isset($_POST['post_type']) || $this->post_type != $_POST['post_type'] || !isset($_POST['advanced_ad']['type'])) {
            return;
        }

        // get ad object
        $ad = new Advads_Ad($post_id);
        if (!$ad instanceof Advads_Ad)
            return;

        $ad->type = $_POST['advanced_ad']['type'];
        if(!empty($_POST['advanced_ad']['content']))
            $ad->content = $_POST['advanced_ad']['content'];
        else $ad->content = '';
        $ad->conditions = $_POST['advanced_ad']['conditions'];

        $ad->save();
    }

    /**
     * get action from the params
     *
     * @since 1.0.0
     */
    public function current_action() {
        if (isset($_REQUEST['action']) && -1 != $_REQUEST['action'])
            return $_REQUEST['action'];

        return false;
    }

    /**
     * initialize settings
     *
     * @since 1.0.1
     */
    public function settings_init(){

        // no additional settings registered yet, but some addons might need this

        // register settings
 	register_setting($this->plugin_screen_hook_suffix, 'advancedads');
    }


}