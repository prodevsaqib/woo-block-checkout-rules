(function () {
	'use strict';

	var config = window.wbcrConfig || {};
	var rules = Array.isArray(config.rules) ? config.rules : [];

	if (!rules.length) {
		return;
	}

	function currentCart() {
		try {
			if (!window.wp || !window.wp.data || !window.wc || !window.wc.wcBlocksData) {
				return null;
			}
			var key = window.wc.wcBlocksData.cartStore || window.wc.wcBlocksData.CART_STORE_KEY || 'wc/store/cart';
			var store = window.wp.data.select(key);
			return store && typeof store.getCartData === 'function' ? store.getCartData() : null;
		} catch (e) {
			return null;
		}
	}

	function numeric(value) {
		var parsed = Number(value);
		return Number.isFinite(parsed) ? parsed : 0;
	}

	function cartItems(cart) {
		return cart && Array.isArray(cart.items) ? cart.items : [];
	}

	function couponCodes(cart) {
		if (!cart || !Array.isArray(cart.coupons)) {
			return [];
		}
		return cart.coupons.map(function (coupon) {
			if (typeof coupon === 'string') {
				return coupon.toLowerCase();
			}
			return coupon && coupon.code ? String(coupon.code).toLowerCase() : '';
		});
	}

	function matches(rule, cart) {
		if (!rule || rule.conditionType === 'always') {
			return true;
		}
		if (!cart) {
			return false;
		}

		switch (rule.conditionType) {
			case 'product':
				var wanted = numeric(rule.conditionValue);
				return cartItems(cart).some(function (item) {
					return numeric(item && item.id) === wanted || numeric(item && item.variation_id) === wanted;
				});
			case 'coupon':
				return couponCodes(cart).indexOf(String(rule.conditionValue || '').toLowerCase()) !== -1;
			case 'item_count_gte':
				var count = typeof cart.itemsCount !== 'undefined'
					? numeric(cart.itemsCount)
					: (typeof cart.items_count !== 'undefined' ? numeric(cart.items_count)
					: cartItems(cart).reduce(function (sum, item) { return sum + numeric(item && item.quantity); }, 0));
				return count >= numeric(rule.conditionValue);
			case 'cart_total_gte':
				return numeric(cart.totals && cart.totals.total_price) >= numeric(rule.thresholdMinor);
			case 'needs_shipping':
				var needsShipping = typeof cart.needsShipping !== 'undefined' ? cart.needsShipping : cart.needs_shipping;
				return Boolean(needsShipping) === (String(rule.conditionValue) === 'yes');
			default:
				return false;
		}
	}

	function firstMatchingLabel(action, cart, fallback) {
		for (var i = 0; i < rules.length; i++) {
			if (rules[i].action === action && matches(rules[i], cart)) {
				return rules[i].label || fallback;
			}
		}
		return fallback;
	}

	function register() {
		if (!window.wc || !window.wc.blocksCheckout || typeof window.wc.blocksCheckout.registerCheckoutFilters !== 'function') {
			return false;
		}

		window.wc.blocksCheckout.registerCheckoutFilters('woo-block-checkout-rules', {
			proceedToCheckoutButtonLabel: function (defaultValue, extensions, args) {
				return firstMatchingLabel('proceed_button', args && args.cart ? args.cart : currentCart(), defaultValue);
			},
			placeOrderButtonLabel: function (defaultValue) {
				return firstMatchingLabel('place_order_button', currentCart(), defaultValue);
			}
		});
		return true;
	}

	if (!register()) {
		var attempts = 0;
		var timer = window.setInterval(function () {
			attempts += 1;
			if (register() || attempts >= 20) {
				window.clearInterval(timer);
			}
		}, 250);
	}
})();
