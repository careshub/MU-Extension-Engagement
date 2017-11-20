(function ( $ ) {
	"use strict";

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
		
	// form submission add engagement returning blank form with validation (cmb2, not sure what's up..)
	$("form#program_information").on("submit", function(e){
		var this_form_obj = $( this );
		var validation_html = "";
		var validation_error = false;
		
		// if no title, no proceed
		if( $("#_muext_title").val() == "" ){
			validation_html += "Error: Engagement requires a title. <br/>";
			validation_error = true;
		}
		// if no description, no proceed
		if( (!($.trim($('#content').val())==="")) ){
			validation_html += "Error: Engagement requires a description. <br/>";
			validation_error = true;
		}
		
		//if no affiliation, no proceed
		
		// if no theme, no proceed
		
		
		// if validation error, no proceed
		if( validation_error == true ){
			e.preventDefault();
			console.log( validation_html );
		}
		
	});
	
	
	
	
	
}(jQuery));
