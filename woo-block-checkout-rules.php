<?php
/**
 * Plugin Name:       Woo Block Checkout Rules
 * Plugin URI:        https://github.com/your-org/woo-block-checkout-rules
 * Description:       Build conditional rules for WooCommerce Cart and Checkout Blocks without editing code.
 * Version:           0.1.1
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Open Source Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-block-checkout-rules
 * Domain Path:       /languages
 * WC requires at least: 8.9
 * WC tested up to:   11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WBCR_VERSION', '0.1.1' );
define( 'WBCR_FILE', __FILE__ );
define( 'WBCR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WBCR_URL', plugin_dir_url( __FILE__ ) );

require_once WBCR_PATH . 'includes/class-wbcr-plugin.php';

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			// WooCommerce Cart and Checkout Blocks compatibility.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				WBCR_FILE,
				true
			);

			// High-Performance Order Storage (HPOS) compatibility.
			// This plugin does not read or write order data directly from wp_posts/wp_postmeta.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				WBCR_FILE,
				true
			);
		}
	}
);


add_action( 'plugins_loaded', array( 'WBCR_Plugin', 'instance' ) );
