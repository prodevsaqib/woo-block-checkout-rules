<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WBCR_Rules {
	const POST_TYPE = 'wbcr_rule';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_rule' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Checkout Rules', 'woo-block-checkout-rules' ),
			'singular_name'      => __( 'Checkout Rule', 'woo-block-checkout-rules' ),
			'add_new'            => __( 'Add Rule', 'woo-block-checkout-rules' ),
			'add_new_item'       => __( 'Add Checkout Rule', 'woo-block-checkout-rules' ),
			'edit_item'          => __( 'Edit Checkout Rule', 'woo-block-checkout-rules' ),
			'new_item'           => __( 'New Checkout Rule', 'woo-block-checkout-rules' ),
			'view_item'          => __( 'View Checkout Rule', 'woo-block-checkout-rules' ),
			'search_items'       => __( 'Search Checkout Rules', 'woo-block-checkout-rules' ),
			'not_found'          => __( 'No checkout rules found.', 'woo-block-checkout-rules' ),
			'not_found_in_trash' => __( 'No checkout rules found in Trash.', 'woo-block-checkout-rules' ),
			'menu_name'          => __( 'Checkout Rules', 'woo-block-checkout-rules' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'woocommerce',
				'show_in_rest'        => false,
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-filter',
			)
		);
	}

	public function add_meta_boxes() {
		add_meta_box(
			'wbcr-rule-builder',
			__( 'Rule Builder', 'woo-block-checkout-rules' ),
			array( $this, 'render_rule_builder' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'wbcr-help',
			__( 'How this rule works', 'woo-block-checkout-rules' ),
			array( $this, 'render_help' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	public function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'wbcr-admin', WBCR_URL . 'assets/admin.css', array(), WBCR_VERSION );
		wp_enqueue_script( 'wbcr-admin', WBCR_URL . 'assets/admin.js', array( 'jquery', 'wc-enhanced-select' ), WBCR_VERSION, true );
	}

	public function render_rule_builder( $post ) {
		wp_nonce_field( 'wbcr_save_rule', 'wbcr_rule_nonce' );

		$action          = get_post_meta( $post->ID, '_wbcr_action', true ) ?: 'checkout_field';
		$condition_type  = get_post_meta( $post->ID, '_wbcr_condition_type', true ) ?: 'always';
		$condition_value = get_post_meta( $post->ID, '_wbcr_condition_value', true );
		$field_key       = get_post_meta( $post->ID, '_wbcr_field_key', true );
		$field_label     = get_post_meta( $post->ID, '_wbcr_field_label', true );
		$optional_label  = get_post_meta( $post->ID, '_wbcr_field_optional_label', true );
		$field_type      = get_post_meta( $post->ID, '_wbcr_field_type', true ) ?: 'text';
		$field_location  = get_post_meta( $post->ID, '_wbcr_field_location', true ) ?: 'order';
		$field_required  = 'yes' === get_post_meta( $post->ID, '_wbcr_field_required', true );
		$field_options   = get_post_meta( $post->ID, '_wbcr_field_options', true );
		$button_label    = get_post_meta( $post->ID, '_wbcr_button_label', true );
		?>
		<div class="wbcr-builder">
			<div class="wbcr-card">
				<h3><?php esc_html_e( '1. Choose an action', 'woo-block-checkout-rules' ); ?></h3>
				<select name="wbcr_action" id="wbcr_action" class="widefat">
					<option value="checkout_field" <?php selected( $action, 'checkout_field' ); ?>><?php esc_html_e( 'Add a checkout field', 'woo-block-checkout-rules' ); ?></option>
					<option value="proceed_button" <?php selected( $action, 'proceed_button' ); ?>><?php esc_html_e( 'Change “Proceed to Checkout” button text', 'woo-block-checkout-rules' ); ?></option>
					<option value="place_order_button" <?php selected( $action, 'place_order_button' ); ?>><?php esc_html_e( 'Change “Place order” button text', 'woo-block-checkout-rules' ); ?></option>
				</select>
			</div>

			<div class="wbcr-card wbcr-action-section" data-action="checkout_field">
				<h3><?php esc_html_e( '2. Configure the checkout field', 'woo-block-checkout-rules' ); ?></h3>
				<div class="wbcr-grid">
					<label>
						<span><?php esc_html_e( 'Field label', 'woo-block-checkout-rules' ); ?></span>
						<input type="text" name="wbcr_field_label" value="<?php echo esc_attr( $field_label ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Delivery instructions', 'woo-block-checkout-rules' ); ?>">
					</label>
					<label>
						<span><?php esc_html_e( 'Optional label', 'woo-block-checkout-rules' ); ?></span>
						<input type="text" name="wbcr_field_optional_label" value="<?php echo esc_attr( $optional_label ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Optional custom label', 'woo-block-checkout-rules' ); ?>">
					</label>
					<label>
						<span><?php esc_html_e( 'Field key', 'woo-block-checkout-rules' ); ?></span>
						<input type="text" name="wbcr_field_key" value="<?php echo esc_attr( $field_key ); ?>" class="widefat" placeholder="delivery-instructions">
						<small><?php esc_html_e( 'Stable machine-readable key. Leave empty to generate one.', 'woo-block-checkout-rules' ); ?></small>
					</label>
					<label>
						<span><?php esc_html_e( 'Field type', 'woo-block-checkout-rules' ); ?></span>
						<select name="wbcr_field_type" id="wbcr_field_type" class="widefat">
							<option value="text" <?php selected( $field_type, 'text' ); ?>><?php esc_html_e( 'Text', 'woo-block-checkout-rules' ); ?></option>
							<option value="select" <?php selected( $field_type, 'select' ); ?>><?php esc_html_e( 'Select', 'woo-block-checkout-rules' ); ?></option>
							<option value="checkbox" <?php selected( $field_type, 'checkbox' ); ?>><?php esc_html_e( 'Checkbox', 'woo-block-checkout-rules' ); ?></option>
						</select>
					</label>
					<label>
						<span><?php esc_html_e( 'Location', 'woo-block-checkout-rules' ); ?></span>
						<select name="wbcr_field_location" class="widefat">
							<option value="contact" <?php selected( $field_location, 'contact' ); ?>><?php esc_html_e( 'Contact information', 'woo-block-checkout-rules' ); ?></option>
							<option value="address" <?php selected( $field_location, 'address' ); ?>><?php esc_html_e( 'Billing & shipping address', 'woo-block-checkout-rules' ); ?></option>
							<option value="order" <?php selected( $field_location, 'order' ); ?>><?php esc_html_e( 'Order information', 'woo-block-checkout-rules' ); ?></option>
						</select>
					</label>
					<label class="wbcr-checkbox-label">
						<input type="checkbox" name="wbcr_field_required" value="yes" <?php checked( $field_required ); ?>>
						<span><?php esc_html_e( 'Required when this rule matches', 'woo-block-checkout-rules' ); ?></span>
					</label>
				</div>

				<label class="wbcr-select-options" data-field-type="select">
					<span><?php esc_html_e( 'Select options', 'woo-block-checkout-rules' ); ?></span>
					<textarea name="wbcr_field_options" rows="5" class="widefat" placeholder="standard|Standard delivery&#10;express|Express delivery"><?php echo esc_textarea( $field_options ); ?></textarea>
					<small><?php esc_html_e( 'One option per line. Use value|Label or just Label.', 'woo-block-checkout-rules' ); ?></small>
				</label>
			</div>

			<div class="wbcr-card wbcr-action-section" data-action="proceed_button place_order_button">
				<h3><?php esc_html_e( '2. Configure the button', 'woo-block-checkout-rules' ); ?></h3>
				<label>
					<span><?php esc_html_e( 'New button text', 'woo-block-checkout-rules' ); ?></span>
					<input type="text" name="wbcr_button_label" value="<?php echo esc_attr( $button_label ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Continue securely', 'woo-block-checkout-rules' ); ?>">
				</label>
			</div>

			<div class="wbcr-card">
				<h3><?php esc_html_e( '3. Set the condition', 'woo-block-checkout-rules' ); ?></h3>
				<div class="wbcr-condition-row">
					<select name="wbcr_condition_type" id="wbcr_condition_type">
						<option value="always" <?php selected( $condition_type, 'always' ); ?>><?php esc_html_e( 'Always', 'woo-block-checkout-rules' ); ?></option>
						<option value="product" <?php selected( $condition_type, 'product' ); ?>><?php esc_html_e( 'Cart contains product', 'woo-block-checkout-rules' ); ?></option>
						<option value="coupon" <?php selected( $condition_type, 'coupon' ); ?>><?php esc_html_e( 'Coupon is applied', 'woo-block-checkout-rules' ); ?></option>
						<option value="item_count_gte" <?php selected( $condition_type, 'item_count_gte' ); ?>><?php esc_html_e( 'Cart item count is at least', 'woo-block-checkout-rules' ); ?></option>
						<option value="cart_total_gte" <?php selected( $condition_type, 'cart_total_gte' ); ?>><?php esc_html_e( 'Cart total is at least', 'woo-block-checkout-rules' ); ?></option>
						<option value="needs_shipping" <?php selected( $condition_type, 'needs_shipping' ); ?>><?php esc_html_e( 'Cart needs shipping', 'woo-block-checkout-rules' ); ?></option>
					</select>

					<div class="wbcr-condition-value" data-condition="product">
						<?php $this->render_product_search( $condition_type === 'product' ? $condition_value : '' ); ?>
					</div>
					<div class="wbcr-condition-value" data-condition="coupon">
						<input type="text" name="wbcr_condition_coupon" value="<?php echo esc_attr( $condition_type === 'coupon' ? $condition_value : '' ); ?>" placeholder="WELCOME10">
					</div>
					<div class="wbcr-condition-value" data-condition="item_count_gte">
						<input type="number" min="1" step="1" name="wbcr_condition_item_count" value="<?php echo esc_attr( $condition_type === 'item_count_gte' ? $condition_value : '' ); ?>" placeholder="3">
					</div>
					<div class="wbcr-condition-value" data-condition="cart_total_gte">
						<input type="number" min="0" step="0.01" name="wbcr_condition_cart_total" value="<?php echo esc_attr( $condition_type === 'cart_total_gte' ? $condition_value : '' ); ?>" placeholder="100.00">
					</div>
					<div class="wbcr-condition-value" data-condition="needs_shipping">
						<select name="wbcr_condition_needs_shipping">
							<option value="yes" <?php selected( $condition_type === 'needs_shipping' ? $condition_value : 'yes', 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-block-checkout-rules' ); ?></option>
							<option value="no" <?php selected( $condition_type === 'needs_shipping' ? $condition_value : '', 'no' ); ?>><?php esc_html_e( 'No', 'woo-block-checkout-rules' ); ?></option>
						</select>
					</div>
				</div>
				<p class="description"><?php esc_html_e( 'Checkout fields are hidden until the selected condition matches. Button rules fall back to the normal WooCommerce text when the condition does not match.', 'woo-block-checkout-rules' ); ?></p>
			</div>
		</div>
		<?php
	}

	private function render_product_search( $product_id ) {
		$product = $product_id ? wc_get_product( absint( $product_id ) ) : false;
		?>
		<select
			class="wc-product-search"
			style="width: 320px;"
			name="wbcr_condition_product"
			data-placeholder="<?php esc_attr_e( 'Search for a product…', 'woo-block-checkout-rules' ); ?>"
			data-action="woocommerce_json_search_products_and_variations"
			data-allow_clear="true"
		>
			<?php if ( $product ) : ?>
				<option value="<?php echo esc_attr( $product->get_id() ); ?>" selected><?php echo wp_kses_post( $product->get_formatted_name() ); ?></option>
			<?php endif; ?>
		</select>
		<?php
	}

	public function render_help() {
		?>
		<p><?php esc_html_e( 'Publish the rule to activate it. Draft rules do nothing on the storefront.', 'woo-block-checkout-rules' ); ?></p>
		<p><?php esc_html_e( 'Checkout fields work with WooCommerce Cart and Checkout Blocks and classic shortcode checkout.', 'woo-block-checkout-rules' ); ?></p>
		<p><strong><?php esc_html_e( 'Tip:', 'woo-block-checkout-rules' ); ?></strong> <?php esc_html_e( 'Use a clear rule title such as “Show VAT Number for Wholesale Product”.', 'woo-block-checkout-rules' ); ?></p>
		<?php
	}

	public function save_rule( $post_id, $post ) {
		if ( ! isset( $_POST['wbcr_rule_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wbcr_rule_nonce'] ) ), 'wbcr_save_rule' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$allowed_actions = array( 'checkout_field', 'proceed_button', 'place_order_button' );
		$action          = isset( $_POST['wbcr_action'] ) ? sanitize_key( wp_unslash( $_POST['wbcr_action'] ) ) : 'checkout_field';
		if ( ! in_array( $action, $allowed_actions, true ) ) {
			$action = 'checkout_field';
		}

		$allowed_conditions = array( 'always', 'product', 'coupon', 'item_count_gte', 'cart_total_gte', 'needs_shipping' );
		$condition_type     = isset( $_POST['wbcr_condition_type'] ) ? sanitize_key( wp_unslash( $_POST['wbcr_condition_type'] ) ) : 'always';
		if ( ! in_array( $condition_type, $allowed_conditions, true ) ) {
			$condition_type = 'always';
		}

		$condition_value = $this->condition_value_from_request( $condition_type );

		$field_key = isset( $_POST['wbcr_field_key'] ) ? sanitize_key( wp_unslash( $_POST['wbcr_field_key'] ) ) : '';
		if ( '' === $field_key ) {
			$field_key = 'rule-' . $post_id;
		}

		$field_type = isset( $_POST['wbcr_field_type'] ) ? sanitize_key( wp_unslash( $_POST['wbcr_field_type'] ) ) : 'text';
		if ( ! in_array( $field_type, array( 'text', 'select', 'checkbox' ), true ) ) {
			$field_type = 'text';
		}

		$field_location = isset( $_POST['wbcr_field_location'] ) ? sanitize_key( wp_unslash( $_POST['wbcr_field_location'] ) ) : 'order';
		if ( ! in_array( $field_location, array( 'contact', 'address', 'order' ), true ) ) {
			$field_location = 'order';
		}

		$meta = array(
			'_wbcr_action'               => $action,
			'_wbcr_condition_type'       => $condition_type,
			'_wbcr_condition_value'      => $condition_value,
			'_wbcr_field_key'            => $field_key,
			'_wbcr_field_label'          => isset( $_POST['wbcr_field_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wbcr_field_label'] ) ) : '',
			'_wbcr_field_optional_label' => isset( $_POST['wbcr_field_optional_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wbcr_field_optional_label'] ) ) : '',
			'_wbcr_field_type'           => $field_type,
			'_wbcr_field_location'       => $field_location,
			'_wbcr_field_required'       => isset( $_POST['wbcr_field_required'] ) ? 'yes' : 'no',
			'_wbcr_field_options'        => isset( $_POST['wbcr_field_options'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wbcr_field_options'] ) ) : '',
			'_wbcr_button_label'         => isset( $_POST['wbcr_button_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wbcr_button_label'] ) ) : '',
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}

	private function condition_value_from_request( $type ) {
		switch ( $type ) {
			case 'product':
				return isset( $_POST['wbcr_condition_product'] ) ? absint( $_POST['wbcr_condition_product'] ) : 0;
			case 'coupon':
				return isset( $_POST['wbcr_condition_coupon'] ) ? wc_format_coupon_code( wp_unslash( $_POST['wbcr_condition_coupon'] ) ) : '';
			case 'item_count_gte':
				return isset( $_POST['wbcr_condition_item_count'] ) ? max( 1, absint( $_POST['wbcr_condition_item_count'] ) ) : 1;
			case 'cart_total_gte':
				return isset( $_POST['wbcr_condition_cart_total'] ) ? wc_format_decimal( wp_unslash( $_POST['wbcr_condition_cart_total'] ) ) : '0';
			case 'needs_shipping':
				return ( isset( $_POST['wbcr_condition_needs_shipping'] ) && 'no' === sanitize_key( wp_unslash( $_POST['wbcr_condition_needs_shipping'] ) ) ) ? 'no' : 'yes';
			default:
				return '';
		}
	}

	public function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['wbcr_action']    = __( 'Action', 'woo-block-checkout-rules' );
				$new['wbcr_condition'] = __( 'Condition', 'woo-block-checkout-rules' );
			}
		}
		return $new;
	}

	public function column_content( $column, $post_id ) {
		if ( 'wbcr_action' === $column ) {
			$action = get_post_meta( $post_id, '_wbcr_action', true );
			$labels = array(
				'checkout_field'     => __( 'Checkout field', 'woo-block-checkout-rules' ),
				'proceed_button'     => __( 'Proceed button text', 'woo-block-checkout-rules' ),
				'place_order_button' => __( 'Place order button text', 'woo-block-checkout-rules' ),
			);
			echo esc_html( isset( $labels[ $action ] ) ? $labels[ $action ] : $action );
		}

		if ( 'wbcr_condition' === $column ) {
			$type  = get_post_meta( $post_id, '_wbcr_condition_type', true );
			$value = get_post_meta( $post_id, '_wbcr_condition_value', true );
			echo esc_html( $this->human_condition( $type, $value ) );
		}
	}

	private function human_condition( $type, $value ) {
		switch ( $type ) {
			case 'product':
				$product = wc_get_product( absint( $value ) );
				return $product ? sprintf( __( 'Contains %s', 'woo-block-checkout-rules' ), $product->get_name() ) : __( 'Contains selected product', 'woo-block-checkout-rules' );
			case 'coupon':
				return sprintf( __( 'Coupon: %s', 'woo-block-checkout-rules' ), $value );
			case 'item_count_gte':
				return sprintf( __( 'Items ≥ %s', 'woo-block-checkout-rules' ), $value );
			case 'cart_total_gte':
				return sprintf( __( 'Total ≥ %s', 'woo-block-checkout-rules' ), wp_strip_all_tags( wc_price( $value ) ) );
			case 'needs_shipping':
				return 'yes' === $value ? __( 'Needs shipping', 'woo-block-checkout-rules' ) : __( 'Does not need shipping', 'woo-block-checkout-rules' );
			default:
				return __( 'Always', 'woo-block-checkout-rules' );
		}
	}

	public function updated_messages( $messages ) {
		if ( isset( $messages[ self::POST_TYPE ] ) ) {
			return $messages;
		}
		$messages[ self::POST_TYPE ] = array_fill( 0, 11, __( 'Checkout rule updated.', 'woo-block-checkout-rules' ) );
		$messages[ self::POST_TYPE ][6] = __( 'Checkout rule published.', 'woo-block-checkout-rules' );
		return $messages;
	}

	public static function get_published_rules( $action = '' ) {
		$meta_query = array();
		if ( $action ) {
			$meta_query[] = array(
				'key'   => '_wbcr_action',
				'value' => $action,
			);
		}

		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
				'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);
	}
}
