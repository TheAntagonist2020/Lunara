<?php
/**
 * The template for displaying search results pages.
 *
 * @package Lunara
 * @since   1.0.0
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="container">
		<header class="page-header">
			<h1 class="page-title">
				<?php
				printf(
					/* translators: %s: search query */
					esc_html__( 'Search results for: %s', 'lunara' ),
					'<span>' . esc_html( get_search_query() ) . '</span>'
				);
				?>
			</h1>
		</header>

		<div class="content-sidebar-wrap">
			<div class="content-area">
				<?php if ( have_posts() ) : ?>
					<div class="posts-grid">
						<?php while ( have_posts() ) : the_post(); ?>
							<?php get_template_part( 'template-parts/content', 'search' ); ?>
						<?php endwhile; ?>
					</div>

					<?php the_posts_pagination( array(
						'mid_size'  => 2,
						'prev_text' => esc_html__( '&laquo; Previous', 'lunara' ),
						'next_text' => esc_html__( 'Next &raquo;', 'lunara' ),
					) ); ?>

				<?php else : ?>
					<div class="no-results">
						<h2><?php esc_html_e( 'No results found', 'lunara' ); ?></h2>
						<p><?php esc_html_e( 'Sorry, no results matched your search. Please try different keywords.', 'lunara' ); ?></p>
						<?php get_search_form(); ?>
					</div>
				<?php endif; ?>
			</div><!-- .content-area -->

			<?php get_sidebar(); ?>
		</div><!-- .content-sidebar-wrap -->
	</div><!-- .container -->
</main><!-- #primary -->

<?php get_footer(); ?>
