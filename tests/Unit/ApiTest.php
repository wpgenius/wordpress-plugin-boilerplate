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

	public function test_allowed_html_adds_block_attributes_and_inputs() {
		Functions\when( 'wp_kses_allowed_html' )->justReturn(
			array(
				'div' => array( 'class' => true ),
				'img' => array( 'src' => true ),
			)
		);
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$allowed = Api::allowed_html();

		$this->assertTrue( $allowed['div']['data-*'] ); // Interactivity API directives.
		$this->assertTrue( $allowed['div']['hidden'] );
		$this->assertTrue( $allowed['div']['class'] ); // Kept from wp_kses_allowed_html().
		$this->assertTrue( $allowed['img']['srcset'] );
		$this->assertTrue( $allowed['input']['type'] );
	}

	public function test_allowed_html_is_filterable() {
		Functions\when( 'wp_kses_allowed_html' )->justReturn( array() );
		Functions\expect( 'apply_filters' )
			->once()
			->with( 'myplugin_allowed_html', \Mockery::type( 'array' ) )
			->andReturn( array( 'span' => array() ) );

		$this->assertSame( array( 'span' => array() ), Api::allowed_html() );
	}
}
