<?php
/**
 * Front Page Template for Lunara Film
 */
get_header();
?>
<main id="primary" class="site-main lunara-front-page">
    <section class="lunara-home-hero">
        <div class="lunara-home-hero-inner">
            <p class="lunara-home-hero-kicker">LUNARA FILM</p>
            <h1 class="lunara-home-hero-title"><?php bloginfo( 'name' ); ?></h1>
            <p class="lunara-home-hero-copy">Reviews, analysis, and an independent archival record of the Academy Awards—because while Oscars do not define cinema, they reveal how the culture surrounding it tries to.</p>
            <div class="lunara-home-hero-actions">
                <a class="lunara-btn" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>">Browse Reviews</a>
                <a class="lunara-btn lunara-btn-secondary" href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>">Explore the Oscar Database</a>
            </div>
        </div>
    </section>

    <?php $featured = lunara_featured_reviews_query(8); if ( $featured->have_posts() ) : ?>
    <section class="lunara-home-section lunara-featured-reviews-section" aria-label="Featured Reviews">
        <div class="lunara-home-section-head">
            <h2 class="lunara-home-section-title">Featured Reviews</h2>
            <div class="lunara-poster-carousel-controls">
                <button type="button" class="lunara-poster-carousel-btn lunara-poster-carousel-prev" aria-label="Previous featured reviews">&#8592;</button>
                <button type="button" class="lunara-poster-carousel-btn lunara-poster-carousel-next" aria-label="Next featured reviews">&#8594;</button>
            </div>
        </div>
        <div class="lunara-poster-carousel-wrap">
            <div class="lunara-poster-carousel-track" data-lunara-poster-carousel>
                <?php while ( $featured->have_posts() ) : $featured->the_post(); ?>
                    <article class="lunara-poster-card">
                        <a class="lunara-poster-card-link" href="<?php the_permalink(); ?>">
                            <div class="lunara-poster-card-image-wrap">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'large', array( 'class' => 'lunara-poster-card-image', 'loading' => 'lazy' ) ); ?>
                                <?php endif; ?>
                            </div>
                            <div class="lunara-poster-card-copy">
                                <p class="lunara-poster-card-kicker">LUNARA FILM REVIEW</p>
                                <h3 class="lunara-poster-card-title"><?php the_title(); ?></h3>
                                <p class="lunara-poster-card-meta"><?php echo esc_html( get_post_meta( get_the_ID(), '_lunara_year', true ) ); ?><?php if ( get_post_meta( get_the_ID(), '_lunara_score', true ) ) : ?> <span class="lunara-inline-score"><?php echo wp_kses_post( lunara_render_stars( get_post_meta( get_the_ID(), '_lunara_score', true ) ) ); ?></span><?php endif; ?></p>
                                <p class="lunara-poster-card-excerpt"><?php echo esc_html( lunara_card_excerpt( get_the_ID(), 22 ) ); ?></p>
                            </div>
                        </a>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="lunara-home-section lunara-database-callout">
        <div class="lunara-home-section-head">
            <h2 class="lunara-home-section-title">The Lunara Oscar Ledger</h2>
        </div>
        <div class="lunara-database-callout-card">
            <p class="lunara-database-callout-text">Compiled from records of the Academy of Motion Picture Arts and Sciences, the Lunara Oscar Ledger documents every nominee and winner in Academy Awards history. Awards are not the final word on cinema—but they remain one of the clearest mirrors of how the industry and its surrounding culture attempt to define greatness.</p>
            <a class="lunara-btn" href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>">Enter the Database</a>
        </div>
    </section>

    <?php $ledger = lunara_ledger_highlights_query(6); if ( $ledger->have_posts() ) : ?>
    <section class="lunara-home-section lunara-ledger-section" aria-label="Oscar Ledger Highlights">
        <div class="lunara-home-section-head">
            <h2 class="lunara-home-section-title">Oscar Ledger Highlights</h2>
        </div>
        <div class="lunara-ledger-grid">
            <?php while ( $ledger->have_posts() ) : $ledger->the_post(); ?>
                <article class="lunara-ledger-card">
                    <a class="lunara-ledger-card-link" href="<?php the_permalink(); ?>">
                        <div class="lunara-ledger-poster-wrap">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium_large', array( 'class' => 'lunara-ledger-poster', 'loading' => 'lazy' ) ); ?>
                            <?php endif; ?>
                        </div>
                        <div class="lunara-ledger-card-copy">
                            <h3 class="lunara-ledger-card-title"><?php the_title(); ?></h3>
                            <p class="lunara-ledger-card-meta"><?php echo esc_html( get_the_excerpt() ? get_the_excerpt() : get_the_date( 'Y' ) ); ?></p>
                        </div>
                    </a>
                </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </section>
    <?php endif; ?>

    <?php $latest = lunara_latest_reviews_query(9); if ( $latest->have_posts() ) : ?>
    <section class="lunara-home-section lunara-latest-reviews-section" aria-label="Latest Reviews">
        <div class="lunara-home-section-head">
            <h2 class="lunara-home-section-title">Latest Reviews</h2>
            <a class="lunara-section-link" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>">View All</a>
        </div>
        <div class="lunara-review-grid">
            <?php while ( $latest->have_posts() ) : $latest->the_post(); ?>
                <article class="lunara-review-grid-card">
                    <a class="lunara-review-grid-link" href="<?php the_permalink(); ?>">
                        <div class="lunara-review-grid-poster-wrap">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium_large', array( 'class' => 'lunara-review-grid-poster', 'loading' => 'lazy' ) ); ?>
                            <?php endif; ?>
                        </div>
                        <div class="lunara-review-grid-copy"><h3 class="lunara-review-grid-title"><?php the_title(); ?></h3><p class="lunara-review-grid-meta"><?php echo esc_html( get_post_meta( get_the_ID(), '_lunara_year', true ) ); ?><?php if ( get_post_meta( get_the_ID(), '_lunara_score', true ) ) : ?> <span class="lunara-inline-score"><?php echo wp_kses_post( lunara_render_stars( get_post_meta( get_the_ID(), '_lunara_score', true ) ) ); ?></span><?php endif; ?></p></div>
                    </a>
                </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </section>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
