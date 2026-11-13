<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 *
 * The plugin itself is NOT loaded here, so do not rely on its classes or constants.
 *
 * @package wordpress-plugin-boilerplate
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove plugin data for the current site, honouring the uninstall preferences.
 */
function myplugin_uninstall_site() {
	global $wpdb;

	if ( get_option( 'myplugin_delete_db' ) ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}myplugin_table" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	if ( get_option( 'myplugin_delete_settings' ) ) {
		$options = array(
			'myplugin_event_per_page',
			'myplugin_reminder_enabled',
			'myplugin_reminder_interval',
			'myplugin_delete_db',
			'myplugin_delete_settings',
		);
		foreach ( $options as $option ) {
			delete_option( $option );
		}
	}

	delete_option( 'myplugin_permalinks_flushed' );
	wp_unschedule_hook( 'myplugin_event_reminder' ); // Clears every event for the hook, whatever its args.
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $myplugin_site_id ) {
		switch_to_blog( $myplugin_site_id );
		myplugin_uninstall_site();
		restore_current_blog();
	}
} else {
	myplugin_uninstall_site();
}
