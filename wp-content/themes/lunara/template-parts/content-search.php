<?php
/**
 * Template part for search result items.
 *
 * @package Lunara
 * @since   1.0.0
 */

$post_type  = get_post_type();
$is_review  = ( 'lunara_review' === $post_type );
$rating     = $is_review ? get_post_meta( get_the_ID(), '_lunara_rating', true ) : '';
$year       = $is_review ? get_post_meta( get_the_ID(), '_lunara_year', true ) : '';
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'review-card' ); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="review-card__thumbnail">
			<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
				<?php the_post_thumbnail( 'lunara-poster', array( 'alt' => '' ) ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="review-card__body">
		<div class="review-card__meta">
			<?php if ( $year ) : ?>
				<span><?php echo esc_html( $year ); ?></span>
			<?php endif; ?>
			<?php if ( $rating !== '' ) : ?>
				<span class="rating"><?php echo esc_html( $rating ); ?>/10</span>
			<?php endif; ?>
			<?php if ( ! $is_review ) : ?>
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
			<?php endif; ?>
		</div>

		<h2 class="review-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<div class="review-card__excerpt">
			<?php echo wp_kses_post( get_the_excerpt() ); ?>
		</div>
	</div><!-- .review-card__body -->

</article>
