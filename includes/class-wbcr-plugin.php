<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WBCR_Plugin {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		require_once WBCR_PATH . 'includes/class-wbcr-rules.php';
		require_once WBCR_PATH . 'includes/class-wbcr-fields.php';
		require_once WBCR_PATH . 'includes/class-wbcr-frontend.php';

		WBCR_Rules::instance();
		WBCR_Fields::instance();
		WBCR_Frontend::instance();
	}

	public function woocommerce_missing_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'Woo Block Checkout Rules requires WooCommerce to be installed and active.', 'woo-block-checkout-rules' );
		echo '</p></div>';
	}
}
