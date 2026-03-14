<?php
/**
 * Lunara Film Child Theme Functions
 * 
 * @package Lunara_Film
 * @version 2.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue parent and child theme styles
 */
function lunara_enqueue_styles() {
    // Parent styles (Blocksy)
    wp_enqueue_style(
        'blocksy-style',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme( get_template() )->get( 'Version' )
    );
    
    wp_enqueue_style(
        'lunara-style',
        get_stylesheet_uri(),
        array( 'blocksy-style' ),
        filemtime( get_stylesheet_directory() . '/style.css' )
    );
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_styles' );

/**
 * Theme setup
 */
function lunara_theme_setup() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    
    // Register navigation menu
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'lunara-film' ),
        'footer'  => __( 'Footer Menu', 'lunara-film' ),
    ) );
}
add_action( 'after_setup_theme', 'lunara_theme_setup' );

/**
 * Customizer options
 */
function lunara_customize_register( $wp_customize ) {
    // Lunara Header Section
    $wp_customize->add_section( 'lunara_header_options', array(
        'title'    => __( 'Lunara Header', 'lunara-film' ),
        'priority' => 30,
    ) );
    
    // Site Title Text
    $wp_customize->add_setting( 'lunara_site_title', array(
        'default'           => 'LUNARA FILM',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    
    $wp_customize->add_control( 'lunara_site_title', array(
        'label'    => __( 'Site Title Text', 'lunara-film' ),
        'section'  => 'lunara_header_options',
        'type'     => 'text',
    ) );
    
    // Show/Hide Site Title
    $wp_customize->add_setting( 'lunara_show_site_title', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    
    $wp_customize->add_control( 'lunara_show_site_title', array(
        'label'    => __( 'Show Site Title', 'lunara-film' ),
        'section'  => 'lunara_header_options',
        'type'     => 'checkbox',
    ) );
    
    // Show/Hide Logo
    $wp_customize->add_setting( 'lunara_show_logo', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    
    $wp_customize->add_control( 'lunara_show_logo', array(
        'label'    => __( 'Show Logo (set in Site Identity)', 'lunara-film' ),
        'section'  => 'lunara_header_options',
        'type'     => 'checkbox',
    ) );

    // Header Spacing (fully editable without touching CSS)
    $wp_customize->add_setting( 'lunara_header_padding_y', array(
        'default'           => 20,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_header_padding_y', array(
        'label'       => __( 'Header Vertical Padding (px)', 'lunara-film' ),
        'section'     => 'lunara_header_options',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 8,
            'max'  => 48,
            'step' => 1,
        ),
    ) );

    $wp_customize->add_setting( 'lunara_logo_max_height', array(
        'default'           => 50,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_logo_max_height', array(
        'label'       => __( 'Logo Max Height (px)', 'lunara-film' ),
        'section'     => 'lunara_header_options',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 24,
            'max'  => 110,
            'step' => 1,
        ),
    ) );

    // Lunara Debrief Section (signature controls)
    $wp_customize->add_section( 'lunara_debrief_options', array(
        'title'    => __( 'Lunara Debrief', 'lunara-film' ),
        'priority' => 31,
    ) );

        $wp_customize->add_setting( 'lunara_debrief_kicker_text', array(
        'default'           => 'A LUNARA FILM SIGNATURE',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_debrief_kicker_text', array(
        'label'   => __( 'Kicker Text', 'lunara-film' ),
        'section' => 'lunara_debrief_options',
        'type'    => 'text',
    ) );

}
add_action( 'customize_register', 'lunara_customize_register' );

/**
 * Register Reviews Custom Post Type
 */
function lunara_register_reviews_cpt() {
    $args = array(
        'labels' => array(
            'name'          => 'Reviews',
            'singular_name' => 'Review',
            'add_new'       => 'Add New Review',
            'add_new_item'  => 'Add New Review',
            'edit_item'     => 'Edit Review',
            'menu_name'     => 'Reviews',
        ),
        'public'            => true,
        'has_archive'       => true,
        'rewrite'           => array( 'slug' => 'reviews' ),
        'menu_icon'         => 'dashicons-star-filled',
        'supports'          => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'taxonomies'        => array( 'category', 'post_tag' ),
        'show_in_rest'      => true,
    );
    register_post_type( 'review', $args );
}
add_action( 'init', 'lunara_register_reviews_cpt' );

/**
 * Flush rewrite rules on activation
 */
function lunara_flush_rewrites() {
    lunara_register_reviews_cpt();
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'lunara_flush_rewrites' );

/**
 * Blocksy controls the site header natively.
 */

/**
 * Shortcode: Homepage Content
 */
function lunara_home_shortcode() {
    ob_start();
    ?>
    <?php echo do_shortcode('[lunara_carousel set="homepage"]'); ?>

    <div class="lunara-tagline">
        <p class="lunara-tagline-text">Film criticism and the living record of the Oscars.</p>
    </div>

    <section class="lunara-section">
        <div class="lunara-section-header">
            <h2 class="lunara-section-title">Latest Reviews</h2>
        </div>
        <?php echo do_shortcode('[lunara_reviews count="3"]'); ?>
        <div class="text-center" style="margin-top: 30px;">
            <a href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>" class="lunara-btn">View All Reviews</a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode( 'lunara_home', 'lunara_home_shortcode' );

/**
 * Shortcode: Display Reviews
 */
function lunara_reviews_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'count' => 6 ), $atts );
    $count = intval( $atts['count'] );
    if ( $count === 0 ) { $count = 6; }

    $query = new WP_Query( array(
        'post_type'      => 'review',
        'posts_per_page' => $count < 0 ? -1 : $count,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'ignore_sticky_posts' => true,
    ) );

    if ( ! $query->have_posts() ) {
        return '<p style="text-align:center;color:#888;">No reviews yet.</p>';
    }

    ob_start();
    echo '<div class="lunara-review-grid lunara-review-archive-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        $score = get_post_meta( get_the_ID(), '_lunara_score', true );
        $year  = get_post_meta( get_the_ID(), '_lunara_year', true );
        $director = get_post_meta( get_the_ID(), '_lunara_director', true );
        ?>
        <article class="lunara-review-grid-card lunara-review-archive-card">
            <a class="lunara-review-grid-link" href="<?php the_permalink(); ?>">
                <div class="lunara-review-grid-poster-wrap">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'medium_large', array( 'class' => 'lunara-review-grid-poster', 'loading' => 'lazy' ) ); ?>
                    <?php endif; ?>
                    <?php if ( $score ) : ?><span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span><?php endif; ?>
                </div>
                <div class="lunara-review-grid-copy">
                    <h3 class="lunara-review-grid-title"><?php the_title(); ?></h3>
                    <p class="lunara-review-grid-meta"><?php echo esc_html( $year ); ?><?php if ( $director ) : ?> · <?php echo esc_html( $director ); ?><?php endif; ?></p>
                </div>
            </a>
        </article>
        <?php
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'lunara_reviews', 'lunara_reviews_shortcode' );

