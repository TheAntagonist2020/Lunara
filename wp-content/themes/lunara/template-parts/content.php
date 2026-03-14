<?php
/**
 * Template part for displaying generic posts in a loop.
 *
 * @package Lunara
 * @since   1.0.0
 */
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
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
		</div>

		<?php if ( is_singular() ) : ?>
			<h1 class="review-card__title entry-title"><?php the_title(); ?></h1>
		<?php else : ?>
			<h2 class="review-card__title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h2>
		<?php endif; ?>

		<div class="review-card__excerpt">
			<?php echo wp_kses_post( get_the_excerpt() ); ?>
		</div>
	</div><!-- .review-card__body -->

	<div class="review-card__footer">
		<?php the_tags( '', ', ', '' ); ?>
	</div>

</article>
