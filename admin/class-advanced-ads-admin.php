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
 *
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
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

        // on post/ad edit screen
        add_action('edit_form_after_title', array($this, 'edit_form_below_title'));
        add_action('admin_init', array($this, 'add_meta_boxes'));
        add_action('post_submitbox_misc_actions', array($this, 'add_submit_box_meta'));

        // save ads post type
        add_action('save_post', array($this, 'save_ad'));

        // handling (ad) lists
        add_filter('request', array($this, 'ad_list_request')); // order ads by title, not ID
        add_filter('manage_advanced_ads_posts_columns', array($this, 'ad_list_columns_head')); // extra column
        add_filter('manage_advanced_ads_posts_custom_column', array($this, 'ad_list_columns_content'), 10, 2); // extra column

        // settings handling
        add_action('admin_init', array($this, 'settings_init'));

        // admin notices
        add_action('admin_notices', array($this, 'admin_notices'));

        // Add an action link pointing to the options page.
        $plugin_basename = plugin_basename(plugin_dir_path('__DIR__') . $this->plugin_slug . '.php');
        add_filter('plugin_action_links_' . $plugin_basename, array($this, 'add_action_links'));

        // add meta box for post types edit pages
        add_action( 'add_meta_boxes', array( $this, 'add_post_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_post_meta_box' ) );

        // register dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));

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
        if (!isset($this->plugin_screen_hook_suffix) && isset($post) && Advanced_Ads::POST_TYPE_SLUG != $post->type) {
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
        if (!isset($this->plugin_screen_hook_suffix) && isset($post) && Advanced_Ads::POST_TYPE_SLUG != $post->type) {
            return;
        }

        wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('assets/js/admin.js', __FILE__), array('jquery', 'jquery-ui-autocomplete'), Advanced_Ads::VERSION);

        // just register this script for later inclusion on ad group list page
        wp_register_script('inline-edit-group-ads', plugins_url('assets/js/inline-edit-group-ads.js', __FILE__), array('jquery'), Advanced_Ads::VERSION);

    }

    /**
    * display admin notices
     *
     * @since 1.2.1
    */
    public function admin_notices()
    {
        // removed ad injection notice in version 1.3.18
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {

        // add main menu item with overview page
        add_menu_page(
            __('Overview', ADVADS_SLUG), __('Advanced Ads', ADVADS_SLUG), 'manage_options', $this->plugin_slug, array($this, 'display_overview_page'), 'dashicons-chart-line', '58.74'
        );

        add_submenu_page(
            $this->plugin_slug, __('Ads', ADVADS_SLUG), __('Ads', ADVADS_SLUG), 'manage_options', 'edit.php?post_type=' . Advanced_Ads::POST_TYPE_SLUG
        );

        $this->ad_group_hook_suffix = add_submenu_page(
            $this->plugin_slug, __('Ad Groups', ADVADS_SLUG), __('Groups', ADVADS_SLUG), 'manage_options', $this->plugin_slug . '-groups', array($this, 'ad_group_admin_page')
        );

        // add placements page
        add_submenu_page(
            $this->plugin_slug, __('Ad Placements', ADVADS_SLUG), __('Placements', ADVADS_SLUG), 'manage_options', $this->plugin_slug . '-placements', array($this, 'display_placements_page')
        );
        // add settings page
        $this->plugin_screen_hook_suffix = add_submenu_page(
            $this->plugin_slug, __('Advanced Ads Settings', ADVADS_SLUG), __('Settings', ADVADS_SLUG), 'manage_options', $this->plugin_slug . '-settings', array($this, 'display_plugin_settings_page')
        );
        add_submenu_page(
            null, __('Advanced Ads Debugging', ADVADS_SLUG), __('Debug', ADVADS_SLUG), 'manage_options', $this->plugin_slug . '-debug', array($this, 'display_plugin_debug_page')
        );

		// allows extensions to insert sub menu pages
		do_action('advanced-ads-submenu-pages', $this->plugin_slug);
    }

    /**
     * Render the overview page
     *
     * @since    1.2.2
     */
    public function display_overview_page() {
        $recent_ads = Advanced_Ads::get_ads();
        $groups = Advanced_Ads::get_ad_groups();
        $placements = Advanced_Ads::get_ad_placements_array();
        include_once( 'views/overview.php' );
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
     * Render the placements page
     *
     * @since    1.1.0
     */
    public function display_placements_page() {
        // sace new placement
        if(isset($_POST['advads']['placement'])){
            $return = Advads_Ad_Placements::save_new_placement($_POST['advads']['placement']);
        }
        // save placement data
        if(isset($_POST['advads']['placements'])){
            $return = Advads_Ad_Placements::save_placements($_POST['advads']['placements']);
        }
        $error = false;
        $success = false;
        if(isset($return) && $return !== true) {
            $error = $return;
        } elseif(isset($return) && $return === true){
            $success = __('Placements updated', ADVADS_SLUG);
        }
        $placement_types = Advads_Ad_Placements::get_placement_types();
        $placements = Advanced_Ads::get_ad_placements_array();
        // load ads and groups for select field

        // display view
        include_once( 'views/placements.php' );
    }

    /**
     * Render the debug page
     *
     * @since    1.0.1
     */
    public function display_plugin_debug_page() {
        // load array with ads by condition
        $plugin = Advanced_Ads::get_instance();
        $plugin_options = $plugin->options();
        $ads_by_conditions = $plugin->get_ads_by_conditions_array();
        $ad_placements = Advanced_Ads::get_ad_placements_array();

        include_once( 'views/debug.php' );
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
                wp_die(__('Sorry, you are not allowed to access this feature.', ADVADS_SLUG));

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
                    wp_die(__('You attempted to edit an ad group that doesn&#8217;t exist. Perhaps it was deleted?', ADVADS_SLUG));

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
                wp_die(__('Sorry, you are not allowed to access this feature.', ADVADS_SLUG));

            wp_delete_term($group_id, $taxonomy);

            $forced_message = 2;
        }

        // handle views
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
            // 'post_type' => constant("Advanced_Ads::POST_TYPE_SLUG"),
            'page' => 'advanced-ads-groups',
        );
        $args = $args + $defaultargs;

        return add_query_arg($args, admin_url('admin.php'));
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */
    public function add_action_links($links) {

        return array_merge(
                array(
            'settings' => '<a href="' . admin_url('edit.php?post_type=advanced_ads&page=advanced-ads-settings') . '">' . __('Settings', ADVADS_SLUG) . '</a>'
                ), $links
        );
    }

    /**
     * add information about the ad below the ad title
     *
     * @since 1.1.0
     * @param obj $post
     */
    public function edit_form_below_title($post){
        if (!isset($post->post_type) || $post->post_type != $this->post_type) {
            return;
        }
        $ad = new Advads_Ad($post->ID);

        require_once('views/ad_info.php');
    }

    /**
     * Add meta boxes
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        add_meta_box(
                'ad-main-box', __('Ad Type', ADVADS_SLUG), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
        );
        add_meta_box(
                'ad-parameters-box', __('Ad Parameters', ADVADS_SLUG), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
        );
        add_meta_box(
                'ad-output-box', __('Layout / Output', ADVADS_SLUG), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
        );
        add_meta_box(
                'ad-display-box', __('Display Conditions', ADVADS_SLUG), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
        );
        add_meta_box(
                'ad-visitor-box', __('Visitor Conditions', ADVADS_SLUG), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
        );
    }

    /**
     * add meta values below submit box
     *
     * @since 1.3.15
     */
    public function add_submit_box_meta(){
        global $post, $wp_locale;

        if($post->post_type !== Advanced_Ads::POST_TYPE_SLUG) return;

        $ad = new Advads_Ad($post->ID);

	$time_adj = current_time('timestamp');

        $curr_day    = (!empty($ad->expiry_date)) ? date('d', $ad->expiry_date) : gmdate( 'd', $time_adj );
        $curr_month  = (!empty($ad->expiry_date)) ? date('m', $ad->expiry_date) : gmdate( 'm', $time_adj );
        $curr_year   = (!empty($ad->expiry_date)) ? date('Y', $ad->expiry_date) : gmdate( 'Y', $time_adj );

        $enabled = (!empty($ad->expiry_date)) ? 1 : 0;

        require_once(plugin_dir_path(__FILE__) . 'views/ad-submitbox-meta.php');
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
            case 'ad-output-box':
                $view = 'ad-output-metabox.php';
                break;
            case 'ad-display-box':
                $view = 'ad-display-metabox.php';
                break;
            case 'ad-visitor-box':
                $view = 'ad-visitor-metabox.php';
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

        // don’t do this on revisions
        if ( wp_is_post_revision( $post_id ) )
		return;

        // get ad object
        $ad = new Advads_Ad($post_id);
        if (!$ad instanceof Advads_Ad)
            return;

        $ad->type = $_POST['advanced_ad']['type'];
        if(isset($_POST['advanced_ad']['output'])) {
            $ad->set_option('output', $_POST['advanced_ad']['output']);
        } else {
            $ad->set_option('output', array());
        }
        if(isset($_POST['advanced_ad']['visitor'])) {
            $ad->set_option('visitor', $_POST['advanced_ad']['visitor']);
        } else {
            $ad->set_option('visitor', array());
        }
        // save size
        $ad->width = 0;
        if(isset($_POST['advanced_ad']['width'])) {
            $ad->width = absint($_POST['advanced_ad']['width']);
        }
        $ad->height = 0;
        if(isset($_POST['advanced_ad']['height'])) {
            $ad->height = absint($_POST['advanced_ad']['height']);
        }

        if(!empty($_POST['advanced_ad']['description']))
            $ad->description = esc_textarea($_POST['advanced_ad']['description']);
        else $ad->description = '';

        if(!empty($_POST['advanced_ad']['content']))
            $ad->content = $_POST['advanced_ad']['content'];
        else $ad->content = '';

        if(!empty($_POST['advanced_ad']['conditions'])){
            $ad->conditions = $_POST['advanced_ad']['conditions'];
        } else {
            $ad->conditions = array();
        }
        // prepare expiry date
        if(isset($_POST['advanced_ad']['expiry_date']['enabled'])) {
            $year   = absint($_POST['advanced_ad']['expiry_date']['year']);
            $month  = absint($_POST['advanced_ad']['expiry_date']['month']);
            $day    = absint($_POST['advanced_ad']['expiry_date']['day']);
            $ad->expiry_date = mktime(0, 0, 0, $month, $day, $year);
        } else {
            $ad->expiry_date = 0;
        }

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

        // get settings page hook
        $hook = $this->plugin_screen_hook_suffix;

        // register settings
 	register_setting($hook, ADVADS_SLUG);

        // add new section
 	add_settings_section(
		'advanced_ads_setting_section',
		__('General', ADVADS_SLUG),
		array($this, 'render_settings_section_callback'),
		$hook
	);

 	// add setting fields to disable ads
 	add_settings_field(
		'disable-ads',
		__('Disable ads', ADVADS_SLUG),
		array($this, 'render_settings_disable_ads'),
		$hook,
		'advanced_ads_setting_section'
	);
 	// add setting fields for user role
 	add_settings_field(
		'hide-for-user-role',
		__('Hide ads for logged in users', ADVADS_SLUG),
		array($this, 'render_settings_hide_for_users'),
		$hook,
		'advanced_ads_setting_section'
	);
 	// add setting fields for advanced js
 	add_settings_field(
		'activate-advanced-js',
		__('Use advanced JavaScript', ADVADS_SLUG),
		array($this, 'render_settings_advanced_js'),
		$hook,
		'advanced_ads_setting_section'
	);
 	// add setting fields for content injection priority
 	add_settings_field(
		'content-injection-priority',
		__('Priority of content injection filter', ADVADS_SLUG),
		array($this, 'render_settings_content_injection_priority'),
		$hook,
		'advanced_ads_setting_section'
	);

        // hook for additional settings from add-ons
        do_action('advanced-ads-settings-init', $hook);
    }

    /**
     * render settings section
     *
     * @since 1.1.1
     */
    public function render_settings_section_callback(){
        // for whatever purpose there might come
    }

    /**
     * options to disable ads
     *
     * @since 1.3.11
     */
    public function render_settings_disable_ads(){
        $options = Advanced_Ads::get_instance()->options();

        // set the variables
        $disable_all = isset($options['disabled-ads']['all']) ? 1 : 0;
        $disable_404 = isset($options['disabled-ads']['404']) ? 1 : 0;
        $disable_archives = isset($options['disabled-ads']['archives']) ? 1 : 0;

        // load the template
        $view = plugin_dir_path(__FILE__) . 'views/settings_disable_ads.php';
        if (is_file($view)) {
            require( $view );
        }
    }

    /**
     * render setting to hide ads from logged in users
     *
     * @since 1.1.1
     */
    public function render_settings_hide_for_users(){
        $options = Advanced_Ads::get_instance()->options();
        $current_capability_role = isset($options['hide-for-user-role']) ? $options['hide-for-user-role'] : 0;


        $capability_roles = array(
            '' => __('(display to all)', ADVADS_SLUG),
            'read' => __('Subscriber', ADVADS_SLUG),
            'delete_posts' => __('Contributor', ADVADS_SLUG),
            'edit_posts' => __('Author', ADVADS_SLUG),
            'edit_pages' => __('Editor', ADVADS_SLUG),
            'activate_plugins' => __('Admin', ADVADS_SLUG),
        );
        echo '<select name="'.ADVADS_SLUG.'[hide-for-user-role]">';
        foreach($capability_roles as $_capability => $_role) {
            echo '<option value="'.$_capability.'" '.selected($_capability, $current_capability_role, false).'>'.$_role.'</option>';
        }
        echo '</select>';

        echo '<p class="description">'. __('Choose the lowest role a user must have in order to not see any ads.', ADVADS_SLUG) .'</p>';
    }

    /**
     * render setting to display advanced js file
     *
     * @since 1.2.3
     */
    public function render_settings_advanced_js(){
        $options = Advanced_Ads::get_instance()->options();
        $checked = (!empty($options['advanced-js'])) ? 1 : 0;

        echo '<input id="advanced-ads-advanced-js" type="checkbox" value="1" name="'.ADVADS_SLUG.'[advanced-js]" '.checked($checked, 1, false).'>';
        echo '<p class="description">'. sprintf(__('Only enable this if you can and want to use the advanced JavaScript functions described <a href="%s">here</a>.', ADVADS_SLUG), 'http://wpadvancedads.com/javascript-functions/') .'</p>';
    }

    /**
     * render setting for content injection priority
     *
     * @since 1.4.1
     */
    public function render_settings_content_injection_priority(){
        $options = Advanced_Ads::get_instance()->options();
        $priority = (!empty($options['content-injection-priority'])) ? absint($options['content-injection-priority']) : 100;

        echo '<input id="advanced-ads-content-injection-priority" type="number" value="'.$priority.'" name="'.ADVADS_SLUG.'[content-injection-priority]" size="3"/>';
        echo '<p class="description">'. __('Play with this value in order to change the priority of the injected ads compared to other auto injected elements in the post content.', ADVADS_SLUG) .'</p>';
    }

    /**
     * add heading for extra column of ads list
     * remove the date column
     *
     * @since 1.3.3
     * @param arr $defaults
     */
    public function ad_list_columns_head($defaults){

        $offset = array_search('title', array_keys($defaults)) + 1;

        $defaults = array_merge
        (
            array_slice($defaults, 0, $offset),
            array('ad_details' => __('Ad Details', ADVADS_SLUG)),
            array_slice($defaults, $offset, null)
        );

        // remove the date
        unset($defaults['date']);

        return $defaults;
    }

    /**
     * order ads by title on ads list
     *
     * @since 1.3.18
     * @param arr $vars array with request vars
     */
    public function ad_list_request($vars){

        // order ads by title on ads list
        if ( is_admin() && empty( $vars['orderby'] ) && $this->post_type == $vars['post_type'] ) {
            $vars = array_merge( $vars, array(
                'orderby' => 'title',
                'order' => 'ASC'
            ) );
        }

        return $vars;
    }

    /**
     * display ad details in ads list
     *
     * @since 1.3.3
     * @param string $column_name name of the column
     * @param int $ad_id id of the ad
     */
    public function  ad_list_columns_content($column_name, $ad_id) {
        if ($column_name == 'ad_details') {
            $ad = new Advads_Ad($ad_id);

            // load ad type title
            $types = Advanced_Ads::get_instance()->ad_types;
            $type = (!empty($types[$ad->type]->title)) ? $types[$ad->type]->title : 0;

            // load ad size
            $size = 0;
            if (!empty($ad->width) || !empty($ad->height)) {
                $size = sprintf('%d x %d', $ad->width, $ad->height);
            }

			$size = apply_filters('advanced-ads-list-ad-size', $size, $ad);

            $view = plugin_dir_path(__FILE__) . 'views/ad_list_details_column.php';
            if (is_file($view)) {
                require( $view );
            }

        }
    }

    /**
     * add a meta box to post type edit screens with ad settings
     *
     * @since 1.3.10
     * @param string $post_type current post type
     */
    public function add_post_meta_box($post_type = ""){
        // get public post types
        $public_post_types = get_post_types(array('public' => true, 'publicly_queryable' => true), 'names', 'or');

        //limit meta box to public post types
        if ( in_array( $post_type, $public_post_types )) {
            add_meta_box(
                'advads-ad-settings',
                __( 'Ad Settings', ADVADS_SLUG),
                array( $this, 'render_post_meta_box' ),
                $post_type,
                'advanced',
                'low'
            );
        }
    }

    /**
     * render meta box for ad settings on a per post basis
     *
     * @since 1.3.10
     * @param WP_Post $post The post object.
    */
    public function render_post_meta_box( $post ) {

        // nonce field to check when we save the values
        wp_nonce_field( 'advads_post_meta_box', 'advads_post_meta_box_nonce' );

        // retrieve an existing value from the database.
        $values = get_post_meta( $post->ID, '_advads_ad_settings', true );

        // load the view
        $view = plugin_dir_path(__FILE__) . 'views/post_ad_settings_metabox.php';
        if (is_file($view)) {
            require( $view );
        }
    }

    /**
     * save the ad meta when the post is saved.
     *
     * @since 1.3.10
     * @param int $post_id The ID of the post being saved.
    */
    public function save_post_meta_box( $post_id ) {

        // check nonce
        if ( ! isset( $_POST['advads_post_meta_box_nonce'] ) )
            return $post_id;

        $nonce = $_POST['advads_post_meta_box_nonce'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'advads_post_meta_box' ) )
            return $post_id;

        // don’t save on autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return $post_id;

        // check the user's permissions.
        if ( 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) )
                return $post_id;
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) )
                return $post_id;
        }

        // Sanitize the user input.
        $_data['disable_ads'] = isset($_POST['advanced_ads']['disable_ads']) ? absint($_POST['advanced_ads']['disable_ads']) : 0;

        // Update the meta field.
        update_post_meta( $post_id, '_advads_ad_settings', $_data );
    }

    /**
     * add dashboard widget with ad stats and additional information
     *
     * @since 1.3.12
     */
    public function add_dashboard_widget(){
        // wp_add_dashboard_widget('advads_dashboard_widget', __('Ads Dashboard', ADVADS_SLUG), array($this, 'dashboard_widget_function'));
        add_meta_box('advads_dashboard_widget', __('Ads Dashboard', ADVADS_SLUG), array($this, 'dashboard_widget_function'), 'dashboard', 'side', 'high');
    }

    /**
     * display widget functions
     */
    public function dashboard_widget_function($post, $callback_args){
        // load ad optimization feed
        $feed = array(
            'link'         => 'http://webgilde.com/en/ad-optimization/',
            'url'          => 'http://webgilde.com/en/ad-optimization/feed/',
            'title'        => __('Tutorials and News'),
            'items'        => 3,
            'show_summary' => 0,
            'show_author'  => 0,
            'show_date'    => 0,
        );

        // get number of ads
        $recent_ads = Advanced_Ads::get_ads();
        echo '<p>';
        printf(__('%d ads – <a href="%s">manage</a> - <a href="%s">new</a>', ADVADS_SLUG),
            count($recent_ads),
            'edit.php?post_type='. Advanced_Ads::POST_TYPE_SLUG,
            'post-new.php?post_type='. Advanced_Ads::POST_TYPE_SLUG);
        echo '</p>';

        // get and display plugin version
        $advads_plugin_data = get_plugin_data(ADVADS_BASE_PATH . 'advanced-ads.php');
        if(isset($advads_plugin_data['Version'])){
            $version = $advads_plugin_data['Version'];
            echo '<p><a href="http://wpadvancedads.com" target="_blank" title="'.
                    __('plugin manual and homepage', ADVADS_SLUG).'">Advanced Ads</a> '. $version .'</p>';
        }

        // rss feed
	echo '<h4>' . __('From the ad optimization universe', ADVADS_SLUG) . '</h4>';
        // $this->dashboard_widget_function_output('advads_dashboard_widget', $feed);
        $this->dashboard_cached_rss_widget( 'advads_dashboard_widget', array($this, 'dashboard_widget_function_output'), array('advads' => $feed) );
    }

    /**
     * checks to see if there are feed urls in transient cache; if not, load them
     * built using a lot of https://developer.wordpress.org/reference/functions/wp_dashboard_cached_rss_widget/
     *
     * @since 1.3.12
     * @param string $widget_id
     * @param callback $callback
     * @param array $check_urls RSS feeds
     * @return bool False on failure. True on success.
     */
    function dashboard_cached_rss_widget( $widget_id, $callback, $feed = array() ) {
        if ( empty($feed) ) {
            return;
        }

        $cache_key = 'dash_' . md5( $widget_id );
        if ( false !== ( $output = get_transient( $cache_key ) ) ) {
            echo $output;
            return true;
        }

        if ( $callback && is_callable( $callback ) ) {
            ob_start();
            call_user_func_array( $callback, $feed );
            set_transient( $cache_key, ob_get_flush(), 12 * HOUR_IN_SECONDS ); // Default lifetime in cache of 12 hours (same as the feeds)
        }

        return true;
    }

    /**
     * create the rss output of the widget
     *
     * @param string $widget_id Widget ID.
     * @param array  $feeds     Array of RSS feeds.
     */
    function dashboard_widget_function_output( $feed ) {

	echo '<div class="rss-widget">';
        wp_widget_rss_output( $feed['url'], $feed );
        echo "</div>";
    }

}
