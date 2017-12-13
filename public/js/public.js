(function ( $ ) {
	"use strict";

	
	//listen for click inside of 'location' field (any) to Google listen
	locationRepeatListener();
	
	// listen for 'other' in region select (id ends with muext_region)
	jQuery("[id$='_muext_region']").on("click", function( ){
		otherRegionListener( this );
	});
	
	// add listener for location repeater (add button)
	jQuery(".cmb-add-group-row").on("click", function(){
		jQuery("[id$='_muext_region']").off("click", function( ){
			otherRegionListener( this );
		});
		jQuery("[id$='_muext_region']").on("click", function( ){
			otherRegionListener( this );
		});
	});
		
		
	
	// Set toggle indicators and add ARIA attributes on page load.
	$( "#page .toggle-container" ).each( function( i, item ){
		var expanded = $( item ).hasClass( "toggle-open" ),

			//on top level, ignore child class? - Mel isn't thinking this through - only 2 levels now
			trigger  = $( item ).children( ".toggle-trigger" ),
			content  = $( item ).children( ".toggle-content" ),
			hash     = new Date().getTime() + "-" + i;

		// Set the toggle indicator arrows.
		if ( expanded ) {
			$( item ).children( ".arrow" ).addClass( "arrow-down" );
		} else {
			$( item ).children( ".arrow" ).addClass( "arrow-right" );
		}

		// Make sure that the content container has a unique ID.
		if ( ! content.attr( "id" ) ) {
			content.attr( "id", "toggleable-content-container-" + hash );
		}

		// Make sure that the content trigger has a unique ID.
		if ( ! trigger.attr( "id" ) ) {
			trigger.attr( "id", "toggleable-content-trigger-" + hash );
		}

		// Add the labelled by attribute.
		content.not( "[aria-labelledby]" )
			.attr( "aria-labelledby", trigger.attr( "id" ) );

		// Let screen readers know that the trigger controls the content
		trigger.attr({
			"aria-controls": content.attr( "id" ),
			"aria-expanded": expanded,
		});

	});

	// Open and close the content container on click.
	$( "#page" ).on( "click", ".toggle-trigger", function(e) {
		e.preventDefault();
		//var toggleable = $( this ).parents( ".toggle-container" ), 
		//changing the abilities here - stricter child/parent stuff
		var toggleable = $( this ).parent( ".toggle-container" ),
			was_expanded   = toggleable.hasClass( "toggle-open" );

		// Toggle the content display and arrow direction classes.
		toggleable.toggleClass( "toggle-open toggle-closed" );
		$( this ).siblings( ".arrow" ).toggleClass( "arrow-down arrow-right" );

		$( this ).attr(	"aria-expanded", toggleable.hasClass( "toggle-open" ) );
	});

	function add_unique_id( element, i ) {
		element = $(element);
		var settings = this.settings;
		if ( ! element.attr( "id" ) ) {
			element.attr( "id", "toggleable-content-container-" + new Date().getTime() + "-" + i );
		}
	}

	// listen to region change (OR location change in google places api code) to trigger geoid info
	$("form#program_information .muext_region_class select").on("change", function(){
		// because race conditions: get data iterator of location group
		var dataiter = jQuery(this).parents(".cmb-repeatable-grouping").attr("data-iterator");
		geoidlistener( dataiter );
	});
	
	// form submission add engagement returning blank form with validation (cmb2, not sure what's up..)
	$("form#program_information").submit( function(e){
		
		//remove all error classes
		$("form#program_information input").removeClass('validationError');
		$("form#program_information textarea").removeClass('validationError');
		
		//clear messages
		$("form#program_information #validation_message").remove();
		
		var this_form_obj = $( this );
		var validation_html = "";
		var validation_error = false;
		
		// if no title, no proceed
		if( $("form#program_information #_muext_title").val() == "" ){
			validation_html += "Error: Engagement requires a Title. <br/>";
			validation_error = true;
		}
		// if no description, no proceed
		if( ($.trim($('form#program_information #content').val())==="") ){
			validation_html += "Error: Engagement requires a Description. <br/>";
			validation_error = true;
		}
		
		//if no affiliation, no proceed
		if( $('form#program_information select#affiliation' ).val() == null ){
			validation_html += "Error: Engagement requires an Affiliation. <br/>";
			validation_error = true;
		}
		
		// if no theme, no proceed
		if( $('form#program_information select#type' ).val() == null ){
			validation_html += "Error: Engagement requires an Engagement Type. <br/>";
			validation_error = true;
		}
		
		// if not engagement type
		if( $('form#program_information select#theme' ).val() == null ){
			validation_html += "Error: Engagement requires a Theme. <br/>";
			validation_error = true;
		}
		
		// if validation error, no proceed
		if( validation_error == true ){
			e.preventDefault();
			console.log( validation_html );
			
			//attach validation message to end
			var validation_html_container = "<div id='validation_message' class='form-message'>";
			validation_html_container += validation_html;
			validation_html_container += "</div>";
			
			$("form#program_information").append( validation_html_container );
			
		} else {
			
			// we're good to go!
			
		}
		
	});
	
	
	// front-end delete posts if author.  Can't use wp delete posts since we're restricting wp-admin and redirecting
	$(".delete-engagement").on("click", function(e){
		
		// are they sure they want to delete?
		if( confirm('Are you SURE you want to delete this Engagement?') ){
			
			// get the id
			var this_engagement_id = jQuery( this ).attr("data-postid");
			console.log( this_engagement_id);
			
			var delete_post_ajax = $.ajax({
				method: "DELETE",
				url: muext_restapi_details.rest_url + 'wp/v2/muext_engagement/' + this_engagement_id ,
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', muext_restapi_details.rest_nonce );
				}
			});
			
			//done!
			delete_post_ajax.done(function( data ) {
				console.log( 'posted: ' + data );
				location.reload();
			});
				
		}
	});
	
	
	
}(jQuery));


