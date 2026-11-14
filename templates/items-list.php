<?php
/**
 * List of items with an optional "Load more" button.
 *
 * Override in your theme: copy to <theme>/wordpress-plugin-boilerplate/items-list.php.
 *
 * @package WPGenius\MyPlugin
 *
 * @var WP_Query $query     Items to show.
 * @var int      $per_page  Items per page.
 * @var string   $layout    'grid' or 'list'.
 * @var bool     $load_more Whether to show the "Load more" button.
 */

use WPGenius\MyPlugin\Api;

defined( 'ABSPATH' ) || exit;

if ( ! $query->have_posts() ) {
	echo '<p class="myplugin-empty">' . esc_html__( 'No items yet.', 'wordpress-plugin-boilerplate' ) . '</p>';
	return;
}
?>
<div class="myplugin-items">
	<div class="myplugin-items__list myplugin-items__list--<?php echo esc_attr( $layout ); ?>">
		<?php
		while ( $query->have_posts() ) {
			$query->the_post();
			echo Api::render_template( 'item.php', array( 'post' => get_post() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the template.
		}
		wp_reset_postdata();
		?>
	</div>

	<?php if ( $load_more && $query->max_num_pages > 1 ) : ?>
		<button type="button" class="myplugin-items__more" data-page="1" data-per-page="<?php echo esc_attr( (string) $per_page ); ?>">
			<?php esc_html_e( 'Load more', 'wordpress-plugin-boilerplate' ); ?>
		</button>
	<?php endif; ?>
</div>
