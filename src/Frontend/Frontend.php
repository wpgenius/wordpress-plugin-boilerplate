<?php
/**
 * Front end: registers the CSS/JS used by the shortcode and the block.
 *
 * Assets are only registered here; the shortcode enqueues them when it is
 * actually on the page, so other pages load nothing from this plugin.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Frontend;

use WPGenius\MyPlugin\Api;
use WPGenius\MyPlugin\Rest;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end assets.
 */
class Frontend extends Api {

	/**
	 * Script and style handle.
	 */
	const HANDLE = 'myplugin-frontend';

	/**
	 * Register front-end hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register the front-end CSS/JS and pass settings to the script.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( self::HANDLE, self::asset_url( 'assets/css/frontend.css' ), array(), self::asset_version( 'assets/css/frontend.css' ) );
		wp_add_inline_style( self::HANDLE, ':root{--myplugin-accent:' . sanitize_hex_color( self::get_setting( 'accent_color' ) ) . ';}' );

		wp_register_script( self::HANDLE, self::asset_url( 'assets/js/frontend.js' ), array(), self::asset_version( 'assets/js/frontend.js' ), true );
		wp_localize_script(
			self::HANDLE,
			'mypluginData',
			array(
				// REST route used by "Load more" (src/Rest.php). It is public, so no
				// nonce is sent: a nonce printed into a cached page expires, and
				// WordPress then rejects the request (403) even on a public route.
				// For a route that needs the logged-in user, add
				// 'nonce' => wp_create_nonce( 'wp_rest' ) and send it as X-WP-Nonce.
				'itemsUrl' => Rest::url( 'items' ),
				'i18n'     => array(
					'loading' => __( 'Loading…', 'wordpress-plugin-boilerplate' ),
					'error'   => __( 'Something went wrong. Please try again.', 'wordpress-plugin-boilerplate' ),
				),
			)
		);
	}

	/**
	 * Enqueue the front-end CSS/JS (call from a shortcode or template).
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		wp_enqueue_style( self::HANDLE );
		wp_enqueue_script( self::HANDLE );
	}
}
