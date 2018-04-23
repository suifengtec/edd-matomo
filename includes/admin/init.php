<?php

/**
 * @Author: suifengtec
 * @Date:   2018-04-20 03:45:10
 * @Last Modified by:   suifengtec
 * @Last Modified time: 2018-04-24 01:35:53
 **/

if (!defined('ABSPATH')) {
	exit;
}

/*
http://dev-bibi.com
&token_auth=dceef7766dc1c5706ac1808aad8f60ae

 */
class EDD_Matomo_Admin_Init {

	const PIWIK_PRO_URL = 'panel.piwik.pro';

	public function __construct() {

/*

apply_filters('edd_settings_tabs', $tabs);
 */
		add_filter('edd_settings_tabs', array($this, 'edd_settings_tabs'), 11, 1);

/*

apply_filters( 'edd_settings_sections', $sections );

 */

		add_filter('edd_settings_sections', array($this, 'edd_settings_sections'), 10, 1);
		/*

						                edd_get_registered_settings()
			apply_filters( 'edd_registered_settings', $edd_settings );
		*/

		add_filter('edd_registered_settings', array($this, 'edd_registered_settings'), 10, 1);

/*

$input = apply_filters('edd_settings_' . $tab . '_sanitize', $input);

 */
		/*add_filter('edd_settings_matomo_sanitize', array($this, 'edd_settings_matomo_sanitize'), 10, 1);*/
		add_filter('edd_get_settings', array($this, 'edd_get_settings'), 11, 1);

	}

/*
add_settings_error('edd-notices', '', __('Settings updated.', 'easy-digital-downloads'), 'updated');
 */
	public function edd_settings_matomo_sanitize($input) {

		/*die(var_dump($input));*/
		return $input;
	}

	protected function generateToken() {
		return rtrim(strtr(base64_encode($this->getRandomNumber()), '+/', '-_'), '=');
	}
	protected function getRandomNumber() {
		$nbBytes = 32;

		// try OpenSSL
		if ($this->useOpenSsl()) {
			$bytes = openssl_random_pseudo_bytes($nbBytes, $strong);

			if (false !== $bytes && true === $strong) {
				return $bytes;
			}
		}

		return hash('sha256', uniqid(mt_rand(), true), true);
	}

	protected function useOpenSsl() {
		if (defined('PHP_WINDOWS_VERSION_BUILD') && version_compare(PHP_VERSION, '5.3.4', '<')) {
			return false;
		} elseif (!function_exists('openssl_random_pseudo_bytes')) {
			return false;
		} else {
			return true;
		}
	}

	protected function getSiteUrl() {
		$siteUrl = str_replace('http://', '', get_site_url());
		$siteUrl = str_replace('https://', '', $siteUrl);

		return $siteUrl;
	}

/*===================================*/
	public function edd_settings_tabs($tabs) {

		$tabs['matomo'] = __('Matomo/Piwik', 'matomo');
		return $tabs;
	}

	public function edd_settings_sections($sections) {

		$sections['matomo'] = apply_filters('edd_settings_sections_matomo', array(
			'main' => __('Main', 'easy-digital-downloads'),
			'common' => __('Common', 'matomo'),
		));

		return $sections;
	}

	public function edd_registered_settings($edd_settings) {

		$edd_settings['matomo'] = apply_filters('edd_settings_matomo',
			[
				'main' => [
					'matomo_enableit' => array(
						'id' => 'matomo_enableit',
						'name' => __('Enable', 'matomo'),
						'desc' => __('Check this to enable Matomo/Piwik.', 'matomo'),
						'type' => 'checkbox',
					),

					'matomo_site_id' => array(
						'id' => 'matomo_site_id',
						'name' => __('Matomo/Piwik site ID', 'easy-digital-downloads'),
						/*'desc' => __('You can find site ID in Matomo/Piwik administration panel', 'matomo'),*/
						'type' => 'text',
						'size' => 'regular',
						'tooltip_title' => __('Matomo/Piwik site ID', 'matomo'),
						'tooltip_desc' => __('You can find site ID in Matomo/Piwik administration panel', 'matomo'),
					),
					'matomo_domain' => array(
						'id' => 'matomo_domain',
						'name' => __('Matomo/Piwik domain', 'easy-digital-downloads'),
						/*'desc' => __('Location of your Matomo/Piwik installation (without http(s)://, i.e. matomo.coolwp.com)', 'matomo'),*/
						'tooltip_title' => __('Matomo/Piwik domain', 'matomo'),
						'tooltip_desc' => __('Location of your Matomo/Piwik installation (without http(s)://, i.e. matomo.coolwp.com)', 'matomo'),
						'type' => 'text',
						'size' => 'regular',
					),
				],
				'common' => [
					'matomo_standard_tracking_enabled' => array(
						'id' => 'matomo_standard_tracking_enabled',
						'name' => __('Add tracking code to your site', 'matomo'),
						'desc' => __('Add tracking code to your site. You don\'t need to enable this if using a 3rd party analytics plugin (i.e. Piwiktracking plugin)', 'matomo'),
						'type' => 'checkbox',
					),
					'matomo_ecommerce_tracking_enabled' => array(
						'id' => 'matomo_ecommerce_tracking_enabled',
						'name' => __('Add tracking code to   the download page', 'matomo'),
						'desc' => __('Add eCommerce tracking code to the download pageto track a payment.', 'matomo'),
						'type' => 'checkbox',
					),
					/*	'matomo_cartupdate_tracking_enabled' => array(
						'id' => 'matomo_cartupdate_tracking_enabled',
						'name' => __('Add cart update for add to cart actions (i.e. allows to track abandoned carts)', 'matomo'),
						'desc' => __('Add cart update for add to cart actions (i.e. allows to track abandoned carts)', 'matomo'),
						'type' => 'checkbox',
					),*/
				],

			]);

		return $edd_settings;
	}

	public function edd_get_settings($settings) {

		return $settings;
	}
}
/*EOF*/
