<?php

/**
 * Advanced Ads dfp Ad Type
 *
 * @package   Advanced_Ads
 * @author    Thomas Maier <thomas.maier@webgilde.com>
 * @license   GPL-2.0+
 * @link      http://webgilde.com
 * @copyright 2015 Thomas Maier, webgilde GmbH
 *
 * Class containing information about the adsense ad type
 *
 * see also includes/class-ad-type-abstract.php for basic object
 *
 */
class Advads_Ad_Type_Adsense extends Advads_Ad_Type_Abstract {

    /**
     * ID - internal type of the ad type
     *
     * must be static so set your own ad type ID here
     * use slug like format, only lower case, underscores and hyphens
     *
     * @since 1.4
     */
    public $ID = 'adsense';

    /**
     * set basic attributes
     *
     * @since 1.4
     */
    public function __construct() {
        $this->title = __('AdSense ad', ADVADS_SLUG);
        $this->description = __('Use ads from your Google AdSense account', ADVADS_SLUG);
        $this->parameters = array(
            'content' => ''
        );
    }

    /**
     * output for the ad parameters metabox
     *
     * this will be loaded using ajax when changing the ad type radio buttons
     * echo the output right away here
     * name parameters must be in the "advanced_ads" array
     *
     * @param obj $ad ad object
     * @since 1.4
     */
    public function render_parameters($ad) {
        $content = (isset($ad->content)) ? $ad->content : '';
        $unit_id = '';
        $unit_code = '';
        $unit_type = '';
        $unit_width = 0;
        $unit_height = 0;
		$json_content = '';
		$unit_resize = '';
		$extra_params = array(
			'default_width' => '',
			'default_height' => '',
			'at_media' => array(),
		);

        $db = Gadsense_Data::get_instance();
        $pub_id = $db->get_adsense_id();

        if (!empty($content)) {
            $json_content = $content;
            $content = json_decode($content);
            if (isset($content->unitType)) {
                $content->json = $json_content;
                $unit_type = $content->unitType;
                $unit_code = $content->slotId;
                if ('responsive' != $content->unitType) {
					// Normal ad unit
                    $unit_width = $ad->width;
                    $unit_height = $ad->height;
                } else {
					// Responsive
					$unit_resize = (isset($content->resize)) ? $content->resize : 'auto';
					if ('auto' != $unit_resize) {
						$extra_params = apply_filters('advanced-ads-gadsense-ad-param-data', $extra_params, $content, $ad);
					}
				}
                if (!empty($pub_id)) {
                    $unit_id = 'ca-' . $pub_id . ':' . $unit_code;
                }
            }
        }
        $default_template = GADSENSE_BASE_PATH . '/admin/views/adsense-ad-parameters.php';
        /**
         * Inclusion of other UI template is done here. The content is passed in order to allow the inclusion of different
         * templates file, depending of the ad. It's up to the developer to verify that $content is not an empty
         * variable (which is the case for a new ad).
         *
         * Inclusion of .js and .css files for the ad creation/editon page are done by another hook. See
         * 'advanced-ads-gadsense-ad-param-script' and 'advanced-ads-gadsense-ad-param-style' in "../admin/class-gadsense-admin.php".
         */
        $template = apply_filters('advanced-ads-gadsense-ad-param-template', $default_template, $content);
        require($template);
    }

    /**
     * sanitize content field on save
     *
     * @param str $content ad content
     * @return str $content sanitized ad content
     * @since 1.0.0
     */
    public function sanitize_content($content = '') {
        $content = wp_unslash($content);
        return $content = apply_filters('content_save_pre', $content);
    }

    /**
     * prepare the ads frontend output
     *
     * @param obj $ad ad object
     * @return str $content ad content prepared for frontend output
     * @since 1.0.0
     */
    public function prepare_output($ad) {
        global $gadsense;

        $content = json_decode($ad->content);
        $output = '';
        $db = Gadsense_Data::get_instance();
        $pub_id = $db->get_adsense_id();
		$limit_per_page = $db->get_limit_per_page();
		
        if (!isset($content->unitType) || empty($pub_id))
            return $output;
        if (!isset($gadsense['google_loaded']) || !$gadsense['google_loaded']) {
            $output .= '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>' . "\n";
            $gadsense['google_loaded'] = true;
        }

        if (isset($gadsense['adsense_count'])) {
            $gadsense['adsense_count']++;
        } else {
            $gadsense['adsense_count'] = 1;
        }
		
        if ($limit_per_page && 3 < $gadsense['adsense_count']) {
            // The maximum allowed adSense ad per page count is 3 (according to the current Google AdSense TOS).
            return '';
        }

        if ('responsive' != $content->unitType) {
			$output .= '<ins class="adsbygoogle" ';
            $output .= 'style="display:inline-block;width:' . $ad->width . 'px;height:' . $ad->height . 'px;" ' . "\n";
            $output .= 'data-ad-client="ca-' . $pub_id . '" ' . "\n";
            $output .= 'data-ad-slot="' . $content->slotId . '"></ins> ' . "\n";
            $output .= '<script> ' . "\n";
            $output .= '(adsbygoogle = window.adsbygoogle || []).push({}); ' . "\n";
            $output .= '</script>' . "\n";
        } else {
			if (!isset($content->resize) || 'auto' == $content->resize) {
				$this->append_defaut_responsive_content($output, $pub_id, $content->slotId);
			} else {
				/**
				 * At this point, the ad is responsive ($ad->content->unitType == responsive)
				 * The value of $ad->content->resize should be tested to format the output correctly
				 * The $output variable already contains the first line which includes "adsbygoogle.js",
				 * The rest of the output should be appended to it.
				 */
				$unmodified = $output;
				$output = apply_filters('advanced-ads-gadsense-responsive-output', $output, $ad, $pub_id);
				if ($unmodified == $output) {
					/**
					 * If the output has not been modified, perform a default responsive output.
					 * A simple did_action check isn't sufficient, some hooks may be attached and fired but didn't touch the output
					 */
					$this->append_defaut_responsive_content($output, $pub_id, $content->slotId);
				}
			}
        }
        return $output;
    }
	
	private function append_defaut_responsive_content(&$output, $pub_id, $slot_id) {
		$output .= '<ins class="adsbygoogle" ';
		$output .= 'style="display:block;"';
		$output .= 'data-ad-client="ca-' . $pub_id . '" ' . "\n";
		$output .= 'data-ad-slot="' . $slot_id . '" ' . "\n";
		$output .= 'data-ad-format="auto"></ins>' . "\n";
		$output .= '<script> ' . "\n";
		$output .= '(adsbygoogle = window.adsbygoogle || []).push({}); ' . "\n";
		$output .= '</script>' . "\n";
	}

}

/**
 * initialize ad type and add it to the plugins ad types
 *
 * "content" key must match the id
 */
function advads_add_ad_type_adsense($types) {
    $types['adsense'] = new Advads_Ad_Type_Adsense();
    return $types;
}

add_filter('advanced-ads-ad-types', 'advads_add_ad_type_adsense');
