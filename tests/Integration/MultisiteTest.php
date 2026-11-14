<?php
/**
 * Multisite: new sites, network deactivation and deleted sites.
 *
 * Skipped on a single site. Run with: npm run test:integration:multisite
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Tests\Integration;

use WPGenius\MyPlugin\Api;
use WPGenius\MyPlugin\Cron;
use WPGenius\MyPlugin\Installer;

/**
 * Multisite behaviour.
 *
 * @group multisite
 */
class MultisiteTest extends \WP_UnitTestCase {

	/**
	 * Skip the whole class on a single site.
	 */
	public function set_up() {
		parent::set_up();
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}
	}

	/**
	 * Mark the plugin as network active, or not.
	 *
	 * @param bool $active Whether it is network active.
	 */
	private function network_active( $active ) {
		update_site_option( 'active_sitewide_plugins', $active ? array( plugin_basename( MYPLUGIN_FILE ) => time() ) : array() );
	}

	public function test_a_new_site_is_set_up_when_the_plugin_is_network_active() {
		$this->network_active( true );
		$site = self::factory()->blog->create();

		switch_to_blog( $site );
		$settings = get_option( Api::OPTION );
		$version  = get_option( Installer::DB_VERSION_OPTION );
		restore_current_blog();

		$this->assertIsArray( $settings );
		$this->assertSame( Installer::DB_VERSION, $version );
	}

	public function test_a_new_site_is_left_alone_when_the_plugin_is_not_network_active() {
		$this->network_active( false );
		$site = self::factory()->blog->create();

		switch_to_blog( $site );
		$settings = get_option( Api::OPTION );
		restore_current_blog();

		$this->assertFalse( $settings );
	}

	public function test_network_deactivation_removes_the_report_from_every_site() {
		$sites = array( get_current_blog_id(), self::factory()->blog->create() );
		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', Cron::REPORT_HOOK );
			restore_current_blog();
		}

		Installer::deactivate( true );

		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			$this->assertFalse( wp_next_scheduled( Cron::REPORT_HOOK ), "Site $site" );
			restore_current_blog();
		}
	}

	public function test_the_log_table_is_dropped_with_its_site() {
		global $wpdb;
		$site = self::factory()->blog->create();

		$tables = apply_filters( 'wpmu_drop_tables', array(), $site ); // phpcs:ignore WordPress.NamingConventions.PrefixAllHooks.NonPrefixedHooknameFound -- Core hook.

		$this->assertContains( $wpdb->get_blog_prefix( $site ) . 'myplugin_log', $tables );
	}
}
