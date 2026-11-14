<?php
/**
 * Unit test bootstrap: loads the plugin's classes without WordPress.
 * WordPress functions are mocked per test with Brain Monkey.
 *
 * @package WPGenius\MyPlugin
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'ABSPATH', __DIR__ . '/' );
define( 'MYPLUGIN_VERSION', '0.0.0-test' );
define( 'MYPLUGIN_FILE', dirname( __DIR__ ) . '/myplugin.php' );
define( 'MYPLUGIN_DIR_PATH', dirname( __DIR__ ) . '/' );
define( 'MYPLUGIN_DIR_URL', 'https://example.test/wp-content/plugins/wordpress-plugin-boilerplate/' );

require_once dirname( __DIR__ ) . '/src/autoload.php';
