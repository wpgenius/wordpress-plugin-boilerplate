<?php
/**
 * AJAX handlers (admin-ajax.php).
 *
 * Each action is hooked twice: wp_ajax_* for logged-in users and
 * wp_ajax_nopriv_* for visitors. Drop the nopriv line for admin-only actions,
 * and pass a capability to verify_request().
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin;

use WPGenius\MyPlugin\PostTypes\Example;

defined( 'ABSPATH' ) || exit;

/**
 * AJAX handlers.
 */
class Ajax extends Api {

	/**
	 * Nonce action shared with assets/js/frontend.js (via mypluginData.nonce).
	 */
	const NONCE = 'myplugin_ajax';

	/**
	 * Register AJAX actions.
	 */
	public function __construct() {
		add_action( 'wp_ajax_myplugin_load_items', array( $this, 'load_items' ) );
		add_action( 'wp_ajax_nopriv_myplugin_load_items', array( $this, 'load_items' ) );
	}

	/**
	 * "Load more": return the HTML of the next page of items.
	 *
	 * Request: action=myplugin_load_items, nonce, page, per_page.
	 * Response: { success: true, data: { html, has_more } }.
	 *
	 * @return void
	 */
	public function load_items() {
		if ( ! self::verify_request( self::NONCE ) ) {
			wp_send_json_error( array( 'message' => __( 'Your session has expired. Reload the page.', 'wordpress-plugin-boilerplate' ) ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked above.
		$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? min( 50, absint( $_POST['per_page'] ) ) : (int) self::get_setting( 'items_per_page' );
		// phpcs:enable

		$query = Example::query( $per_page, $page );
		$html  = '';
		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= self::render_template( 'item.php', array( 'post' => get_post() ) );
		}
		wp_reset_postdata();

		wp_send_json_success(
			array(
				'html'     => $html,
				'has_more' => $page < (int) $query->max_num_pages,
			)
		);
	}
}
