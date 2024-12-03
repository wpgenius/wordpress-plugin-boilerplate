<?php
/*
Plugin Name: WPGenius Events Calendar
Plugin URI: https://wpgenius.in
Description: A WPGenius Events Calendar is a another simple plugin to plan events & collect registrations from attendees.
Version: 1.0
Author: Team WPGenius (Makarand Mane)
Author URI: https://makarandmane.com
Text Domain: wpgenius-events-calendar
*/
/*
Copyright 2022  Team WPGenius  (email : makarand@wpgenius.in)
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WGEC_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'WGEC_DIR_PATH', plugin_dir_path( __FILE__ ) );

include_once WGEC_DIR_PATH.'includes/class.wgec-init.php';
include_once WGEC_DIR_PATH.'includes/class.wgec-database.php';
include_once WGEC_DIR_PATH.'includes/class.wgec-ajax.php';
include_once WGEC_DIR_PATH.'includes/class.wgec-admin.php';
include_once WGEC_DIR_PATH.'includes/class.wgec-settings.php';
include_once WGEC_DIR_PATH.'includes/class.wgec-actions.php';
include_once WGEC_DIR_PATH.'includes/shortcodes/shortcodes.php';
include_once WGEC_DIR_PATH.'includes/widgets/widgets.php';

// Add text domain
add_action('plugins_loaded','wpgenius_events_translations');
function wpgenius_events_translations(){
    $locale = apply_filters("plugin_locale", get_locale(), 'wpgenius-events-calendar');
    $lang_dir = dirname( __FILE__ ) . '/languages/';
    $mofile        = sprintf( '%1$s-%2$s.mo', 'wpgenius-events-calendar', $locale );
    $mofile_local  = $lang_dir . $mofile;
    $mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;

    if ( file_exists( $mofile_global ) ) {
        load_textdomain( 'wpgenius-events-calendar', $mofile_global );
    } else {
        load_textdomain( 'wpgenius-events-calendar', $mofile_local );
    }  
}

if(class_exists('WPGenius_Events_Actions'))
 	WPGenius_Events_Actions::init();

if(class_exists('WPGenius_Shortcodes'))
    WPGenius_Shortcodes::init();

if(class_exists('WPGenius_Events_Ajax'))
 	WPGenius_Events_Ajax::init();

if(class_exists('WPGenius_Events_Admin') && is_admin())
 	WPGenius_Events_Admin::init();

if(class_exists('WPGenius_Events_Settings'))
 	WPGenius_Events_Settings::init();

register_activation_hook( 	__FILE__, array( $wbcdb, 'activate_events' 	) );
register_deactivation_hook( __FILE__, array( $wbcdb, 'deactivate_events' ) );
register_activation_hook( __FILE__, function(){ register_uninstall_hook( __FILE__, array( 'WPGenius_Events_DB', 'uninstall_events' ) ); });
