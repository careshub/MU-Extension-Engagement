var placeSearch, autocomplete, autocompletes = {};
var fieldPrefix = '_muext_';
var locationFields = {
	street_number: 'short_name',
	route: 'long_name',
	locality: 'long_name',
	administrative_area_level_2: 'long_name',
	administrative_area_level_1: 'long_name',
	country: 'long_name',
	postal_code: 'short_name'
};
function initAutocomplete() {
	// Create the autocomplete object, restricting the search to geographical
	// location types.
	
	//trying to get this to listen to all location fields
	//var location_fields = jQuery("[id^=_muext_location_text]:not([id$=_repeat]" ); //without group. repeat fields have a containing div ending in _repeat
	//var location_fields = jQuery("[id^=_muext_location_group_0__muext_location_text]:not([id$=_repeat]" ); //repeat fields have a containing div ending in _repeat
	var location_fields = jQuery("[id^=_muext_location_group_] [id$=_muext_location_text]" ); //repeat fields have a containing div ending in _repeat
	console.log( location_fields );
	//var autocompletes = {};
	
	//for each location_fields, get thee to google
	jQuery.each( location_fields, function( i, v ){
		//do we have the has-autocomplete class? TODO: fix this!
		if( jQuery(v).hasClass("has-autocomplete") ){
			//return true; //go to next location in $.each
			//console.log( 'jas');
		}
		
		//get this location field id
		var this_id = jQuery(v).attr("id");
		console.log( this_id );
		
		//get the trailing number (_muext_location_text_0)
		var res = this_id.replace("__muext_location_text", "");
		var under = res.lastIndexOf('_');
		var int_maybe = parseInt( this_id.substring( under + 1 ) );
		
		//do we need this int check?  It's either an int (and therefore a repeated location field) or not (and therefore an original solo location field)
		if( Number.isInteger( int_maybe ) ){
			var digit = int_maybe;
		} else {
			var digit = "";
		}
		
		autocompletes['autocomplete_' + digit] = new google.maps.places.Autocomplete(
		//autocomplete = new google.maps.places.Autocomplete(
		/** @type {!HTMLInputElement} */(document.getElementById( this_id )),
		{types: ['geocode']});

		// When the user selects an address from the dropdown, populate the address
		// fields in the form.
		//autocomplete.addListener('place_changed', placeChangedCallback);
		autocompletes['autocomplete_' + digit].addListener('place_changed', function(){
			
			placeChangedCallbackNumbered( autocompletes['autocomplete_' + digit], digit );
			
		});
		console.log( 'autocomplete is running' );
		
		//add class to input, to let us know not to autocomplete it if not
		jQuery(v).addClass("has-autocomplete");
		
	});
	
	
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
	for (var field in locationFields) {
		document.getElementById(fieldPrefix + field).value = '';
		document.getElementById(fieldPrefix + field).disabled = false;
	}
	for (var i = 0; i < place.address_components.length; i++) {
		var addressType = place.address_components[i].types[0];
		if (locationFields[addressType]) {
			var val = place.address_components[i][locationFields[addressType]];
			document.getElementById(fieldPrefix + addressType).value = val;
		}
	}
	// Latitude and Longitude
	document.getElementById(fieldPrefix + "latitude").value = place.geometry.location.lat();
	document.getElementById(fieldPrefix + "longitude").value = place.geometry.location.lng();
}

