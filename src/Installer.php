<?php
/**
 * Activation, deactivation and multisite setup.
 *
 * - activate():   saves default settings and flushes rewrite rules.
 * - deactivate(): flushes rewrite rules (data is kept).
 * - Multisite:    a network activation or deactivation runs on every site, and
 *                 a site added later is set up when the plugin is network active.
 *
 * Deleting data happens in uninstall.php, not here.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin;

defined( 'ABSPATH' ) || exit;

// Example: table (start).
// The example log table {prefix}myplugin_log shows how a plugin adds a table:
// created on activation, upgraded when DB_VERSION is raised (create_tables()
// runs again on the next request and dbDelta() adds the new columns), and
// dropped when its site is deleted from a network.
// Example: table (end).
// Example: report (start).
// The daily report (Cron.php) is scheduled on activation and removed on deactivation.
// Example: report (end).

/**
 * Installer.
 */
class Installer extends Api {

	// Example: table (start).
	/**
	 * Database schema version. Raise it when you change create_tables().
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Option that stores the installed schema version.
	 */
	const DB_VERSION_OPTION = 'myplugin_db_version';

	// Example: table (end).
	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'wp_initialize_site', array( __CLASS__, 'new_site' ), 900 ); // After core created the site's tables.
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 5 ); // Example: table.
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'drop_site_tables' ), 10, 2 ); // Example: table.
	}

	/**
	 * Activation hook. On a network activation, runs for every site.
	 *
	 * @param bool $network_wide Whether the plugin is network activated.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::activate_site();
				restore_current_blog();
			}
			return;
		}
		self::activate_site();
	}

	/**
	 * Deactivation hook. On a network deactivation, runs for every site.
	 *
	 * @param bool $network_wide Whether the plugin is network deactivated.
	 * @return void
	 */
	public static function deactivate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::deactivate_site();
				restore_current_blog();
			}
			return;
		}
		self::deactivate_site();
	}

	/**
	 * Multisite: set up a site created while the plugin is network active.
	 *
	 * @param \WP_Site $site The new site.
	 * @return void
	 */
	public static function new_site( $site ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! is_plugin_active_for_network( plugin_basename( MYPLUGIN_FILE ) ) ) {
			return;
		}
		switch_to_blog( (int) $site->blog_id );
		self::activate_site();
		restore_current_blog();
	}

	// Example: table (start).
	/**
	 * Multisite: drop the plugin's table when its site is deleted.
	 *
	 * @param string[] $tables  Tables WordPress will drop.
	 * @param int      $site_id ID of the site being deleted.
	 * @return string[]
	 */
	public static function drop_site_tables( $tables, $site_id ) {
		global $wpdb;
		$tables[] = $wpdb->get_blog_prefix( $site_id ) . 'myplugin_log';
		return $tables;
	}

	// Example: table (end).
	/**
	 * Clean up one site (its data is kept).
	 *
	 * @return void
	 */
	private static function deactivate_site() {
		Cron::unschedule(); // Example: report.
		flush_rewrite_rules();
	}

	/**
	 * Set up one site.
	 *
	 * @return void
	 */
	private static function activate_site() {
		add_option( self::OPTION, Admin\Settings::defaults() ); // Does nothing if settings exist.
		self::create_tables(); // Example: table.
		Cron::schedule(); // Example: report.

		PostTypes\PostTypes::register_all();
		flush_rewrite_rules();
	}
	// Example: table (start).

	/**
	 * Create or update tables when DB_VERSION changed.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
			self::create_tables();
		}
	}

	/**
	 * Name of the example log table.
	 *
	 * @return string
	 */
	public static function log_table() {
		global $wpdb;
		return $wpdb->prefix . 'myplugin_log';
	}

	/**
	 * Create or update the plugin's tables with dbDelta().
	 * dbDelta is strict: two spaces after PRIMARY KEY, one field per line.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::log_table();
		$charset = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				created_at datetime NOT NULL,
				message text NOT NULL,
				PRIMARY KEY  (id),
				KEY created_at (created_at)
			) {$charset};"
		);

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Add a row to the example log table.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public static function log( $message ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table.
			self::log_table(),
			array(
				'created_at' => current_time( 'mysql', true ),
				'message'    => (string) $message,
			),
			array( '%s', '%s' )
		);
	}
	// Example: table (end).
}
