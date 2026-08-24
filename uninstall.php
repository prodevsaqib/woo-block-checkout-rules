<?php
/**
 * Woo Block Checkout Rules deliberately keeps rules on uninstall.
 *
 * This prevents accidental data loss when the plugin is removed temporarily.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
