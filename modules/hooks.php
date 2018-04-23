<?php

/**
 * @Author: suifengtec
 * @Date:   2018-04-20 04:36:56
 * @Last Modified by:   suifengtec
 * @Last Modified time: 2018-04-24 02:19:01
 **/

if (!defined('ABSPATH')) {
	exit;
}

/*

 */
class EDD_Matomo_Module_Hooks {

	private $enable = false;
	private $siteId;
	private $matomoDomain;

	private $standard_tracking_enabled = false;
	private $ecommerce_tracking_enabled = false;
/*	private $cartupdate_tracking_enabled = false;*/

	public function __construct() {

/*

购物车页面
do_action( 'edd_cart_items_before' );
do_action( 'edd_cart_items_middle' );
do_action( 'edd_cart_items_after' );
$cart_items = edd_get_cart_contents();
 */
/*
注册页面
do_action('edd_register_fields_before');
do_action( 'edd_purchase_form_user_register_fields' );

 */
/*
登录页面
do_action('edd_checkout_login_fields_after');

 */

/*
支付模式选择
do_action('edd_payment_mode_bottom');

 */
/*
文件下载页面
apply_filters( 'edd_receipt_show_download_files', $ret, $item_id, $receipt_args, $item );

 */
/*
产品页面
do_action( 'marketify_single_download_after' );

 */
/*

结算后跳转到
do_action( 'edd_payment_receipt_after_table', $payment, $edd_receipt_args );

 */
		global $edd_options;

		if (!$edd_options && function_exists('edd_get_settings')) {
			$edd_options = edd_get_settings();
		}

		if ($edd_options['matomo_domain'] && $edd_options['matomo_site_id'] && ($edd_options['matomo_enableit'] == '1')) {

			$this->matomoDomain = $edd_options['matomo_domain'];
			$this->siteId = $edd_options['matomo_site_id'];
			$this->enable = true;
			$this->standard_tracking_enabled = $edd_options['matomo_standard_tracking_enabled'] == '1' ? true : false;
			$this->ecommerce_tracking_enabled = $edd_options['matomo_ecommerce_tracking_enabled'] == '1' ? true : false;
			/*	$this->cartupdate_tracking_enabled = $edd_options['matomo_cartupdate_tracking_enabled'] == '1' ? true : false;*/

		}

		if ($this->ecommerce_tracking_enabled) {

			/*单件产品*/
			add_action('marketify_single_download_after', array($this, 'ecommerce_tracking_single_product'));
			/*购物车*/
			add_action('edd_cart_items_before', array($this, 'ecommerce_tracking_cart'));
			/*结算后*/
			add_action('edd_payment_receipt_after_table', array($this, 'ecommerce_tracking_paid_order'), 10, 2);

			add_action('wp_footer', array('EDD_Matomo_Module_Utils', 'printJS'), 27);
		}

/*放在最后加载*/
		if ($this->standard_tracking_enabled || $this->ecommerce_tracking_enabled) {

			add_action('wp_footer', array($this, 'standard_tracking_code'), 28);
		}

	}

	public function ecommerce_tracking_paid_order($payment, $edd_receipt_args) {

		$order_id = $payment->ID;
/*		if (!edd_is_payment_complete($order_id) || get_post_meta($order_id, '_matomo_tracked', true) == 1) {
return;
}*/

		$orderStatus = $payment->post_status == 'publish' ? true : false;

		/*	$order = edd_get_payment($order_id);*/
		$status = edd_get_payment_status($payment, true);
		$total = edd_get_payment_amount($order_id);

		/*var_dump($total);*/

		$cart = edd_get_payment_meta_cart_details($order_id, true);

		if (!$cart || !is_array($cart)) {
			return;
		}

		/*$code = 'var _paq = _paq || [];';*/
		$code = 'if(typeof _paq=="undefined" ){var _paq = _paq || [];}';

		foreach ($cart as $key => $item) {
			/*$item['name'];*/

			$code .= '
	                _paq.push(["addEcommerceItem",
	                    "' . esc_js($item['id']) . '",
	                    "' . esc_js($item['name']) . '",';

			$out = array();
			$categories = get_the_terms($item['id'], 'download_category');
			if ($categories) {
				foreach ($categories as $category) {
					$out[] = $category->name;
				}
			}
			if (count($out) > 0) {
				$code .= '["' . join("\", \"", $out) . '"],';
			} else {
				$code .= '[],';
			}

			$price_id = 0;
			if (edd_has_variable_prices($item['id'])) {
				$price_id = edd_get_cart_item_price_id($item);
			}
			$price = EDD_Matomo_Module_Utils::getProductPrice($item['id'], $price_id);
			$code .= '"' . esc_js($price) . '",';
			$code .= '"' . esc_js(1) . '"';
			$code .= "]);";
		}

		$code .= ' _paq.push(["trackEcommerceOrder",
	                "' . esc_js($order_id) . '",
	                "' . esc_js($total) . '",
	                "' . esc_js($total) . '",
	                "' . esc_js(0) . '",
	                "' . esc_js(0) . '"
	            ]); ';

		echo '<script type="text/javascript">' . $code . '</script>';
		update_post_meta($order_id, '_matomo_tracked', 1);

	}

