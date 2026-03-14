<?php
/**
 * Oscars Database — handles all database interactions.
 *
 * Creates and manages the custom `{prefix}lunara_oscars` table which stores
 * every nomination / winner for every Academy Awards ceremony.
 *
 * Schema
 * ------
 * id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
 * ceremony_year SMALLINT UNSIGNED NOT NULL          – e.g. 2024 (96th ceremony)
 * category      VARCHAR(150) NOT NULL               – e.g. "Best Picture"
 * film          VARCHAR(255) NOT NULL               – e.g. "Oppenheimer"
 * nominee       VARCHAR(255) NOT NULL               – person / entity nominated
 * is_winner     TINYINT(1) NOT NULL DEFAULT 0       – 1 = won
 * notes         TEXT                                – optional context
 * created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
 * updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 *
 * @package LunaraOscars
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lunara_Oscars_Database {

	/** Table name without prefix. */
	const TABLE = 'lunara_oscars';

	/** Option key storing the installed DB version. */
	const DB_VERSION_OPTION = 'lunara_oscars_db_version';

	/** Current schema version. Increment when the schema changes. */
	const DB_VERSION = '1.0';

	/* -----------------------------------------------------------------------
	   Install / Uninstall
	   ----------------------------------------------------------------------- */

	/**
	 * Create (or upgrade) the custom database table.
	 * Safe to call multiple times — uses dbDelta.
	 */
	public static function install(): void {
		global $wpdb;

		$table      = $wpdb->prefix . self::TABLE;
		$collate    = $wpdb->get_charset_collate();
		$max_index  = 191; // Safe index length for utf8mb4.

		$sql = "CREATE TABLE {$table} (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			ceremony_year SMALLINT UNSIGNED NOT NULL,
			category      VARCHAR(150) NOT NULL DEFAULT '',
			film          VARCHAR(255) NOT NULL DEFAULT '',
			nominee       VARCHAR(255) NOT NULL DEFAULT '',
			is_winner     TINYINT(1)   NOT NULL DEFAULT 0,
			notes         TEXT,
			created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY ceremony_year  (ceremony_year),
			KEY is_winner      (is_winner),
			KEY category       (category({$max_index})),
			KEY film           (film({$max_index}))
		) {$collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/** Called on plugin deactivation (no destructive action). */
	public static function deactivate(): void {}

	/**
	 * Remove the table and all options.
	 * Called only when the user clicks "Delete" in the Plugins screen.
	 */
	public static function uninstall(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . self::TABLE ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		delete_option( self::DB_VERSION_OPTION );
	}

	/* -----------------------------------------------------------------------
	   CRUD helpers
	   ----------------------------------------------------------------------- */

	/**
	 * Insert a single nomination record.
	 *
	 * @param array $data {
	 *     @type int    $ceremony_year  Four-digit year.
	 *     @type string $category       Award category.
	 *     @type string $film           Film title.
	 *     @type string $nominee        Nominee name.
	 *     @type int    $is_winner      1 if winner, 0 otherwise.
	 *     @type string $notes          Optional notes.
	 * }
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$inserted = $wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'ceremony_year' => absint( $data['ceremony_year'] ?? 0 ),
				'category'      => sanitize_text_field( $data['category'] ?? '' ),
				'film'          => sanitize_text_field( $data['film']     ?? '' ),
				'nominee'       => sanitize_text_field( $data['nominee']  ?? '' ),
				'is_winner'     => isset( $data['is_winner'] ) ? (int) $data['is_winner'] : 0,
				'notes'         => sanitize_textarea_field( $data['notes'] ?? '' ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing nomination.
	 *
	 * @param int   $id   Row ID.
	 * @param array $data Columns to update (same keys as insert()).
	 * @return int|false Number of rows updated, or false on failure.
	 */
	public static function update( int $id, array $data ) {
		global $wpdb;

		$allowed = array( 'ceremony_year', 'category', 'film', 'nominee', 'is_winner', 'notes' );
		$set     = array();
		$formats = array();

		foreach ( $allowed as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}
			switch ( $field ) {
				case 'ceremony_year':
					$set[ $field ] = absint( $data[ $field ] );
					$formats[]     = '%d';
					break;
				case 'is_winner':
					$set[ $field ] = (int) $data[ $field ];
					$formats[]     = '%d';
					break;
				case 'notes':
					$set[ $field ] = sanitize_textarea_field( $data[ $field ] );
					$formats[]     = '%s';
					break;
				default:
					$set[ $field ] = sanitize_text_field( $data[ $field ] );
					$formats[]     = '%s';
			}
		}

		if ( empty( $set ) ) {
			return false;
		}

		return $wpdb->update(
			$wpdb->prefix . self::TABLE,
			$set,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);
	}

	/**
	 * Delete a nomination by ID.
	 *
	 * @param int $id Row ID.
	 * @return int|false
	 */
	public static function delete( int $id ) {
		global $wpdb;

		return $wpdb->delete(
			$wpdb->prefix . self::TABLE,
			array( 'id' => $id ),
			array( '%d' )
		);
	}

	/**
	 * Fetch a single row by ID.
	 *
	 * @param int $id Row ID.
	 * @return object|null
	 */
	public static function get( int $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . $wpdb->prefix . self::TABLE . ' WHERE id = %d LIMIT 1',
				$id
			)
		);
	}

	/**
	 * Query nominations with optional filters and pagination.
	 *
	 * @param array $args {
	 *     @type int    $ceremony_year  Filter by year.
	 *     @type string $category       Filter by category (LIKE).
	 *     @type string $film           Filter by film (LIKE).
	 *     @type int    $is_winner      1 = winners only, 0 = nominees only, -1 = all (default).
	 *     @type string $search         Full-text search across film, nominee, category.
	 *     @type string $orderby        Column to order by (default: ceremony_year).
	 *     @type string $order          ASC or DESC (default: DESC).
	 *     @type int    $per_page       Rows per page (default: 50).
	 *     @type int    $page           1-based page number (default: 1).
	 * }
	 * @return array { results: object[], total: int }
	 */
	public static function query( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'ceremony_year' => 0,
			'category'      => '',
			'film'          => '',
			'is_winner'     => -1,
			'search'        => '',
			'orderby'       => 'ceremony_year',
			'order'         => 'DESC',
			'per_page'      => 50,
			'page'          => 1,
		);

		$args   = wp_parse_args( $args, $defaults );
		$table  = $wpdb->prefix . self::TABLE;
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['ceremony_year'] ) ) {
			$where[]  = 'ceremony_year = %d';
			$params[] = absint( $args['ceremony_year'] );
		}

		if ( ! empty( $args['category'] ) ) {
			$where[]  = 'category LIKE %s';
			$params[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['category'] ) ) . '%';
		}

		if ( ! empty( $args['film'] ) ) {
			$where[]  = 'film LIKE %s';
			$params[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['film'] ) ) . '%';
		}

		if ( $args['is_winner'] >= 0 ) {
			$where[]  = 'is_winner = %d';
			$params[] = absint( $args['is_winner'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]  = '(film LIKE %s OR nominee LIKE %s OR category LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$allowed_orderby = array( 'id', 'ceremony_year', 'category', 'film', 'nominee', 'is_winner' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'ceremony_year';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$per_page        = max( 1, (int) $args['per_page'] );
		$offset          = ( max( 1, (int) $args['page'] ) - 1 ) * $per_page;

		$where_sql = implode( ' AND ', $where );

		// Total count.
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $count_sql );
		}

		// Rows — cannot use prepare() for ORDER BY / LIMIT with named placeholders.
		// We've validated $orderby and $order above; use sprintf for those.
		$rows_sql = sprintf(
			"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY %s %s LIMIT %d OFFSET %d",
			$orderby,
			$order,
			$per_page,
			$offset
		);

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_results( $wpdb->prepare( $rows_sql, $params ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_results( $rows_sql );
		}

		return array(
			'results' => $results ?: array(),
			'total'   => $total,
		);
	}

	/**
	 * Return distinct years present in the database.
	 *
	 * @return int[]
	 */
	public static function get_years(): array {
		global $wpdb;

		$rows = $wpdb->get_col(
			'SELECT DISTINCT ceremony_year FROM ' . $wpdb->prefix . self::TABLE . ' ORDER BY ceremony_year DESC'
		);

		return array_map( 'absint', $rows );
	}

	/**
	 * Return distinct categories present in the database.
	 *
	 * @return string[]
	 */
	public static function get_categories(): array {
		global $wpdb;

		$rows = $wpdb->get_col(
			'SELECT DISTINCT category FROM ' . $wpdb->prefix . self::TABLE . ' ORDER BY category ASC'
		);

		return array_map( 'sanitize_text_field', $rows );
	}

	/**
	 * Return aggregated stats for a given film title.
	 *
	 * @param string $film Film title (exact match).
	 * @return object|null { film, total_nominations, total_wins }
	 */
	public static function get_film_stats( string $film ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT film,
				        COUNT(*) AS total_nominations,
				        SUM(is_winner) AS total_wins
				 FROM ' . $wpdb->prefix . self::TABLE . '
				 WHERE film = %s
				 GROUP BY film',
				$film
			)
		);
	}
}
