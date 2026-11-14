<?php
/**
 * Starts the plugin: creates each part once.
 *
 * To add a part, create its class in src/ and add it to the list below.
 * Three examples are optional and marked "Example: <name>" wherever they
 * appear: report (daily email: Cron.php, Ajax.php), table (the log table in
 * Installer.php) and plugin-info (Admin/PluginInfo.php). `bin/rename.sh --minimal`
 * removes all three; to remove one by hand, search for its marker.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin bootstrap.
 */
final class Plugin {

	/**
	 * The one running instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Created parts, keyed by class name, e.g. Plugin::instance()->get( Admin\Settings::class ).
	 *
	 * @var object[]
	 */
	private $parts = array();

	/**
	 * Start the plugin. Hooked to plugins_loaded in the main plugin file.
	 *
	 * @return void
	 */
	public static function boot() {
		self::instance();
	}

	/**
	 * The running instance (created on first call).
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Create every part. Each part registers its own hooks in its constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Everywhere: front end, admin, AJAX, REST, cron.
		$parts = array(
			Installer::class,
			PostTypes\PostTypes::class,
			Blocks\Blocks::class,
			Rest::class,
			Ajax::class, // Example: report.
			Cron::class, // Example: report.
			Frontend\Frontend::class,
			Frontend\Shortcodes::class,
		);

		// wp-admin only.
		if ( is_admin() ) {
			$parts[] = Admin\Admin::class;
			$parts[] = Admin\Settings::class;
			$parts[] = Admin\PluginInfo::class; // Example: plugin-info.
		}

		foreach ( $parts as $part ) {
			$this->parts[ $part ] = new $part();
		}
	}

	/**
	 * Get a running part, or null if it isn't loaded on this request.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return object|null
	 */
	public function get( $class_name ) {
		return isset( $this->parts[ $class_name ] ) ? $this->parts[ $class_name ] : null;
	}

	/**
	 * Load translations: WP_LANG_DIR/plugins/ first, then this plugin's languages/ folder.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wordpress-plugin-boilerplate', false, dirname( plugin_basename( MYPLUGIN_FILE ) ) . '/languages' );
	}
}
