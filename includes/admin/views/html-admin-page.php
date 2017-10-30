<?php
/**
 * Admin options screen.
 *
 * @package Iugu_WooCommerce\Admin\Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h2><?php echo esc_html( $this->get_method_title() ); ?></h2>

<?php

if ( 'BRL' !== get_woocommerce_currency() && ! class_exists( 'woocommerce_wpml' ) ) {
	include dirname( __FILE__ ) . '/html-notice-currency-not-supported.php';
}

if ( empty( $this->account_id ) ) {
	include dirname( __FILE__ ) . '/html-notice-account-id-missing.php';
}

if ( empty( $this->api_token ) ) {
	include dirname( __FILE__ ) . '/html-notice-api-token-missing.php';
}

?>

<?php echo wp_kses_post( wpautop( $this->get_method_description() ) ); ?>

<table class="form-table">
	<?php $this->generate_settings_html(); ?>
</table>