/**
 * Shortcode: Display Posts by Category
 */
function lunara_posts_shortcode( $atts ) {
    $atts = shortcode_atts( array( 
        'category' => '', 
        'count'    => 6 
    ), $atts );
    
    $query = new WP_Query( array(
        'post_type'      => 'post',
        'category_name'  => sanitize_text_field( $atts['category'] ),
        'posts_per_page' => intval( $atts['count'] ),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        'ignore_sticky_posts' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ) );
    
    if ( ! $query->have_posts() ) {
        return '<p style="text-align:center;color:#888;">No posts found.</p>';
    }
    
    ob_start();
    echo '<div class="lunara-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        ?>
        <article class="lunara-card">
            <?php if ( has_post_thumbnail() ) : ?>
                <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail( 'medium', array( 'class' => 'lunara-card-thumb' ) ); ?>
                </a>
            <?php endif; ?>
            <h3 class="lunara-card-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            <div class="lunara-card-meta"><?php echo get_the_date( 'F j, Y' ); ?></div>
            <div class="lunara-card-excerpt"><?php the_excerpt(); ?></div>
            <a href="<?php the_permalink(); ?>" class="lunara-btn">Read More</a>
        </article>
        <?php
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'lunara_posts', 'lunara_posts_shortcode' );

/* ========================================
   LUNARA DEBRIEF - REVIEW METADATA
   ======================================== */

/**
 * Render star rating
 */
function lunara_render_stars( $score ) {
    if ( empty( $score ) ) {
        return '';
    }
    
    $score = floatval( $score );
    $full_stars = floor( $score );
    $half_star = ( $score - $full_stars ) >= 0.5;
    
    $output = '<span class="lunara-stars">';
    for ( $i = 0; $i < $full_stars; $i++ ) {
        $output .= '★';
    }
    if ( $half_star ) {
        $output .= '½';
    }
    $output .= '</span>';
    
    return $output;
}

/**
 * Add Lunara Debrief meta box to Reviews
 */
