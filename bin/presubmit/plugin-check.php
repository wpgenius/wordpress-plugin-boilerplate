<?php
/**
 * Pre-submission check: Plugin Check with every static check, experimental
 * ones included, on the packaged plugin. Writes /sp/plugin-check.txt.
 *
 * Run by bin/presubmit/run.sh inside WordPress Playground; not part of the plugin.
 */

require_once '/wordpress/wp-load.php';

$presubmit_lines = array();
try {
	$presubmit_repository = 'WordPress\Plugin_Check\Checker\Check_Repository';
	$presubmit_checks     = ( new WordPress\Plugin_Check\Checker\Default_Check_Repository() )->get_checks(
		constant( $presubmit_repository . '::TYPE_STATIC' ) | constant( $presubmit_repository . '::INCLUDE_EXPERIMENTAL' )
	);
	$presubmit_context    = new WordPress\Plugin_Check\Checker\Check_Context( WP_PLUGIN_DIR . '/' . PRESUBMIT_PLUGIN );
	$presubmit_result     = new WordPress\Plugin_Check\Checker\Check_Result( $presubmit_context );

	$presubmit_lines[] = 'checks: ' . implode( ', ', array_keys( iterator_to_array( $presubmit_checks ) ) );
	foreach ( $presubmit_checks as $presubmit_slug => $presubmit_check ) {
		try {
			$presubmit_check->run( $presubmit_result );
		} catch ( Throwable $presubmit_e ) {
			$presubmit_lines[] = "EXCEPTION $presubmit_slug: " . $presubmit_e->getMessage();
		}
	}

	$presubmit_found = array(
		'ERROR'   => $presubmit_result->get_errors(),
		'WARNING' => $presubmit_result->get_warnings(),
	);
	foreach ( $presubmit_found as $presubmit_type => $presubmit_files ) {
		foreach ( $presubmit_files as $presubmit_file => $presubmit_rows ) {
			foreach ( $presubmit_rows as $presubmit_line => $presubmit_columns ) {
				foreach ( $presubmit_columns as $presubmit_messages ) {
					foreach ( $presubmit_messages as $presubmit_message ) {
						$presubmit_lines[] = "$presubmit_type $presubmit_file:$presubmit_line {$presubmit_message['code']} " . wp_strip_all_tags( $presubmit_message['message'] );
					}
				}
			}
		}
	}
	$presubmit_lines[] = 'errors=' . $presubmit_result->get_error_count() . ' warnings=' . $presubmit_result->get_warning_count();
} catch ( Throwable $presubmit_e ) {
	$presubmit_lines[] = 'FATAL ' . get_class( $presubmit_e ) . ': ' . $presubmit_e->getMessage() . ' @ ' . $presubmit_e->getFile() . ':' . $presubmit_e->getLine();
}

file_put_contents( '/sp/plugin-check.txt', implode( "\n", $presubmit_lines ) . "\n" );
