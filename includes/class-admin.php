<?php
/**
 *
 * @class       MyPlugin_Class_Admin
 * @author      Team WPGenius (Makarand Mane)
 * @category    Admin
 * @package     wordpress-plugin-boilerplate/includes
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( !defined( 'MYPLUGIN_BACKEND_URL' ) )
	define( "MYPLUGIN_BACKEND_URL", get_bloginfo('url').'/wp-admin/' ); 

class MyPlugin_Class_Admin extends MyPlugin_Class_API{

	public static $instance;
	public $myplugin_screen;
	public static function init(){

	    if ( is_null( self::$instance ) )
	        self::$instance = new MyPlugin_Class_Admin();
	    return self::$instance;
	}

	private function __construct(){
		//Admin scripts
		add_action('admin_enqueue_scripts', array($this,'dashboard_scripts') );
		add_action('admin_menu', 			array($this,'admin_menu'),	10);
		add_filter('set-screen-option', 	array($this,'screen_options'), 10,	3);	
		
	} // END public function __construct
	
	

	public function dashboard_scripts( $hook_suffix ) {
		
		//All Events - dashboard
		if( $hook_suffix === $this->myplugin_screen ) {
			wp_enqueue_style( 'myplugin-admin', MYPLUGIN_DIR_URL.'assets/css/style-admin.css' );
			wp_enqueue_script( 'myplugin-admin', MYPLUGIN_DIR_URL.'assets/js/admin.js' ,array( 'jquery' ));
		}

	}

	/*************END**************/
	function admin_menu(){
		add_action( "load-".$this->myplugin_screen, array( $this,'myplugin_screen_options' ) );
	}

	public function myplugin_screen_options(){

		$screen = get_current_screen();
 
		if(!is_object($screen) || $screen->id != $this->myplugin_screen)
			return;
	 
		$args = array(
			'label' => __('Events per page', 'wordpress-plugin-boilerplate'),
			'default' => 10,
			'option' => 'myplugin_per_page'
		);
		add_screen_option( 'per_page', $args );
	
	}

	public function screen_options( $status, $option, $value ){
		if ( 'myplugin_per_page' == $option ) return $value;
	}
	

} // END class MyPlugin_Class_Admin