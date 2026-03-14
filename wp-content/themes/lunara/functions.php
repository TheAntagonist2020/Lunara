<?php
/**
 * Lunara Theme Functions
 *
 * @package Lunara
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LUNARA_VERSION', '1.0.0' );
define( 'LUNARA_DIR', get_template_directory() );
define( 'LUNARA_URI', get_template_directory_uri() );

require LUNARA_DIR . '/inc/nav-fallback.php';

/* ==========================================================================
   Theme Setup
   ========================================================================== */

/**
 * Set up theme defaults and register support for various WordPress features.
 */
function lunara_setup() {
	load_theme_textdomain( 'lunara', LUNARA_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	) );
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'custom-background', array(
		'default-color' => '0d0d0d',
	) );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-color-palette', array(
		array(
			'name'  => esc_html__( 'Gold', 'lunara' ),
			'slug'  => 'gold',
			'color' => '#c8a97e',
		),
		array(
			'name'  => esc_html__( 'Dark', 'lunara' ),
			'slug'  => 'dark',
			'color' => '#0d0d0d',
		),
		array(
			'name'  => esc_html__( 'Light', 'lunara' ),
			'slug'  => 'light',
			'color' => '#f5f0e8',
		),
	) );

	// Image sizes for film posters / featured images.
	add_image_size( 'lunara-poster',    300, 450, true );
	add_image_size( 'lunara-hero',      1200, 500, true );
	add_image_size( 'lunara-card',      600, 900, true );

	register_nav_menus( array(
		'primary'  => esc_html__( 'Primary Menu', 'lunara' ),
		'footer'   => esc_html__( 'Footer Menu', 'lunara' ),
		'social'   => esc_html__( 'Social Links', 'lunara' ),
	) );
}
add_action( 'after_setup_theme', 'lunara_setup' );

/* ==========================================================================
   Content Width
   ========================================================================== */

function lunara_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'lunara_content_width', 800 );
}
add_action( 'after_setup_theme', 'lunara_content_width', 0 );

/* ==========================================================================
   Enqueue Scripts & Styles
   ========================================================================== */

