import filtersInitializer from './filters-initializer';

// Includes
import eproCompat from 'includes/epro-compat';

"use strict";

//JetSmartFilters
window.JetSmartFilters = filtersInitializer;

// Init filters
$(document).ready(function () {
	window.JetSmartFilters.initializeFilters();

	// initialize elementor PRO widgets post rendered processing
	eproCompat.init();

	//console.log(window.JetSmartFilters.filterGroups);
});

// Reinit filters events
$(window).on('jet-popup/render-content/ajax/success', function (evt, popup) {
	const popupFilters = window.JetSmartFilters.findFilters($('#jet-popup-' + popup.popup_id));

	if (popupFilters.length)
		window.JetSmartFilters.initializeFilters();
});