<?php
/**
 * Bank Slip - HTML email instructions.
 *
 * @author  Claudio_Sanches
 * @package Iugu_WooCommerce/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<h2><?php esc_html_e( 'Payment', 'iugu-woocommerce' ); ?></h2>

<p class="order_details"><?php esc_html_e( 'Please use the link below to view your bank slip, you can print and pay in your internet banking or in a lottery retailer:', 'iugu-woocommerce' ); ?><br /><a class="button" href="<?php echo esc_url( $pdf ); ?>" target="_blank"><?php esc_html_e( 'Pay the bank slip', 'iugu-woocommerce' ); ?></a><br /><?php esc_html_e( 'After we receive the bank slip payment confirmation, your order will be processed.', 'iugu-woocommerce' ); ?></p>
