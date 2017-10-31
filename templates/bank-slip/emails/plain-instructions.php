<?php
/**
 * Bank Slip - Plain email instructions.
 *
 * @author  Claudio_Sanches
 * @package Iugu_WooCommerce/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

esc_html_e( 'Payment', 'iugu-woocommerce' );

echo "\n\n";

esc_html_e( 'Please use the link below to view your bank slip, you can print and pay in your internet banking or in a lottery retailer:', 'iugu-woocommerce' );

echo "\n";

echo esc_url( $pdf );

echo "\n";

esc_html_e( 'After we receive the bank slip payment confirmation, your order will be processed.', 'iugu-woocommerce' );

echo "\n\n****************************************************\n\n";
