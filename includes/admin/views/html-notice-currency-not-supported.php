<?php
/**
 * Admin View: Notice - Currency not supported
 *
 * @package Iugu_WooCommerce\Admin\Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$currencies       = get_woocommerce_currencies();
$current_currency = get_woocommerce_currency();
$currency         = $currencies[ $current_currency ];
?>

<div class="error inline">
	<p><strong><?php esc_html_e( 'This method is disabled', 'iugu-woocommerce' ); ?></strong>:
	<?php
		/* translators: %s: currency name */
		echo esc_html( sprintf( __( 'Currency "%s" is not supported. Works only with Brazilian Real.', 'iugu-woocommerce' ), $currency ) );
	?>
	</p>
</div>
