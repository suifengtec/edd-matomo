<?php

/**
 * @Author: suifengtec
 * @Date:   2018-04-20 03:40:11
 * @Last Modified by:   suifengtec
 * @Last Modified time: 2018-04-24 02:04:00
 **/
/**
 * Plugin Name: EDD Matomo
 * Plugin URI: http://bbs.coolwp.org/topic/625-edd-matomo/
 * Description: EDD Matomo.
 * Author: suifengtec
 * Author URI: https://coolwp.com
 * Version: 0.9.0
 * Text Domain: matomo
 * Domain Path: /languages/
 *
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('EDD_Matomo')):

	final class EDD_Matomo {

		public static $isDebug = false;
		public static $isDev = false;

		public $dependencies = array();

		public static $version = '1.0.0';
		public static $dbVersion = '1.0.0';
		/**
		 * Stores the single instance of this plugin.
		 * @since 1.0.0
		 */
		private static $instance = null;

		/**
		 * Class instances
		 * @var array
		 * @since 1.0.0
		 */
		private $container = array();

		/**
		 * Minimum PHP version required
		 *
		 * @var string
		 */
		private $min_php = '5.4.0';

		/**
		 * Disable unserializing
		 *
		 * @since 1.0.0
		 */
		public function __wakeup() {}

		/**
		 * We don't want the object to be cloned.
		 * @since 1.0.0
		 */
		public function __clone() {}

		/**
		 * Magic isset to bypass referencing plugin.
		 *
		 * @param $prop
		 * @return mixed
		 * @since 1.0.0
		 */
		public function __isset($prop) {
			return isset($this->{$prop}) || isset($this->container[$prop]);
		}

		/**
		 * Auto-load in-accessible properties on demand.
		 *
		 * @param mixed $prop Key name.
		 * @return mixed
		 */
		public function __get($prop) {

			if (array_key_exists($prop, $this->container)) {
				return $this->container[$prop];
			}

			return $this->{$prop};

		}

		/**
		 *  Constructor
		 *
		 *  @since 1.0.0
		 */
		public function __construct() {

			$this->define_constants();

			$this->dependencies = [
				'easy-digital-downloads/easy-digital-downloads.php' => [
					'name' => 'Easy Digital Downloads',
					'url' => '#',
				],

			];

			if (!$this->is_supported_php()) {
				register_activation_hook(__FILE__, array($this, 'auto_deactivate'));
				add_action('admin_notices', array($this, 'php_version_notice'));
				return;
			}

			register_activation_hook(__FILE__, array($this, 'activate'));
			register_deactivation_hook(__FILE__, array($this, 'deactivate'));

			add_action('plugins_loaded', array($this, 'plugins_loaded'), 11);

		}

		/**
		 * Singleton instance of the current class
		 *
		 * @since 1.0.0
		 * @return EDD_Matomo
		 */
		public static function instance() {

			if (!isset(self::$instance) && !(self::$instance instanceof EDD_Matomo)) {
				self::$instance = new self();

			}
			return self::$instance;

		}

		public function plugins_loaded() {

			$this->includes();
			$this->init_hooks();
			do_action('matomo_loaded');

		}

		/**
		 * Hook into actions and filters.
		 * @since 1.0.0
		 */
		public function init_hooks() {

			spl_autoload_register(array(__CLASS__, '_autoload'));

			add_action('admin_notices', array($this, 'admin_notices'));

			add_action('wp_enqueue_scripts', array(__CLASS__, 'wp_enqueue_scripts'));
			add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));

			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'plugin_action_links'));
			/*add_action( 'matomo_schedule_event_hook' , array(__CLASS__, 'schedule_event_hook' ) );*/

			$this->load_general();

			$this->load_backend();
			//$this->load_frontend();
			//$this->load_api();

		}

		/**
		 * Load general classes.
		 * @return [type] [description]
		 */
		public function load_general() {

			new EDD_Matomo_Admin_Init;
			new EDD_Matomo_Module_Hooks;

		}

		/**
		 * Load general classes.
		 * @return [type] [description]
		 */
		public function load_backend() {

		}

		/**
		 * Load general classes.
		 * @return [type] [description]
		 */
		public function load_frontend() {

		}

		public static function plugin_action_links($links) {

			$links[] = '<a href="' . admin_url('it.php?post_type=download&page=edd-settings&tab=matomo&section=main') . '">' . __('Settings', 'matomo') . '</a>';
			$links[] = '<a href="http://bbs.coolwp.org/topic/625-edd-matomo/" target="_blank">Documentation</a>';

			return $links;

		}

		public static function schedule_event_hook() {

		}

		public static function wp_enqueue_scripts() {

			//$scheme = is_ssl() ? 'https' : 'http';
			//wp_enqueue_style( 'dashicons' );
			//wp_enqueue_style( 'matomo-frontend-css', MATOMO_PLUGIN_URL . 'assets/css/matomo-f.css' );

			/*

				        wp_register_script( 'matomo-frontend-js', MATOMO_PLUGIN_URL . 'assets/js/matomo-f.js', array('jquery'), false, true );

				        wp_localize_script(  'matomo-frontend-js', 'MATOMO_Data', array(
				            'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				            'error_message' => __( 'Please fix the errors to proceed', 'MATOMO' ),
				            'nonce'         => wp_create_nonce( 'MATOMO_nonce' )
				        ) );

				        wp_enqueue_script( 'matomo-frontend-js');

			*/

		}

		public static function admin_enqueue_scripts($hook) {

		}
		/**
		 *  Autoload self PHP class.
		 */
		public static function _autoload($class) {

			if (stripos($class, 'EDD_Matomo_') !== false) {

				$admin = (stripos($class, '_Admin_') !== false) ? true : false;
				$module = (stripos($class, '_Module_') !== false) ? true : false;
				$view = (stripos($class, '_View_') !== false) ? true : false;

				$interface = (stripos($class, '_Interface_') !== false) ? true : false;
				$abstract = (stripos($class, '_Abstract_') !== false) ? true : false;
				$data = (stripos($class, '_Data_') !== false) ? true : false;
				$misc = (stripos($class, 'Misc_') !== false) ? true : false;

				if ($admin) {
					$class_name = str_replace(array('EDD_Matomo_Admin_', '_'), array('', '-'), $class);
					$filename = dirname(__FILE__) . '/includes/admin/' . strtolower($class_name) . '.php';

				} elseif ($module) {

				$class_name = str_replace(array('EDD_Matomo_Module_', '_'), array('', '-'), $class);
				$filename = dirname(__FILE__) . '/modules/' . strtolower($class_name) . '.php';

			} elseif ($interface) {

				$class_name = str_replace(array('EDD_Matomo_Interface_', '_'), array('', '-'), $class);
				$filename = dirname(__FILE__) . '/includes/interfaces/' . strtolower($class_name) . '.php';

			} elseif ($abstract) {

				$class_name = str_replace(array('EDD_Matomo_Abstract_', '_'), array('', '-'), $class);
				$filename = dirname(__FILE__) . '/includes/abstracts/' . strtolower($class_name) . '.php';

			} elseif ($data) {

				$class_name = str_replace(array('EDD_Matomo_Data_', '_'), array('', '-'), $class);
				$filename = dirname(__FILE__) . '/includes/data/' . strtolower($class_name) . '.php';

			} elseif ($misc) {

				$class_name = str_replace(array('EDD_Matomo_Misc_', '_'), array('', '-'), $class);
				$filename = dirname(__FILE__) . '/includes/misc/' . strtolower($class_name) . '.php';

			} elseif ($view) {

				$class_name = str_replace(array('EDD_Matomo_View_', '_'), array('', '-'), $class);
				$filename = dirname(__FILE__) . '/templates/' . strtolower($class_name) . '.php';
				if (!file_exists($filename)) {
					$filename = dirname(__FILE__) . '/views/' . strtolower($class_name) . '.php';
				}

			} else {
				$class_name = str_replace(array('EDD_Matomo_', '_'), array('', '-'), $class);
				$filename = dirname(__FILE__) . '/includes/' . strtolower($class_name) . '.php';
				if (!file_exists($filename)) {
					$filename = dirname(__FILE__) . '/modules/' . strtolower($class_name) . '.php';
				}
			}
			//var_dump($filename);
			if (file_exists($filename)) {
				require_once $filename;
			}
		}
	}

	public static function set_schedule_events() {

		wp_schedule_event(time(), 'daily', 'matomo_schedule_event_hook');

	}

	public static function activate() {

		/*self::set_schedule_events();
        flush_rewrite_rules( false );*/
	}

	public static function deactivate() {

	}

	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	public function includes() {
		require_once ABSPATH . 'wp-includes/pluggable.php';
	}

	/**
	 * Define plugin constants.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function define_constants() {

		$this->define('MATOMO_PLUGIN_FILE', __FILE__);
		$this->define('MATOMO_PLUGIN_DIR', plugin_dir_path(__FILE__));
		$this->define('MATOMO_PLUGIN_URL', plugin_dir_url(__FILE__));
		$this->define('MATOMO_BASENAME', plugin_basename(__FILE__));

		$this->define('MATOMO_VERSION', self::$version);
		//$this->define('MATOMO_DB_VERSION', self::$dbVersion);

		//$this->define('MATOMO_ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);

		//$upload_dir = wp_upload_dir(null, false);

	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define($k, $v) {

		if (!defined($k)) {
			define($k, $v);
		}

	}

	public function admin_notices() {

		$k = $this->check_dependencies();

		if (!empty($k)) {?><div class="error"><p>EDD_Matomo 需要 <a href="<?php echo admin_url($this->dependencies[$k]['url']) ?>" target="_blank"><?php echo $this->dependencies[$k]['name'] ?></a> , 请先安装并激活它！</p></div><?php }

	}

	public function check_dependencies() {

		if (!function_exists('is_plugin_active')) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		foreach ($this->dependencies as $k => $v) {

			if (!is_plugin_active($k)) {

				return $k;
			}
		}
		return false;
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/matomo/matomo-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/matomo-LOCALE.mo
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain('matomo', false, dirname(plugin_basename(__FILE__)) . '/languages/');

	}

	/**
	 * What type of request is this?
	 *
	 * @param  string  admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request($type) {

		switch ($type) {
		case 'admin':
			return is_admin();
		case 'ajax':
			return defined('DOING_AJAX');
		case 'cron':
			return defined('DOING_CRON');
		case 'api':
			return defined('REST_REQUEST');
		case 'frontend':
			return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
		}

	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit(plugins_url('/', MATOMO_PLUGIN_FILE));
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit(plugin_dir_path(MATOMO_PLUGIN_FILE));
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url('admin-ajax.php', 'relative');
	}

	/**
	 * Check if the PHP version is supported
	 *
	 * @return bool
	 */
	public function is_supported_php($min_php = null) {

		$min_php = $min_php ? $min_php : $this->min_php;

		if (version_compare(PHP_VERSION, $min_php, '<=')) {
			return false;
		}

		return true;
	}

	/**
	 * Show notice about PHP version
	 *
	 * @return void
	 */
	function php_version_notice() {

		if ($this->is_supported_php() || !current_user_can('manage_options')) {
			return;
		}

		$error = __('Your installed PHP Version is: ', 'matomo') . PHP_VERSION . '. ';
		$error .= __('The <strong>EDD_Matomo</strong> plugin requires PHP version <strong>', 'free') . $this->min_php . __('</strong> or greater.', 'matomo');
		?>

                    <div class="error">
                        <p><?php printf($error);?></p>
                    </div>

                 <?php

	}

	/**
	 * Bail out if the php version is lower than
	 *
	 * @return void
	 */
	function auto_deactivate() {

		if ($this->is_supported_php()) {
			return;
		}

		deactivate_plugins(plugin_basename(__FILE__));

		$error = __('<h1>An Error Occured</h1>', 'matomo');
		$error .= __('<h2>Your installed PHP Version is: ', 'matomo') . PHP_VERSION . '</h2>';
		$error .= __('<p>The <strong> EDD Matomo</strong> plugin requires PHP version <strong>', 'free') . $this->min_php . __('</strong> or greater', 'matomo');
		$error .= __('<p>The version of your PHP is ', 'matomo') . '<a href="http://php.net/supported-versions.php" target="_blank"><strong>' . __('unsupported and old', 'matomo') . '</strong></a>.';
		$error .= __('You should update your PHP software or contact your host regarding this matter.</p>', 'matomo');

		wp_die($error, __('Plugin Activation Error', 'matomo'), array('back_link' => true));
	}

	public static function log($msg = '', $type = '') {

		if (!self::$isDev && !self::$isDebug) {
			return;
		}

		$msg = sprintf("[%s][%s] %s\n", date('Y-m-d h:i:s'), $type, $msg);
		@error_log($msg, 3, WP_CONTENT_DIR . '/matomo.log');
	}

	public static function get_manager_capability() {

		return apply_filters('matomo_manager_capability', 'manage_options');

	}

}

$GLOBALS['matomo'] = EDD_Matomo::instance();

endif;
