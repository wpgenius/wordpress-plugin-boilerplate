<?php
/**
 * Scheduled jobs (WP-Cron).
 *
 * Example: a daily report email, on/off and time set on the settings page.
 * The job is (re)scheduled on activation and whenever the settings are saved,
 * and removed on deactivation.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin;

use WPGenius\MyPlugin\PostTypes\Example;

defined( 'ABSPATH' ) || exit;

/**
 * Cron jobs.
 */
class Cron extends Api {

	/**
	 * Hook name of the daily report job.
	 */
	const REPORT_HOOK = 'myplugin_daily_report';

	/**
	 * Register the job and reschedule it when the settings change.
	 */
	public function __construct() {
		add_action( self::REPORT_HOOK, array( $this, 'send_report' ) );
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'schedule' ) );
		add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'schedule' ) );
	}

	/**
	 * Schedule (or unschedule) the report from the current settings.
	 *
	 * @return void
	 */
	public static function schedule() {
		self::unschedule();

		if ( ! self::get_setting( 'report_enabled' ) ) {
			return;
		}

		wp_schedule_event( self::next_run( self::get_setting( 'report_time' ) ), 'daily', self::REPORT_HOOK );
	}

	/**
	 * Remove every scheduled run of the jobs.
	 *
	 * @return void
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::REPORT_HOOK );
	}

	/**
	 * Timestamp of the next HH:MM in the site's time zone.
	 *
	 * @param string $time 24-hour time, e.g. '08:00'.
	 * @return int
	 */
	public static function next_run( $time ) {
		$parts = explode( ':', preg_match( '/^\d{2}:\d{2}$/', (string) $time ) ? $time : '08:00' );
		$next  = new \DateTimeImmutable( 'now', wp_timezone() );
		$next  = $next->setTime( (int) $parts[0], (int) $parts[1] );

		if ( $next->getTimestamp() <= time() ) {
			$next = $next->modify( '+1 day' );
		}
		return $next->getTimestamp();
	}

	/**
	 * The job: email how many items were published in the last day.
	 *
	 * @return void
	 */
	public function send_report() {
		$since = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		$query = new \WP_Query(
			array(
				'post_type'      => Example::NAME,
				'post_status'    => 'publish',
				'date_query'     => array(
					array(
						'after'  => $since,
						'column' => 'post_date_gmt',
					),
				),
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => false,
			)
		);

		$to = self::get_setting( 'report_to' );

		self::send_email(
			$to ? $to : get_option( 'admin_email' ),
			self::get_setting( 'report_subject' ),
			self::get_setting( 'report_body' ),
			array(
				'count' => (int) $query->found_posts,
				'date'  => wp_date( get_option( 'date_format' ), time() - DAY_IN_SECONDS ),
			)
		);

		Installer::log( sprintf( 'Daily report sent (%d items).', (int) $query->found_posts ) );
	}
}
