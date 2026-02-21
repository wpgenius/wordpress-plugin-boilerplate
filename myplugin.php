<?php
/*
Plugin Name: Plugin boilerplate for WordPress
Plugin URI: https://wpgenius.in
Description: WordPress plugin boilerplate with complete functionality
Version: 1.0.0
Author: Team WPGenius (Makarand Mane)
Author URI: https://makarandmane.com
Text Domain: wordpress-plugin-boilerplate
*/
/*
Copyright 2022  Team WPGenius  (email : makarand@wpgenius.in)
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MYPLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'MYPLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

include_once MYPLUGIN_DIR_PATH.'includes/class-init.php';
include_once MYPLUGIN_DIR_PATH.'includes/class-database.php';
include_once MYPLUGIN_DIR_PATH.'includes/class-ajax.php';
include_once MYPLUGIN_DIR_PATH.'includes/class-admin.php';
include_once MYPLUGIN_DIR_PATH.'includes/class-settings.php';
include_once MYPLUGIN_DIR_PATH.'includes/class-actions.php';
include_once MYPLUGIN_DIR_PATH.'includes/shortcodes/shortcodes.php';
include_once MYPLUGIN_DIR_PATH.'includes/widgets/widgets.php';

// Add text domain
add_action('plugins_loaded','myplugin_translations');
function myplugin_translations(){
    $locale = apply_filters("plugin_locale", get_locale(), 'wordpress-plugin-boilerplate');
    $lang_dir = dirname( __FILE__ ) . '/languages/';
    $mofile        = sprintf( '%1$s-%2$s.mo', 'wordpress-plugin-boilerplate', $locale );
    $mofile_local  = $lang_dir . $mofile;
    $mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;

    if ( file_exists( $mofile_global ) ) {
        load_textdomain( 'wordpress-plugin-boilerplate', $mofile_global );
    } else {
        load_textdomain( 'wordpress-plugin-boilerplate', $mofile_local );
    }  
}

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

register_activation_hook( 	__FILE__, array( $myplugin_db, 'activate_plugin' 	) );
register_deactivation_hook( __FILE__, array( $myplugin_db, 'deactivate_plugin' ) );
register_activation_hook( __FILE__, function(){ register_uninstall_hook( __FILE__, array( 'MyPlugin_Class_DB', 'uninstall_plugin' ) ); });
