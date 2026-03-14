<?php
/**
 * The sidebar template.
 *
 * @package Lunara
 * @since   1.0.0
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>

<aside id="secondary" class="widget-area sidebar" role="complementary" aria-label="<?php esc_attr_e( 'Sidebar', 'lunara' ); ?>">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside><!-- #secondary -->
