<?php
/**
 * Iugu main class
 *
 * @package Iugu_WooCommerce\Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Iugu class.
 */
class WC_Iugu {

	/**
	 * Initialize the plugin actions.
	 */
	public static function init() {
		// Load plugin text domain.
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );

		// Checks with WooCommerce and WooCommerce Extra Checkout Fields for Brazil is installed.
		if ( class_exists( 'WC_Payment_Gateway' ) && class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			self::includes();

			// Hook to add Iugu Gateway to WooCommerce.
			add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( WC_IUGU_PLUGIN_FILE ), array( __CLASS__, 'plugin_action_links' ) );

			// My account actions.
			$my_account = new WC_Iugu_My_Account();
			$my_account->init();
		} else {
			add_action( 'admin_notices', array( __CLASS__, 'dependencies_notices' ) );
		}
	}

	/**
	 * Get templates path.
	 *
	 * @return string
	 */
	public static function get_templates_path() {
		return plugin_dir_path( WC_IUGU_PLUGIN_FILE ) . 'templates/';
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( 'iugu-woocommerce', false, dirname( plugin_basename( WC_IUGU_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Includes.
	 */
	private static function includes() {
		include_once dirname( __FILE__ ) . '/class-wc-iugu-api.php';
		include_once dirname( __FILE__ ) . '/gateways/class-wc-iugu-bank-slip-gateway.php';
		include_once dirname( __FILE__ ) . '/gateways/class-wc-iugu-credit-card-gateway.php';
		include_once dirname( __FILE__ ) . '/class-wc-iugu-my-account.php';

		if ( class_exists( 'WC_Subscriptions_Order' ) || class_exists( 'WC_Pre_Orders_Order' ) ) {
			include_once dirname( __FILE__ ) . '/class-wc-iugu-bank-slip-addons-gateway.php';
			include_once dirname( __FILE__ ) . '/class-wc-iugu-credit-card-addons-gateway.php';
		}
	}

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 *
	 * @return array          Payment methods with Iugu.
	 */
	public static function add_gateway( $methods ) {
		if ( class_exists( 'WC_Subscriptions_Order' ) || class_exists( 'WC_Pre_Orders_Order' ) ) {
			$methods[] = 'WC_Iugu_Credit_Card_Addons_Gateway';
			$methods[] = 'WC_Iugu_Bank_Slip_Addons_Gateway';
		} else {
			$methods[] = 'WC_Iugu_Credit_Card_Gateway';
			$methods[] = 'WC_Iugu_Bank_Slip_Gateway';
		}

		return $methods;
	}

	/**
	 * Dependencies notices.
	 */
	public static function dependencies_notices() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			include dirname( __FILE__ ) . '/includes/views/html-notice-woocommerce-missing.php';
		}

		if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			include dirname( __FILE__ ) . '/includes/views/html-notice-ecfb-missing.php';
		}
	}

	/**
	 * Action links.
	 *
	 * @param  array $links Plugin actions links.
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		$plugin_links = array();

		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' );

		$plugin_links[] = '<a href="' . esc_url( $settings_url . 'iugu-credit-card' ) . '">' . __( 'Credit Card Settings', 'iugu-woocommerce' ) . '</a>';

		$plugin_links[] = '<a href="' . esc_url( $settings_url . 'iugu-bank-slip' ) . '">' . __( 'Bank Slip Settings', 'iugu-woocommerce' ) . '</a>';

		return array_merge( $plugin_links, $links );
	}
}
