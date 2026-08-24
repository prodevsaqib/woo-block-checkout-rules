<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WBCR_Frontend {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 30 );
	}

	public function enqueue() {
		if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) {
			return;
		}

		$rules = array_merge(
			WBCR_Rules::get_published_rules( 'proceed_button' ),
			WBCR_Rules::get_published_rules( 'place_order_button' )
		);

		if ( empty( $rules ) ) {
			return;
		}

		$data = array();
		foreach ( $rules as $rule ) {
			$label = trim( (string) get_post_meta( $rule->ID, '_wbcr_button_label', true ) );
			if ( '' === $label ) {
				continue;
			}

			$type  = get_post_meta( $rule->ID, '_wbcr_condition_type', true ) ?: 'always';
			$value = get_post_meta( $rule->ID, '_wbcr_condition_value', true );

			$data[] = array(
				'id'             => $rule->ID,
				'action'         => get_post_meta( $rule->ID, '_wbcr_action', true ),
				'label'          => $label,
				'conditionType'  => $type,
				'conditionValue' => $value,
				'thresholdMinor' => 'cart_total_gte' === $type ? $this->to_minor_units( $value ) : 0,
			);
		}

		if ( empty( $data ) ) {
			return;
		}

		wp_enqueue_script(
			'wbcr-frontend',
			WBCR_URL . 'assets/frontend.js',
			array( 'wp-data' ),
			WBCR_VERSION,
			true
		);
		wp_localize_script( 'wbcr-frontend', 'wbcrConfig', array( 'rules' => $data ) );
	}

	private function to_minor_units( $value ) {
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
		return (int) round( (float) $value * ( 10 ** $decimals ) );
	}
}
