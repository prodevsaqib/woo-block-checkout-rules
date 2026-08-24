# Woo Block Checkout Rules

Open-source conditional rule builder for WooCommerce checkout.

## MVP features (v0.1.1)

- Declares compatibility with WooCommerce High-Performance Order Storage (HPOS)

- Add conditional fields to WooCommerce Checkout Blocks or classic shortcode checkout without code.
- Field types: text, select, checkbox.
- Field locations: contact, address, order information.
- Make fields required only when a rule matches.
- Show fields conditionally based on:
  - Product in cart
  - Applied coupon
  - Minimum cart item count
  - Minimum cart total
  - Whether the cart needs shipping
- Change the Cart Block “Proceed to Checkout” button label conditionally.
- Change the Checkout Block “Place order” button label conditionally.
- Uses WooCommerce checkout APIs instead of DOM manipulation.

## Requirements

- WordPress 6.7+
- WooCommerce 8.9+
- PHP 7.4+
- WooCommerce plugin installed and active
- Cart and Checkout Blocks are supported, as well as classic shortcode checkout.

## Install

1. Download or clone this repository.
2. Put the folder in `wp-content/plugins/woo-block-checkout-rules`.
3. Activate **Woo Block Checkout Rules**.
4. Go to **WooCommerce → Checkout Rules**.
5. Add and publish a rule.

## Example

**Rule:** Show a VAT Number field when Product #123 is in the cart.

- Action: Add a checkout field
- Field label: VAT Number
- Field type: Text
- Location: Order information
- Required: Yes
- Condition: Cart contains product
- Product: choose Product #123

## Architecture

- Rules are stored as a private WordPress custom post type (`wbcr_rule`).
- Checkout fields use `woocommerce_register_additional_checkout_field()`.
- Classic checkout fields use WooCommerce checkout filters and order hooks.
- Field visibility/required logic is compiled to WooCommerce Checkout JSON Schema conditions.
- Button text rules use `window.wc.blocksCheckout.registerCheckoutFilters()`.

## Roadmap

### v0.2
- React-based visual rule builder using WordPress components.
- AND / OR condition groups.
- Customer country/state conditions.
- Selected shipping method condition.
- Selected payment method condition.
- User role and logged-in status conditions.

### v0.3
- Checkout notices.
- Payment method enable/disable rules.
- Shipping method enable/disable rules.
- Conditional checkout validation.

### v0.4
- Conditional fees and discounts.
- Import/export rules.
- Duplicate rule.
- Rule priority and drag-and-drop ordering.

### v1.0
- Stable public rule engine API.
- Automated PHPUnit + Playwright coverage.
- WordPress.org-ready internationalization and accessibility review.

## Contributing

Issues and pull requests are welcome. Keep changes focused, backwards-compatible, and based on documented WordPress/WooCommerce APIs where possible.

## License

GPL-2.0-or-later.
