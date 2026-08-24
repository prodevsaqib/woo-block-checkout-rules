<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WBCR_Fields {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'woocommerce_init', array( $this, 'register_fields' ), 20 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_classic_checkout_fields' ), 9999 );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_classic_checkout_fields' ), 9999 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_classic_checkout_fields' ), 9999, 2 );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'display_classic_order_fields' ), 9999 );
	}

	public function register_fields() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		$rules = WBCR_Rules::get_published_rules( 'checkout_field' );
		foreach ( $rules as $rule ) {
			$this->register_rule_field( $rule );
		}
	}

	public function add_classic_checkout_fields( $fields ) {
		foreach ( WBCR_Rules::get_published_rules( 'checkout_field' ) as $rule ) {
			if ( ! $this->rule_matches_cart( $rule->ID ) ) {
				continue;
			}

			$field = $this->classic_field_args( $rule );
			if ( $field ) {
				$section = $field['section'];
				if ( isset( $fields[ $section ] ) ) {
					$fields[ $section ][ $field['key'] ] = $field['args'];
				}
			}
		}

		return $fields;
	}

	public function validate_classic_checkout_fields() {
		foreach ( WBCR_Rules::get_published_rules( 'checkout_field' ) as $rule ) {
			if ( ! $this->rule_matches_cart( $rule->ID ) ) {
				continue;
			}

			$field = $this->classic_field_args( $rule );
			if ( ! $field || empty( $field['args']['required'] ) ) {
				continue;
			}

			$value = isset( $_POST[ $field['key'] ] ) ? wc_clean( wp_unslash( $_POST[ $field['key'] ] ) ) : '';
			if ( '' === $value || array() === $value ) {
				wc_add_notice(
					sprintf(
						/* translators: %s field label. */
						__( '%s is a required field.', 'woo-block-checkout-rules' ),
						$field['args']['label']
					),
					'error'
				);
			}
		}
	}

	public function save_classic_checkout_fields( $order ) {
		foreach ( WBCR_Rules::get_published_rules( 'checkout_field' ) as $rule ) {
			if ( ! $this->rule_matches_cart( $rule->ID ) ) {
				continue;
			}

			$field = $this->classic_field_args( $rule );
			if ( ! $field || ! isset( $_POST[ $field['key'] ] ) ) {
				continue;
			}

			$value = wc_clean( wp_unslash( $_POST[ $field['key'] ] ) );
			$order->update_meta_data( '_wbcr_' . $field['name'], is_array( $value ) ? implode( ', ', $value ) : $value );
		}
	}

	public function display_classic_order_fields( $order ) {
		$values = array();

		foreach ( WBCR_Rules::get_published_rules( 'checkout_field' ) as $rule ) {
			$field = $this->classic_field_args( $rule );
			if ( ! $field ) {
				continue;
			}

			$value = $order->get_meta( '_wbcr_' . $field['name'], true );
			if ( '' !== $value && null !== $value ) {
				$values[ $field['args']['label'] ] = $value;
			}
		}

		if ( empty( $values ) ) {
			return;
		}
		?>
		<div class="wbcr-order-fields">
			<h3><?php esc_html_e( 'Checkout rule fields', 'woo-block-checkout-rules' ); ?></h3>
			<table class="widefat striped">
				<tbody>
					<?php foreach ( $values as $label => $value ) : ?>
						<tr>
							<th><?php echo esc_html( $label ); ?></th>
							<td><?php echo esc_html( is_scalar( $value ) ? (string) $value : implode( ', ', (array) $value ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function classic_field_args( $rule ) {
		$field_key = sanitize_key( get_post_meta( $rule->ID, '_wbcr_field_key', true ) ?: 'rule-' . $rule->ID );
		$label     = trim( (string) get_post_meta( $rule->ID, '_wbcr_field_label', true ) );
		$type      = get_post_meta( $rule->ID, '_wbcr_field_type', true ) ?: 'text';

		if ( '' === $label ) {
			return null;
		}

		$name = 'wbcr_' . $field_key;
		$location = get_post_meta( $rule->ID, '_wbcr_field_location', true ) ?: 'order';
		$section  = 'order' === $location ? 'order' : 'billing';
		$args = array(
			'type'     => in_array( $type, array( 'text', 'select', 'checkbox' ), true ) ? $type : 'text',
			'label'    => $label,
			'required' => 'yes' === get_post_meta( $rule->ID, '_wbcr_field_required', true ),
			'priority' => 1,
		);

		$optional_label = trim( (string) get_post_meta( $rule->ID, '_wbcr_field_optional_label', true ) );
		if ( '' !== $optional_label ) {
			$args['label'] .= ' ' . $optional_label;
		}

		if ( 'select' === $type ) {
			$args['options'] = array( '' => __( 'Select an option', 'woo-block-checkout-rules' ) );
			foreach ( $this->parse_options( get_post_meta( $rule->ID, '_wbcr_field_options', true ) ) as $option ) {
				$args['options'][ $option['value'] ] = $option['label'];
			}
		}

		return array(
			'key'     => $name,
			'name'    => $field_key,
			'section' => $section,
			'args'    => $args,
		);
	}

	private function rule_matches_cart( $rule_id ) {
		$type  = get_post_meta( $rule_id, '_wbcr_condition_type', true ) ?: 'always';
		$value = get_post_meta( $rule_id, '_wbcr_condition_value', true );

		if ( 'always' === $type ) {
			return true;
		}
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		$cart = WC()->cart;
		switch ( $type ) {
			case 'product':
				foreach ( $cart->get_cart() as $item ) {
					if ( absint( $item['product_id'] ) === absint( $value ) || absint( $item['variation_id'] ) === absint( $value ) ) {
						return true;
					}
				}
				return false;
			case 'coupon':
				return in_array( wc_format_coupon_code( $value ), array_map( 'wc_format_coupon_code', $cart->get_applied_coupons() ), true );
			case 'item_count_gte':
				return $cart->get_cart_contents_count() >= absint( $value );
			case 'cart_total_gte':
				return (float) $cart->get_total( 'edit' ) >= (float) $value;
			case 'needs_shipping':
				return $cart->needs_shipping() === ( 'yes' === $value );
		}

		return false;
	}

	private function register_rule_field( $rule ) {
		$field_key      = sanitize_key( get_post_meta( $rule->ID, '_wbcr_field_key', true ) ?: 'rule-' . $rule->ID );
		$label          = get_post_meta( $rule->ID, '_wbcr_field_label', true );
		$optional_label = get_post_meta( $rule->ID, '_wbcr_field_optional_label', true );
		$type           = get_post_meta( $rule->ID, '_wbcr_field_type', true ) ?: 'text';
		$location       = get_post_meta( $rule->ID, '_wbcr_field_location', true ) ?: 'order';
		$required       = 'yes' === get_post_meta( $rule->ID, '_wbcr_field_required', true );
		$condition_type = get_post_meta( $rule->ID, '_wbcr_condition_type', true ) ?: 'always';
		$condition      = $this->compile_condition_schema( $rule->ID );

		if ( '' === trim( $label ) ) {
			return;
		}

		$args = array(
			'id'       => 'woo-block-checkout-rules/' . $field_key,
			'label'    => $label,
			'location' => $location,
			'type'     => in_array( $type, array( 'text', 'select', 'checkbox' ), true ) ? $type : 'text',
		);

		if ( '' !== trim( $optional_label ) ) {
			$args['optionalLabel'] = $optional_label;
		}

		if ( 'always' === $condition_type ) {
			$args['required'] = $required;
		} elseif ( $condition ) {
			$args['hidden']   = array(
				'not' => $condition,
			);
			$args['required'] = $required ? $condition : false;
		}

		if ( 'select' === $type ) {
			$options = $this->parse_options( get_post_meta( $rule->ID, '_wbcr_field_options', true ) );
			if ( empty( $options ) ) {
				return;
			}
			$args['options'] = $options;
		}

		if ( 'checkbox' === $type && $required ) {
			$args['error_message'] = sprintf(
				/* translators: %s field label. */
				__( 'Please confirm “%s” before placing the order.', 'woo-block-checkout-rules' ),
				$label
			);
		}

		try {
			woocommerce_register_additional_checkout_field( $args );
		} catch ( Throwable $e ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error(
					$e->getMessage(),
					array( 'source' => 'woo-block-checkout-rules' )
				);
			}
		}
	}

	private function compile_condition_schema( $rule_id ) {
		$type  = get_post_meta( $rule_id, '_wbcr_condition_type', true ) ?: 'always';
		$value = get_post_meta( $rule_id, '_wbcr_condition_value', true );

		switch ( $type ) {
			case 'product':
				return $this->cart_property_schema(
					'items',
					array(
						'type'     => 'array',
						'contains' => array( 'const' => absint( $value ) ),
					)
				);

			case 'coupon':
				return $this->cart_property_schema(
					'coupons',
					array(
						'type'     => 'array',
						'contains' => array( 'const' => wc_format_coupon_code( $value ) ),
					)
				);

			case 'item_count_gte':
				return $this->cart_property_schema(
					'items_count',
					array(
						'type'    => 'integer',
						'minimum' => absint( $value ),
					)
				);

			case 'cart_total_gte':
				$minor = $this->to_minor_units( $value );
				return array(
					'type'       => 'object',
					'properties' => array(
						'cart' => array(
							'type'       => 'object',
							'properties' => array(
								'totals' => array(
									'type'       => 'object',
									'properties' => array(
										'total_price' => array(
											'type'    => 'integer',
											'minimum' => $minor,
										),
									),
									'required'   => array( 'total_price' ),
								),
							),
							'required'   => array( 'totals' ),
						),
					),
					'required'   => array( 'cart' ),
				);

			case 'needs_shipping':
				return $this->cart_property_schema(
					'needs_shipping',
					array(
						'type'  => 'boolean',
						'const' => 'yes' === $value,
					)
				);
		}

		return null;
	}

	private function cart_property_schema( $property, $property_schema ) {
		return array(
			'type'       => 'object',
			'properties' => array(
				'cart' => array(
					'type'       => 'object',
					'properties' => array(
						$property => $property_schema,
					),
					'required'   => array( $property ),
				),
			),
			'required'   => array( 'cart' ),
		);
	}

	private function to_minor_units( $value ) {
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
		return (int) round( (float) $value * ( 10 ** $decimals ) );
	}

	private function parse_options( $raw ) {
		$lines   = preg_split( '/\r\n|\r|\n/', (string) $raw );
		$options = array();
		$seen    = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( 2 === count( $parts ) ) {
				$value = sanitize_title( $parts[0] );
				$label = sanitize_text_field( $parts[1] );
			} else {
				$label = sanitize_text_field( $parts[0] );
				$value = sanitize_title( $label );
			}

			if ( '' === $value || '' === $label || isset( $seen[ $value ] ) ) {
				continue;
			}

			$seen[ $value ] = true;
			$options[]      = array(
				'value' => $value,
				'label' => $label,
			);
		}

		return $options;
	}
}
