<?php
/**
 * REST API routes (/wp-json/myplugin/v1/...).
 *
 * Use REST for data the front end, blocks or other apps read or change:
 * routes are discoverable, arguments are validated by WordPress, every route
 * declares who may call it (permission_callback), and blocks call it with
 * wp.apiFetch. admin-ajax (see Ajax.php) still suits small one-off admin actions.
 *
 *   GET /wp-json/myplugin/v1/items?page=2&per_page=6
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin;

use WPGenius\MyPlugin\PostTypes\Example;

defined( 'ABSPATH' ) || exit;

/**
 * REST routes.
 */
class Rest extends Api {

	/**
	 * Route namespace: plugin prefix + API version.
	 */
	const ROUTE_NAMESPACE = 'myplugin/v1';

	/**
	 * Register routes when the REST API starts.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Full URL of a route, e.g. self::url( 'items' ).
	 *
	 * @param string $route Route without the namespace.
	 * @return string
	 */
	public static function url( $route ) {
		return rest_url( self::ROUTE_NAMESPACE . '/' . ltrim( $route, '/' ) );
	}

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/items',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					// Published items are public. For private data, check a capability:
					// static function () { return current_user_can( 'edit_posts' ); }.
					'permission_callback' => '__return_true',
					'args'                => array(
						'page'     => array(
							'description' => __( 'Page number.', 'wordpress-plugin-boilerplate' ),
							'type'        => 'integer',
							'default'     => 1,
							'minimum'     => 1,
						),
						'per_page' => array(
							'description' => __( 'Items per page.', 'wordpress-plugin-boilerplate' ),
							'type'        => 'integer',
							'default'     => (int) self::get_setting( 'items_per_page', 6 ),
							'minimum'     => 1,
							'maximum'     => Example::MAX_PER_PAGE,
						),
					),
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);
	}

	/**
	 * GET /items: one page of published items, as data and as rendered HTML.
	 *
	 * @param \WP_REST_Request $request Request with validated page and per_page.
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ) {
		$page  = (int) $request->get_param( 'page' );
		$query = Example::query( (int) $request->get_param( 'per_page' ), $page );
		$items = array();
		$html  = '';

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$items[] = array(
				'id'      => $post->ID,
				'title'   => get_the_title( $post ),
				'excerpt' => get_the_excerpt( $post ),
				'link'    => get_permalink( $post ),
				'image'   => get_the_post_thumbnail_url( $post, 'medium_large' ),
			);
			$html   .= self::render_template( 'item.php', array( 'post' => $post ) );
		}

		$response = rest_ensure_response(
			array(
				'items'    => $items,
				'html'     => $html,
				'has_more' => $page < (int) $query->max_num_pages,
			)
		);
		$response->header( 'X-WP-Total', (string) (int) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) (int) $query->max_num_pages );

		return $response;
	}

	/**
	 * Response schema of GET /items (shown at /wp-json/myplugin/v1).
	 *
	 * @return array<string, mixed>
	 */
	public function get_items_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'myplugin-items',
			'type'       => 'object',
			'properties' => array(
				'items'    => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'      => array( 'type' => 'integer' ),
							'title'   => array( 'type' => 'string' ),
							'excerpt' => array( 'type' => 'string' ),
							'link'    => array(
								'type'   => 'string',
								'format' => 'uri',
							),
							'image'   => array( 'type' => array( 'string', 'boolean' ) ),
						),
					),
				),
				'html'     => array(
					'type'        => 'string',
					'description' => __( 'The items rendered with templates/item.php.', 'wordpress-plugin-boilerplate' ),
				),
				'has_more' => array( 'type' => 'boolean' ),
			),
		);
	}
}
