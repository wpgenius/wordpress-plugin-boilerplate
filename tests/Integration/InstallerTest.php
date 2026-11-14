<?php
/**
 * Activation and deactivation in a real WordPress.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Tests\Integration;

use WPGenius\MyPlugin\Api;
use WPGenius\MyPlugin\Cron;
use WPGenius\MyPlugin\Installer;

/**
 * Installer.
 */
class InstallerTest extends \WP_UnitTestCase {

	public function test_activation_saves_default_settings_and_creates_the_table() {
		global $wpdb;
		delete_option( Api::OPTION );

		Installer::activate();

		$this->assertSame( Installer::DB_VERSION, get_option( Installer::DB_VERSION_OPTION ) );
		$this->assertSame( 6, Api::get_setting( 'items_per_page' ) );

		Installer::log( 'It works.' );
		$this->assertSame( '1', $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Installer::log_table() ) ); // phpcs:ignore WordPress.DB -- Test query on the plugin's own table.
	}

	public function test_activation_keeps_existing_settings() {
		update_option( Api::OPTION, array( 'items_per_page' => 12 ) );

		Installer::activate();

		$this->assertSame( 12, Api::get_setting( 'items_per_page' ) );
	}

	public function test_report_is_scheduled_when_enabled_and_removed_on_deactivation() {
		Installer::activate();
		$this->assertFalse( wp_next_scheduled( Cron::REPORT_HOOK ), 'The report is off by default.' );

		update_option( Api::OPTION, array_merge( Api::get_settings(), array( 'report_enabled' => 1 ) ) );
		$this->assertNotFalse( wp_next_scheduled( Cron::REPORT_HOOK ), 'Saving the settings schedules it.' );

		Installer::deactivate();
		$this->assertFalse( wp_next_scheduled( Cron::REPORT_HOOK ) );
	}
}