function lunara_scripts() {
	wp_enqueue_style(
		'lunara-style',
		get_stylesheet_uri(),
		array(),
		LUNARA_VERSION
	);

	wp_enqueue_style(
		'lunara-main',
		LUNARA_URI . '/assets/css/main.css',
		array( 'lunara-style' ),
		LUNARA_VERSION
	);

	wp_enqueue_script(
		'lunara-main',
		LUNARA_URI . '/assets/js/main.js',
		array(),
		LUNARA_VERSION,
		true
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// Pass localised data to JS.
	wp_localize_script( 'lunara-main', 'lunaraData', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'lunara_nonce' ),
		'i18n'    => array(
			'openMenu'  => esc_html__( 'Open menu', 'lunara' ),
			'closeMenu' => esc_html__( 'Close menu', 'lunara' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'lunara_scripts' );

/* ==========================================================================
   Widget Areas
   ========================================================================== */

function lunara_widgets_init() {
	$shared_args = array(
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
	);

	register_sidebar( array_merge( $shared_args, array(
		'name'        => esc_html__( 'Main Sidebar', 'lunara' ),
		'id'          => 'sidebar-1',
		'description' => esc_html__( 'Appears alongside the main content.', 'lunara' ),
	) ) );

	register_sidebar( array_merge( $shared_args, array(
		'name'        => esc_html__( 'Footer — Column 1', 'lunara' ),
		'id'          => 'footer-1',
		'description' => esc_html__( 'First footer widget column.', 'lunara' ),
	) ) );

	register_sidebar( array_merge( $shared_args, array(
		'name'        => esc_html__( 'Footer — Column 2', 'lunara' ),
		'id'          => 'footer-2',
		'description' => esc_html__( 'Second footer widget column.', 'lunara' ),
	) ) );

	register_sidebar( array_merge( $shared_args, array(
		'name'        => esc_html__( 'Footer — Column 3', 'lunara' ),
		'id'          => 'footer-3',
		'description' => esc_html__( 'Third footer widget column.', 'lunara' ),
	) ) );
}
add_action( 'widgets_init', 'lunara_widgets_init' );

/* ==========================================================================
   Custom Post Type: Film Review
   ========================================================================== */

function lunara_register_post_types() {
	$labels = array(
		'name'                  => esc_html_x( 'Film Reviews', 'Post type general name', 'lunara' ),
		'singular_name'         => esc_html_x( 'Film Review', 'Post type singular name', 'lunara' ),
		'menu_name'             => esc_html_x( 'Film Reviews', 'Admin Menu text', 'lunara' ),
		'name_admin_bar'        => esc_html_x( 'Film Review', 'Add New on Toolbar', 'lunara' ),
		'add_new'               => esc_html__( 'Add New', 'lunara' ),
		'add_new_item'          => esc_html__( 'Add New Film Review', 'lunara' ),
		'new_item'              => esc_html__( 'New Film Review', 'lunara' ),
		'edit_item'             => esc_html__( 'Edit Film Review', 'lunara' ),
		'view_item'             => esc_html__( 'View Film Review', 'lunara' ),
		'all_items'             => esc_html__( 'All Film Reviews', 'lunara' ),
		'search_items'          => esc_html__( 'Search Film Reviews', 'lunara' ),
		'parent_item_colon'     => esc_html__( 'Parent Film Reviews:', 'lunara' ),
		'not_found'             => esc_html__( 'No film reviews found.', 'lunara' ),
		'not_found_in_trash'    => esc_html__( 'No film reviews found in Trash.', 'lunara' ),
		'featured_image'        => esc_html_x( 'Film Poster', 'Overrides the "Featured Image" phrase', 'lunara' ),
		'set_featured_image'    => esc_html_x( 'Set film poster', 'Overrides the "Set featured image" phrase', 'lunara' ),
		'remove_featured_image' => esc_html_x( 'Remove film poster', 'Overrides the "Remove featured image" phrase', 'lunara' ),
		'use_featured_image'    => esc_html_x( 'Use as film poster', 'Overrides the "Use as featured image" phrase', 'lunara' ),
	);

	register_post_type( 'lunara_review', array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'reviews' ),
		'capability_type'    => 'post',
		'has_archive'        => 'reviews',
		'hierarchical'       => false,
		'menu_position'      => 5,
		'menu_icon'          => 'dashicons-format-video',
		'supports'           => array(
			'title',
			'editor',
			'author',
			'thumbnail',
			'excerpt',
			'comments',
			'revisions',
			'custom-fields',
		),
	) );
}
add_action( 'init', 'lunara_register_post_types' );

/* ==========================================================================
   Custom Taxonomy: Genre
   ========================================================================== */

function lunara_register_taxonomies() {
	// Genre taxonomy for film reviews.
	register_taxonomy( 'lunara_genre', array( 'lunara_review', 'post' ), array(
		'hierarchical'      => true,
		'labels'            => array(
			'name'              => esc_html_x( 'Genres', 'taxonomy general name', 'lunara' ),
			'singular_name'     => esc_html_x( 'Genre', 'taxonomy singular name', 'lunara' ),
			'search_items'      => esc_html__( 'Search Genres', 'lunara' ),
			'all_items'         => esc_html__( 'All Genres', 'lunara' ),
			'parent_item'       => esc_html__( 'Parent Genre', 'lunara' ),
			'parent_item_colon' => esc_html__( 'Parent Genre:', 'lunara' ),
			'edit_item'         => esc_html__( 'Edit Genre', 'lunara' ),
			'update_item'       => esc_html__( 'Update Genre', 'lunara' ),
			'add_new_item'      => esc_html__( 'Add New Genre', 'lunara' ),
			'new_item_name'     => esc_html__( 'New Genre Name', 'lunara' ),
			'menu_name'         => esc_html__( 'Genres', 'lunara' ),
		),
		'show_ui'           => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => array( 'slug' => 'genre' ),
	) );

	// Decade taxonomy.
	register_taxonomy( 'lunara_decade', array( 'lunara_review', 'post' ), array(
		'hierarchical'      => false,
		'labels'            => array(
			'name'          => esc_html_x( 'Decades', 'taxonomy general name', 'lunara' ),
			'singular_name' => esc_html_x( 'Decade', 'taxonomy singular name', 'lunara' ),
			'search_items'  => esc_html__( 'Search Decades', 'lunara' ),
			'all_items'     => esc_html__( 'All Decades', 'lunara' ),
			'edit_item'     => esc_html__( 'Edit Decade', 'lunara' ),
			'update_item'   => esc_html__( 'Update Decade', 'lunara' ),
			'add_new_item'  => esc_html__( 'Add New Decade', 'lunara' ),
			'new_item_name' => esc_html__( 'New Decade Name', 'lunara' ),
			'menu_name'     => esc_html__( 'Decades', 'lunara' ),
		),
		'show_ui'           => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => array( 'slug' => 'decade' ),
	) );
}
add_action( 'init', 'lunara_register_taxonomies' );

/* ==========================================================================
   Review Meta Boxes
   ========================================================================== */

function lunara_add_meta_boxes() {
	add_meta_box(
		'lunara_review_details',
		esc_html__( 'Film Details', 'lunara' ),
		'lunara_review_details_callback',
		'lunara_review',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'lunara_add_meta_boxes' );

function lunara_review_details_callback( $post ) {
	wp_nonce_field( 'lunara_save_review_details', 'lunara_review_nonce' );

	$director       = get_post_meta( $post->ID, '_lunara_director', true );
	$year           = get_post_meta( $post->ID, '_lunara_year', true );
	$runtime        = get_post_meta( $post->ID, '_lunara_runtime', true );
	$rating         = get_post_meta( $post->ID, '_lunara_rating', true );
	$certif         = get_post_meta( $post->ID, '_lunara_certification', true );
	$country        = get_post_meta( $post->ID, '_lunara_country', true );
	$streaming      = get_post_meta( $post->ID, '_lunara_streaming', true );
	$oscars_noms    = get_post_meta( $post->ID, '_lunara_oscars_nominations', true );
	$oscars_wins    = get_post_meta( $post->ID, '_lunara_oscars_wins', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="lunara_director"><?php esc_html_e( 'Director', 'lunara' ); ?></label></th>
			<td><input type="text" id="lunara_director" name="lunara_director" value="<?php echo esc_attr( $director ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th><label for="lunara_year"><?php esc_html_e( 'Release Year', 'lunara' ); ?></label></th>
			<td><input type="number" id="lunara_year" name="lunara_year" value="<?php echo esc_attr( $year ); ?>" min="1888" max="<?php echo esc_attr( gmdate( 'Y' ) + 2 ); ?>" class="small-text"></td>
		</tr>
		<tr>
			<th><label for="lunara_runtime"><?php esc_html_e( 'Runtime (minutes)', 'lunara' ); ?></label></th>
			<td><input type="number" id="lunara_runtime" name="lunara_runtime" value="<?php echo esc_attr( $runtime ); ?>" min="1" class="small-text"></td>
		</tr>
		<tr>
			<th><label for="lunara_rating"><?php esc_html_e( 'Rating (0–10)', 'lunara' ); ?></label></th>
			<td><input type="number" id="lunara_rating" name="lunara_rating" value="<?php echo esc_attr( $rating ); ?>" min="0" max="10" step="0.5" class="small-text"></td>
		</tr>
		<tr>
			<th><label for="lunara_certification"><?php esc_html_e( 'Certification', 'lunara' ); ?></label></th>
			<td><input type="text" id="lunara_certification" name="lunara_certification" value="<?php echo esc_attr( $certif ); ?>" class="regular-text" placeholder="e.g. 15, PG-13, R"></td>
		</tr>
		<tr>
			<th><label for="lunara_country"><?php esc_html_e( 'Country', 'lunara' ); ?></label></th>
			<td><input type="text" id="lunara_country" name="lunara_country" value="<?php echo esc_attr( $country ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th><label for="lunara_streaming"><?php esc_html_e( 'Streaming On', 'lunara' ); ?></label></th>
			<td><input type="text" id="lunara_streaming" name="lunara_streaming" value="<?php echo esc_attr( $streaming ); ?>" class="regular-text" placeholder="e.g. Netflix, Prime Video"></td>
		</tr>
		<tr>
			<th><label for="lunara_oscars_nominations"><?php esc_html_e( 'Oscar Nominations', 'lunara' ); ?></label></th>
			<td><input type="number" id="lunara_oscars_nominations" name="lunara_oscars_nominations" value="<?php echo esc_attr( $oscars_noms ); ?>" min="0" class="small-text"></td>
		</tr>
		<tr>
			<th><label for="lunara_oscars_wins"><?php esc_html_e( 'Oscar Wins', 'lunara' ); ?></label></th>
			<td><input type="number" id="lunara_oscars_wins" name="lunara_oscars_wins" value="<?php echo esc_attr( $oscars_wins ); ?>" min="0" class="small-text"></td>
		</tr>
	</table>
	<?php
}

function lunara_save_review_details( $post_id ) {
	if ( ! isset( $_POST['lunara_review_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lunara_review_nonce'] ) ), 'lunara_save_review_details' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array(
		'lunara_director'            => '_lunara_director',
		'lunara_year'                => '_lunara_year',
		'lunara_runtime'             => '_lunara_runtime',
		'lunara_rating'              => '_lunara_rating',
		'lunara_certification'       => '_lunara_certification',
		'lunara_country'             => '_lunara_country',
		'lunara_streaming'           => '_lunara_streaming',
		'lunara_oscars_nominations'  => '_lunara_oscars_nominations',
		'lunara_oscars_wins'         => '_lunara_oscars_wins',
	);

	foreach ( $fields as $input_name => $meta_key ) {
		if ( isset( $_POST[ $input_name ] ) ) {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $input_name ] ) ) );
		}
	}
}
add_action( 'save_post_lunara_review', 'lunara_save_review_details' );

