<?php
/**
 * Plugin Name:          Iugu WooCommerce
 * Plugin URI:           https://github.com/claudiosanches/iugu-woocommerce
 * Description:          Iugu payment gateway for WooCommerce.
 * Author:               Claudio Sanches
 * Author URI:           https://claudiosanches.com
 * Version:              2.0.0-beta.1
 * License:              GPLv3
 * Text Domain:          iugu-woocommerce
 * Domain Path:          /languages
 * WC requires at least: 3.0.0
 * WC tested up to:      3.2.0
 *
 * Copyright (C) 2017 Claudio Sanches
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Iugu_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_IUGU_VERSION', '2.0.0' );
define( 'WC_IUGU_PLUGIN_FILE', __FILE__ );

if ( ! class_exists( 'WC_Iugu' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-wc-iugu.php';

	add_action( 'plugins_loaded', array( 'WC_Iugu', 'init' ) );
}
