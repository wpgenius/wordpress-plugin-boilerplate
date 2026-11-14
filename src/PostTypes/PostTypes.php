<?php
/**
 * Registers the plugin's custom post types.
 *
 * One file per post type in this folder. To add one, copy Example.php,
 * change NAME and the labels, and add its class to TYPES.
 *
 * Post types are registered on every request (front end, admin, REST),
 * which is why they live here and not in Admin/.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Post type loader.
 */
class PostTypes {

	/**
	 * Post type classes.
	 */
	const TYPES = array(
		Example::class,
	);

	/**
	 * Register on init; each type adds its own admin hooks (columns etc.).
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_all' ) );

		foreach ( self::TYPES as $type ) {
			new $type();
		}
	}

	/**
	 * Register every post type and taxonomy. Also called on activation,
	 * before rewrite rules are flushed.
	 *
	 * @return void
	 */
	public static function register_all() {
		foreach ( self::TYPES as $type ) {
			$type::register();
		}
	}
}
