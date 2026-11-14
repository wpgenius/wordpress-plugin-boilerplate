<?php
/**
 * Activation, deactivation and upgrades.
 *
 * - activate():   creates the database table, saves default settings,
 *                 schedules cron jobs and flushes rewrite rules.
 * - deactivate(): removes cron jobs and flushes rewrite rules (data is kept).
 * - Upgrades:     when DB_VERSION is raised, create_tables() runs again on the
 *                 next request; dbDelta() adds new columns and tables.
 *
 * Deleting data happens in uninstall.php, not here.
 * Example table: {prefix}myplugin_log. Delete the table code if you don't need one.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin;

defined( 'ABSPATH' ) || exit;

/**
 * Installer.
 */
class Installer extends Api {

	/**
	 * Database schema version. Raise it when you change create_tables().
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Option that stores the installed schema version.
	 */
	const DB_VERSION_OPTION = 'myplugin_db_version';

	/**
	 * Run upgrades when the schema version changes (e.g. after a plugin update).
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 5 );
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
	 * Deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivate() {
		Cron::unschedule();
		flush_rewrite_rules();
	}

	/**
	 * Set up one site.
	 *
	 * @return void
	 */
	private static function activate_site() {
		self::create_tables();
		add_option( self::OPTION, Admin\Settings::defaults() ); // Does nothing if settings exist.
		Cron::schedule();

		PostTypes\PostTypes::register_all();
		flush_rewrite_rules();
	}

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
}
