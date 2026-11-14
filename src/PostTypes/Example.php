<?php
/**
 * Example post type "Item" with a category taxonomy and a thumbnail column.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\PostTypes;

use WPGenius\MyPlugin\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Item post type.
 */
class Example extends Api {

	/**
	 * Post type name (max 20 characters, lowercase, no spaces).
	 */
	const NAME = 'myplugin_item';

	/**
	 * Taxonomy name (max 32 characters).
	 */
	const TAXONOMY = 'myplugin_item_category';

	/**
	 * Admin list hooks.
	 */
	public function __construct() {
		add_filter( 'manage_' . self::NAME . '_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_' . self::NAME . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Register the post type and its taxonomy.
	 *
	 * @return void
	 */
	public static function register() {
		register_post_type(
			self::NAME,
			array(
				'labels'       => array(
					'name'          => __( 'Items', 'wordpress-plugin-boilerplate' ),
					'singular_name' => __( 'Item', 'wordpress-plugin-boilerplate' ),
					'add_new_item'  => __( 'Add new item', 'wordpress-plugin-boilerplate' ),
					'edit_item'     => __( 'Edit item', 'wordpress-plugin-boilerplate' ),
					'all_items'     => __( 'All items', 'wordpress-plugin-boilerplate' ),
					'search_items'  => __( 'Search items', 'wordpress-plugin-boilerplate' ),
					'not_found'     => __( 'No items found.', 'wordpress-plugin-boilerplate' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true, // Needed for the block editor.
				'menu_icon'    => 'dashicons-portfolio',
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'rewrite'      => array( 'slug' => 'items' ),
			)
		);

		register_taxonomy(
			self::TAXONOMY,
			self::NAME,
			array(
				'labels'            => array(
					'name'          => __( 'Item categories', 'wordpress-plugin-boilerplate' ),
					'singular_name' => __( 'Item category', 'wordpress-plugin-boilerplate' ),
				),
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'item-category' ),
			)
		);
	}

	/**
	 * Latest published items, used by the shortcode, the block and the REST route (Load more).
	 *
	 * @param int $per_page Items per page.
	 * @param int $page     Page number, from 1.
	 * @return \WP_Query
	 */
	public static function query( $per_page, $page = 1 ) {
		return new \WP_Query(
			array(
				'post_type'           => self::NAME,
				'post_status'         => 'publish',
				'posts_per_page'      => max( 1, (int) $per_page ),
				'paged'               => max( 1, (int) $page ),
				'ignore_sticky_posts' => true,
			)
		);
	}

	/**
	 * Add a thumbnail column after the checkbox.
	 *
	 * @param string[] $columns Column titles by key.
	 * @return string[]
	 */
	public function add_columns( $columns ) {
		return array_slice( $columns, 0, 1, true )
			+ array( 'myplugin_thumbnail' => __( 'Image', 'wordpress-plugin-boilerplate' ) )
			+ array_slice( $columns, 1, null, true );
	}

	/**
	 * Output a custom column cell.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		if ( 'myplugin_thumbnail' === $column ) {
			$image = get_the_post_thumbnail( $post_id, array( 48, 48 ) );
			echo $image ? $image : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core markup.
		}
	}
}
