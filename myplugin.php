<?php
/**
 * Plugin Name:       Plugin boilerplate for WordPress
 * Plugin URI:        https://github.com/wpgenius/wordpress-plugin-boilerplate
 * Description:       A starter kit for WordPress plugins: settings page, admin, front end, AJAX, cron, custom post type and a block.
 * Version:           2.0.7
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Team WPGenius
 * Author URI:        https://wpgenius.in
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wordpress-plugin-boilerplate
 * Domain Path:       /languages
 *
 * @package WPGenius\MyPlugin
 */

defined( 'ABSPATH' ) || exit;

// The release workflow keeps MYPLUGIN_VERSION in sync with the Version header.
define( 'MYPLUGIN_VERSION', '2.0.7' );
define( 'MYPLUGIN_FILE', __FILE__ );
define( 'MYPLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'MYPLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

// Loads WPGenius\MyPlugin\* classes from src/. No Composer needed at runtime.
require_once MYPLUGIN_DIR_PATH . 'src/autoload.php';

register_activation_hook( __FILE__, array( 'WPGenius\MyPlugin\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPGenius\MyPlugin\Installer', 'deactivate' ) );
// Cleanup when the plugin is deleted lives in uninstall.php.

add_action( 'plugins_loaded', array( 'WPGenius\MyPlugin\Plugin', 'boot' ) );
