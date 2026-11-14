<?php
/**
 * AJAX handlers (admin-ajax.php).
 *
 * The admin-ajax endpoint suits small one-off actions in wp-admin, like the "Send a test
 * report" button on the settings page. For data the front end or blocks read,
 * prefer a REST route (see Rest.php).
 *
 * Each action is hooked as wp_ajax_<action> (logged-in users). Add
 * wp_ajax_nopriv_<action> only if visitors may call it too.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin;

defined( 'ABSPATH' ) || exit;

/**
 * AJAX handlers.
 */
class Ajax extends Api {

	/**
	 * Nonce action for the test email button (assets/js/admin.js).
	 */
	const NONCE = 'myplugin_ajax';

	/**
	 * Register AJAX actions.
	 */
	public function __construct() {
		add_action( 'wp_ajax_myplugin_send_test_report', array( $this, 'send_test_report' ) );
	}

	/**
	 * Send the daily report now, to the current user, with the saved settings.
	 *
	 * Request: action=myplugin_send_test_report, nonce.
	 * Response: { success: bool, data: { message } }.
	 *
	 * @return void
	 */
	public function send_test_report() {
		if ( ! self::verify_request( self::NONCE, 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this, or the page has expired. Reload it and try again.', 'wordpress-plugin-boilerplate' ) ), 403 );
		}

		$user = wp_get_current_user();

		if ( ! Cron::send_report( $user->user_email ) ) {
			wp_send_json_error( array( 'message' => __( 'The email could not be sent. Check that this site can send email.', 'wordpress-plugin-boilerplate' ) ), 500 );
		}

		wp_send_json_success(
			array(
				/* translators: %s: email address. */
				'message' => sprintf( __( 'Test report sent to %s.', 'wordpress-plugin-boilerplate' ), $user->user_email ),
			)
		);
	}
}
