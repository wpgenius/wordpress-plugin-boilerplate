<?php
/**
 * The "Send a test report" AJAX action: only an administrator with a valid nonce may run it.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Tests\Integration;

use WPGenius\MyPlugin\Ajax;
use WPGenius\MyPlugin\Installer;

/**
 * AJAX permission checks.
 */
class AjaxTest extends \WP_Ajax_UnitTestCase {

	/**
	 * The action writes to the log table, so it must exist.
	 */
	public function set_up() {
		parent::set_up();
		Installer::activate();
		reset_phpmailer_instance();
	}

	/**
	 * Call the action and return its decoded JSON response.
	 *
	 * @return array{success: bool, data: array<string, string>}
	 */
	private function send_test_report() {
		try {
			$this->_handleAjax( 'myplugin_send_test_report' );
		} catch ( \WPAjaxDieContinueException $e ) {
			unset( $e ); // Expected: wp_send_json_*() ends the request.
		}
		return json_decode( $this->_last_response, true );
	}

	public function test_refused_without_a_nonce() {
		$this->_setRole( 'administrator' );

		$this->assertFalse( $this->send_test_report()['success'] );
		$this->assertFalse( tests_retrieve_phpmailer_instance()->get_sent() );
	}

	public function test_refused_for_a_user_who_may_not_manage_options() {
		$this->_setRole( 'subscriber' );
		$_POST['nonce'] = wp_create_nonce( Ajax::NONCE );

		$this->assertFalse( $this->send_test_report()['success'] );
		$this->assertFalse( tests_retrieve_phpmailer_instance()->get_sent() );
	}

	public function test_an_administrator_with_a_nonce_gets_the_email() {
		$this->_setRole( 'administrator' );
		$_POST['nonce'] = wp_create_nonce( Ajax::NONCE );

		$this->assertTrue( $this->send_test_report()['success'] );
		$this->assertSame( wp_get_current_user()->user_email, tests_retrieve_phpmailer_instance()->get_recipient( 'to' )->address );
	}
}
