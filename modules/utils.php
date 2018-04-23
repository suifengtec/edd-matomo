<?php

/**
 * @Author: suifengtec
 * @Date:   2018-04-24 01:48:14
 * @Last Modified by:   suifengtec
 * @Last Modified time: 2018-04-24 02:02:56
 **/

if (!defined('ABSPATH')) {
	exit;
}

/*

 */
class EDD_Matomo_Module_Utils {

	public function __construct() {

	}

	public static function enqueueJS($code) {

		global $cwp_queued_js;

		if (empty($cwp_queued_js)) {
			$cwp_queued_js = '';
		}

		$cwp_queued_js .= "\n" . $code . "\n";
	}

	public static function printJS() {

		global $cwp_queued_js;
		if (!empty($cwp_queued_js)) {
			// Sanitize.
			$cwp_queued_js = wp_check_invalid_utf8($cwp_queued_js);
			$cwp_queued_js = preg_replace('/&#(x)?0*(?(1)27|39);?/i', "'", $cwp_queued_js);
			$cwp_queued_js = str_replace("\r", '', $cwp_queued_js);

			$js = "<!-- EDD Matomo JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $cwp_queued_js });\n</script>\n";

			echo apply_filters('coolwp_edd_matomo_queued_js', $js);

			unset($cwp_queued_js);
		}
	}

	/*
		        EDD_Matomo_Module_Utils::getProductPrice();
	*/
	public static function getProductPrice($download_id, $price_id = 0) {
		if (edd_has_variable_prices($download_id)) {

			$prices = edd_get_variable_prices($download_id);

			if (false !== $price_id && isset($prices[$price_id])) {
				$price = (float) $prices[$price_id]['amount'];
			} else {
				$price = edd_get_lowest_price_option($download_id);
			}

			$price = edd_sanitize_amount($price);

		} else {

			$price = edd_get_download_price($download_id);

		}
		return $price;
	}
}
/*EOF*/