	public function ecommerce_tracking_cart() {
		$cart_items = edd_get_cart_contents();

		if ($cart_items):
			$code = 'var cartItems = [];';

			foreach ($cart_items as $key => $item):

				$item_sku = esc_js($item['id']);
				/*
					edd_cart_item_price
					edd_get_cart_item_price
				*/
				$item_price = edd_get_cart_item_price($item['id'], $item['options']);
				$item_title = edd_get_cart_item_name($item);
				$cats = $this->getProductCategories($item['id']);

			endforeach;
			$code .= " cartItems.push({sku: \"$item_sku\",title: \"$item_title\",price: $item_price,quantity: {$item['quantity']},categories: $cats });";
			EDD_Matomo_Module_Utils::enqueueJS("" . $code . " var arrayLength = cartItems.length, revenue = 0;for (var i = 0; i < arrayLength; i++) {_paq.push(['addEcommerceItem', cartItems[i].sku, cartItems[i].title,cartItems[i].categories,cartItems[i].price, cartItems[i].quantity ]);revenue += cartItems[i].price * cartItems[i].quantity;}_paq.push(['trackEcommerceCartUpdate', revenue]);");
		endif;
	}

	public function ecommerce_tracking_single_product() {
		if (!is_singular('download')) {
			return;
		}
		global $post;
		if (!$post) {
			return;
		}

		$jsCode = sprintf("_paq.push(['setEcommerceView','%s','%s', %s,%f]);_paq.push(['trackPageView']);",
			get_the_ID(),
			urlencode($post->post_title),
			$this->getEncodedCategoriesByProduct(get_the_ID()),
			EDD_Matomo_Module_Utils::getProductPrice(get_the_ID())
		);
		EDD_Matomo_Module_Utils::enqueueJS($jsCode);

	}

	protected function getEncodedCategoriesByProduct($productID) {

		$categories = get_the_terms($productID, 'download_category');

		if (!$categories) {
			$categories = array();
		}

		$categories = array_map(function ($element) {
			return sprintf("'%s'", urlencode($element->name));
		}, $categories);

		return sprintf("[%s]", implode(", ", $categories));
	}

	protected function getProductCategories($itemID) {
		$out = array();
		$categories = get_the_terms($itemID, 'download_category');

		if ($categories) {
			foreach ($categories as $category) {
				$out[] = $category->name;
			}
		}
		if (count($out) > 0) {
			$cats = '["' . join("\", \"", $out) . '"]';

			return $cats;
		} else {
			$cats = '[]';

			return $cats;
		}
	}

	public function standard_tracking_code() {

		?>

<!-- EDD Matomo -->
<script type="text/javascript">
  if(typeof _paq=='undefined' ){var _paq = _paq || [];}
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u=(("https:" == document.location.protocol) ? "https" : "http") + "://<?php echo esc_js($this->matomoDomain); ?>/";
    _paq.push(['setTrackerUrl', u+'piwik.php']);
    _paq.push(['setSiteId', '<?php echo esc_js($this->siteId); ?>']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<noscript>
<img src="http://<?php echo esc_js($this->matomoDomain); ?>/piwik.php?idsite=<?php echo esc_js($this->siteId); ?>"style="border:0;" alt=""/>
</noscript>
<!-- End EDD Matomo Code -->

		<?php
}
}
/*EOF*/
