<?php
/**
 * The template for displaying all single posts and film reviews.
 *
 * @package Lunara
 * @since   1.0.0
 */

get_header();

while ( have_posts() ) :
	the_post();

	$post_type = get_post_type();
	$is_review = ( 'lunara_review' === $post_type );

	// Film review meta.
	$director  = $is_review ? get_post_meta( get_the_ID(), '_lunara_director', true ) : '';
	$year      = $is_review ? get_post_meta( get_the_ID(), '_lunara_year', true ) : '';
	$runtime   = $is_review ? (int) get_post_meta( get_the_ID(), '_lunara_runtime', true ) : 0;
	$rating    = $is_review ? get_post_meta( get_the_ID(), '_lunara_rating', true ) : '';
	$certif    = $is_review ? get_post_meta( get_the_ID(), '_lunara_certification', true ) : '';
	$country   = $is_review ? get_post_meta( get_the_ID(), '_lunara_country', true ) : '';
	$streaming = $is_review ? get_post_meta( get_the_ID(), '_lunara_streaming', true ) : '';
	$osc_noms  = $is_review ? (int) get_post_meta( get_the_ID(), '_lunara_oscars_nominations', true ) : 0;
	$osc_wins  = $is_review ? (int) get_post_meta( get_the_ID(), '_lunara_oscars_wins', true ) : 0;
	?>

	<main id="primary" class="site-main">
		<div class="container">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-article' ); ?>>

				<header class="single-post-header">
					<h1 class="review-film-title"><?php the_title(); ?></h1>

					<?php if ( $is_review ) : ?>
						<div class="review-meta">
							<?php if ( $director ) : ?>
								<span class="director">
									<strong><?php esc_html_e( 'Dir.', 'lunara' ); ?></strong> <?php echo esc_html( $director ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $year ) : ?>
								<span class="year"><?php echo esc_html( $year ); ?></span>
							<?php endif; ?>

							<?php if ( $runtime ) : ?>
								<span class="runtime"><?php echo esc_html( lunara_format_runtime( $runtime ) ); ?></span>
							<?php endif; ?>

							<?php if ( $certif ) : ?>
								<span class="certification"><?php echo esc_html( $certif ); ?></span>
							<?php endif; ?>

							<?php if ( $country ) : ?>
								<span class="country"><?php echo esc_html( $country ); ?></span>
							<?php endif; ?>

							<?php if ( $rating !== '' ) : ?>
								<span class="rating-large">
									<?php echo wp_kses_post( lunara_star_rating( (float) $rating ) ); ?>
									<span class="rating-number"><?php echo esc_html( $rating ); ?>/10</span>
								</span>
							<?php endif; ?>
						</div><!-- .review-meta -->
					<?php else : ?>
						<div class="review-meta">
							<span class="posted-on">
								<?php
								printf(
									/* translators: %s: date */
									esc_html__( 'Published %s', 'lunara' ),
									'<time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date() ) . '</time>'
								);
								?>
							</span>
							<span class="byline">
								<?php esc_html_e( 'by', 'lunara' ); ?> <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php the_author(); ?></a>
							</span>
						</div>
					<?php endif; ?>
				</header><!-- .single-post-header -->

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="review-featured-image">
						<?php the_post_thumbnail( $is_review ? 'lunara-hero' : 'large', array( 'alt' => get_the_title() ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $is_review ) : ?>
					<div class="film-info-box">
						<h3><?php esc_html_e( 'Film Details', 'lunara' ); ?></h3>
						<dl>
							<?php if ( $director ) : ?>
								<dt><?php esc_html_e( 'Director', 'lunara' ); ?></dt>
								<dd><?php echo esc_html( $director ); ?></dd>
							<?php endif; ?>
							<?php if ( $year ) : ?>
								<dt><?php esc_html_e( 'Year', 'lunara' ); ?></dt>
								<dd><?php echo esc_html( $year ); ?></dd>
							<?php endif; ?>
							<?php if ( $runtime ) : ?>
								<dt><?php esc_html_e( 'Runtime', 'lunara' ); ?></dt>
								<dd><?php echo esc_html( lunara_format_runtime( $runtime ) ); ?></dd>
							<?php endif; ?>
							<?php if ( $certif ) : ?>
								<dt><?php esc_html_e( 'Certificate', 'lunara' ); ?></dt>
								<dd><?php echo esc_html( $certif ); ?></dd>
							<?php endif; ?>
							<?php if ( $country ) : ?>
								<dt><?php esc_html_e( 'Country', 'lunara' ); ?></dt>
								<dd><?php echo esc_html( $country ); ?></dd>
							<?php endif; ?>
							<?php if ( $streaming ) : ?>
								<dt><?php esc_html_e( 'Streaming', 'lunara' ); ?></dt>
								<dd><?php echo esc_html( $streaming ); ?></dd>
							<?php endif; ?>
							<?php if ( $osc_noms ) : ?>
								<dt><?php esc_html_e( 'Oscar Noms', 'lunara' ); ?></dt>
								<dd><?php echo esc_html( $osc_noms ); ?></dd>
							<?php endif; ?>
							<?php if ( $osc_wins ) : ?>
								<dt><?php esc_html_e( 'Oscar Wins', 'lunara' ); ?></dt>
								<dd><?php echo esc_html( $osc_wins ); ?></dd>
							<?php endif; ?>
						</dl>
					</div><!-- .film-info-box -->
				<?php endif; ?>

				<div class="entry-content">
					<?php
					the_content( sprintf(
						wp_kses(
							/* translators: %s: Name of current post */
							__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'lunara' ),
							array( 'span' => array( 'class' => array() ) )
						),
						wp_kses_post( get_the_title() )
					) );

					wp_link_pages( array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'lunara' ),
						'after'  => '</div>',
					) );
					?>
				</div><!-- .entry-content -->

				<?php if ( $is_review ) : ?>
					<footer class="entry-footer">
						<div class="entry-genres">
							<?php lunara_genre_tags(); ?>
						</div>
					</footer>
				<?php else : ?>
					<footer class="entry-footer">
						<?php
						the_tags(
							'<div class="entry-tags">' . esc_html__( 'Tags: ', 'lunara' ),
							', ',
							'</div>'
						);
						?>
					</footer>
				<?php endif; ?>

			</article><!-- #post-<?php the_ID(); ?> -->

			<?php
			// Previous/next post navigation.
			the_post_navigation( array(
				'prev_text' => '<span class="nav-subtitle">' . esc_html__( 'Previous', 'lunara' ) . '</span><span class="nav-title">%title</span>',
				'next_text' => '<span class="nav-subtitle">' . esc_html__( 'Next', 'lunara' ) . '</span><span class="nav-title">%title</span>',
			) );

			// Comments.
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>

		</div><!-- .container -->
	</main><!-- #primary -->

<?php endwhile; ?>

<?php get_footer(); ?>
