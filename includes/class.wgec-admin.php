<?php
/**
 *
 * @class       WPGenius_Events_Admin
 * @author      Team WPGenius (Makarand Mane)
 * @category    Admin
 * @package     wpgenius-events-calendar/includes
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( !defined( 'BACKEND_URL' ) )
	define( "BACKEND_URL", get_bloginfo('url').'/wp-admin/' ); 

class WPGenius_Events_Admin extends WPGenius_Events_API{

	public static $instance;
	public $wgec_screen;
	public static function init(){

	    if ( is_null( self::$instance ) )
	        self::$instance = new WPGenius_Events_Admin();
	    return self::$instance;
	}

	private function __construct(){
		//Admin scripts
		add_action('admin_enqueue_scripts', array($this,'events_dashboard_scripts') );
		add_action('admin_menu', 			array($this,'events_menu'),	10);
		add_filter('set-screen-option', 	array($this,'wgec_set_screen_option'), 10,	3);	
		
	} // END public function __construct
	
	

	public function events_dashboard_scripts( $hook_suffix ) {
		
		//All Events - dashboard
		if( $hook_suffix === $this->wgec_screen ) {
			wp_enqueue_style( 'wgec-admin', WGEC_DIR_URL.'assets/css/style-admin.css' );
			wp_enqueue_script( 'wgec-admin', WGEC_DIR_URL.'assets/js/wgec-admin.js' ,array( 'jquery' ));
		}

	}

	/*************END**************/
	function events_menu(){
		add_action( "load-".$this->wgec_screen, array( $this,'events_screen_options' ) );
	}

	public function events_screen_options(){

		$screen = get_current_screen();
 
		if(!is_object($screen) || $screen->id != $this->wgec_screen)
			return;
	 
		$args = array(
			'label' => __('Events per page', 'wpgenius-events-calendar'),
			'default' => 10,
			'option' => 'events_per_page'
		);
		add_screen_option( 'per_page', $args );
	
	}

	public function wgec_set_screen_option( $status, $option, $value ){
		if ( 'events_per_page' == $option ) return $value;
	}
	

} // END class WPGenius_Events_Admin