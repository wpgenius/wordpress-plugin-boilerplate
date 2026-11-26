<?php
/**
 * One item in the list (also returned by the "Load more" AJAX call).
 *
 * Override in your theme: copy to <theme>/wordpress-plugin-boilerplate/item.php.
 *
 * @package WPGenius\MyPlugin
 *
 * Values in $args:
 *   'post'  WP_Post  The item.
 *
 * @var array<string, mixed> $args
 */

use WPGenius\MyPlugin\Api;

defined( 'ABSPATH' ) || exit;
?>
<article class="myplugin-item">
	<?php if ( has_post_thumbnail( $args['post'] ) ) : ?>
		<a class="myplugin-item__image" href="<?php echo esc_url( get_permalink( $args['post'] ) ); ?>">
			<?php echo wp_kses( get_the_post_thumbnail( $args['post'], 'medium_large' ), Api::allowed_html() ); ?>
		</a>
	<?php endif; ?>
	<h3 class="myplugin-item__title">
		<a href="<?php echo esc_url( get_permalink( $args['post'] ) ); ?>"><?php echo esc_html( get_the_title( $args['post'] ) ); ?></a>
	</h3>
	<div class="myplugin-item__excerpt"><?php echo wp_kses_post( get_the_excerpt( $args['post'] ) ); ?></div>
</article>