function lunara_add_debrief_meta_box() {
    add_meta_box(
        'lunara_debrief_meta',
        'Lunara Debrief',
        'lunara_debrief_meta_callback',
        'review',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'lunara_add_debrief_meta_box' );

/**
 * Debrief meta box callback
 */
function lunara_debrief_meta_callback( $post ) {
    wp_nonce_field( 'lunara_debrief_nonce', 'lunara_debrief_nonce' );
    
    $score = get_post_meta( $post->ID, '_lunara_score', true );
    $year = get_post_meta( $post->ID, '_lunara_year', true );
    $imdb_review_id = get_post_meta( $post->ID, '_lunara_imdb_title_id', true );
    $where = get_post_meta( $post->ID, '_lunara_where', true );
    $theme_echo = get_post_meta( $post->ID, '_lunara_theme_echo', true );
    $counter = get_post_meta( $post->ID, '_lunara_counter_program', true );
    $craft = get_post_meta( $post->ID, '_lunara_craft_mirror', true );
    ?>
    <style>
        .lunara-meta-field { margin-bottom: 15px; }
        .lunara-meta-field label { display: block; font-weight: 600; margin-bottom: 5px; }
        .lunara-meta-field input, .lunara-meta-field select { width: 100%; }
        .lunara-meta-field .description { font-style: italic; color: #666; font-size: 12px; margin-top: 4px; }
        .lunara-meta-section { margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; }
        .lunara-meta-section h4 { margin: 0 0 15px; color: #c9a961; }
        .lunara-meta-row { display: flex; gap: 20px; }
        .lunara-meta-row .lunara-meta-field { flex: 1; }
    </style>
    
    <div class="lunara-meta-row">
        <div class="lunara-meta-field">
            <label for="lunara_score">Score (0-5, use .5 for half stars)</label>
            <input type="text" id="lunara_score" name="lunara_score" value="<?php echo esc_attr( $score ); ?>" placeholder="4.5">
            <p class="description">Examples: 4, 4.5, 5 → ★★★★, ★★★★½, ★★★★★</p>
        </div>
        
        <div class="lunara-meta-field">
            <label for="lunara_year">Year Released</label>
            <select id="lunara_year" name="lunara_year">
                <option value="">— Select Year —</option>
                <?php 
                $current_year = (int) date('Y') + 2; // Allow 2 years ahead for upcoming films
                for ( $y = $current_year; $y >= 1920; $y-- ) : 
                ?>
                    <option value="<?php echo $y; ?>" <?php selected( $year, $y ); ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
    
</div>

    <div class="lunara-meta-field">
        <label for="lunara_imdb_title_id">IMDb Title ID (for this review)</label>
        <input type="text" id="lunara_imdb_title_id" name="lunara_imdb_title_id" value="<?php echo esc_attr( $imdb_review_id ); ?>" placeholder="tt1234567">
        <p class="description">Connects this review to the Oscars database film page (shows a “Lunara Review” module on /oscars/title/tt…/).</p>
    </div>

    <div class="lunara-meta-field">
        <label for="lunara_where">Where to Watch</label>
        <input type="text" id="lunara_where" name="lunara_where" value="<?php echo esc_attr( $where ); ?>" placeholder="Netflix, Max, Theaters">
    </div>
    
    <div class="lunara-meta-section">
        <h4>PAIR IT WITH</h4>
        
        <div class="lunara-meta-field">
            <label for="lunara_theme_echo">Theme Echo</label>
            <input type="text" id="lunara_theme_echo" name="lunara_theme_echo" value="<?php echo esc_attr( $theme_echo ); ?>" placeholder="Film that shares thematic DNA">
            <p class="description">Tip: for clickable internal + IMDb links, you can append <code>| tt1234567</code> or paste a full IMDb URL anywhere in the line.</p>
        </div>
        
        <div class="lunara-meta-field">
            <label for="lunara_counter_program">Counter-Program</label>
            <input type="text" id="lunara_counter_program" name="lunara_counter_program" value="<?php echo esc_attr( $counter ); ?>" placeholder="Film that offers opposing perspective">
            <p class="description">Tip: optionally add <code>| tt1234567</code> (or an IMDb URL) to enable direct links.</p>
        </div>
        
        <div class="lunara-meta-field">
            <label for="lunara_craft_mirror">Career Context (Optional)</label>
            <input type="text" id="lunara_craft_mirror" name="lunara_craft_mirror" value="<?php echo esc_attr( $craft ); ?>" placeholder="Film with similar technical approach">
            <p class="description">Tip: optionally add <code>| tt1234567</code> (or an IMDb URL) to enable direct links.</p>
        </div>
    </div>
    <?php
}

/**
 * Save Debrief meta
 */
function lunara_save_debrief_meta( $post_id ) {
    if ( ! isset( $_POST['lunara_debrief_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['lunara_debrief_nonce'], 'lunara_debrief_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    
    $fields = array( 'lunara_score', 'lunara_year', 'lunara_imdb_title_id', 'lunara_where', 'lunara_theme_echo', 'lunara_counter_program', 'lunara_craft_mirror' );
    
    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }
}
add_action( 'save_post_review', 'lunara_save_debrief_meta' );

/**
 * Load the bundled IMDb title map (title|year -> ttID).
 * This lets Debrief lines link directly to IMDb (and Lunara Oscars film pages)
 * without requiring you to paste a tt-id every time.
 *
 * File: /assets/data/imdb-title-map.json
 *
 * Key format: "<normalized_title>|<year>" => "tt1234567"
 */

/**
 * Resolve the active Academy Awards DB table name (supports multiple plugin variants).
 */
function lunara_awards_table_name() {
    global $wpdb;
    $candidates = array(
        $wpdb->prefix . 'academy_awards',
        $wpdb->prefix . 'academy_awards_table',
        $wpdb->prefix . 'aat_awards',
        $wpdb->prefix . 'lunara_academy_awards',
        $wpdb->prefix . 'lunara_awards',
    );
    foreach ( $candidates as $t ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $found = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $t ) );
        if ( $found ) {
            return $t;
        }
    }
    return '';
}

/**
 * Get Oscar nominations/wins counts for a film by IMDb title id (tt...).
 * Returns array( 'noms' => int, 'wins' => int ).
 */
function lunara_get_oscar_ledger_counts( $tt ) {
    $tt = strtolower( trim( (string) $tt ) );
    if ( $tt === '' || ! preg_match( '/^tt\d{7,8}$/', $tt ) ) {
        return array( 'noms' => 0, 'wins' => 0 );
    }

    $cache_key = 'lunara_oscar_ledger_' . $tt;
    $cached = get_transient( $cache_key );
    if ( is_array( $cached ) && isset( $cached['noms'], $cached['wins'] ) ) {
        return $cached;
    }

    $table = lunara_awards_table_name();
    if ( $table === '' ) {
        return array( 'noms' => 0, 'wins' => 0 );
    }

    global $wpdb;

    // Most builds store the column as `film_id`. (MySQL column names are case-insensitive.)
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $noms = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE film_id = %s", $tt ) );

    // Winner is typically stored as 1/0.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wins = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE film_id = %s AND winner = 1", $tt ) );

    $out = array( 'noms' => $noms, 'wins' => $wins );
    set_transient( $cache_key, $out, 6 * HOUR_IN_SECONDS );
    return $out;
}

/**
 * Render the Oscar Ledger pill (clicks into Lunara's Oscars film page).
 */
function lunara_render_oscar_ledger_pill( $tt, $counts = null ) {
    $tt = strtolower( trim( (string) $tt ) );
    if ( $tt === '' || ! preg_match( '/^tt\d{7,8}$/', $tt ) ) {
        return '';
    }

    if ( ! is_array( $counts ) ) {
        $counts = lunara_get_oscar_ledger_counts( $tt );
    }

    $noms = (int) ( $counts['noms'] ?? 0 );
    $wins = (int) ( $counts['wins'] ?? 0 );

    // Keep it classy: only show when the film actually has Oscar presence.
    if ( $noms <= 0 ) {
        return '';
    }

    $href = home_url( '/oscars/title/' . $tt . '/' );
    $label = sprintf( '%d nominations • %d wins', $noms, $wins );

    return '<a class="lunara-oscar-ledger" href="' . esc_url( $href ) . '">'
        . '<span class="lunara-oscar-ledger-pill">Oscar Ledger</span>'
        . '<span class="lunara-oscar-ledger-counts">' . esc_html( $label ) . '</span>'
        . '</a>';
}


function lunara_imdb_title_map() {
    static $map = null;
    if ( $map !== null ) {
        return $map;
    }

    $map = array();
    $file = get_stylesheet_directory() . '/assets/data/imdb-title-map.json';

    if ( file_exists( $file ) ) {
        $json = file_get_contents( $file );
        $data = json_decode( $json, true );
        if ( is_array( $data ) ) {
            $map = $data;
        }
    }

    return $map;
}

/**
 * Normalize a title to a stable lookup key.
 */
function lunara_normalize_title_key( $title ) {
    $t = strtolower( remove_accents( (string) $title ) );
    $t = str_replace( '&', 'and', $t );
    $t = preg_replace( '/[^a-z0-9]+/', ' ', $t );
    $t = trim( preg_replace( '/\s+/', ' ', $t ) );
    return $t;
}



/**
 * Shortcode: The Lunara Debrief Block
 */
function lunara_debrief_shortcode( $atts ) {
    $post_id = get_the_ID();
    
    $score = get_post_meta( $post_id, '_lunara_score', true );
    $year = get_post_meta( $post_id, '_lunara_year', true );
    $where = get_post_meta( $post_id, '_lunara_where', true );
    $theme_echo = get_post_meta( $post_id, '_lunara_theme_echo', true );
    $counter = get_post_meta( $post_id, '_lunara_counter_program', true );
    $craft = get_post_meta( $post_id, '_lunara_craft_mirror', true );
    $review_tt = get_post_meta( $post_id, '_lunara_imdb_title_id', true );
    if ( is_string( $review_tt ) && preg_match( '#imdb\.com/title/(tt\d{7,8})#i', $review_tt, $mtt ) ) { $review_tt = $mtt[1]; }
    $review_tt = strtolower( trim( (string) $review_tt ) );
    
    if ( empty( $score ) && empty( $where ) && empty( $theme_echo ) && empty( $year ) ) {
        return '';
    }

    // Local helper: render a "Pair It With" line.
    // Supports optional IMDb title ID / URL embedded anywhere in the field.
    // Examples you can paste into the meta field:
    //   "There Will Be Blood (2007) — ... | tt0469494"
    //   "There Will Be Blood (2007) — ... https://www.imdb.com/title/tt0469494/"
    // If a tt-id is present, the title links to the internal Oscars film page (/oscars/title/tt.../)
    // and an "IMDb" reference chip is shown.
    $format_pairing = function( $value ) {
    $raw = trim( (string) $value );
    if ( $raw === '' ) {
        return '';
    }

    // 1) Extract a tt-id if present anywhere (either bare tt123... or full IMDb URL).
    //    Also optionally extract a Letterboxd film URL (for clickable title).
    $tt = '';
    $lb = '';
    if ( preg_match( '/\btt\d{7,8}\b/i', $raw, $m ) ) {
        $tt = strtolower( $m[0] );
    } elseif ( preg_match( '#imdb\.com/title/(tt\d{7,8})#i', $raw, $m ) ) {
        $tt = strtolower( $m[1] );
    }

    // Letterboxd film URL (optional). Supports:
    //   - https://letterboxd.com/film/<slug>/
    //   - | lb:https://letterboxd.com/film/<slug>/
    //   - | https://letterboxd.com/film/<slug>/
    if ( preg_match( '#letterboxd\.com/film/[^\s\|\)\]]+/?#i', $raw, $m ) ) {
        $lb = $m[0];
        // Ensure scheme.
        if ( stripos( $lb, 'http' ) !== 0 ) {
            $lb = 'https://' . ltrim( $lb, '/' );
        }
    }

    // 2) Remove the tt-id / IMDb URL / Letterboxd URL from the display string so the line stays clean.
    $clean = $raw;
    if ( $tt !== '' ) {
        $clean = preg_replace( '/\[\s*' . preg_quote( $tt, '/' ) . '\s*\]/i', '', $clean );
        $clean = preg_replace( '/\(\s*' . preg_quote( $tt, '/' ) . '\s*\)/i', '', $clean );
        $clean = preg_replace( '/\s*\|\s*\b' . preg_quote( $tt, '/' ) . '\b\s*$/i', '', $clean );
        $clean = preg_replace( '#\s*\|\s*https?://(www\.)?imdb\.com/title/' . preg_quote( $tt, '#' ) . '/?\s*$#i', '', $clean );
        $clean = preg_replace( '#\s*https?://(www\.)?imdb\.com/title/' . preg_quote( $tt, '#' ) . '/?\s*#i', ' ', $clean );
        $clean = preg_replace( '/\s*\b' . preg_quote( $tt, '/' ) . '\b\s*/i', ' ', $clean );
        $clean = trim( preg_replace( '/\s{2,}/', ' ', $clean ) );
    }
    if ( $lb !== '' ) {
        $clean = preg_replace( '#\s*\|\s*lb:\s*' . preg_quote( $lb, '#' ) . '\s*$#i', '', $clean );
        $clean = preg_replace( '#\s*\|\s*' . preg_quote( $lb, '#' ) . '\s*$#i', '', $clean );
        $clean = preg_replace( '#\s*' . preg_quote( $lb, '#' ) . '\s*#i', ' ', $clean );
        $clean = trim( preg_replace( '/\s{2,}/', ' ', $clean ) );
    }

    // 3) Split into title + note (prefer em dash).
    $parts = preg_split( '/\s+—\s+/u', $clean, 2 );
    if ( count( $parts ) < 2 ) {
        $parts = preg_split( '/\s+-\s+/', $clean, 2 );
    }

    $title = trim( $parts[0] ?? '' );
    $note  = trim( $parts[1] ?? '' );

    // 4) Pull year out of "Title (YYYY)" for smarter lookups & cleaner IMDb search queries.
    $title_base = $title;
    $year = '';
    if ( preg_match( '/^(.*?)(?:\s*\((\d{4})\))\s*$/', $title, $m2 ) ) {
        $title_base = trim( $m2[1] );
        $year = trim( $m2[2] );
    }

    // 5) If no explicit tt-id was provided, try to resolve via the bundled IMDb title map.
    if ( $tt === '' && $title_base !== '' ) {
        $map = lunara_imdb_title_map();
        if ( $year !== '' ) {
            $key = lunara_normalize_title_key( $title_base ) . '|' . $year;
            if ( isset( $map[ $key ] ) ) {
                $tt = strtolower( $map[ $key ] );
            }
        } else {
            // Only use a title-only lookup if it's unambiguous.
            $prefix = lunara_normalize_title_key( $title_base ) . '|';
            $matches = array();
            foreach ( $map as $k => $val ) {
                if ( strpos( $k, $prefix ) === 0 ) {
                    $matches[] = $val;
                }
            }
            $matches = array_values( array_unique( $matches ) );
            if ( count( $matches ) === 1 ) {
                $tt = strtolower( $matches[0] );
            }
        }
    }

    // 6) Build title + links.
    //    Title click goes to Letterboxd (film URL if provided; otherwise Letterboxd search).
    $lb_href = '';
    if ( $lb !== '' ) {
        $lb_href = $lb;
    } else {
        $q = $title_base !== '' ? $title_base : $title;
        if ( $year !== '' ) {
            $q .= ' ' . $year;
        }
        $lb_href = 'https://letterboxd.com/search/' . rawurlencode( $q ) . '/';
    }
    $title_html = '<a class="lunara-pair-title" href="' . esc_url( $lb_href ) . '" target="_blank" rel="noopener noreferrer nofollow"><em>' . esc_html( $title ) . '</em></a>';
    $chips_html = '';

    if ( $tt !== '' ) {
            $imdb = 'https://www.imdb.com/title/' . $tt . '/';

            // IMDb is the external reference. Oscar Ledger pill drives visitors into Lunara's database.
            $chips_html = ' <a class="lunara-debrief-chip lunara-debrief-chip-imdb" href="' . esc_url( $imdb ) . '" target="_blank" rel="noopener noreferrer nofollow">IMDb</a>';
            $chips_html .= ' ' . lunara_render_oscar_ledger_pill( $tt );
        } else {
        // Fallback: IMDb search. Use "Title YYYY" (no parentheses) for better results.
        $q = $title_base !== '' ? $title_base : $title;
        if ( $year !== '' ) {
            $q .= ' ' . $year;
        }
        $imdb_search = 'https://www.imdb.com/find/?q=' . rawurlencode( $q ) . '&s=tt';
        $chips_html  = ' <a class="lunara-debrief-chip lunara-debrief-chip-imdb" href="' . esc_url( $imdb_search ) . '" target="_blank" rel="noopener noreferrer nofollow">IMDb</a>';
    }

    // Optional poster thumbnail (pulled from the Academy Awards Database poster library when available).
    // If no poster is found, we fall back to the original text-only layout.
    $poster_html = '';
    if ( $tt !== '' && class_exists( 'Academy_Awards_Table' ) ) {
        $aat = Academy_Awards_Table::get_instance();
        if ( $aat && method_exists( $aat, 'get_poster_img_html_for_title' ) ) {
            // Use a non-cropped size so posters keep their aspect ratio (we size down via CSS).
            $poster_html = (string) $aat->get_poster_img_html_for_title(
                $tt,
                'medium',
                array(
                    'class'    => 'lunara-debrief-thumb',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                )
            );
        }
    }

    // IMPORTANT UX FIX:
    // The descriptive sentence (note) must not trail AFTER the IMDb chip.
    // Render a clean first line (title + chips), then the note on its own line below.
    $line1 = '<span class="lunara-debrief-line1">' . $title_html . $chips_html . '</span>';
    $line2 = '';
    if ( $note !== '' ) {
        $line2 = '<span class="lunara-debrief-note">' . esc_html( $note ) . '</span>';
    }

    $text_html = '<span class="lunara-debrief-pairing-text">' . $line1 . $line2 . '</span>';

    if ( $poster_html === '' ) {
        return $text_html;
    }

    return '<span class="lunara-debrief-pairing">'
        . '<span class="lunara-debrief-thumb-wrap">' . $poster_html . '</span>'
        . $text_html
        . '</span>';
};
    
    ob_start();
    ?>
    <section class="lunara-debrief-block">
        <h3 class="lunara-debrief-heading">LUNARA DEBRIEF</h3>
        <?php $kicker = trim( (string) get_theme_mod( 'lunara_debrief_kicker_text', 'A LUNARA FILM SIGNATURE' ) ); ?>
        <?php if ( $kicker !== '' ) : ?>
            <div class="lunara-debrief-kicker"><?php echo esc_html( $kicker ); ?></div>
        <?php endif; ?>
<ul class="lunara-debrief-list">
            <?php if ( $score ) : ?>
                <li><strong>Score:</strong><span class="lunara-debrief-value"><?php echo lunara_render_stars( $score ); ?></span></li>
            <?php endif; ?>

            <?php if ( $review_tt ) : ?>
                <?php $ledger = lunara_render_oscar_ledger_pill( $review_tt ); ?>
                <?php if ( $ledger !== '' ) : ?>
                    <li class="lunara-debrief-ledger-row"><strong>&nbsp;</strong><span class="lunara-debrief-value"><?php echo $ledger; ?></span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ( $year ) : ?>
                <li><strong>Year:</strong><span class="lunara-debrief-value"><?php echo esc_html( $year ); ?></span></li>
            <?php endif; ?>
            
            <?php if ( $where ) : ?>
                <li><strong>Where to Watch:</strong><span class="lunara-debrief-value"><?php echo esc_html( $where ); ?></span></li>
            <?php endif; ?>
            
            <?php if ( $theme_echo || $counter || $craft ) : ?>
                <li class="lunara-debrief-pair-header">Pair It With</li>
                
                <?php if ( $theme_echo ) : ?>
                    <li><strong>Theme Echo:</strong><span class="lunara-debrief-value"><?php echo $format_pairing( $theme_echo ); ?></span></li>
                <?php endif; ?>
                
                <?php if ( $counter ) : ?>
                    <li><strong>Counter-Program:</strong><span class="lunara-debrief-value"><?php echo $format_pairing( $counter ); ?></span></li>
                <?php endif; ?>
                
                <?php if ( $craft ) : ?>
                    <li><strong>Career Context:</strong><span class="lunara-debrief-value"><?php echo $format_pairing( $craft ); ?></span></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode( 'lunara_debrief', 'lunara_debrief_shortcode' );

/**
 * Auto-append Lunara Debrief to single review content
 */
function lunara_append_debrief_to_review( $content ) {
    if ( is_singular( 'review' ) && in_the_loop() && is_main_query() ) {
        // Avoid shortcode parsing overhead.
        $content .= lunara_debrief_shortcode( array() );
    }
    return $content;
}
add_filter( 'the_content', 'lunara_append_debrief_to_review' );

/* ========================================
   SLIDE SETS - CURATED CAROUSELS
   ======================================== */

/**
 * Register Slide Sets taxonomy for Media
 * In WP Admin: Media Library → click an image → edit → assign to a Slide Set (e.g., "homepage")
 * Then use: [lunara_carousel set="homepage"]
 */
add_action( 'init', function() {
    register_taxonomy( 'lunara_slide_set', array( 'attachment' ), array(
        'labels' => array(
            'name'          => __( 'Slide Sets', 'lunara-film' ),
            'singular_name' => __( 'Slide Set', 'lunara-film' ),
            'search_items'  => __( 'Search Slide Sets', 'lunara-film' ),
            'all_items'     => __( 'All Slide Sets', 'lunara-film' ),
            'edit_item'     => __( 'Edit Slide Set', 'lunara-film' ),
            'update_item'   => __( 'Update Slide Set', 'lunara-film' ),
            'add_new_item'  => __( 'Add New Slide Set', 'lunara-film' ),
            'new_item_name' => __( 'New Slide Set Name', 'lunara-film' ),
            'menu_name'     => __( 'Slide Sets', 'lunara-film' ),
        ),
        'public'             => false,
        'show_ui'            => true,
        'show_admin_column'  => true,
        'show_in_quick_edit' => true,
        'show_in_rest'       => true,
        'hierarchical'       => false,
        'rewrite'            => false,
        'query_var'          => false,
    ) );
} );


/**
 * Attachment field: Carousel Link URL (stored as _lunara_slide_link).
 * Falls back to Alt Text for backward compatibility.
 */
add_filter('attachment_fields_to_edit', function($form_fields, $post) {
    // Show for all media items; harmless if not used.
    $form_fields['lunara_slide_link'] = array(
        'label' => 'Carousel Link URL',
        'input' => 'text',
        'value' => get_post_meta($post->ID, '_lunara_slide_link', true),
        'helps' => 'Optional. If set, the carousel slide will link here. If empty, the theme falls back to using Alt Text as the link.',
    );
    return $form_fields;
}, 10, 2);

add_filter('attachment_fields_to_save', function($post, $attachment) {
    if (isset($attachment['lunara_slide_link'])) {
        $url = trim((string) $attachment['lunara_slide_link']);
        if ($url === '') {
            delete_post_meta($post['ID'], '_lunara_slide_link');
        } else {
            update_post_meta($post['ID'], '_lunara_slide_link', esc_url_raw($url));
        }
    }
    return $post;
}, 10, 2);

/**
 * Admin: Carousel Manager (drag & drop ordering per Slide Set).
 */
add_action('admin_menu', function() {
    add_theme_page(
        'Lunara Carousel',
        'Lunara Carousel',
        'manage_options',
        'lunara-carousel-manager',
        'lunara_render_carousel_manager_page'
    );
});

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'appearance_page_lunara-carousel-manager') {
        return;
    }

    wp_enqueue_style(
        'lunara-carousel-admin',
        get_stylesheet_directory_uri() . '/assets/css/lunara-carousel-admin.css',
        array(),
        wp_get_theme()->get('Version')
    );

    wp_enqueue_script('jquery-ui-sortable');

    wp_enqueue_script(
        'lunara-carousel-admin',
        get_stylesheet_directory_uri() . '/assets/js/lunara-carousel-admin.js',
        array('jquery', 'jquery-ui-sortable'),
        wp_get_theme()->get('Version'),
        true
    );

    wp_localize_script('lunara-carousel-admin', 'LUNARA_CAROUSEL_ADMIN', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('lunara_carousel_admin'),
    ));
});

