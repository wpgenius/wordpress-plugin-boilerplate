<?php
/**
 * Front-end output of the "Items with live filter" block.
 *
 * The HTML is complete without JavaScript (every item is listed); view.js
 * then makes the search box filter it, using the data-wp-* directives below.
 *
 * Available variables: $attributes (block.json attributes), $content, $block.
 *
 * @package WPGenius\MyPlugin
 */

use WPGenius\MyPlugin\PostTypes\Example;

defined( 'ABSPATH' ) || exit;

$myplugin_posts  = Example::query( isset( $attributes['count'] ) ? (int) $attributes['count'] : 8 )->posts;
$myplugin_titles = array();

// Initial values of the derived state, so the HTML is right before view.js
// runs (and without JavaScript): with an empty search every item is visible.
wp_interactivity_state(
	'wordpress-plugin-boilerplate/interactive',
	array(
		'isVisible' => true,
		'isEmpty'   => false,
	)
);

foreach ( $myplugin_posts as $myplugin_post ) {
	$myplugin_titles[ $myplugin_post->ID ] = function_exists( 'mb_strtolower' ) ? mb_strtolower( get_the_title( $myplugin_post ) ) : strtolower( get_the_title( $myplugin_post ) );
}
?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes it. ?>
	data-wp-interactive="wordpress-plugin-boilerplate/interactive"
	<?php
	echo wp_interactivity_data_wp_context( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes it.
		array(
			'search' => '',
			'titles' => array_values( $myplugin_titles ),
		)
	);
	?>
>
	<?php if ( ! $myplugin_posts ) : ?>
		<p><?php esc_html_e( 'No items yet.', 'wordpress-plugin-boilerplate' ); ?></p>
	<?php else : ?>
		<label class="myplugin-filter__label">
			<?php esc_html_e( 'Filter items', 'wordpress-plugin-boilerplate' ); ?>
			<input type="search" class="myplugin-filter__input" data-wp-on--input="actions.updateSearch" placeholder="<?php esc_attr_e( 'Type to filter…', 'wordpress-plugin-boilerplate' ); ?>">
		</label>

		<ul class="myplugin-filter__list">
			<?php foreach ( $myplugin_posts as $myplugin_post ) : ?>
				<li
					<?php echo wp_interactivity_data_wp_context( array( 'title' => $myplugin_titles[ $myplugin_post->ID ] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes it. ?>
					data-wp-bind--hidden="!state.isVisible"
				>
					<a href="<?php echo esc_url( get_permalink( $myplugin_post ) ); ?>"><?php echo esc_html( get_the_title( $myplugin_post ) ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="myplugin-filter__empty" data-wp-bind--hidden="!state.isEmpty">
			<?php esc_html_e( 'No items match.', 'wordpress-plugin-boilerplate' ); ?>
		</p>
	<?php endif; ?>
</div>
