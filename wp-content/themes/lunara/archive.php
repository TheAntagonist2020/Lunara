<?php
/**
 * The template for displaying archive pages (categories, genres, decades, dates).
 *
 * @package Lunara
 * @since   1.0.0
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="container">
		<header class="archive-header">
			<?php the_archive_title( '<h1 class="archive-title">', '</h1>' ); ?>
			<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
		</header>

		<div class="content-sidebar-wrap">
			<div class="content-area">
				<?php if ( have_posts() ) : ?>
					<div class="posts-grid">
						<?php while ( have_posts() ) : the_post(); ?>
							<?php get_template_part( 'template-parts/content', get_post_type() ); ?>
						<?php endwhile; ?>
					</div>

					<?php the_posts_pagination( array(
						'mid_size'  => 2,
						'prev_text' => esc_html__( '&laquo; Previous', 'lunara' ),
						'next_text' => esc_html__( 'Next &raquo;', 'lunara' ),
					) ); ?>

				<?php else : ?>
					<div class="no-results">
						<p><?php esc_html_e( 'No posts found in this archive.', 'lunara' ); ?></p>
					</div>
				<?php endif; ?>
			</div><!-- .content-area -->

			<?php get_sidebar(); ?>
		</div><!-- .content-sidebar-wrap -->
	</div><!-- .container -->
</main><!-- #primary -->

<?php get_footer(); ?>
