<?php
/**
 * Oscars Shortcodes — registers all front-end shortcodes for the Oscars database.
 *
 * Shortcodes
 * ----------
 * [lunara_oscars]           – Display a filterable table of nominations.
 * [lunara_oscars_winners]   – Display winners for a specific ceremony year.
 * [lunara_film_oscars]      – Display Oscar stats / nominations for a film.
 *
 * @package LunaraOscars
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lunara_Oscars_Shortcodes {

	/** Register all shortcodes. */
	public static function init(): void {
		add_shortcode( 'lunara_oscars',         array( __CLASS__, 'render_oscars_table' ) );
		add_shortcode( 'lunara_oscars_winners',  array( __CLASS__, 'render_winners' ) );
		add_shortcode( 'lunara_film_oscars',     array( __CLASS__, 'render_film_oscars' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/** Enqueue front-end styles. */
	public static function enqueue_assets(): void {
		wp_register_style(
			'lunara-oscars',
			LUNARA_OSCARS_URI . 'assets/oscars.css',
			array(),
			LUNARA_OSCARS_VERSION
		);
	}

	/* -----------------------------------------------------------------------
	   [lunara_oscars]
	   ----------------------------------------------------------------------- */

	/**
	 * Render a filterable nominations table.
	 *
	 * Attributes:
	 *   year       – Filter by ceremony year.
	 *   category   – Filter by category keyword.
	 *   film       – Filter by film keyword.
	 *   winners    – 'only' = winners, 'exclude' = nominees, default = all.
	 *   per_page   – Rows per page (default 50, max 200).
	 *   show_filter – 'yes' (default) | 'no'  – show/hide filter controls.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_oscars_table( $atts ): string {
		$atts = shortcode_atts( array(
			'year'        => '',
			'category'    => '',
			'film'        => '',
			'winners'     => '',
			'per_page'    => 50,
			'show_filter' => 'yes',
		), $atts, 'lunara_oscars' );

		wp_enqueue_style( 'lunara-oscars' );

		$is_winner = -1;
		if ( 'only' === $atts['winners'] ) {
			$is_winner = 1;
		} elseif ( 'exclude' === $atts['winners'] ) {
			$is_winner = 0;
		}

		$query_args = array(
			'ceremony_year' => absint( $atts['year'] ),
			'category'      => sanitize_text_field( $atts['category'] ),
			'film'          => sanitize_text_field( $atts['film'] ),
			'is_winner'     => $is_winner,
			'per_page'      => min( 200, max( 1, (int) $atts['per_page'] ) ),
		);

		$data = Lunara_Oscars_Database::query( $query_args );

		ob_start();

		if ( 'yes' === $atts['show_filter'] ) {
			self::render_filter_controls( $atts );
		}

		if ( empty( $data['results'] ) ) {
			echo '<p class="lunara-oscars-empty">' . esc_html__( 'No nominations found.', 'lunara-oscars' ) . '</p>';
			return ob_get_clean();
		}
		?>
		<div class="oscars-table-wrap">
			<table class="oscars-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Year', 'lunara-oscars' ); ?></th>
						<th><?php esc_html_e( 'Category', 'lunara-oscars' ); ?></th>
						<th><?php esc_html_e( 'Film', 'lunara-oscars' ); ?></th>
						<th><?php esc_html_e( 'Nominee', 'lunara-oscars' ); ?></th>
						<th><?php esc_html_e( 'Result', 'lunara-oscars' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $data['results'] as $row ) : ?>
						<tr class="<?php echo $row->is_winner ? 'winner' : 'nominee'; ?>"
						    data-year="<?php echo esc_attr( $row->ceremony_year ); ?>"
						    data-category="<?php echo esc_attr( $row->category ); ?>">
							<td><?php echo esc_html( $row->ceremony_year ); ?></td>
							<td><?php echo esc_html( $row->category ); ?></td>
							<td class="film-name"><?php echo esc_html( $row->film ); ?></td>
							<td><?php echo esc_html( $row->nominee ); ?></td>
							<td>
								<?php if ( $row->is_winner ) : ?>
									<span class="oscar-winner-badge">&#9733; <?php esc_html_e( 'Winner', 'lunara-oscars' ); ?></span>
								<?php else : ?>
									<span class="oscars-nominee-label"><?php esc_html_e( 'Nominated', 'lunara-oscars' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the filter bar above the table.
	 *
	 * @param array $current_atts Current shortcode attributes (pre-selected values).
	 */
	private static function render_filter_controls( array $current_atts ): void {
		$years      = Lunara_Oscars_Database::get_years();
		$categories = Lunara_Oscars_Database::get_categories();
		?>
		<div class="oscars-filters">
			<select id="oscars-filter-year" aria-label="<?php esc_attr_e( 'Filter by year', 'lunara-oscars' ); ?>">
				<option value=""><?php esc_html_e( 'All Years', 'lunara-oscars' ); ?></option>
				<?php foreach ( $years as $year ) : ?>
					<option value="<?php echo esc_attr( $year ); ?>"
					        <?php selected( (string) $year, $current_atts['year'] ); ?>>
						<?php echo esc_html( $year ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select id="oscars-filter-category" aria-label="<?php esc_attr_e( 'Filter by category', 'lunara-oscars' ); ?>">
				<option value=""><?php esc_html_e( 'All Categories', 'lunara-oscars' ); ?></option>
				<?php foreach ( $categories as $cat ) : ?>
					<option value="<?php echo esc_attr( $cat ); ?>"
					        <?php selected( $cat, $current_atts['category'] ); ?>>
						<?php echo esc_html( $cat ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<input type="search"
			       id="oscars-filter-search"
			       placeholder="<?php esc_attr_e( 'Search films, nominees…', 'lunara-oscars' ); ?>"
			       aria-label="<?php esc_attr_e( 'Search nominations', 'lunara-oscars' ); ?>">
		</div>
		<?php
	}

	/* -----------------------------------------------------------------------
	   [lunara_oscars_winners]
	   ----------------------------------------------------------------------- */

	/**
	 * Render a list of winners for a given ceremony year.
	 *
	 * Attributes:
	 *   year  – Ceremony year (required).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_winners( $atts ): string {
		$atts = shortcode_atts( array(
			'year' => '',
		), $atts, 'lunara_oscars_winners' );

		wp_enqueue_style( 'lunara-oscars' );

		if ( empty( $atts['year'] ) ) {
			return '<p class="lunara-oscars-error">' . esc_html__( 'Please specify a year.', 'lunara-oscars' ) . '</p>';
		}

		$data = Lunara_Oscars_Database::query( array(
			'ceremony_year' => absint( $atts['year'] ),
			'is_winner'     => 1,
			'per_page'      => 200,
			'orderby'       => 'category',
			'order'         => 'ASC',
		) );

		if ( empty( $data['results'] ) ) {
			return '<p class="lunara-oscars-empty">' . esc_html__( 'No winners found for this year.', 'lunara-oscars' ) . '</p>';
		}

		ob_start();
		?>
		<div class="oscars-winners">
			<h3 class="oscars-winners__title">
				<?php
				printf(
					/* translators: %s: ceremony year */
					esc_html__( 'Oscar Winners %s', 'lunara-oscars' ),
					esc_html( $atts['year'] )
				);
				?>
			</h3>
			<ul class="oscars-winners__list">
				<?php foreach ( $data['results'] as $row ) : ?>
					<li class="oscars-winners__item">
						<span class="oscars-winners__category"><?php echo esc_html( $row->category ); ?></span>
						<span class="oscars-winners__film"><?php echo esc_html( $row->film ); ?></span>
						<?php if ( $row->nominee ) : ?>
							<span class="oscars-winners__nominee"><?php echo esc_html( $row->nominee ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	/* -----------------------------------------------------------------------
	   [lunara_film_oscars]
	   ----------------------------------------------------------------------- */

	/**
	 * Render Oscar stats and nominations for a specific film.
	 *
	 * Attributes:
	 *   film  – Film title (required, exact match).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_film_oscars( $atts ): string {
		$atts = shortcode_atts( array(
			'film' => '',
		), $atts, 'lunara_film_oscars' );

		wp_enqueue_style( 'lunara-oscars' );

		if ( empty( $atts['film'] ) ) {
			return '<p class="lunara-oscars-error">' . esc_html__( 'Please specify a film title.', 'lunara-oscars' ) . '</p>';
		}

		$film  = sanitize_text_field( $atts['film'] );
		$stats = Lunara_Oscars_Database::get_film_stats( $film );
		$data  = Lunara_Oscars_Database::query( array(
			'film'     => $film,
			'per_page' => 200,
			'orderby'  => 'ceremony_year',
			'order'    => 'ASC',
		) );

		if ( ! $stats || empty( $data['results'] ) ) {
			return '<p class="lunara-oscars-empty">' . esc_html__( 'No Oscar records found for this film.', 'lunara-oscars' ) . '</p>';
		}

		ob_start();
		?>
		<div class="film-oscars-box">
			<div class="film-oscars-box__summary">
				<span class="film-oscars-box__nominations">
					<?php
					printf(
						/* translators: %d: number of nominations */
						esc_html( _n( '%d Nomination', '%d Nominations', (int) $stats->total_nominations, 'lunara-oscars' ) ),
						(int) $stats->total_nominations
					);
					?>
				</span>
				<span class="film-oscars-box__wins">
					<?php
					printf(
						/* translators: %d: number of wins */
						esc_html( _n( '%d Win', '%d Wins', (int) $stats->total_wins, 'lunara-oscars' ) ),
						(int) $stats->total_wins
					);
					?>
				</span>
			</div>

			<ul class="film-oscars-box__list">
				<?php foreach ( $data['results'] as $row ) : ?>
					<li class="film-oscars-box__item <?php echo $row->is_winner ? 'is-winner' : ''; ?>">
						<span class="film-oscars-box__year"><?php echo esc_html( $row->ceremony_year ); ?></span>
						<span class="film-oscars-box__category"><?php echo esc_html( $row->category ); ?></span>
						<?php if ( $row->nominee ) : ?>
							<span class="film-oscars-box__nominee"><?php echo esc_html( $row->nominee ); ?></span>
						<?php endif; ?>
						<?php if ( $row->is_winner ) : ?>
							<span class="oscar-winner-badge">&#9733; <?php esc_html_e( 'Winner', 'lunara-oscars' ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}
}
