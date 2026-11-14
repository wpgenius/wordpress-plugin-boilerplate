<?php
/**
 * Registers every block built into build/.
 *
 * Block sources live in blocks/<name>/ (block.json, index.js, edit.js,
 * render.php, style.css). `npm run build` compiles them into build/<name>/,
 * which ships with the plugin. To add a block, add a folder in blocks/ and
 * run the build; nothing to change here.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Blocks;

use WPGenius\MyPlugin\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Block loader.
 */
class Blocks extends Api {

	/**
	 * Register blocks on init.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register each build/<name>/block.json.
	 *
	 * @return void
	 */
	public function register_blocks() {
		$block_files = glob( MYPLUGIN_DIR_PATH . 'build/*/block.json' );
		foreach ( $block_files ? $block_files : array() as $block_json ) {
			register_block_type( dirname( $block_json ) );
		}
	}
}
