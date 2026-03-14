<?php
/**
 * The main template file.
 *
 * Used as a fallback when no more-specific template is found.
 *
 * @package Lunara
 * @since   1.0.0
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="container">
		<?php if ( is_home() && ! is_front_page() ) : ?>
			<header class="page-header">
				<h1 class="page-title"><?php single_post_title(); ?></h1>
			</header>
		<?php endif; ?>

		<div class="content-sidebar-wrap">
			<div class="content-area">
				<?php if ( have_posts() ) : ?>
					<div class="posts-grid">
						<?php while ( have_posts() ) : the_post(); ?>
							<?php get_template_part( 'template-parts/content', get_post_type() ); ?>
						<?php endwhile; ?>
					</div>

					<?php the_posts_pagination( array(
						'mid_size'           => 2,
						'prev_text'          => esc_html__( '&laquo; Previous', 'lunara' ),
						'next_text'          => esc_html__( 'Next &raquo;', 'lunara' ),
						'screen_reader_text' => esc_html__( 'Posts navigation', 'lunara' ),
					) ); ?>

				<?php else : ?>
					<div class="no-results">
						<h2><?php esc_html_e( 'Nothing Found', 'lunara' ); ?></h2>
						<p><?php esc_html_e( 'It looks like nothing was found at this location.', 'lunara' ); ?></p>
					</div>
				<?php endif; ?>
			</div><!-- .content-area -->

			<?php get_sidebar(); ?>
		</div><!-- .content-sidebar-wrap -->
	</div><!-- .container -->
</main><!-- #primary -->

<?php
get_footer();