function lunara_render_carousel_manager_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $taxonomy = 'lunara_slide_set';
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
    ));

    $selected = isset($_GET['set']) ? sanitize_text_field(wp_unslash($_GET['set'])) : '';
    if ($selected === '' && !empty($terms) && !is_wp_error($terms)) {
        $selected = $terms[0]->slug;
    }

    echo '<div class="wrap">';
    echo '<h1>Lunara Carousel</h1>';
    echo '<p><strong>How to update the carousel:</strong> Upload (or select) images in <em>Media → Library</em>, then assign them to a <em>Slide Set</em>. Use this page to drag & drop reorder slides. To add a link per slide, edit the media item and fill in <em>Carousel Link URL</em>.</p>';

    echo '<form method="get" action="">';
    echo '<input type="hidden" name="page" value="lunara-carousel-manager" />';
    echo '<label for="lunara-slide-set"><strong>Slide Set:</strong></label> ';
    echo '<select id="lunara-slide-set" name="set">';
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $t) {
            $sel = selected($selected, $t->slug, false);
            echo '<option value="' . esc_attr($t->slug) . '" ' . $sel . '>' . esc_html($t->name) . '</option>';
        }
    }
    echo '</select> ';
    submit_button('Load', 'secondary', '', false);
    echo '</form>';

    if ($selected) {
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'),
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $selected,
                ),
            ),
        ));

        echo '<hr />';
        echo '<h2>Slides in: ' . esc_html($selected) . '</h2>';

        if (empty($attachments)) {
            echo '<p>No slides found in this set yet.</p>';
        } else {
            echo '<p class="description">Drag & drop to reorder. Then click <strong>Save Order</strong>.</p>';
            echo '<ul id="lunara-carousel-sortable" class="lunara-carousel-sortable" data-slide-set="' . esc_attr($selected) . '">';
            foreach ($attachments as $att) {
                $thumb = wp_get_attachment_image($att->ID, array(120, 120), true);
                $link = get_post_meta($att->ID, '_lunara_slide_link', true);
                echo '<li class="lunara-carousel-item" data-id="' . esc_attr($att->ID) . '">';
                echo '<div class="lunara-carousel-thumb">' . $thumb . '</div>';
                echo '<div class="lunara-carousel-meta">';
                echo '<div class="lunara-carousel-title"><strong>' . esc_html(get_the_title($att->ID)) . '</strong></div>';
                if ($link) {
                    echo '<div class="lunara-carousel-link"><code>' . esc_html($link) . '</code></div>';
                }
                echo '<div class="lunara-carousel-actions"><a href="' . esc_url(get_edit_post_link($att->ID)) . '">Edit</a></div>';
                echo '</div>';
                echo '</li>';
            }
            echo '</ul>';
            echo '<button type="button" class="button button-primary" id="lunara-carousel-save-order">Save Order</button> ';
            echo '<span id="lunara-carousel-save-status" style="margin-left:10px;"></span>';
        }
    }

    echo '</div>';
}

