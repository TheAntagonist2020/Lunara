<?php
/**
 * The template for displaying all pages.
 *
 * @package Lunara
 * @since   1.0.0
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<main id="primary" class="site-main">
		<div class="container">
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="entry-thumbnail">
						<?php the_post_thumbnail( 'lunara-hero', array( 'alt' => get_the_title() ) ); ?>
					</div>
				<?php endif; ?>

				<div class="entry-content">
					<?php
					the_content();
					wp_link_pages( array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'lunara' ),
						'after'  => '</div>',
					) );
					?>
				</div><!-- .entry-content -->
			</article>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div><!-- .container -->
	</main><!-- #primary -->

<?php endwhile; ?>

<?php get_footer(); ?>
