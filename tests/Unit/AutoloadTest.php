<?php
/**
 * Every file in src/ must declare the class its path promises.
 *
 * The autoloader (src/autoload.php) turns WPGenius\MyPlugin\Admin\Settings into
 * src/Admin/Settings.php. macOS and Windows ignore the case of file names, Linux
 * servers do not: "settings.php" works on your laptop and fails on the live
 * site. This test catches that, and typos in a namespace, on every system.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Tests\Unit;

use WPGenius\MyPlugin\Tests\TestCase;

/**
 * Autoloader and file naming.
 */
class AutoloadTest extends TestCase {

	/**
	 * Every class file in src/, as [ expected class name, file path ].
	 *
	 * @return array<string, array{string, string}>
	 */
	public function class_files() {
		$src   = dirname( __DIR__, 2 ) . '/src';
		$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $src, \FilesystemIterator::SKIP_DOTS ) );
		$cases = array();

		foreach ( $files as $file ) {
			$relative = substr( $file->getPathname(), strlen( $src ) + 1 );
			if ( 'php' !== $file->getExtension() || 'autoload.php' === $relative ) {
				continue;
			}
			$class              = 'WPGenius\\MyPlugin\\' . str_replace( '/', '\\', substr( $relative, 0, -4 ) );
			$cases[ $relative ] = array( $class, $file->getPathname() );
		}
		return $cases;
	}

	/**
	 * The namespace and class name in the file match its path, letter for letter.
	 *
	 * @dataProvider class_files
	 *
	 * @param string $expected Class name the path promises.
	 * @param string $file     Full path of the file.
	 */
	public function test_file_declares_the_class_its_path_promises( $expected, $file ) {
		$code = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.

		$this->assertSame( 1, preg_match( '/^namespace\s+([^;]+);/m', $code, $namespace ), 'No namespace found.' );
		$this->assertSame( 1, preg_match( '/^(?:final\s+|abstract\s+)?(?:class|interface|trait)\s+(\w+)/m', $code, $class ), 'No class found.' );

		// Compared exactly, so a wrong upper or lower case letter fails here.
		$this->assertSame( $expected, $namespace[1] . '\\' . $class[1] );
	}

	/**
	 * The autoloader finds the class.
	 *
	 * @dataProvider class_files
	 *
	 * @param string $expected Class name the path promises.
	 */
	public function test_class_loads( $expected ) {
		$this->assertTrue( class_exists( $expected ) || interface_exists( $expected ) || trait_exists( $expected ) );
	}
}
