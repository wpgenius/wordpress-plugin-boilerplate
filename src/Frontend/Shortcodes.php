<?php
/**
 * Shortcodes.
 *
 *   [myplugin_items]              latest items, per-page and layout from the settings
 *   [myplugin_items per_page="3"] override the number of items
 *
 * To add a shortcode, add it to the constructor and write its render method.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Frontend;

use WPGenius\MyPlugin\Api;
use WPGenius\MyPlugin\PostTypes\Example;

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode handlers.
 */
class Shortcodes extends Api {

	/**
	 * Register shortcodes.
	 */
	public function __construct() {
		add_shortcode( 'myplugin_items', array( $this, 'render_items' ) );
	}

	/**
	 * [myplugin_items]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_items( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page' => self::get_setting( 'items_per_page' ),
				'layout'   => self::get_setting( 'layout' ),
			),
			$atts,
			'myplugin_items'
		);

		Frontend::enqueue_assets();

		$per_page = max( 1, absint( $atts['per_page'] ) );

		return self::render_template(
			'items-list.php',
			array(
				'query'     => Example::query( $per_page ),
				'per_page'  => $per_page,
				'layout'    => in_array( $atts['layout'], array( 'grid', 'list' ), true ) ? $atts['layout'] : 'grid',
				'load_more' => (bool) self::get_setting( 'show_load_more' ),
			)
		);
	}
}
