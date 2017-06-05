var placeSearch, autocomplete;
function initAutocomplete() {
	// Create the autocomplete object, restricting the search to geographical
	// location types.
	autocomplete = new google.maps.places.Autocomplete(
	/** @type {!HTMLInputElement} */(document.getElementById('_muext_location_text')),
	{types: ['geocode']});

	// When the user selects an address from the dropdown, populate the address
	// fields in the form.
	autocomplete.addListener('place_changed', placeChangedCallback);
	console.log( 'autocomplete is running' );
}

function placeChangedCallback() {
	// Get the place details from the autocomplete object.
	var place = autocomplete.getPlace();
	console.log( place );

	// Return values from the API
	// Street number => street_number
	// Street name => route
	// City => locality
	// County => administrative_area_level_2
	// State => administrative_area_level_1
	// Country => country
	// ZIP code => postal_code
	// The formatted address is dropped into the box.
	for (var i = 0; i < place.address_components.length; i++) {
		var addressType = place.address_components[i].types[0];
		if (componentForm[addressType]) {
			var val = place.address_components[i][componentForm[addressType]];
			document.getElementById(addressType).value = val;
		}
	}
}

// Google sample for grabbing details
function fillInAddress() {
	// Get the place details from the autocomplete object.
	var place = autocomplete.getPlace();

	for (var component in componentForm) {
		document.getElementById(component).value = '';
		document.getElementById(component).disabled = false;
	}

	// Get each component of the address from the place details
	// and fill the corresponding field on the form.
	for (var i = 0; i < place.address_components.length; i++) {
		var addressType = place.address_components[i].types[0];
		if (componentForm[addressType]) {
			var val = place.address_components[i][componentForm[addressType]];
			document.getElementById(addressType).value = val;
		}
	}
}

(function ( $ ) {
	"use strict";
}(jQuery));