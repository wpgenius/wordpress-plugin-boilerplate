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

$myplugin_count = isset( $attributes['count'] ) ? max( 1, (int) $attributes['count'] ) : 3;
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes it. ?>>
	<?php
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
	echo $myplugin_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the template.
	?>
</div>