add_action('wp_ajax_lunara_save_carousel_order', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    check_ajax_referer('lunara_carousel_admin', 'nonce');

    $order = isset($_POST['order']) ? (array) $_POST['order'] : array();
    $order = array_values(array_filter(array_map('intval', $order)));

    if (empty($order)) {
        wp_send_json_error(array('message' => 'No order received.'));
    }

    $menu_order = 0;
    foreach ($order as $id) {
        wp_update_post(array(
            'ID' => $id,
            'menu_order' => $menu_order,
        ));
        $menu_order++;
    }

    wp_send_json_success(array(
        'message' => 'Order saved.',
        'count' => count($order),
    ));
});


/**
 * Shortcode: Curated Carousel
 * Usage: [lunara_carousel set="homepage"]
 * 
 * Each image can have:
 * - Title: Used as slide title
 * - Caption: Used as slide subtitle
 * - Carousel Link URL: Used as link URL (optional)
 *   (set it on the Media item; Alt Text is left for actual alt text)
 */
function lunara_carousel_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'set'   => 'homepage',
        'limit' => -1,  // -1 = unlimited
    ), $atts );

    // Enqueue carousel JS only when the shortcode is used.
    $carousel_js = get_stylesheet_directory() . '/assets/js/lunara-carousel.js';
    if ( file_exists( $carousel_js ) ) {
        wp_enqueue_script(
            'lunara-carousel',
            get_stylesheet_directory_uri() . '/assets/js/lunara-carousel.js',
            array(),
            filemtime( $carousel_js ),
            true
        );
    }

    $set_slug = sanitize_title( $atts['set'] );
    $limit    = (int) $atts['limit'];

    // Query slides for this set. (No object-cache here: we want updates to appear immediately after you assign images.)
    $images = get_posts( array(
        'post_type'              => 'attachment',
        'post_mime_type'         => 'image',
        'posts_per_page'         => $limit,
        'post_status'            => 'inherit',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tax_query'              => array(
            array(
                'taxonomy' => 'lunara_slide_set',
                'field'    => 'slug',
                'terms'    => $set_slug,
            ),
        ),
        'orderby'                => 'menu_order',
        'order'                  => 'ASC',
    ) );
    // Fallback if no images in set
    if ( empty( $images ) ) {
        return '<div class="lunara-carousel-empty" style="background:#0f1d2e;padding:100px 40px;text-align:center;color:#888;">
            <p>No images in slide set "' . esc_html( $atts['set'] ) . '"</p>
            <p style="font-size:0.9em;">Go to Media Library → Edit an image → Assign to Slide Set</p>
        </div>';
    }
    
    ob_start();
    ?>
    <div class="lunara-carousel" id="lunara-carousel-<?php echo esc_attr( $set_slug ); ?>" data-autoplay="5000">
        <?php foreach ( $images as $index => $image ) : 
            $img_url = wp_get_attachment_image_url( $image->ID, 'full' );
            $title = $image->post_title;
            $caption = wp_get_attachment_caption( $image->ID );
            $link = (string) get_post_meta( $image->ID, '_lunara_slide_link', true );
            if ( empty( $link ) ) {
                // Back-compat: if Alt Text was previously used to store a URL, accept it only when it looks like a URL.
                $alt = (string) get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
                if ( $alt && preg_match( '~^https?://~i', $alt ) ) {
                    $link = $alt;
                }
            }
            $link = ( $link && filter_var( $link, FILTER_VALIDATE_URL ) ) ? $link : '';
        ?>
            <div class="lunara-carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: url('<?php echo esc_url( $img_url ); ?>');">
                <div class="lunara-carousel-overlay">
                    <?php if ( $link ) : ?>
                        <a href="<?php echo esc_url( $link ); ?>" class="lunara-carousel-link">
                    <?php endif; ?>
                    
                    <?php if ( $title ) : ?>
                        <h2 class="lunara-carousel-title"><?php echo esc_html( $title ); ?></h2>
                    <?php endif; ?>
                    
                    <?php if ( $caption ) : ?>
                        <p class="lunara-carousel-subtitle"><?php echo esc_html( $caption ); ?></p>
                    <?php endif; ?>
                    
                    <?php if ( $link ) : ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if ( count( $images ) > 1 ) : ?>
            <div class="lunara-carousel-dots">
                <?php foreach ( $images as $index => $image ) : ?>
                    <button class="lunara-carousel-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'lunara_carousel', 'lunara_carousel_shortcode' );


