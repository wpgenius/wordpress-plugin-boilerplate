<?php
/*
Plugin Name: Plugin boilerplate for WordPress
Plugin URI: https://wpgenius.in
Description: WordPress plugin boilerplate with complete functionality
Version: 2.0.0
Requires at least: 6.0
Requires PHP: 7.4
Author: Team WPGenius (Makarand Mane)
Author URI: https://makarandmane.com
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wordpress-plugin-boilerplate
Domain Path: /languages
*/
/*
Copyright 2022  Team WPGenius  (email : makarand@wpgenius.in)
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MYPLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'MYPLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

// Plugin classes. Composer is only used for development tools, so the plugin
// runs straight from a git clone or a release zip without `composer install`.
require_once MYPLUGIN_DIR_PATH . 'includes/class-api.php';
require_once MYPLUGIN_DIR_PATH . 'includes/class-database.php';
require_once MYPLUGIN_DIR_PATH . 'includes/class-ajax.php';
require_once MYPLUGIN_DIR_PATH . 'includes/class-admin.php';
require_once MYPLUGIN_DIR_PATH . 'includes/class-settings.php';
require_once MYPLUGIN_DIR_PATH . 'includes/class-actions.php';
require_once MYPLUGIN_DIR_PATH . 'includes/shortcodes/shortcodes.php';
require_once MYPLUGIN_DIR_PATH . 'includes/widgets/widgets.php';

// Load translations: WP_LANG_DIR/plugins/ first, then this plugin's languages/ folder.
add_action( 'init', 'myplugin_load_textdomain' );
function myplugin_load_textdomain() {
	load_plugin_textdomain( 'wordpress-plugin-boilerplate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

if(class_exists('MyPlugin_Class_DB'))
	MyPlugin_Class_DB::init();

if(class_exists('MyPlugin_Class_Actions'))
 	MyPlugin_Class_Actions::init();

if(class_exists('MyPlugin_Class_Shortcodes'))
    MyPlugin_Class_Shortcodes::init();

if(class_exists('MyPlugin_Class_Ajax'))
 	MyPlugin_Class_Ajax::init();

if(class_exists('MyPlugin_Class_Admin') && is_admin())
 	MyPlugin_Class_Admin::init();

if(class_exists('MyPlugin_Class_Settings'))
 	MyPlugin_Class_Settings::init();

register_activation_hook( 	__FILE__, array( MyPlugin_Class_DB::init(), 'activate_plugin' 	) );
register_deactivation_hook( __FILE__, array( MyPlugin_Class_DB::init(), 'deactivate_plugin' ) );
// Uninstall cleanup lives in uninstall.php (WordPress ignores register_uninstall_hook when that file exists).
