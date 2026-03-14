<?php
/**
 * Oscars Admin — WP-Admin list table, add/edit/delete UI.
 *
 * Registers an admin menu page under "Lunara" for managing the Oscars database.
 *
 * @package LunaraOscars
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lunara_Oscars_Admin {

	/** Admin page slug. */
	const PAGE_SLUG = 'lunara-oscars';

	/** Nonce action for add/edit form. */
	const NONCE_ACTION = 'lunara_oscars_save';

	/** Nonce name. */
	const NONCE_NAME = 'lunara_oscars_nonce';

	/** Register hooks. */
	public static function init(): void {
		add_action( 'admin_menu',           array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_post_lunara_oscars_save',   array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_lunara_oscars_delete', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/** Register the admin menu. */
	public static function register_menu(): void {
		add_menu_page(
			esc_html__( 'Oscars Database', 'lunara-oscars' ),
			esc_html__( 'Oscars DB', 'lunara-oscars' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_list_page' ),
			'dashicons-awards',
			30
		);

		add_submenu_page(
			self::PAGE_SLUG,
			esc_html__( 'All Nominations', 'lunara-oscars' ),
			esc_html__( 'All Nominations', 'lunara-oscars' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_list_page' )
		);

		add_submenu_page(
			self::PAGE_SLUG,
			esc_html__( 'Add Nomination', 'lunara-oscars' ),
			esc_html__( 'Add Nomination', 'lunara-oscars' ),
			'manage_options',
			self::PAGE_SLUG . '-add',
			array( __CLASS__, 'render_add_page' )
		);
	}

	/** Enqueue admin styles/scripts only on our pages. */
	public static function enqueue_assets( string $hook ): void {
		$our_hooks = array(
			'toplevel_page_' . self::PAGE_SLUG,
			'oscars-db_page_' . self::PAGE_SLUG . '-add',
		);
		if ( ! in_array( $hook, $our_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'lunara-oscars-admin',
			LUNARA_OSCARS_URI . 'admin/admin.css',
			array(),
			LUNARA_OSCARS_VERSION
		);
	}

	/* -----------------------------------------------------------------------
	   List page
	   ----------------------------------------------------------------------- */

	/** Render the nominations list table. */
	public static function render_list_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'lunara-oscars' ) );
		}

		// Read filters from GET.
		$filter_year     = isset( $_GET['filter_year'] )     ? absint( $_GET['filter_year'] )                          : 0;
		$filter_category = isset( $_GET['filter_category'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_category'] ) ) : '';
		$filter_winner   = isset( $_GET['filter_winner'] )   ? (int) $_GET['filter_winner']                           : -1;
		$search          = isset( $_GET['s'] )               ? sanitize_text_field( wp_unslash( $_GET['s'] ) )         : '';
		$paged           = isset( $_GET['paged'] )           ? max( 1, absint( $_GET['paged'] ) )                     : 1;

		$data      = Lunara_Oscars_Database::query( array(
			'ceremony_year' => $filter_year,
			'category'      => $filter_category,
			'is_winner'     => $filter_winner,
			'search'        => $search,
			'per_page'      => 50,
			'page'          => $paged,
		) );
		$years      = Lunara_Oscars_Database::get_years();
		$categories = Lunara_Oscars_Database::get_categories();
		$total_pages = (int) ceil( $data['total'] / 50 );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Oscars Database', 'lunara-oscars' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-add' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add Nomination', 'lunara-oscars' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php self::render_admin_notices(); ?>

			<!-- Filters -->
			<form method="get" id="oscars-filter-form">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
				<div class="tablenav top">
					<div class="alignleft actions">
						<select name="filter_year">
							<option value=""><?php esc_html_e( 'All Years', 'lunara-oscars' ); ?></option>
							<?php foreach ( $years as $y ) : ?>
								<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $filter_year, $y ); ?>><?php echo esc_html( $y ); ?></option>
							<?php endforeach; ?>
						</select>

						<select name="filter_category">
							<option value=""><?php esc_html_e( 'All Categories', 'lunara-oscars' ); ?></option>
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $filter_category, $cat ); ?>><?php echo esc_html( $cat ); ?></option>
							<?php endforeach; ?>
						</select>

						<select name="filter_winner">
							<option value="-1" <?php selected( $filter_winner, -1 ); ?>><?php esc_html_e( 'All Results', 'lunara-oscars' ); ?></option>
							<option value="1"  <?php selected( $filter_winner, 1 ); ?>><?php esc_html_e( 'Winners Only', 'lunara-oscars' ); ?></option>
							<option value="0"  <?php selected( $filter_winner, 0 ); ?>><?php esc_html_e( 'Nominees Only', 'lunara-oscars' ); ?></option>
						</select>

						<?php submit_button( esc_html__( 'Filter', 'lunara-oscars' ), 'secondary', 'filter_action', false ); ?>
					</div>

					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php
							printf(
								/* translators: %d: number of items */
								esc_html( _n( '%d item', '%d items', $data['total'], 'lunara-oscars' ) ),
								(int) $data['total']
							);
							?>
						</span>
					</div>
				</div>

				<p class="search-box">
					<label class="screen-reader-text" for="oscars-search-input"><?php esc_html_e( 'Search Nominations:', 'lunara-oscars' ); ?></label>
					<input type="search" id="oscars-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
					<?php submit_button( esc_html__( 'Search Nominations', 'lunara-oscars' ), 'secondary', '', false ); ?>
				</p>
			</form>

			<!-- Table -->
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width:60px"><?php esc_html_e( 'ID', 'lunara-oscars' ); ?></th>
						<th style="width:70px"><?php esc_html_e( 'Year', 'lunara-oscars' ); ?></th>
						<th><?php esc_html_e( 'Category', 'lunara-oscars' ); ?></th>
						<th><?php esc_html_e( 'Film', 'lunara-oscars' ); ?></th>
						<th><?php esc_html_e( 'Nominee', 'lunara-oscars' ); ?></th>
						<th style="width:80px"><?php esc_html_e( 'Winner?', 'lunara-oscars' ); ?></th>
						<th style="width:130px"><?php esc_html_e( 'Actions', 'lunara-oscars' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $data['results'] ) ) : ?>
						<tr>
							<td colspan="7"><?php esc_html_e( 'No nominations found.', 'lunara-oscars' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $data['results'] as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->id ); ?></td>
								<td><?php echo esc_html( $row->ceremony_year ); ?></td>
								<td><?php echo esc_html( $row->category ); ?></td>
								<td><?php echo esc_html( $row->film ); ?></td>
								<td><?php echo esc_html( $row->nominee ); ?></td>
								<td><?php echo $row->is_winner ? '<span style="color:#f0c040">&#9733;</span>' : '&ndash;'; ?></td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-add&id=' . $row->id ) ); ?>">
										<?php esc_html_e( 'Edit', 'lunara-oscars' ); ?>
									</a>
									|
									<a href="<?php echo esc_url( wp_nonce_url(
										admin_url( 'admin-post.php?action=lunara_oscars_delete&id=' . $row->id ),
										'lunara_oscars_delete_' . $row->id
									) ); ?>"
									   onclick="return confirm('<?php esc_attr_e( 'Delete this nomination?', 'lunara-oscars' ); ?>');"
									   style="color:#a00">
										<?php esc_html_e( 'Delete', 'lunara-oscars' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						echo paginate_links( array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
							'total'     => $total_pages,
							'current'   => $paged,
						) );
						?>
					</div>
				</div>
			<?php endif; ?>
		</div><!-- .wrap -->
		<?php
	}

	/* -----------------------------------------------------------------------
	   Add / Edit page
	   ----------------------------------------------------------------------- */

	/** Render the add/edit form. */
	public static function render_add_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'lunara-oscars' ) );
		}

		$id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$row = $id ? Lunara_Oscars_Database::get( $id ) : null;

		$ceremony_year = $row ? esc_attr( $row->ceremony_year ) : esc_attr( (string) (int) gmdate( 'Y' ) );
		$category      = $row ? esc_attr( $row->category )      : '';
		$film          = $row ? esc_attr( $row->film )           : '';
		$nominee       = $row ? esc_attr( $row->nominee )        : '';
		$is_winner     = $row ? (bool) $row->is_winner           : false;
		$notes         = $row ? esc_textarea( $row->notes )      : '';
		?>
		<div class="wrap">
			<h1><?php echo $id ? esc_html__( 'Edit Nomination', 'lunara-oscars' ) : esc_html__( 'Add Nomination', 'lunara-oscars' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>">&laquo; <?php esc_html_e( 'Back to list', 'lunara-oscars' ); ?></a>
			<hr>

			<?php self::render_admin_notices(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lunara_oscars_save">
				<input type="hidden" name="id"     value="<?php echo esc_attr( (string) $id ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<table class="form-table">
					<tr>
						<th><label for="ceremony_year"><?php esc_html_e( 'Ceremony Year', 'lunara-oscars' ); ?></label></th>
						<td><input type="number" id="ceremony_year" name="ceremony_year" value="<?php echo $ceremony_year; ?>" min="1928" max="<?php echo esc_attr( (string) ( (int) gmdate( 'Y' ) + 2 ) ); ?>" required class="small-text"></td>
					</tr>
					<tr>
						<th><label for="category"><?php esc_html_e( 'Category', 'lunara-oscars' ); ?></label></th>
						<td><input type="text" id="category" name="category" value="<?php echo $category; ?>" required class="regular-text" placeholder="e.g. Best Picture"></td>
					</tr>
					<tr>
						<th><label for="film"><?php esc_html_e( 'Film', 'lunara-oscars' ); ?></label></th>
						<td><input type="text" id="film" name="film" value="<?php echo $film; ?>" required class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="nominee"><?php esc_html_e( 'Nominee', 'lunara-oscars' ); ?></label></th>
						<td><input type="text" id="nominee" name="nominee" value="<?php echo $nominee; ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Winner?', 'lunara-oscars' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="is_winner" value="1" <?php checked( $is_winner ); ?>>
								<?php esc_html_e( 'This nomination won the award', 'lunara-oscars' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="notes"><?php esc_html_e( 'Notes', 'lunara-oscars' ); ?></label></th>
						<td><textarea id="notes" name="notes" rows="4" class="large-text"><?php echo $notes; ?></textarea></td>
					</tr>
				</table>

				<?php submit_button( $id ? esc_html__( 'Update Nomination', 'lunara-oscars' ) : esc_html__( 'Add Nomination', 'lunara-oscars' ) ); ?>
			</form>
		</div>
		<?php
	}

	/* -----------------------------------------------------------------------
	   Form handlers
	   ----------------------------------------------------------------------- */

	/** Handle add/edit form submission. */
	public static function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'lunara-oscars' ) );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$data = array(
			'ceremony_year' => isset( $_POST['ceremony_year'] ) ? absint( $_POST['ceremony_year'] )                          : 0,
			'category'      => isset( $_POST['category'] )      ? sanitize_text_field( wp_unslash( $_POST['category'] ) )    : '',
			'film'          => isset( $_POST['film'] )          ? sanitize_text_field( wp_unslash( $_POST['film'] ) )        : '',
			'nominee'       => isset( $_POST['nominee'] )       ? sanitize_text_field( wp_unslash( $_POST['nominee'] ) )     : '',
			'is_winner'     => ! empty( $_POST['is_winner'] ) ? 1 : 0,
			'notes'         => isset( $_POST['notes'] )         ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) )   : '',
		);

		if ( $id ) {
			$result = Lunara_Oscars_Database::update( $id, $data );
			$status = ( false !== $result ) ? 'updated' : 'error';
		} else {
			$result = Lunara_Oscars_Database::insert( $data );
			$status = $result ? 'added' : 'error';
			if ( $result ) {
				$id = (int) $result;
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-add&id=' . $id . '&status=' . $status ) );
		exit;
	}

	/** Handle delete action. */
	public static function handle_delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'lunara-oscars' ) );
		}

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'lunara_oscars_delete_' . $id );

		if ( $id ) {
			Lunara_Oscars_Database::delete( $id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&status=deleted' ) );
		exit;
	}

	/* -----------------------------------------------------------------------
	   Admin notices
	   ----------------------------------------------------------------------- */

	/** Display status notices based on query-string parameter. */
	private static function render_admin_notices(): void {
		if ( ! isset( $_GET['status'] ) ) {
			return;
		}
		$status = sanitize_key( $_GET['status'] );

		$messages = array(
			'added'   => array( 'success', esc_html__( 'Nomination added successfully.', 'lunara-oscars' ) ),
			'updated' => array( 'success', esc_html__( 'Nomination updated successfully.', 'lunara-oscars' ) ),
			'deleted' => array( 'success', esc_html__( 'Nomination deleted.', 'lunara-oscars' ) ),
			'error'   => array( 'error',   esc_html__( 'An error occurred. Please try again.', 'lunara-oscars' ) ),
		);

		if ( isset( $messages[ $status ] ) ) {
			list( $type, $msg ) = $messages[ $status ];
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $type ), esc_html( $msg ) );
		}
	}
}
