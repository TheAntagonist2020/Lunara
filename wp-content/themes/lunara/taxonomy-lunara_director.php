<?php
/**
 * Director archive template
 */
get_header();
$term = get_queried_object();
?>
<main id="primary" class="site-main lunara-archive-page">
    <section class="lunara-home-section">
        <div class="lunara-home-section-head">
            <div>
                <p class="lunara-home-hero-kicker">Director Archive</p>
                <h1 class="lunara-home-section-title"><?php echo esc_html( $term->name ); ?></h1>
            </div>
        </div>
        <?php if ( have_posts() ) : ?>
            <div class="lunara-review-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article class="lunara-review-grid-card">
                        <a class="lunara-review-grid-link" href="<?php the_permalink(); ?>">
                            <div class="lunara-review-grid-poster-wrap">
                                <?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium_large', array( 'class' => 'lunara-review-grid-poster', 'loading' => 'lazy' ) ); } ?>
                                <?php $score = get_post_meta( get_the_ID(), '_lunara_score', true ); if ( $score ) : ?><span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span><?php endif; ?>
                            </div>
                            <div class="lunara-review-grid-copy"><h2 class="lunara-review-grid-title"><?php the_title(); ?></h2><p class="lunara-review-grid-meta"><?php echo esc_html( get_post_meta( get_the_ID(), '_lunara_year', true ) ); ?><?php if ( get_post_meta( get_the_ID(), '_lunara_director', true ) ) : ?> · <?php echo esc_html( get_post_meta( get_the_ID(), '_lunara_director', true ) ); ?><?php endif; ?></p></div>
                        </a>
                    </article>
                <?php endwhile; ?>
            </div>
            <div class="lunara-archive-pagination"><?php echo paginate_links(); ?></div>
        <?php endif; ?>
    </section>
</main>
<?php get_footer(); ?>
