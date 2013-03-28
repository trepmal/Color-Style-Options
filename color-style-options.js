jQuery(document).ready( function($) {

	if ( $('.cso_colorpick').length > 0 ) {
		cpargs = {
			change: function( event, ui ) {
				jQuery(this).val( ui.color.toString() );
			}
		};

		jQuery('.cso_colorpick').wpColorPicker( cpargs );
	}

	$('.wrap').on('click', '.cso_remove', function() {
		$(this).closest('p').animate({
			height: '0',
			opacity: '0'
		}, function() { 
			$(this).remove();
			$('.unsaved').show();
		});
	});

	if ( $('#cso_add_row').length > 0 ) {
		$('#cso_add_row').click( function (ev) {
			ev.preventDefault();
			$(this).closest('p').before( '<p id="cso_ph"><img src="'+ cso.loading +'" id="cso_loading" alt="loading"></p>' );

			$.post( cso.ajaxurl, { action: 'fetch_uniqid' }, function( res ) { 

				row = cso.row_html.replace(/\[%id%\]/g, '['+res+']');
				$('#cso_ph').replaceWith( row );
				$('.unsaved').show();

			});
		});
	}

});