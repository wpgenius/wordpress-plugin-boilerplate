<?php
/**
 * Front-end output of the "Latest items" block.
 *
 * Available variables: $attributes (block.json attributes), $content, $block.
 *
 * @package WPGenius\MyPlugin
 */

use WPGenius\MyPlugin\Api;
use WPGenius\MyPlugin\PostTypes\Example;

defined( 'ABSPATH' ) || exit;

$myplugin_count = Example::per_page( isset( $attributes['count'] ) ? $attributes['count'] : 3 );

// Same template as the [myplugin_items] shortcode, without "Load more".
$myplugin_html = Api::render_template(
	'items-list.php',
	array(
		'query'     => Example::query( $myplugin_count ),
		'per_page'  => $myplugin_count,
		'layout'    => isset( $attributes['layout'] ) && 'list' === $attributes['layout'] ? 'list' : 'grid',
		'load_more' => false,
	)
);

// Escaped late, as printed: only the tags and attributes in Api::allowed_html().
echo wp_kses( '<div ' . get_block_wrapper_attributes() . '>' . $myplugin_html . '</div>', Api::allowed_html() );
