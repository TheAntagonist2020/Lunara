<?php
/**
 * Plugin Name:       Lunara Oscars Database
 * Plugin URI:        https://github.com/TheAntagonist2020/Lunara
 * Description:       A bespoke Oscars database for the Lunara film website. Store nominations and winners for every Academy Awards ceremony, then display them with flexible shortcodes and a searchable admin interface.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Lunara
 * Author URI:        https://github.com/TheAntagonist2020
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lunara-oscars
 * Domain Path:       /languages
 *
 * @package LunaraOscars
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LUNARA_OSCARS_VERSION', '1.0.0' );
define( 'LUNARA_OSCARS_FILE',    __FILE__ );
define( 'LUNARA_OSCARS_DIR',     plugin_dir_path( __FILE__ ) );
define( 'LUNARA_OSCARS_URI',     plugin_dir_url( __FILE__ ) );

/* -------------------------------------------------------------------------
   Autoload classes
   ------------------------------------------------------------------------- */
require_once LUNARA_OSCARS_DIR . 'includes/class-oscars-database.php';
require_once LUNARA_OSCARS_DIR . 'includes/class-oscars-shortcodes.php';

if ( is_admin() ) {
	require_once LUNARA_OSCARS_DIR . 'admin/class-oscars-admin.php';
}

/* -------------------------------------------------------------------------
   Bootstrap
   ------------------------------------------------------------------------- */
function lunara_oscars_init() {
	load_plugin_textdomain(
		'lunara-oscars',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	Lunara_Oscars_Shortcodes::init();

	if ( is_admin() ) {
		Lunara_Oscars_Admin::init();
	}
}
add_action( 'plugins_loaded', 'lunara_oscars_init' );

/* -------------------------------------------------------------------------
   Activation / Deactivation / Uninstall
   ------------------------------------------------------------------------- */

register_activation_hook( __FILE__, array( 'Lunara_Oscars_Database', 'install' ) );
register_deactivation_hook( __FILE__, array( 'Lunara_Oscars_Database', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'Lunara_Oscars_Database', 'uninstall' ) );
