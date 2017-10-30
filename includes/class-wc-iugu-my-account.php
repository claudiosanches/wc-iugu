<?php
/**
 * My account actions
 *
 * @package Iugu_WooCommerce\Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * My account class.
 */
class WC_Iugu_My_Account {

	/**
	 * Initialize my account actions.
	 */
	public function init() {
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'my_orders_bank_slip_link' ), 10, 2 );
	}

	/**
	 * Add bank slip link/button in My Orders section on My Accout page.
	 *
	 * @param  array    $actions Order actions.
	 * @param  WC_Order $order   Order instance.
	 * @return array
	 */
	public function my_orders_bank_slip_link( $actions, $order ) {
		if ( 'iugu-bank-slip' !== $order->get_payment_method() ) {
			return $actions;
		}

		if ( ! $order->has_status( array( 'pending', 'on-hold' ) ) ) {
			return $actions;
		}

		$data = $order->get_meta( '_iugu_wc_transaction_data' );
		if ( ! empty( $data['pdf'] ) ) {
			$actions[] = array(
				'url'  => $data['pdf'],
				'name' => __( 'Pay the bank slip', 'iugu-woocommerce' ),
			);
		}

		return $actions;
	}
}
