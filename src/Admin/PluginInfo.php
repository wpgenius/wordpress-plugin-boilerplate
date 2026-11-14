<?php
/**
 * "View details" on the Plugins screen, filled from the bundled readme.txt.
 *
 * WordPress shows "View details" only for plugins it finds on WordPress.org.
 * For a private plugin this class answers that request itself, so the popup
 * shows the description, installation notes, FAQ and changelog from readme.txt.
 *
 * Delete this file (and its line in Plugin.php) if your plugin is on WordPress.org.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Admin;

use WPGenius\MyPlugin\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin details popup.
 */
class PluginInfo extends Api {

	/**
	 * Plugin folder name, used as the slug in the popup URL.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Plugin basename, e.g. my-plugin/my-plugin.php.
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Register the popup hooks.
	 */
	public function __construct() {
		$this->basename = plugin_basename( MYPLUGIN_FILE );
		$this->slug     = dirname( $this->basename );

		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 20, 3 );
		add_filter( 'plugin_row_meta', array( $this, 'add_details_link' ), 10, 2 );
	}

	/**
	 * Add the "View details" link under the plugin description.
	 *
	 * @param string[] $meta        Plugin row links.
	 * @param string   $plugin_file Plugin basename of the row.
	 * @return string[]
	 */
	public function add_details_link( $meta, $plugin_file ) {
		if ( $plugin_file !== $this->basename ) {
			return $meta;
		}

		$url = add_query_arg(
			array(
				'tab'       => 'plugin-information',
				'plugin'    => $this->slug,
				'TB_iframe' => 'true',
				'width'     => 772,
				'height'    => 788,
			),
			self_admin_url( 'plugin-install.php' )
		);

		$meta[] = sprintf(
			'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s">%s</a>',
			esc_url( $url ),
			esc_attr__( 'More information about this plugin', 'wordpress-plugin-boilerplate' ),
			esc_html__( 'View details', 'wordpress-plugin-boilerplate' )
		);

		return $meta;
	}

	/**
	 * Answer the plugin-information request for this plugin.
	 *
	 * @param mixed  $result Result so far.
	 * @param string $action API action.
	 * @param object $args   API arguments.
	 * @return mixed
	 */
	public function plugins_api( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $this->slug !== $args->slug ) {
			return $result;
		}

		$header = get_file_data(
			MYPLUGIN_FILE,
			array(
				'name'         => 'Plugin Name',
				'homepage'     => 'Plugin URI',
				'version'      => 'Version',
				'author'       => 'Author',
				'author_uri'   => 'Author URI',
				'requires'     => 'Requires at least',
				'requires_php' => 'Requires PHP',
			)
		);
		$readme = self::parse_readme( MYPLUGIN_DIR_PATH . 'readme.txt' );

		$info = array(
			'name'         => $header['name'],
			'slug'         => $this->slug,
			'version'      => $header['version'],
			'author'       => $header['author_uri'] ? sprintf( '<a href="%s">%s</a>', esc_url( $header['author_uri'] ), esc_html( $header['author'] ) ) : esc_html( $header['author'] ),
			'homepage'     => $header['homepage'],
			'requires'     => $header['requires'],
			'requires_php' => $header['requires_php'],
			'last_updated' => gmdate( 'Y-m-d H:i:s', (int) filemtime( MYPLUGIN_FILE ) ),
			'sections'     => $readme['sections'],
			'external'     => true,
		);

		if ( ! empty( $readme['headers']['tested'] ) ) {
			$info['tested'] = $readme['headers']['tested'];
		}

		return (object) $info;
	}

	/**
	 * Read the headers and sections of a readme.txt.
	 *
	 * @param string $path Full path to readme.txt.
	 * @return array{headers: array<string, string>, sections: array<string, string>}
	 */
	public static function parse_readme( $path ) {
		$data = array(
			'headers'  => array(),
			'sections' => array(),
		);

		if ( ! is_readable( $path ) ) {
			return $data;
		}

		$content = str_replace( array( "\r\n", "\r" ), "\n", (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
		$parts   = preg_split( '/^==\s*([^=].*?)\s*==\s*$/m', $content, -1, PREG_SPLIT_DELIM_CAPTURE );

		if ( preg_match( '/^Tested up to:\s*(.+)$/mi', $parts[0], $match ) ) {
			$data['headers']['tested'] = trim( $match[1] );
		}

		$count = count( $parts );
		for ( $i = 1; $i < $count; $i += 2 ) {
			$key  = str_replace( '-', '_', sanitize_title( $parts[ $i ] ) );
			$key  = 'frequently_asked_questions' === $key ? 'faq' : $key;
			$html = self::to_html( isset( $parts[ $i + 1 ] ) ? $parts[ $i + 1 ] : '' );
			if ( '' !== $html ) {
				$data['sections'][ $key ] = $html;
			}
		}

		return $data;
	}

	/**
	 * Convert readme markup (= headings =, lists, paragraphs) to HTML.
	 *
	 * @param string $text Readme markup.
	 * @return string
	 */
	private static function to_html( $text ) {
		$html = '';
		$para = array();
		$list = '';

		$close = static function () use ( &$html, &$para, &$list ) {
			if ( $para ) {
				$html .= '<p>' . implode( ' ', $para ) . "</p>\n";
				$para  = array();
			}
			if ( $list ) {
				$html .= "</{$list}>\n";
				$list  = '';
			}
		};

		foreach ( explode( "\n", trim( $text ) ) as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				$close();
			} elseif ( preg_match( '/^=\s*(.+?)\s*=$/', $line, $m ) ) {
				$close();
				$html .= '<h4>' . self::inline( $m[1] ) . "</h4>\n";
			} elseif ( preg_match( '/^(\*|-|\d+\.)\s+(.+)$/', $line, $m ) ) {
				$type = ctype_digit( rtrim( $m[1], '.' ) ) ? 'ol' : 'ul';
				if ( $list !== $type ) {
					$close();
					$html .= "<{$type}>\n";
					$list  = $type;
				}
				$html .= '<li>' . self::inline( $m[2] ) . "</li>\n";
			} else {
				if ( $list ) {
					$close();
				}
				$para[] = self::inline( $line );
			}
		}
		$close();

		return trim( $html );
	}

	/**
	 * Escape a line and render `code`, **bold** and [links](https://…).
	 *
	 * @param string $text Readme text.
	 * @return string
	 */
	private static function inline( $text ) {
		$text = esc_html( $text );
		$text = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $text );
		$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
		return preg_replace_callback(
			'/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/',
			static function ( $m ) {
				return '<a href="' . esc_url( wp_specialchars_decode( $m[2] ) ) . '">' . $m[1] . '</a>';
			},
			$text
		);
	}
}
