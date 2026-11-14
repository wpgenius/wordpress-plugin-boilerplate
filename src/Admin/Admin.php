<?php
/**
 * Admin area (wp-admin): CSS/JS and the Settings link on the Plugins screen.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Admin;

use WPGenius\MyPlugin\Api;
use WPGenius\MyPlugin\PostTypes\Example;

defined( 'ABSPATH' ) || exit;

/**
 * Admin area.
 */
class Admin extends Api {

	/**
	 * Register admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MYPLUGIN_FILE ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Load admin CSS/JS on this plugin's screens only.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		$screen  = get_current_screen();
		$is_ours = 'settings_page_' . Settings::PAGE === $hook_suffix
			|| ( $screen && Example::NAME === $screen->post_type );

		if ( ! $is_ours ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'myplugin-admin', self::asset_url( 'assets/css/admin.css' ), array(), self::asset_version( 'assets/css/admin.css' ) );
		wp_enqueue_script( 'myplugin-admin', self::asset_url( 'assets/js/admin.js' ), array( 'jquery', 'wp-color-picker' ), self::asset_version( 'assets/js/admin.js' ), true );
	}

	/**
	 * Add "Settings" next to Deactivate on the Plugins screen.
	 *
	 * @param string[] $links Action links.
	 * @return string[]
	 */
	public function add_settings_link( $links ) {
		$url = admin_url( 'options-general.php?page=' . Settings::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wordpress-plugin-boilerplate' ) . '</a>' );
		return $links;
	}
}
