<?php
/**
 * Pre-submission check, step 1: activate the plugin in its own request and
 * record any output (WordPress shows "unexpected output" for it).
 *
 * Run by bin/presubmit/run.sh inside WordPress Playground; not part of the plugin.
 *
 * @package WPGenius\MyPlugin
 */

// phpcs:disable WordPress.WP.AlternativeFunctions -- Writes its results to the mounted folder.

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

ob_start();
$myplugin_result = activate_plugin( PRESUBMIT_PLUGIN );
$myplugin_output = (string) ob_get_clean();

file_put_contents(
	'/sp/runtime-' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.txt',
	'php=' . PHP_VERSION . ' wp=' . get_bloginfo( 'version' ) . "\n"
	. 'activate=' . ( is_wp_error( $myplugin_result ) ? $myplugin_result->get_error_message() : 'ok' )
	. ' output_bytes=' . strlen( $myplugin_output ) . "\n"
);
