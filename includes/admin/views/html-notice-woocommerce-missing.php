<?php
/**
 * Admin View: Notice - Missing WooCommerce
 *
 * @package Iugu_WooCommerce\Admin\Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_installed = false;

if ( function_exists( 'get_plugins' ) ) {
	$all_plugins  = get_plugins();
	$is_installed = ! empty( $all_plugins['woocommerce/woocommerce.php'] );
}

$is_activated = is_plugin_active( 'woocommerce/woocommerce.php' );

// Stop if is on the update screen.
$current_screen = get_current_screen();
if ( in_array( $current_screen->id, array( 'update', 'update-core' ), true ) ) {
	return;
}

?>

<div class="error">
	<p><strong><?php esc_html_e( 'Iugu WooCommerce', 'woocommerce-pagseguro' ); ?></strong> <?php esc_html_e( 'depends on the WooCommerce 3.0 or later to work.', 'woocommerce-pagseguro' ); ?></p>

	<?php if ( $is_activated && current_user_can( 'update_plugins' ) ) : ?>
		<p><a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=woocommerce%2Fwoocommerce.php' ), 'upgrade-plugin_woocommerce/woocommerce.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Update WooCommerce', 'woocommerce-pagseguro' ); ?></a></p>
	<?php elseif ( $is_installed && current_user_can( 'activate_plugins' ) ) : ?>
		<p><a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=active' ), 'activate-plugin_woocommerce/woocommerce.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Active WooCommerce', 'woocommerce-pagseguro' ); ?></a></p>
	<?php else : ?>
	<?php
	if ( current_user_can( 'install_plugins' ) ) {
		$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
	} else {
		$url = 'http://wordpress.org/plugins/woocommerce/';
	}
	?>
		<p><a href="<?php echo esc_url( $url ); ?>" class="button button-primary"><?php esc_html_e( 'Install WooCommerce', 'woocommerce-pagseguro' ); ?></a></p>
	<?php endif; ?>
</div>
