<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 *
 * Settings, items and the log table are deleted only when "Delete data on
 * uninstall" is ticked on the settings page. Scheduled jobs are always removed.
 *
 * The plugin is NOT loaded here, so its classes and constants are not available;
 * keep the names below in sync with src/.
 *
 * @package WPGenius\MyPlugin
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove the plugin's data from the current site.
 *
 * @return void
 */
function myplugin_uninstall_site() {
	global $wpdb;

	wp_clear_scheduled_hook( 'myplugin_daily_report' ); // Cron::REPORT_HOOK.

	$settings = get_option( 'myplugin_settings', array() ); // Api::OPTION.
	if ( empty( $settings['delete_data'] ) ) {
		return;
	}

	// Items (PostTypes\Example::NAME) with their meta, terms and revisions.
	$item_ids = get_posts(
		array(
			'post_type'   => 'myplugin_item',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);
	foreach ( $item_ids as $item_id ) {
		wp_delete_post( $item_id, true );
	}

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}myplugin_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Installer::log_table().

	delete_option( 'myplugin_settings' );
	delete_option( 'myplugin_db_version' ); // Installer::DB_VERSION_OPTION.
}

if ( is_multisite() ) {
	$myplugin_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $myplugin_site_ids as $myplugin_site_id ) {
		switch_to_blog( $myplugin_site_id );
		myplugin_uninstall_site();
		restore_current_blog();
	}
} else {
	myplugin_uninstall_site();
}
