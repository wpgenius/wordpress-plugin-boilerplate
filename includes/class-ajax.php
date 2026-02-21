<?php
/**
 *
 * @class       MyPlugin_Class_Ajax
 * @author      Team WPGenius (Makarand Mane)
 * @category    Admin
 * @package     myplugin/includes
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MyPlugin_Class_Ajax extends MyPlugin_Class_API{
	public static $instance;
	public static function init(){

	    if ( is_null( self::$instance ) )
	        self::$instance = new MyPlugin_Class_Ajax();
	    return self::$instance;
	}

	private function __construct(){
			
		add_action('wp_ajax_rsvp_apply',				array( $this, 'rsvp_apply' ) );
		
	} // END public function __construct

	
	//Schedule class and insert class to db
	public function rsvp_apply(){
		
		$this->security_check( 'event_security_nonce' );
    	if( $_POST['event_id'] ){
			
			//write code
		
		}  else {
			wp_send_json_error( array( 'msg'=>   __( 'Invalid user.', 'wordpress-plugin-boilerplate' ) ) );
		}	
	}

}// END class MyPlugin_Class_Ajax