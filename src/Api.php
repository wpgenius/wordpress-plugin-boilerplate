<?php
/**
 * Shared helpers used by every part of the plugin.
 *
 * Other classes extend Api, so inside them you can write self::get_setting( 'key' ).
 * Templates and block render files can call Api::render_template() directly.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin;

defined( 'ABSPATH' ) || exit;

/**
 * Common base class.
 */
class Api {

	/**
	 * Name of the one option that stores every setting (an array).
	 */
	const OPTION = 'myplugin_settings';

	/**
	 * Folder inside a theme where templates can be overridden.
	 */
	const THEME_TEMPLATE_DIR = 'wordpress-plugin-boilerplate';

	/*
	 * ------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------
	 */

	/**
	 * All settings: the saved values on top of the defaults from Admin\Settings::fields().
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION, array() );
		return array_merge( Admin\Settings::defaults(), is_array( $saved ) ? $saved : array() );
	}

	/**
	 * One setting.
	 *
	 * @param string $key      Field key from Admin\Settings::fields().
	 * @param mixed  $fallback Returned when the key is unknown.
	 * @return mixed
	 */
	public static function get_setting( $key, $fallback = null ) {
		$settings = self::get_settings();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
	}

	/*
	 * ------------------------------------------------------------------
	 * Security
	 * ------------------------------------------------------------------
	 */

	/**
	 * Check a request's nonce and the user's capability. Both are needed: the
	 * nonce proves the request came from your own page, the capability proves
	 * this user may do it.
	 *
	 * The capability is required on purpose, so it can't be forgotten. Use the
	 * one that fits the action: 'manage_options' for settings, 'edit_posts' for
	 * content, 'read' for any logged-in user. For an action open to visitors
	 * (wp_ajax_nopriv_*), don't use this helper: check only the nonce with
	 * check_ajax_referer(), and treat everything in the request as untrusted.
	 *
	 * @param string $action     Nonce action.
	 * @param string $capability Capability the current user must have.
	 * @param string $field      Request field holding the nonce.
	 * @return bool
	 */
	public static function verify_request( $action, $capability, $field = 'nonce' ) {
		if ( ! check_ajax_referer( $action, $field, false ) ) {
			return false;
		}
		return current_user_can( $capability );
	}

	/*
	 * ------------------------------------------------------------------
	 * Templates
	 * ------------------------------------------------------------------
	 */

	/**
	 * Full path of a template. A theme can override any file in templates/ by
	 * copying it to <theme>/wordpress-plugin-boilerplate/<same name>.
	 *
	 * @param string $name Template file, e.g. 'items-list.php'.
	 * @return string Empty if not found.
	 */
	public static function locate_template( $name ) {
		$name  = ltrim( $name, '/' );
		$theme = locate_template( self::THEME_TEMPLATE_DIR . '/' . $name );
		$file  = $theme ? $theme : MYPLUGIN_DIR_PATH . 'templates/' . $name;

		/**
		 * Filters the template file used by the plugin.
		 *
		 * @param string $file Full path.
		 * @param string $name Requested template name.
		 */
		$file = apply_filters( 'myplugin_template', $file, $name );

		return is_readable( $file ) ? $file : '';
	}

	/**
	 * Render a template and return its HTML. Each key of $args becomes a variable in the template.
	 *
	 * @param string               $name Template file, e.g. 'items-list.php'.
	 * @param array<string, mixed> $args Variables for the template.
	 * @return string
	 */
	public static function render_template( $name, array $args = array() ) {
		$file = self::locate_template( $name );
		if ( '' === $file ) {
			return '';
		}

		ob_start();
		( static function ( $myplugin_file, $args ) {
			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Templates read their variables by name.
			include $myplugin_file;
		} )( $file, $args );
		return (string) ob_get_clean();
	}

	/*
	 * ------------------------------------------------------------------
	 * Email
	 * ------------------------------------------------------------------
	 */

	/**
	 * Replace {placeholders} in a text, e.g. 'Hi {name}' with array( 'name' => 'Asha' ).
	 * Keys may be given with or without braces. Unknown placeholders are left as they are.
	 *
	 * @param string               $text Text with placeholders.
	 * @param array<string, mixed> $vars Placeholder values.
	 * @return string
	 */
	public static function replace_placeholders( $text, array $vars ) {
		$pairs = array();
		foreach ( $vars as $key => $value ) {
			$pairs[ '{' . trim( (string) $key, '{}' ) . '}' ] = (string) $value;
		}
		return strtr( (string) $text, $pairs );
	}

	/**
	 * Send an HTML email with {placeholders} replaced in the subject and body.
	 * {site_name} and {site_url} are always available.
	 *
	 * @param string|string[]      $to      Recipient(s).
	 * @param string               $subject Subject, may contain placeholders.
	 * @param string               $body    HTML body, may contain placeholders.
	 * @param array<string, mixed> $vars    Placeholder values.
	 * @return bool Whether wp_mail() accepted the email.
	 */
	public static function send_email( $to, $subject, $body, array $vars = array() ) {
		$vars = array_merge(
			array(
				'site_name' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				'site_url'  => home_url( '/' ),
			),
			$vars
		);

		return wp_mail(
			$to,
			wp_strip_all_tags( self::replace_placeholders( $subject, $vars ) ),
			wpautop( self::replace_placeholders( $body, $vars ) ),
			array( 'Content-Type: text/html; charset=UTF-8' )
		);
	}

	/*
	 * ------------------------------------------------------------------
	 * Assets
	 * ------------------------------------------------------------------
	 */

	/**
	 * URL of a file in the plugin, e.g. self::asset_url( 'assets/css/frontend.css' ).
	 *
	 * @param string $path Path relative to the plugin folder.
	 * @return string
	 */
	public static function asset_url( $path ) {
		return MYPLUGIN_DIR_URL . ltrim( $path, '/' );
	}

	/**
	 * Version string for cache busting: the file's modification time while
	 * WP_DEBUG is on (so edits show up at once), otherwise the plugin version.
	 *
	 * @param string $path Path relative to the plugin folder.
	 * @return string
	 */
	public static function asset_version( $path ) {
		$file = MYPLUGIN_DIR_PATH . ltrim( $path, '/' );
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && is_readable( $file ) ) {
			return (string) filemtime( $file );
		}
		return MYPLUGIN_VERSION;
	}
}
