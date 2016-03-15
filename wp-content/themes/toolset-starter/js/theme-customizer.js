(function ($) {


	//Update site color in real time...
	wp.customize('logo', function (value) {
		value.bind(function (newval) {
			$('.header-nav .logo').html('<img src="'+ newval +'" alt="">');
			_.defer(function(){
				$('.header-background-image img').css({
					'min-height' : $('.js-header-height').height(),
					'margin-bottom' : -$('.js-header-height').height()
				});
			});
			console.log($('.js-header-height').height());

		});
	});


	$('body').append('<style id="custom-ref-style-life"></style>');
	wp.customize('primary_color', function (value) {
		value.bind(function (newval) {
			window.parent.jQuery( '#customize-control-primary_color' ).find( 'input.wp-color-picker' ).trigger( 'change' );
		});
	});

	wp.customize( 'setting_custom_css', function (value) {
		value.bind( function (newval) {
			$.ajax({
				url: toolset.ajaxurl,
				type: 'POST',
				data: ({ action: 'toolset_sanitize_css_ajax', customcss: '' + newval + '', ajax: 1 }),
				success: function (response) {
					if( ! $( '#toolset-custom-css' ).length ) {
						$( '<style id="toolset-custom-css" type="text/css"></style>' ).appendTo( 'body' );
					}
					$( '#toolset-custom-css' ).html( response );
				}
			});
		});
	});

})(jQuery);