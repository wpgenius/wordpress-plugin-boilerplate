<?php
/**
 * Pre-submission check, step 2, the same for every plugin: on a clean site with
 * WP_DEBUG it renders what plugin.php lists, opens the settings page, looks at
 * the Dashboard (not the plugin's screen), deactivates, uninstalls, and counts
 * PHP notices. Appends key=value lines to /sp/runtime-<php>.txt.
 *
 * Don't edit this file for your plugin; edit plugin.php next to it.
 *
 * Hooks are called directly instead of firing init again: a second init floods
 * the log with notices that a real request never shows.
 *
 * Run by bin/presubmit/run.sh inside WordPress Playground; not part of the plugin.
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';

/**
 * Runs the check and returns its lines.
 *
 * @return string[]
 */
function presubmit_runtime() {
	global $wpdb;

	$config = array_merge(
		array(
			'prefix'            => str_replace( '-', '_', dirname( PRESUBMIT_PLUGIN ) ),
			'setup'             => null,
			'render'            => array(),
			'check_render'      => null,
			'settings_page'     => null,
			'before_deactivate' => null,
			'before_uninstall'  => null,
			'after_uninstall'   => null,
		),
		(array) require __DIR__ . '/plugin.php'
	);
	$prefix = $config['prefix'];
	$call   = static function ( $callback, ...$args ) {
		return is_callable( $callback ) ? (string) call_user_func_array( $callback, $args ) : '';
	};

	$lines  = array();
	$errors = array();
	set_error_handler(
		static function ( $number, $message, $file, $line ) use ( &$errors ) {
			$errors[] = ( false !== strpos( $file, '/wp-content/plugins/' ) ? 'PLUGIN ' : 'CORE   ' ) . "$number $message @ $file:$line";
			return false;
		}
	);
	wp_set_current_user( 1 );

	$lines[] = 'active=' . ( is_plugin_active( PRESUBMIT_PLUGIN ) ? 'yes' : 'no' ) . " prefix=$prefix";
	$call( $config['setup'] );

	// Front end: every block and shortcode plugin.php lists.
	$html = '';
	foreach ( (array) $config['render'] as $markup ) {
		$html .= do_shortcode( do_blocks( $markup ) );
	}
	file_put_contents( '/sp/render-' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.html', $html );
	$lines[] = trim(
		'render_bytes=' . strlen( $html )
		. ' inline_style_or_script=' . ( preg_match( '/<(style|script)\b/i', $html ) ? 'yes' : 'no' )
		. ' ' . $call( $config['check_render'], $html )
	);

	// Admin: the settings page. Outside a real admin request core's
	// get_admin_page_title() may raise a deprecation (counted as core_notices).
	if ( is_callable( $config['settings_page'] ) ) {
		$GLOBALS['title'] = 'Settings';
		ob_start();
		$call( $config['settings_page'] );
		$lines[] = 'settings_page_bytes=' . strlen( (string) ob_get_clean() );
	}

	// The Dashboard is not the plugin's screen: no plugin assets, no notices.
	set_current_screen( 'dashboard' );
	$queued = array_merge( wp_scripts()->queue, wp_styles()->queue ); // Blocks above queued their styles.
	do_action( 'admin_enqueue_scripts', 'index.php' );
	$assets = array();
	foreach ( array( wp_scripts(), wp_styles() ) as $deps ) {
		foreach ( array_diff( $deps->queue, $queued ) as $handle ) {
			$src = isset( $deps->registered[ $handle ] ) ? (string) $deps->registered[ $handle ]->src : '';
			if ( false !== strpos( $src, '/plugins/' . dirname( PRESUBMIT_PLUGIN ) . '/' ) ) {
				$assets[] = $handle;
			}
		}
	}
	ob_start();
	do_action( 'admin_notices' );
	$lines[] = 'dashboard_plugin_assets=' . ( $assets ? implode( ',', $assets ) : 'none' )
		. ' dashboard_notice_bytes=' . strlen( (string) ob_get_clean() );

	// Deactivation removes every cron event whose hook starts with the prefix.
	$prefixed_cron = static function () use ( $prefix ) {
		$hooks = array();
		foreach ( (array) _get_cron_array() as $events ) {
			foreach ( array_keys( (array) $events ) as $hook ) {
				if ( 0 === strpos( $hook, $prefix ) ) {
					$hooks[] = $hook;
				}
			}
		}
		return array_unique( $hooks );
	};
	$call( $config['before_deactivate'] );
	$before = $prefixed_cron();
	deactivate_plugins( PRESUBMIT_PLUGIN );
	$after   = $prefixed_cron();
	$lines[] = 'cron_before_deactivate=' . ( $before ? implode( ',', $before ) : 'none' )
		. ' cron_after_deactivate=' . ( $after ? implode( ',', $after ) : 'none' );

	// Uninstall (plugin.php ticks "delete data") leaves no prefixed options,
	// transients or tables.
	$call( $config['before_uninstall'] );
	ob_start();
	uninstall_plugin( PRESUBMIT_PLUGIN );
	$output = (string) ob_get_clean();
	$like   = $wpdb->esc_like( $prefix ) . '%';
	$left   = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
			$like,
			'_transient_' . $like,
			'_transient_timeout_' . $like,
			'_site_transient_' . $like
		)
	);
	$tables  = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . $prefix ) . '%' ) );
	$lines[] = trim(
		'uninstall_output_bytes=' . strlen( $output )
		. ' options_left=' . ( $left ? implode( ',', $left ) : 'none' )
		. ' tables_left=' . ( $tables ? implode( ',', $tables ) : 'none' )
		. ' ' . $call( $config['after_uninstall'] )
	);

	restore_error_handler();
	$plugin_notices = count( preg_grep( '/^PLUGIN /', $errors ) );
	$lines[]        = 'plugin_notices=' . $plugin_notices . ' core_notices=' . ( count( $errors ) - $plugin_notices );
	return array_merge( $lines, $errors );
}

file_put_contents( '/sp/runtime-' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.txt', implode( "\n", presubmit_runtime() ) . "\n", FILE_APPEND );
