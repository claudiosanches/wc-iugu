<?php
/**
 * Credit Card - HTML email instructions.
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

<p class="order_details">
	<?php
		/* translators: %s: instalments */
		echo esc_html( sprintf( __( 'Payment successfully made using credit card in %s.', 'iugu-woocommerce' ), $installments . 'x' ) );
	?>
</p>
