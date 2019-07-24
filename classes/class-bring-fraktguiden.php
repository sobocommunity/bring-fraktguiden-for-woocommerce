<?php
/**
 * This file contains Bring_Fraktguiden class
 *
 * @package Bring_Fraktguiden\Bring_Fraktguiden
 */

/**
 * Bring_Fraktguiden class
 */
class Bring_Fraktguiden {

	const VERSION = '1.5.13';

	const TEXT_DOMAIN = Fraktguiden_Helper::TEXT_DOMAIN;

	/**
	 * Initialize the plugin
	 */
	public static function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		if ( ! class_exists( 'Packer' ) ) {
			require_once FRAKTGUIDEN_PLUGIN_PATH . 'includes/php-laff/src/Packer.php';
		}

		require_once 'class-wc-shipping-method-bring.php';
		require_once 'common/class-fraktguiden-license.php';
		require_once 'common/class-fraktguiden-admin-notices.php';
		require_once FRAKTGUIDEN_PLUGIN_PATH . 'pro/class-wc-shipping-method-bring-pro.php';

		load_plugin_textdomain( 'bring-fraktguiden', false, basename( FRAKTGUIDEN_PLUGIN_PATH ) . '/languages/' );

		add_action( 'woocommerce_shipping_init', 'Bring_Fraktguiden::shipping_init' );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'Bring_Fraktguiden::plugin_action_links' );

		if ( is_admin() ) {
			require_once FRAKTGUIDEN_PLUGIN_PATH . 'system-info-page.php';
			add_action( 'wp_ajax_bring_system_info', 'Fraktguiden_System_Info::generate' );
		}

		Fraktguiden_Minimum_Dimensions::setup();

		self::add_settings();

		// Make sure this event hasn't been scheduled.
		if ( ! wp_next_scheduled( 'bring_fraktguiden_cron' ) ) {
			// Schedule the event.
			wp_schedule_event( time(), 'daily', 'bring_fraktguiden_cron' );
		}
		add_action( 'bring_fraktguiden_cron', __CLASS__ . '::cron_task' );

		add_action( 'woocommerce_before_checkout_form', __CLASS__ . '::checkout_message' );
		add_action( 'klarna_before_kco_checkout', __CLASS__ . '::checkout_message' );

		Fraktguiden_Admin_Notices::init();

		// Check the license when PRO version is activated.
		if ( isset( $_POST['woocommerce_bring_fraktguiden_pro_enabled'] ) ) {
			$license = fraktguiden_license::get_instance();
			$license->check_license();
		}
		require_once 'common/class-postcode-validation.php';
		Bring_Fraktguiden\Postcode_Validation::setup();
	}

	/**
	 * Add plugin settings
	 */
	public static function add_settings() {
		$default = Fraktguiden_Helper::get_kco_support_default();

		if ( 'yes' === Fraktguiden_Helper::get_option( 'enable_kco_support', $default ) ) {
			require_once 'common/class-fraktguiden-kco-support.php';
			Fraktguiden_KCO_Support::setup();
		}

		if ( 'yes' === Fraktguiden_Helper::get_option( 'debug' ) ) {
			require_once 'debug/class-fraktguiden-product-debug.php';
			require_once 'debug/class-fraktguiden-order-debug.php';
			Fraktguiden_Product_Debug::setup();
			Fraktguiden_Order_Debug::setup();
		}

		if ( 'yes' !== Fraktguiden_Helper::get_option( 'disable_stylesheet' ) ) {
			add_action( 'wp_enqueue_scripts', __CLASS__ . '::enqueue_styles' );
		}
	}

	/**
	 * Enqueue styles
	 */
	public static function enqueue_styles() {
		wp_register_style( 'bring-fraktguiden', plugins_url( FRAKTGUIDEN_PLUGIN_PATH . 'assets/css/bring-fraktguiden.css' ), array(), self::VERSION );
		wp_enqueue_style( 'bring-fraktguiden' );
	}

	/**
	 * Set up a cron task for license check
	 */
	public static function cron_task() {
		$license = fraktguiden_license::get_instance();
		$license->check_license();
	}

	/**
	 * Include Bring shipping method
	 */
	public static function shipping_init() {
		// Add the method to WooCommerce.
		add_filter( 'woocommerce_shipping_methods', 'Bring_Fraktguiden::add_bring_method' );
	}

	/**
	 * Add Bring shipping method to WooCommerce
	 *
	 * @package  WooCommerce/Classes/Shipping
	 * @access public
	 * @param array $methods A list of shipping methods.
	 * @return array
	 */
	public static function add_bring_method( $methods ) {
		$methods['bring_fraktguiden'] = 'WC_Shipping_Method_Bring_Pro';
		return $methods;
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @param array $links The action links displayed for each plugin in the Plugins list table.
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . Fraktguiden_Helper::get_settings_url() . '" title="' . esc_attr( __( 'View Bring Fraktguiden Settings', 'bring-fraktguiden' ) ) . '">' . __( 'Settings', 'bring-fraktguiden' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Add action to call when the plugin is deactivated
	 */
	public static function plugin_deactivate() {
		do_action( 'bring_fraktguiden_plugin_deactivate' );
	}

	/**
	 * Display a notification that the PRO version of the plugin runs in a test mode
	 */
	public static function checkout_message() {
		if ( ! Fraktguiden_Helper::pro_test_mode() ) {
			return;
		}

		esc_html_e( 'Bring Fraktguiden PRO is in test-mode. Deactivate the test-mode to remove this message.', 'bring-fraktguiden' );
	}
}