/* ========================================
   LUNARA 2.0 - REVIEW ARCHIVES / LEDGER HIGHLIGHTS / METADATA
   ======================================== */

/**
 * Register archive taxonomies for director and review year.
 */
function lunara_register_review_taxonomies() {
    register_taxonomy( 'lunara_director', array( 'review' ), array(
        'labels' => array(
            'name'          => __( 'Directors', 'lunara-film' ),
            'singular_name' => __( 'Director', 'lunara-film' ),
        ),
        'public'       => true,
        'hierarchical' => false,
        'show_in_rest' => true,
        'rewrite'      => array( 'slug' => 'director' ),
    ) );

    register_taxonomy( 'lunara_review_year', array( 'review' ), array(
        'labels' => array(
            'name'          => __( 'Review Years', 'lunara-film' ),
            'singular_name' => __( 'Review Year', 'lunara-film' ),
        ),
        'public'       => true,
        'hierarchical' => false,
        'show_in_rest' => true,
        'rewrite'      => array( 'slug' => 'review-year' ),
    ) );
}
add_action( 'init', 'lunara_register_review_taxonomies', 20 );

/**
 * Review detail meta box.
 */
function lunara_add_review_details_meta_box() {
    add_meta_box(
        'lunara_review_details_meta',
        'Review Details',
        'lunara_review_details_meta_callback',
        'review',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'lunara_add_review_details_meta_box' );

function lunara_review_details_meta_callback( $post ) {
    wp_nonce_field( 'lunara_review_details_nonce', 'lunara_review_details_nonce' );
    $director = get_post_meta( $post->ID, '_lunara_director', true );
    $runtime  = get_post_meta( $post->ID, '_lunara_runtime', true );
    $studio   = get_post_meta( $post->ID, '_lunara_studio', true );
    ?>
    <p><label for="lunara_director"><strong>Director</strong></label><br>
    <input type="text" name="lunara_director" id="lunara_director" value="<?php echo esc_attr( $director ); ?>" style="width:100%;"></p>

    <p><label for="lunara_runtime"><strong>Runtime</strong></label><br>
    <input type="text" name="lunara_runtime" id="lunara_runtime" value="<?php echo esc_attr( $runtime ); ?>" placeholder="142 min" style="width:100%;"></p>

    <p><label for="lunara_studio"><strong>Studio / Distributor</strong></label><br>
    <input type="text" name="lunara_studio" id="lunara_studio" value="<?php echo esc_attr( $studio ); ?>" style="width:100%;"></p>
    <?php
}

function lunara_save_review_details_meta( $post_id ) {
    if ( ! isset( $_POST['lunara_review_details_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['lunara_review_details_nonce'], 'lunara_review_details_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    foreach ( array( 'lunara_director', 'lunara_runtime', 'lunara_studio' ) as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, '_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
        }
    }
}
add_action( 'save_post_review', 'lunara_save_review_details_meta' );

/**
 * Keep archive taxonomies synchronized with review meta.
 */
function lunara_sync_review_archive_terms( $post_id ) {
    if ( wp_is_post_revision( $post_id ) || 'review' !== get_post_type( $post_id ) ) {
        return;
    }

    $director = trim( (string) get_post_meta( $post_id, '_lunara_director', true ) );
    $year     = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );

    if ( $director !== '' ) {
        wp_set_object_terms( $post_id, array( $director ), 'lunara_director', false );
    }

    if ( $year !== '' ) {
        wp_set_object_terms( $post_id, array( $year ), 'lunara_review_year', false );
    }
}
add_action( 'save_post_review', 'lunara_sync_review_archive_terms', 30 );

/**
 * Helper for card excerpt.
 */
function lunara_card_excerpt( $post_id, $words = 22 ) {
    if ( has_excerpt( $post_id ) ) {
        return wp_trim_words( get_the_excerpt( $post_id ), $words );
    }
    return wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), $words );
}

