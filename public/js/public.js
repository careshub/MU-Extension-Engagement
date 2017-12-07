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
				//location.reload();
			});
				
		}
	});
	
	
	
	
}(jQuery));
