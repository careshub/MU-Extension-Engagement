(function ( $ ) {
	"use strict";
	var meta_image_frame_csv;

	$('#ext-import-file-upload').on( 'click', function(e){
		e.preventDefault();

		// If the frame already exists, just re-open it.
		if ( meta_image_frame_csv ) {
			meta_image_frame_csv.open();
			return;
		}

		// Sets up the media library frame if needed
		meta_image_frame_csv = new wp.media.view.MediaFrame.Select({
			title: "Choose or Upload a CSV",
			button: { text:  "Use this file" },
			library: { type: "text/csv" },
			multiple: false
		});

		// Runs when a file is selected.
		meta_image_frame_csv.on('select', function(){
			// Grabs the attachment selection and creates a JSON representation of the model.
			var media_attachment = meta_image_frame_csv.state().get('selection').first().toJSON();
			// Sends the attachment URL to our custom image input field.
			// $("#import_csv_filename").empty().html(media_attachment.filename);
			// $("#import_csv_id").val(media_attachment.id);
			// $("#import_csv_url").val(media_attachment.url);
			// console.log( media_attachment );

			$.ajax({
				url: ajaxurl,
				data: {
					'action': 'engagement_importer_check_file',
					'attachment_id': media_attachment.id,
					'_nonce': $("#engagement-ajax-nonce").val(),
				},
				cache: false
			}).then( function( response ){ 
				if ( ! response.success ) {
					console.log( 'failed!' );
					console.log( response );
				} else {
					// Output best guess
					$("#import_csv_filename").empty().html( media_attachment.filename );
					$("#program_source_calc").empty().html( response.data.data_source );
					$("#import_type_calc").empty().html( response.data.import_type );
					// Store attachment ID to use
					$("#import_csv_attachment_id").val( media_attachment.id );
					// Enable import buttons
					$( ".import-actions" ).removeAttr( "disabled" );
				}
			});
		});

		// Opens the media library frame.
		meta_image_frame_csv.open();
	});

	$('#import-start').on( 'click', function(e){
		e.preventDefault();
		$( ".import-actions" ).attr( "disabled", "disabled" );

		$.ajax({
			url: ajaxurl,
			data: {
				'action': 'engagement_run_importer',
				'attachment_id': $("#import_csv_attachment_id").val(),
				'_nonce': $("#engagement-ajax-nonce").val(),
			},
			cache: false
		}).then( function( response ){ 
			if ( ! response.success ) {
				console.log( 'failed!' );
				console.log( response );
			} else {
				// Show the results of our important work.
				var results = $( "#import-results" );
				results.empty();
				$.each( response.data, function( index, value ) {
					$( "<li />" ).html( value ).appendTo( results );
				});
			}
		});
	});
}(jQuery));