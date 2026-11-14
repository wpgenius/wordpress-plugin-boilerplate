<?php
/**
 * Tests for sanitising in src/Admin/Settings.php.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Tests\Unit;

use Brain\Monkey\Functions;
use WPGenius\MyPlugin\Admin\Settings;
use WPGenius\MyPlugin\Tests\TestCase;

/**
 * Settings tests.
 */
class SettingsTest extends TestCase {

	public function test_number_is_clamped_to_min_and_max() {
		$field = array(
			'type'    => 'number',
			'default' => 6,
			'min'     => 1,
			'max'     => 50,
		);

		$this->assertSame( 50, Settings::sanitize_value( $field, '500' ) );
		$this->assertSame( 1, Settings::sanitize_value( $field, '-3' ) );
		$this->assertSame( 6, Settings::sanitize_value( $field, 'abc' ) );
	}

	public function test_checkbox_is_one_or_zero() {
		$field = array( 'type' => 'checkbox' );

		$this->assertSame( 1, Settings::sanitize_value( $field, '1' ) );
		$this->assertSame( 0, Settings::sanitize_value( $field, '0' ) );
		$this->assertSame( 0, Settings::sanitize_value( $field, array( 'x' ) ) );
	}

	public function test_select_only_accepts_listed_options() {
		$field = array(
			'type'    => 'select',
			'default' => 'grid',
			'options' => array(
				'grid' => 'Grid',
				'list' => 'List',
			),
		);

		$this->assertSame( 'list', Settings::sanitize_value( $field, 'list' ) );
		$this->assertSame( 'grid', Settings::sanitize_value( $field, 'table' ) );
	}

	public function test_time_must_be_hh_mm() {
		$field = array(
			'type'    => 'time',
			'default' => '08:00',
		);

		$this->assertSame( '23:59', Settings::sanitize_value( $field, '23:59' ) );
		$this->assertSame( '08:00', Settings::sanitize_value( $field, '25:00' ) );
	}

	public function test_text_uses_sanitize_text_field() {
		Functions\expect( 'sanitize_text_field' )->once()->with( ' Hi ' )->andReturn( 'Hi' );

		$this->assertSame( 'Hi', Settings::sanitize_value( array( 'type' => 'text' ), ' Hi ' ) );
	}

	public function test_every_field_has_a_default() {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		foreach ( Settings::flat_fields() as $key => $field ) {
			$this->assertArrayHasKey( 'default', $field, "Field '$key' needs a default." );
			$this->assertArrayHasKey( 'type', $field, "Field '$key' needs a type." );
		}
	}
}
