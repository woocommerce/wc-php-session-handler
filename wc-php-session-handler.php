<?php
/**
 * Plugin Name: WooCommerce PHP Session Handler
 * Plugin URI: https://woocommerce.com/
 * Description: Replaces the core WooCommerce session handler with one which uses php sessions.
 * Version: 1.0.0
 * Author: Automattic
 * Author URI: https://woocommerce.com
 *
 * @package PHPSessionHandler
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_session_handler', function( $handler ) {
	if ( class_exists( 'WC_Session' ) ) {
		include __DIR__ . '/src/class-phpsessionhandler.php';
		$handler = 'WC\PHPSessionHandler\SessionHandler';
	}
	return $handler;
} );