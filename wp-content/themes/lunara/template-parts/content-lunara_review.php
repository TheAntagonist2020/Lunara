<?php
/**
 * Template part for displaying a film review card in loop context.
 *
 * Called via get_template_part( 'template-parts/content', 'lunara_review' )
 *
 * @package Lunara
 * @since   1.0.0
 */

$rating    = get_post_meta( get_the_ID(), '_lunara_rating', true );
$year      = get_post_meta( get_the_ID(), '_lunara_year', true );
$director  = get_post_meta( get_the_ID(), '_lunara_director', true );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'review-card' ); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="review-card__thumbnail">
			<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
				<?php the_post_thumbnail( 'lunara-card', array( 'alt' => '' ) ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="review-card__body">
		<div class="review-card__meta">
			<?php if ( $year ) : ?>
				<span class="year"><?php echo esc_html( $year ); ?></span>
			<?php endif; ?>

			<?php if ( $rating !== '' ) : ?>
				<span class="rating"><?php echo esc_html( $rating ); ?>/10</span>
				<?php echo wp_kses_post( lunara_star_rating( (float) $rating ) ); ?>
			<?php endif; ?>
		</div>

		<h2 class="review-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<?php if ( $director ) : ?>
			<p class="review-card__director text-muted" style="font-size:0.875rem; margin-bottom: 0.5rem;">
				<?php echo esc_html__( 'Dir. ', 'lunara' ) . esc_html( $director ); ?>
			</p>
		<?php endif; ?>

		<div class="review-card__excerpt">
			<?php echo wp_kses_post( get_the_excerpt() ); ?>
		</div>
	</div><!-- .review-card__body -->

	<div class="review-card__footer">
		<?php lunara_genre_tags( get_the_ID() ); ?>
	</div>

</article>
