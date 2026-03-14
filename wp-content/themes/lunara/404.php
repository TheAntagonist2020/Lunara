<?php
/**
 * The template for displaying 404 pages.
 *
 * @package Lunara
 * @since   1.0.0
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="container">
		<section class="error-404 not-found" style="padding: 4rem 0; text-align: center;">
			<header class="page-header">
				<h1 class="page-title"><?php esc_html_e( '404 — Page Not Found', 'lunara' ); ?></h1>
			</header>

			<div class="page-content">
				<p><?php esc_html_e( 'The page you\'re looking for doesn\'t seem to exist. Perhaps a film review search will help?', 'lunara' ); ?></p>
				<?php get_search_form(); ?>
			</div>
		</section>
	</div><!-- .container -->
</main><!-- #primary -->

<?php get_footer(); ?>
