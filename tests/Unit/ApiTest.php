<?php
/**
 * Tests for the shared helpers in src/Api.php.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Tests\Unit;

use Brain\Monkey\Functions;
use WPGenius\MyPlugin\Api;
use WPGenius\MyPlugin\Tests\TestCase;

/**
 * Api tests.
 */
class ApiTest extends TestCase {

	public function test_replace_placeholders_with_or_without_braces() {
		$text = Api::replace_placeholders(
			'Hi {name}, you have {count} new items.',
			array(
				'name'    => 'Asha',
				'{count}' => 3,
			)
		);

		$this->assertSame( 'Hi Asha, you have 3 new items.', $text );
	}

	public function test_replace_placeholders_leaves_unknown_ones() {
		$this->assertSame( 'Hello {unknown}', Api::replace_placeholders( 'Hello {unknown}', array( 'name' => 'x' ) ) );
	}

	public function test_get_setting_falls_back_to_defaults() {
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'get_option' )->justReturn( array( 'layout' => 'list' ) );

		$this->assertSame( 'list', Api::get_setting( 'layout' ) ); // Saved value.
		$this->assertSame( 6, Api::get_setting( 'items_per_page' ) ); // Default.
		$this->assertSame( 'none', Api::get_setting( 'missing', 'none' ) ); // Unknown key.
	}
}
