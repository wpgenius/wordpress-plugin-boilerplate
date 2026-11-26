<?php
/**
 * Pre-submission check: Plugin Check with every static check, experimental
 * ones included, on the packaged plugin. Writes /sp/plugin-check.txt.
 *
 * Run by bin/presubmit/run.sh inside WordPress Playground; not part of the plugin.
 *
 * @package WPGenius\MyPlugin
 */

// phpcs:disable WordPress.WP.AlternativeFunctions -- Writes its results to the mounted folder.

require_once '/wordpress/wp-load.php';

$myplugin_lines = array();
try {
	$myplugin_repository = 'WordPress\Plugin_Check\Checker\Check_Repository';
	$myplugin_checks     = ( new WordPress\Plugin_Check\Checker\Default_Check_Repository() )->get_checks(
		constant( $myplugin_repository . '::TYPE_STATIC' ) | constant( $myplugin_repository . '::INCLUDE_EXPERIMENTAL' )
	);
	$myplugin_context    = new WordPress\Plugin_Check\Checker\Check_Context( WP_PLUGIN_DIR . '/' . PRESUBMIT_PLUGIN );
	$myplugin_result     = new WordPress\Plugin_Check\Checker\Check_Result( $myplugin_context );

	$myplugin_lines[] = 'checks: ' . implode( ', ', array_keys( iterator_to_array( $myplugin_checks ) ) );
	foreach ( $myplugin_checks as $myplugin_slug => $myplugin_check ) {
		try {
			$myplugin_check->run( $myplugin_result );
		} catch ( Throwable $myplugin_e ) {
			$myplugin_lines[] = "EXCEPTION $myplugin_slug: " . $myplugin_e->getMessage();
		}
	}

	$myplugin_found = array(
		'ERROR'   => $myplugin_result->get_errors(),
		'WARNING' => $myplugin_result->get_warnings(),
	);
	foreach ( $myplugin_found as $myplugin_type => $myplugin_files ) {
		foreach ( $myplugin_files as $myplugin_file => $myplugin_rows ) {
			foreach ( $myplugin_rows as $myplugin_line => $myplugin_columns ) {
				foreach ( $myplugin_columns as $myplugin_messages ) {
					foreach ( $myplugin_messages as $myplugin_message ) {
						$myplugin_lines[] = "$myplugin_type $myplugin_file:$myplugin_line {$myplugin_message['code']} " . wp_strip_all_tags( $myplugin_message['message'] );
					}
				}
			}
		}
	}
	$myplugin_lines[] = 'errors=' . $myplugin_result->get_error_count() . ' warnings=' . $myplugin_result->get_warning_count();
} catch ( Throwable $myplugin_e ) {
	$myplugin_lines[] = 'FATAL ' . get_class( $myplugin_e ) . ': ' . $myplugin_e->getMessage() . ' @ ' . $myplugin_e->getFile() . ':' . $myplugin_e->getLine();
}

file_put_contents( '/sp/plugin-check.txt', implode( "\n", $myplugin_lines ) . "\n" );