/**
 * Featured review query.
 */
function lunara_featured_reviews_query( $count = 8 ) {
    return new WP_Query( array(
        'post_type'              => 'review',
        'posts_per_page'         => (int) $count,
        'post_status'            => 'publish',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tag'                    => 'featured',
    ) );
}

/**
 * Ledger highlights query.
 */
function lunara_ledger_highlights_query( $count = 6 ) {
    return new WP_Query( array(
        'post_type'              => 'review',
        'posts_per_page'         => (int) $count,
        'post_status'            => 'publish',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tag'                    => 'oscar-ledger',
    ) );
}

/**
 * Latest review query.
 */
function lunara_latest_reviews_query( $count = 9 ) {
    return new WP_Query( array(
        'post_type'              => 'review',
        'posts_per_page'         => (int) $count,
        'post_status'            => 'publish',
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ) );
}

/**
 * Footer fallback.
 */
function lunara_footer_menu_fallback() {
    echo '<ul class="lunara-footer-fallback">';
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/reviews/' ) ) . '">Reviews</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/' ) ) . '">Oscar Database</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">About</a></li>';
    echo '</ul>';
}

/**
 * Review metadata prepended above single review content.
 */
function lunara_prepend_review_metadata( $content ) {
    if ( ! is_singular( 'review' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    $director = get_post_meta( get_the_ID(), '_lunara_director', true );
    $year     = get_post_meta( get_the_ID(), '_lunara_year', true );
    $runtime  = get_post_meta( get_the_ID(), '_lunara_runtime', true );
    $studio   = get_post_meta( get_the_ID(), '_lunara_studio', true );

    $items = array();
    if ( $director ) $items[] = '<span><strong>Director:</strong> ' . esc_html( $director ) . '</span>';
    if ( $year )     $items[] = '<span><strong>Year:</strong> ' . esc_html( $year ) . '</span>';
    if ( $runtime )  $items[] = '<span><strong>Runtime:</strong> ' . esc_html( $runtime ) . '</span>';
    if ( $studio )   $items[] = '<span><strong>Studio:</strong> ' . esc_html( $studio ) . '</span>';

    if ( empty( $items ) ) {
        return $content;
    }

    $bar = '<div class="lunara-review-metadata">' . implode( '', $items ) . '</div>';
    return $bar . $content;
}
add_filter( 'the_content', 'lunara_prepend_review_metadata', 5 );


/**
 * Poster carousel controls.
 */
add_action( 'wp_footer', function() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.lunara-featured-reviews-section').forEach(function(section) {
            const track = section.querySelector('[data-lunara-poster-carousel]');
            const prev = section.querySelector('.lunara-poster-carousel-prev');
            const next = section.querySelector('.lunara-poster-carousel-next');
            if (!track || !prev || !next) return;
            function amount() {
                const card = track.querySelector('.lunara-poster-card');
                const styles = window.getComputedStyle(track);
                const gap = parseInt(styles.columnGap || styles.gap || 24, 10);
                return card ? card.offsetWidth + gap : 360;
            }
            prev.addEventListener('click', function () {
                track.scrollBy({ left: -amount(), behavior: 'smooth' });
            });
            next.addEventListener('click', function () {
                track.scrollBy({ left: amount(), behavior: 'smooth' });
            });
        });
    });
    </script>
    <?php
}, 99 );
