<?php
/**
 *
 * @class       MyPlugin_Class_Shortcodes
 * @author      Team WPGenius (Makarand Mane)
 * @category    Admin
 * @package     myplugin/includes/widgets
 * @version     1.0
 */

 class MyPlugin_Class_Shortcodes extends MyPlugin_Class_API{

	public static $instance;
	public static function init(){

	    if ( is_null( self::$instance ) )
	        self::$instance = new MyPlugin_Class_Shortcodes();
	    return self::$instance;
	}

	public function __construct(){
		//add_shortcode( 'shortcode', array( $this, 'shortcode_callback') );
	} // END public function __construct
	
	public function shortcode_callback(){
		
	}
}