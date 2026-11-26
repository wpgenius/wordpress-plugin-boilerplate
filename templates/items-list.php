<?php
/**
 * List of items with an optional "Load more" button.
 *
 * Override in your theme: copy to <theme>/wordpress-plugin-boilerplate/items-list.php.
 *
 * @package WPGenius\MyPlugin
 *
 * Values in $args:
 *   'query'     WP_Query  Items to show.
 *   'per_page'  int       Items per page.
 *   'layout'    string    'grid' or 'list'.
 *   'load_more' bool      Whether to show the "Load more" button.
 *
 * @var array<string, mixed> $args
 */

use WPGenius\MyPlugin\Api;

defined( 'ABSPATH' ) || exit;

if ( ! $args['query']->have_posts() ) {
	echo '<p class="myplugin-empty">' . esc_html__( 'No items yet.', 'wordpress-plugin-boilerplate' ) . '</p>';
	return;
}
?>
<div class="myplugin-items">
	<div class="myplugin-items__list myplugin-items__list--<?php echo esc_attr( $args['layout'] ); ?>">
		<?php
		while ( $args['query']->have_posts() ) {
			$args['query']->the_post();
			echo wp_kses( Api::render_template( 'item.php', array( 'post' => get_post() ) ), Api::allowed_html() );
		}
		wp_reset_postdata();
		?>
	</div>

	<?php if ( $args['load_more'] && $args['query']->max_num_pages > 1 ) : ?>
		<button type="button" class="myplugin-items__more" data-page="1" data-per-page="<?php echo esc_attr( (string) $args['per_page'] ); ?>">
			<?php esc_html_e( 'Load more', 'wordpress-plugin-boilerplate' ); ?>
		</button>
	<?php endif; ?>
</div>
