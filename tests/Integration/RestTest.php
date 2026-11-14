<?php
/**
 * The public items route: who may call it, what it returns, its limits.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Tests\Integration;

use WPGenius\MyPlugin\PostTypes\Example;
use WPGenius\MyPlugin\Rest;

/**
 * REST route GET /items.
 */
class RestTest extends \WP_UnitTestCase {

	/**
	 * Three published items and one draft, and a fresh REST server.
	 */
	public function set_up() {
		parent::set_up();
		self::factory()->post->create_many( 3, array( 'post_type' => Example::NAME ) );
		self::factory()->post->create(
			array(
				'post_type'   => Example::NAME,
				'post_status' => 'draft',
				'post_title'  => 'Secret draft',
			)
		);

		$GLOBALS['wp_rest_server'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reset between tests.
		rest_get_server();
	}

	/**
	 * Drop the REST server so the next test starts clean.
	 */
	public function tear_down() {
		$GLOBALS['wp_rest_server'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reset between tests.
		parent::tear_down();
	}

	/**
	 * Call GET /items as a visitor (no login, no nonce).
	 *
	 * @param array<string, mixed> $params Query parameters.
	 * @return \WP_REST_Response
	 */
	private function get_items( array $params = array() ) {
		wp_set_current_user( 0 );
		$request = new \WP_REST_Request( 'GET', '/' . Rest::ROUTE_NAMESPACE . '/items' );
		$request->set_query_params( $params );
		return rest_get_server()->dispatch( $request );
	}

	public function test_visitors_can_read_published_items_only() {
		$response = $this->get_items();
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 3, $data['items'] );
		$this->assertNotContains( 'Secret draft', wp_list_pluck( $data['items'], 'title' ) );
		$this->assertStringNotContainsString( 'Secret draft', $data['html'] );
	}

	public function test_pages_report_whether_there_are_more() {
		$this->assertTrue( $this->get_items( array( 'per_page' => 2 ) )->get_data()['has_more'] );
		$this->assertFalse(
			$this->get_items(
				array(
					'per_page' => 2,
					'page'     => 2,
				)
			)->get_data()['has_more']
		);
	}

	public function test_asking_for_too_many_items_is_refused() {
		$response = $this->get_items( array( 'per_page' => Example::MAX_PER_PAGE + 1 ) );

		$this->assertSame( 400, $response->get_status() );
	}
}
