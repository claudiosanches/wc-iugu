<?php
/**
 * Credit Card - Payment instructions.
 *
 * @author  Claudio_Sanches
 * @package Iugu_WooCommerce/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<div class="woocommerce-message">
	<span>
		<?php
			/* translators: %s: instalments */
			echo esc_html( sprintf( __( 'Payment successfully made using credit card in %s.', 'iugu-woocommerce' ), $installments . 'x' ) );
		?>
	</span>
</div>
