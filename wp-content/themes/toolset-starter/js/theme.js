(function ($) {
		$(window).load(function () {
			$('.header-background-image img').css({
				'min-height' : $('.js-header-height').height(),
				'margin-bottom' : -$('.js-header-height').height()
			});
		$( window ).resize(function() {
			$('.header-background-image img').css({
				'min-height' : $('.js-header-height').height(),
				'margin-bottom' : -$('.js-header-height').height()
			});
		});
	});
})(jQuery);