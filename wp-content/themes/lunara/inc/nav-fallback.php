<?php
/**
 * Fallback nav menu callback.
 *
 * Used when no menu is assigned to the 'primary' location.
 *
 * @package Lunara
 * @since   1.0.0
 */

if ( ! function_exists( 'lunara_default_nav_menu' ) ) {
	/**
	 * Output a minimal nav menu containing only a Home link.
	 */
	function lunara_default_nav_menu() {
		echo '<ul id="primary-menu"><li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'lunara' ) . '</a></li></ul>';
	}
}
