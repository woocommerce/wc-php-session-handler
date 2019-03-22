<?php
/**
 * Plugin Name: WooCommerce PHP Based Session Handler
 * Plugin URI: https://woocommerce.com/
 * Description: Replaces the core WooCommerce session handler with one which uses php sessions.
 * Version: 1.0.0
 * Author: Automattic
 * Author URI: https://woocommerce.com
 *
 * @package PHPSessionHandler
 */

namespace WC\PHPSessionHandler;

defined( 'ABSPATH' ) || exit;

use WC_Session;

/**
 * Session handler class.
 */
class SessionHandler extends WC_Session {

	/**
	 * Key for the session.
	 *
	 * @var string
	 */
	protected $session_key;

	/**
	 * Constructor for the session class.
	 */
	public function __construct() {
		$this->session_key = 'wc_session_' . get_current_blog_id();
	}

	/**
	 * Init hooks and session data.
	 */
	public function init() {
		if ( headers_sent() ) {
			headers_sent( $file, $line );
			trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				sprintf(
					'Session handler cannot start session - headers sent by %s on line $d',
					esc_html( $file ),
					esc_html( $line )
				),
				E_USER_NOTICE
			);
			return;
		}

		session_start(); // phpcs:ignore WordPress.VIP.SessionFunctionsUsage.session_session_start
		$this->_data = $_SESSION[ $this->session_key ]; // phpcs:ignore WordPress.VIP.SessionVariableUsage.SessionVarsProhibited
		
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );

		if ( ! is_user_logged_in() ) {
			add_filter( 'nonce_user_logged_out', array( $this, 'nonce_user_logged_out' ) );
		}
	}

	/**
	 * Generate a unique customer ID for guests, or return user ID if logged in.
	 *
	 * Uses Portable PHP password hashing framework to generate a unique cryptographically strong ID.
	 *
	 * @return string
	 */
	public function generate_customer_id() {
		$customer_id = '';

		if ( is_user_logged_in() ) {
			$customer_id = get_current_user_id();
		}

		if ( empty( $customer_id ) ) {
			require_once ABSPATH . 'wp-includes/class-phpass.php';
			$hasher      = new PasswordHash( 8, false );
			$customer_id = md5( $hasher->get_random_bytes( 32 ) );
		}

		return $customer_id;
	}

	/**
	 * Save data.
	 */
	public function save_data() {
		// Dirty if something changed - prevents saving nothing new.
		if ( $this->_dirty ) {
			$_SESSION[ $this->session_key ] = $this->_data; // phpcs:ignore WordPress.VIP.SessionVariableUsage.SessionVarsProhibited
			$this->_dirty                   = false;
		}
	}

	/**
	 * Destroy all session data.
	 */
	public function destroy_session() {
		unset( $_SESSION[ $this->session_key ] ); // phpcs:ignore WordPress.VIP.SessionVariableUsage.SessionVarsProhibited
		wc_empty_cart();
		$this->_data  = array();
		$this->_dirty = false;
	}

	/**
	 * When a user is logged out, ensure they have a unique nonce by using the customer/session ID.
	 *
	 * @param int $uid User ID.
	 * @return string
	 */
	public function nonce_user_logged_out( $uid ) {
		return $this->_customer_id ? $this->_customer_id : $uid;
	}
}
