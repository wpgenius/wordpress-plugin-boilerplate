<?php
/**
 * Pre-submission check, step 2: on a clean site with WP_DEBUG, render the
 * blocks and the shortcode, open the settings page, look at an admin screen
 * that isn't the plugin's, deactivate, uninstall, and count PHP notices.
 * Appends to /sp/runtime-<php>.txt.
 *
 * Calls hooks directly instead of firing init again: a second init floods the
 * log with notices that a real request never shows.
 *
 * Run by bin/presubmit/run.sh inside WordPress Playground; not part of the plugin.
 *
 * @package WPGenius\MyPlugin
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.DevelopmentFunctions, WordPress.WP.GlobalVariablesOverride, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- A test script, not plugin code.

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';

$myplugin_lines  = array();
$myplugin_errors = array();
set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Counts notices.
	static function ( $number, $message, $file, $line ) use ( &$myplugin_errors ) {
		$myplugin_errors[] = ( false !== strpos( $file, '/wp-content/plugins/' ) ? 'PLUGIN ' : 'CORE   ' ) . "$number $message @ $file:$line";
		return false;
	}
);
wp_set_current_user( 1 );

$myplugin_lines[] = 'active=' . ( is_plugin_active( PRESUBMIT_PLUGIN ) ? 'yes' : 'no' )
	. ' settings_option=' . ( is_array( get_option( 'myplugin_settings' ) ) ? 'yes' : 'no' );

// Two items, so the lists have something to show.
foreach ( array( 'First item', 'Second item & more' ) as $myplugin_title ) {
	wp_insert_post(
		array(
			'post_type'    => 'myplugin_item',
			'post_status'  => 'publish',
			'post_title'   => $myplugin_title,
			'post_excerpt' => 'Excerpt of ' . $myplugin_title,
		)
	);
}

// Front end: both blocks and the shortcode.
$myplugin_html = do_blocks( '<!-- wp:wordpress-plugin-boilerplate/example {"count":2} /-->' )
	. do_blocks( '<!-- wp:wordpress-plugin-boilerplate/interactive {"count":2} /-->' )
	. do_shortcode( '[myplugin_items per_page="1"]' );
file_put_contents( '/sp/render-' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.html', $myplugin_html );
$myplugin_lines[] = 'render_bytes=' . strlen( $myplugin_html )
	. ' items=' . substr_count( $myplugin_html, 'class="myplugin-item"' )
	. ' wp_directives=' . preg_match_all( '/ data-wp-[a-z-]+=/', $myplugin_html )
	. ' search_input=' . ( false !== strpos( $myplugin_html, 'type="search"' ) ? 'yes' : 'no' )
	. ' inline_style_or_script=' . ( preg_match( '/<(style|script)\b/i', $myplugin_html ) ? 'yes' : 'no' );

// Admin: the settings page, then the dashboard (not the plugin's screen).
// Outside a real admin request core's get_admin_page_title() may raise a
// deprecation on PHP 8.1+ (counted as core_notices, not the plugin's).
$GLOBALS['plugin_page'] = 'myplugin_settings'; // Admin\Settings::PAGE.
$GLOBALS['title']       = 'Settings';

$myplugin_settings = new WPGenius\MyPlugin\Admin\Settings();
$myplugin_settings->register_settings();
ob_start();
$myplugin_settings->render_page();
$myplugin_lines[] = 'settings_page_bytes=' . strlen( (string) ob_get_clean() );

set_current_screen( 'dashboard' );
$myplugin_queued = array_merge( wp_scripts()->queue, wp_styles()->queue ); // The blocks above queued their styles.
do_action( 'admin_enqueue_scripts', 'index.php' );
$myplugin_assets = array();
foreach ( array( wp_scripts(), wp_styles() ) as $myplugin_deps ) {
	foreach ( array_diff( $myplugin_deps->queue, $myplugin_queued ) as $myplugin_handle ) {
		$myplugin_src = isset( $myplugin_deps->registered[ $myplugin_handle ] ) ? (string) $myplugin_deps->registered[ $myplugin_handle ]->src : '';
		if ( false !== strpos( $myplugin_src, '/plugins/' . dirname( PRESUBMIT_PLUGIN ) . '/' ) ) {
			$myplugin_assets[] = $myplugin_handle;
		}
	}
}
ob_start();
do_action( 'admin_notices' );
$myplugin_lines[] = 'dashboard_plugin_assets=' . ( $myplugin_assets ? implode( ',', $myplugin_assets ) : 'none' )
	. ' dashboard_notice_bytes=' . strlen( (string) ob_get_clean() );

// Deactivate with the daily report on: its cron event must go.
$myplugin_options                   = (array) get_option( 'myplugin_settings', array() );
$myplugin_options['report_enabled'] = 1;
update_option( 'myplugin_settings', $myplugin_options );
$myplugin_cron_before = wp_next_scheduled( 'myplugin_daily_report' ) ? 'yes' : 'no';
deactivate_plugins( PRESUBMIT_PLUGIN );
$myplugin_lines[] = 'cron_before_deactivate=' . $myplugin_cron_before
	. ' cron_after_deactivate=' . ( wp_next_scheduled( 'myplugin_daily_report' ) ? 'yes' : 'no' );

// Uninstall with "Delete data on uninstall" ticked.
$myplugin_options['delete_data'] = 1;
update_option( 'myplugin_settings', $myplugin_options );
ob_start();
uninstall_plugin( PRESUBMIT_PLUGIN );
$myplugin_uninstall_output = (string) ob_get_clean();
global $wpdb;
$myplugin_items_left = get_posts(
	array(
		'post_type'   => 'myplugin_item',
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);
$myplugin_lines[]    = 'uninstall_output_bytes=' . strlen( $myplugin_uninstall_output )
	. ' option_left=' . ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s", 'myplugin_settings' ) ) ? 'yes' : 'no' ) // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- get_option() would return the registered default.
	. ' items_left=' . count( $myplugin_items_left )
	. ' table_left=' . ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'myplugin_log' ) ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Checks the table is gone.

$myplugin_plugin_notices = count( preg_grep( '/^PLUGIN /', $myplugin_errors ) );
$myplugin_lines[]        = 'plugin_notices=' . $myplugin_plugin_notices . ' core_notices=' . ( count( $myplugin_errors ) - $myplugin_plugin_notices );
$myplugin_lines          = array_merge( $myplugin_lines, $myplugin_errors );
file_put_contents( '/sp/runtime-' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.txt', implode( "\n", $myplugin_lines ) . "\n", FILE_APPEND );