function placeChangedCallbackNumbered( which_autocomplete, which_index ) {
	// Get the place details from the autocomplete object.
	var place = which_autocomplete.getPlace();
	console.log( which_autocomplete );
	
	//group prefix string
	var groupPrefix = '_muext_location_group_' + which_index + '_';
	
	//get the trailing number, if any
	//var under = which_autocomplete.lastIndexOf('_');
	//var int_maybe = which_autocomplete.substring( under + 1 ); //may be empty string if original location (non repeater)
	//console.log( int_maybe );
	//if it's not an empty string, prepend with _
	if( Number.isInteger( which_index ) ){
		var field_suffix = '_' + which_index; 
	} else {
		var field_suffix = which_index; 
	}
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
	for (var field in locationFields) {
		console.log( groupPrefix + fieldPrefix + field ); //_muext_street_number
		
		// if it doesn't exist yet, create it (on new location groups)
		if( !document.getElementById(groupPrefix + fieldPrefix + field) ){
			var new_field = '<input class="cmb2-hidden" name="_muext_location_group[' + which_index + '][' + fieldPrefix + field + ']" id="' + groupPrefix + fieldPrefix + field + '" value="" data-groupid="_muext_location_group" data-iterator="' + which_index + '" type="hidden">';
			$("#program_information").append( new_field );
		}
			
		document.getElementById(groupPrefix + fieldPrefix + field).value = '';
		document.getElementById(groupPrefix + fieldPrefix + field).disabled = false;
	}
	if( typeof place !== 'undefined' ){
		for (var i = 0; i < place.address_components.length; i++) {
			var addressType = place.address_components[i].types[0];
			if (locationFields[addressType]) {
				var val = place.address_components[i][locationFields[addressType]];
				document.getElementById(groupPrefix + fieldPrefix + addressType).value = val;
			}
		}
			
		// Latitude and Longitude: test for existance before assignment
		if( !document.getElementById(groupPrefix + fieldPrefix + "latitude") ){
			var new_field = '<input class="cmb2-hidden" name="_muext_location_group[' + which_index + '][' + fieldPrefix + "latitude" + ']" id="' + groupPrefix + fieldPrefix + "latitude" + '" value="" data-groupid="_muext_location_group" data-iterator="' + which_index + '" type="hidden">';
			$("#program_information").append( new_field );
		}
		if( !document.getElementById(groupPrefix + fieldPrefix + "longitude") ){
			var new_field = '<input class="cmb2-hidden" name="_muext_location_group[' + which_index + '][' + fieldPrefix + "longitude" + ']" id="' + groupPrefix + fieldPrefix + "longitude" + '" value="" data-groupid="_muext_location_group" data-iterator="' + which_index + '" type="hidden">';
			$("#program_information").append( new_field );
		}
		
		document.getElementById(groupPrefix + fieldPrefix + "latitude").value = place.geometry.location.lat();
		document.getElementById(groupPrefix + fieldPrefix + "longitude").value = place.geometry.location.lng();
		
	}
	
	// then get the geokey and geoid
	geoidlistener( which_index );
	
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

function locationRepeatListener(){
	
	//jQuery(".cmb-add-group-row").on("click", function(){ //no, this happens before the field is added, duh
	jQuery("[id^=_muext_location_group_]").on("click", function(){
			
		initAutocomplete();
		
	});
	
};

function outcomesBoxListener(){
	//hide show outcomes button
	jQuery(".wp-admin #program_information #show-outcomes-box").addClass("hidden");

	// remove 'hidden' class from Outcomes/Success stories
	jQuery(".wp-admin #program_information .cmb2-id--muext-outcome-text").removeClass("hidden");

}


// listens to change to add geokey and geoid to 
function geoidlistener( data_iter ){
	
	// what engagement are we on?
	var this_engagement_id = $("form#program_information [name='object_id']").val(); // good thing we're setting the object id before submit!

	// we are good to save, but first we need to create geo_key entry.  
	var location_groups_all = $("#_muext_location_group_repeat"); //encompasses all location groups/items
	var location_groups_each = $("#_muext_location_group_repeat .cmb-repeatable-grouping"); //encompasses EACH location groups/items
	
	var which_iterator = 0; //init var
	
	// for each location group:
	//$.each( location_groups_each, function(){
		// which location group?
		//which_iterator = jQuery(this).attr('data-iterator');
		which_iterator = data_iter;
		
		// find the region, get corresponding geo_key
		//var which_region = $( this ).find(".muext_region_class option").filter(":selected").val();
		var which_region = $("#_muext_location_group_" + which_iterator + "__muext_region option").filter(":selected").val();
		var geo_key = getGeoKey( which_region );
		
		console.log( which_iterator);
		console.log( which_region);
		console.log( geo_key);
		
		// assign geo_key after testing for input existance (b/c cmb2 not adding geo_key and id for repeater group..)
		if( $("[name='_muext_location_group[" + which_iterator + "][_muext_geo_key]']").length == 0 ){
			//cmb2 not adding this field!  Why..
			var new_geo_key_field = '<input class="cmb2-hidden" name="_muext_location_group[' + which_iterator + '][_muext_geo_key]" id="_muext_location_group_' + which_iterator + '__muext_geo_key" value="" data-groupid="_muext_location_group" data-iterator="' + which_iterator + '" type="hidden">';
			console.log( new_geo_key_field );
			$("#program_information").append( new_geo_key_field );
		}
		if( $("[name='_muext_location_group[" + which_iterator + "][_muext_geo_id]']").length == 0 ){
			//cmb2 not adding this field!  Why..
			var new_geo_id_field = '<input class="cmb2-hidden" name="_muext_location_group[' + which_iterator + '][_muext_geo_id]" id="_muext_location_group_' + which_iterator + '__muext_geo_id" value="" data-groupid="_muext_location_group" data-iterator="' + which_iterator + '" type="hidden">';
			$("#program_information").append( new_geo_id_field );
		}
		
		// assign geo_key to this location group's geo_key element
		$("[name='_muext_location_group[" + which_iterator + "][_muext_geo_key]']").val(geo_key);
		
		// do we have a lat/long?
		var latitude = $("[name='_muext_location_group[" + which_iterator + "][_muext_latitude]']").val();
		var longitude = $("[name='_muext_location_group[" + which_iterator + "][_muext_longitude]']").val();
		
		// if we have lat, long and geo_key, get geoid
		if ( geo_key == '010' ){ // default to US geoid if US geo_key and no lat/long 
			var geoid = '01000US'
			$("[name='_muext_location_group[" + which_iterator + "][_muext_geo_id]']").val(geoid);
		} else if( latitude && longitude && geo_key ){
			//get geo id via engagements api
			var services_params = {
				lat		: latitude,
				lon		: longitude
			};
				
			services_api("get", "api-location/v1/geoid/" + geo_key, services_params, function (data) {
				
				console.log( data );
				// add to front end for taxonomification
				$("[name='_muext_location_group[" + which_iterator + "][_muext_geo_id]']").val(data);
				
			});
			
		} else if ( geo_key == '040' ){ // default to MO geoid if state geo_key and no lat/long 
			var geoid = '04000US29'
			$("[name='_muext_location_group[" + which_iterator + "][_muext_geo_id]']").val(geoid);
		}
	//});
	
}

// geokey lookup
function getGeoKey( which_region ){
	switch( which_region ){
		case 'county':
			return '050';
		case 'school_district':
			return '970';
		case 'zipcode':
			return '860';
		case 'state':
			return '040';
		case 'national':
			return '010';
		case 'city_town':
		case 'other':
			return '160';
	}
}

/**
* Send a request to the services.engagementnetwork.org API service to get data.
* @param {string} service - API endpoint and parameters.
* @param {object} data - The data posted to API.
* @param {requestCallback} callback - The callback function to execute after the API request is succefully completed.
* @param {requestCallback} [fallback] - The callback function to execute when the API request returns an error.
*/
function services_api(type, service, data, callback, fallback) {
	var param = {
		type: type,
		url: "https://services.engagementnetwork.org/" + service,
		dataType: "json",
		contentType: "application/json; charset=utf-8",
		crossDomain: true,
		success: callback,
		error: fallback || $.noop
	};
	if (data && typeof data !== "undefined") {
		if (type === "post") {
			param.data = JSON.stringify(data);
		} else {
			param.url += "?" + $.param(data);
		}
	}
	$.ajax(param);
}


(function ( $ ) {
	"use strict";
	
	$(document).ready(function() {

		locationRepeatListener();

		outcomesBoxListener();
		
		// listen to region change (OR location change in google places api code) to trigger geoid info
		$("#program_information .muext_region_class select").on("change", function(){
			var dataiter = jQuery(this).parents(".cmb-repeatable-grouping").attr("data-iterator");
			geoidlistener( dataiter );
		});
	
	});
}(jQuery));