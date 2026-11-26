<?php
/**
 * What the pre-submission runtime check exercises in THIS plugin. The only
 * file in bin/presubmit/ to edit for your plugin; runtime.php does the rest.
 *
 * Every key is optional. Callbacks run on a clean site with WP_DEBUG, as the
 * administrator, after activation. Callbacks that return text add it to the
 * results line as "key=value" pairs.
 *
 * The values below exercise the boilerplate's examples; bin/rename.sh renames
 * them with the rest of the plugin.
 *
 * Run by bin/presubmit/run.sh inside WordPress Playground; not part of the plugin.
 */

return array(
	// Start of the plugin's option, transient, cron hook and table names (after
	// the table prefix). Default: the Text Domain with "-" replaced by "_".
	'prefix'            => 'myplugin',

	// Runs first: create the content the blocks and shortcodes show.
	'setup'             => static function () {
		foreach ( array( 'First item', 'Second item & more' ) as $title ) {
			wp_insert_post(
				array(
					'post_type'    => 'myplugin_item',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_excerpt' => 'Excerpt of ' . $title,
				)
			);
		}
	},

	// Rendered on the front end: block markup and/or shortcodes.
	'render'            => array(
		'<!-- wp:wordpress-plugin-boilerplate/example {"count":2} /-->',
		'<!-- wp:wordpress-plugin-boilerplate/interactive {"count":2} /-->',
		'[myplugin_items per_page="1"]',
	),

	// Extra checks on the rendered HTML.
	'check_render'      => static function ( $html ) {
		return 'items=' . substr_count( $html, 'class="myplugin-item"' )
			. ' wp_directives=' . preg_match_all( '/ data-wp-[a-z-]+=/', $html )
			. ' search_input=' . ( false !== strpos( $html, 'type="search"' ) ? 'yes' : 'no' );
	},

	// Prints the settings page (remove the key if the plugin has none).
	'settings_page'     => static function () {
		$GLOBALS['plugin_page'] = WPGenius\MyPlugin\Admin\Settings::PAGE;
		$settings               = new WPGenius\MyPlugin\Admin\Settings();
		$settings->register_settings();
		$settings->render_page();
	},

	// Before deactivation: turn on what schedules cron events, so the check
	// can see that deactivation removes them.
	'before_deactivate' => static function () {
		$options                   = (array) get_option( 'myplugin_settings', array() );
		$options['report_enabled'] = 1;
		update_option( 'myplugin_settings', $options );
	},

	// Before uninstall: tick "Delete data on uninstall".
	'before_uninstall'  => static function () {
		$options                = (array) get_option( 'myplugin_settings', array() );
		$options['delete_data'] = 1;
		update_option( 'myplugin_settings', $options );
	},

	// After uninstall: leftovers the prefix scan can't see (posts, terms, users).
	'after_uninstall'   => static function () {
		$items = get_posts(
			array(
				'post_type'   => 'myplugin_item',
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		return 'items_left=' . count( $items );
	},
);
