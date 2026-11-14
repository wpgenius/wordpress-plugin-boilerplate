<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 *
 * Settings and items (and the example log table) are deleted only when "Delete
 * data on uninstall" is ticked on the settings page. Scheduled jobs are always
 * removed.
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
	// Example: report (start).
	wp_clear_scheduled_hook( 'myplugin_daily_report' ); // Cron::REPORT_HOOK.

	// Example: report (end).
	$settings = get_option( 'myplugin_settings', array() ); // Api::OPTION.
	if ( empty( $settings['delete_data'] ) ) {
		return;
	}

	// Item categories (PostTypes\Example::TAXONOMY), with their links to items.
	$term_ids = get_terms(
		array(
			'taxonomy'   => 'myplugin_item_category',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	if ( is_array( $term_ids ) ) {
		foreach ( $term_ids as $term_id ) {
			wp_delete_term( (int) $term_id, 'myplugin_item_category' );
		}
	}

	// Items (PostTypes\Example::NAME) with their meta and revisions.
	// 'any' alone skips the trash and auto-drafts, so they are named too.
	$item_ids = get_posts(
		array(
			'post_type'   => 'myplugin_item',
			'post_status' => array( 'any', 'trash', 'auto-draft' ),
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);
	foreach ( $item_ids as $item_id ) {
		wp_delete_post( $item_id, true );
	}

	// Example: table (start).
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}myplugin_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Installer::log_table().
	delete_option( 'myplugin_db_version' ); // Installer::DB_VERSION_OPTION.

	// Example: table (end).
	delete_option( 'myplugin_settings' );
}

// The plugin is not loaded, so its taxonomy is unknown to WordPress here;
// register it so the terms can be found and deleted.
register_taxonomy( 'myplugin_item_category', 'myplugin_item' );

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