/* ==========================================================================
   Template Helpers
   ========================================================================== */

/**
 * Render a star-rating display.
 *
 * @param float $rating Rating value out of 10.
 * @param int   $max    Maximum stars (default 5).
 */
function lunara_star_rating( $rating, $max = 5 ) {
	$full      = floor( $rating / 2 );
	$remainder = $rating - ( $full * 2 );
	$half      = ( $remainder >= 1 ) ? 1 : 0;
	$empty     = $max - $full - $half;

	$html = '<span class="star-rating" aria-label="' . esc_attr( sprintf(
		/* translators: 1: rating, 2: max rating */
		__( 'Rating: %1$s out of %2$s', 'lunara' ),
		$rating,
		$max * 2
	) ) . '">';

	for ( $i = 0; $i < $full; $i++ ) {
		$html .= '<span class="filled" aria-hidden="true">★</span>';
	}
	if ( $half ) {
		$html .= '<span class="half" aria-hidden="true">½</span>';
	}
	for ( $i = 0; $i < $empty; $i++ ) {
		$html .= '<span class="empty" aria-hidden="true">☆</span>';
	}

	$html .= '</span>';

	return $html;
}

/**
 * Returns a formatted runtime string.
 *
 * @param int $minutes Runtime in minutes.
 * @return string
 */
