<?php
/**
 *
 * @class       MyPlugin_Class_DB
 * @author      Team WPGenius (Makarand Mane)
 * @category    Admin
 * @package     myplugin/includes
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MyPlugin_Class_DB extends MyPlugin_Class_API{

	public function __construct(){
		/*
		* https://code.tutsplus.com/tutorials/custom-database-tables-creating-the-table--wp-28124
		* #blog #gist
		*/
		add_action( 'init', array( $this, 'myplugin_endpoint'), 1 );
		add_action( 'init', array( $this, 'myplugin_table'), 1 );
		add_action( 'switch_blog', array( $this, 'myplugin_table') );

	} // END public function __construct
	
	public function myplugin_endpoint(){
		add_rewrite_endpoint( 'completeClass', EP_ROOT );
		
		add_rewrite_rule( '^joinClass/([0-9]+)/?',	'index.php?joinClass=$matches[1]', 'top');
		add_rewrite_tag( '%joinClass%',		'([^&]+)' );
		
		if( !get_option('myplugin_permalinks_flushed') ) { 
			flush_rewrite_rules(false);
			update_option('myplugin_permalinks_flushed', 1);	 
		}
	}
	
	public function myplugin_table() {
		global $wpdb;
		$wpdb->myplugin_Events = "{$wpdb->prefix}myplugin_table";
	}

	public function activate_plugin() {
		$this->install_database();		
		//as Cron jobs are not scheduled while plugin activation, I have added a cron job. It invokes a hook function for update option.
		wp_schedule_single_event( time() + 30 , 'update_option_plugin_reminder_enabled', array(  0, get_option('myplugin_reminder_enabled' ), 'myplugin_reminder_enabled' ) );
		update_option('myplugin_permalinks_flushed', 0);
		flush_rewrite_rules();
	}
	
	public function deactivate_plugin() {
		$this->deactivate_cron();
		delete_option('myplugin_permalinks_flushed');
		flush_rewrite_rules();
	}
	
	public function uninstall_plugin() {		
		$this->delete_database();
		$this->delete_settings();
	}

	private function install_database(){
		global $wpdb;
		
		$table_name = $wpdb->prefix . "myplugin_table"; 
		$charset_collate = $wpdb->get_charset_collate();
		$sql = array();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		foreach($sql as $query )
			dbDelta( $query );
	}
	
	private function delete_database(){		
		if( get_option('myplugin_delete_db') ){		
			global $wpdb;
			$wpdb->query(  "DROP TABLE IF EXISTS ".$wpdb->prefix . "myplugin_table ;" );
		}		
	}
	
	private function delete_settings(){	
		if( get_option('myplugin_delete_settings') ){		
			unregister_setting( 'myplugin_api', 'event_per_page' );
			unregister_setting( 'myplugin_api', 'myplugin_reminder_enabled' );
			unregister_setting( 'myplugin_api', 'myplugin_reminder_interval' );
			unregister_setting( 'myplugin_api', 'myplugin_delete_db' );		
			unregister_setting( 'myplugin_api', 'myplugin_delete_settings' );
			delete_option( 'event_per_page' );
			delete_option( 'myplugin_reminder_enabled' );
			delete_option( 'myplugin_reminder_interval' );
			delete_option( 'myplugin_delete_db' );		
			delete_option( 'myplugin_delete_settings' );
		}
	}

}
$myplugin_db = new MyPlugin_Class_DB();