// listens to change to add geokey and geoid to 
function geoidlistener( dataiter ){
	
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
		
		which_iterator = dataiter;
		
		// find the region, get corresponding geo_key
		//var which_region = $( this ).find(".muext_region_class option").filter(":selected").val(); //for $.each
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
		// assign geo_key to this location group's geo_key element:
		$("[name='_muext_location_group[" + which_iterator + "][_muext_geo_key]']").val(geo_key);
		
		// do we have a lat/long?
		var latitude = $("[name='_muext_location_group[" + which_iterator + "][_muext_latitude]']").val();
		var longitude = $("[name='_muext_location_group[" + which_iterator + "][_muext_longitude]']").val();
		
		// if we have lat, long and geo_key, get geoid
		if( latitude && longitude && geo_key ){
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
			
		} else if ( geo_key == '010' ){ // default to US geoid if US geo_key and no lat/long 
			var geoid = '01000US'
			$("[name='_muext_location_group[" + which_iterator + "][_muext_geo_id]']").val(geoid);
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
			return '871';
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


	/*
	 * Instantiate globals for location api/fields
	 */
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

	/*
	 * google api functions, supporting functions 
	 *
	 */
	function initAutocomplete() {
		// Create the autocomplete object, restricting the search to geographical
		// location types.
		
		//trying to get this to listen to all location fields
		//var location_fields = jQuery("[id^=_muext_location_text]:not([id$=_repeat]" ); //without group. repeat fields have a containing div ending in _repeat
		//var location_fields = jQuery("[id^=_muext_location_group_0__muext_location_text]:not([id$=_repeat]" ); //repeat fields have a containing div ending in _repeat
		var location_fields = jQuery("[id^=_muext_location_group_] [id$=_muext_location_text]" ); //repeat fields have a containing div ending in _repeat
		
		//for each location_fields, get thee to google
		jQuery.each( location_fields, function( i, v ){
			//do we have the has-autocomplete class? TODO: fix this!
			if( jQuery(v).hasClass("has-autocomplete") ){
				//return true; //go to next location in $.each
				//console.log( 'jas');
			}
			
			//get this location field id
			var this_id = jQuery(v).attr("id");
			//console.log( this_id );
			
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

	// Explicit callback for THIS location field out of many
	function placeChangedCallbackNumbered( which_autocomplete, which_index ) {
		// Get the place details from the autocomplete object.
		var place = which_autocomplete.getPlace();
		console.log( which_autocomplete );
		
		//group prefix string
		var groupPrefix = '_muext_location_group_' + which_index + '_';
		var region_select_id = '_muext_location_group_' + which_index + '__muext_region';
		
		//var regions_options = [ 'city_town', 'county', 'school_district', 'zipcode', 'state', 'other' ];
		var address_options = [ 'street_number', 'route', 'locality', 'postal_code', 'administrative_area_level_2', 'administrative_area_level_1' ];
		// crosswalk btw google api address_types and internal dropdown type
		var regions_crosswalk = { 
			street_number:					"city_town", 
			route:							"city_town", 
			locality:						"city_town", 
			postal_code:					"zipcode", 
			administrative_area_level_2:	"county", 
			administrative_area_level_1:	"state"
		};
		
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
		//console.log( 'place: ' + place ); //object

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
			
			//console.log( groupPrefix + fieldPrefix + field ); // e.g.: '_muext_location_group_0__muext_street_number'
			// if it doesn't exist yet, create it (on new location groups)
			if( !document.getElementById(groupPrefix + fieldPrefix + field) ){
				var new_field = '<input class="cmb2-hidden" name="_muext_location_group[' + which_index + '][' + fieldPrefix + field + ']" id="' + groupPrefix + fieldPrefix + field + '" value="" data-groupid="_muext_location_group" data-iterator="' + which_index + '" type="hidden">';
				$("#program_information").append( new_field );
			}
			
			document.getElementById(groupPrefix + fieldPrefix + field).value = '';
			document.getElementById(groupPrefix + fieldPrefix + field).disabled = false;
		}
		
		if( typeof place !== 'undefined' ){
			
			// autofill in the form dropdown for smallest REGION specified
			var smallest_region = ''; 
			var addressTypesArray = new Array();
			
			for (var i = 0; i < place.address_components.length; i++) {
				console.log( place.address_components[i] );
				var addressType = place.address_components[i].types[0];
				
				if (locationFields[addressType]) {
					var val = place.address_components[i][locationFields[addressType]];
					document.getElementById(groupPrefix + fieldPrefix + addressType).value = val;
					
					// get all addressTypes in array
					addressTypesArray.push( addressType );
				}
				
			}
			
			// check for smallest -> largest address type and auto-select appropriate regional dropdown in this group
			console.log( addressTypesArray );
			
			// clear region option selection
			jQuery( "#" + region_select_id + ' option').prop('selected', false);
			
			// go through array, testing for smaller => larger regions
			jQuery.each( regions_crosswalk, function( region_index, region_value ){

				var this_region = region_index;
				var this_value = region_value;
				
				if( jQuery.inArray( this_region, addressTypesArray ) != -1 ){
					
					// set the region dropdown accordingly 
					jQuery( "#" + region_select_id + ' option[value="' + this_value +'"]').prop('selected', true);
					
					return false;
				}
			});
			
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

	function otherRegionListener( this_select ){

		var this_select_id = jQuery(this_select).attr("id");
		
		var parent_row = jQuery("#" +  this_select_id).parent().parent();
		
		var other_text_field = parent_row.siblings("[class*='muext-region-other']");
		
		// if user has selected 'other' as the region option for a location group, make sure the 'other' text field shows
		//if( jQuery("[id$='_muext_region'] option[value='other']").is(":selected") ){
		if( jQuery( "#" +  this_select_id + " option[value='other']").is(":selected") ){
			
			// get the textfield for other (sibling of the cmb-row parent's parent of this select)
			console.log( other_text_field);
			jQuery( other_text_field ).removeClass("hidden");
			
			
		} else {
			
			jQuery( other_text_field ).addClass("hidden");
		}
		
		// listen for the add location button, too...somewhere
		
	}

	function locationRepeatListener(){
		
		jQuery("[id^=_muext_location_group_]").on("click", function(){
			initAutocomplete();
		});
		
	};



	//IE doesn't support Number.isInteger:
	Number.isInteger = Number.isInteger || function(value) {
		return typeof value === "number" && 
			   isFinite(value) && 
			   Math.floor(value) === value;
	};
	