function lunara_format_runtime( $minutes ) {
	if ( ! $minutes ) {
		return '';
	}
	$hours = floor( $minutes / 60 );
	$mins  = $minutes % 60;

	if ( $hours && $mins ) {
		return sprintf(
			/* translators: 1: hours, 2: minutes */
			esc_html__( '%1$dh %2$dm', 'lunara' ),
			$hours,
			$mins
		);
	} elseif ( $hours ) {
		return sprintf(
			/* translators: %d: hours */
			esc_html__( '%dh', 'lunara' ),
			$hours
		);
	}
	return sprintf(
		/* translators: %d: minutes */
		esc_html__( '%dm', 'lunara' ),
		$mins
	);
}

/**
 * Output a set of genre tag links for the given post.
 *
 * @param int|null $post_id Post ID (defaults to current post).
 */
function lunara_genre_tags( $post_id = null ) {
	$terms = get_the_terms( $post_id ?? get_the_ID(), 'lunara_genre' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return;
	}
	foreach ( $terms as $term ) {
		printf(
			'<a class="tag" href="%s">%s</a>',
			esc_url( get_term_link( $term ) ),
			esc_html( $term->name )
		);
	}
}

/* ==========================================================================
   Excerpt
   ========================================================================== */

add_filter( 'excerpt_length', function() {
	return 25;
} );

add_filter( 'excerpt_more', function() {
	return '&hellip;';
} );

/* ==========================================================================
   Body Classes
   ========================================================================== */

add_filter( 'body_class', function( $classes ) {
	if ( is_active_sidebar( 'sidebar-1' ) && ! is_singular( 'lunara_review' ) ) {
		$classes[] = 'has-sidebar';
	}
	return $classes;
} );

/* ==========================================================================
   Admin Columns for Film Reviews
   ========================================================================== */

function lunara_review_columns( $columns ) {
	$new_columns = array();
	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		if ( 'title' === $key ) {
			$new_columns['lunara_year']   = esc_html__( 'Year', 'lunara' );
			$new_columns['lunara_rating'] = esc_html__( 'Rating', 'lunara' );
			$new_columns['lunara_oscars'] = esc_html__( 'Oscars', 'lunara' );
		}
	}
	return $new_columns;
}
add_filter( 'manage_lunara_review_posts_columns', 'lunara_review_columns' );

function lunara_review_column_data( $column, $post_id ) {
	switch ( $column ) {
		case 'lunara_year':
			echo esc_html( get_post_meta( $post_id, '_lunara_year', true ) );
			break;
		case 'lunara_rating':
			$rating = get_post_meta( $post_id, '_lunara_rating', true );
			if ( $rating !== '' ) {
				echo esc_html( $rating ) . '/10';
			}
			break;
		case 'lunara_oscars':
			$wins = get_post_meta( $post_id, '_lunara_oscars_wins', true );
			$noms = get_post_meta( $post_id, '_lunara_oscars_nominations', true );
			if ( $noms !== '' ) {
				printf(
					/* translators: 1: wins, 2: nominations */
					esc_html__( '%1$d wins / %2$d noms', 'lunara' ),
					(int) $wins,
					(int) $noms
				);
			}
			break;
	}
}
add_action( 'manage_lunara_review_posts_custom_column', 'lunara_review_column_data', 10, 2 );
