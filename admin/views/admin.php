<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Advanced_Ads_Admin
 * @author    Thomas Maier <thomas.maier@webgilde.com>
 * @license   GPL-2.0+
 * @link      http://webgilde.com
 * @copyright 2013 Thomas Maier, webgilde GmbH
 */
?>

<div class="wrap">
    <h2 style="color:red;"><?php _e('Work in progress', $this->plugin_slug); ?></h2>
    <p><?php _e('This screen is work in progress. You can use the information if you understand them, but there is nothing to do here yet.', $this->plugin_slug); ?></p>
    <?php screen_icon(); ?>
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <h2><?php _e('Ad Condition Overview', $this->plugin_slug); ?></h2>
    <pre><?php print_r($ads_by_conditions); ?></pre>

</div>
