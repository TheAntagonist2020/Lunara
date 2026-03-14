<?php
/**
 * The template for displaying the footer.
 *
 * @package Lunara
 * @since   1.0.0
 */
?>

</div><!-- #page -->

<footer id="colophon" class="site-footer">
	<div class="container">
		<?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) ) : ?>
			<div class="footer-widgets">
				<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
					<div class="footer-widget-area">
						<?php dynamic_sidebar( 'footer-1' ); ?>
					</div>
				<?php endif; ?>

				<?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
					<div class="footer-widget-area">
						<?php dynamic_sidebar( 'footer-2' ); ?>
					</div>
				<?php endif; ?>

				<?php if ( is_active_sidebar( 'footer-3' ) ) : ?>
					<div class="footer-widget-area">
						<?php dynamic_sidebar( 'footer-3' ); ?>
					</div>
				<?php endif; ?>
			</div><!-- .footer-widgets -->
		<?php endif; ?>

		<div class="footer-bottom">
			<p class="copyright">
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>.
				<?php esc_html_e( 'All rights reserved.', 'lunara' ); ?>
			</p>

			<?php
			wp_nav_menu( array(
				'theme_location' => 'footer',
				'container'      => 'nav',
				'container_class'=> 'footer-nav',
				'menu_class'     => 'footer-menu',
				'depth'          => 1,
				'fallback_cb'    => false,
			) );
			?>

			<p class="powered-by">
				<?php
				printf(
					/* translators: %s: WordPress link */
					esc_html__( 'Proudly powered by %s', 'lunara' ),
					'<a href="https://wordpress.org">WordPress</a>'
				);
				?>
			</p>
		</div><!-- .footer-bottom -->
	</div><!-- .container -->
</footer><!-- #colophon -->

<?php wp_footer(); ?>

</body>
</html>
