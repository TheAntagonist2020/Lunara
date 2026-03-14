/**
 * Lunara Theme — main.js
 *
 * Handles: mobile navigation toggle, live Oscars table filtering.
 */
( function () {
	'use strict';

	/* -----------------------------------------------------------------------
	   Mobile navigation toggle
	   ----------------------------------------------------------------------- */
	document.addEventListener( 'DOMContentLoaded', function () {
		var toggle = document.querySelector( '.menu-toggle' );
		var nav    = document.querySelector( '.main-navigation' );

		if ( ! toggle || ! nav ) {
			return;
		}

		toggle.addEventListener( 'click', function () {
			var isOpen = nav.classList.toggle( 'is-open' );
			toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );

			var i18n = ( window.lunaraData && window.lunaraData.i18n ) || {};
			toggle.setAttribute(
				'aria-label',
				isOpen
					? ( i18n.closeMenu || 'Close menu' )
					: ( i18n.openMenu  || 'Open menu'  )
			);
		} );

		// Close nav when focus leaves it (keyboard accessibility).
		nav.addEventListener( 'focusout', function ( e ) {
			if ( ! nav.contains( e.relatedTarget ) ) {
				nav.classList.remove( 'is-open' );
				toggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );

		// Close nav when clicking outside.
		document.addEventListener( 'click', function ( e ) {
			if ( ! nav.contains( e.target ) && ! toggle.contains( e.target ) ) {
				nav.classList.remove( 'is-open' );
				toggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	} );

	/* -----------------------------------------------------------------------
	   Oscars table — live filter by year and category
	   ----------------------------------------------------------------------- */
	document.addEventListener( 'DOMContentLoaded', function () {
		var filterYear     = document.getElementById( 'oscars-filter-year' );
		var filterCategory = document.getElementById( 'oscars-filter-category' );
		var filterSearch   = document.getElementById( 'oscars-filter-search' );
		var table          = document.querySelector( '.oscars-table' );

		if ( ! table ) {
			return;
		}

		/**
		 * Apply all active filters to the table rows.
		 */
		function applyFilters() {
			var year     = filterYear     ? filterYear.value.toLowerCase()     : '';
			var category = filterCategory ? filterCategory.value.toLowerCase() : '';
			var search   = filterSearch   ? filterSearch.value.toLowerCase()   : '';

			var rows = table.querySelectorAll( 'tbody tr' );

			rows.forEach( function ( row ) {
				var rowYear     = ( row.dataset.year     || '' ).toLowerCase();
				var rowCategory = ( row.dataset.category || '' ).toLowerCase();
				var rowText     = row.textContent.toLowerCase();

				var matchYear     = ! year     || rowYear.indexOf( year )         !== -1;
				var matchCategory = ! category || rowCategory.indexOf( category ) !== -1;
				var matchSearch   = ! search   || rowText.indexOf( search )       !== -1;

				row.style.display = ( matchYear && matchCategory && matchSearch ) ? '' : 'none';
			} );
		}

		if ( filterYear )     { filterYear.addEventListener( 'change', applyFilters ); }
		if ( filterCategory ) { filterCategory.addEventListener( 'change', applyFilters ); }
		if ( filterSearch )   { filterSearch.addEventListener( 'input',  applyFilters ); }
	} );

} )();
