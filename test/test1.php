<?php

/**
 * @Author: suifengtec
 * @Date:   2018-04-24 01:32:33
 * @Last Modified by:   suifengtec
 * @Last Modified time: 2018-04-24 01:47:51
 **/
/*
http://dev-edd.com/wp-content/plugins/edd-matomo/test/test1.php

 */
require_once dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . 'wp-load.php';
global $matomo;
$download_id = 85;
$a = edd_get_download_price($download_id);
$a = get_post_meta($download_id, 'edd_price', true);
$a = get_post_meta($download_id, 'edd_variable_prices', true);
$a = edd_get_lowest_price_option($download_id);
$price = edd_sanitize_amount($a);
var_dump($price);